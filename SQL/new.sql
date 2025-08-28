-- MySQL dump 10.13  Distrib 8.0.29, for Win64 (x86_64)
--
-- Host: localhost    Database: ecommerce_sl
-- ------------------------------------------------------
-- Server version	8.0.29

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
-- Table structure for table `cart`
--

DROP TABLE IF EXISTS `cart`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `cart` (
  `id` int NOT NULL AUTO_INCREMENT,
  `user_id` int NOT NULL,
  `product_id` int NOT NULL,
  `quantity` int NOT NULL DEFAULT '1',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `user_id` (`user_id`,`product_id`),
  KEY `product_id` (`product_id`),
  CONSTRAINT `cart_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `cart_ibfk_2` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=18 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `cart`
--

LOCK TABLES `cart` WRITE;
/*!40000 ALTER TABLE `cart` DISABLE KEYS */;
/*!40000 ALTER TABLE `cart` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `categories`
--

DROP TABLE IF EXISTS `categories`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `categories` (
  `id` int NOT NULL AUTO_INCREMENT,
  `name` varchar(50) COLLATE utf8mb4_general_ci NOT NULL,
  `slug` varchar(50) COLLATE utf8mb4_general_ci NOT NULL,
  `description` text COLLATE utf8mb4_general_ci,
  `image` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `slug` (`slug`)
) ENGINE=InnoDB AUTO_INCREMENT=8 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `categories`
--

LOCK TABLES `categories` WRITE;
/*!40000 ALTER TABLE `categories` DISABLE KEYS */;
INSERT INTO `categories` VALUES (1,'Men\'s Clothing','mens-clothing','Stylish clothing for men','mens-clothing.jpg','2025-07-05 23:27:05'),(2,'Women\'s Clothing','womens-clothing','Fashionable outfits for women','womens-clothing.jpg','2025-07-05 23:27:05'),(5,'Sportswear','sportswear','High-performance sportswear','traditional-wear.jpg','2025-07-05 23:27:05');
/*!40000 ALTER TABLE `categories` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `feedback`
--

DROP TABLE IF EXISTS `feedback`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `feedback` (
  `id` int NOT NULL AUTO_INCREMENT,
  `user_id` int DEFAULT NULL,
  `type` enum('Search','Chatbot','General','contact','feedback','suggestion','complaint') COLLATE utf8mb4_general_ci DEFAULT 'General',
  `message` text COLLATE utf8mb4_general_ci NOT NULL,
  `rating` int DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `name` varchar(255) COLLATE utf8mb4_general_ci NOT NULL DEFAULT '',
  `email` varchar(255) COLLATE utf8mb4_general_ci NOT NULL DEFAULT '',
  `subject` varchar(500) COLLATE utf8mb4_general_ci NOT NULL DEFAULT '',
  `status` enum('new','read','in_progress','resolved','closed') COLLATE utf8mb4_general_ci DEFAULT 'new',
  `admin_reply` text COLLATE utf8mb4_general_ci,
  `admin_user_id` int DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`),
  CONSTRAINT `feedback_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `feedback`
--

LOCK TABLES `feedback` WRITE;
/*!40000 ALTER TABLE `feedback` DISABLE KEYS */;
/*!40000 ALTER TABLE `feedback` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `order_items`
--

DROP TABLE IF EXISTS `order_items`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `order_items` (
  `id` int NOT NULL AUTO_INCREMENT,
  `order_id` int NOT NULL,
  `product_id` int NOT NULL,
  `quantity` int NOT NULL,
  `price` decimal(10,2) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `order_id` (`order_id`),
  KEY `product_id` (`product_id`),
  CONSTRAINT `order_items_ibfk_1` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE CASCADE,
  CONSTRAINT `order_items_ibfk_2` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `order_items`
--

LOCK TABLES `order_items` WRITE;
/*!40000 ALTER TABLE `order_items` DISABLE KEYS */;
INSERT INTO `order_items` VALUES (3,3,11,10,2150.00),(4,4,1,3,1999.00),(5,4,2,1,3500.00),(6,4,13,1,200.00);
/*!40000 ALTER TABLE `order_items` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `orders`
--

DROP TABLE IF EXISTS `orders`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `orders` (
  `id` int NOT NULL AUTO_INCREMENT,
  `user_id` int NOT NULL,
  `order_number` varchar(20) COLLATE utf8mb4_general_ci NOT NULL,
  `total_amount` decimal(10,2) NOT NULL,
  `status` enum('Pending','Processing','Shipped','Delivered','Cancelled') COLLATE utf8mb4_general_ci DEFAULT 'Pending',
  `payment_method` varchar(50) COLLATE utf8mb4_general_ci NOT NULL,
  `shipping_address` text COLLATE utf8mb4_general_ci NOT NULL,
  `contact_number` varchar(15) COLLATE utf8mb4_general_ci NOT NULL,
  `notes` text COLLATE utf8mb4_general_ci,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `order_number` (`order_number`),
  KEY `user_id` (`user_id`),
  CONSTRAINT `orders_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `orders`
