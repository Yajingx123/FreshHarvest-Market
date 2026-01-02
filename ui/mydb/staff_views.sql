USE mydb;

-- ============================================================
-- Staff Views: advanced query examples + inventory query view
-- ============================================================

DROP VIEW IF EXISTS v_staff_inventory_batches;
DROP VIEW IF EXISTS v_staff_branch_staff_inventory;
DROP VIEW IF EXISTS v_staff_in_london_branch;
DROP VIEW IF EXISTS v_branches_without_staff;
DROP VIEW IF EXISTS v_staff_stockitem_counts;
DROP VIEW IF EXISTS v_staff_branch_unstocked_products;
DROP VIEW IF EXISTS v_staff_order_overview;
DROP VIEW IF EXISTS v_staff_order_items_summary;
DROP VIEW IF EXISTS v_staff_profile_detail;
DROP VIEW IF EXISTS v_staff_branch_employees;
DROP VIEW IF EXISTS v_staff_inventory_restock_stats;
DROP VIEW IF EXISTS v_staff_order_daily_summary;
DROP VIEW IF EXISTS v_staff_branch_active_staff_count;
DROP VIEW IF EXISTS v_staff_stockitem_adjustment_actor;
DROP VIEW IF EXISTS v_staff_restock_actor;
DROP VIEW IF EXISTS v_staff_supplier_pricing;
DROP VIEW IF EXISTS v_staff_product_category;

-- 1) Multi-Table Joins (inner + outer joins)
-- Inventory batches with product/category/branch/supplier details.
CREATE OR REPLACE VIEW v_staff_inventory_batches AS
SELECT
    i.product_ID,
    i.batch_ID,
    i.branch_ID,
    i.quantity_on_hand,
    i.quantity_received,
    i.received_date,
    i.date_expired,
    p.product_name,
    p.sku,
    i.unit_cost,
    p.unit_price,
    p.category_id,
    c.parent_category_id,
    b.branch_name,
    s.supplier_ID AS supplier_id,
    s.company_name AS supplier_name,
    s.contact_person AS supplier_contact,
    s.phone AS supplier_phone,
    s.address AS supplier_address
FROM Inventory i
JOIN products p ON i.product_ID = p.product_ID
LEFT JOIN Categories c ON p.category_id = c.category_id
LEFT JOIN Branch b ON i.branch_ID = b.branch_ID
LEFT JOIN PurchaseOrder po ON i.order_ID = po.purchase_order_ID
LEFT JOIN Supplier s ON po.supplier_ID = s.supplier_ID;

-- 4) Unstocked products for a staff member's branch
-- Products not yet sold/stocked in the current staff's branch (for new purchase).
CREATE OR REPLACE VIEW v_staff_branch_unstocked_products AS
SELECT
    b.branch_ID,
    b.branch_name,
    p.product_ID,
    p.product_name,
    p.sku,
    (SELECT MIN(sp.price) FROM SupplierProduct sp WHERE sp.product_ID = p.product_ID) AS unit_cost,
    p.unit_price,
    p.unit,
    p.description,
    p.category_id,
    c.parent_category_id,
    c.category_name
FROM Branch b
CROSS JOIN products p
JOIN Categories c ON p.category_id = c.category_id
WHERE p.status = 'active'
  AND NOT EXISTS (
      SELECT 1
      FROM Inventory i
      WHERE i.product_ID = p.product_ID
        AND i.branch_ID = b.branch_ID
  );

-- 5) Staff order overview (orders + customer + user)
CREATE OR REPLACE VIEW v_staff_order_overview AS
SELECT
    co.order_ID,
    co.order_date,
    co.total_amount,
    co.status,
    co.shipping_address,
    co.branch_ID,
    c.customer_ID,
    c.phone AS customer_phone,
    c.email AS customer_email,
    c.loyalty_level,
    u.first_name,
    u.last_name
FROM CustomerOrder co
LEFT JOIN Customer c ON co.customer_ID = c.customer_ID
LEFT JOIN `User` u ON c.user_name = u.user_name;

