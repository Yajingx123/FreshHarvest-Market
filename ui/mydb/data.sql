USE mydb;

-- Clear old data
SET FOREIGN_KEY_CHECKS = 0;
TRUNCATE TABLE StockItemCertificate;
TRUNCATE TABLE OrderItem;
TRUNCATE TABLE PurchaseItem;
TRUNCATE TABLE StockItem;
TRUNCATE TABLE Inventory;
TRUNCATE TABLE CustomerOrder;
TRUNCATE TABLE PurchaseOrder;
TRUNCATE TABLE Staff;
TRUNCATE TABLE SupplierProduct;
TRUNCATE TABLE Supplier;
TRUNCATE TABLE Customer;
TRUNCATE TABLE `User`;
TRUNCATE TABLE ProductAttribute;
TRUNCATE TABLE CategoryAttribute;
TRUNCATE TABLE products;
TRUNCATE TABLE Categories;
TRUNCATE TABLE Branch;
SET FOREIGN_KEY_CHECKS = 1;

-- 1) Categories table
INSERT INTO Categories (category_id, category_name, parent_category_id, description) VALUES
(1,'Fresh',NULL,'FreshHarvest main category'),
(2,'Fruit & Vegetable',1,'Parent category for vegetables and fruits'),
(3,'Meat & Egg',1,'Parent category for meat and eggs'),
(4,'Aquatic product',1,'Parent category for aquatic products'),
(5,'Vegetables',2,'Seasonal vegetables, leafy greens, root vegetables'),
(6,'Fruits',2,'Seasonal fruits, imported fruits'),
(7,'Meat',3,'Pork, beef, lamb, poultry, etc.'),
(8,'Eggs',3,'Eggs and other egg products'),
(9,'Fish',4,'Fish and seafood'),
(10,'Shrimp',4,'Shrimp and prawns'),
(11,'Other Aquatic Products',4,'Seaweed and other aquatic plants/animals');

-- Add CategoryAttribute table data
INSERT INTO CategoryAttribute (category_id, attr_name, data_type, is_required) VALUES
-- Vegetable category(5) attributes
(5, 'Origin', 'text', TRUE),
(5, 'Net Weight', 'number', TRUE),
(5, 'Preservation Method', 'text', TRUE),
(5, 'Cultivation Method', 'text', FALSE),
(5, 'Harvest Date', 'date', FALSE),

-- Fruit category(6) attributes
(6, 'Origin', 'text', TRUE),
(6, 'Net Weight', 'number', TRUE),
(6, 'Sweetness Level', 'text', FALSE),
(6, 'Preservation Method', 'text', TRUE),
(6, 'Variety', 'text', FALSE),

-- Meat category(7) attributes
(7, 'Origin', 'text', TRUE),
(7, 'Cut/Type', 'text', TRUE),
(7, 'Refrigerated/Frozen', 'text', TRUE),
(7, 'Net Weight', 'number', TRUE),
(7, 'Grade', 'text', FALSE),
(7, 'Raising Method', 'text', FALSE),

-- Egg category(8) attributes
(8, 'Origin', 'text', TRUE),
(8, 'Variety', 'text', FALSE),
(8, 'Specification', 'text', TRUE),
(8, 'Production Date', 'date', TRUE),

-- Fish category(9) attributes
(9, 'Origin', 'text', TRUE),
(9, 'Specification', 'number', TRUE),
(9, 'Refrigerated/Frozen', 'text', TRUE),
(9, 'Variety', 'text', FALSE),
(9, 'Catch Date', 'date', FALSE),

-- Shrimp category(10) attributes
(10, 'Origin', 'text', TRUE),
(10, 'Specification', 'number', TRUE),
(10, 'Refrigerated/Frozen', 'text', TRUE),
(10, 'Variety', 'text', FALSE),
(10, 'Processing Method', 'text', FALSE),

-- Other Aquatic Products category(11) attributes
(11, 'Origin', 'text', TRUE),
(11, 'Variety', 'text', TRUE),
(11, 'Net Weight', 'number', TRUE),
(11, 'Processing Method', 'text', FALSE);

-- 2) Products table (6 products)
INSERT INTO products (product_ID, sku, product_name, status, unit_price, unit, description, category_id) VALUES
(1,'VEG-SPINACH-250','Organic Spinach 250g','active',13.50,'g','Harvested daily, cold chain delivery',5),
(2,'VEG-TOMATO-500','Tomato 500g','active',17.90,'g','Sandy tomato, sweet and sour taste',5),
(3,'FRU-STRAWBERRY-500','Strawberry 500g','active',39.90,'g','Fresh strawberry, direct cold chain',6),
(4,'MEAT-PORK-500','Pork Belly 500g','active',38.90,'g','Selected pork belly',7),
(5,'FISH-SALMON-300','Salmon 300g','active',69.90,'g','Fresh sliced salmon',9),
(6,'SHRIMP-SHRIMP-500','Large Shrimp 500g','active',55.90,'g','Frozen preservation',10);

-- 3) ProductAttribute table
INSERT INTO ProductAttribute (product_id, attr_name, attr_value) VALUES
(1,'Origin','Local Changsha'),(1,'Net Weight','250'),(1,'Preservation Method','Refrigerated'),
(2,'Origin','Xiangtan, Hunan'),(2,'Net Weight','500'),(2,'Preservation Method','Room Temperature'),
(3,'Origin','Dandong'),(3,'Net Weight','500'),(3,'Sweetness Level','High'),
(4,'Origin','Hunan'),(4,'Cut/Type','Belly'),(4,'Refrigerated/Frozen','Refrigerated'),(4,'Net Weight','500'),
(5,'Origin','Norway'),(5,'Specification','300'),(5,'Refrigerated/Frozen','Refrigerated'),
(6,'Origin','Zhanjiang'),(6,'Specification','500'),(6,'Refrigerated/Frozen','Frozen');

