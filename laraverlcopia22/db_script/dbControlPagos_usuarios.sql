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
-- Table structure for table `usuarios`
--

DROP TABLE IF EXISTS `usuarios`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `usuarios` (
  `id_usuario` int NOT NULL AUTO_INCREMENT,
  `correo` varchar(100) CHARACTER SET utf8 COLLATE utf8_spanish_ci DEFAULT NULL,
  `password` varchar(100) CHARACTER SET utf8 COLLATE utf8_spanish_ci DEFAULT NULL,
  `nombre_usuario` varchar(100) CHARACTER SET utf8 COLLATE utf8_spanish_ci DEFAULT NULL,
  `nombre` varchar(100) CHARACTER SET utf8 COLLATE utf8_spanish_ci DEFAULT NULL,
  `apellido` varchar(100) CHARACTER SET utf8 COLLATE utf8_spanish_ci DEFAULT NULL,
  `cambia_password` tinyint(1) DEFAULT '1',
  `activo` tinyint(1) DEFAULT '1',
  `eliminado` tinyint DEFAULT '0',
  PRIMARY KEY (`id_usuario`)
) ENGINE=InnoDB AUTO_INCREMENT=43 DEFAULT CHARSET=utf8mb3 COLLATE=utf8_spanish_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `usuarios`
--

LOCK TABLES `usuarios` WRITE;
/*!40000 ALTER TABLE `usuarios` DISABLE KEYS */;
INSERT INTO `usuarios` VALUES (1,'daniel.marroquin.es@gmail.com','01074766128d697fe6c6915d3557f135','ricardo_pendrogon','Ricardo','Marroquin',1,1,0),(2,'crcf85@gmail.com','0f5c5718c5db8c341f8efbc9b0aec20d','cristian_pendrogon','Cristian','Castellanos',1,1,0),(3,'rcosme@pendrogon.com','6f38604eeade85b0116c112cb5a4698f','rober_pendrogon','Roberto','Cosme',1,0,1),(4,'echajon@sion.com.gt','742b22daeee12688111c156acca33885','echajo_sion','Efrain','Chajon',1,1,0),(5,'cmartinez@sion.com.gt','e8734610d008921891787299cb8a6356','cmartinez_sion','Cristian','Martinez',1,0,0),(6,'alejandro.pendrogon@gmail.com','a11f4fdfdba1f568a0832a9d64258d5c','alejandro_crack','Alejandro','Castellanos',1,1,1),(10,'layfer.hernandez@gmail.com','52a50bf60f1a1cf18b2cee0a527731b0','gustavo_hernangt','Gustavo','Hernández',1,1,0),(11,'super@pendrogon.com','e8734610d008921891787299cb8a6356','super_usuario','Super','Usuario',1,1,0),(12,'archivo@pendrogon.com','e8734610d008921891787299cb8a6356','archivos_usuario','Archivos','Usuario',1,1,0),(13,'autorizador1@pendrogon.com','e8734610d008921891787299cb8a6356','autorizador1','Autorizador','Nivel1',1,1,0),(14,'autorizador2@pendrogon.com','e8734610d008921891787299cb8a6356','autorizador2','Autorizador','Nivel2',1,1,0),(15,'autorizadorf@pendrogon.com','e8734610d008921891787299cb8a6356','autorizador_final','Autorizador','Final',1,1,0),(16,'asignador@pendrogon.com','e8734610d008921891787299cb8a6356','usuario_asignador','Usuario','Asignador',1,1,0),(17,'operez@sion.com.gt','e8734610d008921891787299cb8a6356','operez','Oscar','Pérez',1,1,1),(18,'presidencia@sion.com.gt','e8734610d008921891787299cb8a6356','presidencia_sion','Usuario','Presidencia',1,1,0),(19,'emateu1@sion.com.gt','e8734610d008921891787299cb8a6356','emateu1','Usuario','Emteu1',1,1,0),(20,'emateu@sion.com.gt','e8734610d008921891787299cb8a6356','emateu','Usuario','Emteu',1,1,0),(21,'fsalazar@sion.com.gt','e8734610d008921891787299cb8a6356','fsalazar','Usuario','fsalazar',1,1,0),(22,'jarriaza@sion.com.gt','e8734610d008921891787299cb8a6356','jarriaza','Usuario','jarriaza',1,1,0),(23,'larriaza@sion.com.gt','f9de0188b043d2172b2f758640dc7907','larriaza','Usuario','larriaza',1,1,0),(24,'larriaza_m1@sion.com.gt','e8734610d008921891787299cb8a6356','larriaza_m1','Usuario','larriaza_m1',1,1,0),(25,'msantos@sion.com.gt','e8734610d008921891787299cb8a6356','msantos','Usuario','msantos',1,1,0),(26,'wvillagran@sion.com.gt','e8734610d008921891787299cb8a6356','wvillagran','Wilfredo','Villagran',1,1,1),(27,'jsantizo@sion.com.gt','e8734610d008921891787299cb8a6356','jsantizo','Usuario','jsantizo',1,1,0),(28,'lrodriguez@sion.com.gt','b7179adfd8651a40fffca452d40b7174','lrodriguez','Usuario','lrodriguez',1,1,0),(29,'prueba','e807f1fcf82d132f9bb018ca6738a19f','Prueba','Prueba1','Prueba1',1,1,0),(30,'usuarior@pendrogon.com','e8734610d008921891787299cb8a6356','usuario_revisor','Usuario','Revisor',1,1,0),(31,'ppendrogon@pendrogon.com','0f5c5718c5db8c341f8efbc9b0aec20d','ppendrogon','Prueba','Pendrogon',1,1,0),(32,'http://34.208.193.210/pagos/#/','e807f1fcf82d132f9bb018ca6738a19f','ecasasola','Edgar','Casasola',1,1,0),(33,'ecasasola@sion.com.gt','6fb42da0e32e07b61c9f0251fe627a9c','ecasasola2','Edgar2','casasola2',1,1,0),(34,'wvillagra@sion.com.gt','e807f1fcf82d132f9bb018ca6738a19f','wvillagran','Wilfredo','Villagran',1,1,0),(35,'aeperez@sion.com.gt','e807f1fcf82d132f9bb018ca6738a19f','aeperez','Anibal','Pérez',1,1,0),(36,'ecasasola@','e807f1fcf82d132f9bb018ca6738a19f','EdgarRevisor','EdgarRevisor','casasola',1,1,0),(37,'@correo.com','e807f1fcf82d132f9bb018ca6738a19f','EdgarArchivos','EdgarArchivos','Casasola',1,1,0),(38,'kdkd@gmail.com','e807f1fcf82d132f9bb018ca6738a19f','EdgarCompensar','Edgar','Compensar',1,1,0),(39,'lsls@fmail.com','e807f1fcf82d132f9bb018ca6738a19f','EdgarRegresar','EdgarRegresar','Regresar',1,1,0),(40,'ecasasola@sion.com.gts','e807f1fcf82d132f9bb018ca6738a19f','EdgarGerente','Edgar','Casasola Gerente',1,1,0),(41,'lll@gmail.com','e807f1fcf82d132f9bb018ca6738a19f','EdgarRoot','Edgar','Casas',1,1,0),(42,'hs@sa.com','e807f1fcf82d132f9bb018ca6738a19f','EdgarRevisorAut','EdgarRevAut','Aut',1,1,0);
/*!40000 ALTER TABLE `usuarios` ENABLE KEYS */;
UNLOCK TABLES;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2022-04-19 11:50:01