-- 6) Staff order items summary (aggregated by order + product)
CREATE OR REPLACE VIEW v_staff_order_items_summary AS
SELECT
    co.branch_ID,
    oi.order_ID,
    oi.product_ID,
    p.product_name,
    p.sku,
    COUNT(*) AS quantity,
    MAX(oi.unit_price) AS unit_price
FROM OrderItem oi
JOIN CustomerOrder co ON oi.order_ID = co.order_ID
JOIN products p ON oi.product_ID = p.product_ID
GROUP BY co.branch_ID, oi.order_ID, oi.product_ID, p.product_name, p.sku;

-- 7) Staff profile detail (staff + branch + user)
CREATE OR REPLACE VIEW v_staff_profile_detail AS
SELECT
    s.staff_ID,
    s.branch_ID,
    s.position,
    s.phone,
    s.hire_date,
    s.status,
    s.user_name,
    b.branch_name,
    u.first_name,
    u.last_name,
    u.user_email,
    u.user_telephone
FROM Staff s
LEFT JOIN Branch b ON s.branch_ID = b.branch_ID
LEFT JOIN `User` u ON s.user_name = u.user_name;

-- 8) Branch staff list (staff + user)
CREATE OR REPLACE VIEW v_staff_branch_employees AS
SELECT
    s.branch_ID,
    s.staff_ID,
    s.position,
    s.phone AS staff_phone,
    s.status,
    u.first_name,
    u.last_name,
    u.user_email,
    u.user_telephone
FROM Staff s
LEFT JOIN `User` u ON s.user_name = u.user_name;

-- 9) Inventory totals per branch/product (for restock alerts)
CREATE OR REPLACE VIEW v_staff_inventory_restock_stats AS
SELECT
    branch_ID,
    product_ID,
    SUM(quantity_on_hand) AS total_stock,
    SUM(quantity_received) AS total_received
FROM Inventory
GROUP BY branch_ID, product_ID;

-- 10) Daily order aggregates per branch/status
CREATE OR REPLACE VIEW v_staff_order_daily_summary AS
SELECT
    branch_ID,
    DATE(order_date) AS order_day,
    status,
    COUNT(*) AS orders_count,
    COALESCE(SUM(total_amount), 0) AS revenue_total
FROM CustomerOrder
GROUP BY branch_ID, DATE(order_date), status;

-- 11) Active staff count per branch
CREATE OR REPLACE VIEW v_staff_branch_active_staff_count AS
SELECT
    branch_ID,
    COUNT(*) AS staff_count
FROM Staff
WHERE status = 'active'
GROUP BY branch_ID;

-- 12) Last adjustment/transfer/return actor by batch
CREATE OR REPLACE VIEW v_staff_stockitem_adjustment_actor AS
SELECT
    si.batch_ID AS batch_id,
    c.transaction_ID AS staff_id,
    COALESCE(NULLIF(TRIM(CONCAT_WS(' ', u.first_name, u.last_name)), ''), u.user_name, CONCAT('员工#', c.transaction_ID)) AS staff_name,
    c.date AS action_time,
    c.transaction_type
FROM StockItemCertificate c
JOIN StockItem si ON si.item_ID = c.item_ID
LEFT JOIN Staff s ON s.staff_ID = c.transaction_ID
LEFT JOIN User u ON u.user_name = s.user_name;

-- 13) Last restock actor by product/branch
CREATE OR REPLACE VIEW v_staff_restock_actor AS
SELECT
    si.product_ID,
    si.branch_ID,
    c.transaction_ID AS purchase_order_id,
    c.date AS action_time,
    po.date AS order_date,
    s.company_name AS supplier_name,
    c.transaction_type
FROM StockItemCertificate c
JOIN StockItem si ON si.item_ID = c.item_ID
LEFT JOIN PurchaseOrder po ON po.purchase_order_ID = c.transaction_ID
LEFT JOIN Supplier s ON s.supplier_ID = po.supplier_ID;

-- 14) Supplier pricing list (supplier + product)
CREATE OR REPLACE VIEW v_staff_supplier_pricing AS
SELECT
    sp.supplier_ID,
    sp.product_ID,
    sp.price,
    p.unit_price