-- 4) Branch table
INSERT INTO Branch (branch_ID, branch_name, address, phone, email, manager_ID, manager_phone, status) VALUES
(1,'FreshSelect · Zhongnan Store','Near XX Road 88, Zhongnan University, Yuelu District, Changsha','0731-88880001','zn@freshharvest.com',NULL,NULL,'active'),
(2,'FreshSelect · Lugu Store','XX Lugu Avenue, Yuelu District, Changsha','0731-88880002','lg@freshharvest.com',NULL,NULL,'active'),
(3,'FreshSelect · Meixi Lake Store','XX Meixi Lake Road, Yuelu District, Changsha','0731-88880003','mxh@freshharvest.com',NULL,NULL,'active');

-- 5) User table
INSERT INTO `User` (user_ID, user_name, password_hash, user_type, user_email, user_telephone, first_name, last_name, last_login, is_active) VALUES
-- CEO
(1,'ceo@localhost',MD5('Test1234'),'CEO','ceo@freshharvest.com','13900000000','Yuan','CEO',NULL,TRUE),

-- Zhongnan Store staff
(2,'m1@localhost',MD5('Test1234'),'staff','m1@freshharvest.com','13900000001','Lin','Manager',NULL,TRUE),
(3,'s1_b1@localhost',MD5('Test1234'),'staff','s1b1@freshharvest.com','13800000011','Chen','Sales',NULL,TRUE),
(4,'s2_b1@localhost',MD5('Test1234'),'staff','s2b1@freshharvest.com','13800000012','Sun','Sales',NULL,TRUE),
(5,'d_b1@localhost',MD5('Test1234'),'staff','db1@freshharvest.com','13700000013','Wu','Delivery',NULL,TRUE),

-- Lugu Store staff
(6,'m2@localhost',MD5('Test1234'),'staff','m2@freshharvest.com','13900000002','Zhou','Manager',NULL,TRUE),
(7,'s1_b2@localhost',MD5('Test1234'),'staff','s1b2@freshharvest.com','13800000021','Zheng','Sales',NULL,TRUE),
(8,'s2_b2@localhost',MD5('Test1234'),'staff','s2b2@freshharvest.com','13800000022','Xu','Sales',NULL,TRUE),
(9,'d_b2@localhost',MD5('Test1234'),'staff','db2@freshharvest.com','13700000023','Qian','Delivery',NULL,TRUE),

-- Meixi Lake Store staff
(10,'m3@localhost',MD5('Test1234'),'staff','m3@freshharvest.com','13900000003','He','Manager',NULL,TRUE),
(11,'s1_b3@localhost',MD5('Test1234'),'staff','s1b3@freshharvest.com','13800000031','Li','Sales',NULL,TRUE),
(12,'s2_b3@localhost',MD5('Test1234'),'staff','s2b3@freshharvest.com','13800000032','Zhang','Sales',NULL,TRUE),
(13,'d_b3@localhost',MD5('Test1234'),'staff','db3@freshharvest.com','13700000033','Zhou','Delivery',NULL,TRUE),

-- Suppliers
(14,'sup_a@localhost',MD5('Test1234'),'supplier','supa@vendor.com','13600000001','Supplier','A',NULL,TRUE),
(15,'sup_b@localhost',MD5('Test1234'),'supplier','supb@vendor.com','13600000002','Supplier','B',NULL,TRUE),
(16,'sup_c@localhost',MD5('Test1234'),'supplier','supc@vendor.com','13600000003','Supplier','C',NULL,TRUE),

-- Customers
(17,'cust_regular@localhost',MD5('Test1234'),'customer','regular@user.com','13500000001','Zhang','San',NULL,TRUE),
(18,'cust_vip@localhost',MD5('Test1234'),'customer','vip@user.com','13500000002','Li','Si',NULL,TRUE),
(19,'cust_vvip@localhost',MD5('Test1234'),'customer','vvip@user.com','13500000003','Wang','Wu',NULL,TRUE),

-- Additional suppliers
(20,'sup_d@localhost',MD5('Test1234'),'supplier','supd@vendor.com','13600000004','Supplier','D',NULL,TRUE),  -- Fruits & Vegetables
(21,'sup_e@localhost',MD5('Test1234'),'supplier','supe@vendor.com','13600000005','Supplier','E',NULL,TRUE),  -- Meat, Poultry & Eggs
(22,'sup_f@localhost',MD5('Test1234'),'supplier','supf@vendor.com','13600000006','Supplier','F',NULL,TRUE),  -- Aquatic Products
(23,'sup_g@localhost',MD5('Test1234'),'supplier','supg@vendor.com','13600000007','Supplier','G',NULL,TRUE),  -- Fruits & Vegetables
(24,'sup_h@localhost',MD5('Test1234'),'supplier','suph@vendor.com','13600000008','Supplier','H',NULL,TRUE),  -- Meat, Poultry & Eggs
(25,'sup_i@localhost',MD5('Test1234'),'supplier','supi@vendor.com','13600000009','Supplier','I',NULL,TRUE);  -- Aquatic Products

-- 6) Customer/Supplier/Staff tables
INSERT INTO Customer (customer_ID, user_name, phone, email, gender, address, loyalty_level, accu_cost) VALUES
(1,'cust_regular@localhost','13500000001','regular@user.com','Male','Chaoyang District, Beijing','Regular',0),
(2,'cust_vip@localhost','13500000002','vip@user.com','Male','Pudong District, Shanghai','VIP',139.80),
(3,'cust_vvip@localhost','13500000003','vvip@user.com','Male','Tianhe District, Guangzhou','VVIP',349.50);