--

LOCK TABLES `orders` WRITE;
/*!40000 ALTER TABLE `orders` DISABLE KEYS */;
INSERT INTO `orders` VALUES (3,1,'ORD-686B7E0E6BFC2',21500.00,'Pending','cod','Kugala, KUGALA, 60034','0771234568','','2025-07-07 07:58:06','2025-07-07 07:58:06'),(4,4,'ORD-68AFF1C2B0507',9697.00,'Pending','cod','33/5 Miriswatta,gampaha, Gampaha, 11715','0752957359','','2025-08-28 06:05:54','2025-08-28 06:05:54');
/*!40000 ALTER TABLE `orders` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `products`
--

DROP TABLE IF EXISTS `products`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `products` (
  `id` int NOT NULL AUTO_INCREMENT,
  `category_id` int DEFAULT NULL,
  `name` varchar(100) COLLATE utf8mb4_general_ci NOT NULL,
  `slug` varchar(100) COLLATE utf8mb4_general_ci NOT NULL,
  `description` text COLLATE utf8mb4_general_ci,
  `price` decimal(10,2) NOT NULL,
  `discount_price` decimal(10,2) DEFAULT NULL,
  `size` varchar(50) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `color` varchar(50) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `brand` varchar(50) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `occasion` varchar(50) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `gender` enum('Male','Female','Unisex') COLLATE utf8mb4_general_ci DEFAULT 'Unisex',
  `stock` int NOT NULL DEFAULT '0',
  `image1` varchar(255) COLLATE utf8mb4_general_ci NOT NULL,
  `image2` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `image3` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `slug` (`slug`),
  KEY `category_id` (`category_id`),
  CONSTRAINT `products_ibfk_1` FOREIGN KEY (`category_id`) REFERENCES `categories` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=14 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `products`
--

LOCK TABLES `products` WRITE;
/*!40000 ALTER TABLE `products` DISABLE KEYS */;
INSERT INTO `products` VALUES (1,1,'Men\'s Casual Shirt','mens-casual-shirt','Comfortable cotton shirt for casual wear',2500.00,1999.00,'M,L,XL','Blue','Cotton King','Casual','Male',47,'product_1751861652_686b4994b985f.jpg','shirt2.jpg','shirt3.jpg','2025-07-05 23:34:22','2025-08-28 06:05:54'),(2,1,'Men\'s Formal Trouser','mens-formal-trouser','Premium quality formal trousers',3500.00,NULL,'30,32,34','Black','FormalX','Formal','Male',28,'trouser1.jpg','trouser2.jpg',NULL,'2025-07-05 23:34:22','2025-08-28 06:05:54'),(3,2,'Women\'s Floral Dress','womens-floral-dress','Elegant floral print dress',4200.00,3599.00,'S,M,L','Multicolor','FashionLady','Party','Female',25,'dress1.jpg','dress2.jpg','dress3.jpg','2025-07-05 23:34:22','2025-07-06 20:50:27'),(4,2,'Women\'s Jeans','womens-jeans','Slim fit jeans for women',3800.00,NULL,'28,30,32','Blue','Denim Queen','Casual','Female',40,'jeans1.jpg','jeans2.jpg',NULL,'2025-07-05 23:34:22','2025-07-05 23:34:22'),(9,5,'Men\'s Sarong','mens-sarong','Traditional Sri Lankan sarong',1800.00,1500.00,'Free Size','White/Black','LankaStyle','Traditional','Male',45,'sarong1.jpg','sarong2.jpg',NULL,'2025-07-05 23:34:22','2025-07-05 23:34:22'),(10,5,'Women\'s Kandyan Saree','womens-kandyan-saree','Authentic Kandyan saree',8500.00,7999.00,'Free Size','Red/Gold','LankaStyle','Traditional','Female',18,'saree1.jpg','saree2.jpg','saree3.jpg','2025-07-05 23:34:22','2025-07-05 23:34:22'),(11,5,'Boxy Oversized Tee','boxy-oversized-tee','The Boxy Oversized Tee offers a relaxed, cropped fit for a modern streetwear look. Made from 180 GSM 100% cotton single jersey fabric, it provides a soft, breathable feel. The rib knit crew neck adds a classic touch, while the small F.O.A logo print on the front left side completes the design.\r\n\r\nThe male model is 5\'10\" and wears a size M, while the female model is 5\'4\" and wears a size S.\r\n\r\nProduct color may slightly vary due to photographic lighting sources or your monitor/device settings.',4300.00,2150.00,'S,M,L,XL','White, Black,Gray','FOA Clothing','','Unisex',10,'product_1751861987_686b4ae37c1b2.jpg','product_1751861987_686b4ae37c550.jpg','product_1751861987_686b4ae37c7bd.jpg','2025-07-07 04:19:47','2025-07-07 07:58:06'),(13,1,'Black Hoodies','black-hoodies','Black long sleeves hoodie for parties',2000.00,200.00,'L','Black','Carnage','Casual','Unisex',11,'product_1756359308_68afea8ca8f56.jpg',NULL,NULL,'2025-08-28 05:35:08','2025-08-28 06:05:54');
/*!40000 ALTER TABLE `products` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `search_activity_log`
--

DROP TABLE IF EXISTS `search_activity_log`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `search_activity_log` (
  `id` int NOT NULL AUTO_INCREMENT,
  `user_id` int NOT NULL,
  `search_query` text NOT NULL,
  `results_count` int DEFAULT '0',
  `response_time_seconds` decimal(5,2) DEFAULT NULL,
  `search_timestamp` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_user_timestamp` (`user_id`,`search_timestamp`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `search_activity_log`
--

LOCK TABLES `search_activity_log` WRITE;
/*!40000 ALTER TABLE `search_activity_log` DISABLE KEYS */;
INSERT INTO `search_activity_log` VALUES (1,124,'\n        INSERT INTO search_activity_log \n        (user_id, search_query, results_count, response_time_seconds)\n        VALUES (%s, %s, %s, %s)\n        ',3,3.12,'2025-08-20 23:27:28'),(2,125,'\n        INSERT INTO search_activity_log \n        (user_id, search_query, results_count, response_time_seconds)\n        VALUES (%s, %s, %s, %s)\n        ',3,2.67,'2025-08-20 23:27:31'),(3,126,'\n        INSERT INTO search_activity_log \n        (user_id, search_query, results_count, response_time_seconds)\n        VALUES (%s, %s, %s, %s)\n        ',4,2.84,'2025-08-20 23:27:34');
/*!40000 ALTER TABLE `search_activity_log` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `search_logs`
--

DROP TABLE IF EXISTS `search_logs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `search_logs` (
  `id` int NOT NULL AUTO_INCREMENT,
  `user_id` int DEFAULT NULL,
  `query` text COLLATE utf8mb4_general_ci NOT NULL,
  `results_count` int NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `enhanced_query` text COLLATE utf8mb4_general_ci,
  `processing_time` decimal(5,3) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`),
  CONSTRAINT `search_logs_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=16 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `search_logs`
--

LOCK TABLES `search_logs` WRITE;
/*!40000 ALTER TABLE `search_logs` DISABLE KEYS */;
INSERT INTO `search_logs` VALUES (1,1,'men\'s shirt',1,'2025-08-20 22:19:52','Based on the user\'s query and search history, the most relevant product IDs are:\n\n1, 1, 11 \n\nThese product IDs correspond to the Men\'s Casual Shirt (Product ID: 1) and the Boxy Oversized Tee (Product ID: 11), which are both relevant to the user\'s query \"men\'s shirt\".',6.227),(2,1,'men\'s shirt',2,'2025-08-20 22:20:15','Based on the user\'s query and search history, the most relevant product IDs are:\n\n1, 1, 11 \n\nThese product IDs correspond to the \"Men\'s Casual Shirt\" and \"Boxy Oversized Tee\" which are both men\'s shirts and relevant to the user\'s search history and current query.',2.864),(3,1,'women\'s dress red color',1,'2025-08-20 22:20:19','Based on the user\'s query \"women\'s dress red color\" and search history, I recommend the following product IDs:\n\n3, 10\n\nThese product IDs correspond to the Women\'s Floral Dress (Product ID: 3) and Women\'s Kandyan Saree (Product ID: 10), which are both dresses and match the user\'s query for a red color.',1.431),(4,1,'casual wear under 3000 rupees',7,'2025-08-20 22:20:23','Based on the user\'s query \"casual wear under 3000 rupees\" and their search history, I recommend the following product IDs:\n\n1, 2, 1, 4, 11, 3, 9, 1, 2, 11\n\nThese product IDs are relevant because they match the user\'s query and search history. Product IDs 1 and 2 are men\'s casual shirts and formal trousers, respectively, which match the user\'s search history for \"men\'s shirt\". Product ID 4 is a pair of women\'s jeans, which matches the user\'s query for casual wear. Product ID 11 is a boxy oversized tee, which is a casual wear item under 3000 rupees',1.021),(5,1,'formal trouser black',4,'2025-08-20 22:20:27','Based on the user\'s query \"formal trouser black\" and search history, the most relevant product IDs are:\n\n2, 1, 9\n\nThese product IDs correspond to the following products:\n\n- Product ID 2: Men\'s Formal Trouser (Black)\n- Product ID 1: Men\'s Casual Shirt (Blue) - Although the color is not black, it\'s a casual wear option under 3000 rupees, which is relevant to the user\'s search history.\n- Product ID 9: Men\'s Sarong (White/Black) - Although the color is not black, it\'s a traditional option that might be relevant to the user\'s query.\n\nNote that Product ID 9 is not a perfect match, but',1.019),(6,1,'saree traditional',2,'2025-08-20 22:20:30','Based on the user\'s query \"saree traditional\" and search history, the most relevant product IDs are:\n\n10, 9',0.960),(7,1,'oversized tee white',2,'2025-08-20 22:20:34','Based on the user\'s query \"oversized tee white\" and search history, I recommend the following product IDs:\n\n11, 9\n\nThese product IDs are relevant because they match the user\'s query for an \"oversized tee\" and are likely to be white in color. Product ID 11 is a Boxy Oversized Tee, and Product ID 9 is a Men\'s Sarong, which is a traditional Sri Lankan garment that may be white in color.',1.038),(8,1,'sportswear for men',2,'2025-08-20 22:20:37','Based on the user\'s query \"sportswear for men\" and search history, I recommend the following product IDs:\n\n11, 9\n\nThese product IDs correspond to the \"Boxy Oversized Tee\" and \"Men\'s Sarong\" products, which are directly related to the user\'s query and search history.',0.550),(9,1,'blue jeans women',4,'2025-08-20 22:20:41','Based on the user\'s query \"blue jeans women\" and search history, I recommend the following product IDs:\n\n4, 28, 30, 32 \n\nThese product IDs correspond to the Women\'s Jeans product, which matches the user\'s query.',0.621),(10,1,'party dress',3,'2025-08-20 22:20:45','Based on the user\'s query \"party dress\" and search history, the most relevant product IDs are:\n\n3, 4, 10\n\nThese product IDs correspond to the Women\'s Floral Dress (Product ID: 3), Women\'s Jeans (Product ID: 4), and Women\'s Kandyan Saree (Product ID: 10), which are all relevant to the user\'s query and search history.',1.739),(11,1,'affordable shirts',1,'2025-08-20 22:20:49','Based on the user\'s query \"affordable shirts\" and search history, I recommend the following product IDs:\n\n1, 1, 11 \n\nThese product IDs correspond to the most relevant products in the context: \n\n- Product ID 1: Men\'s Casual Shirt (affordable and a shirt)\n- Product ID 11: Boxy Oversized Tee (affordable and a shirt/tee)',0.653),(12,1,'casual blue shirt',3,'2025-08-20 22:36:23','Based on the user\'s query \"casual blue shirt\" and search history, the most relevant product IDs are:\n\n1, 11, 4, 1',2.360),(13,1,'casual blue shirt',3,'2025-08-20 23:19:12','Based on the user\'s query and search history, here are the most relevant product IDs:\n\n1, 1, 4, 3, 11 \n\nThese product IDs correspond to the following products:\n\n- Product ID: 1 (Men\'s Casual Shirt)\n- Product ID: 1 (Men\'s Casual Shirt) - Duplicate of the first result, but since it\'s the same product, it\'s included again\n- Product ID: 4 (Women\'s Jeans)\n- Product ID: 3 (Women\'s Floral Dress)\n- Product ID: 11 (Boxy Oversized Tee)\n\nThese products are relevant because they match the user\'s query for a \"casual blue shirt\" and are also related to the user\'s search history',1.111),(14,1,'casual blue shirt',2,'2025-08-20 23:21:23','Based on the user\'s query and search history, here are the most relevant product IDs:\n\n1, 1, 4 \n\nThese product IDs correspond to the following products:\n- Product ID: 1 | Men\'s Casual Shirt (Blue)\n- Product ID: 1 | Men\'s Casual Shirt (Blue) (Duplicate query, hence included)\n- Product ID: 4 | Women\'s Jeans (Blue)\n\nThese products match the user\'s query for a \"casual blue shirt\" and are relevant based on their search history for similar products.',0.768),(15,1,'blue shirt',2,'2025-08-28 06:10:50','Based on the user\'s query and search history, the most relevant product IDs are:\n\n1, 1, 4 \n\nThese product IDs correspond to the following products:\n- Product ID: 1 | Men\'s Casual Shirt\n- Product ID: 1 | Men\'s Casual Shirt (since it\'s the same product, it\'s listed twice due to the user\'s search history)\n- Product ID: 4 | Women\'s Jeans \n\nHowever, since the user\'s query is \"blue shirt\" and the search history is about casual blue shirts, I will prioritize the product IDs related to blue shirts. \n\nSo, the final list of product IDs is: 1, 1',1.185);
/*!40000 ALTER TABLE `search_logs` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `search_preferences_log`
--

DROP TABLE IF EXISTS `search_preferences_log`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `search_preferences_log` (
  `id` int NOT NULL AUTO_INCREMENT,
  `user_id` int DEFAULT NULL,
  `query` text,
  `preferences` json DEFAULT NULL,
  `result_count` int DEFAULT NULL,
  `processing_time` decimal(10,3) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `recommended_products` text,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `search_preferences_log`
--

LOCK TABLES `search_preferences_log` WRITE;
/*!40000 ALTER TABLE `search_preferences_log` DISABLE KEYS */;
INSERT INTO `search_preferences_log` VALUES (1,823873,'Men\'s Casual Shirt','{}',NULL,NULL,'2025-08-21 02:24:26','[1, 11, 9]'),(2,823873,'Men\'s Casual Shirt','{}',NULL,NULL,'2025-08-21 02:24:47','[1, 11, 9]'),(3,777403,'short frock','{}',NULL,NULL,'2025-08-21 02:25:38','[3, 7]'),(4,755884,'frock','{}',NULL,NULL,'2025-08-21 02:26:01','[3]'),(5,919717,'give official wears','{}',NULL,NULL,'2025-08-21 02:27:06','[2, 1, 3, 4, 6]'),(6,300629,'black color budget 2000 t shirt','{}',NULL,NULL,'2025-08-21 02:41:10','[2000, 1, 2, 9]');
/*!40000 ALTER TABLE `search_preferences_log` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `user_preferences`
--

DROP TABLE IF EXISTS `user_preferences`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `user_preferences` (
  `id` int NOT NULL AUTO_INCREMENT,
  `user_id` int NOT NULL,
  `style_preferences` json DEFAULT NULL,
  `color_preferences` json DEFAULT NULL,
  `budget_min` decimal(10,2) DEFAULT NULL,
  `budget_max` decimal(10,2) DEFAULT NULL,
  `occasion` varchar(100) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_user` (`user_id`)
) ENGINE=InnoDB AUTO_INCREMENT=14 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `user_preferences`
--

LOCK TABLES `user_preferences` WRITE;
/*!40000 ALTER TABLE `user_preferences` DISABLE KEYS */;
INSERT INTO `user_preferences` VALUES (1,124,'[\"formal\", \"business\"]','[\"blue\", \"white\", \"black\"]',2000.00,8000.00,'office','2025-08-20 23:27:24','2025-08-20 23:27:24'),(2,125,'[\"party\", \"western\", \"trendy\"]','[\"red\", \"black\", \"gold\"]',5000.00,15000.00,'party','2025-08-20 23:27:29','2025-08-20 23:27:29'),(3,126,'[\"casual\", \"comfort\"]','[\"blue\", \"grey\", \"black\"]',800.00,3500.00,'casual','2025-08-20 23:27:31','2025-08-20 23:27:31'),(4,991890,'[]','[]',1000.00,10000.00,'casual','2025-08-21 00:26:14','2025-08-21 00:26:24'),(5,232144,'[\"party\"]','[]',1000.00,10000.00,'office','2025-08-21 00:26:51','2025-08-21 00:26:59'),(6,966027,'[\"business\", \"western\"]','[\"green\"]',1000.00,10000.00,'office','2025-08-21 00:31:13','2025-08-21 00:36:25'),(7,112503,'[\"ethnic\"]','[\"black\"]',1000.00,10000.00,'office','2025-08-21 00:46:26','2025-08-21 00:46:26'),(8,2284,'[]','[]',2500.00,4500.00,'office','2025-08-21 01:01:04','2025-08-21 01:01:05'),(9,241174,'[\"formal\"]','[\"green\"]',1000.00,5000.00,'office','2025-08-21 01:12:19','2025-08-21 01:12:19'),(10,606882,'[\"formal\"]','[]',1000.00,7000.00,'office','2025-08-21 01:19:24','2025-08-21 01:48:00'),(11,859262,'[\"business\"]','[\"blue\"]',1000.00,8000.00,'office','2025-08-21 02:27:48','2025-08-21 02:27:48'),(12,999999,'[\"casual\"]','[\"blue\"]',1000.00,5000.00,'office','2025-08-21 02:30:30','2025-08-21 02:33:15'),(13,300629,'[\"business\"]','[\"blue\"]',1000.00,5500.00,'office','2025-08-21 02:40:41','2025-08-21 02:40:41');
/*!40000 ALTER TABLE `user_preferences` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `user_search_history`
--

DROP TABLE IF EXISTS `user_search_history`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `user_search_history` (
  `id` int NOT NULL AUTO_INCREMENT,
  `user_id` int NOT NULL,
  `search_query` text NOT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_user_search_history_user_id` (`user_id`),
  KEY `idx_user_search_history_created_at` (`created_at`),
  CONSTRAINT `user_search_history_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=16 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `user_search_history`
--

LOCK TABLES `user_search_history` WRITE;
/*!40000 ALTER TABLE `user_search_history` DISABLE KEYS */;
INSERT INTO `user_search_history` VALUES (1,1,'men\'s shirt','2025-08-20 22:19:47'),(2,1,'men\'s shirt','2025-08-20 22:20:14'),(3,1,'women\'s dress red color','2025-08-20 22:20:18'),(4,1,'casual wear under 3000 rupees','2025-08-20 22:20:22'),(5,1,'formal trouser black','2025-08-20 22:20:26'),(6,1,'saree traditional','2025-08-20 22:20:29'),(7,1,'oversized tee white','2025-08-20 22:20:33'),(8,1,'sportswear for men','2025-08-20 22:20:37'),(9,1,'blue jeans women','2025-08-20 22:20:40'),(10,1,'party dress','2025-08-20 22:20:43'),(11,1,'affordable shirts','2025-08-20 22:20:48'),(12,1,'casual blue shirt','2025-08-20 22:36:20'),(13,1,'casual blue shirt','2025-08-20 23:19:11'),(14,1,'casual blue shirt','2025-08-20 23:21:22'),(15,1,'blue shirt','2025-08-28 06:10:49');
/*!40000 ALTER TABLE `user_search_history` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `users`
--

DROP TABLE IF EXISTS `users`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `users` (
  `id` int NOT NULL AUTO_INCREMENT,
  `name` varchar(100) COLLATE utf8mb4_general_ci NOT NULL,
  `email` varchar(100) COLLATE utf8mb4_general_ci NOT NULL,
  `password` varchar(255) COLLATE utf8mb4_general_ci NOT NULL,
  `role` enum('user','admin') COLLATE utf8mb4_general_ci DEFAULT 'user',
  `phone` varchar(15) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `address` text COLLATE utf8mb4_general_ci,
  `city` varchar(50) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `postal_code` varchar(10) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `status` enum('active','inactive') COLLATE utf8mb4_general_ci DEFAULT 'active',
  PRIMARY KEY (`id`),
  UNIQUE KEY `email` (`email`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `users`
--

LOCK TABLES `users` WRITE;
/*!40000 ALTER TABLE `users` DISABLE KEYS */;
INSERT INTO `users` VALUES (1,'Menad Bandara','menad@gmail.com','$2y$10$lQ9Ah5Y7PF9oDyZpLn4/0.qo/6H.xnh2YuRpzdDCg4w/9AGp0kvo6','user','0771234568','Kugala','KUGALA','60034','2025-07-06 06:52:44','2025-07-07 05:21:48','active'),(2,'Admin User','admin@lankafashion.com','$2y$10$HfzIhGCCaxqyahetQmlaL.4Q9g/UGpJAEfgnXzqinzANr2SvKuoxm','admin',NULL,NULL,NULL,NULL,'2025-07-06 17:49:34','2025-07-07 05:17:18','inactive'),(3,'Admin','admin1@gmail.com','$2y$10$0PtjjHpP50fKr3VaUa7XGedebFV7D7ngLgvCooF3FM9T5rZJmuggq','admin','0721236547',NULL,NULL,NULL,'2025-07-07 05:20:31','2025-07-07 05:20:55','active'),(4,'Mihisara Nithadya perera','mihisaranithadya148i@gmail.com','$2y$10$DU7GXKcqobzb0mWNa2A7Vuz4n40mQ2iUB8PjIMNWz/G6/82rF4PCe','user','+94752957359',NULL,NULL,NULL,'2025-08-20 23:23:28','2025-08-20 23:23:28','active');
/*!40000 ALTER TABLE `users` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `wishlist`
--

DROP TABLE IF EXISTS `wishlist`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `wishlist` (
  `id` int NOT NULL AUTO_INCREMENT,
  `user_id` int NOT NULL,
  `product_id` int NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `user_id` (`user_id`,`product_id`),
  KEY `product_id` (`product_id`),
  CONSTRAINT `wishlist_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `wishlist_ibfk_2` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `wishlist`
--

LOCK TABLES `wishlist` WRITE;
/*!40000 ALTER TABLE `wishlist` DISABLE KEYS */;
INSERT INTO `wishlist` VALUES (1,1,3,'2025-07-06 10:15:05'),(2,1,11,'2025-07-07 06:50:17'),(3,4,13,'2025-08-28 06:05:27');
/*!40000 ALTER TABLE `wishlist` ENABLE KEYS */;
UNLOCK TABLES;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2025-08-28 11:45:21
