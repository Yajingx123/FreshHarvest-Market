USE mydb;
DROP VIEW IF EXISTS v_ceo_dashboard;
DROP VIEW IF EXISTS v_financial_overview;
DROP VIEW IF EXISTS v_branch_comparison;
DROP VIEW IF EXISTS v_supplier_information;
DROP VIEW IF EXISTS v_staff_information;
DROP VIEW IF EXISTS v_product_information;
DROP VIEW IF EXISTS v_sales_dashboard;
DROP VIEW IF EXISTS v_customer_interaction;
DROP VIEW IF EXISTS v_purchasing_management;
DROP VIEW IF EXISTS v_supplier_relations;
DROP VIEW IF EXISTS v_stock_movement;
DROP VIEW IF EXISTS v_inventory_management;
DROP VIEW IF EXISTS v_customer_profile;
DROP VIEW IF EXISTS v_order_history;
DROP VIEW IF EXISTS v_wishlist_products;
DROP VIEW IF EXISTS v_customer_product_info;
DROP VIEW IF EXISTS v_alerts_monitoring;
DROP VIEW IF EXISTS v_expiry_alerts;
DROP VIEW IF EXISTS v_low_stock_alerts;
DROP VIEW IF EXISTS v_audit_trail;
DROP VIEW IF EXISTS v_certificate_tracking;
DROP VIEW IF EXISTS product_catalog_view;
DROP VIEW IF EXISTS v_branch_order_details;
DROP VIEW IF EXISTS v_products_list;
DROP VIEW IF EXISTS v_transactions;
DROP VIEW IF EXISTS v_employees;
DROP VIEW IF EXISTS BranchAllOrders;

DROP VIEW IF EXISTS v_sales_trend;
DROP VIEW IF EXISTS v_order_status_distribution;
DROP VIEW IF EXISTS v_branch_sales_comparison;
DROP VIEW IF EXISTS v_alert_summary;


-- ============================================================
-- 📊 CEO/管理者视图
-- 创建获取分店分店所有订单的视图
-- 销售趋势视图（按日期）
CREATE OR REPLACE VIEW v_sales_trend AS
SELECT 
    DATE(co.order_date) AS date,
    SUM(co.final_amount) AS total_sales,
    COUNT(co.order_ID) AS order_count
FROM CustomerOrder co
WHERE co.status = 'completed'
GROUP BY DATE(co.order_date)
ORDER BY date DESC
LIMIT 30;

CREATE OR REPLACE VIEW v_order_status_distribution AS
SELECT 
    status AS order_status,
    COUNT(order_ID) AS count
FROM CustomerOrder
GROUP BY status;

-- 门店销售对比视图
CREATE OR REPLACE VIEW v_branch_sales_comparison AS
SELECT 
    b.branch_name,
    SUM(co.final_amount) AS total_sales,
    COUNT(co.order_ID) AS order_count
FROM Branch b
LEFT JOIN CustomerOrder co ON b.branch_ID = co.branch_ID
WHERE co.order_date >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)
GROUP BY b.branch_ID, b.branch_name;

CREATE OR REPLACE VIEW `BranchAllOrders` AS
SELECT 
    b.branch_ID,
    b.branch_name,
    b.address AS branch_address,
    co.order_ID,
    co.customer_ID,
    co.order_date,
    co.total_amount,
    co.final_amount,
    co.status AS order_status,
    co.shipping_address,
    co.created_at
FROM 
    `mydb`.`Branch` b
LEFT JOIN 
    `mydb`.`CustomerOrder` co ON b.branch_ID = co.branch_ID
ORDER BY 
    co.order_date DESC, b.branch_ID;


-- 1. 优化产品目录视图（支持产品列表查询）
CREATE OR REPLACE VIEW v_products_list AS
SELECT 
    p.product_ID AS id,
    p.product_name AS name,
    p.sku,
    p.description,
    p.unit,
    p.unit_price AS price,
    c.category_name AS category,
    p.status,
    COALESCE(SUM(i.quantity_on_hand), 0) AS stock,
    (SELECT category_name FROM Categories WHERE category_id = c.parent_category_id) AS parent_category
