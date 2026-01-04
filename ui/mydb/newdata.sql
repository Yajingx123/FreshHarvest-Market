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

-- 1) Category table
INSERT INTO Categories (category_id, category_name, parent_category_id, description) VALUES
(1,'Fresh Produce',NULL,'FreshHarvest main category'),
(2,'Fruits and Vegetables',1,'Vegetable and fruit parent category'),
(3,'Meat and Eggs',1,'Meat and egg parent category'),
(4,'Seafood',1,'Seafood parent category'),
(5,'Vegetables',2,'Seasonal vegetables, leafy vegetables, root vegetables'),
(6,'Fruits',2,'Seasonal fruits, imported fruits'),
(7,'Meat',3,'Pork, beef, lamb, poultry and other meats'),
(8,'Eggs',3,'Egg products such as chicken eggs'),
(9,'Fish',4,'Fish seafood'),
(10,'Shrimp',4,'Shrimp seafood'),
(11,'Other Seafood',4,'Kelp and other aquatic plants/seafood');

-- Add CategoryAttribute table data
INSERT INTO CategoryAttribute (category_id, attr_name, data_type, is_required) VALUES
-- Attributes of vegetable category (5)
(5, 'Origin', 'text', TRUE),
(5, 'Net Weight', 'number', TRUE),
(5, 'Preservation Method', 'text', TRUE),
(5, 'Cultivation Method', 'text', FALSE),
(5, 'Harvest Date', 'date', FALSE),

-- Attributes of fruit category (6)
(6, 'Origin', 'text', TRUE),
(6, 'Net Weight', 'number', TRUE),
(6, 'Sweetness Level', 'text', FALSE),
(6, 'Preservation Method', 'text', TRUE),
(6, 'Variety', 'text', FALSE),

-- Attributes of meat category (7)
(7, 'Origin', 'text', TRUE),
(7, 'Cut/Type', 'text', TRUE),
(7, 'Chilled/Frozen', 'text', TRUE),
(7, 'Net Weight', 'number', TRUE),
(7, 'Grade', 'text', FALSE),
(7, 'Feeding Method', 'text', FALSE),

-- Attributes of egg category (8)
(8, 'Origin', 'text', TRUE),
(8, 'Variety', 'text', FALSE),
(8, 'Specification', 'text', TRUE),
(8, 'Production Date', 'date', TRUE),

-- Attributes of fish category (9)
(9, 'Origin', 'text', TRUE),
(9, 'Specification', 'number', TRUE),
(9, 'Chilled/Frozen', 'text', TRUE),
(9, 'Variety', 'text', FALSE),
(9, 'Catch Date', 'date', FALSE),

-- Attributes of shrimp category (10)
(10, 'Origin', 'text', TRUE),
(10, 'Specification', 'number', TRUE),
(10, 'Chilled/Frozen', 'text', TRUE),
(10, 'Variety', 'text', FALSE),
(10, 'Processing Method', 'text', FALSE),

-- Attributes of other seafood category (11)
(11, 'Origin', 'text', TRUE),
(11, 'Variety', 'text', TRUE),
(11, 'Net Weight', 'number', TRUE),
(11, 'Processing Method', 'text', FALSE);

-- 2) Product table (6 products)
INSERT INTO products (product_ID, sku, product_name, status, unit_price, unit, description, category_id) VALUES
(1,'VEG-SPINACH-250','Organic Spinach 250g','active',13.50,'g','Harvested the same day, cold-chain delivery',5),
(2,'VEG-TOMATO-500','Tomato 500g','active',17.90,'g','Juicy tomato, sweet and sour taste',5),
(3,'FRU-STRAW-500','Strawberries 500g','active',39.90,'g','Fresh strawberries, cold-chain direct delivery',6),
(4,'MEAT-PORK-500','Pork Belly 500g','active',38.90,'g','Selected pork belly',7),
(5,'Fish-SALMON-300','Salmon 300g','active',69.90,'g','Chilled sliced salmon',9),
(6,'SHRIMP-SHRIMP-500','Large Shrimp 500g','active',55.90,'g','Frozen for freshness',10);