FROM SupplierProduct sp
JOIN products p ON sp.product_ID = p.product_ID;

-- 15) Product + category mapping
CREATE OR REPLACE VIEW v_staff_product_category AS
SELECT
    p.product_ID,
    p.sku,
    p.product_name,
    p.category_id,
    c.parent_category_id
FROM products p
LEFT JOIN Categories c ON p.category_id = c.category_id;

-- ============================================================
-- Permissions (staff users from newdata.sql)
-- ============================================================

-- NOTE: Run this section with a MySQL admin account (e.g. root).
-- If the DB users already exist, you can comment out the CREATE USER lines.

CREATE USER IF NOT EXISTS 'm1@localhost'@'localhost' IDENTIFIED BY 'Test1234';
CREATE USER IF NOT EXISTS 'm2@localhost'@'localhost' IDENTIFIED BY 'Test1234';
CREATE USER IF NOT EXISTS 's1_b1@localhost'@'localhost' IDENTIFIED BY 'Test1234';
CREATE USER IF NOT EXISTS 's2_b1@localhost'@'localhost' IDENTIFIED BY 'Test1234';
CREATE USER IF NOT EXISTS 'd_b1@localhost'@'localhost' IDENTIFIED BY 'Test1234';
CREATE USER IF NOT EXISTS 'm3@localhost'@'localhost' IDENTIFIED BY 'Test1234';
CREATE USER IF NOT EXISTS 'm4@localhost'@'localhost' IDENTIFIED BY 'Test1234';
CREATE USER IF NOT EXISTS 's1_b2@localhost'@'localhost' IDENTIFIED BY 'Test1234';
CREATE USER IF NOT EXISTS 's2_b2@localhost'@'localhost' IDENTIFIED BY 'Test1234';
CREATE USER IF NOT EXISTS 'd_b2@localhost'@'localhost' IDENTIFIED BY 'Test1234';
CREATE USER IF NOT EXISTS 'm5@localhost'@'localhost' IDENTIFIED BY 'Test1234';
CREATE USER IF NOT EXISTS 'm6@localhost'@'localhost' IDENTIFIED BY 'Test1234';
CREATE USER IF NOT EXISTS 's1_b3@localhost'@'localhost' IDENTIFIED BY 'Test1234';
CREATE USER IF NOT EXISTS 's2_b3@localhost'@'localhost' IDENTIFIED BY 'Test1234';
CREATE USER IF NOT EXISTS 'd_b3@localhost'@'localhost' IDENTIFIED BY 'Test1234';

GRANT SELECT ON mydb.v_staff_inventory_batches TO 'm1@localhost'@'localhost';
GRANT SELECT ON mydb.v_staff_branch_unstocked_products TO 'm1@localhost'@'localhost';
GRANT SELECT ON mydb.v_staff_product_category TO 'm1@localhost'@'localhost';
GRANT SELECT ON mydb.v_staff_order_overview TO 'm1@localhost'@'localhost';
GRANT SELECT ON mydb.v_staff_order_items_summary TO 'm1@localhost'@'localhost';
GRANT SELECT ON mydb.v_staff_profile_detail TO 'm1@localhost'@'localhost';
GRANT SELECT ON mydb.v_staff_branch_employees TO 'm1@localhost'@'localhost';
GRANT SELECT ON mydb.v_staff_inventory_restock_stats TO 'm1@localhost'@'localhost';
GRANT SELECT ON mydb.v_staff_order_daily_summary TO 'm1@localhost'@'localhost';
GRANT SELECT ON mydb.v_staff_branch_active_staff_count TO 'm1@localhost'@'localhost';
GRANT SELECT ON mydb.v_staff_stockitem_adjustment_actor TO 'm1@localhost'@'localhost';
GRANT SELECT ON mydb.v_staff_restock_actor TO 'm1@localhost'@'localhost';
GRANT SELECT ON mydb.v_staff_supplier_pricing TO 'm1@localhost'@'localhost';