INSERT INTO Supplier (supplier_ID, user_name, company_name, contact_person, phone, email, address, tax_number, supplier_category, status) VALUES
(1,'sup_a@localhost','Xiangcaiyuan Fruit and Vegetable Base','Zhou Supplier','13600000001','supa@vendor.com','Fruit and Vegetable Base, Wangcheng District, Changsha','TAX-A-202512','Fruit & Vegetable','active'),
(2,'sup_b@localhost','Meat, Poultry and Egg Integrated Supplier','Li Purchasing','13600000002','supb@vendor.com','No. 8 Cold Chain Warehouse, Yuhua District, Changsha','TAX-B-202512','Meat & Egg','active'),
(3,'sup_c@localhost','Seafood Cold Chain Direct Sourcing','Wang Seafood','13600000003','supc@vendor.com','Seafood and Aquatic Storage Center, Kaifu District, Changsha','TAX-C-202512','Aquatic Product','active'),
(4,'sup_d@localhost','Green Source Vegetable Cooperative','Zhang Vegetable','13600000004','supd@vendor.com','Vegetable Base, Furong District, Changsha','TAX-D-202512','Fruit & Vegetable','active'),
(5,'sup_e@localhost','Gold Ranch Meat Supplier','Wang Meat','13600000005','supe@vendor.com','Ranch Area, Tianxin District, Changsha','TAX-E-202512','Meat & Egg','active'),
(6,'sup_f@localhost','Deep Sea Fishing Port Aquatic Products','Li Seafood','13600000006','supf@vendor.com','Fishing Port Dock, Yuelu District, Changsha','TAX-F-202512','Aquatic Product','active'),
(7,'sup_g@localhost','Orchard Direct Supply','Zhao Fruit','13600000007','supg@vendor.com','Orchard Base, Yuhua District, Changsha','TAX-G-202512','Fruit & Vegetable','active'),
(8,'sup_h@localhost','Poultry and Egg Professional Cooperative','Qian Poultry Egg','13600000008','suph@vendor.com','Breeding Base, Kaifu District, Changsha','TAX-H-202512','Meat & Egg','active'),
(9,'sup_i@localhost','River Fresh Aquatic Products','Sun Aquatic','13600000009','supi@vendor.com','Aquatic Market, Wangcheng District, Changsha','TAX-I-202512','Aquatic Product','active');

INSERT INTO Staff (staff_ID, branch_ID, user_name, position, phone, salary, hire_date, status) VALUES
-- Zhongnan Store
(1,1,'m1@localhost','Manager','13900000001',18000.00,'2024-03-01','active'),
(2,1,'s1_b1@localhost','Sales','13800000011',9000.00,'2024-08-15','active'),
(3,1,'s2_b1@localhost','Sales','13800000012',8800.00,'2024-09-01','active'),
(4,1,'d_b1@localhost','Deliveryman','13700000013',7000.00,'2024-10-01','active'),

-- Lugu Store
(5,2,'m2@localhost','Manager','13900000002',17500.00,'2024-04-01','active'),
(6,2,'s1_b2@localhost','Sales','13800000021',9200.00,'2024-08-20','active'),
(7,2,'s2_b2@localhost','Sales','13800000022',8900.00,'2024-09-10','active'),
(8,2,'d_b2@localhost','Deliveryman','13700000023',7200.00,'2024-10-10','active'),

-- Meixi Lake Store
(9,3,'m3@localhost','Manager','13900000003',17800.00,'2024-05-01','active'),
(10,3,'s1_b3@localhost','Sales','13800000031',9300.00,'2024-08-25','active'),
(11,3,'s2_b3@localhost','Sales','13800000032',9000.00,'2024-09-20','active'),
(12,3,'d_b3@localhost','Deliveryman','13700000033',7300.00,'2024-10-20','active');

UPDATE Branch 
SET manager_ID = CASE branch_ID
    WHEN 1 THEN 1  -- Zhongnan Store manager staff_ID=1
    WHEN 2 THEN 5  -- Lugu Store manager staff_ID=5
    WHEN 3 THEN 9  -- Meixi Lake Store manager staff_ID=9
    END,
    manager_phone = CASE branch_ID
    WHEN 1 THEN '13900000001'
    WHEN 2 THEN '13900000002'
    WHEN 3 THEN '13900000003'
    END
WHERE branch_ID IN (1, 2, 3);

-- 13) Supplier-Product purchase price mapping (suppliers can only sell corresponding categories)
INSERT INTO SupplierProduct (supplier_ID, product_ID, price) VALUES
-- Supplier 1 (Fruits & Vegetables): can only sell products 1,2,3
(1,1,9.45),  -- Organic Spinach
(1,2,12.53), -- Tomato
(1,3,27.93), -- Strawberry

-- Supplier 2 (Meat, Poultry & Eggs): can only sell product 4
(2,4,28.90), -- Pork Belly

-- Supplier 3 (Aquatic Products): can only sell products 5,6
(3,5,56.20), -- Salmon
(3,6,44.00), -- Large Shrimp

-- Supplier 4 (Fruits & Vegetables): can only sell products 1,2,3
(4,1,9.60),  -- Organic Spinach
(4,2,12.40), -- Tomato
(4,3,28.10), -- Strawberry

-- Supplier 5 (Meat, Poultry & Eggs): can only sell product 4
(5,4,29.50), -- Pork Belly

-- Supplier 6 (Aquatic Products): can only sell products 5,6
(6,5,56.20), -- Salmon
(6,6,44.00), -- Large Shrimp

-- Supplier 7 (Fruits & Vegetables): can only sell products 1,2,3
(7,1,9.30),  -- Organic Spinach
(7,2,12.70), -- Tomato
(7,3,27.80), -- Strawberry

-- Supplier 8 (Meat, Poultry & Eggs): can only sell product 4
(8,4,28.90), -- Pork Belly

-- Supplier 9 (Aquatic Products): can only sell products 5,6
(9,5,55.50), -- Salmon
(9,6,45.00); -- Large Shrimp

