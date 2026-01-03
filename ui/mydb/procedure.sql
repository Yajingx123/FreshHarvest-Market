DROP procedure IF EXISTS ProcessCustomerOrder;
DROP PROCEDURE IF EXISTS staff_restock;
DROP PROCEDURE IF EXISTS staff_adjust_inventory;

DELIMITER $$

DELIMITER $$

CREATE PROCEDURE staff_restock(
    IN p_product_id INT,
    IN p_branch_id INT,
    IN p_supplier_id INT,
    IN p_quantity INT,
    IN p_unit_cost DECIMAL(10,2),
    IN p_expected_total_stock INT,
    IN p_expected_last_received DATE,
    IN p_expiry_date DATE,
    OUT o_purchase_order_id INT,
    OUT o_batch_id VARCHAR(20)
)
BEGIN
    DECLARE v_sku VARCHAR(45);
    DECLARE v_prefix VARCHAR(10);
    DECLARE v_last_batch VARCHAR(20);
    DECLARE v_next_number INT DEFAULT 1;
    DECLARE v_batch_id VARCHAR(20);
    DECLARE v_today DATE;
    DECLARE v_total DECIMAL(10,2);
    DECLARE v_clean_batch VARCHAR(50);
    DECLARE v_item_id VARCHAR(60);
    DECLARE v_current_total INT DEFAULT 0;
    DECLARE v_current_last DATE;
    DECLARE i INT DEFAULT 1;

    DECLARE EXIT HANDLER FOR SQLEXCEPTION
    BEGIN
        ROLLBACK;
        RESIGNAL;
    END;

    IF p_product_id <= 0 OR p_branch_id <= 0 OR p_supplier_id <= 0 OR p_quantity <= 0 OR p_unit_cost <= 0 THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Invalid restock inputs';
    END IF;

    START TRANSACTION;
    SET @stock_adjust_reason = NULL;
    SET @stock_adjust_staff_id = NULL;

    SELECT COALESCE(SUM(quantity_on_hand), 0), MAX(received_date)
    INTO v_current_total, v_current_last
    FROM Inventory
    WHERE product_ID = p_product_id
      AND branch_ID = p_branch_id
    FOR UPDATE;

    IF p_expected_total_stock IS NOT NULL AND p_expected_total_stock <> v_current_total THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Restock snapshot mismatch';
    END IF;
    IF p_expected_last_received IS NOT NULL AND v_current_last IS NOT NULL
       AND p_expected_last_received <> v_current_last THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Restock snapshot mismatch';
    END IF;

    SELECT sku INTO v_sku
    FROM products
    WHERE product_ID = p_product_id
    FOR UPDATE;

    IF v_sku IS NULL OR v_sku = '' THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Product not found';
    END IF;

    SET v_prefix = UPPER(
        IF(INSTR(v_sku, '-') > 0, SUBSTRING_INDEX(v_sku, '-', 1), LEFT(v_sku, 3))
    );
    SET v_today = CURRENT_DATE();
    SET v_total = p_unit_cost * p_quantity;

    SELECT batch_ID INTO v_last_batch
    FROM Inventory
    WHERE branch_ID = p_branch_id
      AND batch_ID LIKE CONCAT('B', p_branch_id, '-', v_prefix, '-%')
    ORDER BY batch_ID DESC
    LIMIT 1
    FOR UPDATE;

    IF v_last_batch IS NOT NULL AND v_last_batch <> '' THEN
        SET v_next_number = CAST(SUBSTRING_INDEX(v_last_batch, '-', -1) AS UNSIGNED) + 1;
    END IF;

    SET v_batch_id = CONCAT('B', p_branch_id, '-', v_prefix, '-', LPAD(v_next_number, 3, '0'));

    INSERT INTO PurchaseOrder (supplier_ID, branch_ID, date, status, total_amount)
    VALUES (p_supplier_id, p_branch_id, v_today, 'received', v_total);
    SET o_purchase_order_id = LAST_INSERT_ID();

    INSERT INTO Inventory (
        batch_ID,
        product_ID,
        branch_ID,
        quantity_received,
        quantity_on_hand,
        unit_cost,
        received_date,
        order_ID,
        date_expired
    )
    VALUES (
        v_batch_id,
        p_product_id,
        p_branch_id,
        p_quantity,
        p_quantity,
        p_unit_cost,
        v_today,
        o_purchase_order_id,
        p_expiry_date
    );

    SET v_clean_batch = UPPER(REPLACE(v_batch_id, '-', ''));

    WHILE i <= p_quantity DO
        SET v_item_id = CONCAT('SI-', v_clean_batch, '-', LPAD(i, 3, '0'));
        INSERT INTO StockItem (
            item_ID,
            batch_ID,
            product_ID,
            branch_ID,
            purchase_order_ID,
            customer_order_ID,
            received_date,
            expiry_date,
            status
        )
        VALUES (
            v_item_id,
            v_batch_id,
            p_product_id,
            p_branch_id,
            o_purchase_order_id,
            NULL,
            v_today,
            p_expiry_date,
            'in_stock'
        );
        SET i = i + 1;
    END WHILE;

    SET o_batch_id = v_batch_id;

    COMMIT;
