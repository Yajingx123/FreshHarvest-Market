USE mydb;

DROP VIEW IF EXISTS v_supplier_products;


-- Supplier Products View - Products available from supplier
CREATE OR REPLACE VIEW v_supplier_products AS
SELECT
    sp.supplier_ID,
    s.company_name AS supplier_name,
    s.contact_person,
    s.email AS supplier_email,
    s.phone AS supplier_phone,
    sp.product_ID,
    p.product_name,
    p.sku,
    p.description,
    sp.price AS price,
    p.unit_price AS retail_price,
    p.unit,
    c.category_name,
    (p.unit_price - sp.price) AS profit_margin,
    ROUND(((p.unit_price - sp.price) / sp.price * 100), 2) AS profit_margin_percentage
FROM SupplierProduct sp
JOIN Supplier s ON sp.supplier_ID = s.supplier_ID
JOIN products p ON sp.product_ID = p.product_ID
JOIN Categories c ON p.category_id = c.category_id
WHERE p.status = 'active'
ORDER BY s.company_name, p.product_name;


-- 先删除已存在的用户
DROP USER IF EXISTS 'supplier_user'@'localhost';
-- 1. 创建供应商用户
CREATE USER 'supplier_user'@'localhost' IDENTIFIED BY 'YourPassword123!';

-- 2. 授予基础权限（不需要第三步的复杂视图）
GRANT SELECT ON mydb.PurchaseOrder TO 'supplier_user'@'localhost';
GRANT SELECT ON mydb.Branch TO 'supplier_user'@'localhost';
GRANT SELECT ON mydb.products TO 'supplier_user'@'localhost';
GRANT SELECT ON mydb.User TO 'supplier_user'@'localhost';
GRANT SELECT ON mydb.Supplier TO 'supplier_user'@'localhost';
GRANT SELECT ON mydb.Staff TO 'supplier_user'@'localhost';
GRANT SELECT ON mydb.PurchaseItem TO 'supplier_user'@'localhost';
GRANT SELECT ON mydb.supplierproduct TO 'supplier_user'@'localhost';
GRANT SELECT ON mydb.StockItem TO 'supplier_user'@'localhost';
GRANT SELECT ON mydb.StockItemCertificate TO 'supplier_user'@'localhost';
GRANT SELECT ON mydb.v_supplier_products TO 'supplier_user'@'localhost';

-- 仅授予必要UPDATE权限
GRANT UPDATE (status) ON mydb.PurchaseOrder TO 'supplier_user'@'localhost';
GRANT UPDATE ON mydb.SupplierProduct TO 'supplier_user'@'localhost';
GRANT UPDATE ON mydb.User TO 'supplier_user'@'localhost';
GRANT UPDATE ON mydb.Supplier TO 'supplier_user'@'localhost';

-- 刷新权限
FLUSH PRIVILEGES;