-- 3) Product attribute table
INSERT INTO ProductAttribute (product_id, attr_name, attr_value) VALUES
(1,'Origin','Changsha Local'),(1,'Net Weight','250'),(1,'Preservation Method','Chilled'),
(2,'Origin','Xiangtan, Hunan'),(2,'Net Weight','500'),(2,'Preservation Method','Room Temperature'),
(3,'Origin','Dandong'),(3,'Net Weight','500'),(3,'Sweetness Level','High'),
(4,'Origin','Hunan'),(4,'Cut/Type','Pork Belly'),(4,'Chilled/Frozen','Chilled'),(4,'Net Weight','500'),
(5,'Origin','Norway'),(5,'Specification','300'),(5,'Chilled/Frozen','Chilled'),
(6,'Origin','Zhanjiang'),(6,'Specification','500'),(6,'Chilled/Frozen','Frozen');

-- 4) Branch table
INSERT INTO Branch (branch_ID, branch_name, address, phone, email, manager_ID, manager_phone, status) VALUES
(1,'FreshSelect · Zhongnan Store','No.88 XX Road, near Central South University, Yuelu District, Changsha','0731-88880001','zn@freshharvest.com',NULL,NULL,'active'),
(2,'FreshSelect · Lugu Store','XX No., Lugu Avenue, Yuelu District, Changsha','0731-88880002','lg@freshharvest.com',NULL,NULL,'active'),
(3,'FreshSelect · Meixi Lake Store','XX No., Meixi Lake Road, Yuelu District, Changsha','0731-88880003','mxh@freshharvest.com',NULL,NULL,'active');

-- 5) User table
INSERT INTO `User` (user_ID, user_name, password_hash, user_type, user_email, user_telephone, first_name, last_name, last_login, is_active) VALUES
-- CEO
(1,'ceo@localhost',MD5('Test1234'),'CEO','ceo@freshharvest.com','13900000000','Yuan','CEO',NULL,TRUE),

-- Zhongnan store staff
(2,'m1@localhost',MD5('Test1234'),'staff','m1@freshharvest.com','13900000001','Lin','Manager',NULL,TRUE),
(3,'s1_b1@localhost',MD5('Test1234'),'staff','s1b1@freshharvest.com','13800000011','Chen','Sales',NULL,TRUE),
(4,'s2_b1@localhost',MD5('Test1234'),'staff','s2b1@freshharvest.com','13800000012','Sun','Sales',NULL,TRUE),
(5,'d_b1@localhost',MD5('Test1234'),'staff','db1@freshharvest.com','13700000013','Wu','Delivery',NULL,TRUE),

-- Lugu store staff
(6,'m2@localhost',MD5('Test1234'),'staff','m2@freshharvest.com','13900000002','Zhou','Manager',NULL,TRUE),
(7,'s1_b2@localhost',MD5('Test1234'),'staff','s1b2@freshharvest.com','13800000021','Zheng','Sales',NULL,TRUE),
(8,'s2_b2@localhost',MD5('Test1234'),'staff','s2b2@freshharvest.com','13800000022','Xu','Sales',NULL,TRUE),
(9,'d_b2@localhost',MD5('Test1234'),'staff','db2@freshharvest.com','13700000023','Qian','Delivery',NULL,TRUE),

-- Meixi Lake store staff
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

-- New suppliers
(20,'sup_d@localhost',MD5('Test1234'),'supplier','supd@vendor.com','13600000004','Supplier','D',NULL,TRUE),  -- Fruits and Vegetables
(21,'sup_e@localhost',MD5('Test1234'),'supplier','supe@vendor.com','13600000005','Supplier','E',NULL,TRUE),  -- Meat and Eggs
(22,'sup_f@localhost',MD5('Test1234'),'supplier','supf@vendor.com','13600000006','Supplier','F',NULL,TRUE),  -- Seafood
(23,'sup_g@localhost',MD5('Test1234'),'supplier','supg@vendor.com','13600000007','Supplier','G',NULL,TRUE),  -- Fruits and Vegetables
(24,'sup_h@localhost',MD5('Test1234'),'supplier','suph@vendor.com','13600000008','Supplier','H',NULL,TRUE),  -- Meat and Eggs
(25,'sup_i@localhost',MD5('Test1234'),'supplier','supi@vendor.com','13600000009','Supplier','I',NULL,TRUE);  -- Seafood

-- 6) Customer / Supplier / Staff tables
INSERT INTO Customer (customer_ID, user_name, phone, email, gender, address, loyalty_level, accu_cost) VALUES
(1,'cust_regular@localhost','13500000001','regular@user.com','Male','Chaoyang District, Beijing','Regular',0),
(2,'cust_vip@localhost','13500000002','vip@user.com','Male','Pudong District, Shanghai','VIP',180),
(3,'cust_vvip@localhost','13500000003','vvip@user.com','Male','Tianhe District, Guangzhou','VVIP',300);

