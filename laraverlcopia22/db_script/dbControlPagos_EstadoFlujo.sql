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
-- Table structure for table `EstadoFlujo`
--

DROP TABLE IF EXISTS `EstadoFlujo`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `EstadoFlujo` (
  `id_estadoflujo` int NOT NULL AUTO_INCREMENT,
  `id_estadoflujopadre` int DEFAULT NULL,
  `descripcion` varchar(200) CHARACTER SET utf8 COLLATE utf8_spanish_ci DEFAULT NULL,
  `activo` tinyint(1) DEFAULT NULL,
  `eliminado` tinyint(1) DEFAULT NULL,
  `accion` varchar(200) COLLATE utf8_spanish_ci DEFAULT NULL,
  PRIMARY KEY (`id_estadoflujo`)
) ENGINE=InnoDB AUTO_INCREMENT=16 DEFAULT CHARSET=utf8mb3 COLLATE=utf8_spanish_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `EstadoFlujo`
--

LOCK TABLES `EstadoFlujo` WRITE;
/*!40000 ALTER TABLE `EstadoFlujo` DISABLE KEYS */;
INSERT INTO `EstadoFlujo` VALUES (1,0,'Cargado desde sistema origen',1,0,NULL),(2,1,'Archivos cargados',1,0,'Cargar archivos'),(3,2,'Asignado a responsable',1,0,'Asignar responsable'),(4,3,'Autorizado responsable de nivel',1,0,'Autorizar nivel'),(5,4,'Autorización completa',1,0,'Finalizar autorización'),(6,0,'Rechazado',1,0,NULL),(7,5,'Compensado.',1,0,'Compensar'),(8,0,'Cancelado desde origen',1,0,NULL),(9,0,'Rechazado por banco',1,0,NULL),(10,0,'Pausado por revisor',1,0,NULL),(11,0,'Actualizado por revisor',1,0,NULL),(12,0,'Solicitado retorno a pendientes',1,0,NULL),(13,0,'Rechazada solicitud de retorno',1,0,NULL),(14,0,'No visado',1,0,NULL),(15,7,'Aceptado por banco',1,0,NULL);
/*!40000 ALTER TABLE `EstadoFlujo` ENABLE KEYS */;
UNLOCK TABLES;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2022-04-19 11:47:41
