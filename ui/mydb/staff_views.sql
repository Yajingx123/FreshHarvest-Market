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

-- 1) Three-Table Join (Branch + Staff + Inventory + Products)
CREATE OR REPLACE VIEW v_staff_branch_staff_inventory AS
SELECT
    b.branch_ID,
    b.branch_name,
    s.staff_ID,
    s.user_name,
    i.batch_ID,
    i.product_ID,
    p.product_name,
    i.quantity_on_hand
FROM Branch b
JOIN Staff s ON s.branch_ID = b.branch_ID
JOIN Inventory i ON i.branch_ID = b.branch_ID
JOIN products p ON i.product_ID = p.product_ID;

-- 2) EXISTS / NOT EXISTS
-- Employees who work in a branch located in London (address contains "London").
CREATE OR REPLACE VIEW v_staff_in_london_branch AS
SELECT
    s.staff_ID,
    s.user_name,
    b.branch_ID,
    b.branch_name,
    b.address
FROM Staff s
JOIN Branch b ON s.branch_ID = b.branch_ID
WHERE EXISTS (
    SELECT 1
    FROM Branch b2
    WHERE b2.branch_ID = s.branch_ID
      AND b2.address LIKE '%London%'
);

-- Branches that currently have no staff assigned.
CREATE OR REPLACE VIEW v_branches_without_staff AS
SELECT
    b.branch_ID,
    b.branch_name,
    b.address
FROM Branch b
WHERE NOT EXISTS (
    SELECT 1
    FROM Staff s
    WHERE s.branch_ID = b.branch_ID
);

-- 3) Grouping + Aggregation
-- Count how many stock certificate actions each staff member has handled per branch.
CREATE OR REPLACE VIEW v_staff_stockitem_counts AS
SELECT
    s.branch_ID,
    s.staff_ID,
    s.user_name,
    COUNT(*) AS action_count
FROM Staff s
JOIN StockItemCertificate c ON c.transaction_ID = s.staff_ID
GROUP BY s.branch_ID, s.staff_ID, s.user_name
ORDER BY s.branch_ID, action_count DESC;

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
GRANT SELECT ON mydb.v_staff_branch_staff_inventory TO 'm1@localhost'@'localhost';
GRANT SELECT ON mydb.v_staff_in_london_branch TO 'm1@localhost'@'localhost';
GRANT SELECT ON mydb.v_branches_without_staff TO 'm1@localhost'@'localhost';
GRANT SELECT ON mydb.v_staff_stockitem_counts TO 'm1@localhost'@'localhost';
GRANT SELECT ON mydb.v_staff_branch_unstocked_products TO 'm1@localhost'@'localhost';

GRANT SELECT ON mydb.v_staff_inventory_batches TO 'm2@localhost'@'localhost';
GRANT SELECT ON mydb.v_staff_branch_staff_inventory TO 'm2@localhost'@'localhost';
GRANT SELECT ON mydb.v_staff_in_london_branch TO 'm2@localhost'@'localhost';
GRANT SELECT ON mydb.v_branches_without_staff TO 'm2@localhost'@'localhost';
GRANT SELECT ON mydb.v_staff_stockitem_counts TO 'm2@localhost'@'localhost';
GRANT SELECT ON mydb.v_staff_branch_unstocked_products TO 'm2@localhost'@'localhost';

GRANT SELECT ON mydb.v_staff_inventory_batches TO 's1_b1@localhost'@'localhost';
GRANT SELECT ON mydb.v_staff_branch_staff_inventory TO 's1_b1@localhost'@'localhost';
GRANT SELECT ON mydb.v_staff_in_london_branch TO 's1_b1@localhost'@'localhost';
GRANT SELECT ON mydb.v_branches_without_staff TO 's1_b1@localhost'@'localhost';
GRANT SELECT ON mydb.v_staff_stockitem_counts TO 's1_b1@localhost'@'localhost';
GRANT SELECT ON mydb.v_staff_branch_unstocked_products TO 's1_b1@localhost'@'localhost';

