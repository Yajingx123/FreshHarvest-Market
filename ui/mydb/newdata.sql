USE mydb;

-- 清空旧数据（按依赖顺序）
SET FOREIGN_KEY_CHECKS = 0;
TRUNCATE TABLE StockItemCertificate;
TRUNCATE TABLE OrderItem;
TRUNCATE TABLE PurchaseItem;
TRUNCATE TABLE StockItem;
TRUNCATE TABLE Inventory;
TRUNCATE TABLE CustomerOrder;
TRUNCATE TABLE PurchaseOrder;
TRUNCATE TABLE Staff;
TRUNCATE TABLE Supplier;
TRUNCATE TABLE Customer;
TRUNCATE TABLE `User`;
TRUNCATE TABLE ProductAttribute;
TRUNCATE TABLE CategoryAttribute;
TRUNCATE TABLE products;
TRUNCATE TABLE Categories;
TRUNCATE TABLE Branch;
SET FOREIGN_KEY_CHECKS = 1;

-- 1) 分类表（保持不变）
INSERT INTO Categories (category_id, category_name, parent_category_id, description) VALUES
(1,'生鲜',NULL,'FreshHarvest 主分类'),
(2,'果蔬',1,'蔬菜水果父类'),
(3,'肉禽蛋',1,'肉类与蛋类父类'),
(4,'水产',1,'水产品父类'),
(5,'蔬菜',2,'当季蔬菜、叶菜、根茎类'),
(6,'水果',2,'当季水果、进口水果'),
(7,'肉',3,'猪牛羊、禽类等肉类'),
(8,'蛋',3,'鸡蛋等蛋类'),
(9,'鱼',4,'鱼类水产'),
(10,'虾',4,'虾类水产'),
(11,'其他水产品',4,'海带等其他水生动植物/水产');

-- 分类属性表（保持不变）
INSERT INTO CategoryAttribute (category_id, attr_name, data_type, is_required) VALUES
(5,'产地','text',TRUE),
(5,'净含量','number',TRUE),
(5,'保鲜方式','text',TRUE),
(6,'产地','text',TRUE),
(6,'甜度等级','text',FALSE),
(6,'净含量','number',TRUE),
(7,'产地','text',TRUE),
(7,'部位/品类','text',TRUE),
(7,'冷藏/冷冻','text',TRUE),
(7,'净含量','number',FALSE),
(8,'产地','text',TRUE),
(8,'规格','text',TRUE),
(8,'冷藏/冷冻','text',TRUE),
(9,'产地','text',TRUE),
(9,'规格','text',TRUE),
(9,'冷藏/冷冻','text',TRUE),
(10,'产地','text',TRUE),
(10,'规格','text',TRUE),
(10,'冷藏/冷冻','text',TRUE),
(11,'产地','text',TRUE),
(11,'规格','text',TRUE),
(11,'冷藏/冷冻','text',TRUE);

-- 2) 产品表（保持不变）
INSERT INTO products (product_ID, sku, product_name, status, unit_price, unit, description, category_id) VALUES
-- 蔬菜(5)
(1,'VEG-SPINACH-250','有机菠菜 250g','active',3.50,'g','当日采摘，冷链配送',5),
(2,'VEG-TOMATO-500','番茄 500g','active',7.90,'g','沙瓤番茄，口感酸甜',5),
(3,'VEG-POTATO-1K','土豆 1kg','active',9.90,'kg','黄心土豆，耐储存',5),
-- 水果(6)
(4,'FRU-STRAW-500','草莓 500g','active',29.90,'g','新鲜草莓，冷链直达',6),
(5,'FRU-APPLE-1K','红富士苹果 1kg','active',18.80,'kg','脆甜多汁',6),
(6,'FRU-BANANA-1K','香蕉 1kg','active',12.80,'kg','香甜软糯',6),
-- 肉(7)
(7,'MEAT-PORK-500','五花肉 500g','active',28.90,'g','精选猪五花',7),
(8,'MEAT-BEEF-500','牛腱子 500g','active',49.90,'g','适合卤煮炖',7),
(9,'MEAT-CHICK-1','三黄鸡 1只','active',39.90,'只','散养鸡，冷链配送',7),
-- 蛋(8)
(10,'EGG-30','鲜鸡蛋 30枚','active',26.90,'枚','家庭装鸡蛋',8),
-- 鱼/虾/其他水产品
(11,'FISH-SALMON-300','三文鱼 300g','active',59.90,'g','冰鲜切片',9),
(12,'SHRIMP-500','大虾 500g','active',45.90,'g','冷冻保鲜',10),
(13,'SEA-CRAB-2','梭子蟹 2只','active',69.00,'只','季节限定',11);

