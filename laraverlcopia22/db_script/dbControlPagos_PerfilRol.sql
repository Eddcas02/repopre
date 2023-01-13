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
-- Table structure for table `PerfilRol`
--

DROP TABLE IF EXISTS `PerfilRol`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `PerfilRol` (
  `id_perfilrol` int NOT NULL AUTO_INCREMENT,
  `id_perfil` int DEFAULT NULL,
  `id_rol` int DEFAULT NULL,
  `activo` tinyint(1) DEFAULT NULL,
  `eliminado` tinyint(1) DEFAULT NULL,
  PRIMARY KEY (`id_perfilrol`),
  KEY `perfil` (`id_perfil`),
  KEY `rol` (`id_rol`)
) ENGINE=MyISAM AUTO_INCREMENT=48 DEFAULT CHARSET=utf8mb3 COLLATE=utf8_spanish_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `PerfilRol`
--

LOCK TABLES `PerfilRol` WRITE;
/*!40000 ALTER TABLE `PerfilRol` DISABLE KEYS */;
INSERT INTO `PerfilRol` VALUES (1,1,1,1,0),(2,1,2,1,0),(3,1,3,0,0),(4,1,4,0,0),(5,1,5,0,0),(6,4,7,1,0),(7,2,1,1,0),(8,5,11,1,1),(9,5,12,1,1),(10,5,13,1,1),(11,2,2,1,0),(12,2,3,1,0),(13,2,4,1,0),(14,2,5,1,0),(15,2,6,1,0),(16,2,7,1,0),(17,2,8,1,0),(18,2,9,1,0),(19,2,10,1,0),(20,2,11,1,0),(21,2,12,1,0),(22,2,13,1,0),(23,2,14,1,0),(24,2,15,1,0),(25,2,16,1,0),(26,3,10,1,0),(27,6,17,1,0),(28,2,17,1,0),(29,1,18,1,0),(30,9,14,1,0),(31,5,15,1,0),(32,10,19,1,0),(33,11,20,1,0),(34,11,21,1,0),(35,12,22,1,0),(36,12,23,1,0),(37,5,23,1,1),(38,5,24,1,1),(39,13,26,1,0),(40,14,27,1,0),(41,5,28,1,0),(42,15,29,1,0),(43,1,30,1,0),(44,1,31,1,0),(45,2,32,1,0),(46,16,33,1,0),(47,1,34,1,0);
/*!40000 ALTER TABLE `PerfilRol` ENABLE KEYS */;
UNLOCK TABLES;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2022-04-19 11:49:23
