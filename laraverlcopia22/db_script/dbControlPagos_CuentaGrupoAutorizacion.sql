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
-- Table structure for table `CuentaGrupoAutorizacion`
--

DROP TABLE IF EXISTS `CuentaGrupoAutorizacion`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `CuentaGrupoAutorizacion` (
  `id_cuentagrupo` int NOT NULL AUTO_INCREMENT,
  `id_grupoautorizacion` int NOT NULL,
  `CodigoCuenta` varchar(10) COLLATE utf8_spanish_ci NOT NULL,
  `activo` tinyint NOT NULL DEFAULT '1',
  `eliminado` tinyint NOT NULL DEFAULT '0',
  PRIMARY KEY (`id_cuentagrupo`),
  KEY `id_grupoautorizacion_idx` (`id_grupoautorizacion`),
  CONSTRAINT `id_grupoautorizacion` FOREIGN KEY (`id_grupoautorizacion`) REFERENCES `GrupoAutorizacion` (`id_grupoautorizacion`)
) ENGINE=InnoDB AUTO_INCREMENT=45 DEFAULT CHARSET=utf8mb3 COLLATE=utf8_spanish_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `CuentaGrupoAutorizacion`
--

LOCK TABLES `CuentaGrupoAutorizacion` WRITE;
/*!40000 ALTER TABLE `CuentaGrupoAutorizacion` DISABLE KEYS */;
INSERT INTO `CuentaGrupoAutorizacion` VALUES (1,7,'11251002',1,0),(2,7,'11251003',1,0),(3,7,'11253005',1,0),(4,7,'11253002',1,0),(5,5,'11254003',1,0),(6,7,'11254009',1,0),(7,7,'11299002',1,0),(8,5,'11299003',1,0),(9,7,'21203001',1,0),(10,5,'21203004',1,0),(11,5,'21203005',1,0),(12,7,'21205001',1,0),(13,7,'21205002',1,0),(14,7,'21205003',1,0),(15,7,'21205004',1,0),(16,7,'21205005',1,0),(17,7,'21205006',1,0),(18,7,'21205007',1,0),(19,7,'21205008',1,0),(20,5,'21205012',1,0),(21,7,'21205013',1,0),(22,7,'21205014',1,0),(23,5,'21205015',1,0),(24,5,'21205016',1,0),(25,5,'21301001',1,0),(26,5,'21301002',1,0),(27,5,'21301003',1,0),(28,5,'21301004',1,0),(29,5,'21301005',1,0),(30,5,'21301006',1,0),(31,5,'21301010',1,0),(32,5,'21302001',1,0),(33,5,'21302002',1,0),(34,5,'21302003',1,0),(35,5,'21302005',1,0),(36,5,'21302006',1,0),(37,5,'21302007',1,0),(38,5,'21302020',1,0),(39,8,'21501001',1,0),(40,8,'21501002',1,0),(41,8,'21501003',1,0),(42,8,'22101003',1,0);
/*!40000 ALTER TABLE `CuentaGrupoAutorizacion` ENABLE KEYS */;
UNLOCK TABLES;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2022-04-19 11:48:58