-- 7) Purchase Order table (2 per store) --- Dates adjusted to before Jan 3, total amount calculated at purchase price
INSERT INTO PurchaseOrder (purchase_order_ID, supplier_ID, branch_ID, date, status, total_amount) VALUES
-- Zhongnan Store
(1,1,1,'2026-01-02','ordered', 94.50),  -- 10 spinach * 9.45 (Supplier 1 price) = 94.50
(2,5,1,'2026-01-02','ordered', 236.00), -- 8 pork belly * 29.50 (Supplier 5 price) = 236.00

-- Lugu Store
(3,4,2,'2026-01-02','ordered', 115.20), -- 12 spinach * 9.60 (Supplier 4 price) = 115.20
(4,6,2,'2026-01-02','ordered', 562.00), -- 10 salmon * 56.20 (Supplier 6 price) = 562.00

-- Meixi Lake Store
(5,7,3,'2026-01-02','ordered', 139.50), -- 15 spinach * 9.30 (Supplier 7 price) = 139.50
(6,1,3,'2026-01-02','ordered', 94.50);  -- 10 spinach * 9.45 (Supplier 1 price) = 94.50

-- 8) Customer Order table --- Dates adjusted to before Jan 3
INSERT INTO CustomerOrder (order_ID, customer_ID, order_date, branch_ID, total_amount, final_amount, status, shipping_address) VALUES
(1,1,'2026-01-02 10:15:00',1,  38.90, 0,'Pending','XX Zhongnan University, Yuelu District, Changsha'),
(2,2,'2026-01-02 11:20:00',1,  139.80, 139.80,'Completed','XX Road 66, Yuhua District, Changsha'),
(3,3,'2026-01-02 09:00:00',2,  349.50, 349.50,'Completed','XX Community, Kaifu District, Changsha');

-- 9) Inventory table (2 batches per store) --- Receiving and expiry dates adjusted, unit_cost using purchase price
INSERT INTO Inventory (batch_ID, product_ID, branch_ID, locked_inventory, quantity_received, quantity_on_hand, unit_cost, received_date, order_ID, date_expired) VALUES
-- Zhongnan Store
('B1-VEG-001',1,1,0,10,8,9.45,'2026-01-02',1,'2026-03-15'),   -- Spinach from Supplier 1, price 9.45
('B1-MEAT-001',4,1,1,8,8,29.50,'2026-01-02',2,'2026-03-10'),   -- Pork Belly from Supplier 5, price 29.50

-- Lugu Store
('B2-VEG-001',1,2,1,12,12,9.60,'2026-01-02',3,'2026-03-18'),   -- Spinach from Supplier 4, price 9.60
('B2-FISH-001',5,2,0,10,3,56.20,'2026-01-02',4,'2026-03-05'),   -- Salmon from Supplier 6, price 56.20

-- Meixi Lake Store
('B3-VEG-001',1,3,0,15,14,9.30,'2026-01-02',5,'2026-03-20'),   -- Spinach from Supplier 7, price 9.30
('B3-VEG-002',1,3,0,10,10,9.45,'2026-01-02',6,'2026-04-01');   -- Spinach from Supplier 1, price 9.45

-- 10) Stock Item Details (Direct insertion, fully corresponding to Inventory)
-- Zhongnan Store B1-VEG-001 batch: 10 Organic Spinach
INSERT INTO StockItem (item_ID, batch_ID, product_ID, branch_ID, purchase_order_ID, customer_order_ID, received_date, expiry_date, status) VALUES
('SI-B1VEG001-001','B1-VEG-001',1,1,1,NULL,'2026-01-02','2026-03-15','sold'),
('SI-B1VEG001-002','B1-VEG-001',1,1,1,NULL,'2026-01-02','2026-03-15','sold'),
('SI-B1VEG001-003','B1-VEG-001',1,1,1,NULL,'2026-01-02','2026-03-15','in_stock'),
('SI-B1VEG001-004','B1-VEG-001',1,1,1,NULL,'2026-01-02','2026-03-15','in_stock'),
('SI-B1VEG001-005','B1-VEG-001',1,1,1,NULL,'2026-01-02','2026-03-15','in_stock'),
('SI-B1VEG001-006','B1-VEG-001',1,1,1,NULL,'2026-01-02','2026-03-15','in_stock'),
('SI-B1VEG001-007','B1-VEG-001',1,1,1,NULL,'2026-01-02','2026-03-15','in_stock'),
('SI-B1VEG001-008','B1-VEG-001',1,1,1,NULL,'2026-01-02','2026-03-15','in_stock'),
('SI-B1VEG001-009','B1-VEG-001',1,1,1,NULL,'2026-01-02','2026-03-15','in_stock'),
('SI-B1VEG001-010','B1-VEG-001',1,1,1,NULL,'2026-01-02','2026-03-15','in_stock');

-- Zhongnan Store B1-MEAT-001 batch: 8 Pork Belly
INSERT INTO StockItem (item_ID, batch_ID, product_ID, branch_ID, purchase_order_ID, customer_order_ID, received_date, expiry_date, status) VALUES
('SI-B1MEAT001-001','B1-MEAT-001',4,1,2,1,'2026-01-02','2026-03-10','pending'),
('SI-B1MEAT001-002','B1-MEAT-001',4,1,2,NULL,'2026-01-02','2026-03-10','in_stock'),
('SI-B1MEAT001-003','B1-MEAT-001',4,1,2,NULL,'2026-01-02','2026-03-10','in_stock'),
('SI-B1MEAT001-004','B1-MEAT-001',4,1,2,NULL,'2026-01-02','2026-03-10','in_stock'),
('SI-B1MEAT001-005','B1-MEAT-001',4,1,2,NULL,'2026-01-02','2026-03-10','in_stock'),
('SI-B1MEAT001-006','B1-MEAT-001',4,1,2,NULL,'2026-01-02','2026-03-10','in_stock'),
('SI-B1MEAT001-007','B1-MEAT-001',4,1,2,NULL,'2026-01-02','2026-03-10','in_stock'),
('SI-B1MEAT001-008','B1-MEAT-001',4,1,2,NULL,'2026-01-02','2026-03-10','in_stock');

