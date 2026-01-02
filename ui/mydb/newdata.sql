USE mydb;

-- 清空旧数据
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

-- 1) 分类表
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

-- 添加CategoryAttribute表数据
INSERT INTO CategoryAttribute (category_id, attr_name, data_type, is_required) VALUES
-- 蔬菜类(5)的属性
(5, '产地', 'text', TRUE),
(5, '净含量', 'number', TRUE),
(5, '保鲜方式', 'text', TRUE),
(5, '种植方式', 'text', FALSE),
(5, '采摘日期', 'date', FALSE),

-- 水果类(6)的属性
(6, '产地', 'text', TRUE),
(6, '净含量', 'number', TRUE),
(6, '甜度等级', 'text', FALSE),
(6, '保鲜方式', 'text', TRUE),
(6, '品种', 'text', FALSE),

-- 肉类(7)的属性
(7, '产地', 'text', TRUE),
(7, '部位/品类', 'text', TRUE),
(7, '冷藏/冷冻', 'text', TRUE),
(7, '净含量', 'number', TRUE),
(7, '等级', 'text', FALSE),
(7, '饲养方式', 'text', FALSE),

-- 蛋类(8)的属性
(8, '产地', 'text', TRUE),
(8, '品种', 'text', FALSE),
(8, '规格', 'text', TRUE),
(8, '生产日期', 'date', TRUE),

-- 鱼类(9)的属性
(9, '产地', 'text', TRUE),
(9, '规格', 'number', TRUE),
(9, '冷藏/冷冻', 'text', TRUE),
(9, '品种', 'text', FALSE),
(9, '捕捞日期', 'date', FALSE),

-- 虾类(10)的属性
(10, '产地', 'text', TRUE),
(10, '规格', 'number', TRUE),
(10, '冷藏/冷冻', 'text', TRUE),
(10, '品种', 'text', FALSE),
(10, '加工方式', 'text', FALSE),

-- 其他水产品(11)的属性
(11, '产地', 'text', TRUE),
(11, '品种', 'text', TRUE),
(11, '净含量', 'number', TRUE),
(11, '加工方式', 'text', FALSE);

-- 2) 产品表（6个产品）
INSERT INTO products (product_ID, sku, product_name, status, unit_price, unit, description, category_id) VALUES
(1,'VEG-SPINACH-250','有机菠菜 250g','active',13.50,'g','当日采摘，冷链配送',5),
(2,'VEG-TOMATO-500','番茄 500g','active',17.90,'g','沙瓤番茄，口感酸甜',5),
(3,'FRU-STRAW-500','草莓 500g','active',39.90,'g','新鲜草莓，冷链直达',6),
(4,'MEAT-PORK-500','五花肉 500g','active',38.90,'g','精选猪五花',7),
(5,'Fish-SALMON-300','三文鱼 300g','active',69.90,'g','冰鲜切片',9),
(6,'SHRIMP-SHRIMP-500','大虾 500g','active',55.90,'g','冷冻保鲜',10);

-- 3) 产品属性表
INSERT INTO ProductAttribute (product_id, attr_name, attr_value) VALUES
(1,'产地','长沙本地'),(1,'净含量','250'),(1,'保鲜方式','冷藏'),
(2,'产地','湖南湘潭'),(2,'净含量','500'),(2,'保鲜方式','常温'),
(3,'产地','丹东'),(3,'净含量','500'),(3,'甜度等级','高'),
(4,'产地','湖南'),(4,'部位/品类','五花'),(4,'冷藏/冷冻','冷藏'),(4,'净含量','500'),
(5,'产地','挪威'),(5,'规格','300'),(5,'冷藏/冷冻','冷藏'),
(6,'产地','湛江'),(6,'规格','500'),(6,'冷藏/冷冻','冷冻');