-- 3) 产品属性表（保持不变）
INSERT INTO ProductAttribute (product_id, attr_name, attr_value) VALUES
-- 蔬菜
(1,'产地','长沙本地'),(1,'净含量','250'),(1,'保鲜方式','冷藏'),
(2,'产地','湖南湘潭'),(2,'净含量','500'),(2,'保鲜方式','常温'),
(3,'产地','内蒙古'),(3,'净含量','1'),(3,'保鲜方式','常温'),
-- 水果
(4,'产地','丹东'),(4,'净含量','500'),(4,'甜度等级','高'),
(5,'产地','陕西'),(5,'净含量','1'),(5,'甜度等级','中高'),
(6,'产地','云南'),(6,'净含量','1'),(6,'甜度等级','中'),
-- 肉
(7,'产地','湖南'),(7,'部位/品类','五花'),(7,'冷藏/冷冻','冷藏'),(7,'净含量','500'),
(8,'产地','澳洲'),(8,'部位/品类','牛腱'),(8,'冷藏/冷冻','冷藏'),(8,'净含量','500'),
(9,'产地','湖南'),(9,'部位/品类','整鸡'),(9,'冷藏/冷冻','冷藏'),(9,'规格','1'),
-- 蛋
(10,'产地','湖南'),(10,'规格','30'),(10,'冷藏/冷冻','常温'),
-- 鱼/虾/其他
(11,'产地','挪威'),(11,'规格','300'),(11,'冷藏/冷冻','冷藏'),
(12,'产地','湛江'),(12,'规格','500'),(12,'冷藏/冷冻','冷冻'),
(13,'产地','舟山'),(13,'规格','2'),(13,'冷藏/冷冻','冷藏');

-- 4) 门店表（保持不变）
INSERT INTO Branch (branch_ID, branch_name, address, phone, email, manager_ID, manager_phone, status) VALUES
(1,'鲜选生鲜·中南店','长沙市岳麓区中南大学附近XX路88号','0731-88880001','zn@freshharvest.com',NULL,NULL,'active'),
(2,'鲜选生鲜·麓谷店','长沙市岳麓区麓谷大道XX号','0731-88880002','lg@freshharvest.com',NULL,NULL,'active'),
(3,'鲜选生鲜·梅溪湖店','长沙市岳麓区梅溪湖路XX号','0731-88880003','mxh@freshharvest.com',NULL,NULL,'active');