-- Lugu Store B2-VEG-001 batch: 12 Organic Spinach
INSERT INTO StockItem (item_ID, batch_ID, product_ID, branch_ID, purchase_order_ID, customer_order_ID, received_date, expiry_date, status) VALUES
('SI-B2VEG001-001','B2-VEG-001',1,2,3,3,'2026-01-02','2026-03-18','pending'),
('SI-B2VEG001-002','B2-VEG-001',1,2,3,NULL,'2026-01-02','2026-03-18','in_stock'),
('SI-B2VEG001-003','B2-VEG-001',1,2,3,NULL,'2026-01-02','2026-03-18','in_stock'),
('SI-B2VEG001-004','B2-VEG-001',1,2,3,NULL,'2026-01-02','2026-03-18','in_stock'),
('SI-B2VEG001-005','B2-VEG-001',1,2,3,NULL,'2026-01-02','2026-03-18','in_stock'),
('SI-B2VEG001-006','B2-VEG-001',1,2,3,NULL,'2026-01-02','2026-03-18','in_stock'),
('SI-B2VEG001-007','B2-VEG-001',1,2,3,NULL,'2026-01-02','2026-03-18','in_stock'),
('SI-B2VEG001-008','B2-VEG-001',1,2,3,NULL,'2026-01-02','2026-03-18','in_stock'),
('SI-B2VEG001-009','B2-VEG-001',1,2,3,NULL,'2026-01-02','2026-03-18','in_stock'),
('SI-B2VEG001-010','B2-VEG-001',1,2,3,NULL,'2026-01-02','2026-03-18','in_stock'),
('SI-B2VEG001-011','B2-VEG-001',1,2,3,NULL,'2026-01-02','2026-03-18','in_stock'),
('SI-B2VEG001-012','B2-VEG-001',1,2,3,NULL,'2026-01-02','2026-03-18','in_stock');

-- Lugu Store B2-FISH-001 batch: 10 Salmon
INSERT INTO StockItem (item_ID, batch_ID, product_ID, branch_ID, purchase_order_ID, customer_order_ID, received_date, expiry_date, status) VALUES
('SI-B2FISH001-001','B2-FISH-001',5,2,4,3,'2026-01-02','2026-03-05','sold'),
('SI-B2FISH001-002','B2-FISH-001',5,2,4,3,'2026-01-02','2026-03-05','sold'),
('SI-B2FISH001-003','B2-FISH-001',5,2,4,3,'2026-01-02','2026-03-05','sold'),
('SI-B2FISH001-004','B2-FISH-001',5,2,4,3,'2026-01-02','2026-03-05','sold'),
('SI-B2FISH001-005','B2-FISH-001',5,2,4,3,'2026-01-02','2026-03-05','sold'),
('SI-B2FISH001-006','B2-FISH-001',5,2,4,2,'2026-01-02','2026-03-05','sold'),
('SI-B2FISH001-007','B2-FISH-001',5,2,4,2,'2026-01-02','2026-03-05','sold'),
('SI-B2FISH001-008','B2-FISH-001',5,2,4,NULL,'2026-01-02','2026-03-05','in_stock'),
('SI-B2FISH001-009','B2-FISH-001',5,2,4,NULL,'2026-01-02','2026-03-05','in_stock'),
('SI-B2FISH001-010','B2-FISH-001',5,2,4,NULL,'2026-01-02','2026-03-05','in_stock');

-- Meixi Lake Store B3-VEG-001 batch: 15 Organic Spinach
INSERT INTO StockItem (item_ID, batch_ID, product_ID, branch_ID, purchase_order_ID, customer_order_ID, received_date, expiry_date, status) VALUES
('SI-B3VEG001-001','B3-VEG-001',1,3,5,NULL,'2026-01-02','2026-03-20','in_stock'),
('SI-B3VEG001-002','B3-VEG-001',1,3,5,NULL,'2026-01-02','2026-03-20','in_stock'),
('SI-B3VEG001-003','B3-VEG-001',1,3,5,NULL,'2026-01-02','2026-03-20','in_stock'),
('SI-B3VEG001-004','B3-VEG-001',1,3,5,NULL,'2026-01-02','2026-03-20','in_stock'),
('SI-B3VEG001-005','B3-VEG-001',1,3,5,NULL,'2026-01-02','2026-03-20','in_stock'),
('SI-B3VEG001-006','B3-VEG-001',1,3,5,NULL,'2026-01-02','2026-03-20','in_stock'),
('SI-B3VEG001-007','B3-VEG-001',1,3,5,NULL,'2026-01-02','2026-03-20','in_stock'),
('SI-B3VEG001-008','B3-VEG-001',1,3,5,NULL,'2026-01-02','2026-03-20','in_stock'),
('SI-B3VEG001-009','B3-VEG-001',1,3,5,NULL,'2026-01-02','2026-03-20','in_stock'),
('SI-B3VEG001-010','B3-VEG-001',1,3,5,NULL,'2026-01-02','2026-03-20','in_stock'),
('SI-B3VEG001-011','B3-VEG-001',1,3,5,NULL,'2026-01-02','2026-03-20','in_stock'),
('SI-B3VEG001-012','B3-VEG-001',1,3,5,NULL,'2026-01-02','2026-03-20','in_stock'),
('SI-B3VEG001-013','B3-VEG-001',1,3,5,NULL,'2026-01-02','2026-03-20','in_stock'),
('SI-B3VEG001-014','B3-VEG-001',1,3,5,NULL,'2026-01-02','2026-03-20','in_stock'),
('SI-B3VEG001-015','B3-VEG-001',1,3,5,NULL,'2026-01-02','2026-03-20','damaged');