INSERT INTO Supplier (supplier_ID, user_name, company_name, contact_person, phone, email, address, tax_number, supplier_category, status) VALUES
(1,'sup_a@localhost','Xiangcaiyuan Fruit and Vegetable Base','Zhou Supply','13600000001','supa@vendor.com','Fruit and Vegetable Integrated Base, Wangcheng District, Changsha','TAX-A-202512','果蔬','active'),
(2,'sup_b@localhost','Integrated Meat and Egg Supplier','Li Procurement','13600000002','supb@vendor.com','No.8 Cold Chain Warehouse, Yuhua District, Changsha','TAX-B-202512','肉禽蛋','active'),
(3,'sup_c@localhost','Seafood Cold Chain Direct Supply','Wang Seafood','13600000003','supc@vendor.com','Seafood Storage Center, Kaifu District, Changsha','TAX-C-202512','水产','active'),
(4,'sup_d@localhost','Green Source Vegetable Cooperative','Zhang Vegetable','13600000004','supd@vendor.com','Vegetable Base, Furong District, Changsha','TAX-D-202512','果蔬','active'),
(5,'sup_e@localhost','Golden Pasture Meat Supply','Wang Meat','13600000005','supe@vendor.com','Pasture Park, Tianxin District, Changsha','TAX-E-202512','肉禽蛋','active'),
(6,'sup_f@localhost','Deep Sea Harbor Seafood','Li Seafood','13600000006','supf@vendor.com','Fishing Harbor Wharf, Yuelu District, Changsha','TAX-F-202512','水产','active'),
(7,'sup_g@localhost','Orchard Direct Supply','Zhao Fruit','13600000007','supg@vendor.com','Orchard Base, Yuhua District, Changsha','TAX-G-202512','果蔬','active'),
(8,'sup_h@localhost','Poultry and Egg Specialized Cooperative','Qian Poultry','13600000008','suph@vendor.com','Breeding Base, Kaifu District, Changsha','TAX-H-202512','肉禽蛋','active'),
(9,'sup_i@localhost','River and Lake Fresh Seafood','Sun Seafood','13600000009','supi@vendor.com','Seafood Market, Wangcheng District, Changsha','TAX-I-202512','水产','active');

INSERT INTO Staff (staff_ID, branch_ID, user_name, position, phone, salary, hire_date, status) VALUES
-- Zhongnan store
(1,1,'m1@localhost','Manager','13900000001',18000.00,'2024-03-01','active'),
(2,1,'s1_b1@localhost','Sales','13800000011',9000.00,'2024-08-15','active'),
(3,1,'s2_b1@localhost','Sales','13800000012',8800.00,'2024-09-01','active'),
(4,1,'d_b1@localhost','Deliveryman','13700000013',7000.00,'2024-10-01','active'),

-- Lugu store
(5,2,'m2@localhost','Manager','13900000002',17500.00,'2024-04-01','active'),
(6,2,'s1_b2@localhost','Sales','13800000021',9200.00,'2024-08-20','active'),
(7,2,'s2_b2@localhost','Sales','13800000022',8900.00,'2024-09-10','active'),
(8,2,'d_b2@localhost','Deliveryman','13700000023',7200.00,'2024-10-10','active'),

-- Meixi Lake store
(9,3,'m3@localhost','Manager','13900000003',17800.00,'2024-05-01','active'),
(10,3,'s1_b3@localhost','Sales','13800000031',9300.00,'2024-08-25','active'),
(11,3,'s2_b3@localhost','Sales','13800000032',9000.00,'2024-09-20','active'),
(12,3,'d_b3@localhost','Deliveryman','13700000033',7300.00,'2024-10-20','active');

UPDATE Branch 
SET manager_ID = CASE branch_ID
    WHEN 1 THEN 1  -- Zhongnan store manager staff_ID=1
    WHEN 2 THEN 5  -- Lugu store manager staff_ID=5
    WHEN 3 THEN 9  -- Meixi Lake store manager staff_ID=9
    END,
    manager_phone = CASE branch_ID
    WHEN 1 THEN '13900000001'
    WHEN 2 THEN '13900000002'
    WHEN 3 THEN '13900000003'
    END
WHERE branch_ID IN (1, 2, 3);

-- 13) Supplier-product purchase price mapping (suppliers can only sell corresponding categories)
INSERT INTO SupplierProduct (supplier_ID, product_ID, price) VALUES
-- Supplier 1 (Fruits and Vegetables): can only sell products 1,2,3
(1,1,9.45),  -- Organic spinach
(1,2,12.53), -- Tomato
(1,3,27.93), -- Strawberries