-- 4) 门店表
INSERT INTO Branch (branch_ID, branch_name, address, phone, email, manager_ID, manager_phone, status) VALUES
(1,'鲜选生鲜·中南店','长沙市岳麓区中南大学附近XX路88号','0731-88880001','zn@freshharvest.com',NULL,NULL,'active'),
(2,'鲜选生鲜·麓谷店','长沙市岳麓区麓谷大道XX号','0731-88880002','lg@freshharvest.com',NULL,NULL,'active'),
(3,'鲜选生鲜·梅溪湖店','长沙市岳麓区梅溪湖路XX号','0731-88880003','mxh@freshharvest.com',NULL,NULL,'active');

-- 5) 用户表
INSERT INTO `User` (user_ID, user_name, password_hash, user_type, user_email, user_telephone, first_name, last_name, last_login, is_active) VALUES
-- CEO
(1,'ceo@localhost',MD5('Test1234'),'CEO','ceo@freshharvest.com','13900000000','Yuan','CEO',NULL,TRUE),

-- 中南店员工
(2,'m1@localhost',MD5('Test1234'),'staff','m1@freshharvest.com','13900000001','Lin','Manager',NULL,TRUE),
(3,'s1_b1@localhost',MD5('Test1234'),'staff','s1b1@freshharvest.com','13800000011','Chen','Sales',NULL,TRUE),
(4,'s2_b1@localhost',MD5('Test1234'),'staff','s2b1@freshharvest.com','13800000012','Sun','Sales',NULL,TRUE),
(5,'d_b1@localhost',MD5('Test1234'),'staff','db1@freshharvest.com','13700000013','Wu','Delivery',NULL,TRUE),

-- 麓谷店员工
(6,'m2@localhost',MD5('Test1234'),'staff','m2@freshharvest.com','13900000002','Zhou','Manager',NULL,TRUE),
(7,'s1_b2@localhost',MD5('Test1234'),'staff','s1b2@freshharvest.com','13800000021','Zheng','Sales',NULL,TRUE),
(8,'s2_b2@localhost',MD5('Test1234'),'staff','s2b2@freshharvest.com','13800000022','Xu','Sales',NULL,TRUE),
(9,'d_b2@localhost',MD5('Test1234'),'staff','db2@freshharvest.com','13700000023','Qian','Delivery',NULL,TRUE),

-- 梅溪湖店员工
(10,'m3@localhost',MD5('Test1234'),'staff','m3@freshharvest.com','13900000003','He','Manager',NULL,TRUE),
(11,'s1_b3@localhost',MD5('Test1234'),'staff','s1b3@freshharvest.com','13800000031','Li','Sales',NULL,TRUE),
(12,'s2_b3@localhost',MD5('Test1234'),'staff','s2b3@freshharvest.com','13800000032','Zhang','Sales',NULL,TRUE),
(13,'d_b3@localhost',MD5('Test1234'),'staff','db3@freshharvest.com','13700000033','Zhou','Delivery',NULL,TRUE),

-- 供应商
(14,'sup_a@localhost',MD5('Test1234'),'supplier','supa@vendor.com','13600000001','Supplier','A',NULL,TRUE),
(15,'sup_b@localhost',MD5('Test1234'),'supplier','supb@vendor.com','13600000002','Supplier','B',NULL,TRUE),
(16,'sup_c@localhost',MD5('Test1234'),'supplier','supc@vendor.com','13600000003','Supplier','C',NULL,TRUE),

-- 客户
(17,'cust_regular@localhost',MD5('Test1234'),'customer','regular@user.com','13500000001','Zhang','San',NULL,TRUE),
(18,'cust_vip@localhost',MD5('Test1234'),'customer','vip@user.com','13500000002','Li','Si',NULL,TRUE),
(19,'cust_vvip@localhost',MD5('Test1234'),'customer','vvip@user.com','13500000003','Wang','Wu',NULL,TRUE),