-- Meixi Lake Store B3-VEG-002 batch: 10 Organic Spinach
INSERT INTO StockItem (item_ID, batch_ID, product_ID, branch_ID, purchase_order_ID, customer_order_ID, received_date, expiry_date, status) VALUES
('SI-B3VEG002-001','B3-VEG-002',1,3,6,NULL,'2026-01-02','2026-04-01','in_stock'),
('SI-B3VEG002-002','B3-VEG-002',1,3,6,NULL,'2026-01-02','2026-04-01','in_stock'),
('SI-B3VEG002-003','B3-VEG-002',1,3,6,NULL,'2026-01-02','2026-04-01','in_stock'),
('SI-B3VEG002-004','B3-VEG-002',1,3,6,NULL,'2026-01-02','2026-04-01','in_stock'),
('SI-B3VEG002-005','B3-VEG-002',1,3,6,NULL,'2026-01-02','2026-04-01','in_stock'),
('SI-B3VEG002-006','B3-VEG-002',1,3,6,NULL,'2026-01-02','2026-04-01','in_stock'),
('SI-B3VEG002-007','B3-VEG-002',1,3,6,NULL,'2026-01-02','2026-04-01','in_stock'),
('SI-B3VEG002-008','B3-VEG-002',1,3,6,NULL,'2026-01-02','2026-04-01','in_stock'),
('SI-B3VEG002-009','B3-VEG-002',1,3,6,NULL,'2026-01-02','2026-04-01','in_stock'),
('SI-B3VEG002-010','B3-VEG-002',1,3,6,NULL,'2026-01-02','2026-04-01','in_stock');

-- 11) Purchase Details table --- unit_cost using purchase price
INSERT INTO PurchaseItem (supply_id, purchase_order_ID, item_ID, product_ID, unit_cost, received_date) VALUES
-- Purchase Order 1 (Spinach, Supplier 1, price 9.45)
(1,1,'SI-B1VEG001-001',1,9.45,'2026-01-02'),
(2,1,'SI-B1VEG001-002',1,9.45,'2026-01-02'),
(3,1,'SI-B1VEG001-003',1,9.45,'2026-01-02'),
(4,1,'SI-B1VEG001-004',1,9.45,'2026-01-02'),
(5,1,'SI-B1VEG001-005',1,9.45,'2026-01-02'),
(6,1,'SI-B1VEG001-006',1,9.45,'2026-01-02'),
(7,1,'SI-B1VEG001-007',1,9.45,'2026-01-02'),
(8,1,'SI-B1VEG001-008',1,9.45,'2026-01-02'),
(9,1,'SI-B1VEG001-009',1,9.45,'2026-01-02'),
(10,1,'SI-B1VEG001-010',1,9.45,'2026-01-02'),

-- Purchase Order 2 (Pork Belly, Supplier 5, price 29.50)
(11,2,'SI-B1MEAT001-001',4,29.50,'2026-01-02'),
(12,2,'SI-B1MEAT001-002',4,29.50,'2026-01-02'),
(13,2,'SI-B1MEAT001-003',4,29.50,'2026-01-02'),
(14,2,'SI-B1MEAT001-004',4,29.50,'2026-01-02'),
(15,2,'SI-B1MEAT001-005',4,29.50,'2026-01-02'),
(16,2,'SI-B1MEAT001-006',4,29.50,'2026-01-02'),
(17,2,'SI-B1MEAT001-007',4,29.50,'2026-01-02'),
(18,2,'SI-B1MEAT001-008',4,29.50,'2026-01-02'),

-- Purchase Order 3 (Spinach, Supplier 4, price 9.60)
(19,3,'SI-B2VEG001-001',1,9.60,'2026-01-02'),
(20,3,'SI-B2VEG001-002',1,9.60,'2026-01-02'),
(21,3,'SI-B2VEG001-003',1,9.60,'2026-01-02'),
(22,3,'SI-B2VEG001-004',1,9.60,'2026-01-02'),
(23,3,'SI-B2VEG001-005',1,9.60,'2026-01-02'),
(24,3,'SI-B2VEG001-006',1,9.60,'2026-01-02'),
(25,3,'SI-B2VEG001-007',1,9.60,'2026-01-02'),
(26,3,'SI-B2VEG001-008',1,9.60,'2026-01-02'),
(27,3,'SI-B2VEG001-009',1,9.60,'2026-01-02'),
(28,3,'SI-B2VEG001-010',1,9.60,'2026-01-02'),
(29,3,'SI-B2VEG001-011',1,9.60,'2026-01-02'),
(30,3,'SI-B2VEG001-012',1,9.60,'2026-01-02'),

-- Purchase Order 4 (Salmon, Supplier 6, price 56.20)
(31,4,'SI-B2FISH001-001',5,56.20,'2026-01-02'),
(32,4,'SI-B2FISH001-002',5,56.20,'2026-01-02'),
(33,4,'SI-B2FISH001-003',5,56.20,'2026-01-02'),
(34,4,'SI-B2FISH001-004',5,56.20,'2026-01-02'),
(35,4,'SI-B2FISH001-005',5,56.20,'2026-01-02'),
(36,4,'SI-B2FISH001-006',5,56.20,'2026-01-02'),
(37,4,'SI-B2FISH001-007',5,56.20,'2026-01-02'),
(38,4,'SI-B2FISH001-008',5,56.20,'2026-01-02'),
(39,4,'SI-B2FISH001-009',5,56.20,'2026-01-02'),
(40,4,'SI-B2FISH001-010',5,56.20,'2026-01-02'),