-- 5) 用户表（保持不变）
INSERT INTO `User` (user_ID,user_name,password_hash,user_type,user_email,user_telephone,first_name,last_name,last_login,is_active) VALUES
(1,'ceo@localhost',MD5('Test1234'),'CEO','ceo@freshharvest.com','13900000000','Yuan','CEO',NULL,TRUE),
-- 员工用户
(2,'m1@localhost',MD5('Test1234'),'staff','m1@freshharvest.com','13900000001','Lin','Manager',NULL,TRUE),
(3,'m2@localhost',MD5('Test1234'),'staff','m2@freshharvest.com','13900000002','Zhou','Manager',NULL,TRUE),
(4,'s1_b1@localhost',MD5('Test1234'),'staff','s1b1@freshharvest.com','13800000011','Chen','Sales',NULL,TRUE),
(5,'s2_b1@localhost',MD5('Test1234'),'staff','s2b1@freshharvest.com','13800000012','Sun','Sales',NULL,TRUE),
(6,'d_b1@localhost',MD5('Test1234'),'staff','db1@freshharvest.com','13700000013','Wu','Delivery',NULL,TRUE),
(7,'m3@localhost',MD5('Test1234'),'staff','m3@freshharvest.com','13900000003','He','Manager',NULL,TRUE),
(8,'m4@localhost',MD5('Test1234'),'staff','m4@freshharvest.com','13900000004','Wang','Manager',NULL,TRUE),
(9,'s1_b2@localhost',MD5('Test1234'),'staff','s1b2@freshharvest.com','13800000021','Zheng','Sales',NULL,TRUE),
(10,'s2_b2@localhost',MD5('Test1234'),'staff','s2b2@freshharvest.com','13800000022','Xu','Sales',NULL,TRUE),
(11,'d_b2@localhost',MD5('Test1234'),'staff','db2@freshharvest.com','13700000023','Qian','Delivery',NULL,TRUE),
(12,'m5@localhost',MD5('Test1234'),'staff','m5@freshharvest.com','13900000005','Zhao','Manager',NULL,TRUE),
(13,'m6@localhost',MD5('Test1234'),'staff','m6@freshharvest.com','13900000006','Liu','Manager',NULL,TRUE),
(14,'s1_b3@localhost',MD5('Test1234'),'staff','s1b3@localhost','13800000031','Li','Sales',NULL,TRUE),
(15,'s2_b3@localhost',MD5('Test1234'),'staff','s2b3@localhost','13800000032','Zhang','Sales',NULL,TRUE),
(16,'d_b3@localhost',MD5('Test1234'),'staff','db3@localhost','13700000033','Zhou','Delivery',NULL,TRUE),
-- 供应商
(17,'sup_a@localhost',MD5('Test1234'),'supplier','supa@vendor.com','13600000001','Supplier','A',NULL,TRUE),
(18,'sup_b@localhost',MD5('Test1234'),'supplier','supb@vendor.com','13600000002','Supplier','B',NULL,TRUE),
(19,'sup_c@localhost',MD5('Test1234'),'supplier','supc@vendor.com','13600000003','Supplier','C',NULL,TRUE),
(20,'sup_d@localhost',MD5('Test1234'),'supplier','supd@vendor.com','13600000004','Supplier','D',NULL,TRUE),
(21,'sup_e@localhost',MD5('Test1234'),'supplier','supe@vendor.com','13600000005','Supplier','E',NULL,TRUE),
(22,'sup_f@localhost',MD5('Test1234'),'supplier','supf@vendor.com','13600000006','Supplier','F',NULL,TRUE),
-- 客户
(23,'cust_regular@localhost',MD5('Test1234'),'customer','regular@user.com','13500000001','Zhang','San',NULL,TRUE),
(24,'cust_vip@localhost',MD5('Test1234'),'customer','vip@user.com','13500000002','Li','Si',NULL,TRUE),
(25,'cust_vvip@localhost',MD5('Test1234'),'customer','vvip@user.com','13500000003','Wang','Wu',NULL,TRUE);

-- 6) 客户/供应商/员工表（保持不变）
INSERT INTO Customer (customer_ID, user_name, phone, email, gender, address, loyalty_level,accu_cost) VALUES
(1,'cust_regular@localhost','13500000001','regular@user.com','Men','Beijing','Regular',99.00),
(2,'cust_vip@localhost','13500000002','vip@user.com','Men','Beijing','VIP',59.90),
(3,'cust_vvip@localhost','13500000003','vvip@user.com','Men','Beijing','VVIP',1.00);

