<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\RolesController;
use App\Http\Controllers\PerfilesController;
use App\Http\Controllers\CondicionAutorizacionController;
use App\Http\Controllers\EstadoFlujoController;
use App\Http\Controllers\GrupoAutorizacionController;
use App\Http\Controllers\PermisosController;
use App\Http\Controllers\PoliticasController;
use App\Http\Controllers\UsuariosController;
use App\Http\Controllers\TipoFlujoController;
use App\Http\Controllers\MensajesController;
use App\Http\Controllers\MonedasController;
use App\Http\Controllers\BancosController;
use App\Http\Controllers\CuentasController;
use App\Http\Controllers\UsuarioPerfilController;
use App\Http\Controllers\UsuarioGrupoController;
use App\Http\Controllers\PerfilRolController;
use App\Http\Controllers\RolPermisoController;
use App\Http\Controllers\CondicionGrupoController;
use App\Http\Controllers\UsuarioAutorizacionController;
use App\Http\Controllers\FlujosController;
use App\Http\Controllers\FlujoSolicitudController;
use App\Http\Controllers\FlujoOrdenController;
use App\Http\Controllers\FlujoOfertaController;
use App\Http\Controllers\FlujoIngresoController;
use App\Http\Controllers\FlujoFacturaCantidadController;
use App\Http\Controllers\FlujoFacturaDocumentoController;
use App\Http\Controllers\ArchivosFlujoController;
use App\Http\Controllers\FlujoGrupoController;
use App\Http\Controllers\NotificacionController;
use App\Http\Controllers\LogLoginController;
use App\Http\Controllers\FlujoDetalleController;
use App\Http\Controllers\PaisesController;
use App\Http\Controllers\LoginController;
use App\Http\Controllers\CargaDatosController;
use App\Http\Controllers\RecuperarPasswordController;
use App\Http\Controllers\SesionUsuarioController;
use App\Http\Controllers\ReportesController;
use App\Http\Controllers\LotePagoController;
use App\Http\Controllers\RestriccionEmpresaController;
use App\Http\Controllers\CuentaGrupoAutorizacionController;
use App\Http\Controllers\TestFuncionalidadController;
use App\Http\Controllers\SugerenciaAsignacionGrupoController;
use App\Http\Controllers\UsuarioNotificacionTransaccionController;
use App\Http\Controllers\NotificacionTipoDocumentoLoteController;
use App\Http\Controllers\TipoDocumentoLoteController;
use App\Http\Controllers\BitacoraController;
use App\Http\Controllers\RecordatorioUsuarioController;
use App\Http\Controllers\UsuarioPrioridadMensajesController;
use App\Http\Controllers\OcultarColumnaUsuarioController;
use App\Http\Controllers\UsuarioRecordatorioGrupoController;
use App\Http\Controllers\UsuarioSinNotificacionCorreoController;
use App\Http\Controllers\SeccionAplicacionController;
use App\Http\Controllers\UsuarioRedireccionController;
use App\Http\Controllers\UsuarioRestriccionEmpresaController;
use App\Http\Controllers\UsuarioRestriccionTextoController;
use App\Http\Controllers\ConsultorController;
use App\Http\Controllers\ReasignacionController;
use App\Http\Controllers\FlujoCompensarSeleccionadoController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/
Route::middleware('auth:api')->get('/user', function (Request $request) {
    return $request->user();
});
//Politicas
Route::get('politicas', [PoliticasController::class, 'index']);
//Login
Route::post('login', [LoginController::class, 'autenticar']);
//Test funcionalidad
Route::post('test', [TestFuncionalidadController::class, 'index']);

//Recuperar password
Route::post('recuperapassword', [RecuperarPasswordController::class, 'store']);    
Route::post('cambiapassword', [RecuperarPasswordController::class, 'cambio']);

Route::post('flujoarchivo', [FlujosController::class, 'flujoarchivo']);

