USE mydb;

DROP VIEW IF EXISTS v_customer_profile;
DROP VIEW IF EXISTS product_branch_view;
DROP VIEW IF EXISTS product_catalog_view;
DROP VIEW IF EXISTS v_order_history;
DROP VIEW IF EXISTS v_favorite_products;
DROP VIEW IF EXISTS v_wishlist_products;
DROP VIEW IF EXISTS v_customer_product_info;



-- 1. Customer Profile View
CREATE OR REPLACE VIEW v_customer_profile AS
SELECT 
    c.customer_ID,
    CONCAT(u.first_name, ' ', u.last_name) AS full_name,
    u.user_name,
    COALESCE(c.phone, u.user_telephone) AS phone,
    COALESCE(c.email, u.user_email) AS email,
    c.loyalty_level,
    c.gender,
    c.address,
    DATE(c.created_at) AS registered_date,
    COUNT(DISTINCT co.order_ID) AS order_count,
    COALESCE(SUM(co.total_amount), 0) AS total_spent,
    MAX(co.shipping_address) AS last_shipping_address
FROM Customer c
JOIN User u ON c.user_name = u.user_name
LEFT JOIN CustomerOrder co ON c.customer_ID = co.customer_ID
GROUP BY c.customer_ID, u.first_name, u.last_name, u.user_name,
         c.phone, c.email, u.user_telephone, u.user_email,
         c.loyalty_level, c.gender, c.address, c.created_at;

-- 2. Product Branch View (products with branch availability)
CREATE OR REPLACE VIEW product_branch_view AS
SELECT 
    p.product_ID,
    p.product_name,
    p.sku,
    p.unit_price,
    p.description,
    p.status AS product_status,
    c.category_id,
    c.category_name,
    b.branch_ID AS store_id,
    b.branch_name AS store_name,
    b.address AS store_address,
    b.phone AS store_phone,
    b.status AS store_status,
    COALESCE(SUM(i.quantity_on_hand), 0) AS total_stock_in_store,
    COALESCE(SUM(i.quantity_on_hand - i.locked_inventory), 0) AS available_stock_in_store,
    CASE 
        WHEN COALESCE(SUM(i.quantity_on_hand - i.locked_inventory), 0) > 0 THEN 'In Stock'
        ELSE 'Out of Stock'
    END AS stock_status_in_store,
    COUNT(DISTINCT i.batch_ID) AS batch_count,
    MIN(i.received_date) AS earliest_received_date
FROM products p
JOIN Categories c ON p.category_id = c.category_id
JOIN Inventory i ON p.product_ID = i.product_ID
JOIN Branch b ON i.branch_ID = b.branch_ID
WHERE p.status = 'active' AND b.status = 'active'
GROUP BY p.product_ID, p.product_name, p.sku, p.unit_price, p.description, p.status,
         c.category_id, c.category_name,
         b.branch_ID, b.branch_name, b.address, b.phone, b.status
ORDER BY p.product_name, b.branch_name;

-- 3. Product Catalog View
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
    pa.product_attributes,
    COALESCE(inv.total_stock, 0) AS total_stock,
    COALESCE(inv.available_stock, 0) AS available_stock,
    CASE 
        WHEN COALESCE(inv.available_stock, 0) > 0 THEN 'In Stock'
        ELSE 'Out of Stock'
    END AS stock_status,
    si.item_ids AS item_id
FROM products p
JOIN Categories c ON p.category_id = c.category_id
LEFT JOIN (
    SELECT product_id,
           GROUP_CONCAT(DISTINCT CONCAT(attr_name, ': ', attr_value) SEPARATOR ', ') AS product_attributes
    FROM ProductAttribute
    GROUP BY product_id
) pa ON p.product_ID = pa.product_id
LEFT JOIN (
    SELECT product_ID,
           SUM(quantity_on_hand) AS total_stock,
           SUM(quantity_on_hand - locked_inventory) AS available_stock
    FROM Inventory
    GROUP BY product_ID
) inv ON p.product_ID = inv.product_ID
LEFT JOIN (
    SELECT product_ID,
           GROUP_CONCAT(DISTINCT item_ID SEPARATOR ', ') AS item_ids
    FROM StockItem
    GROUP BY product_ID
) si ON p.product_ID = si.product_ID
WHERE p.status = 'active'
ORDER BY c.category_name, p.product_name;

-- 4. Order History View
CREATE OR REPLACE VIEW v_order_history AS
SELECT 
    co.order_ID,
    co.customer_ID,
    co.order_date,
    co.total_amount,
    co.final_amount,
    co.status AS order_status,
    co.shipping_address,
    b.branch_name AS store_name,
    GROUP_CONCAT(
        CONCAT(p.product_name, '(', oi.quantity, 'x¥', oi.unit_price, ')') 
        SEPARATOR '; '
    ) AS product_details,
    COUNT(oi.item_ID) AS item_count,
    SUM(oi.quantity) AS total_quantity
FROM CustomerOrder co
JOIN Branch b ON co.branch_ID = b.branch_ID
LEFT JOIN OrderItem oi ON co.order_ID = oi.order_ID
LEFT JOIN StockItem si ON oi.item_ID = si.item_ID
LEFT JOIN products p ON si.product_ID = p.product_ID
GROUP BY co.order_ID, co.customer_ID, co.order_date, co.total_amount, 
         co.final_amount, co.status, co.shipping_address, b.branch_name
ORDER BY co.order_date DESC;

-- 5. Favorite Products View
CREATE OR REPLACE VIEW v_favorite_products AS
SELECT 
    p.product_ID,
    p.product_name,
    co.customer_ID,
    SUM(oi.quantity * oi.unit_price) AS total_spent,
    COUNT(oi.order_ID) AS purchase_count
FROM OrderItem oi
JOIN products p ON oi.product_ID = p.product_ID
JOIN CustomerOrder co ON oi.order_ID = co.order_ID
WHERE co.status = 'Completed'
GROUP BY p.product_ID, p.product_name, co.customer_ID
ORDER BY total_spent DESC, purchase_count DESC;

-- 6. Wishlist Products View
CREATE OR REPLACE VIEW v_wishlist_products AS
SELECT 
    p.product_id, 
    p.product_name, 
    o.customer_id,
    oi.quantity,
    oi.item_id,
    p.unit_price,
    p.category_id,
    i.quantity_on_hand AS available_stock,
    i.locked_inventory,
    CASE 
        WHEN (i.quantity_on_hand - i.locked_inventory) >= 0 THEN 'In Stock' 
        ELSE 'Out of Stock' 
    END AS stock_status,
    o.branch_id
FROM CustomerOrder o
JOIN OrderItem oi ON o.order_id = oi.order_id
JOIN products p ON oi.product_id = p.product_id
JOIN inventory i ON p.product_id = i.product_id AND o.branch_id = i.branch_id
WHERE o.status = 'Pending'
AND o.customer_id IS NOT NULL;

-- 7. Customer Product Info View
CREATE OR REPLACE VIEW v_customer_product_info AS
SELECT 
    p.product_name,
    c.category_name,
    p.unit_price,
    p.description,
    p.status AS product_status,
    GROUP_CONCAT(DISTINCT CONCAT(pa.attr_name, ': ', pa.attr_value) SEPARATOR ', ') AS attributes
FROM products p
JOIN Categories c ON p.category_id = c.category_id
LEFT JOIN ProductAttribute pa ON p.product_ID = pa.product_id
WHERE p.status = 'active'
GROUP BY p.product_ID, p.product_name, c.category_name, 
         p.unit_price, p.description, p.status;