GRANT SELECT ON mydb.v_staff_inventory_batches TO 's2_b1@localhost'@'localhost';
GRANT SELECT ON mydb.v_staff_branch_staff_inventory TO 's2_b1@localhost'@'localhost';
GRANT SELECT ON mydb.v_staff_in_london_branch TO 's2_b1@localhost'@'localhost';
GRANT SELECT ON mydb.v_branches_without_staff TO 's2_b1@localhost'@'localhost';
GRANT SELECT ON mydb.v_staff_stockitem_counts TO 's2_b1@localhost'@'localhost';
GRANT SELECT ON mydb.v_staff_branch_unstocked_products TO 's2_b1@localhost'@'localhost';

GRANT SELECT ON mydb.v_staff_inventory_batches TO 'd_b1@localhost'@'localhost';
GRANT SELECT ON mydb.v_staff_branch_staff_inventory TO 'd_b1@localhost'@'localhost';
GRANT SELECT ON mydb.v_staff_in_london_branch TO 'd_b1@localhost'@'localhost';
GRANT SELECT ON mydb.v_branches_without_staff TO 'd_b1@localhost'@'localhost';
GRANT SELECT ON mydb.v_staff_stockitem_counts TO 'd_b1@localhost'@'localhost';
GRANT SELECT ON mydb.v_staff_branch_unstocked_products TO 'd_b1@localhost'@'localhost';

GRANT SELECT ON mydb.v_staff_inventory_batches TO 'm3@localhost'@'localhost';
GRANT SELECT ON mydb.v_staff_branch_staff_inventory TO 'm3@localhost'@'localhost';
GRANT SELECT ON mydb.v_staff_in_london_branch TO 'm3@localhost'@'localhost';
GRANT SELECT ON mydb.v_branches_without_staff TO 'm3@localhost'@'localhost';
GRANT SELECT ON mydb.v_staff_stockitem_counts TO 'm3@localhost'@'localhost';
GRANT SELECT ON mydb.v_staff_branch_unstocked_products TO 'm3@localhost'@'localhost';

GRANT SELECT ON mydb.v_staff_inventory_batches TO 'm4@localhost'@'localhost';
GRANT SELECT ON mydb.v_staff_branch_staff_inventory TO 'm4@localhost'@'localhost';
GRANT SELECT ON mydb.v_staff_in_london_branch TO 'm4@localhost'@'localhost';
GRANT SELECT ON mydb.v_branches_without_staff TO 'm4@localhost'@'localhost';
GRANT SELECT ON mydb.v_staff_stockitem_counts TO 'm4@localhost'@'localhost';
GRANT SELECT ON mydb.v_staff_branch_unstocked_products TO 'm4@localhost'@'localhost';

GRANT SELECT ON mydb.v_staff_inventory_batches TO 's1_b2@localhost'@'localhost';
GRANT SELECT ON mydb.v_staff_branch_staff_inventory TO 's1_b2@localhost'@'localhost';
GRANT SELECT ON mydb.v_staff_in_london_branch TO 's1_b2@localhost'@'localhost';
GRANT SELECT ON mydb.v_branches_without_staff TO 's1_b2@localhost'@'localhost';
GRANT SELECT ON mydb.v_staff_stockitem_counts TO 's1_b2@localhost'@'localhost';
GRANT SELECT ON mydb.v_staff_branch_unstocked_products TO 's1_b2@localhost'@'localhost';

GRANT SELECT ON mydb.v_staff_inventory_batches TO 's2_b2@localhost'@'localhost';
GRANT SELECT ON mydb.v_staff_branch_staff_inventory TO 's2_b2@localhost'@'localhost';
GRANT SELECT ON mydb.v_staff_in_london_branch TO 's2_b2@localhost'@'localhost';
GRANT SELECT ON mydb.v_branches_without_staff TO 's2_b2@localhost'@'localhost';
GRANT SELECT ON mydb.v_staff_stockitem_counts TO 's2_b2@localhost'@'localhost';
GRANT SELECT ON mydb.v_staff_branch_unstocked_products TO 's2_b2@localhost'@'localhost';

