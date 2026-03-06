USE mydb;

DROP VIEW IF EXISTS v_customer_profile;
DROP VIEW IF EXISTS product_branch_view;
DROP VIEW IF EXISTS product_catalog_view;
DROP VIEW IF EXISTS v_order_history;
DROP VIEW IF EXISTS v_favorite_products;
DROP VIEW IF EXISTS v_wishlist_products;
DROP VIEW IF EXISTS v_customer_carts_by_branch;



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
WITH customer_product_stats AS (
    SELECT 
        co.customer_ID,
        p.product_ID,
        p.product_name,
        p.sku,
        SUM(oi.quantity) AS total_quantity,  
        COUNT(DISTINCT co.order_ID) AS order_count,  
        SUM(oi.quantity * oi.unit_price) AS total_spent 
    FROM OrderItem oi
    JOIN products p ON oi.product_ID = p.product_ID
    JOIN CustomerOrder co ON oi.order_ID = co.order_ID
    WHERE co.status = 'Completed'
    GROUP BY co.customer_ID, p.product_ID, p.product_name, p.sku
),
ranked_products AS (
    SELECT 
        customer_ID,
        product_ID,
        product_name,
        sku,
        total_quantity,
        order_count,
        total_spent,
        ROW_NUMBER() OVER (
            PARTITION BY customer_ID 
            ORDER BY total_quantity DESC,
            order_count DESC,              
            total_spent DESC              
        ) AS product_rank
    FROM customer_product_stats
    WHERE total_quantity > 0  
)
SELECT 
    customer_ID,
    product_ID,
    product_name,
    sku,
    total_quantity,
    order_count,
    total_spent,
    product_rank
FROM ranked_products
WHERE product_rank = 1 ; 

-- 6
CREATE OR REPLACE VIEW v_wishlist_products AS
SELECT 
    co.order_ID,
    co.customer_id,
    co.branch_id,
    b.branch_name,
    oi.item_ID,
    oi.product_ID,
    p.product_name,
    p.unit_price,
    oi.quantity,
    (oi.unit_price * oi.quantity) AS item_total,
    si.batch_ID,
    si.status as stock_item_status,
    CASE 
        WHEN si.status = 'pending' THEN 'locked'
        WHEN si.status = 'in_stock' THEN 'in stock'
        ELSE 'not available'
    END AS stock_status,
    co.status as order_status,
    co.total_amount
FROM CustomerOrder co
JOIN Branch b ON co.branch_id = b.branch_ID
JOIN OrderItem oi ON co.order_ID = oi.order_ID
JOIN products p ON oi.product_ID = p.product_ID
LEFT JOIN StockItem si ON oi.item_ID = si.item_ID
WHERE co.status = 'Pending';

-- 7
CREATE OR REPLACE VIEW v_customer_carts_by_branch AS
SELECT 
    co.customer_id,
    co.branch_id,
    b.branch_name,
    co.order_ID,
    COUNT(DISTINCT oi.product_ID) as unique_products,
    COUNT(oi.item_ID) as total_items,
    SUM(oi.quantity) as total_quantity,
    SUM(oi.quantity * oi.unit_price) as total_amount,
    GROUP_CONCAT(DISTINCT p.product_name SEPARATOR ', ') as product_list,
    co.created_at
FROM CustomerOrder co
JOIN Branch b ON co.branch_id = b.branch_ID
JOIN OrderItem oi ON co.order_ID = oi.order_ID
JOIN products p ON oi.product_ID = p.product_ID
WHERE co.status = 'Pending'
GROUP BY co.order_ID, co.branch_id, b.branch_name
ORDER BY b.branch_name;


 CREATE USER IF NOT EXISTS 'login_user'@'localhost' IDENTIFIED BY 'LoginP@ss123!';


GRANT SELECT ON mydb.User TO 'login_user'@'localhost';
GRANT SELECT ON mydb.Customer TO 'login_user'@'localhost';
GRANT SELECT ON mydb.Supplier TO 'login_user'@'localhost';
GRANT SELECT ON mydb.Staff TO 'login_user'@'localhost';
GRANT INSERT ON mydb.User TO 'login_user'@'localhost';
GRANT INSERT ON mydb.Customer TO 'login_user'@'localhost';
GRANT SELECT, UPDATE ON mydb.Inventory TO 'login_user'@'localhost';
GRANT SELECT ON mydb.StockItem TO 'login_user'@'localhost';
GRANT INSERT ON mydb.StockItemCertificate TO 'login_user'@'localhost';
DROP USER IF EXISTS 'customer_user'@'localhost';
CREATE USER 'customer_user'@'localhost' IDENTIFIED BY 'YourPassword123!';
GRANT SELECT ON mydb.v_customer_profile TO 'customer_user'@'localhost';
GRANT SELECT ON mydb.v_order_history TO 'customer_user'@'localhost';
GRANT SELECT ON mydb.v_wishlist_products TO 'customer_user'@'localhost';
GRANT SELECT ON mydb.v_customer_carts_by_branch TO 'customer_user'@'localhost';
GRANT SELECT ON mydb.v_favorite_products TO 'customer_user'@'localhost';
GRANT SELECT ON mydb.product_catalog_view TO 'customer_user'@'localhost';
GRANT SELECT ON mydb.product_branch_view TO 'customer_user'@'localhost';
GRANT SELECT ON mydb.Branch TO 'customer_user'@'localhost';
GRANT SELECT ON mydb.Categories TO 'customer_user'@'localhost';
GRANT SELECT ON mydb.products TO 'customer_user'@'localhost';
GRANT SELECT, UPDATE ON mydb.Customer TO 'customer_user'@'localhost';
GRANT SELECT, UPDATE ON mydb.User TO 'customer_user'@'localhost';
GRANT SELECT, INSERT, UPDATE, Delete ON mydb.CustomerOrder TO 'customer_user'@'localhost';
GRANT SELECT, INSERT, UPDATE, DELETE ON mydb.OrderItem TO 'customer_user'@'localhost';
GRANT SELECT, update ON mydb.Inventory TO 'customer_user'@'localhost';
GRANT SELECT, UPDATE ON mydb.StockItem TO 'customer_user'@'localhost';
GRANT LOCK TABLES ON mydb.* TO 'customer_user'@'localhost';
GRANT TRIGGER ON mydb.* TO 'customer_user'@'localhost';
GRANT EXECUTE ON PROCEDURE mydb.ProcessCustomerOrder TO 'customer_user'@'localhost';

FLUSH PRIVILEGES;