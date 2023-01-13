-- MySQL dump 10.13  Distrib 8.0.26, for macos11 (x86_64)
--
-- Host: 34.208.193.210    Database: dbControlPagos
-- ------------------------------------------------------
-- Server version	8.0.27

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
-- Table structure for table `RolPermiso`
--

DROP TABLE IF EXISTS `RolPermiso`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `RolPermiso` (
  `id_rolpermiso` int NOT NULL AUTO_INCREMENT,
  `id_rol` int DEFAULT NULL,
  `id_permiso` int DEFAULT NULL,
  `activo` tinyint(1) DEFAULT NULL,
  `eliminado` tinyint(1) DEFAULT NULL,
  PRIMARY KEY (`id_rolpermiso`),
  KEY `rol` (`id_rol`),
  KEY `permiso` (`id_permiso`)
) ENGINE=MyISAM AUTO_INCREMENT=103 DEFAULT CHARSET=utf8mb3 COLLATE=utf8_spanish_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `RolPermiso`
--

LOCK TABLES `RolPermiso` WRITE;
/*!40000 ALTER TABLE `RolPermiso` DISABLE KEYS */;
INSERT INTO `RolPermiso` VALUES (1,1,1,1,0),(2,1,2,1,0),(3,1,3,1,0),(4,1,4,1,0),(5,1,5,1,0),(6,7,1,1,0),(7,7,2,1,0),(8,7,4,1,0),(9,7,6,1,0),(10,7,5,1,0),(11,2,1,1,0),(12,2,2,1,0),(13,2,3,1,0),(14,2,4,1,0),(15,2,5,1,0),(16,3,1,1,0),(17,3,2,1,0),(18,3,3,1,0),(19,3,4,1,0),(20,3,5,1,0),(21,4,1,1,0),(22,4,2,1,0),(23,4,3,1,0),(24,4,4,1,0),(25,4,5,1,0),(26,5,1,1,0),(27,5,2,1,0),(28,5,3,1,0),(29,5,4,1,0),(30,5,5,1,0),(31,11,1,1,0),(32,11,2,1,0),(33,11,3,1,0),(34,11,4,1,0),(35,11,5,1,0),(36,12,1,1,0),(37,12,2,1,0),(38,12,3,1,0),(39,12,4,1,0),(40,12,5,1,0),(41,13,1,1,0),(42,13,2,1,0),(43,13,3,1,0),(44,13,4,1,0),(45,13,5,1,0),(46,8,1,1,0),(47,8,2,1,0),(48,8,3,1,0),(49,8,4,1,0),(50,8,5,1,0),(51,9,1,1,0),(52,9,2,1,0),(53,9,3,1,0),(54,9,4,1,0),(55,9,5,1,0),(56,10,7,1,0),(57,17,6,1,0),(58,6,1,1,0),(59,6,2,1,0),(60,6,3,1,0),(61,6,4,1,0),(62,6,6,1,0),(63,15,4,1,0),(64,15,1,1,0),(65,18,4,1,0),(66,18,5,1,0),(67,14,8,1,0),(68,19,4,1,0),(69,19,5,1,0),(70,20,5,1,0),(71,21,7,1,0),(72,22,11,1,0),(73,23,12,1,0),(74,24,5,1,0),(75,24,11,1,0),(76,24,1,1,1),(77,24,8,1,0),(78,24,9,1,0),(79,15,9,1,0),(80,26,13,1,0),(81,27,14,1,0),(82,28,12,1,0),(83,29,15,1,0),(84,26,1,1,0),(85,26,3,1,0),(86,26,4,1,0),(87,26,5,1,0),(88,30,1,1,0),(89,30,2,1,0),(90,30,3,1,0),(91,30,4,1,0),(92,31,1,1,0),(93,31,2,1,0),(94,31,3,1,0),(95,31,4,1,0),(96,32,16,1,0),(97,33,8,1,0),(98,33,11,1,0),(99,34,1,1,0),(100,34,2,1,0),(101,34,3,1,0),(102,34,4,1,0);
/*!40000 ALTER TABLE `RolPermiso` ENABLE KEYS */;
UNLOCK TABLES;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2022-04-19 11:46:31
