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
-- Table structure for table `Mensaje`
--

DROP TABLE IF EXISTS `Mensaje`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `Mensaje` (
  `id_mensaje` int NOT NULL AUTO_INCREMENT,
  `id_flujo` int DEFAULT NULL,
  `id_usuarioenvia` int DEFAULT NULL,
  `id_usuariorecibe` int DEFAULT NULL,
  `fecha_hora` datetime DEFAULT NULL,
  `mensaje` varchar(200) CHARACTER SET utf8 COLLATE utf8_spanish_ci DEFAULT NULL,
  `leido` tinyint(1) DEFAULT NULL,
  `activo` tinyint(1) DEFAULT NULL,
  `eliminado` tinyint(1) DEFAULT NULL,
  PRIMARY KEY (`id_mensaje`),
  KEY `flujo` (`id_flujo`),
  KEY `emisor` (`id_usuarioenvia`),
  KEY `receptor` (`id_usuariorecibe`),
  CONSTRAINT `emisor` FOREIGN KEY (`id_usuarioenvia`) REFERENCES `usuarios` (`id_usuario`),
  CONSTRAINT `flujo` FOREIGN KEY (`id_flujo`) REFERENCES `Flujo` (`id_flujo`),
  CONSTRAINT `Mensaje_ibfk_1` FOREIGN KEY (`id_flujo`) REFERENCES `Flujo` (`id_flujo`),
  CONSTRAINT `Mensaje_ibfk_2` FOREIGN KEY (`id_usuarioenvia`) REFERENCES `usuarios` (`id_usuario`),
  CONSTRAINT `Mensaje_ibfk_3` FOREIGN KEY (`id_usuariorecibe`) REFERENCES `usuarios` (`id_usuario`),
  CONSTRAINT `receptor` FOREIGN KEY (`id_usuariorecibe`) REFERENCES `usuarios` (`id_usuario`)
) ENGINE=InnoDB AUTO_INCREMENT=57 DEFAULT CHARSET=utf8mb3 COLLATE=utf8_spanish_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `Mensaje`
--

LOCK TABLES `Mensaje` WRITE;
/*!40000 ALTER TABLE `Mensaje` DISABLE KEYS */;
INSERT INTO `Mensaje` VALUES (1,206,1,2,'2021-08-16 17:47:14','Solicitud de aprobacion de pago.',1,1,0),(2,1,2,1,'2021-08-16 17:47:47','CONFIRMACION.',1,1,0),(3,206,1,2,'2021-08-17 02:04:18','VALIDACION',1,1,0),(4,206,1,2,'2021-08-17 02:09:39','Buenas noches.',1,1,0),(5,206,1,2,'2021-08-17 14:05:00','APROBACION',1,1,0),(6,206,1,2,'2021-08-17 14:05:00','Prueba hora',1,1,0),(7,2,2,1,'2021-08-17 21:47:00','Excelente',1,1,0),(8,1,2,1,'2021-08-26 22:51:00','Buenas noches',1,1,0),(10,206,1,2,'2021-09-13 12:31:00','Una consulta, el pago tiene muchos digitos, para quièn es?',1,1,0),(11,9,1,2,'2021-09-14 22:43:00','Hola',1,1,0),(12,6,2,10,'2021-09-15 12:56:00','El documento de pago tiene una fecha que me parece incorrecta.',0,1,0),(13,11,1,2,'2021-09-21 21:36:00','Son muchos digitos',1,1,0),(14,24,1,2,'2021-09-21 21:39:00','La fecha fue hace más de un mes.',1,1,0),(15,7,1,2,'2021-09-21 21:54:00','Tiene un archivo muy pequeño.',1,1,0),(16,10,1,2,'2021-10-12 13:32:00','Hay algo raro en el pago.',1,1,0),(17,12,1,2,'2021-10-12 14:19:00','Hola buenas tardes.',1,1,0),(18,207,2,14,'2021-11-17 21:55:00','Hola',1,1,0),(19,207,2,14,'2021-11-17 21:56:00','Esto es una prueba',1,1,0),(20,207,14,2,'2021-11-17 22:02:00','Hola, todo bien?',1,1,0),(21,207,14,2,'2021-11-17 22:06:00','Hola',1,1,0),(22,204,2,14,'2021-11-17 23:30:00','Hola, prueba de mensaje',1,1,0),(23,207,2,14,'2021-11-18 00:24:00','Hola, todo bien?',1,1,0),(24,207,14,2,'2021-11-18 00:29:00','Creo que si',1,1,0),(25,207,2,14,'2021-11-18 19:17:00','Hola, esto es una prueba',1,1,0),(26,207,14,2,'2021-11-18 19:18:00','Calidad',1,1,0),(27,204,14,2,'2021-11-18 19:19:00','Ok',1,1,0),(28,218,33,4,'2022-03-08 14:30:00','pruebas pagos',0,1,0),(29,218,32,4,'2022-03-17 11:10:00','Prueba',0,1,0),(30,376,33,35,'2022-03-18 09:41:00','prueba',1,1,0),(31,376,35,33,'2022-03-18 09:42:00','prueba 2',1,1,0),(32,376,33,35,'2022-03-18 09:43:00','hola',1,1,0),(33,376,35,33,'2022-03-18 09:44:00','hi',1,1,0),(34,376,33,35,'2022-03-18 09:44:00','.',1,1,0),(35,375,33,35,'2022-03-18 09:46:00','.',1,1,0),(36,376,35,33,'2022-03-18 09:48:00','..',1,1,0),(37,375,35,33,'2022-03-18 09:50:00','...',1,1,0),(38,375,35,33,'2022-03-18 09:50:00','{{',1,1,0),(39,375,35,33,'2022-03-18 09:51:00','{+',1,1,0),(40,378,33,35,'2022-03-21 08:14:00','buenas',1,1,0),(41,385,36,33,'2022-03-22 09:15:00','Hola',0,1,0),(42,385,36,33,'2022-03-22 09:16:00','Ji',0,1,0),(43,385,33,32,'2022-03-28 08:25:00','prueba',1,1,0),(44,385,32,33,'2022-03-28 08:25:00','gracias',1,1,0),(45,385,33,32,'2022-03-28 08:46:00','prueba 2',1,1,0),(46,385,32,33,'2022-03-28 08:51:00','si',1,1,0),(47,388,33,35,'2022-03-28 10:51:00','revisar el pago',1,1,0),(48,388,35,33,'2022-03-28 10:52:00','bueno',1,1,0),(49,3465,33,35,'2022-03-28 11:20:00','Revisar si todo esta bien',1,1,0),(50,3465,35,33,'2022-03-28 11:20:00','si',1,1,0),(51,389,33,35,'2022-03-29 11:14:00','Revise el pago',1,1,0),(52,389,35,33,'2022-03-29 11:15:00','revisado',1,1,0),(53,383,35,33,'2022-04-04 10:18:00','que tal',0,1,0),(54,3684,35,33,'2022-04-06 11:39:00','aprobado',0,1,0),(55,386,33,28,'2022-04-11 11:45:00','revisar',1,1,0),(56,386,28,33,'2022-04-11 11:46:00','que necesita?',1,1,0);
/*!40000 ALTER TABLE `Mensaje` ENABLE KEYS */;
UNLOCK TABLES;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2022-04-19 11:47:56