FROM 
    products p
JOIN Categories c ON p.category_id = c.category_id
LEFT JOIN Inventory i ON p.product_ID = i.product_ID
GROUP BY 
    p.product_ID, p.product_name, p.sku, p.description, 
    p.unit, p.unit_price, c.category_name, p.status;

-- 2. 优化交易流水视图（替代原StockItemCertificate查询）
CREATE OR REPLACE VIEW v_transactions AS
SELECT 
    sc.certificate_ID AS id,
    sc.date AS time,
    si.product_ID AS productId,
    si.batch_ID AS batchId,
    p.product_name AS productName,
    sc.transaction_type AS type,
    sc.note,
    CASE 
        WHEN sc.transaction_type = 'purchase' THEN 'in'
        WHEN sc.transaction_type = 'sale' THEN 'out'
        ELSE sc.transaction_type
    END AS direction,
    1 AS qty,
    p.unit
FROM 
    StockItemCertificate sc
LEFT JOIN StockItem si ON sc.item_ID = si.item_ID
LEFT JOIN products p ON si.product_ID = p.product_ID
ORDER BY sc.date DESC
LIMIT 100;

-- 3. 员工信息视图（整合Staff和User表）
CREATE OR REPLACE VIEW v_employees AS
SELECT 
    s.staff_ID AS id,
    CONCAT(u.first_name, ' ', u.last_name) AS name,
    s.position AS role,
    s.salary,
    s.hire_date AS start_date,
    s.status AS status_raw,
    u.user_email AS email,
    u.user_telephone AS phone,
    b.branch_name,
    b.branch_ID AS branch_id,
    u.user_name AS username,
    s.created_at,
    s.phone AS staff_phone
FROM 
    Staff s
JOIN User u ON s.user_name = u.user_name
JOIN Branch b ON s.branch_ID = b.branch_ID;

-- 1. v_ceo_dashboard - 管理者仪表盘
-- 用途：CEO查看整体业务概览
CREATE VIEW v_ceo_dashboard AS
SELECT 
    DATE(CURDATE()) AS report_date,
    'daily' AS period_type,
    COALESCE(SUM(co.total_amount), 0) AS total_sales,
    COALESCE(COUNT(DISTINCT co.customer_ID), 0) AS active_customers,
    COUNT(DISTINCT s.staff_ID) AS employee_count,
    COALESCE(SUM(i.quantity_on_hand * i.unit_cost), 0) AS total_inventory_value
FROM 
    Staff s
    LEFT JOIN CustomerOrder co ON DATE(co.order_date) = CURDATE() AND co.status = 'Completed'
    LEFT JOIN Inventory i ON i.branch_ID = s.branch_ID
WHERE 
    s.status = 'active';

-- 创建/修改产品目录视图
-- 修正后的产品目录视图（按门店分组）
CREATE OR REPLACE VIEW product_catalog_view AS
SELECT 
    p.product_ID,
    p.sku,
    p.product_name,
    c.category_name,
    c.parent_category_id,
    (SELECT category_name FROM Categories WHERE category_id = c.parent_category_id) AS parent_category_name,
    p.unit_price,
    p.unit,
    p.description,
    p.status AS product_status,
    -- 聚合产品属性
    GROUP_CONCAT(DISTINCT CONCAT(pa.attr_name, ': ', pa.attr_value) SEPARATOR ', ') AS product_attributes,
    -- 按门店分组的在手库存（total_stock = quantity_on_hand）
    i.quantity_on_hand AS total_stock,
    -- 按门店分组的可用库存（available_stock = quantity_on_hand - locked_inventory）
    (i.quantity_on_hand - i.locked_inventory) AS available_stock,
    -- 库存状态
    CASE 
        WHEN (i.quantity_on_hand - i.locked_inventory) > 0 THEN '有货'
        ELSE '无货'
    END AS stock_status,
    b.branch_name AS store_name,  -- 关联门店名称
    b.branch_id AS store_id,       -- 关联门店ID
    GROUP_CONCAT(DISTINCT si.item_ID SEPARATOR ', ') AS item_id  -- 新增：所有相关的item_id