GRANT SELECT ON mydb.v_staff_inventory_batches TO 'm2@localhost'@'localhost';
GRANT SELECT ON mydb.v_staff_branch_unstocked_products TO 'm2@localhost'@'localhost';
GRANT SELECT ON mydb.v_staff_product_category TO 'm2@localhost'@'localhost';
GRANT SELECT ON mydb.v_staff_order_overview TO 'm2@localhost'@'localhost';
GRANT SELECT ON mydb.v_staff_order_items_summary TO 'm2@localhost'@'localhost';
GRANT SELECT ON mydb.v_staff_profile_detail TO 'm2@localhost'@'localhost';
GRANT SELECT ON mydb.v_staff_branch_employees TO 'm2@localhost'@'localhost';
GRANT SELECT ON mydb.v_staff_inventory_restock_stats TO 'm2@localhost'@'localhost';
GRANT SELECT ON mydb.v_staff_order_daily_summary TO 'm2@localhost'@'localhost';
GRANT SELECT ON mydb.v_staff_branch_active_staff_count TO 'm2@localhost'@'localhost';
GRANT SELECT ON mydb.v_staff_stockitem_adjustment_actor TO 'm2@localhost'@'localhost';
GRANT SELECT ON mydb.v_staff_restock_actor TO 'm2@localhost'@'localhost';
GRANT SELECT ON mydb.v_staff_supplier_pricing TO 'm2@localhost'@'localhost';

GRANT SELECT ON mydb.v_staff_inventory_batches TO 's1_b1@localhost'@'localhost';
GRANT SELECT ON mydb.v_staff_branch_unstocked_products TO 's1_b1@localhost'@'localhost';
GRANT SELECT ON mydb.v_staff_product_category TO 's1_b1@localhost'@'localhost';
GRANT SELECT ON mydb.v_staff_order_overview TO 's1_b1@localhost'@'localhost';
GRANT SELECT ON mydb.v_staff_order_items_summary TO 's1_b1@localhost'@'localhost';
GRANT SELECT ON mydb.v_staff_profile_detail TO 's1_b1@localhost'@'localhost';
GRANT SELECT ON mydb.v_staff_branch_employees TO 's1_b1@localhost'@'localhost';
GRANT SELECT ON mydb.v_staff_inventory_restock_stats TO 's1_b1@localhost'@'localhost';
GRANT SELECT ON mydb.v_staff_order_daily_summary TO 's1_b1@localhost'@'localhost';
GRANT SELECT ON mydb.v_staff_branch_active_staff_count TO 's1_b1@localhost'@'localhost';
GRANT SELECT ON mydb.v_staff_stockitem_adjustment_actor TO 's1_b1@localhost'@'localhost';
GRANT SELECT ON mydb.v_staff_restock_actor TO 's1_b1@localhost'@'localhost';
GRANT SELECT ON mydb.v_staff_supplier_pricing TO 's1_b1@localhost'@'localhost';

GRANT SELECT ON mydb.v_staff_inventory_batches TO 's2_b1@localhost'@'localhost';
GRANT SELECT ON mydb.v_staff_branch_unstocked_products TO 's2_b1@localhost'@'localhost';
GRANT SELECT ON mydb.v_staff_product_category TO 's2_b1@localhost'@'localhost';
GRANT SELECT ON mydb.v_staff_order_overview TO 's2_b1@localhost'@'localhost';
GRANT SELECT ON mydb.v_staff_order_items_summary TO 's2_b1@localhost'@'localhost';
GRANT SELECT ON mydb.v_staff_profile_detail TO 's2_b1@localhost'@'localhost';
GRANT SELECT ON mydb.v_staff_branch_employees TO 's2_b1@localhost'@'localhost';
GRANT SELECT ON mydb.v_staff_inventory_restock_stats TO 's2_b1@localhost'@'localhost';
GRANT SELECT ON mydb.v_staff_order_daily_summary TO 's2_b1@localhost'@'localhost';
GRANT SELECT ON mydb.v_staff_branch_active_staff_count TO 's2_b1@localhost'@'localhost';
GRANT SELECT ON mydb.v_staff_stockitem_adjustment_actor TO 's2_b1@localhost'@'localhost';
GRANT SELECT ON mydb.v_staff_restock_actor TO 's2_b1@localhost'@'localhost';
GRANT SELECT ON mydb.v_staff_supplier_pricing TO 's2_b1@localhost'@'localhost';

