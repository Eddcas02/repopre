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
-- Dumping events for database 'dbControlPagos'
--

--
-- Dumping routines for database 'dbControlPagos'
--
/*!50003 DROP PROCEDURE IF EXISTS `CerrarSesionesActivas` */;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = utf8mb4 */ ;
/*!50003 SET character_set_results = utf8mb4 */ ;
/*!50003 SET collation_connection  = utf8mb4_0900_ai_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'ONLY_FULL_GROUP_BY,STRICT_TRANS_TABLES,NO_ZERO_IN_DATE,NO_ZERO_DATE,ERROR_FOR_DIVISION_BY_ZERO,NO_ENGINE_SUBSTITUTION' */ ;
DELIMITER ;;
CREATE PROCEDURE `CerrarSesionesActivas`()
BEGIN
	UPDATE
		dbControlPagos.SesionUsuario
	SET
		Activo = 0
	WHERE 
		IdSesion > 1
		and FechaHoraFinal is null 
		and TIMESTAMPDIFF(HOUR,FechaHoraInicio,NOW()) > 8
		and Activo = 1;
END ;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;
/*!50003 DROP PROCEDURE IF EXISTS `ReportePendientesValidacion` */;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = utf8mb4 */ ;
/*!50003 SET character_set_results = utf8mb4 */ ;
/*!50003 SET collation_connection  = utf8mb4_0900_ai_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'ONLY_FULL_GROUP_BY,STRICT_TRANS_TABLES,NO_ZERO_IN_DATE,NO_ZERO_DATE,ERROR_FOR_DIVISION_BY_ZERO,NO_ENGINE_SUBSTITUTION' */ ;
DELIMITER ;;
CREATE PROCEDURE `ReportePendientesValidacion`()
BEGIN
select T1.id_flujo, T1.empresa_nombre, T1.doc_num, DATE_FORMAT(T1.doc_date,'%d-%m-%Y') as doc_date, 
T1.en_favor_de, T1.comments, T1.doc_total, DATE_FORMAT(min(FD.Fecha),'%d-%m-%Y %H:%i:%s') as fecha_asignacion,
U.nombre_usuario,
TIMESTAMPDIFF(DAY, T1.doc_date, DATE_ADD(NOW(), INTERVAL 1 HOUR)) as dias
FROM (
    select F.id_flujo, F.empresa_nombre, F.doc_num, F.doc_date, F.en_favor_de,
    F.comments, F.doc_total, F.nivel, F.id_grupoautorizacion
    from Flujo F join FlujoDetalle FD
    on F.id_flujo = FD.IdFlujo and FD.IdEstadoFlujo = F.estado
    and F.id_grupoautorizacion and FD.IdEstadoFlujo = 4 group by F.id_flujo
)
T1 join FlujoDetalle FD join UsuarioGrupoAutorizacion UG join usuarios U
on FD.IdFlujo = T1.id_flujo and T1.nivel = UG.nivel and UG.id_usuario = U.id_usuario
and UG.id_grupoautorizacion = T1.id_grupoautorizacion 
group by T1.id_flujo, T1.empresa_nombre, T1.doc_num, T1.doc_date, 
T1.en_favor_de, T1.comments, T1.doc_total, U.nombre_usuario;
END ;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;
/*!50003 DROP PROCEDURE IF EXISTS `ReporteSemaforoListar` */;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = utf8mb4 */ ;
/*!50003 SET character_set_results = utf8mb4 */ ;
/*!50003 SET collation_connection  = utf8mb4_0900_ai_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'ONLY_FULL_GROUP_BY,STRICT_TRANS_TABLES,NO_ZERO_IN_DATE,NO_ZERO_DATE,ERROR_FOR_DIVISION_BY_ZERO,NO_ENGINE_SUBSTITUTION' */ ;
DELIMITER ;;
CREATE PROCEDURE `ReporteSemaforoListar`()
BEGIN
set @SemaforoVerde = (select CONVERT(P.valor, UNSIGNED INTEGER) FROM dbControlPagos.politicas as P where P.identificador = '_SEMAFORO_VERDE' );
set @SemaforoAmarillo = (select CONVERT(P.valor, UNSIGNED INTEGER) FROM dbControlPagos.politicas as P where P.identificador = '_SEMAFORO_AMARILLO' );