FROM 
    products p
JOIN Categories c ON p.category_id = c.category_id
LEFT JOIN ProductAttribute pa ON p.product_ID = pa.product_id
LEFT JOIN Inventory i ON p.product_ID = i.product_ID
LEFT JOIN Branch b ON i.branch_id = b.branch_id  -- 关联门店表
LEFT JOIN StockItem si ON p.product_ID = si.product_ID AND i.branch_id = si.branch_id
WHERE 
    p.status = 'active'  -- 只显示活跃产品
GROUP BY 
    p.product_ID,
    p.sku,
    p.product_name,
    c.category_name,
    c.parent_category_id,
    p.unit_price,
    p.unit,
    p.description,
    p.status,
    i.quantity_on_hand,  -- 关键：按实际在手库存分组
    i.locked_inventory,  -- 关键：按锁定库存分组
    b.branch_id,
    b.branch_name
ORDER BY 
    c.category_name,
    p.product_name,
    b.branch_name;

-- 2. v_financial_overview - 财务概览
-- 用途：查看公司财务状况可以看年月日
CREATE VIEW v_financial_overview AS
SELECT 
    DATE_FORMAT(co.order_date, '%Y-%m-%d') AS date_day,
    b.branch_name,
    SUM(co.total_amount) AS revenue,
    COUNT(DISTINCT co.order_ID) AS order_count
FROM 
    CustomerOrder co
    JOIN Branch b ON co.branch_ID = b.branch_ID
WHERE 
    co.status = 'Completed'
GROUP BY 
    DATE_FORMAT(co.order_date, '%Y-%m-%d'),
    DATE_FORMAT(co.order_date, '%Y-%m'),
    YEAR(co.order_date),
    b.branch_name;

-- 3. v_branch_comparison - 分店对比
-- 用途：比较各分店绩效
CREATE VIEW v_branch_comparison AS
SELECT 
    b.branch_name,
    DATE_FORMAT(co.order_date, '%Y-%m') AS period,
    COUNT(DISTINCT co.order_ID) AS total_orders,
    SUM(co.total_amount) AS total_sales,
    COUNT(DISTINCT co.customer_ID) AS unique_customers
FROM 
    Branch b
    LEFT JOIN CustomerOrder co ON b.branch_ID = co.branch_ID 
        AND co.status = 'Completed'
GROUP BY 
    b.branch_name,
    DATE_FORMAT(co.order_date, '%Y-%m');

-- 4. v_supplier_information - 供应商信息
-- 用途：查看供应商信息
CREATE VIEW v_supplier_information AS
SELECT 
    s.supplier_ID,
    s.company_name,
    s.contact_person,
    s.phone,
    s.email,
    s.tax_number,
    s.status,
    COUNT(DISTINCT po.purchase_order_ID) AS total_orders,
    COALESCE(SUM(po.total_amount), 0) AS total_amount
FROM 
    Supplier s
    LEFT JOIN PurchaseOrder po ON s.supplier_ID = po.supplier_ID
GROUP BY 
    s.supplier_ID,
    s.company_name,
    s.contact_person,
    s.phone,
    s.email,
    s.tax_number,
    s.status;

-- 5. v_staff_information - 员工信息
-- 用途：查看员工信息
CREATE VIEW v_staff_information AS
SELECT 
    b.branch_name,
    u.first_name,
    u.last_name,
    s.position,
    u.user_telephone AS phone,
    u.user_email AS email,
    s.salary,
    s.hire_date,
    s.status
FROM 
    Staff s
    JOIN User u ON s.user_name = u.user_name
    JOIN Branch b ON s.branch_ID = b.branch_ID
WHERE 
    u.user_type = 'staff';