GRANT SELECT ON mydb.v_staff_inventory_batches TO 'd_b1@localhost'@'localhost';
GRANT SELECT ON mydb.v_staff_branch_unstocked_products TO 'd_b1@localhost'@'localhost';
GRANT SELECT ON mydb.v_staff_product_category TO 'd_b1@localhost'@'localhost';
GRANT SELECT ON mydb.v_staff_order_overview TO 'd_b1@localhost'@'localhost';
GRANT SELECT ON mydb.v_staff_order_items_summary TO 'd_b1@localhost'@'localhost';
GRANT SELECT ON mydb.v_staff_profile_detail TO 'd_b1@localhost'@'localhost';
GRANT SELECT ON mydb.v_staff_branch_employees TO 'd_b1@localhost'@'localhost';
GRANT SELECT ON mydb.v_staff_inventory_restock_stats TO 'd_b1@localhost'@'localhost';
GRANT SELECT ON mydb.v_staff_order_daily_summary TO 'd_b1@localhost'@'localhost';
GRANT SELECT ON mydb.v_staff_branch_active_staff_count TO 'd_b1@localhost'@'localhost';
GRANT SELECT ON mydb.v_staff_stockitem_adjustment_actor TO 'd_b1@localhost'@'localhost';
GRANT SELECT ON mydb.v_staff_restock_actor TO 'd_b1@localhost'@'localhost';
GRANT SELECT ON mydb.v_staff_supplier_pricing TO 'd_b1@localhost'@'localhost';

GRANT SELECT ON mydb.v_staff_inventory_batches TO 'm3@localhost'@'localhost';
GRANT SELECT ON mydb.v_staff_branch_unstocked_products TO 'm3@localhost'@'localhost';
GRANT SELECT ON mydb.v_staff_product_category TO 'm3@localhost'@'localhost';
GRANT SELECT ON mydb.v_staff_order_overview TO 'm3@localhost'@'localhost';
GRANT SELECT ON mydb.v_staff_order_items_summary TO 'm3@localhost'@'localhost';
GRANT SELECT ON mydb.v_staff_profile_detail TO 'm3@localhost'@'localhost';
GRANT SELECT ON mydb.v_staff_branch_employees TO 'm3@localhost'@'localhost';
GRANT SELECT ON mydb.v_staff_inventory_restock_stats TO 'm3@localhost'@'localhost';
GRANT SELECT ON mydb.v_staff_order_daily_summary TO 'm3@localhost'@'localhost';
GRANT SELECT ON mydb.v_staff_branch_active_staff_count TO 'm3@localhost'@'localhost';
GRANT SELECT ON mydb.v_staff_stockitem_adjustment_actor TO 'm3@localhost'@'localhost';
GRANT SELECT ON mydb.v_staff_restock_actor TO 'm3@localhost'@'localhost';
GRANT SELECT ON mydb.v_staff_supplier_pricing TO 'm3@localhost'@'localhost';

GRANT SELECT ON mydb.v_staff_inventory_batches TO 'm4@localhost'@'localhost';
GRANT SELECT ON mydb.v_staff_branch_unstocked_products TO 'm4@localhost'@'localhost';
GRANT SELECT ON mydb.v_staff_product_category TO 'm4@localhost'@'localhost';
GRANT SELECT ON mydb.v_staff_order_overview TO 'm4@localhost'@'localhost';
GRANT SELECT ON mydb.v_staff_order_items_summary TO 'm4@localhost'@'localhost';
GRANT SELECT ON mydb.v_staff_profile_detail TO 'm4@localhost'@'localhost';
GRANT SELECT ON mydb.v_staff_branch_employees TO 'm4@localhost'@'localhost';
GRANT SELECT ON mydb.v_staff_inventory_restock_stats TO 'm4@localhost'@'localhost';
GRANT SELECT ON mydb.v_staff_order_daily_summary TO 'm4@localhost'@'localhost';
GRANT SELECT ON mydb.v_staff_branch_active_staff_count TO 'm4@localhost'@'localhost';
GRANT SELECT ON mydb.v_staff_stockitem_adjustment_actor TO 'm4@localhost'@'localhost';
GRANT SELECT ON mydb.v_staff_restock_actor TO 'm4@localhost'@'localhost';
GRANT SELECT ON mydb.v_staff_supplier_pricing TO 'm4@localhost'@'localhost';