END$$

CREATE PROCEDURE staff_adjust_inventory(
    IN p_batch_id VARCHAR(20),
    IN p_branch_id INT,
    IN p_new_quantity INT,
    IN p_reason VARCHAR(20),
    IN p_staff_id INT,
    IN p_expected_quantity INT,
    IN p_note VARCHAR(200)
)
BEGIN
    DECLARE v_product_id INT;
    DECLARE v_received_date DATE;
    DECLARE v_expiry_date DATE;
    DECLARE v_current_qty INT;
    DECLARE v_unit_cost DECIMAL(10,2);
    DECLARE v_order_id INT;
    DECLARE v_delta INT;
    DECLARE v_next_index INT DEFAULT 0;
    DECLARE v_last_item VARCHAR(60);
    DECLARE v_clean_batch VARCHAR(50);
    DECLARE v_target_status VARCHAR(20);
    DECLARE v_item_id VARCHAR(60);
    DECLARE v_remaining INT DEFAULT 0;
    DECLARE v_return_qty INT DEFAULT 0;
    DECLARE done INT DEFAULT 0;

    DECLARE cur_items CURSOR FOR
        SELECT item_ID
        FROM StockItem
        WHERE batch_ID = p_batch_id
          AND status = 'in_stock'
        ORDER BY item_ID DESC
        FOR UPDATE;
    DECLARE CONTINUE HANDLER FOR NOT FOUND SET done = 1;

    DECLARE EXIT HANDLER FOR SQLEXCEPTION
    BEGIN
        ROLLBACK;
        SET @stock_adjust_reason = NULL;
        SET @stock_adjust_staff_id = NULL;
        SET @stock_adjust_note = NULL;
        SET @skip_stockitem_certificate = NULL;
        RESIGNAL;
    END;

    IF p_staff_id IS NULL OR p_staff_id <= 0 THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Invalid staff';
    END IF;
    IF p_new_quantity < 0 THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Invalid quantity';
    END IF;
    IF p_reason NOT IN ('return', 'transfer', 'adjustment') THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Invalid reason';
    END IF;

    START TRANSACTION;
    SET @stock_adjust_reason = p_reason;
    SET @stock_adjust_staff_id = p_staff_id;
    SET @stock_adjust_note = NULLIF(TRIM(p_note), '');
    IF p_reason IN ('return', 'adjustment') THEN
        SET @skip_stockitem_certificate = 1;
    END IF;

    SELECT product_ID, received_date, date_expired, quantity_on_hand, unit_cost, order_ID
    INTO v_product_id, v_received_date, v_expiry_date, v_current_qty, v_unit_cost, v_order_id
    FROM Inventory
    WHERE batch_ID = p_batch_id
      AND branch_ID = p_branch_id
    FOR UPDATE;

    IF v_product_id IS NULL THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Batch not found';
    END IF;

    IF p_reason = 'return' AND (@stock_adjust_note IS NULL OR @stock_adjust_note = '') THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Return reason required';
    END IF;

    IF p_expected_quantity IS NOT NULL AND p_expected_quantity <> v_current_qty THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Quantity mismatch';
    END IF;

    SET v_delta = p_new_quantity - v_current_qty;
    IF v_delta = 0 THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Quantity unchanged';
    END IF;

    IF p_reason = 'return' AND p_new_quantity > v_current_qty THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Return must reduce stock';
    END IF;

    SET v_target_status = CASE p_reason
        WHEN 'return' THEN 'returned'
        WHEN 'transfer' THEN 'sold'
        ELSE 'damaged'
    END;

    SELECT item_ID INTO v_last_item
    FROM StockItem
    WHERE batch_ID = p_batch_id
    ORDER BY item_ID DESC
    LIMIT 1
    FOR UPDATE;

    IF v_last_item IS NOT NULL AND v_last_item <> '' THEN
        SET v_next_index = CAST(SUBSTRING_INDEX(v_last_item, '-', -1) AS UNSIGNED);
    END IF;

    SET v_clean_batch = UPPER(REPLACE(p_batch_id, '-', ''));

    IF v_delta > 0 THEN
        WHILE v_delta > 0 DO
            SET v_next_index = v_next_index + 1;
            SET v_item_id = CONCAT('SI-', v_clean_batch, '-', LPAD(v_next_index, 3, '0'));
            INSERT INTO StockItem (
                item_ID,
                batch_ID,
                product_ID,
                branch_ID,
                purchase_order_ID,
                customer_order_ID,
                received_date,
                expiry_date,
                status
            )
            VALUES (
                v_item_id,
                p_batch_id,
                v_product_id,
                p_branch_id,
                NULL,
                NULL,
                COALESCE(v_received_date, CURRENT_DATE()),
                v_expiry_date,
                'in_stock'
            );
            SET v_delta = v_delta - 1;
        END WHILE;
    ELSE
        SET v_remaining = ABS(v_delta);
        DROP TEMPORARY TABLE IF EXISTS tmp_adjust_items;
        CREATE TEMPORARY TABLE tmp_adjust_items (
            item_ID VARCHAR(60) PRIMARY KEY
        ) ENGINE = MEMORY;

        OPEN cur_items;
        read_loop: LOOP
            FETCH cur_items INTO v_item_id;
            IF done = 1 THEN
                LEAVE read_loop;
            END IF;
            INSERT IGNORE INTO tmp_adjust_items (item_ID) VALUES (v_item_id);
            SET v_remaining = v_remaining - 1;
            IF v_remaining <= 0 THEN
                LEAVE read_loop;
            END IF;
        END LOOP;
        CLOSE cur_items;

        WHILE v_remaining > 0 DO
            SET v_next_index = v_next_index + 1;
            SET v_item_id = CONCAT('SI-', v_clean_batch, '-', LPAD(v_next_index, 3, '0'));
            INSERT INTO StockItem (
                item_ID,
                batch_ID,
                product_ID,
                branch_ID,
                purchase_order_ID,
                customer_order_ID,
                received_date,
                expiry_date,
                status
            )
            VALUES (
                v_item_id,
                p_batch_id,
                v_product_id,
                p_branch_id,
                NULL,
                NULL,
                COALESCE(v_received_date, CURRENT_DATE()),
                v_expiry_date,
                v_target_status
            );
            SET v_remaining = v_remaining - 1;
            INSERT IGNORE INTO tmp_adjust_items (item_ID) VALUES (v_item_id);
        END WHILE;

        UPDATE StockItem
        SET status = v_target_status
        WHERE item_ID IN (SELECT item_ID FROM tmp_adjust_items);

        IF p_reason IN ('return', 'adjustment') THEN
            INSERT INTO StockItemCertificate (
                item_ID,
                transaction_type,
                date,
                transaction_ID,
                note
            )
            SELECT item_ID,
                   p_reason,
                   NOW(),
                   p_staff_id,
                   @stock_adjust_note
            FROM tmp_adjust_items;
        END IF;

        DROP TEMPORARY TABLE IF EXISTS tmp_adjust_items;
    END IF;

    UPDATE Inventory
    SET quantity_on_hand = p_new_quantity
    WHERE batch_ID = p_batch_id
      AND branch_ID = p_branch_id;

    IF p_reason = 'return' THEN
        SET v_return_qty = v_current_qty - p_new_quantity;
        IF v_return_qty > 0 AND v_order_id IS NOT NULL THEN
            UPDATE PurchaseOrder
            SET total_amount = GREATEST(total_amount - (v_return_qty * v_unit_cost), 0)
            WHERE purchase_order_ID = v_order_id;
        END IF;
    END IF;

    COMMIT;
    SET @stock_adjust_reason = NULL;
    SET @stock_adjust_staff_id = NULL;
    SET @stock_adjust_note = NULL;
    SET @skip_stockitem_certificate = NULL;