-- 新增供应商
(20,'sup_d@localhost',MD5('Test1234'),'supplier','supd@vendor.com','13600000004','Supplier','D',NULL,TRUE),  -- 果蔬
(21,'sup_e@localhost',MD5('Test1234'),'supplier','supe@vendor.com','13600000005','Supplier','E',NULL,TRUE),  -- 肉禽蛋
(22,'sup_f@localhost',MD5('Test1234'),'supplier','supf@vendor.com','13600000006','Supplier','F',NULL,TRUE),  -- 水产
(23,'sup_g@localhost',MD5('Test1234'),'supplier','supg@vendor.com','13600000007','Supplier','G',NULL,TRUE),  -- 果蔬
(24,'sup_h@localhost',MD5('Test1234'),'supplier','suph@vendor.com','13600000008','Supplier','H',NULL,TRUE),  -- 肉禽蛋
(25,'sup_i@localhost',MD5('Test1234'),'supplier','supi@vendor.com','13600000009','Supplier','I',NULL,TRUE);  -- 水产

-- 6) 客户/供应商/员工表
INSERT INTO Customer (customer_ID, user_name, phone, email, gender, address, loyalty_level, accu_cost) VALUES
(1,'cust_regular@localhost','13500000001','regular@user.com','Male','北京市朝阳区','Regular',0),
(2,'cust_vip@localhost','13500000002','vip@user.com','Male','上海市浦东区','VIP',180),
(3,'cust_vvip@localhost','13500000003','vvip@user.com','Male','广州市天河区','VVIP',300);

INSERT INTO Supplier (supplier_ID, user_name, company_name, contact_person, phone, email, address, tax_number, supplier_category, status) VALUES
(1,'sup_a@localhost','湘菜源蔬果基地','周供应','13600000001','supa@vendor.com','长沙市望城区果蔬综合基地','TAX-A-202512','果蔬','active'),
(2,'sup_b@localhost','肉禽蛋综合供应商','李集采','13600000002','supb@vendor.com','长沙市雨花区8号冷链仓','TAX-B-202512','肉禽蛋','active'),
(3,'sup_c@localhost','海鲜冷链直采','王海鲜','13600000003','supc@vendor.com','长沙市开福区海鲜水产仓储中心','TAX-C-202512','水产','active'),
(4,'sup_d@localhost','绿源蔬菜合作社','张蔬菜','13600000004','supd@vendor.com','长沙市芙蓉区蔬菜基地','TAX-D-202512','果蔬','active'),
(5,'sup_e@localhost','金牧场肉类供应','王肉类','13600000005','supe@vendor.com','长沙市天心区牧场园区','TAX-E-202512','肉禽蛋','active'),
(6,'sup_f@localhost','深海渔港水产','李海鲜','13600000006','supf@vendor.com','长沙市岳麓区渔港码头','TAX-F-202512','水产','active'),
(7,'sup_g@localhost','果园直供','赵水果','13600000007','supg@vendor.com','长沙市雨花区果园基地','TAX-G-202512','果蔬','active'),
(8,'sup_h@localhost','禽蛋专业合作社','钱禽蛋','13600000008','suph@vendor.com','长沙市开福区养殖基地','TAX-H-202512','肉禽蛋','active'),
(9,'sup_i@localhost','江河鲜水产','孙水产','13600000009','supi@vendor.com','长沙市望城区水产市场','TAX-I-202512','水产','active');

INSERT INTO Staff (staff_ID, branch_ID, user_name, position, phone, salary, hire_date, status) VALUES
-- 中南店
(1,1,'m1@localhost','Manager','13900000001',18000.00,'2024-03-01','active'),
(2,1,'s1_b1@localhost','Sales','13800000011',9000.00,'2024-08-15','active'),
(3,1,'s2_b1@localhost','Sales','13800000012',8800.00,'2024-09-01','active'),
(4,1,'d_b1@localhost','Deliveryman','13700000013',7000.00,'2024-10-01','active'),

-- 麓谷店
(5,2,'m2@localhost','Manager','13900000002',17500.00,'2024-04-01','active'),
(6,2,'s1_b2@localhost','Sales','13800000021',9200.00,'2024-08-20','active'),
(7,2,'s2_b2@localhost','Sales','13800000022',8900.00,'2024-09-10','active'),
(8,2,'d_b2@localhost','Deliveryman','13700000023',7200.00,'2024-10-10','active'),