select tmp.colorSemaforo as nombreSemaforo, count(*) as cantidad from (
select
	F.doc_date as FechaDocumento,
    NOW() as FechaAhora,
    DATEDIFF(NOW(),F.doc_date) as Diferencia,
    F.dias_credito,
    (( DATEDIFF(NOW(),F.doc_date) * 100)/F.dias_credito) as porcentajeDias,
    CASE
		WHEN (( DATEDIFF(NOW(),F.doc_date) * 100)/F.dias_credito) <= @SemaforoVerde THEN 'VERDE'
        WHEN (( DATEDIFF(NOW(),F.doc_date) * 100)/F.dias_credito) > @SemaforoVerde and (( DATEDIFF(NOW(),F.doc_date) * 100)/F.dias_credito) <= @SemaforoAmarillo THEN 'AMARILLO'
        WHEN (( DATEDIFF(NOW(),F.doc_date) * 100)/F.dias_credito) > @SemaforoAmarillo THEN 'ROJO'
        ELSE 'ERROR'
    END as colorSemaforo
from
	dbControlPagos.Flujo as F
where
	F.estado not in (5,6)) as tmp
where not tmp.colorSemaforo = 'ERROR'
group by tmp.colorSemaforo;
END ;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;
/*!50003 DROP PROCEDURE IF EXISTS `TiempoPromedioEstadosFlujoListar` */;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = utf8mb4 */ ;
/*!50003 SET character_set_results = utf8mb4 */ ;
/*!50003 SET collation_connection  = utf8mb4_0900_ai_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'ONLY_FULL_GROUP_BY,STRICT_TRANS_TABLES,NO_ZERO_IN_DATE,NO_ZERO_DATE,ERROR_FOR_DIVISION_BY_ZERO,NO_ENGINE_SUBSTITUTION' */ ;
DELIMITER ;;
CREATE PROCEDURE `TiempoPromedioEstadosFlujoListar`()
BEGIN
select 
	tmp2.IdEstadoFlujo estadoOrigen, 
    tmp2.descripcion nombreEstadoOrigen, 
    EF3.id_estadoflujo estadoDestino, 
    EF3.descripcion nombreEstadoDestino, 
    AVG(tmp2.diferencia) promedioPorNivel
from (select 
		FD.IdFlujo, 
		FD.IdEstadoFlujo, 
		EF.descripcion, 
		MAX(FD.Fecha) as FechaEstado, 
		tmp.fecha as FechaSiguienteEstado,
		timestampdiff(HOUR,MAX(FD.Fecha),tmp.fecha) as diferencia
	from
		dbControlPagos.Flujo as F
		inner join dbControlPagos.FlujoDetalle FD
			on F.id_flujo = FD.IdFlujo
		inner join dbControlPagos.EstadoFlujo EF
			on FD.IdEstadoFlujo = EF.id_estadoflujo
		inner join (
			select 
				FD2.IdFlujo, 
				FD2.IdEstadoFlujo, 
				EF2.descripcion, 
				EF2.id_estadoflujopadre, 
				MAX(FD2.Fecha) as fecha
			from
				dbControlPagos.Flujo as F2
				inner join dbControlPagos.FlujoDetalle FD2
					on F2.id_flujo = FD2.IdFlujo
				inner join dbControlPagos.EstadoFlujo EF2
					on FD2.IdEstadoFlujo = EF2.id_estadoflujo
			where
				F2.Eliminado = 0
				and F2.estado <> 6
			group by 
				FD2.IdFlujo, 
				FD2.IdEstadoFlujo, 
				EF2.descripcion, 
				EF2.id_estadoflujopadre
		) tmp
			on FD.IdFlujo = tmp.IdFlujo
			and FD.IdEstadoFlujo = tmp.id_estadoflujopadre
	where
		F.Eliminado = 0
		and F.estado <> 6
	group by 
		FD.IdFlujo, 
		FD.IdEstadoFlujo, 
		EF.descripcion, 
		tmp.fecha
	) as tmp2
	inner join dbControlPagos.EstadoFlujo EF3
		on tmp2.IdEstadoFlujo = EF3.id_estadoflujopadre
group by
	tmp2.IdEstadoFlujo,
    tmp2.descripcion, 
    EF3.id_estadoflujo, 
    EF3.descripcion;
END ;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2022-04-19 11:51:01
