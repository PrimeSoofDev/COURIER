-- MariaDB dump 10.19  Distrib 10.4.32-MariaDB, for Win64 (AMD64)
--
-- Host: localhost    Database: gaatitrack
-- ------------------------------------------------------
-- Server version	10.4.32-MariaDB

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;

--
-- Table structure for table `branches`
--

DROP TABLE IF EXISTS `branches`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `branches` (
  `id` int(30) NOT NULL AUTO_INCREMENT,
  `branch_code` varchar(50) NOT NULL,
  `street` text NOT NULL,
  `city` text NOT NULL,
  `state` text NOT NULL,
  `zip_code` varchar(50) NOT NULL,
  `country` text NOT NULL,
  `contact` varchar(100) NOT NULL,
  `date_created` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=13 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `branches`
--

LOCK TABLES `branches` WRITE;
/*!40000 ALTER TABLE `branches` DISABLE KEYS */;
INSERT INTO `branches` VALUES (4,'dIbUK5mEh96f0Zc','Sample','Sample','Sample','123456','Philippines','123456','2023-11-27 13:31:49'),(5,'ugjU3qv4QOVd5J8','Shivaji Nagar','Nashik','MH','422201','India','9090909090','2023-07-31 07:30:11'),(6,'SouWaBUl38xMpIK','pune','pune','maharshtra','424002','India','8585858585','2023-08-02 18:33:35'),(9,'PRmKESVF4tWCzl2',' Shankar Bhawan','Pune','maharshtra','411038','India','9090909090','2023-08-03 13:04:18'),(10,'FDB7N4z0CnQr2Ky','2nd Floor, Opposite Hotel Central Park, Apte Road, near HDFC Bank, Deccan Gymkhana, Pune,','pune','maharshtra','424002','India','7070707070','2023-08-03 13:07:25'),(11,'Fe8czxW94PY2lwq',' 759/8, Off, Prabhat Road, next to Sahyadri Hospital, Deccan Gymkhana','pune','Maharashtra',' 411004','India','6060707070','2023-08-03 13:08:24'),(12,'SVYGbLwdW7em3zh','Shankar Bhawan, 2nd Floor, Ravi Shankar Marg, Kothrud, Pune','pune','Maharashtra',' 411038','India','9090909090','2023-08-03 14:40:41');
/*!40000 ALTER TABLE `branches` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `parcel_tracks`
--

DROP TABLE IF EXISTS `parcel_tracks`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `parcel_tracks` (
  `id` int(30) NOT NULL AUTO_INCREMENT,
  `parcel_id` int(30) NOT NULL,
  `status` int(2) NOT NULL,
  `date_created` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=13 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `parcel_tracks`
--

LOCK TABLES `parcel_tracks` WRITE;
/*!40000 ALTER TABLE `parcel_tracks` DISABLE KEYS */;
INSERT INTO `parcel_tracks` VALUES (1,2,1,'2023-11-27 09:53:27'),(2,3,1,'2023-11-27 09:55:17'),(3,1,1,'2023-11-27 10:28:01'),(4,1,2,'2023-11-27 10:28:10'),(5,1,3,'2023-11-27 10:28:16'),(6,1,4,'2023-11-27 11:05:03'),(7,1,5,'2023-11-27 11:05:17'),(8,1,7,'2023-11-27 11:05:26'),(9,3,2,'2023-11-27 11:05:41'),(10,6,1,'2023-11-27 14:06:57'),(11,20,4,'2023-08-11 21:36:28'),(12,18,5,'2023-08-11 21:36:36');
/*!40000 ALTER TABLE `parcel_tracks` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `parcels`
--

DROP TABLE IF EXISTS `parcels`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `parcels` (
  `id` int(30) NOT NULL AUTO_INCREMENT,
  `reference_number` varchar(100) NOT NULL,
  `sender_name` text NOT NULL,
  `sender_address` text NOT NULL,
  `sender_contact` text NOT NULL,
  `recipient_name` text NOT NULL,
  `recipient_address` text NOT NULL,
  `recipient_contact` text NOT NULL,
  `type` int(1) NOT NULL COMMENT '1 = Deliver, 2=Pickup',
  `from_branch_id` varchar(30) NOT NULL,
  `to_branch_id` varchar(30) NOT NULL,
  `weight` varchar(100) NOT NULL,
  `height` varchar(100) NOT NULL,
  `width` varchar(100) NOT NULL,
  `length` varchar(100) NOT NULL,
  `price` float NOT NULL,
  `status` int(2) NOT NULL DEFAULT 0,
  `date_created` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=21 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `parcels`
--

LOCK TABLES `parcels` WRITE;
/*!40000 ALTER TABLE `parcels` DISABLE KEYS */;
INSERT INTO `parcels` VALUES (1,'201406231415','John Smith','Sample','+123456','Claire Blake','Sample','Sample',1,'1','0','30kg','12in','12in','15in',2500,7,'2023-11-26 16:15:46'),(3,'983186540795','John Smith','Sample','+123456','Claire Blake','Sample','Sample',2,'1','3','20Kg','10in','10in','10in',1500,2,'2023-11-26 16:46:03'),(10,'798438738398',' Mayuri',' Shankar Bhawan, 2nd Floor, Ravi Shankar Marg, Kothrud, Pune, Maharashtra 411038','9090909090','k',' Shankar Bhawan, 2nd Floor, Ravi Shankar Marg, Kothrud, Pune, Maharashtra 411038','9090909090',2,'5','9','45','120','677','670',800,0,'2023-08-03 13:14:13'),(11,'701589720901','Radhika','2nd Floor, Opposite Hotel Central Park, Apte Road, near HDFC Bank, Deccan Gymkhana, Pune, Maharashtra 411005   ','7070707070','Gandhi','2nd Floor, Opposite Hotel Central Park, Apte Road, near HDFC Bank, Deccan Gymkhana, Pune, Maharashtra 411005   ','7070707070',2,'5','11','55','160','455','555',7000,0,'2023-08-03 13:18:45'),(12,'618331985221','Rakesh','759/8, Off, Prabhat Road, next to Sahyadri Hospital, Deccan Gymkhana, Pune, Maharashtra 411004','6060707070','Jain','759/8, Off, Prabhat Road, next to Sahyadri Hospital, Deccan Gymkhana, Pune, Maharashtra 411004','6060707070',2,'5','','45','160','455','670',6000,0,'2023-08-03 13:20:55'),(13,'275728717086','Radhika',' Shankar Bhawan, 2nd Floor, Ravi Shankar Marg, Kothrud, Pune, Maharashtra 411038','9090909090','Gandhi',' Shankar Bhawan, 2nd Floor, Ravi Shankar Marg, Kothrud, Pune, Maharashtra 411038','9090909090',2,'9','9','45','120','677','67',6000,0,'2023-08-03 13:22:09'),(14,'753697280349','Mayuri ','Shankar Bhawan, 2nd Floor, Ravi Shankar Marg, Kothrud, Pune, Maharashtra 411038',' 9090909090','k','Shankar Bhawan, 2nd Floor, Ravi Shankar Marg, Kothrud, Pune, Maharashtra 411038',' 9090909090',2,'6','10','45','160','677','670',7000,0,'2023-08-03 13:27:19'),(15,'667392903463','Radhika ','2nd Floor, Opposite Hotel Central Park, Apte Road, near HDFC Bank, Deccan Gymkhana, Pune,','7070707070','Gandhi','2nd Floor, Opposite Hotel Central Park, Apte Road, near HDFC Bank, Deccan Gymkhana, Pune,','7070707070',2,'6','4','45','160','455','555',7000,0,'2023-08-03 13:33:33'),(16,'429522865395','mayuri','Shankar Bhawan, 2nd Floor, Ravi Shankar Marg, Kothrud, Pune, Maharashtra 411038','9090909090','k','Shankar Bhawan, 2nd Floor, Ravi Shankar Marg, Kothrud, Pune, Maharashtra 411038','9090909090',2,'6','6','45','120','677','67',7000,0,'2023-08-03 13:34:34'),(17,'726627862410','mayuri','Shankar Bhawan, 2nd Floor, Ravi Shankar Marg, Kothrud, Pune, Maharashtra 411038','9090909090','k','Shankar Bhawan, 2nd Floor, Ravi Shankar Marg, Kothrud, Pune, Maharashtra 411038','9090909090',2,'6','5','45','120','677','67',6000,0,'2023-08-03 14:27:17'),(18,'026242450983','Radhika','Shankar Bhawan, 2nd Floor, Ravi Shankar Marg, Kothrud, Pune, Maharashtra 411038','7070707070','Gandhi','Shankar Bhawan, 2nd Floor, Ravi Shankar Marg, Kothrud, Pune, Maharashtra 411038','7070707070',2,'6','9','45','120','677','670',5000,5,'2023-08-03 14:29:54'),(19,'923246250964','Radhika','2nd Floor, Opposite Hotel Central Park, Apte Road, near HDFC Bank, Deccan Gymkhana, Pune,','7070707070','Gandhi','2nd Floor, Opposite Hotel Central Park, Apte Road, near HDFC Bank, Deccan Gymkhana, Pune,','7070707070',2,'6','9','45','160','455','670',6000,0,'2023-08-03 14:33:19'),(20,'199897593227','Radhika','2nd Floor, Opposite Hotel Central Park, Apte Road, near HDFC Bank, Deccan Gymkhana, Pune,','7070707070','Gandhi','2nd Floor, Opposite Hotel Central Park, Apte Road, near HDFC Bank, Deccan Gymkhana, Pune,','7070707070',2,'5','9','45','160','455','555',7000,4,'2023-08-03 14:45:56');
/*!40000 ALTER TABLE `parcels` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `system_settings`
--

DROP TABLE IF EXISTS `system_settings`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `system_settings` (
  `id` int(30) NOT NULL AUTO_INCREMENT,
  `name` text NOT NULL,
  `email` varchar(200) NOT NULL,
  `contact` varchar(20) NOT NULL,
  `address` text NOT NULL,
  `cover_img` text NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `system_settings`
--

LOCK TABLES `system_settings` WRITE;
/*!40000 ALTER TABLE `system_settings` DISABLE KEYS */;
INSERT INTO `system_settings` VALUES (1,'Courier Management System','mayuri.infospace@gmail.com','+919000090000','8, Shivaji Nagar, Pune, Maharashtra, India','');
/*!40000 ALTER TABLE `system_settings` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `users`
--

DROP TABLE IF EXISTS `users`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `users` (
  `id` int(30) NOT NULL AUTO_INCREMENT,
  `firstname` varchar(200) NOT NULL,
  `lastname` varchar(200) NOT NULL,
  `email` varchar(200) NOT NULL,
  `password` text NOT NULL,
  `type` tinyint(1) NOT NULL DEFAULT 2 COMMENT '1 = admin, 2 = staff',
  `branch_id` int(30) NOT NULL,
  `date_created` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=8 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `users`
--

LOCK TABLES `users` WRITE;
/*!40000 ALTER TABLE `users` DISABLE KEYS */;
INSERT INTO `users` VALUES (1,'Mayuri K','K','mayuri.infospace@gmail.com','21232f297a57a5a743894a0e4a801fc3',1,0,'2023-11-26 10:57:04'),(4,'Suhit','Chavan','suhit@gmail.com','e35b98bdf7f7b2ec50eaab211dd950de',2,5,'2023-07-31 07:30:37'),(5,'mayuri','Kale','mayuri@admin.com','21232f297a57a5a743894a0e4a801fc3',2,5,'2023-08-02 18:34:44'),(6,'Nilesh','Chaure','n@admin.com','0192023a7bbd73250516f069df18b500',2,6,'2023-08-02 19:47:31'),(7,'Mayuri','K','mayuri1@gmail.com','cd92a26534dba48cd785cdcc0b3e6bd1',2,9,'2023-08-03 13:09:32');
/*!40000 ALTER TABLE `users` ENABLE KEYS */;
UNLOCK TABLES;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2026-10-08 19:57:32