-- 梅溪湖店
(9,3,'m3@localhost','Manager','13900000003',17800.00,'2024-05-01','active'),
(10,3,'s1_b3@localhost','Sales','13800000031',9300.00,'2024-08-25','active'),
(11,3,'s2_b3@localhost','Sales','13800000032',9000.00,'2024-09-20','active'),
(12,3,'d_b3@localhost','Deliveryman','13700000033',7300.00,'2024-10-20','active');

UPDATE Branch 
SET manager_ID = CASE branch_ID
    WHEN 1 THEN 1  -- 中南店经理 staff_ID=1
    WHEN 2 THEN 5  -- 麓谷店经理 staff_ID=5
    WHEN 3 THEN 9  -- 梅溪湖店经理 staff_ID=9
    END,
    manager_phone = CASE branch_ID
    WHEN 1 THEN '13900000001'
    WHEN 2 THEN '13900000002'
    WHEN 3 THEN '13900000003'
    END
WHERE branch_ID IN (1, 2, 3);

-- 13) 供应商-商品进货价映射（供应商只能卖对应种类）
INSERT INTO SupplierProduct (supplier_ID, product_ID, price) VALUES
-- 供应商1（果蔬）：只能卖商品1,2,3
(1,1,9.45),  -- 有机菠菜
(1,2,12.53), -- 番茄
(1,3,27.93), -- 草莓

-- 供应商2（肉禽蛋）：只能卖商品4
(2,4,28.90), -- 五花肉

-- 供应商3（水产）：只能卖商品5,6
(3,5,56.20), -- 三文鱼
(3,6,44.00), -- 大虾

-- 供应商4（果蔬）：只能卖商品1,2,3
(4,1,9.60),  -- 有机菠菜
(4,2,12.40), -- 番茄
(4,3,28.10), -- 草莓

-- 供应商5（肉禽蛋）：只能卖商品4
(5,4,29.50), -- 五花肉

-- 供应商6（水产）：只能卖商品5,6
(6,5,56.20), -- 三文鱼
(6,6,44.00), -- 大虾

-- 供应商7（果蔬）：只能卖商品1,2,3
(7,1,9.30),  -- 有机菠菜
(7,2,12.70), -- 番茄
(7,3,27.80), -- 草莓

-- 供应商8（肉禽蛋）：只能卖商品4
(8,4,28.90), -- 五花肉

-- 供应商9（水产）：只能卖商品5,6
(9,5,55.50), -- 三文鱼
(9,6,45.00); -- 大虾

-- 7) 采购订单表（每个店2个）--- 日期调整到1月3日之前，总金额按进货价计算
INSERT INTO PurchaseOrder (purchase_order_ID, supplier_ID, branch_ID, date, status, total_amount) VALUES
-- 中南店
(1,1,1,'2026-01-02','ordered', 94.50),  -- 10个菠菜 * 9.45（供应商1价格）= 94.50
(2,5,1,'2026-01-02','ordered', 236.00), -- 8个五花肉 * 29.50（供应商5价格）= 236.00

-- 麓谷店
(3,4,2,'2026-01-02','ordered', 115.20), -- 12个菠菜 * 9.60（供应商4价格）= 115.20
(4,6,2,'2026-01-02','ordered', 337.20), -- 6个三文鱼 * 56.20（供应商6价格）= 337.20

-- 梅溪湖店
(5,7,3,'2026-01-02','ordered', 139.50), -- 15个菠菜 * 9.30（供应商7价格）= 139.50
(6,1,3,'2026-01-02','ordered', 94.50);  -- 10个菠菜 * 9.45（供应商1价格）= 94.50

-- 8) 客户订单表 --- 日期调整到1月3日之前
INSERT INTO CustomerOrder (order_ID, customer_ID, order_date, branch_ID, total_amount, final_amount, status, shipping_address) VALUES
(1,1,'2026-01-02 10:15:00',1,  38.90, 0,'Pending','长沙市岳麓区中南大学XX号'),
(2,2,'2026-01-02 11:20:00',1,  27.00, 25.65,'Completed','长沙市雨花区XX路66号'),
(3,3,'2026-01-02 09:00:00',2,  13.50, 0,'Pending','长沙市开福区XX小区'),
(4,1,'2026-01-02 14:10:00',2,  69.90, 69.90,'Completed','长沙市岳麓区麓谷XX号');