INSERT INTO Supplier (supplier_ID, user_name, company_name, contact_person, phone, email, address, tax_number, supplier_category, status) VALUES
(1,'sup_a@localhost','湘菜源蔬果基地','周供应','13600000001','supa@vendor.com','长沙市望城区果蔬综合基地','TAX-A-202512','果蔬','active'),
(2,'sup_b@localhost','肉禽蛋综合供应商','李集采','13600000002','supb@vendor.com','长沙市雨花区8号冷链仓','TAX-B-202512','肉禽蛋','active'),
(3,'sup_c@localhost','海鲜冷链直采','王海鲜','13600000003','supc@vendor.com','长沙市开福区海鲜水产仓储中心','TAX-C-202512','水产','active'),
(4,'sup_d@localhost','橘子洲果蔬超级市场','赵果蔬','13600000004','supd@vendor.com','长沙市岳麓区第3超级市场','TAX-D-202512','果蔬','active'),
(5,'sup_e@localhost','湘江牧场','钱牧场','13600000005','supe@vendor.com','长沙市天心区综合牧场','TAX-E-202512','肉禽蛋','active'),
(6,'sup_f@localhost','极速鲜海鲜水产超市','孙水产','13600000006','supf@vendor.com','长沙市芙蓉区7号路市场','TAX-F-202512','水产','active');

INSERT INTO Staff (staff_ID, branch_ID, user_name, position, phone, salary, hire_date, status) VALUES
-- 中南店(1)
(1,1,'m1@localhost','Manager','13900000001',18000.00,'2024-03-01','active'),
(2,1,'m2@localhost','Manager','13900000002',17000.00,'2024-06-01','active'),
(3,1,'s1_b1@localhost','Sales','13800000011',9000.00,'2024-08-15','active'),
(4,1,'s2_b1@localhost','Sales','13800000012',8800.00,'2024-09-01','active'),
(5,1,'d_b1@localhost','Deliveryman','13700000013',7000.00,'2024-10-01','active'),
-- 麓谷店(2)
(6,2,'m3@localhost','Manager','13900000003',17500.00,'2024-04-01','active'),
(7,2,'m4@localhost','Manager','13900000004',16500.00,'2024-07-01','active'),
(8,2,'s1_b2@localhost','Sales','13800000021',9200.00,'2024-08-20','active'),
(9,2,'s2_b2@localhost','Sales','13800000022',8900.00,'2024-09-10','active'),
(10,2,'d_b2@localhost','Deliveryman','13700000023',7200.00,'2024-10-10','active'),
-- 梅溪湖店(3)
(11,3,'m5@localhost','Manager','13900000005',17800.00,'2024-05-01','active'),
(12,3,'m6@localhost','Manager','13900000006',16800.00,'2024-07-15','active'),
(13,3,'s1_b3@localhost','Sales','13800000031',9300.00,'2024-08-25','active'),
(14,3,'s2_b3@localhost','Sales','13800000032',9000.00,'2024-09-20','active'),
(15,3,'d_b3@localhost','Deliveryman','13700000033',7300.00,'2024-10-20','active');

-- 回填门店经理
UPDATE Branch SET manager_ID = 1, manager_phone = '13900000001' WHERE branch_ID = 1;
UPDATE Branch SET manager_ID = 6, manager_phone = '13900000003' WHERE branch_ID = 2;
UPDATE Branch SET manager_ID = 11, manager_phone = '13900000005' WHERE branch_ID = 3;

-- 7) 采购订单表（保持不变）
INSERT INTO PurchaseOrder (purchase_order_ID, supplier_ID, branch_ID, date, status, total_amount) VALUES
(1,1,1,'2025-12-10','received', 1200.00),
(2,2,1,'2025-12-11','received', 1800.00),
(3,3,1,'2025-12-12','ordered',  900.00),
(4,1,2,'2025-12-10','received', 1500.00),
(5,2,2,'2025-12-12','received', 1600.00),
(6,3,2,'2025-12-13','pending',  800.00),
(7,1,3,'2025-12-11','received', 1100.00),
(8,2,3,'2025-12-12','ordered',  1400.00),
(9,3,3,'2025-12-13','pending',  700.00);

