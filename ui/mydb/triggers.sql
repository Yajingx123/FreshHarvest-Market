-- 第一个触发器
DROP TRIGGER IF EXISTS trg_customer_update_level;
DELIMITER //
CREATE TRIGGER trg_customer_update_level
BEFORE UPDATE ON customer
FOR EACH ROW
BEGIN
    IF NEW.accu_cost != OLD.accu_cost THEN
        IF NEW.accu_cost >= 200 THEN
            SET NEW.loyalty_level = 'VVIP';
        ELSEIF NEW.accu_cost >= 100 THEN
            SET NEW.loyalty_level = 'VIP';
        ELSE
            SET NEW.loyalty_level = 'Regular';
        END IF;
    END IF;
END //
DELIMITER ;

-- 第二个触发器
DROP TRIGGER IF EXISTS trg_stockitem_after_insert_purchase;
DELIMITER //
CREATE TRIGGER trg_stockitem_after_insert_purchase
AFTER INSERT ON StockItem
FOR EACH ROW
BEGIN
    DECLARE v_unit_cost DECIMAL(10,2);
    IF @stock_adjust_reason IS NOT NULL AND @stock_adjust_reason <> '' THEN
        INSERT INTO StockItemCertificate (
            item_ID,
            transaction_type,
            date,
            transaction_ID
        )
        VALUES (
            NEW.item_ID,
            @stock_adjust_reason,
            NOW(),
            @stock_adjust_staff_id
        );
    ELSEIF NEW.purchase_order_ID IS NOT NULL
       AND NEW.customer_order_ID IS NULL
       AND NEW.status = 'in_stock'
       THEN
        SELECT unit_cost INTO v_unit_cost
        FROM Inventory
        WHERE batch_ID = NEW.batch_ID
        LIMIT 1;

        INSERT INTO PurchaseItem (
            purchase_order_ID,
            item_ID,
            product_ID,
            unit_cost,
            received_date
        )
        VALUES (
            NEW.purchase_order_ID,
            NEW.item_ID,
            NEW.product_ID,
            v_unit_cost,
            NEW.received_date
        );

        INSERT INTO StockItemCertificate (
            item_ID,
            transaction_type,
            date,
            transaction_ID
        )
        VALUES (
            NEW.item_ID,
            'purchase',
            NOW(),
            NEW.purchase_order_ID
        );
    END IF;
END //
DELIMITER ;

-- 第三个触发器
DROP TRIGGER IF EXISTS trg_stockitem_after_update_adjustment;
DELIMITER //
CREATE TRIGGER trg_stockitem_after_update_adjustment
AFTER UPDATE ON StockItem
FOR EACH ROW
BEGIN
    IF @stock_adjust_reason IS NOT NULL
       AND @stock_adjust_reason <> ''
       AND NEW.status <> OLD.status THEN
        INSERT INTO StockItemCertificate (
            item_ID,
            transaction_type,
            date,
            transaction_ID
        )
        VALUES (
            NEW.item_ID,
            @stock_adjust_reason,
            NOW(),
            @stock_adjust_staff_id
        );
    END IF;
END //
DELIMITER ;