USE mydb;

DROP VIEW IF EXISTS v_sales_trend;
DROP VIEW IF EXISTS v_order_status_distribution;
DROP VIEW IF EXISTS v_branch_sales_comparison;
DROP VIEW IF EXISTS BranchAllOrders;
DROP VIEW IF EXISTS v_products_list;
DROP VIEW IF EXISTS v_transactions;
DROP VIEW IF EXISTS v_employees;
DROP VIEW IF EXISTS v_manager_product_inventory_by_branch;
DROP VIEW IF EXISTS v_manager_product_supplier_pricing;
DROP VIEW IF EXISTS v_low_stock_alerts;
DROP VIEW IF EXISTS v_alert_summary;
DROP VIEW IF EXISTS v_expiry_alerts;
DROP VIEW IF EXISTS v_customer_profile;

-- 1. Sales Trend View (by date)
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

-- 2. Order Status Distribution View
CREATE OR REPLACE VIEW v_order_status_distribution AS
SELECT 
    status AS order_status,
    COUNT(order_ID) AS count
FROM CustomerOrder
GROUP BY status;

-- 3. Branch Sales Comparison View
CREATE OR REPLACE VIEW v_branch_sales_comparison AS
SELECT 
    b.branch_name,
    SUM(co.final_amount) AS total_sales,
    COUNT(co.order_ID) AS order_count
FROM Branch b
LEFT JOIN CustomerOrder co ON b.branch_ID = co.branch_ID
WHERE co.order_date >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)
GROUP BY b.branch_ID, b.branch_name;

-- 4. All Branch Orders View
CREATE OR REPLACE VIEW BranchAllOrders AS
SELECT 
    b.branch_ID,
    b.branch_name,
    co.order_ID,
    co.customer_ID,
    CONCAT(u.first_name, ' ', u.last_name) AS customer_name,
    co.order_date,
    co.total_amount,
    co.final_amount,
    co.status AS order_status,
    co.shipping_address,
    (co.total_amount - co.final_amount) AS discount_amount
FROM Branch b
LEFT JOIN CustomerOrder co ON b.branch_ID = co.branch_ID
LEFT JOIN Customer c ON co.customer_ID = c.customer_ID
LEFT JOIN User u ON c.user_name = u.user_name;

-- 5. Products List View
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
FROM products p
JOIN Categories c ON p.category_id = c.category_id
LEFT JOIN Inventory i ON p.product_ID = i.product_ID
GROUP BY p.product_ID, p.product_name, p.sku, p.description, 
         p.unit, p.unit_price, c.category_name, p.status;

-- 6. Transactions View
CREATE OR REPLACE VIEW v_transactions AS
SELECT 
    MAX(t.id) AS id,
    MAX(t.time) AS time,
    t.productId,
    t.batchId,
    t.productName,
    t.type,
    GROUP_CONCAT(DISTINCT t.note SEPARATOR '; ') AS note,
    CASE 
        WHEN t.type = 'purchase' THEN 'in'
        ELSE 'out'
    END AS direction,
    COUNT(*) AS qty,
    t.unit,
    t.source_name,
    t.destination_name
FROM (
    SELECT
        sc.certificate_ID AS id,
        sc.date AS time,
        si.product_ID AS productId,
        si.batch_ID AS batchId,
        p.product_name AS productName,
        sc.transaction_type AS type,
        sc.note,
        p.unit,
        CASE
            WHEN sc.transaction_type = 'sale' THEN COALESCE(b_sale.branch_name, b_item.branch_name, '')
            WHEN sc.transaction_type = 'purchase' THEN COALESCE(s.company_name, '')
            WHEN sc.transaction_type = 'return' THEN COALESCE(b_item.branch_name, '')
            WHEN sc.transaction_type = 'adjustment' THEN COALESCE(b_item.branch_name, '')
            ELSE COALESCE(b_item.branch_name, '')
        END AS source_name,
        CASE
            WHEN sc.transaction_type = 'sale' THEN COALESCE(NULLIF(TRIM(CONCAT_WS(' ', u.first_name, u.last_name)), ''), u.user_name, '')
            WHEN sc.transaction_type = 'purchase' THEN COALESCE(b_purchase.branch_name, '')
            WHEN sc.transaction_type = 'return' THEN COALESCE(s.company_name, '')
            WHEN sc.transaction_type = 'adjustment' THEN ''
            ELSE ''
        END AS destination_name
    FROM StockItemCertificate sc
    LEFT JOIN StockItem si ON sc.item_ID = si.item_ID
    LEFT JOIN products p ON si.product_ID = p.product_ID
    LEFT JOIN PurchaseOrder po ON po.purchase_order_ID = si.purchase_order_ID
    LEFT JOIN Supplier s ON s.supplier_ID = po.supplier_ID
    LEFT JOIN Branch b_purchase ON b_purchase.branch_ID = po.branch_ID
    LEFT JOIN Branch b_item ON b_item.branch_ID = si.branch_ID
    LEFT JOIN CustomerOrder co ON co.order_ID = si.customer_order_ID
    LEFT JOIN Branch b_sale ON b_sale.branch_ID = co.branch_ID
    LEFT JOIN Customer c ON c.customer_ID = co.customer_ID
    LEFT JOIN `User` u ON u.user_name = c.user_name
) AS t
GROUP BY
    t.productId,
    t.batchId,
    t.productName,
    t.type,
    t.unit,
    t.source_name,
    t.destination_name