-- 6. v_product_information - 产品信息
-- 用途：查看产品信息
CREATE VIEW v_product_information AS
SELECT 
    p.product_name,
    p.sku,
    c.category_name,
    GROUP_CONCAT(DISTINCT CONCAT(pa.attr_name, ': ', pa.attr_value) SEPARATOR ', ') AS attributes,
    p.unit_price AS selling_price,
    (SELECT AVG(pi.unit_cost) FROM PurchaseItem pi WHERE pi.product_ID = p.product_ID) AS avg_cost,
    (SELECT GROUP_CONCAT(DISTINCT s.company_name) 
     FROM PurchaseOrder po 
     JOIN Supplier s ON po.supplier_ID = s.supplier_ID
     JOIN PurchaseItem pi ON po.purchase_order_ID = pi.purchase_order_ID
     WHERE pi.product_ID = p.product_ID) AS suppliers,
    (SELECT SUM(quantity_on_hand) FROM Inventory WHERE product_ID = p.product_ID) AS total_inventory
FROM 
    products p
    JOIN Categories c ON p.category_id = c.category_id
    LEFT JOIN ProductAttribute pa ON p.product_ID = pa.product_id
GROUP BY 
    p.product_ID,
    p.product_name,
    p.sku,
    c.category_name,
    p.unit_price;

-- ============================================================
-- 👨‍💼 staff视图
-- ============================================================

-- 7. v_sales_dashboard - 销售仪表盘
-- 用途：销售员查看个人业绩
CREATE VIEW v_sales_dashboard AS
SELECT 
    DATE(CURDATE()) AS today,
    COUNT(DISTINCT co.order_ID) AS today_orders,
    COALESCE(SUM(co.total_amount), 0) AS today_sales,
    (SELECT COUNT(DISTINCT order_ID) 
     FROM CustomerOrder 
     WHERE branch_ID = (SELECT branch_ID FROM Staff WHERE user_name = USER())
     AND MONTH(order_date) = MONTH(CURDATE())
     AND YEAR(order_date) = YEAR(CURDATE())
     AND status = 'Completed') AS month_orders
FROM 
    CustomerOrder co
WHERE 
    DATE(co.order_date) = CURDATE()
    AND co.branch_ID = (SELECT branch_ID FROM Staff WHERE user_name = USER())
    AND co.status = 'Completed';

-- 8. v_customer_interaction - 客户互动
-- 用途：销售员查看客户信息
CREATE VIEW v_customer_interaction AS
SELECT 
    c.customer_ID,
    u.first_name,
    u.last_name,
    c.loyalty_level,
    c.phone,
    c.email,
    COUNT(DISTINCT co.order_ID) AS total_orders,
    COALESCE(SUM(co.total_amount), 0) AS total_spent,
    MAX(co.order_date) AS last_purchase
FROM 
    Customer c
    JOIN User u ON c.user_name = u.user_name
    LEFT JOIN CustomerOrder co ON c.customer_ID = co.customer_ID
        AND co.branch_ID = (SELECT branch_ID FROM Staff WHERE user_name = USER())
GROUP BY 
    c.customer_ID,
    u.first_name,
    u.last_name,
    c.loyalty_level,
    c.phone,
    c.email;

-- 13. v_purchasing_management - 采购管理
-- 用途：管理采购订单
CREATE VIEW v_purchasing_management AS
SELECT 
    po.purchase_order_ID,
    s.company_name AS supplier,
    po.date,
    po.status,
    po.total_amount,
    COUNT(DISTINCT pi.supply_id) AS item_count
FROM 
    PurchaseOrder po
    JOIN Supplier s ON po.supplier_ID = s.supplier_ID
    LEFT JOIN PurchaseItem pi ON po.purchase_order_ID = pi.purchase_order_ID
GROUP BY 
    po.purchase_order_ID,
    s.company_name,
    po.date,
    po.status,
    po.total_amount;

-- 14. v_supplier_relations - 供应商关系
-- 用途：管理供应商信息
CREATE VIEW v_supplier_relations AS
SELECT 
    s.supplier_ID,
    s.company_name,
    s.contact_person,
    s.phone,
    s.email,
    s.address,
    s.status