-- 9) 库存表（每个店2个批次）--- 接收日期和过期日期已调整，unit_cost使用进货价
INSERT INTO Inventory (batch_ID, product_ID, branch_ID, locked_inventory, quantity_received, quantity_on_hand, unit_cost, received_date, order_ID, date_expired) VALUES
-- 中南店
('B1-VEG-001',1,1,0,10,8,9.45,'2026-01-02',1,'2026-03-15'),   -- 菠菜从供应商1进货，价格9.45
('B1-MEAT-001',4,1,1,8,8,29.50,'2026-01-02',2,'2026-03-10'),   -- 五花肉从供应商5进货，价格29.50

-- 麓谷店
('B2-VEG-001',1,2,1,12,12,9.60,'2026-01-02',3,'2026-03-18'),   -- 菠菜从供应商4进货，价格9.60
('B2-FISH-001',5,2,0,6,5,56.20,'2026-01-02',4,'2026-03-05'),   -- 三文鱼从供应商6进货，价格56.20

-- 梅溪湖店
('B3-VEG-001',1,3,0,15,14,9.30,'2026-01-02',5,'2026-03-20'),   -- 菠菜从供应商7进货，价格9.30
('B3-VEG-002',1,3,0,10,10,9.45,'2026-01-02',6,'2026-04-01');   -- 菠菜从供应商1进货，价格9.45

-- 10) 库存明细项（直接插入，与Inventory完全对应）
-- 中南店 B1-VEG-001 批次：10个有机菠菜
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

-- 中南店 B1-MEAT-001 批次：8个五花肉
INSERT INTO StockItem (item_ID, batch_ID, product_ID, branch_ID, purchase_order_ID, customer_order_ID, received_date, expiry_date, status) VALUES
('SI-B1MEAT001-001','B1-MEAT-001',4,1,2,1,'2026-01-02','2026-03-10','pending'),
('SI-B1MEAT001-002','B1-MEAT-001',4,1,2,NULL,'2026-01-02','2026-03-10','in_stock'),
('SI-B1MEAT001-003','B1-MEAT-001',4,1,2,NULL,'2026-01-02','2026-03-10','in_stock'),
('SI-B1MEAT001-004','B1-MEAT-001',4,1,2,NULL,'2026-01-02','2026-03-10','in_stock'),
('SI-B1MEAT001-005','B1-MEAT-001',4,1,2,NULL,'2026-01-02','2026-03-10','in_stock'),
('SI-B1MEAT001-006','B1-MEAT-001',4,1,2,NULL,'2026-01-02','2026-03-10','in_stock'),
('SI-B1MEAT001-007','B1-MEAT-001',4,1,2,NULL,'2026-01-02','2026-03-10','in_stock'),
('SI-B1MEAT001-008','B1-MEAT-001',4,1,2,NULL,'2026-01-02','2026-03-10','in_stock');

-- 麓谷店 B2-VEG-001 批次：12个有机菠菜
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

-- 麓谷店 B2-FISH-001 批次：6个三文鱼
INSERT INTO StockItem (item_ID, batch_ID, product_ID, branch_ID, purchase_order_ID, customer_order_ID, received_date, expiry_date, status) VALUES
('SI-B2FISH001-001','B2-FISH-001',5,2,4,4,'2026-01-02','2026-03-05','sold'),
('SI-B2FISH001-002','B2-FISH-001',5,2,4,NULL,'2026-01-02','2026-03-05','in_stock'),
('SI-B2FISH001-003','B2-FISH-001',5,2,4,NULL,'2026-01-02','2026-03-05','in_stock'),
('SI-B2FISH001-004','B2-FISH-001',5,2,4,NULL,'2026-01-02','2026-03-05','in_stock'),
('SI-B2FISH001-005','B2-FISH-001',5,2,4,NULL,'2026-01-02','2026-03-05','in_stock'),
('SI-B2FISH001-006','B2-FISH-001',5,2,4,NULL,'2026-01-02','2026-03-05','in_stock');

