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
-- Table structure for table `RecuperarPassword`
--

DROP TABLE IF EXISTS `RecuperarPassword`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `RecuperarPassword` (
  `id_recuperacion` int NOT NULL AUTO_INCREMENT,
  `id_usuario` int NOT NULL,
  `llave_acceso` varchar(45) CHARACTER SET utf8 COLLATE utf8_spanish_ci NOT NULL,
  `usada` tinyint DEFAULT '0',
  `activo` tinyint DEFAULT '1',
  `eliminado` tinyint DEFAULT '0',
  PRIMARY KEY (`id_recuperacion`),
  KEY `id_usuario_idx` (`id_usuario`),
  CONSTRAINT `id_usuario_key` FOREIGN KEY (`id_usuario`) REFERENCES `usuarios` (`id_usuario`)
) ENGINE=InnoDB AUTO_INCREMENT=33 DEFAULT CHARSET=utf8mb3 COLLATE=utf8_spanish_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `RecuperarPassword`
--

LOCK TABLES `RecuperarPassword` WRITE;
/*!40000 ALTER TABLE `RecuperarPassword` DISABLE KEYS */;
INSERT INTO `RecuperarPassword` VALUES (1,1,'Ibu6tCdDSG3gw2oelA0E',0,0,0),(2,2,'qU77qjjgtXOpyB2ZqRKq',1,1,0),(3,1,'PJuJmMTXcYsMuUdUKUu5',1,1,0),(4,1,'eVwDQwrCttXGMV5UNR0F',1,1,0),(5,1,'HYc18kasJENEmQn74jrx',0,0,0),(6,2,'HyWu6GEbydi5XGI4sc8n',0,0,0),(7,2,'6SfkCUL7VvxLKpCDw4UG',0,0,0),(8,2,'n14yDrQUmuPFv0DI6M6x',0,0,0),(9,2,'Dw4qzHEaXrHzEN7uNUZc',0,0,0),(10,2,'gX67qwFpVXJBsu3B93Hz',0,0,0),(11,2,'mxAueHkRTraqyWvdTNB6',0,0,0),(12,2,'JQ1jxTXLd0KMcHD50orr',1,1,0),(13,2,'O6RiWzJ5dqTRjIvHGHbj',0,0,0),(14,1,'qdzfGkMRJrGsypu2TATW',0,0,0),(15,1,'PBDJcpGlIrkL2YP8EPpo',0,0,0),(16,1,'Ga0CFAfOCaqJCVYfUtbh',0,0,0),(17,1,'r2WOSlV4WUJCfKyLpMAV',0,0,0),(18,1,'XUGZtyQbyIj5pDzAwL6H',1,1,0),(19,4,'8YFutMEwwyYuUkrmIN48',0,0,0),(20,2,'MQ3nN9H2ijBOJaatbGl0',1,1,0),(21,2,'43ufRtee5Gv0wix8O1Nf',0,0,0),(22,17,'gzNbJZ8z2EWdrufeMKOO',0,1,0),(23,2,'a6JpveWxO12EwtlJE5Mx',0,0,0),(24,2,'GLoy5QWzg8plftIoDMZg',1,1,0),(25,2,'5B3DwggKT2S6LmdalgR3',1,1,0),(26,4,'E7BrQH88ejb9odaJs50y',1,1,0),(27,2,'csFrI5GYeBjt8L1qQooS',1,1,0),(28,23,'u5va0ho8fKRpyPlpm2ch',0,0,0),(29,23,'QKtVqGfcM5X7HCbu7U2s',1,1,0),(30,33,'CR1vbyM3ZdOZJBo1HPqt',1,1,0),(31,28,'Ual2syEfUOUSB23Pfs0x',0,0,0),(32,28,'sOIcSFC9V4OROo0QE0BG',1,1,0);
/*!40000 ALTER TABLE `RecuperarPassword` ENABLE KEYS */;
UNLOCK TABLES;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2022-04-19 11:49:28
