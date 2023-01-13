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
-- Table structure for table `roles`
--

DROP TABLE IF EXISTS `roles`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `roles` (
  `id_rol` int NOT NULL AUTO_INCREMENT,
  `descripcion` varchar(200) CHARACTER SET utf8 COLLATE utf8_spanish_ci DEFAULT NULL,
  `objeto` varchar(100) CHARACTER SET utf8 COLLATE utf8_spanish_ci DEFAULT NULL,
  `activo` tinyint(1) DEFAULT NULL,
  `eliminado` tinyint(1) DEFAULT NULL,
  PRIMARY KEY (`id_rol`)
) ENGINE=MyISAM AUTO_INCREMENT=35 DEFAULT CHARSET=utf8mb3 COLLATE=utf8_spanish_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `roles`
--

LOCK TABLES `roles` WRITE;
/*!40000 ALTER TABLE `roles` DISABLE KEYS */;
INSERT INTO `roles` VALUES (1,'Mantenimiento Usuarios.','Modulo Usuarios',1,0),(2,'Mantenimiento Perfiles.','Modulo Perfiles',1,0),(3,'Mantenimiento Roles.','Modulo Roles',1,0),(4,'Mantenimiento Permisos.','Modulo Permisos',1,0),(5,'Mantenimiento Políticas.','Modulo Politicas',1,0),(6,'Mantenimiento Condiciones.','Modulo Condiciones',1,0),(7,'Mantenimiento Grupos Autorización.','Modulo Grupos Autorizacion',1,0),(8,'Mantenimiento Estados Pago','Modulo Estados Pago',1,0),(9,'Mantenimiento Tipos Flujo.','Modulo Tipos Flujo',1,0),(10,'Mantenimiento Archivos Pago.','Modulo Archivos Pago',1,0),(11,'Mantenimiento Bancos.','Modulo Bancos',1,0),(12,'Mantenimiento Monedas.','Modulo Monedas',1,0),(13,'Mantenimiento Cuentas.','Modulo Cuentas',1,0),(14,'Autorización Pagos.','Modulo Autorizacion Pagos',1,0),(15,'Compensación Pagos.','Modulo Compensacion Pagos',1,0),(16,'Autorización Usuario Temporal.','Modulo Autorizacion',1,0),(17,'Asignación Responsable.','Modulo Autorizacion Pagos',1,0),(18,'Consulta Usuarios Conectados','Modulo Conectados',1,0),(19,'Visualización Reportes','Seccion Reportes',1,0),(20,'Consulta archivos cargados','Modulo Archivos Pago',1,0),(21,'Carga Archivos Pago','Modulo Autorizacion Pagos',1,0),(22,'Revisión de pagos','Modulo Autorizacion Pagos',1,0),(23,'Actualización de pagos','Modulo Autorizacion Pagos',1,0),(24,'Visualizador de pagos','Ver pagos creados',1,1),(25,'Visualizador de pagos','Ver pagos',1,0),(26,'Carga nuevos pagos','Modulo Autorizacion Pagos',1,0),(27,'Reprocesar pagos','Modulo Compensacion Pagos',1,0),(28,'Actualización de pagos por compensar','Modulo Compensacion Pagos',1,0),(29,'Consultor de pagos','Modulo Autorizacion Pagos',1,0),(30,'Mantenimiento restricción empresas','Modulo RestriccionEmpresa',1,0),(31,'Mantenimiento grupo de autorización por cuenta','Modulo CuentaGrupoAutorizacion',1,0),(32,'Descarga de archivos QR','Modulo DescargaArchivos',1,0),(33,'Revisor/Autorizador de pagos','Modulo Autorizacion Pagos',1,0),(34,'Mantenimiento notificación de lotes','Modulo NotificacionLote',1,0);
/*!40000 ALTER TABLE `roles` ENABLE KEYS */;
UNLOCK TABLES;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2022-04-19 11:46:50
