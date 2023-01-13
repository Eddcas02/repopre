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
-- Table structure for table `UsuarioGrupoAutorizacion`
--

DROP TABLE IF EXISTS `UsuarioGrupoAutorizacion`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `UsuarioGrupoAutorizacion` (
  `id_usuariogrupo` int NOT NULL AUTO_INCREMENT,
  `id_usuario` int DEFAULT NULL,
  `id_grupoautorizacion` int DEFAULT NULL,
  `nivel` int DEFAULT NULL,
  `activo` tinyint(1) DEFAULT NULL,
  `eliminado` tinyint(1) DEFAULT NULL,
  PRIMARY KEY (`id_usuariogrupo`),
  KEY `usuario` (`id_usuario`),
  KEY `grupo` (`id_grupoautorizacion`)
) ENGINE=MyISAM AUTO_INCREMENT=47 DEFAULT CHARSET=utf8mb3 COLLATE=utf8_spanish_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `UsuarioGrupoAutorizacion`
--

LOCK TABLES `UsuarioGrupoAutorizacion` WRITE;
/*!40000 ALTER TABLE `UsuarioGrupoAutorizacion` DISABLE KEYS */;
INSERT INTO `UsuarioGrupoAutorizacion` VALUES (1,2,1,1,1,0),(2,1,1,2,1,0),(3,10,1,3,1,0),(4,6,1,4,1,0),(7,1,2,2,1,0),(8,10,2,3,1,0),(9,4,1,4,1,0),(10,13,3,1,1,0),(11,14,3,2,1,0),(12,15,3,3,1,0),(13,2,3,1,1,0),(14,4,3,2,1,1),(15,4,3,3,1,0),(16,5,1,1,1,0),(17,5,3,1,1,0),(18,19,3,1,1,0),(19,5,4,1,1,0),(20,20,5,1,1,0),(21,21,6,1,1,0),(22,22,7,1,1,0),(23,23,8,1,1,0),(24,24,4,2,1,0),(25,24,5,2,1,0),(26,24,6,2,1,0),(27,24,7,2,1,0),(28,24,8,2,1,0),(29,33,1,1,1,1),(30,33,7,1,1,1),(31,33,9,1,1,0),(32,35,9,2,1,0),(33,32,9,2,1,1),(34,32,4,1,1,1),(35,32,1,1,1,1),(36,33,7,1,1,0),(37,37,9,1,0,0),(38,2,9,1,1,0),(39,31,9,1,1,0),(40,28,9,2,1,0),(41,2,4,1,1,0),(42,2,5,1,1,0),(43,32,9,2,1,0),(44,32,9,1,1,0),(45,32,9,2,1,0),(46,42,9,2,1,0);
/*!40000 ALTER TABLE `UsuarioGrupoAutorizacion` ENABLE KEYS */;
UNLOCK TABLES;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2022-04-19 11:47:46