ORDER BY MAX(t.time) DESC
LIMIT 100;

-- 7. Employees View
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
    COALESCE(b.branch_name, 'Not Assigned') AS branch_name,
    COALESCE(b.branch_ID, 0) AS branch_id,
    u.user_name AS username,
    s.created_at,
    s.phone AS staff_phone
FROM Staff s
JOIN User u ON s.user_name = u.user_name
LEFT JOIN Branch b ON s.branch_ID = b.branch_ID;

-- 8. Product Inventory by Branch View
CREATE OR REPLACE VIEW v_manager_product_inventory_by_branch AS
SELECT
    i.product_ID,
    p.product_name,
    p.sku,
    i.branch_ID,
    b.branch_name,
    SUM(i.quantity_on_hand) AS total_stock
FROM Inventory i
JOIN products p ON i.product_ID = p.product_ID
JOIN Branch b ON i.branch_ID = b.branch_ID
GROUP BY i.product_ID, p.product_name, p.sku, i.branch_ID, b.branch_name;

-- 9. Product Supplier Pricing View
CREATE OR REPLACE VIEW v_manager_product_supplier_pricing AS
SELECT
    sp.product_ID,
    p.product_name,
    p.sku,
    sp.supplier_ID,
    s.company_name AS supplier_name,
    sp.price AS unit_cost,
    p.unit_price AS selling_price
FROM SupplierProduct sp
JOIN products p ON sp.product_ID = p.product_ID
JOIN Supplier s ON sp.supplier_ID = s.supplier_ID;


-- 10. Expiry Alerts View
CREATE OR REPLACE VIEW v_expiry_alerts AS
SELECT 
    p.product_name,
    i.batch_ID,
    i.date_expired,
    DATEDIFF(i.date_expired, CURDATE()) AS remaining_days,
    i.quantity_on_hand,
    CASE 
        WHEN DATEDIFF(i.date_expired, CURDATE()) <= 7 THEN 'Urgent Action'
        WHEN DATEDIFF(i.date_expired, CURDATE()) <= 30 THEN 'Promotion Action'
        ELSE 'Normal Monitoring'
    END AS suggested_action
FROM Inventory i
JOIN products p ON i.product_ID = p.product_ID
WHERE i.date_expired IS NOT NULL 
  AND i.date_expired >= CURDATE()
ORDER BY i.date_expired ASC;

-- 11. Low Stock Alerts View
CREATE OR REPLACE VIEW v_low_stock_alerts AS
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
FROM Inventory i
JOIN products p ON i.product_ID = p.product_ID
WHERE i.quantity_on_hand <= 10
ORDER BY i.quantity_on_hand ASC;

-- 12. Alert Summary View
CREATE OR REPLACE VIEW v_alert_summary AS
SELECT 
    'expiry' AS alert_type,
    COUNT(*) AS count
FROM v_expiry_alerts
UNION ALL
SELECT 
    'low_stock' AS alert_type,
    COUNT(*) AS count
FROM v_low_stock_alerts;



-- 13. Customer Profile View
CREATE OR REPLACE VIEW v_customer_profile AS
SELECT 
    c.customer_ID,
    CONCAT(u.first_name, ' ', u.last_name) as full_name,
    u.user_name,
    COALESCE(c.phone, u.user_telephone) as phone,
    COALESCE(c.email, u.user_email) as email,
    c.loyalty_level,
    c.gender,
    c.address,
    DATE(c.created_at) as registered_date,
    COUNT(DISTINCT co.order_ID) as order_count,
    COALESCE(SUM(co.total_amount), 0) as total_spent,
    MAX(co.shipping_address) as last_shipping_address
FROM Customer c
JOIN User u ON c.user_name = u.user_name
LEFT JOIN CustomerOrder co ON c.customer_ID = co.customer_ID
GROUP BY c.customer_ID, u.first_name, u.last_name, u.user_name,
         c.phone, c.email, u.user_telephone, u.user_email,
         c.loyalty_level, c.gender, c.address, c.created_at;