-- Supplier 2 (Meat and Eggs): can only sell product 4
(2,4,28.90), -- Pork belly

-- Supplier 3 (Seafood): can only sell products 5,6
(3,5,56.20), -- Salmon
(3,6,44.00), -- Shrimp

-- Supplier 4 (Fruits and Vegetables): can only sell products 1,2,3
(4,1,9.60),  -- Organic spinach
(4,2,12.40), -- Tomato
(4,3,28.10), -- Strawberries

-- Supplier 5 (Meat and Eggs): can only sell product 4
(5,4,29.50), -- Pork belly

-- Supplier 6 (Seafood): can only sell products 5,6
(6,5,56.20), -- Salmon
(6,6,44.00), -- Shrimp

-- Supplier 7 (Fruits and Vegetables): can only sell products 1,2,3
(7,1,9.30),  -- Organic spinach
(7,2,12.70), -- Tomato
(7,3,27.80), -- Strawberries

-- Supplier 8 (Meat and Eggs): can only sell product 4
(8,4,28.90), -- Pork belly

-- Supplier 9 (Seafood): can only sell products 5,6
(9,5,55.50), -- Salmon
(9,6,45.00); -- Shrimp

-- 7) Purchase order table (2 per store) --- dates adjusted before January 3, total amount calculated based on purchase price
INSERT INTO PurchaseOrder (purchase_order_ID, supplier_ID, branch_ID, date, status, total_amount) VALUES
-- Zhongnan store
(1,1,1,'2026-01-02','ordered', 94.50),  -- 10 spinach * 9.45 (supplier 1 price) = 94.50
(2,5,1,'2026-01-02','ordered', 236.00), -- 8 pork belly * 29.50 (supplier 5 price) = 236.00

-- Lugu store
(3,4,2,'2026-01-02','ordered', 115.20), -- 12 spinach * 9.60 (supplier 4 price) = 115.20
(4,6,2,'2026-01-02','ordered', 337.20), -- 6 salmon * 56.20 (supplier 6 price) = 337.20

-- Meixi Lake store
(5,7,3,'2026-01-02','ordered', 139.50), -- 15 spinach * 9.30 (supplier 7 price) = 139.50
(6,1,3,'2026-01-02','ordered', 94.50);  -- 10 spinach * 9.45 (supplier 1 price) = 94.50

-- 8) Customer order table --- dates adjusted before January 3
INSERT INTO CustomerOrder (order_ID, customer_ID, order_date, branch_ID, total_amount, final_amount, status, shipping_address) VALUES
(1,1,'2026-01-02 10:15:00',1,  38.90, 0,'Pending','No. XX, Central South University, Yuelu District, Changsha'),
(2,2,'2026-01-02 11:20:00',1,  27.00, 25.65,'Completed','No.66 XX Road, Yuhua District, Changsha'),
(3,3,'2026-01-02 09:00:00',2,  13.50, 0,'Pending','XX Residential Area, Kaifu District, Changsha'),
(4,1,'2026-01-02 14:10:00',2,  69.90, 69.90,'Completed','No. XX, Lugu, Yuelu District, Changsha');

-- 9) Inventory table (2 batches per store) --- received and expiry dates adjusted, unit_cost uses purchase price
INSERT INTO Inventory (batch_ID, product_ID, branch_ID, locked_inventory, quantity_received, quantity_on_hand, unit_cost, received_date, order_ID, date_expired) VALUES
-- Zhongnan store
('B1-VEG-001',1,1,0,10,8,9.45,'2026-01-02',1,'2026-03-15'),   -- Spinach purchased from supplier 1, price 9.45
('B1-MEAT-001',4,1,1,8,8,29.50,'2026-01-02',2,'2026-03-10'),   -- Pork belly purchased from supplier 5, price 29.50

-- Lugu store
('B2-VEG-001',1,2,1,12,12,9.60,'2026-01-02',3,'2026-03-18'),   -- Spinach purchased from supplier 4, price 9.60
('B2-FISH-001',5,2,0,6,5,56.20,'2026-01-02',4,'2026-03-05'),   -- Salmon purchased from supplier 6, price 56.20

-- Meixi Lake store
('B3-VEG-001',1,3,0,15,14,9.30,'2026-01-02',5,'2026-03-20'),   -- Spinach purchased from supplier 7, price 9.30
('B3-VEG-002',1,3,0,10,10,9.45,'2026-01-02',6,'2026-04-01');   -- Spinach purchased from supplier 1, price 9.45

