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
-- Table structure for table `SugerenciaAsignacionGrupo`
--

DROP TABLE IF EXISTS `SugerenciaAsignacionGrupo`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `SugerenciaAsignacionGrupo` (
  `id_sugerenciagrupo` int NOT NULL AUTO_INCREMENT,
  `id_flujo` int DEFAULT NULL,
  `id_grupoautorizacion` int DEFAULT NULL,
  `activo` tinyint DEFAULT '1',
  `eliminado` tinyint DEFAULT '0',
  PRIMARY KEY (`id_sugerenciagrupo`),
  KEY `id_flujo_idx` (`id_flujo`),
  KEY `id_grupoautorizacion_idx` (`id_grupoautorizacion`),
  CONSTRAINT `id_flujo_sugerencia` FOREIGN KEY (`id_flujo`) REFERENCES `Flujo` (`id_flujo`),
  CONSTRAINT `id_grupoautorizacion_sugerencia` FOREIGN KEY (`id_grupoautorizacion`) REFERENCES `GrupoAutorizacion` (`id_grupoautorizacion`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb3 COLLATE=utf8_spanish_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `SugerenciaAsignacionGrupo`
--

LOCK TABLES `SugerenciaAsignacionGrupo` WRITE;
/*!40000 ALTER TABLE `SugerenciaAsignacionGrupo` DISABLE KEYS */;
INSERT INTO `SugerenciaAsignacionGrupo` VALUES (1,3,5,0,0),(2,3,7,0,0),(3,3,5,0,0),(4,3,7,0,0);
/*!40000 ALTER TABLE `SugerenciaAsignacionGrupo` ENABLE KEYS */;
UNLOCK TABLES;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2022-04-19 11:49:51