-- 梅溪湖店 B3-VEG-001 批次：15个有机菠菜
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

-- 梅溪湖店 B3-VEG-002 批次：10个有机菠菜
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

-- 11) 采购明细表 --- unit_cost使用进货价
INSERT INTO PurchaseItem (supply_id, purchase_order_ID, item_ID, product_ID, unit_cost, received_date) VALUES
-- 采购订单1（菠菜，供应商1，价格9.45）
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

-- 采购订单2（五花肉，供应商5，价格29.50）
(11,2,'SI-B1MEAT001-001',4,29.50,'2026-01-02'),
(12,2,'SI-B1MEAT001-002',4,29.50,'2026-01-02'),
(13,2,'SI-B1MEAT001-003',4,29.50,'2026-01-02'),
(14,2,'SI-B1MEAT001-004',4,29.50,'2026-01-02'),
(15,2,'SI-B1MEAT001-005',4,29.50,'2026-01-02'),
(16,2,'SI-B1MEAT001-006',4,29.50,'2026-01-02'),
(17,2,'SI-B1MEAT001-007',4,29.50,'2026-01-02'),
(18,2,'SI-B1MEAT001-008',4,29.50,'2026-01-02'),

-- 采购订单3（菠菜，供应商4，价格9.60）
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

-- 采购订单4（三文鱼，供应商6，价格56.20）
(31,4,'SI-B2FISH001-001',5,56.20,'2026-01-02'),
(32,4,'SI-B2FISH001-002',5,56.20,'2026-01-02'),
(33,4,'SI-B2FISH001-003',5,56.20,'2026-01-02'),
(34,4,'SI-B2FISH001-004',5,56.20,'2026-01-02'),
(35,4,'SI-B2FISH001-005',5,56.20,'2026-01-02'),
(36,4,'SI-B2FISH001-006',5,56.20,'2026-01-02'),

-- 采购订单5（菠菜，供应商7，价格9.30）
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

-- 采购订单6（菠菜，供应商1，价格9.45）
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

-- 12) 订单明细表
INSERT INTO OrderItem (order_ID, item_ID, unit_price, product_ID, quantity, status) VALUES
-- 订单1：1个五花肉（pending）
(1,'SI-B1MEAT001-001',38.90,4,1,'pending'),

-- 订单2：2个有机菠菜（completed）
(2,'SI-B1VEG001-001',13.50,1,1,'completed'),
(2,'SI-B1VEG001-002',13.50,1,1,'completed'),

-- 订单3：1个有机菠菜（pending）
(3,'SI-B2VEG001-001',13.50,1,1,'pending'),

-- 订单4：1个三文鱼（completed）
(4,'SI-B2FISH001-001',69.90,5,1,'completed');

-- 14) 库存凭证表
INSERT INTO StockItemCertificate (certificate_ID, item_ID, transaction_type, date, note, transaction_ID) VALUES
-- 采购记录
(1,'SI-B1VEG001-001','purchase','2026-01-02 09:00:00','进货有机菠菜',1),
(2,'SI-B1MEAT001-001','purchase','2026-01-02 10:00:00','进货五花肉',2),
(3,'SI-B2VEG001-001','purchase','2026-01-02 09:00:00','进货有机菠菜',3),
(4,'SI-B2FISH001-001','purchase','2026-01-02 10:00:00','进货三文鱼',4),
(5,'SI-B3VEG001-001','purchase','2026-01-02 09:00:00','进货有机菠菜',5),
(6,'SI-B3VEG002-001','purchase','2026-01-02 09:00:00','进货有机菠菜',6),

-- 销售记录
(7,'SI-B1VEG001-001','sale','2026-01-02 10:15:00','售出给订单2',2),
(8,'SI-B1VEG001-002','sale','2026-01-02 10:15:00','售出给订单2',2),
(9,'SI-B2FISH001-001','sale','2026-01-02 14:10:00','售出给订单4',4),
(10,'SI-B3VEG001-015','adjustment','2026-01-02 12:00:00','商品损坏报损',NULL);