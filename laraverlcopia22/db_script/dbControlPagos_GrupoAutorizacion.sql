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
-- Table structure for table `GrupoAutorizacion`
--

DROP TABLE IF EXISTS `GrupoAutorizacion`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `GrupoAutorizacion` (
  `id_grupoautorizacion` int NOT NULL AUTO_INCREMENT,
  `identificador` varchar(100) CHARACTER SET utf8 COLLATE utf8_spanish_ci DEFAULT NULL,
  `descripcion` varchar(100) CHARACTER SET utf8 COLLATE utf8_spanish_ci DEFAULT NULL,
  `numero_niveles` int DEFAULT NULL,
  `activo` tinyint(1) DEFAULT NULL,
  `eliminado` tinyint(1) DEFAULT NULL,
  PRIMARY KEY (`id_grupoautorizacion`)
) ENGINE=InnoDB AUTO_INCREMENT=11 DEFAULT CHARSET=utf8mb3 COLLATE=utf8_spanish_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `GrupoAutorizacion`
--

LOCK TABLES `GrupoAutorizacion` WRITE;
/*!40000 ALTER TABLE `GrupoAutorizacion` DISABLE KEYS */;
INSERT INTO `GrupoAutorizacion` VALUES (1,'Aprobadores TI','Grupo encargado de aprobar los pagos al departamento de TI.',4,1,0),(2,'Aprobadores Agrícola','Grupo encargado de aprobar los pagos a los empleados del sector agrícola.',3,1,0),(3,'GA1','Grupo de autorización 1',3,1,0),(4,'GPA1','Grupo de autorización para CMARTINEZ, LARRIAZA/M1',2,1,0),(5,'GPA2','Grupo de autorización para EMATEU, LARRIAZA/M1',2,1,0),(6,'GPA3','Grupo de autorización para FSALAZAR, LARRIAZA/M1',2,1,0),(7,'GPA4','Grupo de autorización para JARRIAZA, LARRIAZA/M1',2,1,0),(8,'GPA5','Grupo de autorización para LARRIAZA, LARRIAZA/M1',2,1,0),(9,'GrupoPruebas','Prueba Flujo',2,1,0),(10,'ZGPC1','Grupo de prueba para cuentas',1,1,0);
/*!40000 ALTER TABLE `GrupoAutorizacion` ENABLE KEYS */;
UNLOCK TABLES;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2022-04-19 11:49:13