-- Purchase Order 5 (Spinach, Supplier 7, price 9.30)
(41,5,'SI-B3VEG001-001',1,9.30,'2026-01-02'),
(42,5,'SI-B3VEG001-002',1,9.30,'2026-01-02'),
(43,5,'SI-B3VEG001-003',1,9.30,'2026-01-02'),
(44,5,'SI-B3VEG001-004',1,9.30,'2026-01-02'),
(45,5,'SI-B3VEG001-005',1,9.30,'2026-01-02'),
(46,5,'SI-B3VEG001-006',1,9.30,'2026-01-02'),
(47,5,'SI-B3VEG001-007',1,9.30,'2026-01-02'),
(48,5,'SI-B3VEG001-008',1,9.30,'2026-01-02'),
(49,5,'SI-B3VEG001-009',1,9.30,'2026-01-02'),
(50,5,'SI-B3VEG001-010',1,9.30,'2026-01-02'),
(51,5,'SI-B3VEG001-011',1,9.30,'2026-01-02'),
(52,5,'SI-B3VEG001-012',1,9.30,'2026-01-02'),
(53,5,'SI-B3VEG001-013',1,9.30,'2026-01-02'),
(54,5,'SI-B3VEG001-014',1,9.30,'2026-01-02'),
(55,5,'SI-B3VEG001-015',1,9.30,'2026-01-02'),

-- Purchase Order 6 (Spinach, Supplier 1, price 9.45)
(56,6,'SI-B3VEG002-001',1,9.45,'2026-01-02'),
(57,6,'SI-B3VEG002-002',1,9.45,'2026-01-02'),
(58,6,'SI-B3VEG002-003',1,9.45,'2026-01-02'),
(59,6,'SI-B3VEG002-004',1,9.45,'2026-01-02'),
(60,6,'SI-B3VEG002-005',1,9.45,'2026-01-02'),
(61,6,'SI-B3VEG002-006',1,9.45,'2026-01-02'),
(62,6,'SI-B3VEG002-007',1,9.45,'2026-01-02'),
(63,6,'SI-B3VEG002-008',1,9.45,'2026-01-02'),
(64,6,'SI-B3VEG002-009',1,9.45,'2026-01-02'),
(65,6,'SI-B3VEG002-010',1,9.45,'2026-01-02');

-- 12) Order Details table
INSERT INTO OrderItem (order_ID, item_ID, unit_price, product_ID, quantity, status) VALUES
-- Order 1: 1 Pork Belly (pending)
(1,'SI-B1MEAT001-001',38.90,4,1,'pending'),


-- Order 4: 1 Salmon (completed)
(3,'SI-B2FISH001-001',69.90,5,1,'completed'),
(3,'SI-B2FISH001-002',69.90,5,1,'completed'),
(3,'SI-B2FISH001-003',69.90,5,1,'completed'),
(3,'SI-B2FISH001-004',69.90,5,1,'completed'),
(3,'SI-B2FISH001-005',69.90,5,1,'completed'),
(2,'SI-B2FISH001-006',69.90,5,1,'completed'),
(2,'SI-B2FISH001-007',69.90,5,1,'completed');

-- 14) Stock Item Certificate table
INSERT INTO StockItemCertificate (certificate_ID, item_ID, transaction_type, date, note, transaction_ID) VALUES
-- Purchase records
(1,'SI-B1VEG001-001','purchase','2026-01-02 09:00:00','Purchase Organic Spinach',1),
(2,'SI-B1VEG001-002','purchase','2026-01-02 09:00:00','Purchase Organic Spinach',1),
(3,'SI-B1VEG001-003','purchase','2026-01-02 09:00:00','Purchase Organic Spinach',1),
(4,'SI-B1VEG001-004','purchase','2026-01-02 09:00:00','Purchase Organic Spinach',1),
(5,'SI-B1VEG001-005','purchase','2026-01-02 09:00:00','Purchase Organic Spinach',1),
(6,'SI-B1VEG001-006','purchase','2026-01-02 09:00:00','Purchase Organic Spinach',1),
(7,'SI-B1VEG001-007','purchase','2026-01-02 09:00:00','Purchase Organic Spinach',1),
(8,'SI-B1VEG001-008','purchase','2026-01-02 09:00:00','Purchase Organic Spinach',1),
(9,'SI-B1VEG001-009','purchase','2026-01-02 09:00:00','Purchase Organic Spinach',1),
(10,'SI-B1VEG001-010','purchase','2026-01-02 09:00:00','Purchase Organic Spinach',1),


(11,'SI-B1MEAT001-001','purchase','2026-01-02 10:00:00','Purchase Pork Belly',2),
(12,'SI-B1MEAT001-002','purchase','2026-01-02 10:00:00','Purchase Pork Belly',2),
(13,'SI-B1MEAT001-003','purchase','2026-01-02 10:00:00','Purchase Pork Belly',2),
(14,'SI-B1MEAT001-004','purchase','2026-01-02 10:00:00','Purchase Pork Belly',2),
(15,'SI-B1MEAT001-005','purchase','2026-01-02 10:00:00','Purchase Pork Belly',2),
(16,'SI-B1MEAT001-006','purchase','2026-01-02 10:00:00','Purchase Pork Belly',2),
(17,'SI-B1MEAT001-007','purchase','2026-01-02 10:00:00','Purchase Pork Belly',2),
(18,'SI-B1MEAT001-008','purchase','2026-01-02 10:00:00','Purchase Pork Belly',2),