FROM 
    Supplier s;

-- 15. v_stock_movement - 库存流动
-- 用途：跟踪库存变化
CREATE VIEW v_stock_movement AS
SELECT 
    p.product_name,
    si.batch_ID,
    si.received_date,
    si.expiry_date,
    si.status AS item_status
FROM 
    StockItem si
    JOIN products p ON si.product_ID = p.product_ID
WHERE 
    si.branch_ID = (SELECT branch_ID FROM Staff WHERE user_name = USER())
ORDER BY 
    si.received_date DESC;

-- 5. v_inventory_management - 库存管理
-- 用途：管理本店库存
CREATE VIEW v_inventory_management AS
SELECT 
    p.product_name,
    p.sku,
    i.product_ID, -- 新增：保留商品ID，方便代码关联
    i.branch_ID,  -- 新增：保留门店ID，方便代码关联
    i.quantity_on_hand AS current_stock,
    i.locked_inventory, -- 新增：添加锁定库存字段（代码中需要用）
    i.batch_ID,
    i.received_date,
    i.date_expired
FROM 
    Inventory i
    JOIN products p ON i.product_ID = p.product_ID;

-- ============================================================
-- 👤 客户视图
-- ============================================================

-- 10. v_customer_profile - 个人资料
-- 用途：客户查看个人信息和订单
CREATE VIEW v_customer_profile AS
SELECT 
    u.first_name,
    u.last_name,
    u.user_name,
    u.user_telephone AS phone,
    u.user_email AS email,
    c.loyalty_level,
    c.customer_ID,  -- 新增：便于关联其他表
    c.gender,
    c.address,
    MAX(co.shipping_address) AS last_shipping_address,
    COUNT(DISTINCT co.order_ID) AS total_orders
FROM 
    User u
    JOIN Customer c ON u.user_name = c.user_name
    LEFT JOIN CustomerOrder co ON c.customer_ID = co.customer_ID
GROUP BY 
    u.first_name,
    u.last_name,
    u.user_name,
    u.user_telephone,
    u.user_email,
    c.loyalty_level,
    c.gender, 
    c.address,
    c.customer_ID;

-- 11. v_order_history - 订单历史
CREATE OR REPLACE VIEW v_order_history AS
SELECT 
    co.order_ID,
    co.customer_ID,
    co.order_date,
    co.total_amount,
    co.final_amount,  -- 折扣后金额
    co.status AS order_status,
    co.shipping_address,
    b.branch_name AS store_name,
    -- 调整为通过item_ID关联StockItem获取商品信息，确保所有订单项都能被正确记录
    GROUP_CONCAT(
        CONCAT(p.product_name, '(', oi.quantity, 'x¥', oi.unit_price, ')') 
        SEPARATOR '; '
    ) AS product_details,
    COUNT(oi.item_ID) AS item_count,  -- 商品总数
    SUM(oi.quantity) AS total_quantity  -- 总件数
FROM 
    CustomerOrder co
JOIN Branch b ON co.branch_ID = b.branch_ID
LEFT JOIN OrderItem oi ON co.order_ID = oi.order_ID
-- 通过StockItem表关联商品，确保item_ID能正确映射到product
LEFT JOIN StockItem si ON oi.item_ID = si.item_ID
LEFT JOIN products p ON si.product_ID = p.product_ID
GROUP BY 
    co.order_ID, co.customer_ID, co.order_date, co.total_amount, 
    co.final_amount, co.status, co.shipping_address, b.branch_name
ORDER BY 
    co.order_date DESC;

-- 用于获取用户最喜欢的产品（按消费金额排序）
CREATE OR REPLACE VIEW v_favorite_products AS
SELECT 
    p.product_ID,
    p.product_name,
    co.customer_ID,  -- 保留客户ID用于后续筛选
    SUM(oi.quantity * oi.unit_price) AS total_spent,
    COUNT(oi.order_ID) AS purchase_count
FROM 
    OrderItem oi