-- 8) 库存表（核心修正：确保quantity_on_hand等于StockItem中有效库存数量）
INSERT INTO Inventory
(batch_ID, product_ID, branch_ID, quantity_received, quantity_on_hand, unit_cost, received_date, order_ID, date_produced, date_expired)
VALUES
-- 中南店有机菠菜：总进货50，当前库存=在库(2)+锁定(1)=3（修正前8）
('B1-VEG-001',1,1,50, 3, 3.50,'2025-12-10',1,'2025-12-09','2025-12-30'),
-- 中南店草莓：总进货30，当前库存12（假设StockItem匹配）
('B1-FRU-001',4,1,30, 12,18.00,'2025-12-10',1,'2025-12-09','2025-12-18'),
-- 中南店五花肉：总进货40，当前库存25
('B1-MEAT-001',7,1,40, 25,16.50,'2025-12-11',2,'2025-12-10','2025-12-25'),
-- 中南店大虾：总进货20，当前库存3
('B1-SEA-001',12,1,20,  3,26.00,'2025-12-11',2,'2025-12-10','2026-01-30'),

-- 麓谷店番茄：总进货60，当前库存20
('B2-VEG-001',2,2,60, 20,3.20,'2025-12-10',4,'2025-12-09','2025-12-22'),
-- 麓谷店苹果：总进货40，当前库存9
('B2-FRU-001',5,2,40,  9,10.00,'2025-12-10',4,'2025-12-09','2026-01-10'),
-- 麓谷店牛腱子：总进货30，当前库存15
('B2-MEAT-001',8,2,30, 15,28.00,'2025-12-12',5,'2025-12-11','2025-12-28'),
-- 麓谷店三文鱼：总进货25，当前库存18（在库2+已售1=3，修正为3）
('B2-SEA-001',11,2,25,  3,35.00,'2025-12-12',5,'2025-12-11','2025-12-19'),

-- 梅溪湖店土豆：总进货50，当前库存35
('B3-VEG-001',3,3,50, 35, 4.00,'2025-12-11',7,'2025-12-10','2026-02-10'),
-- 梅溪湖店香蕉：总进货45，当前库存=在库1+损坏1=2（修正前7）
('B3-FRU-001',6,3,45,  2, 6.50,'2025-12-11',7,'2025-12-10','2025-12-21'),
-- 梅溪湖店三黄鸡：总进货20，当前库存10
('B3-MEAT-001',9,3,20, 10,25.00,'2025-12-12',8,'2025-12-11','2025-12-26');

-- 9) 客户订单表（保持不变）
INSERT INTO CustomerOrder
(order_ID, customer_ID, order_date, branch_ID, total_amount, final_amount, status, shipping_address)
VALUES
(1,1,'2025-12-14 10:15:00',1,  68.60,68.60,'Completed','长沙市岳麓区中南大学XX号'),
(2,2,'2025-12-14 11:20:00',1,  59.90,59.90,'Completed','长沙市雨花区XX路66号'),
(3,3,'2025-12-15 09:00:00',1,  8.80,0,'Pending','长沙市开福区XX小区'),
(4,1,'2025-12-14 14:10:00',2,  78.70,78.70,'Completed','长沙市岳麓区麓谷XX号'),
(5,2,'2025-12-15 09:30:00',2,  59.90,0,'Pending','长沙市望城区XX号'),
(6,3,'2025-12-13 19:00:00',2,  45.90,0,'Cancelled','长沙市天心区XX号'),
(7,1,'2025-12-14 16:00:00',3,  32.70,32.70,'Completed','长沙市梅溪湖XX号'),
(8,2,'2025-12-15 10:00:00',3,  39.90,0,'Pending','长沙市梅溪湖XX号');