Route::middleware('auth:api')->group(function (){
    //usuario login
    Route::get('usuarios/{usuario}', [UsuariosController::class, 'login']);
    
    //RecordatorioUsuario
    Route::get('recordatoriousuario/{id}', [RecordatorioUsuarioController::class, 'index']);
    Route::post('recordatoriousuario', [RecordatorioUsuarioController::class, 'store']);
    Route::post('recordatoriousuario/{id}/{opcion}', [RecordatorioUsuarioController::class, 'update']);
    
    //UsuarioPrioridadMensajes
    Route::get('usuarioprioridadmensajes/{id}', [UsuarioPrioridadMensajesController::class, 'index']);
    Route::post('usuarioprioridadmensajes', [UsuarioPrioridadMensajesController::class, 'store']);
    Route::post('usuarioprioridadmensajes/{id}/{opcion}', [UsuarioPrioridadMensajesController::class, 'update']);
    
    //UsuarioSinNotificacionCorreo
    Route::get('usuariosinnotificacioncorreo/', [UsuarioSinNotificacionCorreoController::class, 'index']);
    Route::post('usuariosinnotificacioncorreo', [UsuarioSinNotificacionCorreoController::class, 'store']);
    Route::post('usuariosinnotificacioncorreo/{id}/{opcion}', [UsuarioSinNotificacionCorreoController::class, 'update']);
    
    //SeccionAplicacion
    Route::get('seccionaplicacion/', [SeccionAplicacionController::class, 'index']);
    Route::post('seccionaplicacion', [SeccionAplicacionController::class, 'store']);
    Route::post('seccionaplicacion/{id}/{opcion}', [SeccionAplicacionController::class, 'update']);
    
    //UsuarioRedireccion
    Route::get('usuarioredireccion/', [UsuarioRedireccionController::class, 'index']);
    Route::get('usuarioredireccion/{id}', [UsuarioRedireccionController::class, 'show']);
    Route::post('usuarioredireccion', [UsuarioRedireccionController::class, 'store']);
    Route::post('usuarioredireccion/{id}/{opcion}', [UsuarioRedireccionController::class, 'update']);
    
    //UsuarioRestriccionEmpresa
    Route::get('usuariorestriccionempresa/', [UsuarioRestriccionEmpresaController::class, 'index']);
    Route::get('usuariorestriccionempresa/{id}', [UsuarioRestriccionEmpresaController::class, 'show']);
    Route::post('usuariorestriccionempresa', [UsuarioRestriccionEmpresaController::class, 'store']);
    Route::post('usuariorestriccionempresa/{id}/{opcion}', [UsuarioRestriccionEmpresaController::class, 'update']);
    
    //UsuarioRestriccionTexto
    Route::get('usuariorestricciontexto/', [UsuarioRestriccionTextoController::class, 'index']);
    Route::get('usuariorestricciontexto/{id}', [UsuarioRestriccionTextoController::class, 'show']);
    Route::post('usuariorestricciontexto', [UsuarioRestriccionTextoController::class, 'store']);
    Route::post('usuariorestricciontexto/{id}/{opcion}', [UsuarioRestriccionTextoController::class, 'update']);

    //OcultarColumnaUsuario
    Route::get('ocultarcolumnausuario', [OcultarColumnaUsuarioController::class, 'index']);
    Route::get('ocultarcolumnausuario/{id}', [OcultarColumnaUsuarioController::class, 'show']);
    Route::post('ocultarcolumnausuario', [OcultarColumnaUsuarioController::class, 'store']);
    Route::post('ocultarcolumnausuario/{id}/{opcion}', [OcultarColumnaUsuarioController::class, 'update']);
    
    //UsuarioRecordatorioGrupo
    Route::get('usuariorecordatoriogrupo/', [UsuarioRecordatorioGrupoController::class, 'index']);
    Route::get('usuariorecordatoriogrupo/{id}', [UsuarioRecordatorioGrupoController::class, 'show']);
    Route::get('usuariorecordatoriogrupo/{id}/{id_flujo}', [UsuarioRecordatorioGrupoController::class, 'showByGroup']);
    Route::post('usuariorecordatoriogrupo', [UsuarioRecordatorioGrupoController::class, 'store']);
    Route::post('usuariorecordatoriogrupo/{id}/{opcion}', [UsuarioRecordatorioGrupoController::class, 'update']);
    
    //bitacora
    Route::get('soportebitacora/{inicio}/{fin}', [BitacoraController::class, 'index']);
    Route::get('soportebitacora/{id}', [BitacoraController::class, 'show']);
    
    
    //UsuarioNotificacionTransaccion
    Route::get('usuarionotificaciontransaccion', [UsuarioNotificacionTransaccionController::class, 'index']);
    Route::get('usuarionotificaciontransaccion/{id}', [UsuarioNotificacionTransaccionController::class, 'show']);
    Route::post('usuarionotificaciontransaccion', [UsuarioNotificacionTransaccionController::class, 'store']);
    Route::post('usuarionotificaciontransaccion/{id}/{opcion}', [UsuarioNotificacionTransaccionController::class, 'update']);
    
    //NotificacionTipoDocumentoLote
    Route::get('notificaciontipodocumentolote', [NotificacionTipoDocumentoLoteController::class, 'index']);
    Route::get('notificaciontipodocumentolote/{id}', [NotificacionTipoDocumentoLoteController::class, 'show']);
    Route::post('notificaciontipodocumentolote', [NotificacionTipoDocumentoLoteController::class, 'store']);
    Route::post('notificaciontipodocumentolote/{id}/{opcion}', [NotificacionTipoDocumentoLoteController::class, 'update']);
    
    //TipoDocumentoLote
    Route::get('tipodocumentolote', [TipoDocumentoLoteController::class, 'index']);
    Route::get('tipodocumentolote/{id}', [TipoDocumentoLoteController::class, 'show']);
    Route::post('tipodocumentolote', [TipoDocumentoLoteController::class, 'store']);
    Route::post('tipodocumentolote/{id}/{opcion}', [TipoDocumentoLoteController::class, 'update']);
    
    //Roles
    Route::get('roles', [RolesController::class, 'index']);
    Route::get('roles/{id}', [RolesController::class, 'show']);
    Route::post('roles', [RolesController::class, 'store']);
    Route::post('roles/{id}/{opcion}', [RolesController::class, 'update']);
    
    //RestriccionEmpresa
    Route::get('restriccionempresa', [RestriccionEmpresaController::class, 'index']);
    Route::get('restriccionempresa/{id}', [RestriccionEmpresaController::class, 'show']);
    Route::post('restriccionempresa', [RestriccionEmpresaController::class, 'store']);
    Route::post('restriccionempresa/{id}/{opcion}', [RestriccionEmpresaController::class, 'update']);
    Route::get('empresasdisponibles', [RestriccionEmpresaController::class, 'EmpresasDisponibles']);
    
    //SugerenciaAsignacionGrupo
    Route::get('sugerenciaasignacion', [SugerenciaAsignacionGrupoController::class, 'index']);
    Route::get('sugerenciaasignacion/{id}', [SugerenciaAsignacionGrupoController::class, 'show']);
    Route::post('sugerenciaasignacion', [SugerenciaAsignacionGrupoController::class, 'store']);
    Route::post('sugerenciaasignacion/{id}/{opcion}', [SugerenciaAsignacionGrupoController::class, 'update']);
    Route::get('sugerenciaasignacionflujo/{id}', [SugerenciaAsignacionGrupoController::class, 'SugerenciasPorFlujo']);
    
    //CuentaGrupoAutorizacion
    Route::get('cuentagrupoautorizacion', [CuentaGrupoAutorizacionController::class, 'index']);
    Route::get('cuentagrupoautorizacion/{id}', [CuentaGrupoAutorizacionController::class, 'show']);
    Route::post('cuentagrupoautorizacion', [CuentaGrupoAutorizacionController::class, 'store']);
    Route::post('cuentagrupoautorizacion/{id}/{opcion}', [CuentaGrupoAutorizacionController::class, 'update']);
    
    //Perfiles
    Route::get('perfiles', [PerfilesController::class, 'index']);
    Route::get('perfiles/{id}', [PerfilesController::class, 'show']);
    Route::post('perfiles', [PerfilesController::class, 'store']);
    Route::post('perfiles/{id}/{opcion}', [PerfilesController::class, 'update']);
    Route::get('perfilesparaasignar/{id}', [PerfilesController::class, 'paraasignar']);
    
    //CondicionAutorizacion
    Route::get('condicionautorizacion', [CondicionAutorizacionController::class, 'index']);
    Route::get('condicionautorizacion/{id}', [CondicionAutorizacionController::class, 'show']);
    Route::post('condicionautorizacion', [CondicionAutorizacionController::class, 'store']);
    Route::post('condicionautorizacion/{id}/{opcion}', [CondicionAutorizacionController::class, 'update']);
    
    //EstadoFlujo
    Route::get('estadoflujo', [EstadoFlujoController::class, 'index']);
    Route::get('estadoflujo/{id}', [EstadoFlujoController::class, 'show']);
    Route::post('estadoflujo', [EstadoFlujoController::class, 'store']);
    Route::post('estadoflujo/{id}/{opcion}', [EstadoFlujoController::class, 'update']);
    
    //GrupoAutorizacion
    Route::get('grupoautorizacion', [GrupoAutorizacionController::class, 'index']);
    Route::get('grupoautorizacion/{id}', [GrupoAutorizacionController::class, 'show']);
    Route::post('grupoautorizacion', [GrupoAutorizacionController::class, 'store']);
    Route::post('grupoautorizacion/{id}/{opcion}', [GrupoAutorizacionController::class, 'update']);
    
    //Permisos
    Route::get('permisos', [PermisosController::class, 'index']);
    Route::get('permisos/{id}', [PermisosController::class, 'show']);
    Route::post('permisos', [PermisosController::class, 'store']);
    Route::post('permisos/{id}/{opcion}', [PermisosController::class, 'update']);
    
    //Politicas
    //Route::get('politicas', [PoliticasController::class, 'index']);
    Route::get('politicas/{id}', [PoliticasController::class, 'show']);
    Route::post('politicas', [PoliticasController::class, 'store']);
    Route::post('politicas/{id}/{opcion}', [PoliticasController::class, 'update']);
    
    //Usuarios
    Route::get('usuarios', [UsuariosController::class, 'index']);
    //Route::get('usuarios/{usuario}', [UsuariosController::class, 'login']);
    Route::get('usuarios/{id_grupo}/{id_flujo}', [UsuariosController::class, 'show']);
    Route::post('usuarios', [UsuariosController::class, 'store']);
    Route::post('usuarios/{id}/{opcion}', [UsuariosController::class, 'update']);
    
    //TipoFlujo
    Route::get('tipoflujo', [TipoFlujoController::class, 'index']);
    Route::get('tipoflujo/{id}', [TipoFlujoController::class, 'show']);
    Route::post('tipoflujo', [TipoFlujoController::class, 'store']);
    Route::post('tipoflujo/{id}/{opcion}', [TipoFlujoController::class, 'update']);
    
    //Mensajes
    Route::get('mensajeschat/{id_pago}/{id}', [MensajesController::class, 'showchat']);
    Route::get('mensajeschatapp/{id_pago}/{id}', [MensajesController::class, 'chatapp']);
    Route::get('contadorchat/{id_pago}/{id}', [MensajesController::class, 'showcontador']);
    Route::get('mensajesrecibidos/{id}', [MensajesController::class, 'showrecibidos']);
    Route::post('mensajes', [MensajesController::class, 'store']);
    Route::post('mensajes/{opcion}', [MensajesController::class, 'update']);
    
    //Monedas
    Route::get('monedas', [MonedasController::class, 'index']);
    Route::get('monedas/{id}', [MonedasController::class, 'show']);
    Route::post('monedas', [MonedasController::class, 'store']);
    Route::post('monedas/{id}/{opcion}', [MonedasController::class, 'update']);
    
    //Bancos
    Route::get('bancos', [BancosController::class, 'index']);
    Route::get('bancos/{id}', [BancosController::class, 'show']);
    Route::post('bancos', [BancosController::class, 'store']);
    Route::post('bancos/{id}/{opcion}', [BancosController::class, 'update']);
    
    //Cuentas
    Route::get('cuentas', [CuentasController::class, 'index']);
    Route::get('cuentas/{id}', [CuentasController::class, 'show']);
    Route::post('cuentas', [CuentasController::class, 'store']);
    Route::post('cuentas/{id}/{opcion}', [CuentasController::class, 'update']);
    
    //Usuario Perfil
    Route::get('usuarioperfil', [UsuarioPerfilController::class, 'index']);
    Route::get('usuarioperfil/{id_usuario}/{objeto}/{opcion}', [UsuarioPerfilController::class, 'show']);
    Route::post('usuarioperfil/{codigo}', [UsuarioPerfilController::class, 'store']);
    Route::post('usuarioperfil/{id}/{opcion}', [UsuarioPerfilController::class, 'update']);
    
    //Usuario Grupo
    Route::get('usuariogrupo', [UsuarioGrupoController::class, 'index']);
    Route::get('usuariogrupo/{id}', [UsuarioGrupoController::class, 'show']);
    Route::post('usuariogrupo', [UsuarioGrupoController::class, 'store']);
    Route::post('usuariogrupo/{id}/{opcion}', [UsuarioGrupoController::class, 'update']);
    Route::get('usuariosporgrupo/{id}/{grupo}', [UsuarioGrupoController::class, 'UsuariosPorGrupo']);
    
    //Perfil Rol
    Route::get('perfilrol', [PerfilRolController::class, 'index']);
    Route::get('perfilrol/{id}', [PerfilRolController::class, 'show']);
    Route::post('perfilrol/{codigo}', [PerfilRolController::class, 'store']);
    Route::post('perfilrol/{id}/{opcion}', [PerfilRolController::class, 'update']);
    
    //Rol Permiso
    Route::get('rolpermiso', [RolPermisoController::class, 'index']);
    Route::get('rolpermiso/{id}', [RolPermisoController::class, 'show']);
    Route::post('rolpermiso/{codigo}', [RolPermisoController::class, 'store']);
    Route::post('rolpermiso/{id}/{opcion}', [RolPermisoController::class, 'update']);
    
    //Condición Grupo
    Route::get('condiciongrupo', [CondicionGrupoController::class, 'index']);
    Route::get('condiciongrupo/{id}', [CondicionGrupoController::class, 'show']);
    Route::post('condiciongrupo/{codigo}', [CondicionGrupoController::class, 'store']);
    Route::post('condiciongrupo/{id}/{opcion}', [CondicionGrupoController::class, 'update']);
    
    //Usuario Autorizacion
    Route::get('usuarioautorizacion', [UsuarioAutorizacionController::class, 'index']);
    Route::get('usuarioautorizacion/{id}', [UsuarioAutorizacionController::class, 'show']);
    Route::post('usuarioautorizacion', [UsuarioAutorizacionController::class, 'store']);
    Route::post('usuarioautorizacion/{id}/{opcion}', [UsuarioAutorizacionController::class, 'update']);
    
    //Flujos
    Route::get('reportesflujos/{opcion}/{year}/{mes}', [FlujosController::class, 'index']);
    Route::get('flujo/{id_flujo}', [FlujosController::class, 'show']);
    Route::get('pendientesautorizacioncompleto/{id_usuario}', [FlujosController::class, 'pendientesautorizacioncompleto']);
    Route::get('pendientesautorizacionrecordatorio/{id_usuario}', [FlujosController::class, 'pendientesautorizacionrecordatorio']);
    Route::get('pendientesautorizacion/{tipo}/{id_usuario}', [FlujosController::class, 'pendientesautorizacion']);
    Route::get('pendientescompensacion/{tipo}/{id_usuario}', [FlujosController::class, 'pendientescompensacion']);
    Route::get('rechazadobanco/{tipo}/{id_usuario}', [FlujosController::class, 'rechazadobanco']);
    Route::get('solicitudretorno/{tipo}/{id_usuario}', [FlujosController::class, 'solicitudretorno']);
    Route::post('postflujos', [FlujosController::class, 'store']);
    Route::post('postflujos/{id}', [FlujosController::class, 'update']);
    
    //Reporte Pendientes de Aprobación
    Route::post('pendientesreporte/{id}', [ReportesController::class, 'pendientesreporte']);
    
    //Reporte Cancelado de Origen
    Route::post('canceladosreporte/{id}', [ReportesController::class, 'canceladosreporte']);
    
    //Reporte Pendientes Validación
    Route::post('pendientesvalidacionreporte/{id}', [ReportesController::class, 'pendientesvalidacionreporte']);
    
    //Reporte Rechazado por banco
    Route::post('rechazadosreporte/{id}', [ReportesController::class, 'rechazadosreporte']);
    
    //Reporte Compensados
    Route::post('compensadosreporte/{id}', [ReportesController::class, 'compensadosreporte']);
    
    //Reporte no visados
    Route::post('novisadoreporte/{id}', [ReportesController::class, 'novisadoreporte']);
    
    //Reporte reemplazos
    Route::post('reemplazosreporte/{id}', [ReportesController::class, 'reemplazosreporte']);

    //Reporte Pendientes de compensar
    Route::post('pendientecompensarreporte/{id}', [ReportesController::class, 'pendientecompensarreporte']);
    
    //Grafico semáforo individual
    Route::post('semaforoindividual', [ReportesController::class, 'graficoSemaforoIndividual']);
    //Grafico semáforo individual
    Route::post('semaforo', [ReportesController::class, 'graficoSemaforo']);
    
    //Flujo Solicitud 
    Route::get('flujosolicitud/{id}', [FlujoSolicitudController::class, 'show']); 
    
    //Flujo Orden 
    Route::get('flujoorden/{id}', [FlujoOrdenController::class, 'show']); 
    
    //Flujo Oferta 
    Route::get('flujooferta/{id}', [FlujoOfertaController::class, 'show']); 
    
    //Flujo Ingreso
    Route::get('flujoingreso/{id}', [FlujoIngresoController::class, 'show']);
    
    //Flujo Factura Cantidad 
    Route::get('flujofacturacantidad/{id}', [FlujoFacturaCantidadController::class, 'show']); 
    
    //Flujo Factura Documento
    Route::get('flujofacturadocumento/{id}', [FlujoFacturaDocumentoController::class, 'show']);
    
    //Bitacora
    Route::get('bitacora/{IdFlujo}', [FlujoDetalleController::class, 'bitacora']); 
    
    //FlujoProceso
    Route::get('flujoproceso/{IdFlujo}', [FlujoDetalleController::class, 'flujoproceso']);
    
    //ArchivosFlujo
    Route::get('archivosflujo', [ArchivosFlujoController::class, 'index']);
    Route::get('archivosflujo/{id_usuario}/{id_fujo}', [ArchivosFlujoController::class, 'show']);
    Route::post('archivosflujo', [ArchivosFlujoController::class, 'store']);
    Route::post('archivosflujo/{id}/{opcion}', [ArchivosFlujoController::class, 'update']);
    Route::get('flujosconarchivos/{id}', [ArchivosFlujoController::class, 'flujosconarchivos']);
    
    //FlujoDetalle
    Route::get('flujodetalle', [FlujoDetalleController::class, 'index']); 
    Route::post('flujodetalle', [FlujoDetalleController::class, 'store']);
    Route::post('flujodetalle/{id}', [FlujoDetalleController::class, 'update']);
    
    //Pagos Autorizados
    Route::get('autorizados/{IdUsuario}/{Tipo}', [FlujoDetalleController::class, 'autorizados']);
    
    //Pagos Rechazados
    Route::get('rechazados/{IdUsuario}/{Tipo}', [FlujoDetalleController::class, 'rechazados']);
    
    //Pagos Compensados
    Route::get('compensados/{IdUsuario}/{Tipo}', [FlujoDetalleController::class, 'compensados']);  
    
    //Pagos Enviados a banco
    Route::get('enviadosbanco/{IdUsuario}/{Tipo}', [FlujoDetalleController::class, 'enviadosBanco']);  
    
    //Pagos Aceptados por banco
    Route::get('aceptadosbanco/{IdUsuario}/{Tipo}', [FlujoDetalleController::class, 'aceptadosBanco']);  
    
    //Pagos Cancelados
    Route::get('cancelados/{IdUsuario}/{Tipo}', [FlujoDetalleController::class, 'cancelados']);  
    
    //Pagos reemplazos
    Route::get('reemplazos/{IdUsuario}/{Tipo}', [FlujoDetalleController::class, 'reemplazos']);  
    
    //Notificacion Usuario
    Route::get('notificacion', [NotificacionController::class, 'index']);
    Route::get('notificacion/{id}', [NotificacionController::class, 'show']);
    Route::post('notificacion', [NotificacionController::class, 'store']);
    Route::post('notificacion/{opcion}', [NotificacionController::class, 'update']);
    
    //Log Login
    Route::get('loglogin', [LogLoginController::class, 'index']);
    Route::get('loglogin/{id}', [LogLoginController::class, 'show']);
    Route::post('loglogin', [LogLoginController::class, 'store']);
    Route::post('loglogin/{opcion}', [LogLoginController::class, 'update']);
    
    
    //Paises
    Route::get('paises', [PaisesController::class, 'index']);
    Route::get('paises/{id}', [PaisesController::class, 'show']);
    
    //Cargar datos
    Route::get('cargadatos', [CargaDatosController::class, 'index']);
    Route::get('cargacancelados', [CargaDatosController::class, 'cancelados']);
    Route::get('cargaits', [CargaDatosController::class, 'cargaits']);
    
    Route::get('calculardias/{id}', [RecuperarPasswordController::class, 'calcular']);
    
    Route::post('logpassword', [RecuperarPasswordController::class, 'crear']);
    
    //Sesion Usuario
    Route::get('sesionusuario', [SesionUsuarioController::class, 'index']);
    Route::get('sesionusuario/{id}', [SesionUsuarioController::class, 'show']);
    Route::get('estoyconectado/{id}', [SesionUsuarioController::class, 'isconnected']);
    Route::post('sesionusuario', [SesionUsuarioController::class, 'store']);
    Route::get('sesionusuariogeneral', [SesionUsuarioController::class, 'general']);
     
    //Lotes
    Route::get('lotes/{Tipo}', [LotePagoController::class, 'ListaLotes']);  

    //Consultas de consultor
    Route::get('consultorpendientes/{IdUsuario}', [ConsultorController::class, 'pendientes']);

    Route::get('consultorautorizados/{IdUsuario}', [ConsultorController::class, 'autorizados']);

    Route::get('consultorrechazados/{IdUsuario}', [ConsultorController::class, 'rechazados']);

    Route::get('consultorcompensados/{IdUsuario}', [ConsultorController::class, 'compensados']);

    Route::get('consultorcancelados/{IdUsuario}', [ConsultorController::class, 'cancelados']);

    Route::get('consultorrechazadosbanco/{IdUsuario}', [ConsultorController::class, 'rechazadosBanco']);

    Route::get('consultornovisados/{IdUsuario}', [ConsultorController::class, 'noVisados']);

    Route::get('consultorpagadosbanco/{IdUsuario}', [ConsultorController::class, 'pagadosBanco']);

    Route::get('consultorenviadosbanco/{IdUsuario}', [ConsultorController::class, 'enviadosBanco']);

    Route::get('consultorreemplazados/{IdUsuario}', [ConsultorController::class, 'reemplazados']);
    Route::get('reasignacion/{id}', [ReasignacionController::class, 'show']);
    Route::post('reasignacion', [ReasignacionController::class, 'store']);

    //FlujoCompensarSeleccionado
    Route::get('flujocompensarseleccionado/{id}', [FlujoCompensarSeleccionadoController::class, 'index']);
    Route::get('flujocompensarseleccionado/{id}/{IdFlujo}', [FlujoCompensarSeleccionadoController::class, 'show']); 
    Route::post('flujocompensarseleccionado/{id}', [FlujoCompensarSeleccionadoController::class, 'update']);
});