JOIN products p ON oi.product_ID = p.product_ID
JOIN CustomerOrder co ON oi.order_ID = co.order_ID
WHERE 
    co.status = 'Completed'  -- 只统计已完成订单
GROUP BY 
    p.product_ID, p.product_name, co.customer_ID  -- 按客户+产品分组
ORDER BY 
    total_spent DESC, purchase_count DESC;

-- 12. v_wishlist_products - 心愿单产品
-- 用途：查看收藏或关注的产品（使用pending订单作为购物车）
CREATE OR REPLACE VIEW v_wishlist_products AS
SELECT 
  p.product_id, 
  p.product_name, 
  o.customer_id,  -- 关联订单的顾客ID
  oi.quantity,    -- 订单商品数量（从订单项表获取）
  oi.item_id,     -- 订单项ID
  p.unit_price,
  p.category_id,
  i.quantity_on_hand AS available_stock,
  i.locked_inventory,
  CASE 
    WHEN (i.quantity_on_hand - i.locked_inventory) >= 0 THEN '有货' 
    ELSE '无货' 
  END AS stock_status,
  o.branch_id     -- 订单对应的门店ID
FROM CustomerOrder o  -- 订单主表（存储订单状态、顾客ID等）
JOIN OrderItem oi ON o.order_id = oi.order_id  -- 订单项表（关联商品和数量）
JOIN products p ON oi.product_id = p.product_id  -- 商品表
JOIN inventory i ON p.product_id = i.product_id AND o.branch_id = i.branch_id  -- 库存表
WHERE o.status = 'Pending'  -- 仅筛选未结算的订单（购物车状态）
AND o.customer_id IS NOT NULL;  -- 确保关联有效用户

-- 13. v_customer_product_info - 产品信息（客户视图）
-- 用途：客户查看产品信息
CREATE VIEW v_customer_product_info AS
SELECT 
    p.product_name,
    c.category_name,
    p.unit_price,
    p.description,
    p.status AS product_status,
    GROUP_CONCAT(DISTINCT CONCAT(pa.attr_name, ': ', pa.attr_value) SEPARATOR ', ') AS attributes
FROM 
    products p
    JOIN Categories c ON p.category_id = c.category_id
    LEFT JOIN ProductAttribute pa ON p.product_ID = pa.product_id
WHERE 
    p.status = 'active'
GROUP BY 
    p.product_ID,
    p.product_name,
    c.category_name,
    p.unit_price,
    p.description,
    p.status;

-- ============================================================
-- ⚠️ 预警类视图
-- ============================================================

-- 21. v_alerts_monitoring - 预警监控
-- 用途：监控各种预警信息
CREATE VIEW v_alerts_monitoring AS
SELECT 
    'expiry' AS alert_type,
    p.product_ID,
    p.product_name,
    i.batch_ID,
    DATEDIFF(i.date_expired, CURDATE()) AS days_to_expire,
    '高' AS severity,
    CONCAT('产品即将在', DATEDIFF(i.date_expired, CURDATE()), '天后过期') AS description,
    '尽快促销或处理' AS suggested_action
FROM 
    Inventory i
    JOIN products p ON i.product_ID = p.product_ID
WHERE 
    i.date_expired IS NOT NULL 
    AND DATEDIFF(i.date_expired, CURDATE()) BETWEEN 0 AND 30

UNION ALL

SELECT 
    'low_stock' AS alert_type,
    p.product_ID,
    p.product_name,
    i.batch_ID,
    i.quantity_on_hand AS current_stock,
    CASE 
        WHEN i.quantity_on_hand <= 5 THEN '高'
        WHEN i.quantity_on_hand <= 10 THEN '中'
        ELSE '低'
    END AS severity,
    CONCAT('库存仅剩', i.quantity_on_hand, '件') AS description,
    '建议补货' AS suggested_action
FROM 
    Inventory i
    JOIN products p ON i.product_ID = p.product_ID
WHERE 
    i.quantity_on_hand <= 10;