GRANT SELECT ON mydb.v_staff_inventory_batches TO 's1_b2@localhost'@'localhost';
GRANT SELECT ON mydb.v_staff_branch_unstocked_products TO 's1_b2@localhost'@'localhost';
GRANT SELECT ON mydb.v_staff_product_category TO 's1_b2@localhost'@'localhost';
GRANT SELECT ON mydb.v_staff_order_overview TO 's1_b2@localhost'@'localhost';
GRANT SELECT ON mydb.v_staff_order_items_summary TO 's1_b2@localhost'@'localhost';
GRANT SELECT ON mydb.v_staff_profile_detail TO 's1_b2@localhost'@'localhost';
GRANT SELECT ON mydb.v_staff_branch_employees TO 's1_b2@localhost'@'localhost';
GRANT SELECT ON mydb.v_staff_inventory_restock_stats TO 's1_b2@localhost'@'localhost';
GRANT SELECT ON mydb.v_staff_order_daily_summary TO 's1_b2@localhost'@'localhost';
GRANT SELECT ON mydb.v_staff_branch_active_staff_count TO 's1_b2@localhost'@'localhost';
GRANT SELECT ON mydb.v_staff_stockitem_adjustment_actor TO 's1_b2@localhost'@'localhost';
GRANT SELECT ON mydb.v_staff_restock_actor TO 's1_b2@localhost'@'localhost';
GRANT SELECT ON mydb.v_staff_supplier_pricing TO 's1_b2@localhost'@'localhost';

GRANT SELECT ON mydb.v_staff_inventory_batches TO 's2_b2@localhost'@'localhost';
GRANT SELECT ON mydb.v_staff_branch_unstocked_products TO 's2_b2@localhost'@'localhost';
GRANT SELECT ON mydb.v_staff_product_category TO 's2_b2@localhost'@'localhost';
GRANT SELECT ON mydb.v_staff_order_overview TO 's2_b2@localhost'@'localhost';
GRANT SELECT ON mydb.v_staff_order_items_summary TO 's2_b2@localhost'@'localhost';
GRANT SELECT ON mydb.v_staff_profile_detail TO 's2_b2@localhost'@'localhost';
GRANT SELECT ON mydb.v_staff_branch_employees TO 's2_b2@localhost'@'localhost';
GRANT SELECT ON mydb.v_staff_inventory_restock_stats TO 's2_b2@localhost'@'localhost';
GRANT SELECT ON mydb.v_staff_order_daily_summary TO 's2_b2@localhost'@'localhost';
GRANT SELECT ON mydb.v_staff_branch_active_staff_count TO 's2_b2@localhost'@'localhost';
GRANT SELECT ON mydb.v_staff_stockitem_adjustment_actor TO 's2_b2@localhost'@'localhost';
GRANT SELECT ON mydb.v_staff_restock_actor TO 's2_b2@localhost'@'localhost';
GRANT SELECT ON mydb.v_staff_supplier_pricing TO 's2_b2@localhost'@'localhost';