-- 10) 库存明细项（核心修正：确保与Inventory.quantity_on_hand一致）
INSERT INTO StockItem
(item_ID, batch_ID, product_ID, branch_ID, purchase_order_ID, customer_order_ID, received_date, expiry_date, status)
VALUES
-- 中南店有机菠菜（共5条，与Inventory.quantity_on_hand=3匹配）
('SI-B1-SP-001','B1-VEG-001',1,1,1,1,'2025-12-10','2025-12-30','sold'),  -- 已售
('SI-B1-SP-002','B1-VEG-001',1,1,1,1,'2025-12-10','2025-12-30','sold'),  -- 已售
('SI-B1-SP-003','B1-VEG-001',1,1,1,4,'2025-12-10','2025-12-30','sold'),  -- 已售
('SI-B1-SP-004','B1-VEG-001',1,1,1,3,'2025-12-10','2025-12-30','pending'),-- 锁定
('SI-B1-SP-005','B1-VEG-001',1,1,1,NULL,'2025-12-10','2025-12-30','in_stock'),-- 在库
('SI-B1-SP-006','B1-VEG-001',1,1,1,NULL,'2025-12-10','2025-12-30','in_stock'),-- 在库

-- 麓谷店三文鱼（共3条，与Inventory.quantity_on_hand=3匹配）
('SI-B2-SA-001','B2-SEA-001',11,2,5,4,'2025-12-12','2025-12-19','sold'),   -- 已售
('SI-B2-SA-002','B2-SEA-001',11,2,5,NULL,'2025-12-12','2025-12-19','in_stock'),-- 在库
('SI-B2-SA-003','B2-SEA-001',11,2,5,NULL,'2025-12-12','2025-12-19','in_stock'),-- 在库

-- 梅溪湖店香蕉（共2条，与Inventory.quantity_on_hand=2匹配）
('SI-B3-BN-001','B3-FRU-001',6,3,7,NULL,'2025-12-11','2025-12-21','damaged'),-- 损坏
('SI-B3-BN-002','B3-FRU-001',6,3,7,NULL,'2025-12-11','2025-12-21','in_stock');-- 在库

SET SQL_SAFE_UPDATES = 0;

-- 只统计状态为'pending'且未被销售的库存项
SET SQL_SAFE_UPDATES = 0;

UPDATE Inventory i
SET locked_inventory = (
    SELECT COUNT(*) 
    FROM StockItem si 
    WHERE si.product_ID = i.product_ID 
      AND si.branch_ID = i.branch_ID 
      AND si.batch_ID = i.batch_ID  -- 关键：新增批次匹配，确保只统计本批次的锁定项
      AND si.status = 'pending'    -- 仅统计pending状态
);

SET SQL_SAFE_UPDATES = 1;

SET SQL_SAFE_UPDATES = 1;

-- 11) 采购明细表（保持不变）
INSERT INTO PurchaseItem (supply_id, purchase_order_ID, item_ID, product_ID, unit_cost, received_date) VALUES
(1,1,'SI-B1-SP-001',1,3.50,'2025-12-10'),
(2,1,'SI-B1-SP-002',1,3.50,'2025-12-10'),
(3,1,'SI-B1-SP-003',1,3.50,'2025-12-10'),
(4,5,'SI-B2-SA-001',11,35.00,'2025-12-12'),
(5,5,'SI-B2-SA-002',11,35.00,'2025-12-12');

-- 12) 订单明细表（保持不变）
INSERT INTO OrderItem (order_ID, item_ID, unit_price, product_ID,status) VALUES
(1,'SI-B1-SP-001',3.50,1,'completed'),  -- 匹配产品单价
(1,'SI-B1-SP-002',3.50,1,'completed'),  -- 匹配产品单价
(4,'SI-B2-SA-001',59.90,11,'completed'),-- 匹配产品单价
(3,'SI-B1-SP-004',3.50,1,'pending');    -- 匹配产品单价

-- 13) 库存凭证表（保持不变）
INSERT INTO StockItemCertificate (certificate_ID, item_ID, transaction_type, date, note, transaction_ID) VALUES
(1,'SI-B1-SP-001','purchase','2025-12-10 09:00:00','Empty',1),
(2,'SI-B2-SA-001','purchase','2025-12-12 10:00:00','Empty',5),
(3,'SI-B1-SP-001','sale','2025-12-14 10:15:00','Empty',1),
(4,'SI-B2-SA-001','sale','2025-12-14 14:10:00','Empty',4),
(5,'SI-B3-BN-001','adjustment','2025-12-12 12:00:00','Empty',NULL);

