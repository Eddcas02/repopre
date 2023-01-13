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
-- Table structure for table `FlujoFacturaDocumento`
--

DROP TABLE IF EXISTS `FlujoFacturaDocumento`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `FlujoFacturaDocumento` (
  `id_flujofacturadocumento` int NOT NULL AUTO_INCREMENT,
  `id_flujo` int NOT NULL,
  `src_path` varchar(100) CHARACTER SET utf8 COLLATE utf8_spanish_ci DEFAULT NULL,
  `file_name` varchar(100) CHARACTER SET utf8 COLLATE utf8_spanish_ci DEFAULT NULL,
  `file_ext` varchar(10) CHARACTER SET utf8 COLLATE utf8_spanish_ci DEFAULT NULL,
  PRIMARY KEY (`id_flujofacturadocumento`),
  KEY `id_flujo_idx` (`id_flujo`)
) ENGINE=MyISAM AUTO_INCREMENT=94 DEFAULT CHARSET=utf8mb3 COLLATE=utf8_spanish_ci COMMENT='Tabla para manejo de documentos de facturas';
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `FlujoFacturaDocumento`
--

LOCK TABLES `FlujoFacturaDocumento` WRITE;
/*!40000 ALTER TABLE `FlujoFacturaDocumento` DISABLE KEYS */;
INSERT INTO `FlujoFacturaDocumento` VALUES (1,362,'','',''),(2,369,'','',''),(3,366,'','',''),(4,471,'','',''),(5,472,'','',''),(6,473,'','',''),(7,482,'','',''),(8,483,'','',''),(9,486,'','',''),(10,308,'','',''),(11,311,'','',''),(12,312,'','',''),(13,314,'','',''),(14,335,'','',''),(15,336,'','',''),(16,337,'','',''),(17,338,'','',''),(18,339,'','',''),(19,340,'','',''),(20,329,'','',''),(21,323,'','',''),(22,303,'','',''),(23,304,'','',''),(24,305,'','',''),(25,307,'','',''),(26,282,'','',''),(27,272,'','',''),(28,275,'','',''),(29,276,'','',''),(30,278,'','',''),(31,279,'','',''),(32,494,'','',''),(33,497,'','',''),(34,501,'','',''),(35,502,'','',''),(36,507,'','',''),(37,508,'','',''),(38,512,'','',''),(39,514,'','',''),(40,515,'','',''),(41,517,'','',''),(42,521,'','',''),(43,526,'','',''),(44,527,'','',''),(45,529,'','',''),(46,533,'','',''),(47,535,'','',''),(48,537,'','',''),(49,538,'','',''),(50,539,'','',''),(51,540,'','',''),(52,542,'','',''),(53,543,'','',''),(54,544,'','',''),(55,547,'','',''),(56,550,'','',''),(57,554,'','',''),(58,556,'','',''),(59,343,'','',''),(60,357,'','',''),(61,358,'','',''),(62,360,'','',''),(63,286,'','',''),(64,347,'','',''),(65,352,'','',''),(66,349,'','',''),(67,296,'','',''),(68,297,'','',''),(69,292,'','',''),(70,288,'','',''),(71,346,'','',''),(72,287,'','',''),(73,293,'','',''),(74,266,'','',''),(75,268,'','',''),(76,562,'','',''),(77,258,'','',''),(78,264,'','',''),(79,563,'','',''),(80,567,'','',''),(81,569,'','',''),(82,570,'','',''),(83,571,'','',''),(84,572,'','',''),(85,578,'','',''),(86,245,'','',''),(87,233,'','',''),(88,584,'','',''),(89,585,'','',''),(90,253,'','',''),(91,591,'','',''),(92,593,'','',''),(93,596,'','','');
/*!40000 ALTER TABLE `FlujoFacturaDocumento` ENABLE KEYS */;
UNLOCK TABLES;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2022-04-19 11:48:44
