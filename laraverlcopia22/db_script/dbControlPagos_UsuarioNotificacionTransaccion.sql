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
-- Table structure for table `UsuarioNotificacionTransaccion`
--

DROP TABLE IF EXISTS `UsuarioNotificacionTransaccion`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `UsuarioNotificacionTransaccion` (
  `id_usuarionotificaciontransaccion` int NOT NULL AUTO_INCREMENT,
  `id_usuario` int NOT NULL,
  `TipoTransaccion` varchar(55) COLLATE utf8_spanish_ci NOT NULL,
  `Activo` tinyint DEFAULT '1',
  `Eliminado` tinyint DEFAULT '0',
  PRIMARY KEY (`id_usuarionotificaciontransaccion`),
  KEY `id_usuario_idx` (`id_usuario`),
  CONSTRAINT `id_usuario_notificacion` FOREIGN KEY (`id_usuario`) REFERENCES `usuarios` (`id_usuario`)
) ENGINE=InnoDB AUTO_INCREMENT=25 DEFAULT CHARSET=utf8mb3 COLLATE=utf8_spanish_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `UsuarioNotificacionTransaccion`
--

LOCK TABLES `UsuarioNotificacionTransaccion` WRITE;
/*!40000 ALTER TABLE `UsuarioNotificacionTransaccion` DISABLE KEYS */;
INSERT INTO `UsuarioNotificacionTransaccion` VALUES (13,2,'BANCARIO',1,0),(14,2,'TRANSFERENCIA',1,0),(15,2,'INTERNA',1,0),(16,38,'BANCARIO',1,0),(17,38,'TRANSFERENCIA',1,0),(18,38,'INTERNA',1,0),(19,27,'BANCARIO',1,0),(20,27,'TRANSFERENCIA',1,0),(21,27,'INTERNA',1,0),(22,28,'BANCARIO',1,0),(23,28,'TRANSFERENCIA',1,0),(24,28,'INTERNA',1,0);
/*!40000 ALTER TABLE `UsuarioNotificacionTransaccion` ENABLE KEYS */;
UNLOCK TABLES;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2022-04-19 11:48:54