GRANT SELECT ON mydb.v_staff_inventory_batches TO 'd_b2@localhost'@'localhost';
GRANT SELECT ON mydb.v_staff_branch_staff_inventory TO 'd_b2@localhost'@'localhost';
GRANT SELECT ON mydb.v_staff_in_london_branch TO 'd_b2@localhost'@'localhost';
GRANT SELECT ON mydb.v_branches_without_staff TO 'd_b2@localhost'@'localhost';
GRANT SELECT ON mydb.v_staff_stockitem_counts TO 'd_b2@localhost'@'localhost';
GRANT SELECT ON mydb.v_staff_branch_unstocked_products TO 'd_b2@localhost'@'localhost';

GRANT SELECT ON mydb.v_staff_inventory_batches TO 'm5@localhost'@'localhost';
GRANT SELECT ON mydb.v_staff_branch_staff_inventory TO 'm5@localhost'@'localhost';
GRANT SELECT ON mydb.v_staff_in_london_branch TO 'm5@localhost'@'localhost';
GRANT SELECT ON mydb.v_branches_without_staff TO 'm5@localhost'@'localhost';
GRANT SELECT ON mydb.v_staff_stockitem_counts TO 'm5@localhost'@'localhost';
GRANT SELECT ON mydb.v_staff_branch_unstocked_products TO 'm5@localhost'@'localhost';

GRANT SELECT ON mydb.v_staff_inventory_batches TO 'm6@localhost'@'localhost';
GRANT SELECT ON mydb.v_staff_branch_staff_inventory TO 'm6@localhost'@'localhost';
GRANT SELECT ON mydb.v_staff_in_london_branch TO 'm6@localhost'@'localhost';
GRANT SELECT ON mydb.v_branches_without_staff TO 'm6@localhost'@'localhost';
GRANT SELECT ON mydb.v_staff_stockitem_counts TO 'm6@localhost'@'localhost';
GRANT SELECT ON mydb.v_staff_branch_unstocked_products TO 'm6@localhost'@'localhost';

GRANT SELECT ON mydb.v_staff_inventory_batches TO 's1_b3@localhost'@'localhost';
GRANT SELECT ON mydb.v_staff_branch_staff_inventory TO 's1_b3@localhost'@'localhost';
GRANT SELECT ON mydb.v_staff_in_london_branch TO 's1_b3@localhost'@'localhost';
GRANT SELECT ON mydb.v_branches_without_staff TO 's1_b3@localhost'@'localhost';
GRANT SELECT ON mydb.v_staff_stockitem_counts TO 's1_b3@localhost'@'localhost';
GRANT SELECT ON mydb.v_staff_branch_unstocked_products TO 's1_b3@localhost'@'localhost';

GRANT SELECT ON mydb.v_staff_inventory_batches TO 's2_b3@localhost'@'localhost';
GRANT SELECT ON mydb.v_staff_branch_staff_inventory TO 's2_b3@localhost'@'localhost';
GRANT SELECT ON mydb.v_staff_in_london_branch TO 's2_b3@localhost'@'localhost';
GRANT SELECT ON mydb.v_branches_without_staff TO 's2_b3@localhost'@'localhost';
GRANT SELECT ON mydb.v_staff_stockitem_counts TO 's2_b3@localhost'@'localhost';
GRANT SELECT ON mydb.v_staff_branch_unstocked_products TO 's2_b3@localhost'@'localhost';

GRANT SELECT ON mydb.v_staff_inventory_batches TO 'd_b3@localhost'@'localhost';
GRANT SELECT ON mydb.v_staff_branch_staff_inventory TO 'd_b3@localhost'@'localhost';
GRANT SELECT ON mydb.v_staff_in_london_branch TO 'd_b3@localhost'@'localhost';
GRANT SELECT ON mydb.v_branches_without_staff TO 'd_b3@localhost'@'localhost';
GRANT SELECT ON mydb.v_staff_stockitem_counts TO 'd_b3@localhost'@'localhost';
GRANT SELECT ON mydb.v_staff_branch_unstocked_products TO 'd_b3@localhost'@'localhost';