-- 10) Inventory item details (direct insert, fully corresponding to Inventory)
-- Zhongnan store B1-VEG-001 batch: 10 organic spinach
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

-- Zhongnan store B1-MEAT-001 batch: 8 pork belly
INSERT INTO StockItem (item_ID, batch_ID, product_ID, branch_ID, purchase_order_ID, customer_order_ID, received_date, expiry_date, status) VALUES
('SI-B1MEAT001-001','B1-MEAT-001',4,1,2,1,'2026-01-02','2026-03-10','pending'),
('SI-B1MEAT001-002','B1-MEAT-001',4,1,2,NULL,'2026-01-02','2026-03-10','in_stock'),
('SI-B1MEAT001-003','B1-MEAT-001',4,1,2,NULL,'2026-01-02','2026-03-10','in_stock'),
('SI-B1MEAT001-004','B1-MEAT-001',4,1,2,NULL,'2026-01-02','2026-03-10','in_stock'),
('SI-B1MEAT001-005','B1-MEAT-001',4,1,2,NULL,'2026-01-02','2026-03-10','in_stock'),
('SI-B1MEAT001-006','B1-MEAT-001',4,1,2,NULL,'2026-01-02','2026-03-10','in_stock'),
('SI-B1MEAT001-007','B1-MEAT-001',4,1,2,NULL,'2026-01-02','2026-03-10','in_stock'),
('SI-B1MEAT001-008','B1-MEAT-001',4,1,2,NULL,'2026-01-02','2026-03-10','in_stock');

-- Lugu store B2-VEG-001 batch: 12 organic spinach
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

-- Lugu store B2-FISH-001 batch: 6 salmon
INSERT INTO StockItem (item_ID, batch_ID, product_ID, branch_ID, purchase_order_ID, customer_order_ID, received_date, expiry_date, status) VALUES
('SI-B2FISH001-001','B2-FISH-001',5,2,4,4,'2026-01-02','2026-03-05','sold'),
('SI-B2FISH001-002','B2-FISH-001',5,2,4,NULL,'2026-01-02','2026-03-05','in_stock'),
('SI-B2FISH001-003','B2-FISH-001',5,2,4,NULL,'2026-01-02','2026-03-05','in_stock'),
('SI-B2FISH001-004','B2-FISH-001',5,2,4,NULL,'2026-01-02','2026-03-05','in_stock'),
('SI-B2FISH001-005','B2-FISH-001',5,2,4,NULL,'2026-01-02','2026-03-05','in_stock'),
('SI-B2FISH001-006','B2-FISH-001',5,2,4,NULL,'2026-01-02','2026-03-05','in_stock');

-- Meixi Lake store B3-VEG-001 batch: 15 organic spinach
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

-- Meixi Lake store B3-VEG-002 batch: 10 organic spinach
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

-- 11) Purchase item table --- unit_cost uses purchase price
INSERT INTO PurchaseItem (supply_id, purchase_order_ID, item_ID, product_ID, unit_cost, received_date) VALUES
-- Purchase order 1 (spinach, supplier 1, price 9.45)
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

-- Purchase order 2 (pork belly, supplier 5, price 29.50)
(11,2,'SI-B1MEAT001-001',4,29.50,'2026-01-02'),
(12,2,'SI-B1MEAT001-002',4,29.50,'2026-01-02'),
(13,2,'SI-B1MEAT001-003',4,29.50,'2026-01-02'),
(14,2,'SI-B1MEAT001-004',4,29.50,'2026-01-02'),
(15,2,'SI-B1MEAT001-005',4,29.50,'2026-01-02'),
(16,2,'SI-B1MEAT001-006',4,29.50,'2026-01-02'),
(17,2,'SI-B1MEAT001-007',4,29.50,'2026-01-02'),
(18,2,'SI-B1MEAT001-008',4,29.50,'2026-01-02'),

-- Purchase order 3 (spinach, supplier 4, price 9.60)
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

-- Purchase order 4 (salmon, supplier 6, price 56.20)
(31,4,'SI-B2FISH001-001',5,56.20,'2026-01-02'),
(32,4,'SI-B2FISH001-002',5,56.20,'2026-01-02'),
(33,4,'SI-B2FISH001-003',5,56.20,'2026-01-02'),
(34,4,'SI-B2FISH001-004',5,56.20,'2026-01-02'),
(35,4,'SI-B2FISH001-005',5,56.20,'2026-01-02'),
(36,4,'SI-B2FISH001-006',5,56.20,'2026-01-02'),

