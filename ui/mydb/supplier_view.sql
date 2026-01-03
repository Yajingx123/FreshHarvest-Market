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