(19,'SI-B2VEG001-001','purchase','2026-01-02 09:00:00','Purchase Organic Spinach',3),
(20,'SI-B2VEG001-002','purchase','2026-01-02 09:00:00','Purchase Organic Spinach',3),
(21,'SI-B2VEG001-003','purchase','2026-01-02 09:00:00','Purchase Organic Spinach',3),
(22,'SI-B2VEG001-004','purchase','2026-01-02 09:00:00','Purchase Organic Spinach',3),
(23,'SI-B2VEG001-005','purchase','2026-01-02 09:00:00','Purchase Organic Spinach',3),
(24,'SI-B2VEG001-006','purchase','2026-01-02 09:00:00','Purchase Organic Spinach',3),
(25,'SI-B2VEG001-007','purchase','2026-01-02 09:00:00','Purchase Organic Spinach',3),
(26,'SI-B2VEG001-008','purchase','2026-01-02 09:00:00','Purchase Organic Spinach',3),
(27,'SI-B2VEG001-009','purchase','2026-01-02 09:00:00','Purchase Organic Spinach',3),
(28,'SI-B2VEG001-010','purchase','2026-01-02 09:00:00','Purchase Organic Spinach',3),
(29,'SI-B2VEG001-011','purchase','2026-01-02 09:00:00','Purchase Organic Spinach',3),
(30,'SI-B2VEG001-012','purchase','2026-01-02 09:00:00','Purchase Organic Spinach',3),

(31,'SI-B2FISH001-001','purchase','2026-01-02 10:00:00','Purchase Salmon',4),
(32,'SI-B2FISH001-002','purchase','2026-01-02 10:00:00','Purchase Salmon',4),
(33,'SI-B2FISH001-003','purchase','2026-01-02 10:00:00','Purchase Salmon',4),
(34,'SI-B2FISH001-004','purchase','2026-01-02 10:00:00','Purchase Salmon',4),
(35,'SI-B2FISH001-005','purchase','2026-01-02 10:00:00','Purchase Salmon',4),
(36,'SI-B2FISH001-006','purchase','2026-01-02 10:00:00','Purchase Salmon',4),
(37,'SI-B2FISH001-007','purchase','2026-01-02 10:00:00','Purchase Salmon',4),
(38,'SI-B2FISH001-008','purchase','2026-01-02 10:00:00','Purchase Salmon',4),
(39,'SI-B2FISH001-009','purchase','2026-01-02 10:00:00','Purchase Salmon',4),
(40,'SI-B2FISH001-010','purchase','2026-01-02 10:00:00','Purchase Salmon',4),


(41,'SI-B3VEG001-001','purchase','2026-01-02 09:00:00','Purchase Organic Spinach',5),
(42,'SI-B3VEG001-002','purchase','2026-01-02 09:00:00','Purchase Organic Spinach',5),
(43,'SI-B3VEG001-003','purchase','2026-01-02 09:00:00','Purchase Organic Spinach',5),
(44,'SI-B3VEG001-004','purchase','2026-01-02 09:00:00','Purchase Organic Spinach',5),
(45,'SI-B3VEG001-005','purchase','2026-01-02 09:00:00','Purchase Organic Spinach',5),
(46,'SI-B3VEG001-006','purchase','2026-01-02 09:00:00','Purchase Organic Spinach',5),
(47,'SI-B3VEG001-007','purchase','2026-01-02 09:00:00','Purchase Organic Spinach',5),
(48,'SI-B3VEG001-008','purchase','2026-01-02 09:00:00','Purchase Organic Spinach',5),
(49,'SI-B3VEG001-009','purchase','2026-01-02 09:00:00','Purchase Organic Spinach',5),
(50,'SI-B3VEG001-010','purchase','2026-01-02 09:00:00','Purchase Organic Spinach',5),
(51,'SI-B3VEG001-011','purchase','2026-01-02 09:00:00','Purchase Organic Spinach',5),
(52,'SI-B3VEG001-012','purchase','2026-01-02 09:00:00','Purchase Organic Spinach',5),
(53,'SI-B3VEG001-013','purchase','2026-01-02 09:00:00','Purchase Organic Spinach',5),
(54,'SI-B3VEG001-014','purchase','2026-01-02 09:00:00','Purchase Organic Spinach',5),
(55,'SI-B3VEG001-015','purchase','2026-01-02 09:00:00','Purchase Organic Spinach',5),

(56,'SI-B3VEG002-001','purchase','2026-01-02 09:00:00','Purchase Organic Spinach',6),
(57,'SI-B3VEG002-002','purchase','2026-01-02 09:00:00','Purchase Organic Spinach',6),
(58,'SI-B3VEG002-003','purchase','2026-01-02 09:00:00','Purchase Organic Spinach',6),
(59,'SI-B3VEG002-004','purchase','2026-01-02 09:00:00','Purchase Organic Spinach',6),
(60,'SI-B3VEG002-005','purchase','2026-01-02 09:00:00','Purchase Organic Spinach',6),
(61,'SI-B3VEG002-006','purchase','2026-01-02 09:00:00','Purchase Organic Spinach',6),
(62,'SI-B3VEG002-007','purchase','2026-01-02 09:00:00','Purchase Organic Spinach',6),
(63,'SI-B3VEG002-008','purchase','2026-01-02 09:00:00','Purchase Organic Spinach',6),
(64,'SI-B3VEG002-009','purchase','2026-01-02 09:00:00','Purchase Organic Spinach',6),
(65,'SI-B3VEG002-010','purchase','2026-01-02 09:00:00','Purchase Organic Spinach',6),

-- Sales records
(66,'SI-B2FISH001-006','sale','2026-01-02 10:15:00','Sold to Order 2',2),
(67,'SI-B2FISH001-007','sale','2026-01-02 10:15:00','Sold to Order 2',2),
(68,'SI-B2FISH001-001','sale','2026-01-02 14:10:00','Sold to Order 3',3),
(69,'SI-B2FISH001-002','sale','2026-01-02 14:10:00','Sold to Order 3',3),
(70,'SI-B2FISH001-003','sale','2026-01-02 14:10:00','Sold to Order 3',3),
(71,'SI-B2FISH001-004','sale','2026-01-02 14:10:00','Sold to Order 3',3),
(72,'SI-B2FISH001-005','sale','2026-01-02 14:10:00','Sold to Order 3',3),
(73,'SI-B3VEG001-015','adjustment','2026-01-02 12:00:00','Product damage write-off',NULL);