-- 22. v_expiry_alerts - 过期预警
-- 用途：管理即将过期产品
CREATE VIEW v_expiry_alerts AS
SELECT 
    p.product_name,
    i.batch_ID,
    i.date_expired,
    DATEDIFF(i.date_expired, CURDATE()) AS remaining_days,
    i.quantity_on_hand,
    CASE 
        WHEN DATEDIFF(i.date_expired, CURDATE()) <= 7 THEN '紧急处理'
        WHEN DATEDIFF(i.date_expired, CURDATE()) <= 30 THEN '促销处理'
        ELSE '正常监控'
    END AS suggested_action
FROM 
    Inventory i
    JOIN products p ON i.product_ID = p.product_ID
WHERE 
    i.date_expired IS NOT NULL 
    AND i.date_expired >= CURDATE()
ORDER BY 
    i.date_expired ASC;

-- 23. v_low_stock_alerts - 低库存预警
-- 用途：提醒补货
CREATE VIEW v_low_stock_alerts AS
SELECT 
    p.product_name,
    p.sku,
    i.batch_ID,
    i.quantity_on_hand AS current_stock,
    (SELECT AVG(pi.unit_cost) FROM PurchaseItem pi WHERE pi.product_ID = p.product_ID) AS avg_cost,
    (SELECT GROUP_CONCAT(DISTINCT s.company_name) 
     FROM PurchaseOrder po 
     JOIN Supplier s ON po.supplier_ID = s.supplier_ID
     JOIN PurchaseItem pi ON po.purchase_order_ID = pi.purchase_order_ID
     WHERE pi.product_ID = p.product_ID) AS suppliers
FROM 
    Inventory i
    JOIN products p ON i.product_ID = p.product_ID
WHERE 
    i.quantity_on_hand <= 10
ORDER BY 
    i.quantity_on_hand ASC;

CREATE OR REPLACE VIEW v_alert_summary AS
SELECT 
    'expiry' AS alert_type,
    COUNT(*) AS count
FROM 
    v_expiry_alerts
UNION ALL
SELECT 
    'low_stock' AS alert_type,
    COUNT(*) AS count
FROM 
    v_low_stock_alerts;

-- ============================================================
-- 🔄 审计/合规视图
-- ============================================================

-- 24. v_audit_trail - 审计追踪
-- 用途：跟踪数据变更（基于您的StockItemCertificate表）
CREATE VIEW v_audit_trail AS
SELECT 
    sc.certificate_ID,
    sc.item_ID,
    sc.transaction_type,
    sc.date AS transaction_date,
    sc.transaction_ID,
    si.product_ID,
    p.product_name,
    si.status AS item_status
FROM 
    StockItemCertificate sc
    LEFT JOIN StockItem si ON sc.item_ID = si.item_ID
    LEFT JOIN products p ON si.product_ID = p.product_ID
ORDER BY 
    sc.date DESC;

-- 25. v_certificate_tracking - 证书追踪
-- 用途：追踪产品证书和溯源
CREATE VIEW v_certificate_tracking AS
SELECT 
    sc.certificate_ID,
    si.batch_ID,
    p.product_name,
    sc.transaction_type,
    sc.date,
    si.received_date,
    si.expiry_date,
    si.status AS item_status
FROM 
    StockItemCertificate sc
    JOIN StockItem si ON sc.item_ID = si.item_ID
    JOIN products p ON si.product_ID = p.product_ID
ORDER BY 
    sc.date DESC,
    si.batch_ID;
    
    -- ============================================================
-- 📊 CEO/管理者视图 SELECT语句
-- ============================================================

-- 1. v_ceo_dashboard - 管理者仪表盘
SELECT * FROM v_ceo_dashboard;
-- 查看整体业务概览：总销售额、活跃客户数、员工数、库存总值




-- 2. v_financial_overview - 财务概览
SELECT * FROM v_financial_overview ORDER BY date_day DESC, branch_name;
-- 查看公司财务状况：日期、分店、收入、订单数

-- 3. v_branch_comparison - 分店对比
SELECT * FROM v_branch_comparison ORDER BY period DESC, total_sales DESC;
-- 比较各分店绩效：分店名、期间、总订单、总销售额、客户数

