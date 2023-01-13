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
-- Table structure for table `LotePago`
--

DROP TABLE IF EXISTS `LotePago`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `LotePago` (
  `id_lotepago` int NOT NULL AUTO_INCREMENT,
  `tipo` varchar(50) COLLATE utf8_spanish_ci NOT NULL,
  `fecha_hora` datetime NOT NULL,
  `id_usuario` int NOT NULL,
  `Activo` tinyint DEFAULT '1',
  `Eliminado` tinyint DEFAULT '0',
  `PathDocumentoPDF` varchar(150) COLLATE utf8_spanish_ci DEFAULT NULL,
  `PathDocumentoExcel` varchar(150) COLLATE utf8_spanish_ci DEFAULT NULL,
  PRIMARY KEY (`id_lotepago`),
  KEY `id_usuario_idx` (`id_usuario`),
  CONSTRAINT `id_usuario` FOREIGN KEY (`id_usuario`) REFERENCES `usuarios` (`id_usuario`)
) ENGINE=InnoDB AUTO_INCREMENT=77 DEFAULT CHARSET=utf8mb3 COLLATE=utf8_spanish_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `LotePago`
--

LOCK TABLES `LotePago` WRITE;
/*!40000 ALTER TABLE `LotePago` DISABLE KEYS */;
INSERT INTO `LotePago` VALUES (10,'BANCARIO','2022-04-03 19:33:00',2,1,0,'/var/www/laravel_apipagos/archivosPdf/PagosLote10.pdf','/var/www/laravel_apipagos/storage/app/PagosLote10.xlsx'),(11,'TRANSFERENCIA','2022-04-03 22:56:00',2,1,0,NULL,NULL),(12,'TRANSFERENCIA','2022-04-04 21:38:00',2,1,0,NULL,NULL),(13,'TRANSFERENCIA','2022-04-04 22:02:00',2,1,0,NULL,NULL),(14,'TRANSFERENCIA','2022-04-04 22:20:00',2,1,0,NULL,NULL),(15,'TRANSFERENCIA','2022-04-04 22:26:00',2,1,0,NULL,NULL),(16,'TRANSFERENCIA','2022-04-04 23:21:00',2,1,0,NULL,NULL),(17,'TRANSFERENCIA','2022-04-04 23:24:00',2,1,0,NULL,NULL),(18,'TRANSFERENCIA','2022-04-04 23:27:00',2,1,0,NULL,NULL),(19,'TRANSFERENCIA','2022-04-04 23:32:00',2,1,0,NULL,NULL),(20,'TRANSFERENCIA','2022-04-04 23:36:00',2,1,0,NULL,NULL),(21,'TRANSFERENCIA','2022-04-04 23:37:00',2,1,0,'/var/www/laravel_apipagos/archivosPdf/PagosLote21.pdf','/var/www/laravel_apipagos/storage/app/PagosLote21.xlsx'),(22,'TRANSFERENCIA','2022-04-04 23:43:00',2,1,0,NULL,NULL),(23,'TRANSFERENCIA','2022-04-04 23:47:00',2,1,0,'/var/www/laravel_apipagos/archivosPdf/PagosLote23.pdf','/var/www/laravel_apipagos/storage/app/PagosLote23.xlsx'),(24,'TRANSFERENCIA','2022-04-04 23:59:00',2,1,0,NULL,NULL),(25,'TRANSFERENCIA','2022-04-05 00:04:00',2,1,0,NULL,NULL),(26,'TRANSFERENCIA','2022-04-05 00:46:00',2,1,0,NULL,NULL),(27,'BANCARIO','2022-04-06 11:19:00',38,1,0,'/var/www/laravel_apipagos/archivosPdf/PagosLote27.pdf','/var/www/laravel_apipagos/storage/app/PagosLote27.xlsx'),(28,'TRANSFERENCIA','2022-04-07 19:36:00',2,1,0,NULL,NULL),(29,'TRANSFERENCIA','2022-04-07 19:37:00',2,1,0,NULL,NULL),(30,'TRANSFERENCIA','2022-04-07 19:38:00',2,1,0,NULL,NULL),(31,'TRANSFERENCIA','2022-04-07 19:55:00',2,1,0,NULL,NULL),(32,'TRANSFERENCIA','2022-04-07 19:56:00',2,1,0,NULL,NULL),(33,'TRANSFERENCIA','2022-04-07 20:04:00',2,1,0,NULL,NULL),(34,'TRANSFERENCIA','2022-04-07 20:08:00',2,1,0,NULL,NULL),(35,'TRANSFERENCIA','2022-04-07 20:08:00',2,1,0,'/var/www/laravel_apipagos/archivosPdf/PagosLote35.pdf','/var/www/laravel_apipagos/storage/app/PagosLote35.xlsx'),(36,'TRANSFERENCIA','2022-04-07 20:11:00',2,1,0,'/var/www/laravel_apipagos/archivosPdf/PagosLote36.pdf','/var/www/laravel_apipagos/storage/app/PagosLote36.xlsx'),(37,'TRANSFERENCIA','2022-04-07 20:17:00',2,1,0,NULL,NULL),(38,'TRANSFERENCIA','2022-04-07 20:18:00',2,1,0,'/var/www/laravel_apipagos/archivosPdf/PagosLote38.pdf','/var/www/laravel_apipagos/storage/app/PagosLote38.xlsx'),(39,'TRANSFERENCIA','2022-04-07 20:28:00',2,1,0,NULL,NULL),(40,'TRANSFERENCIA','2022-04-07 20:30:00',2,1,0,'/var/www/laravel_apipagos/archivosPdf/PagosLote40.pdf','/var/www/laravel_apipagos/storage/app/PagosLote40.xlsx'),(41,'TRANSFERENCIA','2022-04-07 20:35:00',2,1,0,'/var/www/laravel_apipagos/archivosPdf/PagosLote41.pdf','/var/www/laravel_apipagos/storage/app/PagosLote41.xlsx'),(42,'TRANSFERENCIA','2022-04-07 20:36:00',2,1,0,NULL,NULL),(43,'TRANSFERENCIA','2022-04-07 20:36:00',2,1,0,'/var/www/laravel_apipagos/archivosPdf/PagosLote43.pdf','/var/www/laravel_apipagos/storage/app/PagosLote43.xlsx'),(44,'TRANSFERENCIA','2022-04-07 20:37:00',2,1,0,'/var/www/laravel_apipagos/archivosPdf/PagosLote44.pdf','/var/www/laravel_apipagos/storage/app/PagosLote44.xlsx'),(45,'TRANSFERENCIA','2022-04-07 20:40:00',2,1,0,'/var/www/laravel_apipagos/archivosPdf/PagosLote45.pdf','/var/www/laravel_apipagos/storage/app/PagosLote45.xlsx'),(46,'TRANSFERENCIA','2022-04-07 20:43:00',2,1,0,'/var/www/laravel_apipagos/archivosPdf/PagosLote46.pdf','/var/www/laravel_apipagos/storage/app/PagosLote46.xlsx'),(47,'TRANSFERENCIA','2022-04-07 20:46:00',2,1,0,NULL,NULL),(48,'TRANSFERENCIA','2022-04-07 20:48:00',2,1,0,NULL,NULL),(49,'TRANSFERENCIA','2022-04-07 20:49:00',2,1,0,NULL,NULL),(50,'TRANSFERENCIA','2022-04-07 20:51:00',2,1,0,NULL,NULL),(51,'TRANSFERENCIA','2022-04-07 20:54:00',2,1,0,NULL,NULL),(52,'TRANSFERENCIA','2022-04-07 20:55:00',2,1,0,'/var/www/laravel_apipagos/archivosPdf/PagosLote52.pdf','/var/www/laravel_apipagos/storage/app/PagosLote52.xlsx'),(53,'TRANSFERENCIA','2022-04-07 20:56:00',2,1,0,'/var/www/laravel_apipagos/archivosPdf/PagosLote53.pdf','/var/www/laravel_apipagos/storage/app/PagosLote53.xlsx'),(54,'TRANSFERENCIA','2022-04-07 20:59:00',2,1,0,'/var/www/laravel_apipagos/archivosPdf/PagosLote54.pdf','/var/www/laravel_apipagos/storage/app/PagosLote54.xlsx'),(55,'TRANSFERENCIA','2022-04-07 21:03:00',2,1,0,NULL,NULL),(56,'TRANSFERENCIA','2022-04-07 21:06:00',2,1,0,NULL,NULL),(57,'TRANSFERENCIA','2022-04-07 21:07:00',2,1,0,NULL,NULL),(58,'TRANSFERENCIA','2022-04-07 21:09:00',2,1,0,NULL,NULL),(59,'TRANSFERENCIA','2022-04-07 21:10:00',2,1,0,NULL,NULL),(60,'TRANSFERENCIA','2022-04-07 21:12:00',2,1,0,NULL,NULL),(61,'TRANSFERENCIA','2022-04-07 21:17:00',2,1,0,'/var/www/laravel_apipagos/archivosPdf/PagosLote61.pdf','/var/www/laravel_apipagos/storage/app/PagosLote61.xlsx'),(62,'TRANSFERENCIA','2022-04-07 21:18:00',2,1,0,'/var/www/laravel_apipagos/archivosPdf/PagosLote62.pdf','/var/www/laravel_apipagos/storage/app/PagosLote62.xlsx'),(63,'TRANSFERENCIA','2022-04-07 21:25:00',2,1,0,'/var/www/laravel_apipagos/archivosPdf/PagosLote63.pdf','/var/www/laravel_apipagos/storage/app/PagosLote63.xlsx'),(64,'TRANSFERENCIA','2022-04-07 21:28:00',2,1,0,'/var/www/laravel_apipagos/archivosPdf/PagosLote64.pdf','/var/www/laravel_apipagos/storage/app/PagosLote64.xlsx'),(65,'BANCARIO','2022-04-11 11:56:00',38,1,0,'/var/www/laravel_apipagos/archivosPdf/PagosLote65.pdf','/var/www/laravel_apipagos/storage/app/PagosLote65.xlsx'),(66,'TRANSFERENCIA','2022-04-12 09:29:00',38,1,0,'/var/www/laravel_apipagos/archivosPdf/PagosLote66.pdf','/var/www/laravel_apipagos/storage/app/PagosLote66.xlsx'),(67,'TRANSFERENCIA','2022-04-13 00:27:00',2,1,0,'/var/www/laravel_apipagos/archivosPdf/PagosLote67.pdf','/var/www/laravel_apipagos/storage/app/PagosLote67.xlsx'),(68,'BANCARIO','2022-04-14 20:23:00',2,1,0,'/var/www/laravel_apipagos/archivosPdf/PagosLote68.pdf','/var/www/laravel_apipagos/storage/app/PagosLote68.xlsx'),(69,'BANCARIO','2022-04-14 20:23:00',2,1,0,'/var/www/laravel_apipagos/archivosPdf/PagosLote69.pdf','/var/www/laravel_apipagos/storage/app/PagosLote69.xlsx'),(70,'BANCARIO','2022-04-14 20:24:00',2,1,0,'/var/www/laravel_apipagos/archivosPdf/PagosLote70.pdf','/var/www/laravel_apipagos/storage/app/PagosLote70.xlsx'),(71,'BANCARIO','2022-04-14 20:27:00',2,1,0,'/var/www/laravel_apipagos/archivosPdf/PagosLote71.pdf','/var/www/laravel_apipagos/storage/app/PagosLote71.xlsx'),(72,'BANCARIO','2022-04-14 20:31:00',2,1,0,'/var/www/laravel_apipagos/archivosPdf/PagosLote72.pdf','/var/www/laravel_apipagos/storage/app/PagosLote72.xlsx'),(73,'BANCARIO','2022-04-14 20:34:00',2,1,0,'/var/www/laravel_apipagos/archivosPdf/PagosLote73.pdf','/var/www/laravel_apipagos/storage/app/PagosLote73.xlsx'),(74,'BANCARIO','2022-04-19 07:59:00',38,1,0,'/var/www/laravel_apipagos/archivosPdf/PagosLote74.pdf','/var/www/laravel_apipagos/storage/app/PagosLote74.xlsx'),(75,'TRANSFERENCIA','2022-04-19 08:49:00',38,1,0,'/var/www/laravel_apipagos/archivosPdf/PagosLote75.pdf','/var/www/laravel_apipagos/storage/app/PagosLote75.xlsx'),(76,'TRANSFERENCIA','2022-04-19 10:14:00',38,1,0,'/var/www/laravel_apipagos/archivosPdf/PagosLote76.pdf','/var/www/laravel_apipagos/storage/app/PagosLote76.xlsx');
/*!40000 ALTER TABLE `LotePago` ENABLE KEYS */;
UNLOCK TABLES;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2022-04-19 11:46:36