-- Purchase order 5 (spinach, supplier 7, price 9.30)
(37,5,'SI-B3VEG001-001',1,9.30,'2026-01-02'),
(38,5,'SI-B3VEG001-002',1,9.30,'2026-01-02'),
(39,5,'SI-B3VEG001-003',1,9.30,'2026-01-02'),
(40,5,'SI-B3VEG001-004',1,9.30,'2026-01-02'),
(41,5,'SI-B3VEG001-005',1,9.30,'2026-01-02'),
(42,5,'SI-B3VEG001-006',1,9.30,'2026-01-02'),
(43,5,'SI-B3VEG001-007',1,9.30,'2026-01-02'),
(44,5,'SI-B3VEG001-008',1,9.30,'2026-01-02'),
(45,5,'SI-B3VEG001-009',1,9.30,'2026-01-02'),
(46,5,'SI-B3VEG001-010',1,9.30,'2026-01-02'),
(47,5,'SI-B3VEG001-011',1,9.30,'2026-01-02'),
(48,5,'SI-B3VEG001-012',1,9.30,'2026-01-02'),
(49,5,'SI-B3VEG001-013',1,9.30,'2026-01-02'),
(50,5,'SI-B3VEG001-014',1,9.30,'2026-01-02'),
(51,5,'SI-B3VEG001-015',1,9.30,'2026-01-02'),

-- Purchase order 6 (spinach, supplier 1, price 9.45)
(52,6,'SI-B3VEG002-001',1,9.45,'2026-01-02'),
(53,6,'SI-B3VEG002-002',1,9.45,'2026-01-02'),
(54,6,'SI-B3VEG002-003',1,9.45,'2026-01-02'),
(55,6,'SI-B3VEG002-004',1,9.45,'2026-01-02'),
(56,6,'SI-B3VEG002-005',1,9.45,'2026-01-02'),
(57,6,'SI-B3VEG002-006',1,9.45,'2026-01-02'),
(58,6,'SI-B3VEG002-007',1,9.45,'2026-01-02'),
(59,6,'SI-B3VEG002-008',1,9.45,'2026-01-02'),
(60,6,'SI-B3VEG002-009',1,9.45,'2026-01-02'),
(61,6,'SI-B3VEG002-010',1,9.45,'2026-01-02');

-- 12) Order item table
INSERT INTO OrderItem (order_ID, item_ID, unit_price, product_ID, quantity, status) VALUES
-- Order 1: 1 pork belly (pending)
(1,'SI-B1MEAT001-001',38.90,4,1,'pending'),

-- Order 2: 2 organic spinach (completed)
(2,'SI-B1VEG001-001',13.50,1,1,'completed'),
(2,'SI-B1VEG001-002',13.50,1,1,'completed'),

-- Order 3: 1 organic spinach (pending)
(3,'SI-B2VEG001-001',13.50,1,1,'pending'),

-- Order 4: 1 salmon (completed)
(4,'SI-B2FISH001-001',69.90,5,1,'completed');

-- 14) Stock item certificate table
INSERT INTO StockItemCertificate (certificate_ID, item_ID, transaction_type, date, note, transaction_ID) VALUES
-- Purchase records
(1,'SI-B1VEG001-001','purchase','2026-01-02 09:00:00','Purchase organic spinach',1),
(2,'SI-B1MEAT001-001','purchase','2026-01-02 10:00:00','Purchase pork belly',2),
(3,'SI-B2VEG001-001','purchase','2026-01-02 09:00:00','Purchase organic spinach',3),
(4,'SI-B2FISH001-001','purchase','2026-01-02 10:00:00','Purchase salmon',4),
(5,'SI-B3VEG001-001','purchase','2026-01-02 09:00:00','Purchase organic spinach',5),
(6,'SI-B3VEG002-001','purchase','2026-01-02 09:00:00','Purchase organic spinach',6),

-- Sales records
(7,'SI-B1VEG001-001','sale','2026-01-02 10:15:00','Sold to order 2',2),
(8,'SI-B1VEG001-002','sale','2026-01-02 10:15:00','Sold to order 2',2),
(9,'SI-B2FISH001-001','sale','2026-01-02 14:10:00','Sold to order 4',4),
(10,'SI-B3VEG001-015','adjustment','2026-01-02 12:00:00','Item damaged and written off',NULL);
