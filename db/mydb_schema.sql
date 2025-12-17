CREATE DATABASE  IF NOT EXISTS `mydb` /*!40100 DEFAULT CHARACTER SET utf8mb3 */ /*!80016 DEFAULT ENCRYPTION='N' */;
USE `mydb`;
-- MySQL dump 10.13  Distrib 8.0.43, for macos15 (arm64)
--
-- Host: localhost    Database: mydb
-- ------------------------------------------------------
-- Server version	9.4.0

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!50503 SET NAMES utf8 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;

--
-- Table structure for table `Branch`
--

DROP TABLE IF EXISTS `Branch`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `Branch` (
  `branch_ID` int NOT NULL AUTO_INCREMENT,
  `branch_name` varchar(100) NOT NULL,
  `address` varchar(255) NOT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `email` varchar(100) DEFAULT NULL,
  `manager_ID` int DEFAULT NULL,
  `manager_phone` varchar(20) DEFAULT NULL,
  `status` enum('active','inactive','under_renovation') DEFAULT 'active',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`branch_ID`),
  UNIQUE KEY `branch_name` (`branch_name`),
  KEY `fk_branch_manager` (`manager_ID`),
  CONSTRAINT `fk_branch_manager` FOREIGN KEY (`manager_ID`) REFERENCES `Staff` (`staff_ID`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb3;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `Branch`
--

LOCK TABLES `Branch` WRITE;
/*!40000 ALTER TABLE `Branch` DISABLE KEYS */;
INSERT INTO `Branch` VALUES (1,'鲜选生鲜·中南店','长沙市岳麓区中南大学附近XX路88号','0731-88880001','zn@freshharvest.com',1,'13900000001','active','2025-12-17 07:30:03'),(2,'鲜选生鲜·麓谷店','长沙市岳麓区麓谷大道XX号','0731-88880002','lg@freshharvest.com',6,'13900000003','active','2025-12-17 07:30:03'),(3,'鲜选生鲜·梅溪湖店','长沙市岳麓区梅溪湖路XX号','0731-88880003','mxh@freshharvest.com',11,'13900000005','active','2025-12-17 07:30:03');
/*!40000 ALTER TABLE `Branch` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `Categories`
--

DROP TABLE IF EXISTS `Categories`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `Categories` (
  `category_id` int NOT NULL AUTO_INCREMENT,
  `category_name` varchar(100) NOT NULL,
  `parent_category_id` int DEFAULT NULL,
  `description` text,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`category_id`),
  KEY `fk_parent_category` (`parent_category_id`),
  CONSTRAINT `fk_parent_category` FOREIGN KEY (`parent_category_id`) REFERENCES `Categories` (`category_id`)
) ENGINE=InnoDB AUTO_INCREMENT=12 DEFAULT CHARSET=utf8mb3;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `Categories`
--

LOCK TABLES `Categories` WRITE;
/*!40000 ALTER TABLE `Categories` DISABLE KEYS */;
INSERT INTO `Categories` VALUES (1,'生鲜',NULL,'FreshHarvest 主分类','2025-12-17 07:30:03'),(2,'果蔬',1,'蔬菜水果父类','2025-12-17 07:30:03'),(3,'肉禽蛋',1,'肉类与蛋类父类','2025-12-17 07:30:03'),(4,'水产',1,'水产品父类','2025-12-17 07:30:03'),(5,'蔬菜',2,'当季蔬菜，叶菜、根茎类','2025-12-17 07:30:03'),(6,'水果',2,'当季水果、进口水果','2025-12-17 07:30:03'),(7,'肉',3,'猪牛羊、禽类等肉类','2025-12-17 07:30:03'),(8,'蛋',3,'鸡蛋等蛋类','2025-12-17 07:30:03'),(9,'鱼',4,'鱼类水产','2025-12-17 07:30:03'),(10,'虾',4,'虾类水产','2025-12-17 07:30:03'),(11,'其他水产',4,'海带等其他水生动植物/水产','2025-12-17 07:30:03');
/*!40000 ALTER TABLE `Categories` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `CategoryAttribute`
--

DROP TABLE IF EXISTS `CategoryAttribute`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `CategoryAttribute` (
  `category_id` int NOT NULL,
  `attr_name` varchar(50) NOT NULL,
  `data_type` enum('text','number','date','boolean','color','size') DEFAULT 'text',
  `is_required` tinyint(1) DEFAULT '0',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`category_id`,`attr_name`),
  CONSTRAINT `fk_category_attribute` FOREIGN KEY (`category_id`) REFERENCES `Categories` (`category_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `CategoryAttribute`
--

LOCK TABLES `CategoryAttribute` WRITE;
/*!40000 ALTER TABLE `CategoryAttribute` DISABLE KEYS */;
/*!40000 ALTER TABLE `CategoryAttribute` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `Customer`
--

DROP TABLE IF EXISTS `Customer`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `Customer` (
  `customer_ID` int NOT NULL AUTO_INCREMENT,
  `user_name` varchar(45) DEFAULT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `email` varchar(100) DEFAULT NULL,
  `loyalty_level` enum('Regular','VIP','VVIP') DEFAULT 'Regular',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`customer_ID`),
  UNIQUE KEY `email` (`email`),
  KEY `fk_customer_user_name` (`user_name`),
  CONSTRAINT `fk_customer_user_name` FOREIGN KEY (`user_name`) REFERENCES `User` (`user_name`)
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb3;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `Customer`
--

LOCK TABLES `Customer` WRITE;
/*!40000 ALTER TABLE `Customer` DISABLE KEYS */;
INSERT INTO `Customer` VALUES (1,'c1','13800000001','c1@test.com','Regular','2025-12-17 07:30:03'),(2,'c2','13800000002','c2@test.com','VIP','2025-12-17 07:30:03'),(3,'c3','13800000003','c3@test.com','Regular','2025-12-17 07:30:03'),(4,'c4','13800000004','c4@test.com','VVIP','2025-12-17 07:30:03'),(5,'c5','13800000005','c5@test.com','Regular','2025-12-17 07:30:03');
/*!40000 ALTER TABLE `Customer` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `CustomerOrder`
--

DROP TABLE IF EXISTS `CustomerOrder`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `CustomerOrder` (
  `order_ID` int NOT NULL AUTO_INCREMENT,
  `customer_ID` int DEFAULT NULL,
  `order_date` datetime DEFAULT CURRENT_TIMESTAMP,
  `branch_ID` int NOT NULL,
  `total_amount` decimal(10,2) NOT NULL DEFAULT '0.00',
  `status` enum('Pending','Completed','Cancelled') DEFAULT 'Pending',
  `shipping_address` varchar(255) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`order_ID`),
  KEY `fk_CustomerOrder_branch_id` (`branch_ID`),
  KEY `fk_CustomerOrder_customer_id` (`customer_ID`),
  CONSTRAINT `fk_CustomerOrder_branch_id` FOREIGN KEY (`branch_ID`) REFERENCES `Branch` (`branch_ID`),
  CONSTRAINT `fk_CustomerOrder_customer_id` FOREIGN KEY (`customer_ID`) REFERENCES `Customer` (`customer_ID`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb3;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `CustomerOrder`
--

LOCK TABLES `CustomerOrder` WRITE;
/*!40000 ALTER TABLE `CustomerOrder` DISABLE KEYS */;
INSERT INTO `CustomerOrder` VALUES (1,1,'2025-12-15 10:10:00',1,67.60,'Completed','长沙市岳麓区中南大学XX宿舍','2025-12-17 07:30:03'),(2,2,'2025-12-15 11:20:00',1,34.80,'Completed','长沙市岳麓区XX小区','2025-12-17 07:30:03'),(3,3,'2025-12-16 09:00:00',2,0.00,'Pending','长沙市岳麓区麓谷XX','2025-12-17 07:30:03'),(4,4,'2025-12-16 12:30:00',3,58.70,'Completed','长沙市岳麓区梅溪湖XX','2025-12-17 07:30:03');
/*!40000 ALTER TABLE `CustomerOrder` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `Inventory`
--

DROP TABLE IF EXISTS `Inventory`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `Inventory` (
  `batch_ID` varchar(20) NOT NULL,
  `product_ID` int NOT NULL,
  `branch_ID` int NOT NULL,
  `quantity_received` int NOT NULL,
  `quantity_on_hand` int NOT NULL,
  `unit_cost` decimal(10,2) NOT NULL,
  `received_date` date DEFAULT NULL,
  `order_ID` int DEFAULT NULL,
  `date_produced` date DEFAULT NULL,
  `date_expired` date DEFAULT NULL,
  PRIMARY KEY (`batch_ID`),
  KEY `fk_inventory_branch_id` (`branch_ID`),
  KEY `fk_inventory_product_id` (`product_ID`),
  KEY `fk_order_id` (`order_ID`),
  CONSTRAINT `fk_inventory_branch_id` FOREIGN KEY (`branch_ID`) REFERENCES `Branch` (`branch_ID`),
  CONSTRAINT `fk_inventory_product_id` FOREIGN KEY (`product_ID`) REFERENCES `products` (`product_ID`),
  CONSTRAINT `fk_order_id` FOREIGN KEY (`order_ID`) REFERENCES `PurchaseOrder` (`purchase_order_ID`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `Inventory`
--

LOCK TABLES `Inventory` WRITE;
/*!40000 ALTER TABLE `Inventory` DISABLE KEYS */;
INSERT INTO `Inventory` VALUES ('B1-EGG-001',10,1,60,50,12.00,'2025-12-10',4,'2025-12-09','2025-12-30'),('B1-FISH-001',11,1,30,20,35.00,'2025-12-11',5,'2025-12-10','2025-12-22'),('B1-FRU-001',4,1,40,30,18.00,'2025-12-02',2,'2025-12-01','2025-12-18'),('B1-FRU-002',6,1,50,41,12.80,'2025-12-02',2,'2025-12-01','2026-01-10'),('B1-MEAT-001',7,1,40,28,16.50,'2025-12-03',3,'2025-12-02','2025-12-25'),('B1-SEA-001',12,1,30,25,26.00,'2025-12-12',6,'2025-12-11','2026-01-30'),('B1-VEG-001',1,1,60,45,3.50,'2025-12-01',1,'2025-11-30','2025-12-20'),('B1-VEG-002',2,1,80,72,3.20,'2025-12-01',1,'2025-11-30','2025-12-22'),('B2-EGG-001',10,2,40,36,12.00,'2025-12-10',10,'2025-12-09','2025-12-30'),('B2-FISH-001',11,2,25,21,35.00,'2025-12-11',11,'2025-12-10','2025-12-22'),('B2-FRU-001',5,2,50,44,10.00,'2025-12-02',8,'2025-12-01','2026-01-10'),('B2-MEAT-001',8,2,30,25,28.00,'2025-12-03',9,'2025-12-02','2025-12-28'),('B2-SEA-001',13,2,20,17,40.00,'2025-12-12',12,'2025-12-11','2026-01-20'),('B2-VEG-001',1,2,60,52,3.50,'2025-12-01',7,'2025-11-30','2025-12-20'),('B3-EGG-001',10,3,35,30,12.00,'2025-12-10',16,'2025-12-09','2025-12-30'),('B3-FISH-001',11,3,25,20,35.00,'2025-12-11',17,'2025-12-10','2025-12-22'),('B3-FRU-001',6,3,40,34,12.80,'2025-12-02',14,'2025-12-01','2026-01-10'),('B3-MEAT-001',9,3,25,19,25.00,'2025-12-03',15,'2025-12-02','2025-12-26'),('B3-SEA-001',12,3,25,21,26.00,'2025-12-12',18,'2025-12-11','2026-01-30'),('B3-VEG-001',3,3,70,63,4.00,'2025-12-01',13,'2025-11-30','2026-02-10');
/*!40000 ALTER TABLE `Inventory` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `OrderItem`
--

DROP TABLE IF EXISTS `OrderItem`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `OrderItem` (
  `order_ID` int NOT NULL,
  `item_ID` varchar(50) NOT NULL,
  `quantity` int NOT NULL DEFAULT '1',
  `unit_price` decimal(10,2) NOT NULL,
  `product_ID` int NOT NULL,
  PRIMARY KEY (`order_ID`,`item_ID`),
  KEY `fk_OrderItem_product_id` (`product_ID`),
  KEY `fk_OrderItem_item_ID` (`item_ID`),
  CONSTRAINT `fk_OrderItem_item_ID` FOREIGN KEY (`item_ID`) REFERENCES `StockItem` (`item_ID`),
  CONSTRAINT `fk_OrderItem_order_id` FOREIGN KEY (`order_ID`) REFERENCES `CustomerOrder` (`order_ID`),
  CONSTRAINT `fk_OrderItem_product_id` FOREIGN KEY (`product_ID`) REFERENCES `products` (`product_ID`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `OrderItem`
--

LOCK TABLES `OrderItem` WRITE;
/*!40000 ALTER TABLE `OrderItem` DISABLE KEYS */;
INSERT INTO `OrderItem` VALUES (1,'SI-B1-0001',1,8.80,1),(1,'SI-B1-0021',1,29.90,4),(1,'SI-B1-0027',1,28.90,7),(2,'SI-B1-0011',1,7.90,2),(2,'SI-B1-0033',1,26.90,10),(4,'SI-B3-0002',1,12.80,6),(4,'SI-B3-0006',1,45.90,12);
/*!40000 ALTER TABLE `OrderItem` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `ProductAttribute`
--

DROP TABLE IF EXISTS `ProductAttribute`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `ProductAttribute` (
  `product_id` int NOT NULL,
  `attr_name` varchar(50) NOT NULL,
  `attr_value` varchar(200) NOT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`product_id`,`attr_name`),
  CONSTRAINT `fk_product_attribute_product` FOREIGN KEY (`product_id`) REFERENCES `products` (`product_ID`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `ProductAttribute`
--

LOCK TABLES `ProductAttribute` WRITE;
/*!40000 ALTER TABLE `ProductAttribute` DISABLE KEYS */;
/*!40000 ALTER TABLE `ProductAttribute` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `products`
--

DROP TABLE IF EXISTS `products`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `products` (
  `product_ID` int NOT NULL AUTO_INCREMENT,
  `sku` varchar(45) NOT NULL,
  `product_name` varchar(45) NOT NULL,
  `status` enum('active','discontinued') DEFAULT 'active',
  `unit_price` decimal(10,2) NOT NULL,
  `unit` varchar(20) DEFAULT NULL,
  `description` text,
  `category_id` int NOT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`product_ID`),
  UNIQUE KEY `sku` (`sku`),
  KEY `fk_products_category` (`category_id`),
  CONSTRAINT `fk_products_category` FOREIGN KEY (`category_id`) REFERENCES `Categories` (`category_id`)
) ENGINE=InnoDB AUTO_INCREMENT=14 DEFAULT CHARSET=utf8mb3;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `products`
--

LOCK TABLES `products` WRITE;
/*!40000 ALTER TABLE `products` DISABLE KEYS */;
INSERT INTO `products` VALUES (1,'VEG-SPINACH-250','有机菠菜 250g','active',8.80,'g','当日采摘，冷链配送',5,'2025-12-17 07:30:03'),(2,'VEG-TOMATO-500','番茄 500g','active',7.90,'g','沙瓤番茄，口感酸甜',5,'2025-12-17 07:30:03'),(3,'VEG-POTATO-1K','土豆 1kg','active',9.90,'kg','黄心土豆，耐储存',5,'2025-12-17 07:30:03'),(4,'FRU-STRAW-500','草莓 500g','active',29.90,'g','新鲜草莓，冷链直达',6,'2025-12-17 07:30:03'),(5,'FRU-APPLE-1K','红富士苹果 1kg','active',18.80,'kg','脆甜多汁',6,'2025-12-17 07:30:03'),(6,'FRU-BANANA-1K','香蕉 1kg','active',12.80,'kg','香甜软糯',6,'2025-12-17 07:30:03'),(7,'MEAT-PORK-500','五花肉 500g','active',28.90,'g','精选猪五花',7,'2025-12-17 07:30:03'),(8,'MEAT-BEEF-500','牛腱子 500g','active',49.90,'g','适合卤煮炖煮',7,'2025-12-17 07:30:03'),(9,'MEAT-CHICK-1','三黄鸡 1只','active',39.90,'只','散养鸡，冷链配送',7,'2025-12-17 07:30:03'),(10,'EGG-30','鲜鸡蛋 30枚','active',26.90,'枚','家庭装鸡蛋',8,'2025-12-17 07:30:03'),(11,'FISH-SALMON-300','三文鱼 300g','active',59.90,'g','冰鲜切片',9,'2025-12-17 07:30:03'),(12,'SHRIMP-500','大虾 500g','active',45.90,'g','冷冻保鲜',10,'2025-12-17 07:30:03'),(13,'SEA-CRAB-2','梭子蟹 2只','active',69.00,'只','季节限定',11,'2025-12-17 07:30:03');
/*!40000 ALTER TABLE `products` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `PurchaseItem`
--

DROP TABLE IF EXISTS `PurchaseItem`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `PurchaseItem` (
  `supply_id` int NOT NULL AUTO_INCREMENT,
  `purchase_order_ID` int NOT NULL,
  `item_ID` varchar(50) NOT NULL,
  `product_ID` int NOT NULL,
  `unit_cost` decimal(10,2) NOT NULL,
  `received_date` date DEFAULT NULL,
  PRIMARY KEY (`supply_id`),
  KEY `fk_PurchaseItem_product_id` (`product_ID`),
  KEY `fk_PurchaseItem_item_id` (`item_ID`),
  KEY `fk_PurchaseItem_order_id` (`purchase_order_ID`),
  CONSTRAINT `fk_PurchaseItem_item_id` FOREIGN KEY (`item_ID`) REFERENCES `StockItem` (`item_ID`),
  CONSTRAINT `fk_PurchaseItem_order_id` FOREIGN KEY (`purchase_order_ID`) REFERENCES `PurchaseOrder` (`purchase_order_ID`),
  CONSTRAINT `fk_PurchaseItem_product_id` FOREIGN KEY (`product_ID`) REFERENCES `products` (`product_ID`)
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb3;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `PurchaseItem`
--

LOCK TABLES `PurchaseItem` WRITE;
/*!40000 ALTER TABLE `PurchaseItem` DISABLE KEYS */;
INSERT INTO `PurchaseItem` VALUES (1,1,'SI-B1-0001',1,3.50,'2025-12-01'),(2,1,'SI-B1-0011',2,3.20,'2025-12-01'),(3,2,'SI-B1-0021',4,18.00,'2025-12-02'),(4,3,'SI-B1-0027',7,16.50,'2025-12-03'),(5,4,'SI-B1-0033',10,12.00,'2025-12-10'),(6,5,'SI-B1-0037',11,35.00,'2025-12-11');
/*!40000 ALTER TABLE `PurchaseItem` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `PurchaseOrder`
--

DROP TABLE IF EXISTS `PurchaseOrder`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `PurchaseOrder` (
  `purchase_order_ID` int NOT NULL AUTO_INCREMENT,
  `supplier_ID` int NOT NULL,
  `branch_ID` int NOT NULL,
  `date` date NOT NULL,
  `status` enum('pending','ordered','received','cancelled') DEFAULT 'pending',
  `total_amount` decimal(10,2) DEFAULT '0.00',
  PRIMARY KEY (`purchase_order_ID`),
  KEY `fk_branch_id` (`branch_ID`),
  KEY `fk_supplier_id` (`supplier_ID`),
  CONSTRAINT `fk_branch_id` FOREIGN KEY (`branch_ID`) REFERENCES `Branch` (`branch_ID`),
  CONSTRAINT `fk_supplier_id` FOREIGN KEY (`supplier_ID`) REFERENCES `Supplier` (`supplier_ID`)
) ENGINE=InnoDB AUTO_INCREMENT=19 DEFAULT CHARSET=utf8mb3;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `PurchaseOrder`
--

LOCK TABLES `PurchaseOrder` WRITE;
/*!40000 ALTER TABLE `PurchaseOrder` DISABLE KEYS */;
INSERT INTO `PurchaseOrder` VALUES (1,1,1,'2025-12-01','received',466.00),(2,2,1,'2025-12-02','received',1360.00),(3,3,1,'2025-12-03','received',660.00),(4,1,1,'2025-12-10','received',720.00),(5,2,1,'2025-12-11','received',1050.00),(6,3,1,'2025-12-12','received',780.00),(7,4,2,'2025-12-01','received',210.00),(8,5,2,'2025-12-02','received',500.00),(9,6,2,'2025-12-03','received',840.00),(10,4,2,'2025-12-10','received',480.00),(11,5,2,'2025-12-11','received',875.00),(12,6,2,'2025-12-12','received',800.00),(13,7,3,'2025-12-01','received',280.00),(14,8,3,'2025-12-02','received',512.00),(15,9,3,'2025-12-03','received',625.00),(16,7,3,'2025-12-10','received',420.00),(17,8,3,'2025-12-11','received',875.00),(18,9,3,'2025-12-12','received',650.00);
/*!40000 ALTER TABLE `PurchaseOrder` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `Staff`
--

DROP TABLE IF EXISTS `Staff`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `Staff` (
  `staff_ID` int NOT NULL AUTO_INCREMENT,
  `branch_ID` int NOT NULL,
  `user_name` varchar(45) DEFAULT NULL,
  `position` enum('Manager','Sales','Deliveryman') NOT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `salary` decimal(10,2) NOT NULL,
  `hire_date` date NOT NULL,
  `status` enum('active','on_leave','terminated') DEFAULT 'active',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`staff_ID`),
  KEY `fk_staff_user_name` (`user_name`),
  KEY `fk_Staff_branch_id` (`branch_ID`),
  CONSTRAINT `fk_Staff_branch_id` FOREIGN KEY (`branch_ID`) REFERENCES `Branch` (`branch_ID`),
  CONSTRAINT `fk_staff_user_name` FOREIGN KEY (`user_name`) REFERENCES `User` (`user_name`)
) ENGINE=InnoDB AUTO_INCREMENT=16 DEFAULT CHARSET=utf8mb3;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `Staff`
--

LOCK TABLES `Staff` WRITE;
/*!40000 ALTER TABLE `Staff` DISABLE KEYS */;
INSERT INTO `Staff` VALUES (1,1,'b1_mgr','Manager','13900001001',12000.00,'2025-10-01','active','2025-12-17 07:30:03'),(2,1,'b1_s1','Sales','13900001002',7000.00,'2025-10-05','active','2025-12-17 07:30:03'),(3,1,'b1_s2','Sales','13900001003',7000.00,'2025-10-06','active','2025-12-17 07:30:03'),(4,1,'b1_s3','Sales','13900001004',7000.00,'2025-10-07','active','2025-12-17 07:30:03'),(5,1,'b1_d1','Deliveryman','13900001005',6500.00,'2025-10-08','active','2025-12-17 07:30:03'),(6,2,'b2_mgr','Manager','13900002001',12000.00,'2025-10-01','active','2025-12-17 07:30:03'),(7,2,'b2_s1','Sales','13900002002',7000.00,'2025-10-05','active','2025-12-17 07:30:03'),(8,2,'b2_s2','Sales','13900002003',7000.00,'2025-10-06','active','2025-12-17 07:30:03'),(9,2,'b2_s3','Sales','13900002004',7000.00,'2025-10-07','active','2025-12-17 07:30:03'),(10,2,'b2_d1','Deliveryman','13900002005',6500.00,'2025-10-08','active','2025-12-17 07:30:03'),(11,3,'b3_mgr','Manager','13900003001',12000.00,'2025-10-01','active','2025-12-17 07:30:03'),(12,3,'b3_s1','Sales','13900003002',7000.00,'2025-10-05','active','2025-12-17 07:30:03'),(13,3,'b3_s2','Sales','13900003003',7000.00,'2025-10-06','active','2025-12-17 07:30:03'),(14,3,'b3_s3','Sales','13900003004',7000.00,'2025-10-07','active','2025-12-17 07:30:03'),(15,3,'b3_d1','Deliveryman','13900003005',6500.00,'2025-10-08','active','2025-12-17 07:30:03');
/*!40000 ALTER TABLE `Staff` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `StockItem`
--

DROP TABLE IF EXISTS `StockItem`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `StockItem` (
  `item_ID` varchar(50) NOT NULL,
  `batch_ID` varchar(20) NOT NULL,
  `product_ID` int NOT NULL,
  `branch_ID` int NOT NULL,
  `purchase_order_ID` int DEFAULT NULL,
  `customer_order_ID` int DEFAULT NULL,
  `received_date` date NOT NULL,
  `expiry_date` date DEFAULT NULL,
  `status` enum('in_stock','sold','returned','damaged') DEFAULT 'in_stock',
  PRIMARY KEY (`item_ID`),
  KEY `fk_batch_id` (`batch_ID`),
  KEY `fk_Stock_purchase_order_id` (`purchase_order_ID`),
  KEY `fk_Stock_customer_order_id` (`customer_order_ID`),
  KEY `fk_StockItem_product_id` (`product_ID`),
  KEY `fk_StockItem_branch_id` (`branch_ID`),
  CONSTRAINT `fk_batch_id` FOREIGN KEY (`batch_ID`) REFERENCES `Inventory` (`batch_ID`),
  CONSTRAINT `fk_Stock_customer_order_id` FOREIGN KEY (`customer_order_ID`) REFERENCES `CustomerOrder` (`order_ID`),
  CONSTRAINT `fk_Stock_purchase_order_id` FOREIGN KEY (`purchase_order_ID`) REFERENCES `PurchaseOrder` (`purchase_order_ID`),
  CONSTRAINT `fk_StockItem_branch_id` FOREIGN KEY (`branch_ID`) REFERENCES `Branch` (`branch_ID`),
  CONSTRAINT `fk_StockItem_product_id` FOREIGN KEY (`product_ID`) REFERENCES `products` (`product_ID`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `StockItem`
--

LOCK TABLES `StockItem` WRITE;
/*!40000 ALTER TABLE `StockItem` DISABLE KEYS */;
INSERT INTO `StockItem` VALUES ('SI-B1-0001','B1-VEG-001',1,1,1,1,'2025-12-01','2025-12-20','sold'),('SI-B1-0002','B1-VEG-001',1,1,1,NULL,'2025-12-01','2025-12-20','in_stock'),('SI-B1-0003','B1-VEG-001',1,1,1,NULL,'2025-12-01','2025-12-20','in_stock'),('SI-B1-0004','B1-VEG-001',1,1,1,NULL,'2025-12-01','2025-12-20','in_stock'),('SI-B1-0005','B1-VEG-001',1,1,1,NULL,'2025-12-01','2025-12-20','in_stock'),('SI-B1-0006','B1-VEG-001',1,1,1,NULL,'2025-12-01','2025-12-20','in_stock'),('SI-B1-0007','B1-VEG-001',1,1,1,NULL,'2025-12-01','2025-12-20','in_stock'),('SI-B1-0008','B1-VEG-001',1,1,1,NULL,'2025-12-01','2025-12-20','in_stock'),('SI-B1-0009','B1-VEG-001',1,1,1,NULL,'2025-12-01','2025-12-20','in_stock'),('SI-B1-0010','B1-VEG-001',1,1,1,NULL,'2025-12-01','2025-12-20','in_stock'),('SI-B1-0011','B1-VEG-002',2,1,1,2,'2025-12-01','2025-12-22','sold'),('SI-B1-0012','B1-VEG-002',2,1,1,NULL,'2025-12-01','2025-12-22','in_stock'),('SI-B1-0013','B1-VEG-002',2,1,1,NULL,'2025-12-01','2025-12-22','in_stock'),('SI-B1-0014','B1-VEG-002',2,1,1,NULL,'2025-12-01','2025-12-22','in_stock'),('SI-B1-0015','B1-VEG-002',2,1,1,NULL,'2025-12-01','2025-12-22','in_stock'),('SI-B1-0016','B1-VEG-002',2,1,1,NULL,'2025-12-01','2025-12-22','in_stock'),('SI-B1-0017','B1-VEG-002',2,1,1,NULL,'2025-12-01','2025-12-22','in_stock'),('SI-B1-0018','B1-VEG-002',2,1,1,NULL,'2025-12-01','2025-12-22','in_stock'),('SI-B1-0019','B1-VEG-002',2,1,1,NULL,'2025-12-01','2025-12-22','in_stock'),('SI-B1-0020','B1-VEG-002',2,1,1,NULL,'2025-12-01','2025-12-22','in_stock'),('SI-B1-0021','B1-FRU-001',4,1,2,1,'2025-12-02','2025-12-18','sold'),('SI-B1-0022','B1-FRU-001',4,1,2,NULL,'2025-12-02','2025-12-18','in_stock'),('SI-B1-0023','B1-FRU-001',4,1,2,NULL,'2025-12-02','2025-12-18','in_stock'),('SI-B1-0024','B1-FRU-001',4,1,2,NULL,'2025-12-02','2025-12-18','in_stock'),('SI-B1-0025','B1-FRU-001',4,1,2,NULL,'2025-12-02','2025-12-18','in_stock'),('SI-B1-0026','B1-FRU-001',4,1,2,NULL,'2025-12-02','2025-12-18','in_stock'),('SI-B1-0027','B1-MEAT-001',7,1,3,1,'2025-12-03','2025-12-25','sold'),('SI-B1-0028','B1-MEAT-001',7,1,3,NULL,'2025-12-03','2025-12-25','in_stock'),('SI-B1-0029','B1-MEAT-001',7,1,3,NULL,'2025-12-03','2025-12-25','in_stock'),('SI-B1-0030','B1-MEAT-001',7,1,3,NULL,'2025-12-03','2025-12-25','in_stock'),('SI-B1-0031','B1-MEAT-001',7,1,3,NULL,'2025-12-03','2025-12-25','in_stock'),('SI-B1-0032','B1-MEAT-001',7,1,3,NULL,'2025-12-03','2025-12-25','in_stock'),('SI-B1-0033','B1-EGG-001',10,1,4,2,'2025-12-10','2025-12-30','sold'),('SI-B1-0034','B1-EGG-001',10,1,4,NULL,'2025-12-10','2025-12-30','in_stock'),('SI-B1-0035','B1-EGG-001',10,1,4,NULL,'2025-12-10','2025-12-30','in_stock'),('SI-B1-0036','B1-EGG-001',10,1,4,NULL,'2025-12-10','2025-12-30','in_stock'),('SI-B1-0037','B1-FISH-001',11,1,5,NULL,'2025-12-11','2025-12-22','in_stock'),('SI-B1-0038','B1-FISH-001',11,1,5,NULL,'2025-12-11','2025-12-22','in_stock'),('SI-B1-0039','B1-FISH-001',11,1,5,NULL,'2025-12-11','2025-12-22','in_stock'),('SI-B1-0040','B1-FISH-001',11,1,5,NULL,'2025-12-11','2025-12-22','in_stock'),('SI-B2-0001','B2-VEG-001',1,2,7,NULL,'2025-12-01','2025-12-20','in_stock'),('SI-B2-0002','B2-FRU-001',5,2,8,NULL,'2025-12-02','2026-01-10','in_stock'),('SI-B2-0003','B2-MEAT-001',8,2,9,NULL,'2025-12-03','2025-12-28','in_stock'),('SI-B2-0004','B2-EGG-001',10,2,10,NULL,'2025-12-10','2025-12-30','in_stock'),('SI-B2-0005','B2-FISH-001',11,2,11,NULL,'2025-12-11','2025-12-22','in_stock'),('SI-B2-0006','B2-SEA-001',13,2,12,NULL,'2025-12-12','2026-01-20','in_stock'),('SI-B3-0001','B3-VEG-001',3,3,13,NULL,'2025-12-01','2026-02-10','in_stock'),('SI-B3-0002','B3-FRU-001',6,3,14,4,'2025-12-02','2026-01-10','sold'),('SI-B3-0003','B3-MEAT-001',9,3,15,NULL,'2025-12-03','2025-12-26','in_stock'),('SI-B3-0004','B3-EGG-001',10,3,16,NULL,'2025-12-10','2025-12-30','in_stock'),('SI-B3-0005','B3-FISH-001',11,3,17,NULL,'2025-12-11','2025-12-22','in_stock'),('SI-B3-0006','B3-SEA-001',12,3,18,4,'2025-12-12','2026-01-30','sold');
/*!40000 ALTER TABLE `StockItem` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `StockItemCertificate`
--

DROP TABLE IF EXISTS `StockItemCertificate`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `StockItemCertificate` (
  `certificate_ID` int NOT NULL AUTO_INCREMENT,
  `item_ID` varchar(50) DEFAULT NULL,
  `transaction_type` enum('purchase','sale','return','transfer','adjustment') NOT NULL,
  `date` datetime DEFAULT CURRENT_TIMESTAMP,
  `transaction_ID` int DEFAULT NULL,
  PRIMARY KEY (`certificate_ID`),
  KEY `fk_Stock_item_ID` (`item_ID`),
  CONSTRAINT `fk_Stock_item_ID` FOREIGN KEY (`item_ID`) REFERENCES `StockItem` (`item_ID`)
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb3;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `StockItemCertificate`
--

LOCK TABLES `StockItemCertificate` WRITE;
/*!40000 ALTER TABLE `StockItemCertificate` DISABLE KEYS */;
INSERT INTO `StockItemCertificate` VALUES (1,'SI-B1-0001','sale','2025-12-15 10:10:00',1),(2,'SI-B1-0021','sale','2025-12-15 10:10:00',1),(3,'SI-B1-0027','sale','2025-12-15 10:10:00',1),(4,'SI-B3-0002','sale','2025-12-16 12:30:00',4),(5,'SI-B3-0006','sale','2025-12-16 12:30:00',4);
/*!40000 ALTER TABLE `StockItemCertificate` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `Supplier`
--

DROP TABLE IF EXISTS `Supplier`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `Supplier` (
  `supplier_ID` int NOT NULL AUTO_INCREMENT,
  `user_name` varchar(45) DEFAULT NULL,
  `company_name` varchar(100) NOT NULL,
  `contact_person` varchar(100) DEFAULT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `email` varchar(100) DEFAULT NULL,
  `address` varchar(255) DEFAULT NULL,
  `tax_number` varchar(50) DEFAULT NULL,
  `status` enum('active','inactive') DEFAULT 'active',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`supplier_ID`),
  UNIQUE KEY `tax_number` (`tax_number`),
  KEY `fk_user_name` (`user_name`),
  CONSTRAINT `fk_user_name` FOREIGN KEY (`user_name`) REFERENCES `User` (`user_name`)
) ENGINE=InnoDB AUTO_INCREMENT=10 DEFAULT CHARSET=utf8mb3;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `Supplier`
--

LOCK TABLES `Supplier` WRITE;
/*!40000 ALTER TABLE `Supplier` DISABLE KEYS */;
INSERT INTO `Supplier` VALUES (1,'sup_b1_veg','中南店-果蔬供应商','周供应','13600001001','sup_b1_veg@fh.com','长沙市望城区XX基地','TAX-B1-VEG','active','2025-12-17 07:30:03'),(2,'sup_b1_meat','中南店-肉禽蛋供应商','王海鲜','13600001002','sup_b1_meat@fh.com','长沙市雨花区XX冷链仓','TAX-B1-MEAT','active','2025-12-17 07:30:03'),(3,'sup_b1_sea','中南店-水产供应商','李集采','13600001003','sup_b1_sea@fh.com','长沙市开福区XX仓储中心','TAX-B1-SEA','active','2025-12-17 07:30:03'),(4,'sup_b2_veg','麓谷店-果蔬供应商','周供应','13600002001','sup_b2_veg@fh.com','长沙市望城区XX基地','TAX-B2-VEG','active','2025-12-17 07:30:03'),(5,'sup_b2_meat','麓谷店-肉禽蛋供应商','王海鲜','13600002002','sup_b2_meat@fh.com','长沙市雨花区XX冷链仓','TAX-B2-MEAT','active','2025-12-17 07:30:03'),(6,'sup_b2_sea','麓谷店-水产供应商','李集采','13600002003','sup_b2_sea@fh.com','长沙市开福区XX仓储中心','TAX-B2-SEA','active','2025-12-17 07:30:03'),(7,'sup_b3_veg','梅溪湖店-果蔬供应商','周供应','13600003001','sup_b3_veg@fh.com','长沙市望城区XX基地','TAX-B3-VEG','active','2025-12-17 07:30:03'),(8,'sup_b3_meat','梅溪湖店-肉禽蛋供应商','王海鲜','13600003002','sup_b3_meat@fh.com','长沙市雨花区XX冷链仓','TAX-B3-MEAT','active','2025-12-17 07:30:03'),(9,'sup_b3_sea','梅溪湖店-水产供应商','李集采','13600003003','sup_b3_sea@fh.com','长沙市开福区XX仓储中心','TAX-B3-SEA','active','2025-12-17 07:30:03');
/*!40000 ALTER TABLE `Supplier` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `User`
--

DROP TABLE IF EXISTS `User`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `User` (
  `user_ID` int NOT NULL AUTO_INCREMENT,
  `user_name` varchar(45) NOT NULL,
  `password_hash` varchar(255) NOT NULL,
  `user_type` enum('customer','staff','supplier','CEO') NOT NULL,
  `user_email` varchar(45) NOT NULL,
  `user_telephone` varchar(20) DEFAULT NULL,
  `first_name` varchar(45) NOT NULL,
  `last_name` varchar(45) NOT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `last_login` datetime DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT '1',
  PRIMARY KEY (`user_ID`),
  UNIQUE KEY `user_name` (`user_name`),
  UNIQUE KEY `user_email` (`user_email`)
) ENGINE=InnoDB AUTO_INCREMENT=210 DEFAULT CHARSET=utf8mb3;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `User`
--

LOCK TABLES `User` WRITE;
/*!40000 ALTER TABLE `User` DISABLE KEYS */;
INSERT INTO `User` VALUES (1,'b1_mgr','hash','staff','b1_mgr@fh.com','13900001001','店长','中南','2025-12-17 07:30:03',NULL,1),(2,'b1_s1','hash','staff','b1_s1@fh.com','13900001002','销售','中南1','2025-12-17 07:30:03',NULL,1),(3,'b1_s2','hash','staff','b1_s2@fh.com','13900001003','销售','中南2','2025-12-17 07:30:03',NULL,1),(4,'b1_s3','hash','staff','b1_s3@fh.com','13900001004','销售','中南3','2025-12-17 07:30:03',NULL,1),(5,'b1_d1','hash','staff','b1_d1@fh.com','13900001005','配送','中南','2025-12-17 07:30:03',NULL,1),(6,'b2_mgr','hash','staff','b2_mgr@fh.com','13900002001','店长','麓谷','2025-12-17 07:30:03',NULL,1),(7,'b2_s1','hash','staff','b2_s1@fh.com','13900002002','销售','麓谷1','2025-12-17 07:30:03',NULL,1),(8,'b2_s2','hash','staff','b2_s2@fh.com','13900002003','销售','麓谷2','2025-12-17 07:30:03',NULL,1),(9,'b2_s3','hash','staff','b2_s3@fh.com','13900002004','销售','麓谷3','2025-12-17 07:30:03',NULL,1),(10,'b2_d1','hash','staff','b2_d1@fh.com','13900002005','配送','麓谷','2025-12-17 07:30:03',NULL,1),(11,'b3_mgr','hash','staff','b3_mgr@fh.com','13900003001','店长','梅溪湖','2025-12-17 07:30:03',NULL,1),(12,'b3_s1','hash','staff','b3_s1@fh.com','13900003002','销售','梅溪湖1','2025-12-17 07:30:03',NULL,1),(13,'b3_s2','hash','staff','b3_s2@fh.com','13900003003','销售','梅溪湖2','2025-12-17 07:30:03',NULL,1),(14,'b3_s3','hash','staff','b3_s3@fh.com','13900003004','销售','梅溪湖3','2025-12-17 07:30:03',NULL,1),(15,'b3_d1','hash','staff','b3_d1@fh.com','13900003005','配送','梅溪湖','2025-12-17 07:30:03',NULL,1),(101,'c1','hash','customer','c1@test.com','13800000001','C','One','2025-12-17 07:30:03',NULL,1),(102,'c2','hash','customer','c2@test.com','13800000002','C','Two','2025-12-17 07:30:03',NULL,1),(103,'c3','hash','customer','c3@test.com','13800000003','C','Three','2025-12-17 07:30:03',NULL,1),(104,'c4','hash','customer','c4@test.com','13800000004','C','Four','2025-12-17 07:30:03',NULL,1),(105,'c5','hash','customer','c5@test.com','13800000005','C','Five','2025-12-17 07:30:03',NULL,1),(201,'sup_b1_veg','hash','supplier','sup_b1_veg@fh.com','13600001001','湘菜源','中南果蔬','2025-12-17 07:30:03',NULL,1),(202,'sup_b1_meat','hash','supplier','sup_b1_meat@fh.com','13600001002','海鲜冷链','中南肉蛋','2025-12-17 07:30:03',NULL,1),(203,'sup_b1_sea','hash','supplier','sup_b1_sea@fh.com','13600001003','综合供应','中南水产','2025-12-17 07:30:03',NULL,1),(204,'sup_b2_veg','hash','supplier','sup_b2_veg@fh.com','13600002001','湘菜源','麓谷果蔬','2025-12-17 07:30:03',NULL,1),(205,'sup_b2_meat','hash','supplier','sup_b2_meat@fh.com','13600002002','海鲜冷链','麓谷肉蛋','2025-12-17 07:30:03',NULL,1),(206,'sup_b2_sea','hash','supplier','sup_b2_sea@fh.com','13600002003','综合供应','麓谷水产','2025-12-17 07:30:03',NULL,1),(207,'sup_b3_veg','hash','supplier','sup_b3_veg@fh.com','13600003001','湘菜源','梅溪湖果蔬','2025-12-17 07:30:03',NULL,1),(208,'sup_b3_meat','hash','supplier','sup_b3_meat@fh.com','13600003002','海鲜冷链','梅溪湖肉蛋','2025-12-17 07:30:03',NULL,1),(209,'sup_b3_sea','hash','supplier','sup_b3_sea@fh.com','13600003003','综合供应','梅溪湖水产','2025-12-17 07:30:03',NULL,1);
/*!40000 ALTER TABLE `User` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Temporary view structure for view `v_alerts_monitoring`
--

DROP TABLE IF EXISTS `v_alerts_monitoring`;
/*!50001 DROP VIEW IF EXISTS `v_alerts_monitoring`*/;
SET @saved_cs_client     = @@character_set_client;
/*!50503 SET character_set_client = utf8mb4 */;
/*!50001 CREATE VIEW `v_alerts_monitoring` AS SELECT 
 1 AS `alert_type`,
 1 AS `product_ID`,
 1 AS `product_name`,
 1 AS `batch_ID`,
 1 AS `days_to_expire`,
 1 AS `severity`,
 1 AS `description`,
 1 AS `suggested_action`*/;
SET character_set_client = @saved_cs_client;

--
-- Temporary view structure for view `v_audit_trail`
--

DROP TABLE IF EXISTS `v_audit_trail`;
/*!50001 DROP VIEW IF EXISTS `v_audit_trail`*/;
SET @saved_cs_client     = @@character_set_client;
/*!50503 SET character_set_client = utf8mb4 */;
/*!50001 CREATE VIEW `v_audit_trail` AS SELECT 
 1 AS `certificate_ID`,
 1 AS `item_ID`,
 1 AS `transaction_type`,
 1 AS `transaction_date`,
 1 AS `transaction_ID`,
 1 AS `product_ID`,
 1 AS `product_name`,
 1 AS `item_status`*/;
SET character_set_client = @saved_cs_client;

--
-- Temporary view structure for view `v_branch_comparison`
--

DROP TABLE IF EXISTS `v_branch_comparison`;
/*!50001 DROP VIEW IF EXISTS `v_branch_comparison`*/;
SET @saved_cs_client     = @@character_set_client;
/*!50503 SET character_set_client = utf8mb4 */;
/*!50001 CREATE VIEW `v_branch_comparison` AS SELECT 
 1 AS `branch_name`,
 1 AS `period`,
 1 AS `total_orders`,
 1 AS `total_sales`,
 1 AS `unique_customers`*/;
SET character_set_client = @saved_cs_client;

--
-- Temporary view structure for view `v_ceo_dashboard`
--

DROP TABLE IF EXISTS `v_ceo_dashboard`;
/*!50001 DROP VIEW IF EXISTS `v_ceo_dashboard`*/;
SET @saved_cs_client     = @@character_set_client;
/*!50503 SET character_set_client = utf8mb4 */;
/*!50001 CREATE VIEW `v_ceo_dashboard` AS SELECT 
 1 AS `report_date`,
 1 AS `period_type`,
 1 AS `total_sales`,
 1 AS `active_customers`,
 1 AS `employee_count`,
 1 AS `total_inventory_value`*/;
SET character_set_client = @saved_cs_client;

--
-- Temporary view structure for view `v_certificate_tracking`
--

DROP TABLE IF EXISTS `v_certificate_tracking`;
/*!50001 DROP VIEW IF EXISTS `v_certificate_tracking`*/;
SET @saved_cs_client     = @@character_set_client;
/*!50503 SET character_set_client = utf8mb4 */;
/*!50001 CREATE VIEW `v_certificate_tracking` AS SELECT 
 1 AS `certificate_ID`,
 1 AS `batch_ID`,
 1 AS `product_name`,
 1 AS `transaction_type`,
 1 AS `date`,
 1 AS `received_date`,
 1 AS `expiry_date`,
 1 AS `item_status`*/;
SET character_set_client = @saved_cs_client;

--
-- Temporary view structure for view `v_customer_interaction`
--

DROP TABLE IF EXISTS `v_customer_interaction`;
/*!50001 DROP VIEW IF EXISTS `v_customer_interaction`*/;
SET @saved_cs_client     = @@character_set_client;
/*!50503 SET character_set_client = utf8mb4 */;
/*!50001 CREATE VIEW `v_customer_interaction` AS SELECT 
 1 AS `branch_ID`,
 1 AS `customer_ID`,
 1 AS `first_name`,
 1 AS `last_name`,
 1 AS `loyalty_level`,
 1 AS `phone`,
 1 AS `email`,
 1 AS `total_orders`,
 1 AS `total_spent`,
 1 AS `last_purchase`*/;
SET character_set_client = @saved_cs_client;

--
-- Temporary view structure for view `v_customer_product_info`
--

DROP TABLE IF EXISTS `v_customer_product_info`;
/*!50001 DROP VIEW IF EXISTS `v_customer_product_info`*/;
SET @saved_cs_client     = @@character_set_client;
/*!50503 SET character_set_client = utf8mb4 */;
/*!50001 CREATE VIEW `v_customer_product_info` AS SELECT 
 1 AS `product_ID`,
 1 AS `product_name`,
 1 AS `category_name`,
 1 AS `unit_price`,
 1 AS `unit`,
 1 AS `description`,
 1 AS `product_status`,
 1 AS `attributes`*/;
SET character_set_client = @saved_cs_client;

--
-- Temporary view structure for view `v_customer_profile`
--

DROP TABLE IF EXISTS `v_customer_profile`;
/*!50001 DROP VIEW IF EXISTS `v_customer_profile`*/;
SET @saved_cs_client     = @@character_set_client;
/*!50503 SET character_set_client = utf8mb4 */;
/*!50001 CREATE VIEW `v_customer_profile` AS SELECT 
 1 AS `customer_ID`,
 1 AS `first_name`,
 1 AS `last_name`,
 1 AS `phone`,
 1 AS `email`,
 1 AS `loyalty_level`,
 1 AS `last_shipping_address`,
 1 AS `total_orders`*/;
SET character_set_client = @saved_cs_client;

--
-- Temporary view structure for view `v_expiry_alerts`
--

DROP TABLE IF EXISTS `v_expiry_alerts`;
/*!50001 DROP VIEW IF EXISTS `v_expiry_alerts`*/;
SET @saved_cs_client     = @@character_set_client;
/*!50503 SET character_set_client = utf8mb4 */;
/*!50001 CREATE VIEW `v_expiry_alerts` AS SELECT 
 1 AS `product_name`,
 1 AS `batch_ID`,
 1 AS `date_expired`,
 1 AS `remaining_days`,
 1 AS `quantity_on_hand`,
 1 AS `suggested_action`*/;
SET character_set_client = @saved_cs_client;

--
-- Temporary view structure for view `v_financial_overview`
--

DROP TABLE IF EXISTS `v_financial_overview`;
/*!50001 DROP VIEW IF EXISTS `v_financial_overview`*/;
SET @saved_cs_client     = @@character_set_client;
/*!50503 SET character_set_client = utf8mb4 */;
/*!50001 CREATE VIEW `v_financial_overview` AS SELECT 
 1 AS `date_day`,
 1 AS `branch_name`,
 1 AS `revenue`,
 1 AS `order_count`*/;
SET character_set_client = @saved_cs_client;

--
-- Temporary view structure for view `v_inventory_management`
--

DROP TABLE IF EXISTS `v_inventory_management`;
/*!50001 DROP VIEW IF EXISTS `v_inventory_management`*/;
SET @saved_cs_client     = @@character_set_client;
/*!50503 SET character_set_client = utf8mb4 */;
/*!50001 CREATE VIEW `v_inventory_management` AS SELECT 
 1 AS `branch_ID`,
 1 AS `product_ID`,
 1 AS `product_name`,
 1 AS `sku`,
 1 AS `unit`,
 1 AS `current_stock`,
 1 AS `batch_ID`,
 1 AS `received_date`,
 1 AS `date_expired`*/;
SET character_set_client = @saved_cs_client;

--
-- Temporary view structure for view `v_low_stock_alerts`
--

DROP TABLE IF EXISTS `v_low_stock_alerts`;
/*!50001 DROP VIEW IF EXISTS `v_low_stock_alerts`*/;
SET @saved_cs_client     = @@character_set_client;
/*!50503 SET character_set_client = utf8mb4 */;
/*!50001 CREATE VIEW `v_low_stock_alerts` AS SELECT 
 1 AS `product_name`,
 1 AS `sku`,
 1 AS `batch_ID`,
 1 AS `current_stock`,
 1 AS `avg_cost`,
 1 AS `suppliers`*/;
SET character_set_client = @saved_cs_client;

--
-- Temporary view structure for view `v_order_history`
--

DROP TABLE IF EXISTS `v_order_history`;
/*!50001 DROP VIEW IF EXISTS `v_order_history`*/;
SET @saved_cs_client     = @@character_set_client;
/*!50503 SET character_set_client = utf8mb4 */;
/*!50001 CREATE VIEW `v_order_history` AS SELECT 
 1 AS `customer_ID`,
 1 AS `order_ID`,
 1 AS `order_date`,
 1 AS `total_amount`,
 1 AS `order_status`,
 1 AS `shipping_address`,
 1 AS `item_count`*/;
SET character_set_client = @saved_cs_client;

--
-- Temporary view structure for view `v_product_information`
--

DROP TABLE IF EXISTS `v_product_information`;
/*!50001 DROP VIEW IF EXISTS `v_product_information`*/;
SET @saved_cs_client     = @@character_set_client;
/*!50503 SET character_set_client = utf8mb4 */;
/*!50001 CREATE VIEW `v_product_information` AS SELECT 
 1 AS `product_ID`,
 1 AS `product_name`,
 1 AS `sku`,
 1 AS `category_name`,
 1 AS `unit`,
 1 AS `attributes`,
 1 AS `selling_price`,
 1 AS `avg_cost`,
 1 AS `suppliers`,
 1 AS `total_inventory`*/;
SET character_set_client = @saved_cs_client;

--
-- Temporary view structure for view `v_purchasing_management`
--

DROP TABLE IF EXISTS `v_purchasing_management`;
/*!50001 DROP VIEW IF EXISTS `v_purchasing_management`*/;
SET @saved_cs_client     = @@character_set_client;
/*!50503 SET character_set_client = utf8mb4 */;
/*!50001 CREATE VIEW `v_purchasing_management` AS SELECT 
 1 AS `purchase_order_ID`,
 1 AS `supplier`,
 1 AS `branch_ID`,
 1 AS `date`,
 1 AS `status`,
 1 AS `total_amount`,
 1 AS `item_count`*/;
SET character_set_client = @saved_cs_client;

--
-- Temporary view structure for view `v_sales_dashboard`
--

DROP TABLE IF EXISTS `v_sales_dashboard`;
/*!50001 DROP VIEW IF EXISTS `v_sales_dashboard`*/;
SET @saved_cs_client     = @@character_set_client;
/*!50503 SET character_set_client = utf8mb4 */;
/*!50001 CREATE VIEW `v_sales_dashboard` AS SELECT 
 1 AS `branch_ID`,
 1 AS `branch_name`,
 1 AS `today`,
 1 AS `today_orders`,
 1 AS `today_sales`,
 1 AS `month_orders`*/;
SET character_set_client = @saved_cs_client;

--
-- Temporary view structure for view `v_staff_employees`
--

DROP TABLE IF EXISTS `v_staff_employees`;
/*!50001 DROP VIEW IF EXISTS `v_staff_employees`*/;
SET @saved_cs_client     = @@character_set_client;
/*!50503 SET character_set_client = utf8mb4 */;
/*!50001 CREATE VIEW `v_staff_employees` AS SELECT 
 1 AS `branch_ID`,
 1 AS `branch_name`,
 1 AS `staff_ID`,
 1 AS `user_name`,
 1 AS `first_name`,
 1 AS `last_name`,
 1 AS `user_email`,
 1 AS `user_telephone`,
 1 AS `staff_phone`,
 1 AS `staff_position`,
 1 AS `hire_date`,
 1 AS `staff_status`*/;
SET character_set_client = @saved_cs_client;

--
-- Temporary view structure for view `v_staff_information`
--

DROP TABLE IF EXISTS `v_staff_information`;
/*!50001 DROP VIEW IF EXISTS `v_staff_information`*/;
SET @saved_cs_client     = @@character_set_client;
/*!50503 SET character_set_client = utf8mb4 */;
/*!50001 CREATE VIEW `v_staff_information` AS SELECT 
 1 AS `branch_name`,
 1 AS `first_name`,
 1 AS `last_name`,
 1 AS `position`,
 1 AS `phone`,
 1 AS `email`,
 1 AS `salary`,
 1 AS `hire_date`,
 1 AS `status`*/;
SET character_set_client = @saved_cs_client;

--
-- Temporary view structure for view `v_staff_inventory`
--

DROP TABLE IF EXISTS `v_staff_inventory`;
/*!50001 DROP VIEW IF EXISTS `v_staff_inventory`*/;
SET @saved_cs_client     = @@character_set_client;
/*!50503 SET character_set_client = utf8mb4 */;
/*!50001 CREATE VIEW `v_staff_inventory` AS SELECT 
 1 AS `branch_ID`,
 1 AS `branch_name`,
 1 AS `batch_ID`,
 1 AS `product_ID`,
 1 AS `sku`,
 1 AS `product_name`,
 1 AS `unit`,
 1 AS `category_name`,
 1 AS `quantity_received`,
 1 AS `quantity_on_hand`,
 1 AS `unit_cost`,
 1 AS `inventory_value`,
 1 AS `received_date`,
 1 AS `date_produced`,
 1 AS `date_expired`,
 1 AS `low_stock_severity`,
 1 AS `days_to_expire`,
 1 AS `is_expiring_soon`*/;
SET character_set_client = @saved_cs_client;

--
-- Temporary view structure for view `v_staff_order_items`
--

DROP TABLE IF EXISTS `v_staff_order_items`;
/*!50001 DROP VIEW IF EXISTS `v_staff_order_items`*/;
SET @saved_cs_client     = @@character_set_client;
/*!50503 SET character_set_client = utf8mb4 */;
/*!50001 CREATE VIEW `v_staff_order_items` AS SELECT 
 1 AS `branch_ID`,
 1 AS `branch_name`,
 1 AS `order_ID`,
 1 AS `order_date`,
 1 AS `customer_ID`,
 1 AS `item_ID`,
 1 AS `product_ID`,
 1 AS `sku`,
 1 AS `product_name`,
 1 AS `unit`,
 1 AS `unit_price`,
 1 AS `quantity`,
 1 AS `line_amount`,
 1 AS `batch_ID`,
 1 AS `stock_item_status`*/;
SET character_set_client = @saved_cs_client;

--
-- Temporary view structure for view `v_staff_orders`
--

DROP TABLE IF EXISTS `v_staff_orders`;
/*!50001 DROP VIEW IF EXISTS `v_staff_orders`*/;
SET @saved_cs_client     = @@character_set_client;
/*!50503 SET character_set_client = utf8mb4 */;
/*!50001 CREATE VIEW `v_staff_orders` AS SELECT 
 1 AS `branch_ID`,
 1 AS `branch_name`,
 1 AS `order_ID`,
 1 AS `order_date`,
 1 AS `customer_ID`,
 1 AS `total_amount`,
 1 AS `order_status`,
 1 AS `shipping_address`*/;
SET character_set_client = @saved_cs_client;

--
-- Temporary view structure for view `v_staff_profile`
--

DROP TABLE IF EXISTS `v_staff_profile`;
/*!50001 DROP VIEW IF EXISTS `v_staff_profile`*/;
SET @saved_cs_client     = @@character_set_client;
/*!50503 SET character_set_client = utf8mb4 */;
/*!50001 CREATE VIEW `v_staff_profile` AS SELECT 
 1 AS `staff_ID`,
 1 AS `user_name`,
 1 AS `first_name`,
 1 AS `last_name`,
 1 AS `user_email`,
 1 AS `user_telephone`,
 1 AS `staff_phone`,
 1 AS `staff_position`,
 1 AS `salary`,
 1 AS `hire_date`,
 1 AS `staff_status`,
 1 AS `branch_ID`,
 1 AS `branch_name`,
 1 AS `branch_address`,
 1 AS `branch_phone`,
 1 AS `branch_email`*/;
SET character_set_client = @saved_cs_client;

--
-- Temporary view structure for view `v_staff_purchase_items`
--

DROP TABLE IF EXISTS `v_staff_purchase_items`;
/*!50001 DROP VIEW IF EXISTS `v_staff_purchase_items`*/;
SET @saved_cs_client     = @@character_set_client;
/*!50503 SET character_set_client = utf8mb4 */;
/*!50001 CREATE VIEW `v_staff_purchase_items` AS SELECT 
 1 AS `branch_ID`,
 1 AS `branch_name`,
 1 AS `purchase_order_ID`,
 1 AS `purchase_date`,
 1 AS `purchase_status`,
 1 AS `supplier_ID`,
 1 AS `supplier_company`,
 1 AS `supply_id`,
 1 AS `item_ID`,
 1 AS `product_ID`,
 1 AS `sku`,
 1 AS `product_name`,
 1 AS `unit`,
 1 AS `unit_cost`,
 1 AS `received_date`,
 1 AS `batch_ID`,
 1 AS `stock_item_status`,
 1 AS `expiry_date`*/;
SET character_set_client = @saved_cs_client;

--
-- Temporary view structure for view `v_staff_purchase_orders`
--

DROP TABLE IF EXISTS `v_staff_purchase_orders`;
/*!50001 DROP VIEW IF EXISTS `v_staff_purchase_orders`*/;
SET @saved_cs_client     = @@character_set_client;
/*!50503 SET character_set_client = utf8mb4 */;
/*!50001 CREATE VIEW `v_staff_purchase_orders` AS SELECT 
 1 AS `branch_ID`,
 1 AS `branch_name`,
 1 AS `purchase_order_ID`,
 1 AS `date`,
 1 AS `purchase_status`,
 1 AS `total_amount`,
 1 AS `supplier_ID`,
 1 AS `supplier_company`,
 1 AS `contact_person`,
 1 AS `supplier_phone`,
 1 AS `supplier_email`,
 1 AS `item_count`*/;
SET character_set_client = @saved_cs_client;

--
-- Temporary view structure for view `v_staff_stock_quality`
--

DROP TABLE IF EXISTS `v_staff_stock_quality`;
/*!50001 DROP VIEW IF EXISTS `v_staff_stock_quality`*/;
SET @saved_cs_client     = @@character_set_client;
/*!50503 SET character_set_client = utf8mb4 */;
/*!50001 CREATE VIEW `v_staff_stock_quality` AS SELECT 
 1 AS `branch_ID`,
 1 AS `branch_name`,
 1 AS `item_ID`,
 1 AS `batch_ID`,
 1 AS `product_ID`,
 1 AS `sku`,
 1 AS `product_name`,
 1 AS `unit`,
 1 AS `received_date`,
 1 AS `expiry_date`,
 1 AS `stock_item_status`,
 1 AS `quality_flag`,
 1 AS `suggested_action`*/;
SET character_set_client = @saved_cs_client;

--
-- Temporary view structure for view `v_stock_movement`
--

DROP TABLE IF EXISTS `v_stock_movement`;
/*!50001 DROP VIEW IF EXISTS `v_stock_movement`*/;
SET @saved_cs_client     = @@character_set_client;
/*!50503 SET character_set_client = utf8mb4 */;
/*!50001 CREATE VIEW `v_stock_movement` AS SELECT 
 1 AS `branch_ID`,
 1 AS `product_name`,
 1 AS `batch_ID`,
 1 AS `received_date`,
 1 AS `expiry_date`,
 1 AS `item_status`*/;
SET character_set_client = @saved_cs_client;

--
-- Temporary view structure for view `v_supplier_information`
--

DROP TABLE IF EXISTS `v_supplier_information`;
/*!50001 DROP VIEW IF EXISTS `v_supplier_information`*/;
SET @saved_cs_client     = @@character_set_client;
/*!50503 SET character_set_client = utf8mb4 */;
/*!50001 CREATE VIEW `v_supplier_information` AS SELECT 
 1 AS `supplier_ID`,
 1 AS `company_name`,
 1 AS `contact_person`,
 1 AS `phone`,
 1 AS `email`,
 1 AS `tax_number`,
 1 AS `status`,
 1 AS `total_orders`,
 1 AS `total_amount`*/;
SET character_set_client = @saved_cs_client;

--
-- Temporary view structure for view `v_supplier_relations`
--

DROP TABLE IF EXISTS `v_supplier_relations`;
/*!50001 DROP VIEW IF EXISTS `v_supplier_relations`*/;
SET @saved_cs_client     = @@character_set_client;
/*!50503 SET character_set_client = utf8mb4 */;
/*!50001 CREATE VIEW `v_supplier_relations` AS SELECT 
 1 AS `supplier_ID`,
 1 AS `company_name`,
 1 AS `contact_person`,
 1 AS `phone`,
 1 AS `email`,
 1 AS `address`,
 1 AS `status`*/;
SET character_set_client = @saved_cs_client;

--
-- Temporary view structure for view `v_wishlist_products`
--

DROP TABLE IF EXISTS `v_wishlist_products`;
/*!50001 DROP VIEW IF EXISTS `v_wishlist_products`*/;
SET @saved_cs_client     = @@character_set_client;
/*!50503 SET character_set_client = utf8mb4 */;
/*!50001 CREATE VIEW `v_wishlist_products` AS SELECT 
 1 AS `customer_ID`,
 1 AS `order_ID`,
 1 AS `product_ID`,
 1 AS `product_name`,
 1 AS `sku`,
 1 AS `category_name`,
 1 AS `unit_price`,
 1 AS `unit`,
 1 AS `description`,
 1 AS `quantity`,
 1 AS `available_stock`*/;
SET character_set_client = @saved_cs_client;

--
-- Final view structure for view `v_alerts_monitoring`
--

/*!50001 DROP VIEW IF EXISTS `v_alerts_monitoring`*/;
/*!50001 SET @saved_cs_client          = @@character_set_client */;
/*!50001 SET @saved_cs_results         = @@character_set_results */;
/*!50001 SET @saved_col_connection     = @@collation_connection */;
/*!50001 SET character_set_client      = utf8mb4 */;
/*!50001 SET character_set_results     = utf8mb4 */;
/*!50001 SET collation_connection      = utf8mb4_0900_ai_ci */;
/*!50001 CREATE ALGORITHM=UNDEFINED */
/*!50013 DEFINER=`root`@`localhost` SQL SECURITY DEFINER */
/*!50001 VIEW `v_alerts_monitoring` AS select 'expiry' AS `alert_type`,`p`.`product_ID` AS `product_ID`,`p`.`product_name` AS `product_name`,`i`.`batch_ID` AS `batch_ID`,(to_days(`i`.`date_expired`) - to_days(curdate())) AS `days_to_expire`,'高' AS `severity`,concat('产品即将在',(to_days(`i`.`date_expired`) - to_days(curdate())),'天后过期') AS `description`,'尽快促销或处理' AS `suggested_action` from (`inventory` `i` join `products` `p` on((`i`.`product_ID` = `p`.`product_ID`))) where ((`i`.`date_expired` is not null) and ((to_days(`i`.`date_expired`) - to_days(curdate())) between 0 and 30)) union all select 'low_stock' AS `alert_type`,`p`.`product_ID` AS `product_ID`,`p`.`product_name` AS `product_name`,`i`.`batch_ID` AS `batch_ID`,`i`.`quantity_on_hand` AS `days_to_expire`,(case when (`i`.`quantity_on_hand` <= 5) then '高' when (`i`.`quantity_on_hand` <= 10) then '中' else '低' end) AS `severity`,concat('库存仅剩',`i`.`quantity_on_hand`,'件') AS `description`,'建议补货' AS `suggested_action` from (`inventory` `i` join `products` `p` on((`i`.`product_ID` = `p`.`product_ID`))) where (`i`.`quantity_on_hand` <= 10) */;
/*!50001 SET character_set_client      = @saved_cs_client */;
/*!50001 SET character_set_results     = @saved_cs_results */;
/*!50001 SET collation_connection      = @saved_col_connection */;

--
-- Final view structure for view `v_audit_trail`
--

/*!50001 DROP VIEW IF EXISTS `v_audit_trail`*/;
/*!50001 SET @saved_cs_client          = @@character_set_client */;
/*!50001 SET @saved_cs_results         = @@character_set_results */;
/*!50001 SET @saved_col_connection     = @@collation_connection */;
/*!50001 SET character_set_client      = utf8mb4 */;
/*!50001 SET character_set_results     = utf8mb4 */;
/*!50001 SET collation_connection      = utf8mb4_0900_ai_ci */;
/*!50001 CREATE ALGORITHM=UNDEFINED */
/*!50013 DEFINER=`root`@`localhost` SQL SECURITY DEFINER */
/*!50001 VIEW `v_audit_trail` AS select `sc`.`certificate_ID` AS `certificate_ID`,`sc`.`item_ID` AS `item_ID`,`sc`.`transaction_type` AS `transaction_type`,`sc`.`date` AS `transaction_date`,`sc`.`transaction_ID` AS `transaction_ID`,`si`.`product_ID` AS `product_ID`,`p`.`product_name` AS `product_name`,`si`.`status` AS `item_status` from ((`stockitemcertificate` `sc` left join `stockitem` `si` on((`sc`.`item_ID` = `si`.`item_ID`))) left join `products` `p` on((`si`.`product_ID` = `p`.`product_ID`))) order by `sc`.`date` desc */;
/*!50001 SET character_set_client      = @saved_cs_client */;
/*!50001 SET character_set_results     = @saved_cs_results */;
/*!50001 SET collation_connection      = @saved_col_connection */;

--
-- Final view structure for view `v_branch_comparison`
--

/*!50001 DROP VIEW IF EXISTS `v_branch_comparison`*/;
/*!50001 SET @saved_cs_client          = @@character_set_client */;
/*!50001 SET @saved_cs_results         = @@character_set_results */;
/*!50001 SET @saved_col_connection     = @@collation_connection */;
/*!50001 SET character_set_client      = utf8mb4 */;
/*!50001 SET character_set_results     = utf8mb4 */;
/*!50001 SET collation_connection      = utf8mb4_0900_ai_ci */;
/*!50001 CREATE ALGORITHM=UNDEFINED */
/*!50013 DEFINER=`root`@`localhost` SQL SECURITY DEFINER */
/*!50001 VIEW `v_branch_comparison` AS select `b`.`branch_name` AS `branch_name`,date_format(`co`.`order_date`,'%Y-%m') AS `period`,count(distinct `co`.`order_ID`) AS `total_orders`,coalesce(sum(`co`.`total_amount`),0) AS `total_sales`,count(distinct `co`.`customer_ID`) AS `unique_customers` from (`branch` `b` left join `customerorder` `co` on(((`b`.`branch_ID` = `co`.`branch_ID`) and (`co`.`status` = 'Completed')))) group by `b`.`branch_name`,date_format(`co`.`order_date`,'%Y-%m') */;
/*!50001 SET character_set_client      = @saved_cs_client */;
/*!50001 SET character_set_results     = @saved_cs_results */;
/*!50001 SET collation_connection      = @saved_col_connection */;

--
-- Final view structure for view `v_ceo_dashboard`
--

/*!50001 DROP VIEW IF EXISTS `v_ceo_dashboard`*/;
/*!50001 SET @saved_cs_client          = @@character_set_client */;
/*!50001 SET @saved_cs_results         = @@character_set_results */;
/*!50001 SET @saved_col_connection     = @@collation_connection */;
/*!50001 SET character_set_client      = utf8mb4 */;
/*!50001 SET character_set_results     = utf8mb4 */;
/*!50001 SET collation_connection      = utf8mb4_0900_ai_ci */;
/*!50001 CREATE ALGORITHM=UNDEFINED */
/*!50013 DEFINER=`root`@`localhost` SQL SECURITY DEFINER */
/*!50001 VIEW `v_ceo_dashboard` AS select cast(curdate() as date) AS `report_date`,'daily' AS `period_type`,(select coalesce(sum(`customerorder`.`total_amount`),0) from `customerorder` where ((cast(`customerorder`.`order_date` as date) = curdate()) and (`customerorder`.`status` = 'Completed'))) AS `total_sales`,(select coalesce(count(distinct `customerorder`.`customer_ID`),0) from `customerorder` where ((cast(`customerorder`.`order_date` as date) = curdate()) and (`customerorder`.`status` = 'Completed'))) AS `active_customers`,(select coalesce(count(0),0) from `staff` where (`staff`.`status` = 'active')) AS `employee_count`,(select coalesce(sum((`inventory`.`quantity_on_hand` * `inventory`.`unit_cost`)),0) from `inventory`) AS `total_inventory_value` */;
/*!50001 SET character_set_client      = @saved_cs_client */;
/*!50001 SET character_set_results     = @saved_cs_results */;
/*!50001 SET collation_connection      = @saved_col_connection */;

--
-- Final view structure for view `v_certificate_tracking`
--

/*!50001 DROP VIEW IF EXISTS `v_certificate_tracking`*/;
/*!50001 SET @saved_cs_client          = @@character_set_client */;
/*!50001 SET @saved_cs_results         = @@character_set_results */;
/*!50001 SET @saved_col_connection     = @@collation_connection */;
/*!50001 SET character_set_client      = utf8mb4 */;
/*!50001 SET character_set_results     = utf8mb4 */;
/*!50001 SET collation_connection      = utf8mb4_0900_ai_ci */;
/*!50001 CREATE ALGORITHM=UNDEFINED */
/*!50013 DEFINER=`root`@`localhost` SQL SECURITY DEFINER */
/*!50001 VIEW `v_certificate_tracking` AS select `sc`.`certificate_ID` AS `certificate_ID`,`si`.`batch_ID` AS `batch_ID`,`p`.`product_name` AS `product_name`,`sc`.`transaction_type` AS `transaction_type`,`sc`.`date` AS `date`,`si`.`received_date` AS `received_date`,`si`.`expiry_date` AS `expiry_date`,`si`.`status` AS `item_status` from ((`stockitemcertificate` `sc` join `stockitem` `si` on((`sc`.`item_ID` = `si`.`item_ID`))) join `products` `p` on((`si`.`product_ID` = `p`.`product_ID`))) order by `sc`.`date` desc,`si`.`batch_ID` */;
/*!50001 SET character_set_client      = @saved_cs_client */;
/*!50001 SET character_set_results     = @saved_cs_results */;
/*!50001 SET collation_connection      = @saved_col_connection */;

--
-- Final view structure for view `v_customer_interaction`
--

/*!50001 DROP VIEW IF EXISTS `v_customer_interaction`*/;
/*!50001 SET @saved_cs_client          = @@character_set_client */;
/*!50001 SET @saved_cs_results         = @@character_set_results */;
/*!50001 SET @saved_col_connection     = @@collation_connection */;
/*!50001 SET character_set_client      = utf8mb4 */;
/*!50001 SET character_set_results     = utf8mb4 */;
/*!50001 SET collation_connection      = utf8mb4_0900_ai_ci */;
/*!50001 CREATE ALGORITHM=UNDEFINED */
/*!50013 DEFINER=`root`@`localhost` SQL SECURITY DEFINER */
/*!50001 VIEW `v_customer_interaction` AS select `co`.`branch_ID` AS `branch_ID`,`c`.`customer_ID` AS `customer_ID`,`u`.`first_name` AS `first_name`,`u`.`last_name` AS `last_name`,`c`.`loyalty_level` AS `loyalty_level`,`c`.`phone` AS `phone`,`c`.`email` AS `email`,count(distinct `co`.`order_ID`) AS `total_orders`,coalesce(sum((case when (`co`.`status` = 'Completed') then `co`.`total_amount` end)),0) AS `total_spent`,max(`co`.`order_date`) AS `last_purchase` from ((`customer` `c` join `user` `u` on((`c`.`user_name` = `u`.`user_name`))) left join `customerorder` `co` on((`c`.`customer_ID` = `co`.`customer_ID`))) group by `co`.`branch_ID`,`c`.`customer_ID`,`u`.`first_name`,`u`.`last_name`,`c`.`loyalty_level`,`c`.`phone`,`c`.`email` */;
/*!50001 SET character_set_client      = @saved_cs_client */;
/*!50001 SET character_set_results     = @saved_cs_results */;
/*!50001 SET collation_connection      = @saved_col_connection */;

--
-- Final view structure for view `v_customer_product_info`
--

/*!50001 DROP VIEW IF EXISTS `v_customer_product_info`*/;
/*!50001 SET @saved_cs_client          = @@character_set_client */;
/*!50001 SET @saved_cs_results         = @@character_set_results */;
/*!50001 SET @saved_col_connection     = @@collation_connection */;
/*!50001 SET character_set_client      = utf8mb4 */;
/*!50001 SET character_set_results     = utf8mb4 */;
/*!50001 SET collation_connection      = utf8mb4_0900_ai_ci */;
/*!50001 CREATE ALGORITHM=UNDEFINED */
/*!50013 DEFINER=`root`@`localhost` SQL SECURITY DEFINER */
/*!50001 VIEW `v_customer_product_info` AS select `p`.`product_ID` AS `product_ID`,`p`.`product_name` AS `product_name`,`c`.`category_name` AS `category_name`,`p`.`unit_price` AS `unit_price`,`p`.`unit` AS `unit`,`p`.`description` AS `description`,`p`.`status` AS `product_status`,group_concat(distinct concat(`pa`.`attr_name`,': ',`pa`.`attr_value`) separator ', ') AS `attributes` from ((`products` `p` join `categories` `c` on((`p`.`category_id` = `c`.`category_id`))) left join `productattribute` `pa` on((`p`.`product_ID` = `pa`.`product_id`))) where (`p`.`status` = 'active') group by `p`.`product_ID`,`p`.`product_name`,`c`.`category_name`,`p`.`unit_price`,`p`.`unit`,`p`.`description`,`p`.`status` */;
/*!50001 SET character_set_client      = @saved_cs_client */;
/*!50001 SET character_set_results     = @saved_cs_results */;
/*!50001 SET collation_connection      = @saved_col_connection */;

--
-- Final view structure for view `v_customer_profile`
--

/*!50001 DROP VIEW IF EXISTS `v_customer_profile`*/;
/*!50001 SET @saved_cs_client          = @@character_set_client */;
/*!50001 SET @saved_cs_results         = @@character_set_results */;
/*!50001 SET @saved_col_connection     = @@collation_connection */;
/*!50001 SET character_set_client      = utf8mb4 */;
/*!50001 SET character_set_results     = utf8mb4 */;
/*!50001 SET collation_connection      = utf8mb4_0900_ai_ci */;
/*!50001 CREATE ALGORITHM=UNDEFINED */
/*!50013 DEFINER=`root`@`localhost` SQL SECURITY DEFINER */
/*!50001 VIEW `v_customer_profile` AS select `c`.`customer_ID` AS `customer_ID`,`u`.`first_name` AS `first_name`,`u`.`last_name` AS `last_name`,`u`.`user_telephone` AS `phone`,`u`.`user_email` AS `email`,`c`.`loyalty_level` AS `loyalty_level`,max(`co`.`shipping_address`) AS `last_shipping_address`,count(distinct `co`.`order_ID`) AS `total_orders` from ((`user` `u` join `customer` `c` on((`u`.`user_name` = `c`.`user_name`))) left join `customerorder` `co` on((`c`.`customer_ID` = `co`.`customer_ID`))) group by `c`.`customer_ID`,`u`.`first_name`,`u`.`last_name`,`u`.`user_telephone`,`u`.`user_email`,`c`.`loyalty_level` */;
/*!50001 SET character_set_client      = @saved_cs_client */;
/*!50001 SET character_set_results     = @saved_cs_results */;
/*!50001 SET collation_connection      = @saved_col_connection */;

--
-- Final view structure for view `v_expiry_alerts`
--

/*!50001 DROP VIEW IF EXISTS `v_expiry_alerts`*/;
/*!50001 SET @saved_cs_client          = @@character_set_client */;
/*!50001 SET @saved_cs_results         = @@character_set_results */;
/*!50001 SET @saved_col_connection     = @@collation_connection */;
/*!50001 SET character_set_client      = utf8mb4 */;
/*!50001 SET character_set_results     = utf8mb4 */;
/*!50001 SET collation_connection      = utf8mb4_0900_ai_ci */;
/*!50001 CREATE ALGORITHM=UNDEFINED */
/*!50013 DEFINER=`root`@`localhost` SQL SECURITY DEFINER */
/*!50001 VIEW `v_expiry_alerts` AS select `p`.`product_name` AS `product_name`,`i`.`batch_ID` AS `batch_ID`,`i`.`date_expired` AS `date_expired`,(to_days(`i`.`date_expired`) - to_days(curdate())) AS `remaining_days`,`i`.`quantity_on_hand` AS `quantity_on_hand`,(case when ((to_days(`i`.`date_expired`) - to_days(curdate())) <= 7) then '紧急处理' when ((to_days(`i`.`date_expired`) - to_days(curdate())) <= 30) then '促销处理' else '正常监控' end) AS `suggested_action` from (`inventory` `i` join `products` `p` on((`i`.`product_ID` = `p`.`product_ID`))) where ((`i`.`date_expired` is not null) and (`i`.`date_expired` >= curdate())) order by `i`.`date_expired` */;
/*!50001 SET character_set_client      = @saved_cs_client */;
/*!50001 SET character_set_results     = @saved_cs_results */;
/*!50001 SET collation_connection      = @saved_col_connection */;

--
-- Final view structure for view `v_financial_overview`
--

/*!50001 DROP VIEW IF EXISTS `v_financial_overview`*/;
/*!50001 SET @saved_cs_client          = @@character_set_client */;
/*!50001 SET @saved_cs_results         = @@character_set_results */;
/*!50001 SET @saved_col_connection     = @@collation_connection */;
/*!50001 SET character_set_client      = utf8mb4 */;
/*!50001 SET character_set_results     = utf8mb4 */;
/*!50001 SET collation_connection      = utf8mb4_0900_ai_ci */;
/*!50001 CREATE ALGORITHM=UNDEFINED */
/*!50013 DEFINER=`root`@`localhost` SQL SECURITY DEFINER */
/*!50001 VIEW `v_financial_overview` AS select date_format(`co`.`order_date`,'%Y-%m-%d') AS `date_day`,`b`.`branch_name` AS `branch_name`,sum(`co`.`total_amount`) AS `revenue`,count(distinct `co`.`order_ID`) AS `order_count` from (`customerorder` `co` join `branch` `b` on((`co`.`branch_ID` = `b`.`branch_ID`))) where (`co`.`status` = 'Completed') group by date_format(`co`.`order_date`,'%Y-%m-%d'),`b`.`branch_name` */;
/*!50001 SET character_set_client      = @saved_cs_client */;
/*!50001 SET character_set_results     = @saved_cs_results */;
/*!50001 SET collation_connection      = @saved_col_connection */;

--
-- Final view structure for view `v_inventory_management`
--

/*!50001 DROP VIEW IF EXISTS `v_inventory_management`*/;
/*!50001 SET @saved_cs_client          = @@character_set_client */;
/*!50001 SET @saved_cs_results         = @@character_set_results */;
/*!50001 SET @saved_col_connection     = @@collation_connection */;
/*!50001 SET character_set_client      = utf8mb4 */;
/*!50001 SET character_set_results     = utf8mb4 */;
/*!50001 SET collation_connection      = utf8mb4_0900_ai_ci */;
/*!50001 CREATE ALGORITHM=UNDEFINED */
/*!50013 DEFINER=`root`@`localhost` SQL SECURITY DEFINER */
/*!50001 VIEW `v_inventory_management` AS select `i`.`branch_ID` AS `branch_ID`,`p`.`product_ID` AS `product_ID`,`p`.`product_name` AS `product_name`,`p`.`sku` AS `sku`,`p`.`unit` AS `unit`,`i`.`quantity_on_hand` AS `current_stock`,`i`.`batch_ID` AS `batch_ID`,`i`.`received_date` AS `received_date`,`i`.`date_expired` AS `date_expired` from (`inventory` `i` join `products` `p` on((`i`.`product_ID` = `p`.`product_ID`))) */;
/*!50001 SET character_set_client      = @saved_cs_client */;
/*!50001 SET character_set_results     = @saved_cs_results */;
/*!50001 SET collation_connection      = @saved_col_connection */;

--
-- Final view structure for view `v_low_stock_alerts`
--

/*!50001 DROP VIEW IF EXISTS `v_low_stock_alerts`*/;
/*!50001 SET @saved_cs_client          = @@character_set_client */;
/*!50001 SET @saved_cs_results         = @@character_set_results */;
/*!50001 SET @saved_col_connection     = @@collation_connection */;
/*!50001 SET character_set_client      = utf8mb4 */;
/*!50001 SET character_set_results     = utf8mb4 */;
/*!50001 SET collation_connection      = utf8mb4_0900_ai_ci */;
/*!50001 CREATE ALGORITHM=UNDEFINED */
/*!50013 DEFINER=`root`@`localhost` SQL SECURITY DEFINER */
/*!50001 VIEW `v_low_stock_alerts` AS select `p`.`product_name` AS `product_name`,`p`.`sku` AS `sku`,`i`.`batch_ID` AS `batch_ID`,`i`.`quantity_on_hand` AS `current_stock`,(select avg(`pi`.`unit_cost`) from `purchaseitem` `pi` where (`pi`.`product_ID` = `p`.`product_ID`)) AS `avg_cost`,(select group_concat(distinct `s`.`company_name` separator ',') from ((`purchaseorder` `po` join `supplier` `s` on((`po`.`supplier_ID` = `s`.`supplier_ID`))) join `purchaseitem` `pi` on((`po`.`purchase_order_ID` = `pi`.`purchase_order_ID`))) where (`pi`.`product_ID` = `p`.`product_ID`)) AS `suppliers` from (`inventory` `i` join `products` `p` on((`i`.`product_ID` = `p`.`product_ID`))) where (`i`.`quantity_on_hand` <= 10) order by `i`.`quantity_on_hand` */;
/*!50001 SET character_set_client      = @saved_cs_client */;
/*!50001 SET character_set_results     = @saved_cs_results */;
/*!50001 SET collation_connection      = @saved_col_connection */;

--
-- Final view structure for view `v_order_history`
--

/*!50001 DROP VIEW IF EXISTS `v_order_history`*/;
/*!50001 SET @saved_cs_client          = @@character_set_client */;
/*!50001 SET @saved_cs_results         = @@character_set_results */;
/*!50001 SET @saved_col_connection     = @@collation_connection */;
/*!50001 SET character_set_client      = utf8mb4 */;
/*!50001 SET character_set_results     = utf8mb4 */;
/*!50001 SET collation_connection      = utf8mb4_0900_ai_ci */;
/*!50001 CREATE ALGORITHM=UNDEFINED */
/*!50013 DEFINER=`root`@`localhost` SQL SECURITY DEFINER */
/*!50001 VIEW `v_order_history` AS select `co`.`customer_ID` AS `customer_ID`,`co`.`order_ID` AS `order_ID`,`co`.`order_date` AS `order_date`,`co`.`total_amount` AS `total_amount`,`co`.`status` AS `order_status`,`co`.`shipping_address` AS `shipping_address`,count(distinct `oi`.`item_ID`) AS `item_count` from (`customerorder` `co` left join `orderitem` `oi` on((`co`.`order_ID` = `oi`.`order_ID`))) group by `co`.`customer_ID`,`co`.`order_ID`,`co`.`order_date`,`co`.`total_amount`,`co`.`status`,`co`.`shipping_address` order by `co`.`order_date` desc */;
/*!50001 SET character_set_client      = @saved_cs_client */;
/*!50001 SET character_set_results     = @saved_cs_results */;
/*!50001 SET collation_connection      = @saved_col_connection */;

--
-- Final view structure for view `v_product_information`
--

/*!50001 DROP VIEW IF EXISTS `v_product_information`*/;
/*!50001 SET @saved_cs_client          = @@character_set_client */;
/*!50001 SET @saved_cs_results         = @@character_set_results */;
/*!50001 SET @saved_col_connection     = @@collation_connection */;
/*!50001 SET character_set_client      = utf8mb4 */;
/*!50001 SET character_set_results     = utf8mb4 */;
/*!50001 SET collation_connection      = utf8mb4_0900_ai_ci */;
/*!50001 CREATE ALGORITHM=UNDEFINED */
/*!50013 DEFINER=`root`@`localhost` SQL SECURITY DEFINER */
/*!50001 VIEW `v_product_information` AS select `p`.`product_ID` AS `product_ID`,`p`.`product_name` AS `product_name`,`p`.`sku` AS `sku`,`c`.`category_name` AS `category_name`,`p`.`unit` AS `unit`,group_concat(distinct concat(`pa`.`attr_name`,': ',`pa`.`attr_value`) separator ', ') AS `attributes`,`p`.`unit_price` AS `selling_price`,(select avg(`pi`.`unit_cost`) from `purchaseitem` `pi` where (`pi`.`product_ID` = `p`.`product_ID`)) AS `avg_cost`,(select group_concat(distinct `sup`.`company_name` separator ',') from ((`purchaseorder` `po` join `supplier` `sup` on((`po`.`supplier_ID` = `sup`.`supplier_ID`))) join `purchaseitem` `pi` on((`po`.`purchase_order_ID` = `pi`.`purchase_order_ID`))) where (`pi`.`product_ID` = `p`.`product_ID`)) AS `suppliers`,(select coalesce(sum(`inventory`.`quantity_on_hand`),0) from `inventory` where (`inventory`.`product_ID` = `p`.`product_ID`)) AS `total_inventory` from ((`products` `p` join `categories` `c` on((`p`.`category_id` = `c`.`category_id`))) left join `productattribute` `pa` on((`p`.`product_ID` = `pa`.`product_id`))) group by `p`.`product_ID`,`p`.`product_name`,`p`.`sku`,`c`.`category_name`,`p`.`unit`,`p`.`unit_price` */;
/*!50001 SET character_set_client      = @saved_cs_client */;
/*!50001 SET character_set_results     = @saved_cs_results */;
/*!50001 SET collation_connection      = @saved_col_connection */;

--
-- Final view structure for view `v_purchasing_management`
--

/*!50001 DROP VIEW IF EXISTS `v_purchasing_management`*/;
/*!50001 SET @saved_cs_client          = @@character_set_client */;
/*!50001 SET @saved_cs_results         = @@character_set_results */;
/*!50001 SET @saved_col_connection     = @@collation_connection */;
/*!50001 SET character_set_client      = utf8mb4 */;
/*!50001 SET character_set_results     = utf8mb4 */;
/*!50001 SET collation_connection      = utf8mb4_0900_ai_ci */;
/*!50001 CREATE ALGORITHM=UNDEFINED */
/*!50013 DEFINER=`root`@`localhost` SQL SECURITY DEFINER */
/*!50001 VIEW `v_purchasing_management` AS select `po`.`purchase_order_ID` AS `purchase_order_ID`,`s`.`company_name` AS `supplier`,`po`.`branch_ID` AS `branch_ID`,`po`.`date` AS `date`,`po`.`status` AS `status`,`po`.`total_amount` AS `total_amount`,count(distinct `pi`.`supply_id`) AS `item_count` from ((`purchaseorder` `po` join `supplier` `s` on((`po`.`supplier_ID` = `s`.`supplier_ID`))) left join `purchaseitem` `pi` on((`po`.`purchase_order_ID` = `pi`.`purchase_order_ID`))) group by `po`.`purchase_order_ID`,`s`.`company_name`,`po`.`branch_ID`,`po`.`date`,`po`.`status`,`po`.`total_amount` */;
/*!50001 SET character_set_client      = @saved_cs_client */;
/*!50001 SET character_set_results     = @saved_cs_results */;
/*!50001 SET collation_connection      = @saved_col_connection */;

--
-- Final view structure for view `v_sales_dashboard`
--

/*!50001 DROP VIEW IF EXISTS `v_sales_dashboard`*/;
/*!50001 SET @saved_cs_client          = @@character_set_client */;
/*!50001 SET @saved_cs_results         = @@character_set_results */;
/*!50001 SET @saved_col_connection     = @@collation_connection */;
/*!50001 SET character_set_client      = utf8mb4 */;
/*!50001 SET character_set_results     = utf8mb4 */;
/*!50001 SET collation_connection      = utf8mb4_0900_ai_ci */;
/*!50001 CREATE ALGORITHM=UNDEFINED */
/*!50013 DEFINER=`root`@`localhost` SQL SECURITY DEFINER */
/*!50001 VIEW `v_sales_dashboard` AS select `b`.`branch_ID` AS `branch_ID`,`b`.`branch_name` AS `branch_name`,cast(curdate() as date) AS `today`,count(distinct (case when ((cast(`co`.`order_date` as date) = curdate()) and (`co`.`status` = 'Completed')) then `co`.`order_ID` end)) AS `today_orders`,coalesce(sum((case when ((cast(`co`.`order_date` as date) = curdate()) and (`co`.`status` = 'Completed')) then `co`.`total_amount` end)),0) AS `today_sales`,count(distinct (case when ((month(`co`.`order_date`) = month(curdate())) and (year(`co`.`order_date`) = year(curdate())) and (`co`.`status` = 'Completed')) then `co`.`order_ID` end)) AS `month_orders` from (`branch` `b` left join `customerorder` `co` on((`co`.`branch_ID` = `b`.`branch_ID`))) group by `b`.`branch_ID`,`b`.`branch_name` */;
/*!50001 SET character_set_client      = @saved_cs_client */;
/*!50001 SET character_set_results     = @saved_cs_results */;
/*!50001 SET collation_connection      = @saved_col_connection */;

--
-- Final view structure for view `v_staff_employees`
--

/*!50001 DROP VIEW IF EXISTS `v_staff_employees`*/;
/*!50001 SET @saved_cs_client          = @@character_set_client */;
/*!50001 SET @saved_cs_results         = @@character_set_results */;
/*!50001 SET @saved_col_connection     = @@collation_connection */;
/*!50001 SET character_set_client      = utf8mb3 */;
/*!50001 SET character_set_results     = utf8mb3 */;
/*!50001 SET collation_connection      = utf8mb3_general_ci */;
/*!50001 CREATE ALGORITHM=UNDEFINED */
/*!50013 DEFINER=`root`@`localhost` SQL SECURITY DEFINER */
/*!50001 VIEW `v_staff_employees` AS select `s`.`branch_ID` AS `branch_ID`,`b`.`branch_name` AS `branch_name`,`s`.`staff_ID` AS `staff_ID`,`s`.`user_name` AS `user_name`,`u`.`first_name` AS `first_name`,`u`.`last_name` AS `last_name`,`u`.`user_email` AS `user_email`,`u`.`user_telephone` AS `user_telephone`,`s`.`phone` AS `staff_phone`,`s`.`position` AS `staff_position`,`s`.`hire_date` AS `hire_date`,`s`.`status` AS `staff_status` from ((`staff` `s` join `user` `u` on((`u`.`user_name` = `s`.`user_name`))) join `branch` `b` on((`b`.`branch_ID` = `s`.`branch_ID`))) where (`u`.`user_type` = 'staff') */;
/*!50001 SET character_set_client      = @saved_cs_client */;
/*!50001 SET character_set_results     = @saved_cs_results */;
/*!50001 SET collation_connection      = @saved_col_connection */;

--
-- Final view structure for view `v_staff_information`
--

/*!50001 DROP VIEW IF EXISTS `v_staff_information`*/;
/*!50001 SET @saved_cs_client          = @@character_set_client */;
/*!50001 SET @saved_cs_results         = @@character_set_results */;
/*!50001 SET @saved_col_connection     = @@collation_connection */;
/*!50001 SET character_set_client      = utf8mb4 */;
/*!50001 SET character_set_results     = utf8mb4 */;
/*!50001 SET collation_connection      = utf8mb4_0900_ai_ci */;
/*!50001 CREATE ALGORITHM=UNDEFINED */
/*!50013 DEFINER=`root`@`localhost` SQL SECURITY DEFINER */
/*!50001 VIEW `v_staff_information` AS select `b`.`branch_name` AS `branch_name`,`u`.`first_name` AS `first_name`,`u`.`last_name` AS `last_name`,`s`.`position` AS `position`,`u`.`user_telephone` AS `phone`,`u`.`user_email` AS `email`,`s`.`salary` AS `salary`,`s`.`hire_date` AS `hire_date`,`s`.`status` AS `status` from ((`staff` `s` join `user` `u` on((`s`.`user_name` = `u`.`user_name`))) join `branch` `b` on((`s`.`branch_ID` = `b`.`branch_ID`))) where (`u`.`user_type` = 'staff') */;
/*!50001 SET character_set_client      = @saved_cs_client */;
/*!50001 SET character_set_results     = @saved_cs_results */;
/*!50001 SET collation_connection      = @saved_col_connection */;

--
-- Final view structure for view `v_staff_inventory`
--

/*!50001 DROP VIEW IF EXISTS `v_staff_inventory`*/;
/*!50001 SET @saved_cs_client          = @@character_set_client */;
/*!50001 SET @saved_cs_results         = @@character_set_results */;
/*!50001 SET @saved_col_connection     = @@collation_connection */;
/*!50001 SET character_set_client      = utf8mb3 */;
/*!50001 SET character_set_results     = utf8mb3 */;
/*!50001 SET collation_connection      = utf8mb3_general_ci */;
/*!50001 CREATE ALGORITHM=UNDEFINED */
/*!50013 DEFINER=`root`@`localhost` SQL SECURITY DEFINER */
/*!50001 VIEW `v_staff_inventory` AS select `i`.`branch_ID` AS `branch_ID`,`b`.`branch_name` AS `branch_name`,`i`.`batch_ID` AS `batch_ID`,`i`.`product_ID` AS `product_ID`,`p`.`sku` AS `sku`,`p`.`product_name` AS `product_name`,`p`.`unit` AS `unit`,`c`.`category_name` AS `category_name`,`i`.`quantity_received` AS `quantity_received`,`i`.`quantity_on_hand` AS `quantity_on_hand`,`i`.`unit_cost` AS `unit_cost`,(`i`.`quantity_on_hand` * `i`.`unit_cost`) AS `inventory_value`,`i`.`received_date` AS `received_date`,`i`.`date_produced` AS `date_produced`,`i`.`date_expired` AS `date_expired`,(case when (`i`.`quantity_on_hand` <= 5) then 'HIGH' when (`i`.`quantity_on_hand` <= 10) then 'MEDIUM' else 'LOW' end) AS `low_stock_severity`,(case when (`i`.`date_expired` is null) then NULL else (to_days(`i`.`date_expired`) - to_days(curdate())) end) AS `days_to_expire`,(case when ((`i`.`date_expired` is not null) and ((to_days(`i`.`date_expired`) - to_days(curdate())) between 0 and 30)) then 1 else 0 end) AS `is_expiring_soon` from (((`inventory` `i` join `branch` `b` on((`b`.`branch_ID` = `i`.`branch_ID`))) join `products` `p` on((`p`.`product_ID` = `i`.`product_ID`))) join `categories` `c` on((`c`.`category_id` = `p`.`category_id`))) */;
/*!50001 SET character_set_client      = @saved_cs_client */;
/*!50001 SET character_set_results     = @saved_cs_results */;
/*!50001 SET collation_connection      = @saved_col_connection */;

--
-- Final view structure for view `v_staff_order_items`
--

/*!50001 DROP VIEW IF EXISTS `v_staff_order_items`*/;
/*!50001 SET @saved_cs_client          = @@character_set_client */;
/*!50001 SET @saved_cs_results         = @@character_set_results */;
/*!50001 SET @saved_col_connection     = @@collation_connection */;
/*!50001 SET character_set_client      = utf8mb3 */;
/*!50001 SET character_set_results     = utf8mb3 */;
/*!50001 SET collation_connection      = utf8mb3_general_ci */;
/*!50001 CREATE ALGORITHM=UNDEFINED */
/*!50013 DEFINER=`root`@`localhost` SQL SECURITY DEFINER */
/*!50001 VIEW `v_staff_order_items` AS select `co`.`branch_ID` AS `branch_ID`,`b`.`branch_name` AS `branch_name`,`oi`.`order_ID` AS `order_ID`,`co`.`order_date` AS `order_date`,`co`.`customer_ID` AS `customer_ID`,`oi`.`item_ID` AS `item_ID`,`oi`.`product_ID` AS `product_ID`,`p`.`sku` AS `sku`,`p`.`product_name` AS `product_name`,`p`.`unit` AS `unit`,`oi`.`unit_price` AS `unit_price`,1 AS `quantity`,(`oi`.`unit_price` * 1) AS `line_amount`,`si`.`batch_ID` AS `batch_ID`,`si`.`status` AS `stock_item_status` from ((((`orderitem` `oi` join `customerorder` `co` on((`co`.`order_ID` = `oi`.`order_ID`))) join `branch` `b` on((`b`.`branch_ID` = `co`.`branch_ID`))) join `products` `p` on((`p`.`product_ID` = `oi`.`product_ID`))) left join `stockitem` `si` on((`si`.`item_ID` = `oi`.`item_ID`))) */;
/*!50001 SET character_set_client      = @saved_cs_client */;
/*!50001 SET character_set_results     = @saved_cs_results */;
/*!50001 SET collation_connection      = @saved_col_connection */;

--
-- Final view structure for view `v_staff_orders`
--

/*!50001 DROP VIEW IF EXISTS `v_staff_orders`*/;
/*!50001 SET @saved_cs_client          = @@character_set_client */;
/*!50001 SET @saved_cs_results         = @@character_set_results */;
/*!50001 SET @saved_col_connection     = @@collation_connection */;
/*!50001 SET character_set_client      = utf8mb3 */;
/*!50001 SET character_set_results     = utf8mb3 */;
/*!50001 SET collation_connection      = utf8mb3_general_ci */;
/*!50001 CREATE ALGORITHM=UNDEFINED */
/*!50013 DEFINER=`root`@`localhost` SQL SECURITY DEFINER */
/*!50001 VIEW `v_staff_orders` AS select `co`.`branch_ID` AS `branch_ID`,`b`.`branch_name` AS `branch_name`,`co`.`order_ID` AS `order_ID`,`co`.`order_date` AS `order_date`,`co`.`customer_ID` AS `customer_ID`,`co`.`total_amount` AS `total_amount`,`co`.`status` AS `order_status`,`co`.`shipping_address` AS `shipping_address` from (`customerorder` `co` join `branch` `b` on((`b`.`branch_ID` = `co`.`branch_ID`))) */;
/*!50001 SET character_set_client      = @saved_cs_client */;
/*!50001 SET character_set_results     = @saved_cs_results */;
/*!50001 SET collation_connection      = @saved_col_connection */;

--
-- Final view structure for view `v_staff_profile`
--

/*!50001 DROP VIEW IF EXISTS `v_staff_profile`*/;
/*!50001 SET @saved_cs_client          = @@character_set_client */;
/*!50001 SET @saved_cs_results         = @@character_set_results */;
/*!50001 SET @saved_col_connection     = @@collation_connection */;
/*!50001 SET character_set_client      = utf8mb3 */;
/*!50001 SET character_set_results     = utf8mb3 */;
/*!50001 SET collation_connection      = utf8mb3_general_ci */;
/*!50001 CREATE ALGORITHM=UNDEFINED */
/*!50013 DEFINER=`root`@`localhost` SQL SECURITY DEFINER */
/*!50001 VIEW `v_staff_profile` AS select `s`.`staff_ID` AS `staff_ID`,`s`.`user_name` AS `user_name`,`u`.`first_name` AS `first_name`,`u`.`last_name` AS `last_name`,`u`.`user_email` AS `user_email`,`u`.`user_telephone` AS `user_telephone`,`s`.`phone` AS `staff_phone`,`s`.`position` AS `staff_position`,`s`.`salary` AS `salary`,`s`.`hire_date` AS `hire_date`,`s`.`status` AS `staff_status`,`s`.`branch_ID` AS `branch_ID`,`b`.`branch_name` AS `branch_name`,`b`.`address` AS `branch_address`,`b`.`phone` AS `branch_phone`,`b`.`email` AS `branch_email` from ((`staff` `s` join `user` `u` on((`u`.`user_name` = `s`.`user_name`))) join `branch` `b` on((`b`.`branch_ID` = `s`.`branch_ID`))) where (`u`.`user_type` = 'staff') */;
/*!50001 SET character_set_client      = @saved_cs_client */;
/*!50001 SET character_set_results     = @saved_cs_results */;
/*!50001 SET collation_connection      = @saved_col_connection */;

--
-- Final view structure for view `v_staff_purchase_items`
--

/*!50001 DROP VIEW IF EXISTS `v_staff_purchase_items`*/;
/*!50001 SET @saved_cs_client          = @@character_set_client */;
/*!50001 SET @saved_cs_results         = @@character_set_results */;
/*!50001 SET @saved_col_connection     = @@collation_connection */;
/*!50001 SET character_set_client      = utf8mb3 */;
/*!50001 SET character_set_results     = utf8mb3 */;
/*!50001 SET collation_connection      = utf8mb3_general_ci */;
/*!50001 CREATE ALGORITHM=UNDEFINED */
/*!50013 DEFINER=`root`@`localhost` SQL SECURITY DEFINER */
/*!50001 VIEW `v_staff_purchase_items` AS select `po`.`branch_ID` AS `branch_ID`,`b`.`branch_name` AS `branch_name`,`pi`.`purchase_order_ID` AS `purchase_order_ID`,`po`.`date` AS `purchase_date`,`po`.`status` AS `purchase_status`,`po`.`supplier_ID` AS `supplier_ID`,`sup`.`company_name` AS `supplier_company`,`pi`.`supply_id` AS `supply_id`,`pi`.`item_ID` AS `item_ID`,`pi`.`product_ID` AS `product_ID`,`p`.`sku` AS `sku`,`p`.`product_name` AS `product_name`,`p`.`unit` AS `unit`,`pi`.`unit_cost` AS `unit_cost`,`pi`.`received_date` AS `received_date`,`si`.`batch_ID` AS `batch_ID`,`si`.`status` AS `stock_item_status`,`si`.`expiry_date` AS `expiry_date` from (((((`purchaseitem` `pi` join `purchaseorder` `po` on((`po`.`purchase_order_ID` = `pi`.`purchase_order_ID`))) join `branch` `b` on((`b`.`branch_ID` = `po`.`branch_ID`))) join `supplier` `sup` on((`sup`.`supplier_ID` = `po`.`supplier_ID`))) join `products` `p` on((`p`.`product_ID` = `pi`.`product_ID`))) left join `stockitem` `si` on((`si`.`item_ID` = `pi`.`item_ID`))) */;
/*!50001 SET character_set_client      = @saved_cs_client */;
/*!50001 SET character_set_results     = @saved_cs_results */;
/*!50001 SET collation_connection      = @saved_col_connection */;

--
-- Final view structure for view `v_staff_purchase_orders`
--

/*!50001 DROP VIEW IF EXISTS `v_staff_purchase_orders`*/;
/*!50001 SET @saved_cs_client          = @@character_set_client */;
/*!50001 SET @saved_cs_results         = @@character_set_results */;
/*!50001 SET @saved_col_connection     = @@collation_connection */;
/*!50001 SET character_set_client      = utf8mb3 */;
/*!50001 SET character_set_results     = utf8mb3 */;
/*!50001 SET collation_connection      = utf8mb3_general_ci */;
/*!50001 CREATE ALGORITHM=UNDEFINED */
/*!50013 DEFINER=`root`@`localhost` SQL SECURITY DEFINER */
/*!50001 VIEW `v_staff_purchase_orders` AS select `po`.`branch_ID` AS `branch_ID`,`b`.`branch_name` AS `branch_name`,`po`.`purchase_order_ID` AS `purchase_order_ID`,`po`.`date` AS `date`,`po`.`status` AS `purchase_status`,`po`.`total_amount` AS `total_amount`,`po`.`supplier_ID` AS `supplier_ID`,`s`.`company_name` AS `supplier_company`,`s`.`contact_person` AS `contact_person`,`s`.`phone` AS `supplier_phone`,`s`.`email` AS `supplier_email`,count(distinct `pi`.`supply_id`) AS `item_count` from (((`purchaseorder` `po` join `branch` `b` on((`b`.`branch_ID` = `po`.`branch_ID`))) join `supplier` `s` on((`s`.`supplier_ID` = `po`.`supplier_ID`))) left join `purchaseitem` `pi` on((`pi`.`purchase_order_ID` = `po`.`purchase_order_ID`))) group by `po`.`branch_ID`,`b`.`branch_name`,`po`.`purchase_order_ID`,`po`.`date`,`po`.`status`,`po`.`total_amount`,`po`.`supplier_ID`,`s`.`company_name`,`s`.`contact_person`,`s`.`phone`,`s`.`email` */;
/*!50001 SET character_set_client      = @saved_cs_client */;
/*!50001 SET character_set_results     = @saved_cs_results */;
/*!50001 SET collation_connection      = @saved_col_connection */;

--
-- Final view structure for view `v_staff_stock_quality`
--

/*!50001 DROP VIEW IF EXISTS `v_staff_stock_quality`*/;
/*!50001 SET @saved_cs_client          = @@character_set_client */;
/*!50001 SET @saved_cs_results         = @@character_set_results */;
/*!50001 SET @saved_col_connection     = @@collation_connection */;
/*!50001 SET character_set_client      = utf8mb3 */;
/*!50001 SET character_set_results     = utf8mb3 */;
/*!50001 SET collation_connection      = utf8mb3_general_ci */;
/*!50001 CREATE ALGORITHM=UNDEFINED */
/*!50013 DEFINER=`root`@`localhost` SQL SECURITY DEFINER */
/*!50001 VIEW `v_staff_stock_quality` AS select `si`.`branch_ID` AS `branch_ID`,`b`.`branch_name` AS `branch_name`,`si`.`item_ID` AS `item_ID`,`si`.`batch_ID` AS `batch_ID`,`si`.`product_ID` AS `product_ID`,`p`.`sku` AS `sku`,`p`.`product_name` AS `product_name`,`p`.`unit` AS `unit`,`si`.`received_date` AS `received_date`,`si`.`expiry_date` AS `expiry_date`,`si`.`status` AS `stock_item_status`,(case when (`si`.`status` in ('damaged','returned')) then 'ITEM_EXCEPTION' when ((`si`.`expiry_date` is not null) and ((to_days(`si`.`expiry_date`) - to_days(curdate())) between 0 and 30)) then 'EXPIRING_SOON' else 'NORMAL' end) AS `quality_flag`,(case when (`si`.`status` = 'damaged') then '建议：标记损耗并减少库存/生成 adjustment 记录' when (`si`.`status` = 'returned') then '建议：确认退回原因并决定是否重新入库' when ((`si`.`expiry_date` is not null) and ((to_days(`si`.`expiry_date`) - to_days(curdate())) between 0 and 30)) then '建议：临期处理（促销/下架/报损）' else NULL end) AS `suggested_action` from ((`stockitem` `si` join `branch` `b` on((`b`.`branch_ID` = `si`.`branch_ID`))) join `products` `p` on((`p`.`product_ID` = `si`.`product_ID`))) where ((`si`.`status` in ('damaged','returned')) or ((`si`.`expiry_date` is not null) and ((to_days(`si`.`expiry_date`) - to_days(curdate())) between 0 and 30))) */;
/*!50001 SET character_set_client      = @saved_cs_client */;
/*!50001 SET character_set_results     = @saved_cs_results */;
/*!50001 SET collation_connection      = @saved_col_connection */;

--
-- Final view structure for view `v_stock_movement`
--

/*!50001 DROP VIEW IF EXISTS `v_stock_movement`*/;
/*!50001 SET @saved_cs_client          = @@character_set_client */;
/*!50001 SET @saved_cs_results         = @@character_set_results */;
/*!50001 SET @saved_col_connection     = @@collation_connection */;
/*!50001 SET character_set_client      = utf8mb4 */;
/*!50001 SET character_set_results     = utf8mb4 */;
/*!50001 SET collation_connection      = utf8mb4_0900_ai_ci */;
/*!50001 CREATE ALGORITHM=UNDEFINED */
/*!50013 DEFINER=`root`@`localhost` SQL SECURITY DEFINER */
/*!50001 VIEW `v_stock_movement` AS select `si`.`branch_ID` AS `branch_ID`,`p`.`product_name` AS `product_name`,`si`.`batch_ID` AS `batch_ID`,`si`.`received_date` AS `received_date`,`si`.`expiry_date` AS `expiry_date`,`si`.`status` AS `item_status` from (`stockitem` `si` join `products` `p` on((`si`.`product_ID` = `p`.`product_ID`))) order by `si`.`received_date` desc */;
/*!50001 SET character_set_client      = @saved_cs_client */;
/*!50001 SET character_set_results     = @saved_cs_results */;
/*!50001 SET collation_connection      = @saved_col_connection */;

--
-- Final view structure for view `v_supplier_information`
--

/*!50001 DROP VIEW IF EXISTS `v_supplier_information`*/;
/*!50001 SET @saved_cs_client          = @@character_set_client */;
/*!50001 SET @saved_cs_results         = @@character_set_results */;
/*!50001 SET @saved_col_connection     = @@collation_connection */;
/*!50001 SET character_set_client      = utf8mb4 */;
/*!50001 SET character_set_results     = utf8mb4 */;
/*!50001 SET collation_connection      = utf8mb4_0900_ai_ci */;
/*!50001 CREATE ALGORITHM=UNDEFINED */
/*!50013 DEFINER=`root`@`localhost` SQL SECURITY DEFINER */
/*!50001 VIEW `v_supplier_information` AS select `s`.`supplier_ID` AS `supplier_ID`,`s`.`company_name` AS `company_name`,`s`.`contact_person` AS `contact_person`,`s`.`phone` AS `phone`,`s`.`email` AS `email`,`s`.`tax_number` AS `tax_number`,`s`.`status` AS `status`,count(distinct `po`.`purchase_order_ID`) AS `total_orders`,coalesce(sum(`po`.`total_amount`),0) AS `total_amount` from (`supplier` `s` left join `purchaseorder` `po` on((`s`.`supplier_ID` = `po`.`supplier_ID`))) group by `s`.`supplier_ID`,`s`.`company_name`,`s`.`contact_person`,`s`.`phone`,`s`.`email`,`s`.`tax_number`,`s`.`status` */;
/*!50001 SET character_set_client      = @saved_cs_client */;
/*!50001 SET character_set_results     = @saved_cs_results */;
/*!50001 SET collation_connection      = @saved_col_connection */;

--
-- Final view structure for view `v_supplier_relations`
--

/*!50001 DROP VIEW IF EXISTS `v_supplier_relations`*/;
/*!50001 SET @saved_cs_client          = @@character_set_client */;
/*!50001 SET @saved_cs_results         = @@character_set_results */;
/*!50001 SET @saved_col_connection     = @@collation_connection */;
/*!50001 SET character_set_client      = utf8mb4 */;
/*!50001 SET character_set_results     = utf8mb4 */;
/*!50001 SET collation_connection      = utf8mb4_0900_ai_ci */;
/*!50001 CREATE ALGORITHM=UNDEFINED */
/*!50013 DEFINER=`root`@`localhost` SQL SECURITY DEFINER */
/*!50001 VIEW `v_supplier_relations` AS select `supplier`.`supplier_ID` AS `supplier_ID`,`supplier`.`company_name` AS `company_name`,`supplier`.`contact_person` AS `contact_person`,`supplier`.`phone` AS `phone`,`supplier`.`email` AS `email`,`supplier`.`address` AS `address`,`supplier`.`status` AS `status` from `supplier` */;
/*!50001 SET character_set_client      = @saved_cs_client */;
/*!50001 SET character_set_results     = @saved_cs_results */;
/*!50001 SET collation_connection      = @saved_col_connection */;

--
-- Final view structure for view `v_wishlist_products`
--

/*!50001 DROP VIEW IF EXISTS `v_wishlist_products`*/;
/*!50001 SET @saved_cs_client          = @@character_set_client */;
/*!50001 SET @saved_cs_results         = @@character_set_results */;
/*!50001 SET @saved_col_connection     = @@collation_connection */;
/*!50001 SET character_set_client      = utf8mb4 */;
/*!50001 SET character_set_results     = utf8mb4 */;
/*!50001 SET collation_connection      = utf8mb4_0900_ai_ci */;
/*!50001 CREATE ALGORITHM=UNDEFINED */
/*!50013 DEFINER=`root`@`localhost` SQL SECURITY DEFINER */
/*!50001 VIEW `v_wishlist_products` AS select `co`.`customer_ID` AS `customer_ID`,`co`.`order_ID` AS `order_ID`,`p`.`product_ID` AS `product_ID`,`p`.`product_name` AS `product_name`,`p`.`sku` AS `sku`,`c`.`category_name` AS `category_name`,`p`.`unit_price` AS `unit_price`,`p`.`unit` AS `unit`,`p`.`description` AS `description`,count(`oi`.`item_ID`) AS `quantity`,(select coalesce(sum(`inventory`.`quantity_on_hand`),0) from `inventory` where (`inventory`.`product_ID` = `p`.`product_ID`)) AS `available_stock` from (((`customerorder` `co` join `orderitem` `oi` on((`co`.`order_ID` = `oi`.`order_ID`))) join `products` `p` on((`oi`.`product_ID` = `p`.`product_ID`))) join `categories` `c` on((`p`.`category_id` = `c`.`category_id`))) where (`co`.`status` = 'Pending') group by `co`.`customer_ID`,`co`.`order_ID`,`p`.`product_ID`,`p`.`product_name`,`p`.`sku`,`c`.`category_name`,`p`.`unit_price`,`p`.`unit`,`p`.`description` */;
/*!50001 SET character_set_client      = @saved_cs_client */;
/*!50001 SET character_set_results     = @saved_cs_results */;
/*!50001 SET collation_connection      = @saved_col_connection */;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2025-12-17 15:32:25