END$$


DELIMITER //

CREATE PROCEDURE ProcessCustomerOrder(
    IN p_customer_id INT,
    IN p_shipping_address TEXT,
    IN p_cart_id INT,
    OUT p_success BOOLEAN,
    OUT p_message VARCHAR(500),
    OUT p_order_id INT
)
proc_label: BEGIN
    DECLARE v_total_amount DECIMAL(10,2) DEFAULT 0;
    DECLARE v_final_amount DECIMAL(10,2) DEFAULT 0;
    DECLARE v_discount_rate DECIMAL(5,2) DEFAULT 0;
    DECLARE v_done BOOLEAN DEFAULT FALSE;
    DECLARE v_debug_message VARCHAR(1000);
    
    -- 游标变量
    DECLARE v_item_id VARCHAR(50);
    DECLARE v_batch_id VARCHAR(50);
    DECLARE v_product_id INT;
    DECLARE v_branch_id INT;
    DECLARE v_quantity INT;
    DECLARE v_unit_price DECIMAL(10,2);
    DECLARE v_order_item_count INT DEFAULT 0;
    DECLARE v_stock_item_count INT DEFAULT 0;
    
    -- 定义游标：获取购物车中所有待处理的商品
    DECLARE cur_cart_items CURSOR FOR 
        SELECT 
            oi.item_ID,
            oi.product_ID,
            si.batch_ID,
            si.branch_ID,
            oi.quantity,
            oi.unit_price
        FROM OrderItem oi
        JOIN StockItem si ON oi.item_ID = si.item_ID
        WHERE oi.order_ID = p_cart_id 
          AND (oi.status = 'pending' OR oi.status = 'Pending')
          AND (si.status = 'pending' OR si.status = 'Pending')
          AND si.customer_order_ID = p_cart_id;
    
    -- 错误处理器
    DECLARE CONTINUE HANDLER FOR NOT FOUND SET v_done = TRUE;
    DECLARE EXIT HANDLER FOR SQLEXCEPTION
    BEGIN
        -- 获取错误信息
        GET DIAGNOSTICS CONDITION 1
            @sqlstate = RETURNED_SQLSTATE,
            @errno = MYSQL_ERRNO,
            @text = MESSAGE_TEXT;
        
        -- 回滚事务
        ROLLBACK;
        SET p_success = FALSE;
        SET p_message = CONCAT('数据库错误: ', @text, ' (错误码: ', @errno, ')');
        SET p_order_id = NULL;
    END;
    
    -- 初始化输出参数
    SET p_success = FALSE;
    SET p_message = '';
    SET p_order_id = p_cart_id;
    SET v_debug_message = '';
    
    -- 调试日志：记录输入参数
    SET v_debug_message = CONCAT('输入参数: customer_id=', p_customer_id, 
                                ', cart_id=', p_cart_id);
    
    -- 验证参数
    IF p_customer_id IS NULL OR p_customer_id <= 0 THEN
        SET p_message = '无效的客户ID';
        LEAVE proc_label;
    END IF;
    
    IF p_shipping_address IS NULL OR TRIM(p_shipping_address) = '' THEN
        SET p_message = '请填写收货地址';
        LEAVE proc_label;
    END IF;

    -- 开始事务
    START TRANSACTION;
    
    -- 1. 验证订单是否存在且属于当前用户
    SELECT COUNT(*) INTO v_order_item_count
    FROM CustomerOrder 
    WHERE order_ID = p_cart_id 
      AND customer_id = p_customer_id
      AND (status = 'Pending' OR status = 'pending');
    
    IF v_order_item_count = 0 THEN
        SET p_message = CONCAT('购物车不存在或不属于当前用户。cart_id=', p_cart_id, ', customer_id=', p_customer_id);
        ROLLBACK;
        LEAVE proc_label;
    END IF;
    
    -- 2. 获取购物车总金额
    SELECT COALESCE(SUM(oi.quantity * oi.unit_price), 0) INTO v_total_amount
    FROM OrderItem oi
    WHERE oi.order_ID = p_cart_id 
      AND (oi.status = 'pending' OR oi.status = 'Pending');
    
    SET v_debug_message = CONCAT(v_debug_message, ', 总金额=', v_total_amount);
    
    IF v_total_amount <= 0 THEN
        SET p_message = '购物车无有效商品或金额为零';
        ROLLBACK;
        LEAVE proc_label;
    END IF;
    
    -- 3. 检查是否有待处理的OrderItem
    SELECT COUNT(*) INTO v_order_item_count
    FROM OrderItem 
    WHERE order_ID = p_cart_id 
      AND (status = 'pending' OR status = 'Pending');
    
    SET v_debug_message = CONCAT(v_debug_message, ', OrderItem数量=', v_order_item_count);
    
    IF v_order_item_count = 0 THEN
        SET p_message = '购物车中没有待处理的商品';
        ROLLBACK;
        LEAVE proc_label;
    END IF;
    
    -- 4. 获取客户折扣率
    SELECT 
        CASE 
            WHEN loyalty_level = 'VIP' THEN 0.05
            WHEN loyalty_level = 'VVIP' THEN 0.10
            ELSE 0.00
        END INTO v_discount_rate
    FROM customer 
    WHERE customer_ID = p_customer_id;
    
    -- 5. 计算最终金额（应用折扣）
    SET v_final_amount = v_total_amount * (1 - COALESCE(v_discount_rate, 0));
    
    -- 6. 更新订单主表状态
    UPDATE CustomerOrder 
    SET status = 'Completed', 
        total_amount = v_total_amount, 
        final_amount = v_final_amount,
        shipping_address = p_shipping_address,
        created_at = NOW()
    WHERE order_ID = p_cart_id;
    
    IF ROW_COUNT() <= 0 THEN
        SET p_message = '订单状态更新失败';
        ROLLBACK;
        LEAVE proc_label;
    END IF;
    
    -- 7. 更新OrderItem状态为completed
    UPDATE OrderItem 
    SET status = 'completed'
    WHERE order_ID = p_cart_id 
      AND (status = 'pending' OR status = 'Pending');
    
    SET v_stock_item_count = ROW_COUNT();
    SET v_debug_message = CONCAT(v_debug_message, ', 更新的OrderItem数量=', v_stock_item_count);
    
    -- 8. 更新库存：释放锁定库存，减少实际库存
    UPDATE Inventory inv
    JOIN (
        SELECT 
            si.batch_ID,
            si.product_ID,
            si.branch_ID,
            COUNT(*) as sold_count
        FROM StockItem si
        WHERE si.customer_order_ID = p_cart_id
          AND (si.status = 'pending' OR si.status = 'Pending')
        GROUP BY si.batch_ID, si.product_ID, si.branch_ID
    ) sold_items ON inv.batch_ID = sold_items.batch_ID
                 AND inv.product_ID = sold_items.product_ID
                 AND inv.branch_ID = sold_items.branch_ID
    SET 
        inv.quantity_on_hand = inv.quantity_on_hand - sold_items.sold_count,
        inv.locked_inventory = inv.locked_inventory - sold_items.sold_count;
    
    SET v_stock_item_count = ROW_COUNT();
    SET v_debug_message = CONCAT(v_debug_message, ', 更新的Inventory批次数量=', v_stock_item_count);
    
    -- 9. 更新StockItem状态为sold
    UPDATE StockItem 
    SET status = 'sold'
    WHERE customer_order_ID = p_cart_id 
      AND (status = 'pending' OR status = 'Pending');
    
    SET v_stock_item_count = ROW_COUNT();
    SET v_debug_message = CONCAT(v_debug_message, ', 更新的StockItem数量=', v_stock_item_count);

    -- 10. 记录交易日志
    INSERT INTO stockitemcertificate (
        item_ID,
        transaction_type, 
        transaction_id,
        note,
        `date`
    )
    SELECT 
        si.item_ID,
        'sale',
        p_cart_id,
        CONCAT('customer_id:', p_customer_id, '; product_id:', si.product_ID),
        NOW()
    FROM StockItem si
    WHERE si.customer_order_ID = p_cart_id
      AND (si.status = 'sold');
    
    -- 11. 更新客户累计消费
    UPDATE customer 
    SET accu_cost = accu_cost + v_final_amount 
    WHERE customer_ID = p_customer_id;
    
    -- 12. 提交事务
    COMMIT;
    
    -- 设置成功响应
    SET p_success = TRUE;
    SET p_message = CONCAT('订单提交成功！订单号: ', p_cart_id, 
                          '，总金额: ', FORMAT(v_total_amount, 2), 
                          '，折扣率: ', (COALESCE(v_discount_rate, 0) * 100), 
                          '%，实付金额: ', FORMAT(v_final_amount, 2),
                          '。调试信息: ', v_debug_message);
    
END //

DELIMITER ;