GRANT SELECT ON mydb.v_staff_inventory_batches TO 'd_b2@localhost'@'localhost';
GRANT SELECT ON mydb.v_staff_branch_unstocked_products TO 'd_b2@localhost'@'localhost';
GRANT SELECT ON mydb.v_staff_product_category TO 'd_b2@localhost'@'localhost';
GRANT SELECT ON mydb.v_staff_order_overview TO 'd_b2@localhost'@'localhost';
GRANT SELECT ON mydb.v_staff_order_items_summary TO 'd_b2@localhost'@'localhost';
GRANT SELECT ON mydb.v_staff_profile_detail TO 'd_b2@localhost'@'localhost';
GRANT SELECT ON mydb.v_staff_branch_employees TO 'd_b2@localhost'@'localhost';
GRANT SELECT ON mydb.v_staff_inventory_restock_stats TO 'd_b2@localhost'@'localhost';
GRANT SELECT ON mydb.v_staff_order_daily_summary TO 'd_b2@localhost'@'localhost';
GRANT SELECT ON mydb.v_staff_branch_active_staff_count TO 'd_b2@localhost'@'localhost';
GRANT SELECT ON mydb.v_staff_stockitem_adjustment_actor TO 'd_b2@localhost'@'localhost';
GRANT SELECT ON mydb.v_staff_restock_actor TO 'd_b2@localhost'@'localhost';
GRANT SELECT ON mydb.v_staff_supplier_pricing TO 'd_b2@localhost'@'localhost';

GRANT SELECT ON mydb.v_staff_inventory_batches TO 'm5@localhost'@'localhost';
GRANT SELECT ON mydb.v_staff_branch_unstocked_products TO 'm5@localhost'@'localhost';
GRANT SELECT ON mydb.v_staff_product_category TO 'm5@localhost'@'localhost';
GRANT SELECT ON mydb.v_staff_order_overview TO 'm5@localhost'@'localhost';
GRANT SELECT ON mydb.v_staff_order_items_summary TO 'm5@localhost'@'localhost';
GRANT SELECT ON mydb.v_staff_profile_detail TO 'm5@localhost'@'localhost';
GRANT SELECT ON mydb.v_staff_branch_employees TO 'm5@localhost'@'localhost';
GRANT SELECT ON mydb.v_staff_inventory_restock_stats TO 'm5@localhost'@'localhost';
GRANT SELECT ON mydb.v_staff_order_daily_summary TO 'm5@localhost'@'localhost';
GRANT SELECT ON mydb.v_staff_branch_active_staff_count TO 'm5@localhost'@'localhost';
GRANT SELECT ON mydb.v_staff_stockitem_adjustment_actor TO 'm5@localhost'@'localhost';
GRANT SELECT ON mydb.v_staff_restock_actor TO 'm5@localhost'@'localhost';
GRANT SELECT ON mydb.v_staff_supplier_pricing TO 'm5@localhost'@'localhost';

GRANT SELECT ON mydb.v_staff_inventory_batches TO 'm6@localhost'@'localhost';
GRANT SELECT ON mydb.v_staff_branch_unstocked_products TO 'm6@localhost'@'localhost';
GRANT SELECT ON mydb.v_staff_product_category TO 'm6@localhost'@'localhost';
GRANT SELECT ON mydb.v_staff_order_overview TO 'm6@localhost'@'localhost';
GRANT SELECT ON mydb.v_staff_order_items_summary TO 'm6@localhost'@'localhost';
GRANT SELECT ON mydb.v_staff_profile_detail TO 'm6@localhost'@'localhost';
GRANT SELECT ON mydb.v_staff_branch_employees TO 'm6@localhost'@'localhost';
GRANT SELECT ON mydb.v_staff_inventory_restock_stats TO 'm6@localhost'@'localhost';
GRANT SELECT ON mydb.v_staff_order_daily_summary TO 'm6@localhost'@'localhost';
GRANT SELECT ON mydb.v_staff_branch_active_staff_count TO 'm6@localhost'@'localhost';
GRANT SELECT ON mydb.v_staff_stockitem_adjustment_actor TO 'm6@localhost'@'localhost';
GRANT SELECT ON mydb.v_staff_restock_actor TO 'm6@localhost'@'localhost';
GRANT SELECT ON mydb.v_staff_supplier_pricing TO 'm6@localhost'@'localhost';