-- 4. v_supplier_information - 供应商信息
SELECT * FROM v_supplier_information ORDER BY total_amount DESC;
-- 查看供应商信息：公司名、联系人、电话、email、订单数、总金额

-- 5. v_staff_information - 员工信息
SELECT * FROM v_staff_information ORDER BY branch_name, position;
-- 查看员工信息：分店、姓名、职位、电话、薪水、状态

-- 6. v_product_information - 产品信息
SELECT * FROM v_product_information ORDER BY category_name, product_name;
-- 查看产品信息：产品名、SKU、分类、属性、售价、成本、供应商、库存
/*
-- ============================================================
-- 👨‍💼 staff视图 SELECT语句
-- ============================================================

-- 7. v_sales_dashboard - 销售仪表盘
SELECT * FROM v_sales_dashboard;
-- 销售员查看个人业绩：今日订单、今日销售额、月度订单

-- 8. v_customer_interaction - 客户互动
SELECT * FROM v_customer_interaction ORDER BY total_spent DESC;
-- 销售员查看客户信息：客户姓名、忠诚度、订单数、总消费、最后购买

-- 13. v_purchasing_management - 采购管理
SELECT * FROM v_purchasing_management ORDER BY date DESC;
-- 管理采购订单：订单号、供应商、日期、状态、金额、物品数

-- 14. v_supplier_relations - 供应商关系
SELECT * FROM v_supplier_relations ORDER BY company_name;
-- 管理供应商信息：公司名、联系人、电话、地址、状态

-- 15. v_stock_movement - 库存流动
SELECT * FROM v_stock_movement;
-- 跟踪库存变化：产品名、批次号、入库时间、过期时间、状态

-- 5. v_inventory_management - 库存管理
SELECT * FROM v_inventory_management ORDER BY current_stock ASC;
-- 管理本店库存：产品名、SKU、当前库存、批次、入库日期、过期日期

-- ============================================================
-- 👤 客户视图 SELECT语句
-- ============================================================

-- 10. v_customer_profile - 个人资料
SELECT * FROM v_customer_profile;
-- 客户查看个人信息和订单：姓名、联系方式、忠诚度、订单数

-- 11. v_order_history - 订单历史
SELECT * FROM v_order_history;
-- 查看购买记录：订单号、日期、金额、状态、物品数

-- 12. v_wishlist_products - 心愿单产品
SELECT * FROM v_wishlist_products;
-- 查看收藏或关注的产品（购物车）：产品名、价格、数量、库存

-- 13. v_customer_product_info - 产品信息（客户视图）
SELECT * FROM v_customer_product_info ORDER BY category_name, product_name;
-- 客户查看产品信息：产品名、分类、价格、描述、属性

-- ============================================================
-- ⚠️ 预警类视图 SELECT语句
-- ============================================================

-- 21. v_alerts_monitoring - 预警监控
SELECT * FROM v_alerts_monitoring ORDER BY severity DESC, days_to_expire ASC;
-- 监控各种预警信息：预警类型、产品、严重程度、描述、建议

-- 22. v_expiry_alerts - 过期预警
SELECT * FROM v_expiry_alerts ORDER BY remaining_days ASC;
-- 管理即将过期产品：产品名、批次、过期日期、剩余天数、库存量

-- 23. v_low_stock_alerts - 低库存预警
SELECT * FROM v_low_stock_alerts ORDER BY current_stock ASC;
-- 提醒补货：产品名、SKU、当前库存、平均成本、供应商

-- ============================================================
-- 🔄 审计/合规视图 SELECT语句
-- ============================================================

-- 24. v_audit_trail - 审计追踪
SELECT * FROM v_audit_trail;
-- 跟踪数据变更：证书号、物品号、交易类型、日期、产品名

-- 25. v_certificate_tracking - 证书追踪
SELECT * FROM v_certificate_tracking;
-- 追踪产品证书和溯源：证书号、批次、产品名、交易类型、日期  */