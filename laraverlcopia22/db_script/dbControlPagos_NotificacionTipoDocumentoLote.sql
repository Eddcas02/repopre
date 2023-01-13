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
-- Table structure for table `NotificacionTipoDocumentoLote`
--

DROP TABLE IF EXISTS `NotificacionTipoDocumentoLote`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `NotificacionTipoDocumentoLote` (
  `id_notificaciontipodocumentolote` int NOT NULL AUTO_INCREMENT,
  `id_usuarionotificaciontransaccion` int NOT NULL,
  `id_tipodocumentolote` int NOT NULL,
  `Activo` tinyint DEFAULT '1',
  `Eliminado` tinyint DEFAULT '0',
  PRIMARY KEY (`id_notificaciontipodocumentolote`),
  KEY `id_usuarionotificaciontransaccion_idx` (`id_usuarionotificaciontransaccion`),
  KEY `id_tipodocumentolote_idx` (`id_tipodocumentolote`),
  CONSTRAINT `id_tipodocumentolote` FOREIGN KEY (`id_tipodocumentolote`) REFERENCES `TipoDocumentoLote` (`id_tipodocumentolote`),
  CONSTRAINT `id_usuarionotificaciontransaccion` FOREIGN KEY (`id_usuarionotificaciontransaccion`) REFERENCES `UsuarioNotificacionTransaccion` (`id_usuarionotificaciontransaccion`)
) ENGINE=InnoDB AUTO_INCREMENT=27 DEFAULT CHARSET=utf8mb3 COLLATE=utf8_spanish_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `NotificacionTipoDocumentoLote`
--

LOCK TABLES `NotificacionTipoDocumentoLote` WRITE;
/*!40000 ALTER TABLE `NotificacionTipoDocumentoLote` DISABLE KEYS */;
INSERT INTO `NotificacionTipoDocumentoLote` VALUES (7,13,1,1,0),(8,14,2,1,0),(9,15,1,1,0),(10,13,2,1,0),(11,14,1,1,0),(12,15,2,1,0),(13,16,1,1,0),(14,16,2,1,0),(15,17,1,1,0),(16,17,2,1,0),(17,18,1,1,0),(18,18,2,1,0),(19,19,1,1,0),(20,19,2,1,0),(21,20,1,1,0),(22,20,2,1,0),(23,21,1,1,0),(24,21,2,1,0),(25,22,1,1,0),(26,22,2,1,0);
/*!40000 ALTER TABLE `NotificacionTipoDocumentoLote` ENABLE KEYS */;
UNLOCK TABLES;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2022-04-19 11:48:11