GRANT SELECT ON mydb.v_staff_inventory_batches TO 's1_b3@localhost'@'localhost';
GRANT SELECT ON mydb.v_staff_branch_unstocked_products TO 's1_b3@localhost'@'localhost';
GRANT SELECT ON mydb.v_staff_product_category TO 's1_b3@localhost'@'localhost';
GRANT SELECT ON mydb.v_staff_order_overview TO 's1_b3@localhost'@'localhost';
GRANT SELECT ON mydb.v_staff_order_items_summary TO 's1_b3@localhost'@'localhost';
GRANT SELECT ON mydb.v_staff_profile_detail TO 's1_b3@localhost'@'localhost';
GRANT SELECT ON mydb.v_staff_branch_employees TO 's1_b3@localhost'@'localhost';
GRANT SELECT ON mydb.v_staff_inventory_restock_stats TO 's1_b3@localhost'@'localhost';
GRANT SELECT ON mydb.v_staff_order_daily_summary TO 's1_b3@localhost'@'localhost';
GRANT SELECT ON mydb.v_staff_branch_active_staff_count TO 's1_b3@localhost'@'localhost';
GRANT SELECT ON mydb.v_staff_stockitem_adjustment_actor TO 's1_b3@localhost'@'localhost';
GRANT SELECT ON mydb.v_staff_restock_actor TO 's1_b3@localhost'@'localhost';
GRANT SELECT ON mydb.v_staff_supplier_pricing TO 's1_b3@localhost'@'localhost';

GRANT SELECT ON mydb.v_staff_inventory_batches TO 's2_b3@localhost'@'localhost';
GRANT SELECT ON mydb.v_staff_branch_unstocked_products TO 's2_b3@localhost'@'localhost';
GRANT SELECT ON mydb.v_staff_product_category TO 's2_b3@localhost'@'localhost';
GRANT SELECT ON mydb.v_staff_order_overview TO 's2_b3@localhost'@'localhost';
GRANT SELECT ON mydb.v_staff_order_items_summary TO 's2_b3@localhost'@'localhost';
GRANT SELECT ON mydb.v_staff_profile_detail TO 's2_b3@localhost'@'localhost';
GRANT SELECT ON mydb.v_staff_branch_employees TO 's2_b3@localhost'@'localhost';
GRANT SELECT ON mydb.v_staff_inventory_restock_stats TO 's2_b3@localhost'@'localhost';
GRANT SELECT ON mydb.v_staff_order_daily_summary TO 's2_b3@localhost'@'localhost';
GRANT SELECT ON mydb.v_staff_branch_active_staff_count TO 's2_b3@localhost'@'localhost';
GRANT SELECT ON mydb.v_staff_stockitem_adjustment_actor TO 's2_b3@localhost'@'localhost';
GRANT SELECT ON mydb.v_staff_restock_actor TO 's2_b3@localhost'@'localhost';
GRANT SELECT ON mydb.v_staff_supplier_pricing TO 's2_b3@localhost'@'localhost';

GRANT SELECT ON mydb.v_staff_inventory_batches TO 'd_b3@localhost'@'localhost';
GRANT SELECT ON mydb.v_staff_branch_unstocked_products TO 'd_b3@localhost'@'localhost';
GRANT SELECT ON mydb.v_staff_product_category TO 'd_b3@localhost'@'localhost';
GRANT SELECT ON mydb.v_staff_order_overview TO 'd_b3@localhost'@'localhost';
GRANT SELECT ON mydb.v_staff_order_items_summary TO 'd_b3@localhost'@'localhost';
GRANT SELECT ON mydb.v_staff_profile_detail TO 'd_b3@localhost'@'localhost';
GRANT SELECT ON mydb.v_staff_branch_employees TO 'd_b3@localhost'@'localhost';
GRANT SELECT ON mydb.v_staff_inventory_restock_stats TO 'd_b3@localhost'@'localhost';
GRANT SELECT ON mydb.v_staff_order_daily_summary TO 'd_b3@localhost'@'localhost';
GRANT SELECT ON mydb.v_staff_branch_active_staff_count TO 'd_b3@localhost'@'localhost';
GRANT SELECT ON mydb.v_staff_stockitem_adjustment_actor TO 'd_b3@localhost'@'localhost';
GRANT SELECT ON mydb.v_staff_restock_actor TO 'd_b3@localhost'@'localhost';
GRANT SELECT ON mydb.v_staff_supplier_pricing TO 'd_b3@localhost'@'localhost';
