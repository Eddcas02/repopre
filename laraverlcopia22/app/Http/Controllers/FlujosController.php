<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Flujos;
use App\Models\UsuarioPerfil;
use App\Models\UsuarioAutorizacion;
use App\Models\UsuarioGrupo;
use App\Models\FlujoGrupo;
use App\Models\FlujoOrden;
use App\Models\FlujoDetalle;
use App\Models\Bancos;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Models\FlujoFacturaCantidad;
use App\Models\FlujoFacturaDocumento;
use App\Models\FlujoIngreso;
use App\Models\FlujoOferta;
use App\Models\FlujoSolicitud;
use App\Models\LotePago;
use App\Models\FlujoLotePago;
use App\Models\FlujoNumeroCheque;
use PDF;
use QrCode;
use Illuminate\Support\Facades\Storage;
use Carbon\Carbon;
use App\Exports\ArchivoPrimarioExport;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Support\Facades\Mail;
use App\Mail\EnvioArchivos;
use App\Mail\EnviarNotificacion;
use File;
use App\Models\Politicas;
use App\Models\RestriccionEmpresa;
use App\Models\UsuarioRestriccionEmpresa;
use App\Models\UsuarioRestriccionTexto;
use App\Models\SugerenciaAsignacionGrupo;
use App\Models\UsuarioNotificacionTransaccion;
use App\Models\Usuarios;
use App\Models\NotificacionTipoDocumentoLote;
use App\Models\ZBancoMaestro;
use App\Models\ZEmpresa;
use App\Models\RecordatorioUsuario;
use App\Models\FlujoCambioDias;
use App\Models\Notificacion;
use App\Models\UsuarioSinNotificacionCorreo;

class FlujosController extends Controller
{
    function generarXML($encabezado, $pagos, $xmlDoc) {
        if($encabezado){
            foreach($encabezado as $key => $value) {
                if(is_array($value)) {
                    if(!is_numeric($key)){
                        $subnode = $xmlDoc->addChild($key);
                        FlujosController::generarXML($value, null, $subnode);
                    }else{
                        $subnode = $xmlDoc->addChild("item".$key);
                        FlujosController::generarXML($value, null, $subnode);
                    }
                }else {
                    $xmlDoc->addChild($key,htmlspecialchars($value));
                }
            }
        }
        if($pagos){
            foreach($pagos as $key => $value){
                $attrArr = array();
                $kArray = explode(' ',$key);
                $tag = array_shift($kArray);
                if (count($kArray) > 0) {
                    foreach($kArray as $attrValue) {
                        $attrArr[] = explode('=',$attrValue);                   
                    }
                }
                if(is_array($value)) {
                    if(!is_numeric($key)){
                        $subnode = $xmlDoc->addChild($key);
                        FlujosController::generarXML(null, $value, $subnode);
                    }else{
                        $firstKey = array_key_first($value);
                        if($firstKey == "CdtTrfTxInf"){
                            $subnode = $xmlDoc->addChild("CdtTrfTxInf");
                            FlujosController::generarXML(null, $value["CdtTrfTxInf"], $subnode);
                        }else{
                            $subnode = $xmlDoc->addChild("PmtInf");
                            FlujosController::generarXML(null, $value, $subnode);
                        }
                    }
                }else {
                    $child = $xmlDoc->addChild($tag,htmlspecialchars($value));
                    if (isset($attrArr)) {
                        foreach($attrArr as $attrArrV) {
                            $child->addAttribute($attrArrV[0],$attrArrV[1]);
                        }
                    }
                }
            }
        }
    }

    /* function calcularTotal($array) { 
        $total = 0;          
        foreach($array as $item){
            if(is_array($item)){
                $total+=floatval($item['DocTotal']);
            }
        }               
        return $total;
    } */

    function generarCodigo($length) { 
        $codigo = "";
        $codigo.=substr(str_shuffle("123456789"), 0, 1);
        $codigo.=substr(str_shuffle("0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZ"), 1, $length);
        return $codigo;
    }
    
    function obtenerTipo($tipo, $opcion) {
        if($opcion == 1){
            if($tipo == "TRANSFERENCIA"){
                return "TRF";
            }else{
                return "TRF";
            }
        }else if($opcion == 2) {
            if($tipo != "TRANSFERENCIA"){
                return "BCHQ";
            }else{
                return "";
            }
        }
    }
    function esAhorro($cuenta) {
        if($cuenta == "AHORRO"){
            return "SVGS";
        }else{
            return "";
        }
    }

    function obtenerDatos($codigo, $opcion) {
        $respuesta = "";
        if($opcion == 1){
            $banco = Bancos::select('codigo_transferencia')->where('codigo_SAP', $codigo)->first();
            if(!is_null($banco)){
                $respuesta = $banco->codigo_transferencia;
            }
        }else if($opcion == 2){
            $pais = Bancos::join('Pais', function($join){
                $join->on('Banco.id_pais', 'Pais.IdPais');
            })
            ->select('Pais.CodigoPais')->where('Banco.codigo_SAP', $codigo)->first();
            if(!is_null($pais)){
                $respuesta = $pais->CodigoPais;
            }
        }else if($opcion == 3){
            $BIC = Bancos::join('Pais', function($join){
                $join->on('Banco.id_pais', 'Pais.IdPais');
            })->join('DatosBIC', function($join){
                $join->on('Pais.IdPais', 'DatosBIC.IdPais');
            })
            ->select('DatosBIC.CodigoBic')->where('Banco.codigo_SAP', $codigo)->first();
            if(!is_null($BIC)){
                $respuesta = $BIC->CodigoBic;
            }
        }else if($opcion == 4){
            $direccion = Bancos::select('nombre')->where('codigo_SAP', $codigo)->first();
            if(!is_null($direccion)){
                $respuesta = $direccion->nombre;
            }
        }else if($opcion == 5){ //nit de proveedor

            $existeFlujo = Flujos::where('id_flujo',$codigo)
                    ->where('activo','=',1)
                    ->where('eliminado','=',0)->first();

            if($existeFlujo) {
                //Consulta a WS por correo de proveedor
                $client = new \nusoap_client('http://10.20.30.144/GSION_WS/WSGetFromSAP.asmx?wsdl',true);
                $param = array('sCardCode'=>$existeFlujo->card_code);
                $resultado = $client->call('Get_PROVEEDOR_XML',$param);
                if($client->fault)
                {
                    $error = $client->getError();;
                    if($error)
                    {
                        echo 'Error:' . $client->faultstring;
                    }
                    die();
                }
                $lineas = $resultado['Get_PROVEEDOR_XMLResult']['BOM']['BO']['OCRD']['row'];
                $nitWS = "crcf85@gmail.com";
                if(count($lineas) == count($lineas, COUNT_RECURSIVE))
                {
                    if($lineas['U_NIT'] != ""){
                        $nitWS = $lineas['U_NIT'];
                    }
                }
                if(!is_null($nitWS)){
                    $respuesta = $nitWS;
                }
            }
        }else if($opcion == 6){
            $cardName = FlujoOrden::select('card_name')->where('id_flujo', $codigo)->first();
            if(!is_null($cardName)){
                $respuesta = $cardName->card_name;
            }
        }else if($opcion == 7){
            $Address = FlujoOrden::select('address')->where('id_flujo', $codigo)->first();
            if(!is_null($Address)){
                $respuesta = $Address->address;
            }
        }
        return $respuesta;
    }

    public function index($opcion, $year, $mes)
    {
        $flujos = array();
        if($opcion == '1'){
            if($year != 0 && $mes != 0){
                $flujos = Flujos::join('EstadoFlujo as EstadoFlujo', function($join){
                    $join->on('EstadoFlujo.id_estadoflujo', '=', 'Flujo.estado');
                })
                ->selectRaw(
                    "count(Flujo.id_flujo) as CantidadEstados, EstadoFlujo.descripcion as estado"
                )
                ->where('Flujo.estado', '>', 0)
                ->where('Flujo.activo', '=',1)
                ->where('Flujo.eliminado', '=',0)
                ->whereMonth('Flujo.doc_date', $mes)->whereYear('Flujo.doc_date', $year)
                ->groupBy('Flujo.estado')->orderBy('Flujo.estado')->get();
            }else{
                $flujos = Flujos::join('EstadoFlujo as EstadoFlujo', function($join){
                    $join->on('EstadoFlujo.id_estadoflujo', '=', 'Flujo.estado');
                })
                ->selectRaw(
                    "count(Flujo.id_flujo) as CantidadEstados, EstadoFlujo.descripcion as estado"
                )
                ->where('Flujo.estado', '>', 0)
                ->where('Flujo.activo', '=',1)
                ->where('Flujo.eliminado', '=',0)
                ->groupBy('Flujo.estado')->orderBy('Flujo.estado')->get();
            }
        }else if($opcion == '2'){
            if($year != 0 && $mes != 0){
                //Cambiar a fecha de autorización
                $flujos = Flujos::leftJoin('FlujoDetalle', function($join){
                    $join->on('FlujoDetalle.IdFlujo', '=', 'Flujo.id_flujo')
                    ->on('FlujoDetalle.IdEstadoFlujo','=','Flujo.estado');
                })->selectRaw(
                    "count(Flujo.id_flujo) as PagosAprobados, Flujo.tipo"
                )
                ->where('Flujo.estado', 5)
                ->where('Flujo.activo', '=',1)
                ->where('Flujo.eliminado', '=',0)
                ->where('FlujoDetalle.FlujoActivo', 1)
                //->whereMonth('Flujo.doc_date', $mes)->whereYear('Flujo.doc_date', $year)
                ->whereMonth('FlujoDetalle.Fecha', $mes)->whereYear('FlujoDetalle.Fecha', $year)
                ->groupBy('Flujo.tipo')->orderBy('Flujo.tipo')->get();
            }else{
                $flujos = Flujos::selectRaw(
                    "count(Flujo.id_flujo) as PagosAprobados, Flujo.tipo"
                )
                ->where('Flujo.estado', 5)
                ->where('Flujo.activo', '=',1)
                ->where('Flujo.eliminado', '=',0)
                ->groupBy('Flujo.tipo')->orderBy('Flujo.tipo')->get();
            }
        }else if($opcion == '3'){
            $flujos = DB::select('CALL TiempoPromedioEstadosFlujoListar()', array());
		}else if($opcion == '4'){
            $flujosTmp = DB::select('CALL ReporteSemaforoListar()', array());
            $cantidadRojo = 0;
            $cantidadAmarillo = 0;
            $cantidadVerde = 0;
            foreach($flujosTmp as $item){
                if($item->nombreSemaforo == "ROJO"){
                    $cantidadRojo = $item->cantidad;
                }
                if($item->nombreSemaforo == "AMARILLO"){
                    $cantidadAmarillo = $item->cantidad;
                }
                if($item->nombreSemaforo == "VERDE"){
                    $cantidadVerde = $item->cantidad;
                }
            }
            $flujos = array();
            $flujos[] = array(
                "nombreSemaforo" => "ROJO",
                "cantidad" => $cantidadRojo
            );
            $flujos[] = array(
                "nombreSemaforo" => "AMARILLO",
                "cantidad" => $cantidadAmarillo
            );
            $flujos[] = array(
                "nombreSemaforo" => "VERDE",
                "cantidad" => $cantidadVerde
            );
		}        
		$datos = array();
        $datos['flujos'] = $flujos;
        return $datos;
    }

    public function show($id_flujo)
    {
        $EmpresasRestringidas = RestriccionEmpresa::select(['empresa_codigo'])->where('eliminado',0)
        ->where('activo',1)->get()->toArray();
        $flujos = Flujos::leftJoin('GrupoAutorizacion', function($join){
            $join->on('GrupoAutorizacion.id_grupoautorizacion', '=', 'Flujo.id_grupoautorizacion');
		})->selectRaw(
			"Flujo.id_flujo,
			 Flujo.id_tipoflujo,
			 Flujo.doc_num,
			 Flujo.tipo,
			 DATE_FORMAT(Flujo.tax_date,'%Y-%m-%d')as tax_date,
			 DATE_FORMAT(Flujo.doc_date,'%Y-%m-%d')as doc_date,
			 Flujo.card_code,
			 Flujo.card_name,
             Flujo.en_favor_de,
			 Flujo.comments,
			 Flujo.doc_total,
			 Flujo.doc_curr,
			 Flujo.bank_code,
			 Flujo.dfl_account,
			 Flujo.tipo_cuenta_destino,
			 Flujo.cuenta_orgien,
			 Flujo.empresa_codigo,
			 Flujo.empresa_nombre,
			 Flujo.cheque,
			 Flujo.email,
			 Flujo.estado,
			 Flujo.nivel,
			 Flujo.id_grupoautorizacion,
			 GrupoAutorizacion.identificador as grupoautorizacion,
             Flujo.dias_credito,
             Flujo.nombre_condicion_pago_dias as condicion_pago,
             (select DATE_FORMAT(MAX(fd.Fecha),'%Y-%m-%d') from FlujoDetalle as fd where fd.IdEstadoFlujo = 1
             and fd.IdFlujo = Flujo.id_flujo) as creation_date,
             CASE
                WHEN Flujo.tipo = 'BANCARIO' THEN (select count(FC.id_flujo) from FlujoNumeroCheque as FC where FC.id_flujo = Flujo.id_flujo)
                WHEN Flujo.tipo = 'TRANSFERENCIA' THEN 1
                WHEN Flujo.tipo = 'INTERNA' THEN 1
             END as TieneCheque"
		)
		->where('Flujo.id_flujo', $id_flujo)
        //->whereIn('Flujo.empresa_codigo', $EmpresasRestringidas)
        ->orderBy('Flujo.id_flujo', 'ASC')->get();
        $datos = array();
        $datos['flujos'] = $flujos;
        return $datos;
    }

    public function pendientesautorizacionrecordatorio($id_usuario)
    {
        $temporal = UsuarioAutorizacion::select('UsuarioAutorizacion.id_usuarioaprobador')
        ->where('UsuarioAutorizacion.id_usuariotemporal', $id_usuario)
        ->where('UsuarioAutorizacion.fecha_inicio','<=', 
            date("Y-m-d", strtotime('-6 hour', strtotime(now()))))
        ->where('UsuarioAutorizacion.fecha_final', '>=',
            date("Y-m-d", strtotime('-6 hour', strtotime(now()))))->get();

        $IdUsuario = 0;

        if($temporal->count() > 0){
            $usuario = $temporal->toArray();
            foreach($usuario as $item){
                $IdUsuario = $item['id_usuarioaprobador'];
            }
        }else{
            $IdUsuario = $id_usuario;
        }

        $usuariogrupo = UsuarioGrupo::join('GrupoAutorizacion', function($join){
            $join->on('UsuarioGrupoAutorizacion.id_grupoautorizacion', '=',
            'GrupoAutorizacion.id_grupoautorizacion');
        })
        ->select('UsuarioGrupoAutorizacion.id_grupoautorizacion', 'UsuarioGrupoAutorizacion.nivel')
        ->where('UsuarioGrupoAutorizacion.id_usuario', $IdUsuario)
        ->where('UsuarioGrupoAutorizacion.activo', 1)->where('UsuarioGrupoAutorizacion.eliminado', 0)
        ->where('GrupoAutorizacion.activo', 1)->where('GrupoAutorizacion.eliminado', 0)
        ->get();

        $flujos = array();
        $grupos = array();
        $ListaGruposUsuarios = array();
        $i = 0;
        if($usuariogrupo->count()>0){
            $ListaGruposUsuarios = $usuariogrupo->toArray();
        }
        foreach($ListaGruposUsuarios as $item){
            $grupos[$i] = $item['id_grupoautorizacion'];
            $i += 1;
        }

        $EmpresasRestringidasLista = RestriccionEmpresa::select(['empresa_codigo'])->where('eliminado',0)
        ->where('activo',1)->get()->toArray();

        $EmpresasDeUsuario = UsuarioRestriccionEmpresa::select(['empresa_codigo'])
        ->where('id_usuario',$IdUsuario)
        ->where('eliminado',0)
        ->where('activo',1)
        ->get()->toArray();

        if(!empty($EmpresasDeUsuario)){
            $EmpresasRestringidas = Flujos::select(['empresa_codigo'])
            ->whereIn('empresa_codigo', $EmpresasDeUsuario)
            ->groupBy('empresa_codigo')
            ->groupBy('empresa_nombre')
            ->get()->toArray();
        }else{
            $EmpresasRestringidas = Flujos::select(['empresa_codigo'])
            ->whereNotIn('empresa_codigo', $EmpresasRestringidasLista)
            ->groupBy('empresa_codigo')
            ->groupBy('empresa_nombre')
            ->get()->toArray();
        }

        $listaWhereTextosTmp = UsuarioRestriccionTexto::select(['texto'])
        ->where('id_usuario',$IdUsuario)
        ->where('eliminado',0)
        ->where('activo',1)
        ->get()->toArray();

        $listaWhereTextos = array();

        if(count($listaWhereTextosTmp) > 0){
            foreach($listaWhereTextosTmp as $item){
                $listaWhereTextos[] = $item['texto'];
            }
        }

        $ListadoRecordatorio = RecordatorioUsuario::where('activo',1)->where('eliminado',0)
        ->where('id_usuario','=', $IdUsuario)->pluck('id_flujo')->toArray();
        
        $ListaFlujosGrupo = Flujos::selectRaw(
            "Flujo.id_flujo,
            Flujo.id_tipoflujo,
            Flujo.doc_num,
            Flujo.tipo,
            DATE_FORMAT(Flujo.tax_date,'%Y-%m-%d')as tax_date,
            DATE_FORMAT(Flujo.doc_date,'%Y-%m-%d')as doc_date,
            Flujo.comments,
            Flujo.activo,
            Flujo.estado,
            Flujo.nivel,
            Flujo.id_grupoautorizacion,
            Flujo.card_name,
            Flujo.en_favor_de,
            Flujo.doc_total,
            Flujo.doc_curr,
            Flujo.empresa_nombre,
            (select DATE_FORMAT(MAX(fd.Fecha),'%Y-%m-%d') from FlujoDetalle as fd where fd.IdEstadoFlujo = 5
            and fd.IdFlujo = Flujo.id_flujo) as aut_date,
            (select DATE_FORMAT(MAX(fd.Fecha),'%Y-%m-%d') from FlujoDetalle as fd where fd.IdEstadoFlujo = 1
            and fd.IdFlujo = Flujo.id_flujo) as creation_date,
            CASE
                WHEN Flujo.tipo = 'BANCARIO' THEN (select count(FC.id_flujo) from FlujoNumeroCheque as FC where FC.id_flujo = Flujo.id_flujo)
                WHEN Flujo.tipo = 'TRANSFERENCIA' THEN 1
                WHEN Flujo.tipo = 'INTERNA' THEN 1
            END as TieneCheque,
            '1' as PuedoAutorizar
            "
        )
        ->whereIn('Flujo.id_grupoautorizacion', $grupos)
        ->whereIn('Flujo.id_flujo', $ListadoRecordatorio)
        ->whereIn('Flujo.estado', [3,4])
        ->where('Flujo.activo', '=',1)
        ->where('Flujo.eliminado', '=',0)
        ->whereIn('Flujo.empresa_codigo', $EmpresasRestringidas)
        ->where(function ($q) use ($listaWhereTextos) {
            foreach ($listaWhereTextos as $value) {
                 $q->orWhere('Flujo.comments', 'like', "%{$value}%");
            }
        })
        ->orderBy('Flujo.id_flujo', 'ASC')  
        ->get();

        $j = 0;
        foreach($ListaGruposUsuarios as $item){
            foreach($ListaFlujosGrupo as $itemFlujo){
                if($item['id_grupoautorizacion'] == $itemFlujo['id_grupoautorizacion'] && 
                $item['nivel'] == $itemFlujo['nivel']){
                    $flujos[$j] = $itemFlujo;
                    $j += 1;
                }elseif($item['id_grupoautorizacion'] == $itemFlujo['id_grupoautorizacion'] && 
                $itemFlujo['estado'] == 3 && $itemFlujo['nivel'] == 0 && $item['nivel'] == 1){
                    $flujos[$j] = $itemFlujo;
                    $j += 1;
                }
            }
        }
        
        $datos = array();
        $datos['flujos'] = $flujos;
        return $datos;        
    }

    public function pendientesautorizacion($tipo, $id_usuario)
    {
        $temporal = UsuarioAutorizacion::select('UsuarioAutorizacion.id_usuarioaprobador')
        ->where('UsuarioAutorizacion.id_usuariotemporal', $id_usuario)
        ->where('UsuarioAutorizacion.fecha_inicio','<=', 
            date("Y-m-d", strtotime('-6 hour', strtotime(now()))))
        ->where('UsuarioAutorizacion.fecha_final', '>=',
            date("Y-m-d", strtotime('-6 hour', strtotime(now()))))->get();

        $IdUsuario = 0;

        if($temporal->count() > 0){
            $usuario = $temporal->toArray();
            foreach($usuario as $item){
                $IdUsuario = $item['id_usuarioaprobador'];
            }
        }else{
            $IdUsuario = $id_usuario;
        }
        
        $usuarioperfil = UsuarioPerfil::join('PerfilRol', function($join){
            $join->on('UsuarioPerfil.id_perfil', '=', 'PerfilRol.id_perfil');
        })->join('perfiles', function($join){
            $join->on('UsuarioPerfil.id_perfil', '=', 'perfiles.id_perfil');
        })->join('roles', function($join){
            $join->on('PerfilRol.id_rol', '=', 'roles.id_rol');
        })->join('RolPermiso', function($join){
            $join->on('PerfilRol.id_rol', '=', 'RolPermiso.id_rol');
        })->join('permisos', function($join){
            $join->on('RolPermiso.id_permiso', '=', 'permisos.id_permiso');
        })
        ->select('roles.objeto', 'RolPermiso.id_permiso', 'permisos.descripcion')
        ->where('UsuarioPerfil.activo', 1)->where('UsuarioPerfil.eliminado', 0)
        ->where('perfiles.activo', 1)->where('perfiles.eliminado', 0)
        ->where('PerfilRol.activo', 1)->where('PerfilRol.eliminado', 0)
        ->where('roles.activo', 1)->where('roles.eliminado', 0)
        ->where('RolPermiso.activo', 1)->where('RolPermiso.eliminado', 0)
        ->where('permisos.activo', 1)->where('permisos.eliminado', 0)
        ->where('roles.objeto', "Modulo Autorizacion Pagos")
        ->where('UsuarioPerfil.id_usuario', $IdUsuario)
        ->orderBy('RolPermiso.id_permiso', 'ASC')
        ->get();
        
        $primernivel = UsuarioGrupo::join('Flujo', function($join){
            $join->on('UsuarioGrupoAutorizacion.id_grupoautorizacion', '=', 
            'Flujo.id_grupoautorizacion');
        })->join('GrupoAutorizacion', function($join){
            $join->on('Flujo.id_grupoautorizacion', '=', 
            'GrupoAutorizacion.id_grupoautorizacion');
        })
        ->select('UsuarioGrupoAutorizacion.id_usuario')
        ->where('UsuarioGrupoAutorizacion.id_usuario', $IdUsuario)
        ->where('Flujo.estado', 3)->where('Flujo.nivel', 0)
        ->where('UsuarioGrupoAutorizacion.nivel', 1)
        ->where('UsuarioGrupoAutorizacion.activo', 1)->where('UsuarioGrupoAutorizacion.eliminado', 0)
        ->where('GrupoAutorizacion.activo', 1)->where('GrupoAutorizacion.eliminado', 0)
        ->get();     

        $usuariogrupo = UsuarioGrupo::join('GrupoAutorizacion', function($join){
            $join->on('UsuarioGrupoAutorizacion.id_grupoautorizacion', '=',
            'GrupoAutorizacion.id_grupoautorizacion');
        })
        ->select('UsuarioGrupoAutorizacion.id_grupoautorizacion', 'UsuarioGrupoAutorizacion.nivel')
        ->where('UsuarioGrupoAutorizacion.id_usuario', $IdUsuario)
        ->where('UsuarioGrupoAutorizacion.activo', 1)->where('UsuarioGrupoAutorizacion.eliminado', 0)
        ->where('GrupoAutorizacion.activo', 1)->where('GrupoAutorizacion.eliminado', 0)
        ->get();

        $flujos = array();
        $estados = array();
        $grupos = array();
        $ListaGruposUsuarios = array();
        $permisos = array();
        $i = 0;
        if($usuariogrupo->count()>0){
            $ListaGruposUsuarios = $usuariogrupo->toArray();
        }
        if($usuarioperfil->count()>0){
            $permisos = $usuarioperfil->toArray();
        }
        foreach($ListaGruposUsuarios as $item){
            $grupos[$i] = $item['id_grupoautorizacion'];
            $i += 1;
        }
        $consultor = 0;
        foreach($permisos as $item){
            if($item['id_permiso'] == 7){
                $estados[] = 1;
                $estados[] = 2;
                $estados[] = 3;
                $estados[] = 4;
                $estados[] = 10;
                $estados[] = 11;
            }
            if($item['id_permiso'] == 6){
                $estados[] = 2;
            }
            if($item['descripcion'] == "Revisar"){
                $estados[] = 3;
                $estados[] = 4;
                $estados[] = 10;
                $estados[] = 11;
            }
            if($item['descripcion'] == "Visualizar_completo"){
                $consultor = 1;
                $estados[] = 1;
                $estados[] = 2;
                $estados[] = 3;
                $estados[] = 4;
                $estados[] = 10;
                $estados[] = 11;
            }
        }
        /* if($primernivel->count() > 0){
            $estados[] = 3;
        } */
        //Cambiar nombre a variable de listado
        //Cambiar todos los NotIn por In
        $EmpresasRestringidasLista = RestriccionEmpresa::select(['empresa_codigo'])->where('eliminado',0)
        ->where('activo',1)->get()->toArray();

        $EmpresasDeUsuario = UsuarioRestriccionEmpresa::select(['empresa_codigo'])
        ->where('id_usuario',$IdUsuario)
        ->where('eliminado',0)
        ->where('activo',1)
        ->get()->toArray();

        if(!empty($EmpresasDeUsuario)){
            $EmpresasRestringidas = Flujos::select(['empresa_codigo'])
            ->whereIn('empresa_codigo', $EmpresasDeUsuario)
            ->groupBy('empresa_codigo')
            ->groupBy('empresa_nombre')
            ->get()->toArray();
        }else{
            $EmpresasRestringidas = Flujos::select(['empresa_codigo'])
            ->whereNotIn('empresa_codigo', $EmpresasRestringidasLista)
            ->groupBy('empresa_codigo')
            ->groupBy('empresa_nombre')
            ->get()->toArray();
        }

        $listaWhereTextosTmp = UsuarioRestriccionTexto::select(['texto'])
        ->where('id_usuario',$IdUsuario)
        ->where('eliminado',0)
        ->where('activo',1)
        ->get()->toArray();

        $listaWhereTextos = array();

        if(count($listaWhereTextosTmp) > 0){
            foreach($listaWhereTextosTmp as $item){
                $listaWhereTextos[] = $item['texto'];
            }
        }

        $ListaFlujosGrupo = array();
        
        if($consultor == 0){

            $politicaVerde = Politicas::where('identificador','=','_SEMAFORO_VERDE')
            ->where('activo',1)->where('eliminado',0)->first();
            $valorVerde = intval($politicaVerde->valor);
    
            $politicaAmarillo = Politicas::where('identificador','=','_SEMAFORO_AMARILLO')
            ->where('activo',1)->where('eliminado',0)->first();
            $valorAmarillo = intval($politicaAmarillo->valor);   

            $ListaFlujosGrupo = Flujos::selectRaw(
                "Flujo.id_flujo,
                Flujo.id_tipoflujo,
                Flujo.doc_num,
                Flujo.tipo,
                DATE_FORMAT(Flujo.tax_date,'%Y-%m-%d')as tax_date,
                DATE_FORMAT(Flujo.doc_date,'%Y-%m-%d')as doc_date,
                Flujo.comments,
                Flujo.activo,
                Flujo.estado,
                Flujo.nivel,
                Flujo.id_grupoautorizacion,
                Flujo.card_name,
                Flujo.en_favor_de,
                Flujo.doc_total,
                Flujo.doc_curr,
                Flujo.empresa_nombre,
                (select DATE_FORMAT(MAX(fd.Fecha),'%Y-%m-%d') from FlujoDetalle as fd where fd.IdEstadoFlujo = 5
                and fd.IdFlujo = Flujo.id_flujo) as aut_date,
                (select DATE_FORMAT(MAX(fd.Fecha),'%Y-%m-%d') from FlujoDetalle as fd where fd.IdEstadoFlujo = 1
                and fd.IdFlujo = Flujo.id_flujo) as creation_date,
                CASE
                   WHEN Flujo.tipo = 'BANCARIO' THEN (select count(FC.id_flujo) from FlujoNumeroCheque as FC where FC.id_flujo = Flujo.id_flujo)
                   WHEN Flujo.tipo = 'TRANSFERENCIA' THEN 1
                   WHEN Flujo.tipo = 'INTERNA' THEN 1
                END as TieneCheque,
                '1' as PuedoAutorizar,
                CASE
                    WHEN Flujo.ConDuda = 1 THEN 'AZUL'
                    WHEN (( DATEDIFF(NOW(),Flujo.doc_date) * 100)/Flujo.dias_credito) <= ".$valorVerde." THEN 'VERDE'
                    WHEN (( DATEDIFF(NOW(),Flujo.doc_date) * 100)/Flujo.dias_credito) > ".$valorVerde." and (( DATEDIFF(NOW(),Flujo.doc_date) * 100)/Flujo.dias_credito) <= ".$valorAmarillo." THEN 'AMARILLO'
                    WHEN (( DATEDIFF(NOW(),Flujo.doc_date) * 100)/Flujo.dias_credito) > ".$valorAmarillo." THEN 'ROJO'
                    WHEN Flujo.dias_credito = 0 THEN 'ROJO'
                    WHEN Flujo.dias_credito is null THEN 'ROJO'
                    ELSE 'NO'
                END as colorSemaforo,
                (select COUNT(RU.id_flujo) from RecordatorioUsuario as RU where RU.id_flujo = Flujo.id_flujo  
                and activo = 1 and eliminado = 0
                and (RU.id_usuario_origen = ".$IdUsuario." 
                or RU.id_usuario = ".$IdUsuario.")) as marcarRecordado
                "
            )
            ->where('Flujo.tipo', $tipo)
            ->whereIn('Flujo.id_grupoautorizacion', $grupos)
            ->whereIn('Flujo.estado', [3,4])
            ->where('Flujo.activo', '=',1)
            ->where('Flujo.eliminado', '=',0)
            ->whereIn('Flujo.empresa_codigo', $EmpresasRestringidas)
            ->where(function ($q) use ($listaWhereTextos) {
                foreach ($listaWhereTextos as $value) {
                     $q->orWhere('Flujo.comments', 'like', "%{$value}%");
                }
            })
            ->orderBy('Flujo.id_flujo', 'ASC')  
            ->get();
        }

        $flujosNoMostrar = array();

        if(count($ListaFlujosGrupo) > 0){
            foreach($ListaFlujosGrupo as $item){
                $flujosNoMostrar[] = $item->id_flujo;
            }
        }

        $ListaFlujosEstado = Flujos::selectRaw(
            "Flujo.id_flujo,
            Flujo.id_tipoflujo,
            Flujo.doc_num,
            Flujo.tipo,
            DATE_FORMAT(Flujo.tax_date,'%Y-%m-%d')as tax_date,
            DATE_FORMAT(Flujo.doc_date,'%Y-%m-%d')as doc_date,
            Flujo.comments,
            Flujo.activo,
            Flujo.estado,
            Flujo.nivel,
            Flujo.id_grupoautorizacion,
            Flujo.card_name,
            Flujo.en_favor_de,
            Flujo.doc_total,
            Flujo.doc_curr,
            Flujo.empresa_nombre,
            (select DATE_FORMAT(MAX(fd.Fecha),'%Y-%m-%d') from FlujoDetalle as fd where fd.IdEstadoFlujo = 5
            and fd.IdFlujo = Flujo.id_flujo) as aut_date,
            (select DATE_FORMAT(MAX(fd.Fecha),'%Y-%m-%d') from FlujoDetalle as fd where fd.IdEstadoFlujo = 1
            and fd.IdFlujo = Flujo.id_flujo) as creation_date,
            CASE
               WHEN Flujo.tipo = 'BANCARIO' THEN (select count(FC.id_flujo) from FlujoNumeroCheque as FC where FC.id_flujo = Flujo.id_flujo)
               WHEN Flujo.tipo = 'TRANSFERENCIA' THEN 1
               WHEN Flujo.tipo = 'INTERNA' THEN 1
            END as TieneCheque,
            '0' as PuedoAutorizar,
            CASE
                WHEN Flujo.ConDuda = 1 THEN 'AZUL'
                ELSE 'NO'
            END as colorSemaforo,
            (select COUNT(RU.id_flujo) from RecordatorioUsuario as RU where RU.id_flujo = Flujo.id_flujo  
            and activo = 1 and eliminado = 0
            and (RU.id_usuario_origen = ".$IdUsuario." 
            or RU.id_usuario = ".$IdUsuario.")) as marcarRecordado
            "
        )
        ->where('Flujo.tipo', $tipo)
        ->whereIn('Flujo.estado', $estados)
        ->where('Flujo.activo', '=',1)
        ->where('Flujo.eliminado', '=',0)
        ->whereIn('Flujo.empresa_codigo', $EmpresasRestringidas)
        ->where(function ($q) use ($listaWhereTextos) {
            foreach ($listaWhereTextos as $value) {
                 $q->orWhere('Flujo.comments', 'like', "%{$value}%");
            }
        })
        ->whereNotIn('Flujo.id_flujo', $flujosNoMostrar)
        ->orderBy('Flujo.id_flujo', 'ASC')  
        ->get();

        $j = 0;
        if($consultor == 0){
            foreach($ListaGruposUsuarios as $item){
                foreach($ListaFlujosGrupo as $itemFlujo){
                    if($item['id_grupoautorizacion'] == $itemFlujo['id_grupoautorizacion'] && 
                    $item['nivel'] == $itemFlujo['nivel']){
                        $flujos[$j] = $itemFlujo;
                        $j += 1;
                    }elseif($item['id_grupoautorizacion'] == $itemFlujo['id_grupoautorizacion'] && 
                    $itemFlujo['estado'] == 3 && $itemFlujo['nivel'] == 0 && $item['nivel'] == 1){
                        $flujos[$j] = $itemFlujo;
                        $j += 1;
                    }
                }
            }       
        } 
        foreach($ListaFlujosEstado as $item){
            $flujos[$j] = $item;
            $j += 1;
        }
        $datos = array();
        $datos['flujos'] = $flujos;
        return $datos;        
    }

    public function pendientesautorizacioncompleto($id_usuario)
    {
        $IdUsuario = $id_usuario;   

        $usuariogrupo = UsuarioGrupo::join('GrupoAutorizacion', function($join){
            $join->on('UsuarioGrupoAutorizacion.id_grupoautorizacion', '=',
            'GrupoAutorizacion.id_grupoautorizacion');
        })
        ->select('UsuarioGrupoAutorizacion.id_grupoautorizacion', 'UsuarioGrupoAutorizacion.nivel')
        ->where('UsuarioGrupoAutorizacion.id_usuario', $IdUsuario)
        ->where('UsuarioGrupoAutorizacion.activo', 1)->where('UsuarioGrupoAutorizacion.eliminado', 0)
        ->where('GrupoAutorizacion.activo', 1)->where('GrupoAutorizacion.eliminado', 0)
        ->get();

        $flujos = array();
        $grupos = array();
        $ListaGruposUsuarios = array();
        $i = 0;
        if($usuariogrupo->count()>0){
            $ListaGruposUsuarios = $usuariogrupo->toArray();
        }
        foreach($ListaGruposUsuarios as $item){
            $grupos[$i] = $item['id_grupoautorizacion'];
            $i += 1;
        }
        $consultor = 0;
        
        $EmpresasRestringidasLista = RestriccionEmpresa::select(['empresa_codigo'])->where('eliminado',0)
        ->where('activo',1)->get()->toArray();

        $EmpresasDeUsuario = UsuarioRestriccionEmpresa::select(['empresa_codigo'])
        ->where('id_usuario',$IdUsuario)
        ->where('eliminado',0)
        ->where('activo',1)
        ->get()->toArray();

        if(!empty($EmpresasDeUsuario)){
            $EmpresasRestringidas = Flujos::select(['empresa_codigo'])
            ->whereIn('empresa_codigo', $EmpresasDeUsuario)
            ->groupBy('empresa_codigo')
            ->groupBy('empresa_nombre')
            ->get()->toArray();
        }else{
            $EmpresasRestringidas = Flujos::select(['empresa_codigo'])
            ->whereNotIn('empresa_codigo', $EmpresasRestringidasLista)
            ->groupBy('empresa_codigo')
            ->groupBy('empresa_nombre')
            ->get()->toArray();
        }

        $listaWhereTextosTmp = UsuarioRestriccionTexto::select(['texto'])
        ->where('id_usuario',$IdUsuario)
        ->where('eliminado',0)
        ->where('activo',1)
        ->get()->toArray();

        $listaWhereTextos = array();

        if(count($listaWhereTextosTmp) > 0){
            foreach($listaWhereTextosTmp as $item){
                $listaWhereTextos[] = $item['texto'];
            }
        }

        $ListaFlujosGrupo = array();
        
        $politicaVerde = Politicas::where('identificador','=','_SEMAFORO_VERDE')
        ->where('activo',1)->where('eliminado',0)->first();
        $valorVerde = intval($politicaVerde->valor);

        $politicaAmarillo = Politicas::where('identificador','=','_SEMAFORO_AMARILLO')
        ->where('activo',1)->where('eliminado',0)->first();
        $valorAmarillo = intval($politicaAmarillo->valor);   

        $ListaFlujosGrupo = Flujos::selectRaw(
            "Flujo.id_flujo,
            Flujo.id_tipoflujo,
            Flujo.doc_num,
            Flujo.tipo,
            DATE_FORMAT(Flujo.tax_date,'%Y-%m-%d')as tax_date,
            DATE_FORMAT(Flujo.doc_date,'%Y-%m-%d')as doc_date,
            Flujo.comments,
            Flujo.activo,
            Flujo.estado,
            Flujo.nivel,
            Flujo.id_grupoautorizacion,
            Flujo.card_name,
            Flujo.en_favor_de,
            Flujo.doc_total,
            Flujo.doc_curr,
            Flujo.empresa_nombre,
            (select DATE_FORMAT(MAX(fd.Fecha),'%Y-%m-%d') from FlujoDetalle as fd where fd.IdEstadoFlujo = 5
            and fd.IdFlujo = Flujo.id_flujo) as aut_date,
            (select DATE_FORMAT(MAX(fd.Fecha),'%Y-%m-%d') from FlujoDetalle as fd where fd.IdEstadoFlujo = 1
            and fd.IdFlujo = Flujo.id_flujo) as creation_date,
            CASE
                WHEN Flujo.tipo = 'BANCARIO' THEN (select count(FC.id_flujo) from FlujoNumeroCheque as FC where FC.id_flujo = Flujo.id_flujo)
                WHEN Flujo.tipo = 'TRANSFERENCIA' THEN 1
                WHEN Flujo.tipo = 'INTERNA' THEN 1
            END as TieneCheque,
            '1' as PuedoAutorizar,
            CASE
                WHEN Flujo.ConDuda = 1 THEN 'AZUL'
                WHEN (( DATEDIFF(NOW(),Flujo.doc_date) * 100)/Flujo.dias_credito) <= ".$valorVerde." THEN 'VERDE'
                WHEN (( DATEDIFF(NOW(),Flujo.doc_date) * 100)/Flujo.dias_credito) > ".$valorVerde." and (( DATEDIFF(NOW(),Flujo.doc_date) * 100)/Flujo.dias_credito) <= ".$valorAmarillo." THEN 'AMARILLO'
                WHEN (( DATEDIFF(NOW(),Flujo.doc_date) * 100)/Flujo.dias_credito) > ".$valorAmarillo." THEN 'ROJO'
                WHEN Flujo.dias_credito = 0 THEN 'ROJO'
                WHEN Flujo.dias_credito is null THEN 'ROJO'
                ELSE 'NO'
            END as colorSemaforo,
            (select COUNT(RU.id_flujo) from RecordatorioUsuario as RU where RU.id_flujo = Flujo.id_flujo and RU.id_usuario_origen = ".$IdUsuario." 
            and activo = 1 and eliminado = 0) as marcarRecordado
            "
        )
        ->whereIn('Flujo.id_grupoautorizacion', $grupos)
        ->whereIn('Flujo.estado', [3,4])
        ->where('Flujo.activo', '=',1)
        ->where('Flujo.eliminado', '=',0)
        ->whereIn('Flujo.empresa_codigo', $EmpresasRestringidas)
        ->where(function ($q) use ($listaWhereTextos) {
            foreach ($listaWhereTextos as $value) {
                 $q->orWhere('Flujo.comments', 'like', "%{$value}%");
            }
        })
        ->orderBy('Flujo.id_flujo', 'ASC')  
        ->get();

        $j = 0;
        if($consultor == 0){
            foreach($ListaGruposUsuarios as $item){
                foreach($ListaFlujosGrupo as $itemFlujo){
                    if($item['id_grupoautorizacion'] == $itemFlujo['id_grupoautorizacion'] && 
                    $item['nivel'] == $itemFlujo['nivel']){
                        $flujos[$j] = $itemFlujo;
                        $j += 1;
                    }elseif($item['id_grupoautorizacion'] == $itemFlujo['id_grupoautorizacion'] && 
                    $itemFlujo['estado'] == 3 && $itemFlujo['nivel'] == 0 && $item['nivel'] == 1){
                        $flujos[$j] = $itemFlujo;
                        $j += 1;
                    }
                }
            }       
        } 
        $datos = array();
        $datos['flujos'] = $flujos;
        return $datos;        
    }

    public function pendientescompensacion($tipo, $id_usuario)
    {
        $temporal = UsuarioAutorizacion::select('UsuarioAutorizacion.id_usuarioaprobador')
        ->where('UsuarioAutorizacion.id_usuariotemporal', $id_usuario)
        ->where('UsuarioAutorizacion.fecha_inicio','<=', 
            date("Y-m-d", strtotime('-6 hour', strtotime(now()))))
        ->where('UsuarioAutorizacion.fecha_final', '>=',
            date("Y-m-d", strtotime('-6 hour', strtotime(now()))))->get();

        $IdUsuario = 0;

        if($temporal->count() > 0){
            $usuario = $temporal->toArray();
            foreach($usuario as $item){
                $IdUsuario = $item['id_usuarioaprobador'];
            }
        }else{
            $IdUsuario = $id_usuario;
        }
        
        $usuarioperfil = UsuarioPerfil::join('PerfilRol', function($join){
            $join->on('UsuarioPerfil.id_perfil', '=', 'PerfilRol.id_perfil');
        })->join('perfiles', function($join){
            $join->on('UsuarioPerfil.id_perfil', '=', 'perfiles.id_perfil');
        })->join('roles', function($join){
            $join->on('PerfilRol.id_rol', '=', 'roles.id_rol');
        })->join('RolPermiso', function($join){
            $join->on('PerfilRol.id_rol', '=', 'RolPermiso.id_rol');
        })
        ->select('roles.objeto', 'RolPermiso.id_permiso')
        ->where('UsuarioPerfil.activo', 1)->where('UsuarioPerfil.eliminado', 0)
        ->where('perfiles.activo', 1)->where('perfiles.eliminado', 0)
        ->where('PerfilRol.activo', 1)->where('PerfilRol.eliminado', 0)
        ->where('roles.activo', 1)->where('roles.eliminado', 0)
        ->where('RolPermiso.activo', 1)->where('RolPermiso.eliminado', 0)
        ->where('roles.objeto', "Modulo Compensacion Pagos")
        ->where('UsuarioPerfil.id_usuario', $IdUsuario)
        ->orderBy('RolPermiso.id_permiso', 'ASC')
        ->get();
        $estado = 0;
        $flujos = array();
        $permisos = array();
        if($usuarioperfil->count()>0){
            $permisos = $usuarioperfil->toArray();
        }
        foreach($permisos as $item){
            if($item['objeto'] == "Modulo Compensacion Pagos"){
                $estado = 5;
            }
        }

        $EmpresasRestringidasLista = RestriccionEmpresa::select(['empresa_codigo'])->where('eliminado',0)
        ->where('activo',1)->get()->toArray();

        $EmpresasDeUsuario = UsuarioRestriccionEmpresa::select(['empresa_codigo'])
        ->where('id_usuario',$IdUsuario)
        ->where('eliminado',0)
        ->where('activo',1)
        ->get()->toArray();

        if(!empty($EmpresasDeUsuario)){
            $EmpresasRestringidas = Flujos::select(['empresa_codigo'])
            ->whereIn('empresa_codigo', $EmpresasDeUsuario)
            ->groupBy('empresa_codigo')
            ->groupBy('empresa_nombre')
            ->get()->toArray();
        }else{
            $EmpresasRestringidas = Flujos::select(['empresa_codigo'])
            ->whereNotIn('empresa_codigo', $EmpresasRestringidasLista)
            ->groupBy('empresa_codigo')
            ->groupBy('empresa_nombre')
            ->get()->toArray();
        }

        $listaWhereTextosTmp = UsuarioRestriccionTexto::select(['texto'])
        ->where('id_usuario',$IdUsuario)
        ->where('eliminado',0)
        ->where('activo',1)
        ->get()->toArray();

        $listaWhereTextos = array();

        if(count($listaWhereTextosTmp) > 0){
            foreach($listaWhereTextosTmp as $item){
                $listaWhereTextos[] = $item['texto'];
            }
        }

        $flujos = Flujos::selectRaw(
            "Flujo.id_flujo,
            Flujo.id_tipoflujo,
            Flujo.doc_num,
            Flujo.tipo,
            DATE_FORMAT(Flujo.tax_date,'%Y-%m-%d')as tax_date,
            DATE_FORMAT(Flujo.doc_date,'%Y-%m-%d')as doc_date,
            Flujo.comments,
            Flujo.activo,
            Flujo.estado,
            Flujo.nivel,
            Flujo.id_grupoautorizacion,
            Flujo.card_name,
            Flujo.en_favor_de,
            Flujo.doc_total,
            Flujo.doc_curr,
            Flujo.empresa_nombre,
            (select DATE_FORMAT(MAX(fd.Fecha),'%Y-%m-%d') from FlujoDetalle as fd where fd.IdEstadoFlujo = 5
            and fd.IdFlujo = Flujo.id_flujo) as aut_date,
            (select DATE_FORMAT(MAX(fd.Fecha),'%Y-%m-%d') from FlujoDetalle as fd where fd.IdEstadoFlujo = 1
            and fd.IdFlujo = Flujo.id_flujo) as creation_date,
            CASE
               WHEN (Flujo.tipo = 'BANCARIO' AND Flujo.cheque is not null AND Flujo.cheque > 0) THEN 1
               WHEN (Flujo.tipo = 'BANCARIO' AND ( Flujo.cheque is null OR Flujo.cheque = 0 )) THEN 0
               WHEN Flujo.tipo = 'TRANSFERENCIA' THEN 1
               WHEN Flujo.tipo = 'INTERNA' THEN 1
            END as TieneCheque"
        )
        ->where('Flujo.tipo', $tipo)
        ->where('Flujo.estado', $estado)
        ->where('Flujo.activo', '=',1)
        ->where('Flujo.eliminado', '=',0)
        ->whereIn('Flujo.empresa_codigo', $EmpresasRestringidas)
        ->where(function ($q) use ($listaWhereTextos) {
            foreach ($listaWhereTextos as $value) {
                 $q->orWhere('Flujo.comments', 'like', "%{$value}%");
            }
        })
        ->orderBy('Flujo.id_flujo', 'ASC')  
        ->get();
        $datos = array();
        $datos['flujos'] = $flujos;
        return $datos;
    }
    
    public function rechazadobanco($tipo, $id_usuario)
    {

        $temporal = UsuarioAutorizacion::select('UsuarioAutorizacion.id_usuarioaprobador')
        ->where('UsuarioAutorizacion.id_usuariotemporal', $id_usuario)
        ->where('UsuarioAutorizacion.fecha_inicio','<=', 
            date("Y-m-d", strtotime('-6 hour', strtotime(now()))))
        ->where('UsuarioAutorizacion.fecha_final', '>=',
            date("Y-m-d", strtotime('-6 hour', strtotime(now()))))->get();

        $IdUsuario = 0;

        if($temporal->count() > 0){
            $usuario = $temporal->toArray();
            foreach($usuario as $item){
                $IdUsuario = $item['id_usuarioaprobador'];
            }
        }else{
            $IdUsuario = $id_usuario;
        }
        
        $usuarioperfil = UsuarioPerfil::join('PerfilRol', function($join){
            $join->on('UsuarioPerfil.id_perfil', '=', 'PerfilRol.id_perfil');
        })->join('perfiles', function($join){
            $join->on('UsuarioPerfil.id_perfil', '=', 'perfiles.id_perfil');
        })->join('roles', function($join){
            $join->on('PerfilRol.id_rol', '=', 'roles.id_rol');
        })->join('RolPermiso', function($join){
            $join->on('PerfilRol.id_rol', '=', 'RolPermiso.id_rol');
        })->join('permisos', function($join){
            $join->on('RolPermiso.id_permiso', '=', 'permisos.id_permiso');
        })
        ->select('roles.objeto', 'RolPermiso.id_permiso', 'permisos.descripcion')
        ->where('UsuarioPerfil.activo', 1)->where('UsuarioPerfil.eliminado', 0)
        ->where('perfiles.activo', 1)->where('perfiles.eliminado', 0)
        ->where('PerfilRol.activo', 1)->where('PerfilRol.eliminado', 0)
        ->where('roles.activo', 1)->where('roles.eliminado', 0)
        ->where('RolPermiso.activo', 1)->where('RolPermiso.eliminado', 0)
        ->where('permisos.activo', 1)->where('permisos.eliminado', 0)
        ->whereIn('roles.objeto', ["Modulo Autorizacion Pagos","Modulo Compensacion Pagos"])
        ->where('UsuarioPerfil.id_usuario', $IdUsuario)
        ->orderBy('RolPermiso.id_permiso', 'ASC')
        ->get();

        $flujos = array();
        $estados = array();
        $grupos = array();
        $permisos = array();
        $i = 0;
        if($usuarioperfil->count()>0){
            $permisos = $usuarioperfil->toArray();
        }
        foreach($permisos as $item){
            if($item['objeto'] == "Modulo Compensacion Pagos"){
                $estados[] = 9;
                $estados[] = 13;
            }
            if($item['descripcion'] == "Actualizar"){
                $estados[] = 9;
            }
        }

        $EmpresasRestringidasLista = RestriccionEmpresa::select(['empresa_codigo'])->where('eliminado',0)
        ->where('activo',1)->get()->toArray();

        $EmpresasDeUsuario = UsuarioRestriccionEmpresa::select(['empresa_codigo'])
        ->where('id_usuario',$IdUsuario)
        ->where('eliminado',0)
        ->where('activo',1)
        ->get()->toArray();

        if(!empty($EmpresasDeUsuario)){
            $EmpresasRestringidas = Flujos::select(['empresa_codigo'])
            ->whereIn('empresa_codigo', $EmpresasDeUsuario)
            ->groupBy('empresa_codigo')
            ->groupBy('empresa_nombre')
            ->get()->toArray();
        }else{
            $EmpresasRestringidas = Flujos::select(['empresa_codigo'])
            ->whereNotIn('empresa_codigo', $EmpresasRestringidasLista)
            ->groupBy('empresa_codigo')
            ->groupBy('empresa_nombre')
            ->get()->toArray();
        }

        $listaWhereTextosTmp = UsuarioRestriccionTexto::select(['texto'])
        ->where('id_usuario',$IdUsuario)
        ->where('eliminado',0)
        ->where('activo',1)
        ->get()->toArray();

        $listaWhereTextos = array();

        if(count($listaWhereTextosTmp) > 0){
            foreach($listaWhereTextosTmp as $item){
                $listaWhereTextos[] = $item['texto'];
            }
        }

        $ListaFlujosEstado = Flujos::selectRaw(
            "Flujo.id_flujo,
            Flujo.id_tipoflujo,
            Flujo.doc_num,
            Flujo.tipo,
            DATE_FORMAT(Flujo.tax_date,'%Y-%m-%d')as tax_date,
            DATE_FORMAT(Flujo.doc_date,'%Y-%m-%d')as doc_date,
            Flujo.comments,
            Flujo.activo,
            Flujo.estado,
            Flujo.nivel,
            Flujo.id_grupoautorizacion,
            Flujo.card_name,
            Flujo.en_favor_de,
            Flujo.doc_total,
            Flujo.doc_curr,
            Flujo.empresa_nombre,
            (select DATE_FORMAT(MAX(fd.Fecha),'%Y-%m-%d') from FlujoDetalle as fd where fd.IdEstadoFlujo = 5
            and fd.IdFlujo = Flujo.id_flujo) as aut_date,
            (select DATE_FORMAT(MAX(fd.Fecha),'%Y-%m-%d') from FlujoDetalle as fd where fd.IdEstadoFlujo = 1
            and fd.IdFlujo = Flujo.id_flujo) as creation_date,
            CASE
               WHEN Flujo.tipo = 'BANCARIO' THEN (select count(FC.id_flujo) from FlujoNumeroCheque as FC where FC.id_flujo = Flujo.id_flujo)
               WHEN Flujo.tipo = 'TRANSFERENCIA' THEN 1
               WHEN Flujo.tipo = 'INTERNA' THEN 1
            END as TieneCheque"
        )
        ->where('Flujo.tipo', $tipo)
        ->whereIn('Flujo.estado', $estados)
        ->where('Flujo.activo', '=',1)
        ->where('Flujo.eliminado', '=',0)
        ->whereIn('Flujo.empresa_codigo', $EmpresasRestringidas)
        ->where(function ($q) use ($listaWhereTextos) {
            foreach ($listaWhereTextos as $value) {
                 $q->orWhere('Flujo.comments', 'like', "%{$value}%");
            }
        })
        ->orderBy('Flujo.id_flujo', 'ASC')  
        ->get();

        $j = 0;     
        foreach($ListaFlujosEstado as $item){
            $flujos[$j] = $item;
            $j += 1;
        }
        $datos = array();
        $datos['flujos'] = $flujos;
        return $datos;        
    }
    
    public function solicitudretorno($tipo, $id_usuario)
    {

        $temporal = UsuarioAutorizacion::select('UsuarioAutorizacion.id_usuarioaprobador')
        ->where('UsuarioAutorizacion.id_usuariotemporal', $id_usuario)
        ->where('UsuarioAutorizacion.fecha_inicio','<=', 
            date("Y-m-d", strtotime('-6 hour', strtotime(now()))))
        ->where('UsuarioAutorizacion.fecha_final', '>=',
            date("Y-m-d", strtotime('-6 hour', strtotime(now()))))->get();

        $IdUsuario = 0;

        if($temporal->count() > 0){
            $usuario = $temporal->toArray();
            foreach($usuario as $item){
                $IdUsuario = $item['id_usuarioaprobador'];
            }
        }else{
            $IdUsuario = $id_usuario;
        }
        
        $usuarioperfil = UsuarioPerfil::join('PerfilRol', function($join){
            $join->on('UsuarioPerfil.id_perfil', '=', 'PerfilRol.id_perfil');
        })->join('perfiles', function($join){
            $join->on('UsuarioPerfil.id_perfil', '=', 'perfiles.id_perfil');
        })->join('roles', function($join){
            $join->on('PerfilRol.id_rol', '=', 'roles.id_rol');
        })->join('RolPermiso', function($join){
            $join->on('PerfilRol.id_rol', '=', 'RolPermiso.id_rol');
        })->join('permisos', function($join){
            $join->on('RolPermiso.id_permiso', '=', 'permisos.id_permiso');
        })
        ->select('roles.objeto', 'RolPermiso.id_permiso', 'permisos.descripcion')
        ->where('UsuarioPerfil.activo', 1)->where('UsuarioPerfil.eliminado', 0)
        ->where('perfiles.activo', 1)->where('perfiles.eliminado', 0)
        ->where('PerfilRol.activo', 1)->where('PerfilRol.eliminado', 0)
        ->where('roles.activo', 1)->where('roles.eliminado', 0)
        ->where('RolPermiso.activo', 1)->where('RolPermiso.eliminado', 0)
        ->where('permisos.activo', 1)->where('permisos.eliminado', 0)
        ->whereIn('roles.objeto', ["Modulo Autorizacion Pagos","Modulo Compensacion Pagos"])
        ->where('UsuarioPerfil.id_usuario', $IdUsuario)
        ->orderBy('RolPermiso.id_permiso', 'ASC')
        ->get();

        $flujos = array();
        $estados = array();
        $grupos = array();
        $permisos = array();
        $i = 0;
        if($usuarioperfil->count()>0){
            $permisos = $usuarioperfil->toArray();
        }
        foreach($permisos as $item){
            if($item['descripcion'] == "Reprocesar"){
                $estados[] = 12;
            }
        }

        $EmpresasRestringidasLista = RestriccionEmpresa::select(['empresa_codigo'])->where('eliminado',0)
        ->where('activo',1)->get()->toArray();

        $EmpresasDeUsuario = UsuarioRestriccionEmpresa::select(['empresa_codigo'])
        ->where('id_usuario',$IdUsuario)
        ->where('eliminado',0)
        ->where('activo',1)
        ->get()->toArray();

        if(!empty($EmpresasDeUsuario)){
            $EmpresasRestringidas = Flujos::select(['empresa_codigo'])
            ->whereIn('empresa_codigo', $EmpresasDeUsuario)
            ->groupBy('empresa_codigo')
            ->groupBy('empresa_nombre')
            ->get()->toArray();
        }else{
            $EmpresasRestringidas = Flujos::select(['empresa_codigo'])
            ->whereNotIn('empresa_codigo', $EmpresasRestringidasLista)
            ->groupBy('empresa_codigo')
            ->groupBy('empresa_nombre')
            ->get()->toArray();
        }

        $listaWhereTextosTmp = UsuarioRestriccionTexto::select(['texto'])
        ->where('id_usuario',$IdUsuario)
        ->where('eliminado',0)
        ->where('activo',1)
        ->get()->toArray();

        $listaWhereTextos = array();

        if(count($listaWhereTextosTmp) > 0){
            foreach($listaWhereTextosTmp as $item){
                $listaWhereTextos[] = $item['texto'];
            }
        }

        $ListaFlujosEstado = Flujos::selectRaw(
            "Flujo.id_flujo,
            Flujo.id_tipoflujo,
            Flujo.doc_num,
            Flujo.tipo,
            DATE_FORMAT(Flujo.tax_date,'%Y-%m-%d')as tax_date,
            DATE_FORMAT(Flujo.doc_date,'%Y-%m-%d')as doc_date,
            Flujo.comments,
            Flujo.activo,
            Flujo.estado,
            Flujo.nivel,
            Flujo.id_grupoautorizacion,
            Flujo.card_name,
            Flujo.en_favor_de,
            Flujo.doc_total,
            Flujo.doc_curr,
            Flujo.empresa_nombre,
            (select DATE_FORMAT(MAX(fd.Fecha),'%Y-%m-%d') from FlujoDetalle as fd where fd.IdEstadoFlujo = 5
            and fd.IdFlujo = Flujo.id_flujo) as aut_date,
            (select DATE_FORMAT(MAX(fd.Fecha),'%Y-%m-%d') from FlujoDetalle as fd where fd.IdEstadoFlujo = 1
            and fd.IdFlujo = Flujo.id_flujo) as creation_date,
            CASE
               WHEN Flujo.tipo = 'BANCARIO' THEN (select count(FC.id_flujo) from FlujoNumeroCheque as FC where FC.id_flujo = Flujo.id_flujo)
               WHEN Flujo.tipo = 'TRANSFERENCIA' THEN 1
               WHEN Flujo.tipo = 'INTERNA' THEN 1
            END as TieneCheque"
        )
        ->where('Flujo.tipo', $tipo)
        ->whereIn('Flujo.estado', $estados)
        ->where('Flujo.activo', '=',1)
        ->where('Flujo.eliminado', '=',0)
        ->whereIn('Flujo.empresa_codigo', $EmpresasRestringidas)
        ->where(function ($q) use ($listaWhereTextos) {
            foreach ($listaWhereTextos as $value) {
                 $q->orWhere('Flujo.comments', 'like', "%{$value}%");
            }
        })
        ->orderBy('Flujo.id_flujo', 'ASC')  
        ->get();

        $j = 0;     
        foreach($ListaFlujosEstado as $item){
            $flujos[$j] = $item;
            $j += 1;
        }
        $datos = array();
        $datos['flujos'] = $flujos;
        return $datos;        
    }

    public function pendientesreporte()
    {
        $pagos = Flujos::leftJoin('EstadoFlujo as EstadoFlujo', function($join){
            $join->on('EstadoFlujo.id_estadoflujo', '=', 'Flujo.estado');
        })
        ->selectRaw(
            "Flujo.doc_num,
            DATE_FORMAT(Flujo.doc_date,'%Y-%m-%d')as doc_date,
            Flujo.comments,
            Flujo.tipo,
            EstadoFlujo.descripcion as estado,
            Flujo.dias_credito,
            Flujo.dias_credito - TIMESTAMPDIFF(DAY, Flujo.doc_date, DATE_ADD(NOW(), INTERVAL 1 HOUR)) as dias_vencimiento,
            Flujo.nivel,
            ((TIMESTAMPDIFF(DAY, Flujo.doc_date, DATE_ADD(NOW(), INTERVAL 1 HOUR))*100)/Flujo.dias_credito) as porcentaje"
        )
        ->where('Flujo.estado', '<', 5)
        ->orderBy('Flujo.id_flujo', 'ASC')  
        ->get();

        foreach($pagos as $item){
            if($item['dias_vencimiento'] < 0){
                $item['dias_vencimiento'] = 0;
            }
            if($item['porcentaje'] == null){
                $item['porcentaje'] = 0;
            }else{
                $item['porcentaje'] = (float)$item['porcentaje'];
            }
            if($item['nivel'] > 0){
                $item['estado'] = 'Autorizado nivel '.$item['nivel'];
            }else{
                $item['estado'] = $item['estado'];
            }
        }
        
        $datos = array();
        $datos['flujos'] = $pagos;
        return $datos;        
    }

    public function store(Request $request)
    {
        $opcion = $request->opcion;
        $estado = 0;

        if($opcion == '4'){//Pausar
            Flujos::where('id_flujo', $request->id_flujo)
            ->update([
                'estado' => 10
            ]);
            return response()->json("OK");

        }else if($opcion == '44'){//No visado
            Flujos::where('id_flujo', $request->id_flujo)
            ->update([
                'estado' => 14
            ]);
            return response()->json("OK");

        }else if($opcion == '45'){//Remplazo de pago
            Flujos::where('id_flujo', $request->id_flujo)
            ->update([
                'estado' => 16
            ]);
            return response()->json("OK");

        }else if ($opcion == '5'){//Estado actualizar
            Flujos::where('id_flujo', $request->id_flujo)
            ->update([
                'estado' => 11
            ]);
            return response()->json("OK");
        }else if ($opcion == '6'){//Actualizar y reiniciar
            FlujoDetalle::where('IdFlujo', $request->id_flujo)
            ->where('IdEstadoFlujo', '>',2)
            ->update([
                'FlujoActivo' => 0
            ]);
            Flujos::where('id_flujo', $request->id_flujo)
            ->update([
                'estado' => 3,
                'nivel' => 0
            ]);
            self::ActualizarDatosOrigen($request->id_flujo);
            return response()->json("OK");
        }else if ($opcion == '66'){//Actualizar desde compensación
            self::ActualizarDatosOrigenCompensar($request->id_flujo);
            //self::ActualizarDatosOrigenValidacion($request->id_flujo, $request->idUsuario);
            return response()->json("OK");
        }else if ($opcion == '67'){//Restituir para reprocesamiento
            Flujos::where('id_flujo', $request->id_flujo)
            ->update([
                'estado' => 5,
                'nivel' => 0,
                'ConDuda' => 0
            ]);
            FlujoDetalle::where('IdFlujo', $request->id_flujo)
                ->where('IdEstadoFlujo', '>',7)
                ->where('FlujoActivo', '=',1)
                ->update([
                    'FlujoActivo' => 0
                ]);
            return response()->json("OK");
        }else if ($opcion == '68'){//Solicitud de retorno a bandeja de pendientes
            Flujos::where('id_flujo', $request->id_flujo)
            ->update([
                'estado' => 12,
                'nivel' => 0
            ]);
            return response()->json("OK");
        }else if ($opcion == '69'){//Rechazo de solicitud de retorno a bandeja de pendientes
            Flujos::where('id_flujo', $request->id_flujo)
            ->update([
                'estado' => 13,
                'nivel' => 0
            ]);
            return response()->json("OK");
        }else if ($opcion == '7'){ //Actualizar y continuar
            self::ActualizarDatosOrigen($request->id_flujo);
            //self::ActualizarDatosOrigenValidacion($request->id_flujo, $request->idUsuario);
            return response()->json("OK");
        }else if ($opcion == '8'){ //Marcar Con Duda
            Flujos::where('id_flujo', $request->id_flujo)
            ->update([
                'ConDuda' => 1
            ]);
            
            $flujoDetalleCD = new FlujoDetalle;
            $flujoDetalleCD->IdFlujo = $request->id_flujo;
            $flujoDetalleCD->IdEstadoFlujo = 18;
            $flujoDetalleCD->IdUsuario = $request->id_usuario;
            $flujoDetalleCD->Fecha = date("Y-m-d H:i",strtotime('-6 hour',strtotime(now())));
            $flujoDetalleCD->Comentario = "Marcado con duda por usuario";
            $flujoDetalleCD->NivelAutorizo = 0;
            $flujoDetalleCD->FlujoActivo = 0;
            $flujoDetalleCD->save();
            return response()->json("OK");
        }else{
            //obtenemos el estado actual del pago
            $estados = Flujos::select('Flujo.estado')
            ->where('Flujo.id_flujo', $request->id_flujo)
            ->get()->toArray();

            //obtenemos el nivel del pago
            $nivel_pago = Flujos::select('Flujo.nivel')
            ->where('Flujo.id_flujo', $request->id_flujo)
            ->first();

            //Desactivamos los recordatorios del pago
            RecordatorioUsuario::where('id_flujo', $request->id_flujo)
            ->where('activo',1)
            ->update([
                'activo' => 0
            ]);
    
            //Sacamos el estado del flujo
            foreach($estados as $item){
                $estado = $item['estado'];
            }

            //Se completó la carga de archivos
            if($estado == 1){
                Flujos::where('id_flujo', $request->id_flujo)
                ->update([
                    'estado' => 2,
                    'nivel' => 0
                ]);
                return response()->json("OK");
            }

            //Listado de usuarios que no reciben correo
            $UsuariosSinCorreos = UsuarioSinNotificacionCorreo::select(['id_usuario'])->where('eliminado',0)->where('activo',1)->get()->toArray();

            //Se asignó a un responsable
            //Agregar notificación para usuarios de nivel 1
            if($estado == 2){
                Flujos::where('id_flujo', $request->id_flujo)
                ->update([
                    'id_grupoautorizacion' => $request->id_grupoautorizacion,
                    'estado' => 3,
                    'nivel' => 0
                ]);
                SugerenciaAsignacionGrupo::where('id_flujo', $request->id_flujo)
                ->update([
                    'activo' => 0
                ]);
                //Desactivamos notificaciones
                Notificacion::where('IdFlujo', $request->id_flujo)
                ->update([
                    'Leido' => 1
                ]);
                //Enviar notificación de autorización
                $usuariosNivel = UsuarioGrupo::join('Flujo', function($join){
                    $join->on('UsuarioGrupoAutorizacion.id_grupoautorizacion', '=', 
                    'Flujo.id_grupoautorizacion');
                })->join('usuarios', function($join){
                    $join->on('UsuarioGrupoAutorizacion.id_usuario','=','usuarios.id_usuario');
                })
                ->select('usuarios.id_usuario', 'usuarios.correo', 'Flujo.doc_num')
                ->where('Flujo.id_flujo', $request->id_flujo)
                ->where('UsuarioGrupoAutorizacion.nivel', 1)
                ->where('UsuarioGrupoAutorizacion.activo', 1)
                ->where('UsuarioGrupoAutorizacion.eliminado', 0)
                ->where('usuarios.activo', 1)
                ->where('usuarios.eliminado', 0)
                ->whereNotIn('UsuarioGrupoAutorizacion.id_usuario', $UsuariosSinCorreos)->get();

                foreach($usuariosNivel as $usuario){
                    
                    $details=['id_flujo' => $request->id_flujo];
                    $details+=['doc_num' => $usuario->doc_num];
                    $details+=['correo' => $usuario->correo];
                    //Enviamos corre
                    if(filter_var($usuario->correo, FILTER_VALIDATE_EMAIL)){
                        try{
                            Mail::to($usuario->correo)->send(new EnviarNotificacion($details));
                        }catch(Exception $e){
                            Log::error($e->getMessage());
                        }
                    }

                    //Creamos notificación
                    $notificaciones = new Notificacion;
                    $notificaciones->IdFlujo = $request->id_flujo;
                    $notificaciones->IdUsuario = $usuario->id_usuario;
                    $notificaciones->Mensaje = 'Nuevo pago para autorizar No. '.$usuario->doc_num; 
                    $notificaciones->Leido = 0;
                    $notificaciones->save();
                }

                return response()->json("OK");
            }

            //Se aprobó en nivel 1
            //Agregar notificación para usuarios de nivel siguiente
            if($estado == 3 && $request->nivel == 1){
                Flujos::where('id_flujo', $request->id_flujo)
                ->update([
                    'estado' => 4,
                    'nivel' => 2,
                    'ConDuda' => 0
                ]);
                //Desactivamos notificaciones
                Notificacion::where('IdFlujo', $request->id_flujo)
                ->update([
                    'Leido' => 1
                ]);
                //Enviar notificación de autorización
                $usuariosNivel = UsuarioGrupo::join('Flujo', function($join){
                    $join->on('UsuarioGrupoAutorizacion.id_grupoautorizacion', '=', 
                    'Flujo.id_grupoautorizacion');
                })->join('usuarios', function($join){
                    $join->on('UsuarioGrupoAutorizacion.id_usuario','=','usuarios.id_usuario');
                })
                ->select('usuarios.id_usuario', 'usuarios.correo', 'Flujo.doc_num')
                ->where('Flujo.id_flujo', $request->id_flujo)
                ->where('UsuarioGrupoAutorizacion.nivel', 2)
                ->where('UsuarioGrupoAutorizacion.activo', 1)
                ->where('UsuarioGrupoAutorizacion.eliminado', 0)
                ->where('usuarios.activo', 1)
                ->where('usuarios.eliminado', 0)
                ->whereNotIn('UsuarioGrupoAutorizacion.id_usuario', $UsuariosSinCorreos)->get();

                foreach($usuariosNivel as $usuario){
                    
                    $details=['id_flujo' => $request->id_flujo];
                    $details+=['doc_num' => $usuario->doc_num];
                    $details+=['correo' => $usuario->correo];
                    //Enviamos corre
                    if(filter_var($usuario->correo, FILTER_VALIDATE_EMAIL)){
                        try{
                            Mail::to($usuario->correo)->send(new EnviarNotificacion($details));
                        }catch(Exception $e){
                            Log::error($e->getMessage());
                        }
                    }

                    //Creamos notificación
                    $notificaciones = new Notificacion;
                    $notificaciones->IdFlujo = $request->id_flujo;
                    $notificaciones->IdUsuario = $usuario->id_usuario;
                    $notificaciones->Mensaje = 'Nuevo pago para autorizar No. '.$usuario->doc_num; 
                    $notificaciones->Leido = 0;
                    $notificaciones->save();
                }
                return response()->json("OK");
            }

            //Se aprobó en nivel > 1, se valida si es último nivel se cambia de estado
            //Agregar notificación para usuarios de nivel siguiente en validación else de último nivel
            if($estado == 4 && $request->nivel == $nivel_pago->nivel){
                $respuesta = "";
                $ultimonivel = UsuarioGrupo::join('Flujo', function($join){
                    $join->on('UsuarioGrupoAutorizacion.id_grupoautorizacion', '=', 
                    'Flujo.id_grupoautorizacion');
                })->join('GrupoAutorizacion', function($join){
                    $join->on('Flujo.id_grupoautorizacion', '=', 
                    'GrupoAutorizacion.id_grupoautorizacion');
                })
                ->select('Flujo.id_flujo', 'Flujo.nivel')
                ->where('Flujo.id_flujo', $request->id_flujo)
                ->where('GrupoAutorizacion.numero_niveles', $request->nivel)
                ->groupBy('Flujo.id_flujo')->get();
                //Desactivamos notificaciones
                Notificacion::where('IdFlujo', $request->id_flujo)
                ->update([
                    'Leido' => 1
                ]);
    
                if($ultimonivel->count() > 0){
                    Flujos::where('id_flujo', $request->id_flujo)
                    ->update(['estado' => 5, 'nivel' => $request->nivel,'ConDuda' => 0]);
                    $respuesta = "Finalizado";
                } else {
                    Flujos::where('id_flujo', $request->id_flujo)
                    ->update(['nivel' => $request->nivel + 1,'ConDuda' => 0]);
                    $respuesta = "OK";
                    //Enviar notificación de autorización
                    $usuariosNivel = UsuarioGrupo::join('Flujo', function($join){
                        $join->on('UsuarioGrupoAutorizacion.id_grupoautorizacion', '=', 
                        'Flujo.id_grupoautorizacion');
                    })->join('usuarios', function($join){
                        $join->on('UsuarioGrupoAutorizacion.id_usuario','=','usuarios.id_usuario');
                    })
                    ->select('usuarios.id_usuario', 'usuarios.correo', 'Flujo.doc_num')
                    ->where('Flujo.id_flujo', $request->id_flujo)
                    ->where('UsuarioGrupoAutorizacion.nivel', $request->nivel + 1)
                    ->where('UsuarioGrupoAutorizacion.activo', 1)
                    ->where('UsuarioGrupoAutorizacion.eliminado', 0)
                    ->where('usuarios.activo', 1)
                    ->where('usuarios.eliminado', 0)
                    ->whereNotIn('UsuarioGrupoAutorizacion.id_usuario', $UsuariosSinCorreos)->get();
    
                    foreach($usuariosNivel as $usuario){
                        
                        $details=['id_flujo' => $request->id_flujo];
                        $details+=['doc_num' => $usuario->doc_num];
                        $details+=['correo' => $usuario->correo];
                        //Enviamos corre
                        if(filter_var($usuario->correo, FILTER_VALIDATE_EMAIL)){
                            try{
                                Mail::to($usuario->correo)->send(new EnviarNotificacion($details));
                            }catch(Exception $e){
                                Log::error($e->getMessage());
                            }
                        }
    
                        //Creamos notificación
                        $notificaciones = new Notificacion;
                        $notificaciones->IdFlujo = $request->id_flujo;
                        $notificaciones->IdUsuario = $usuario->id_usuario;
                        $notificaciones->Mensaje = 'Nuevo pago para autorizar No. '.$usuario->doc_num; 
                        $notificaciones->Leido = 0;
                        $notificaciones->save();
                    }
                }
                return response()->json($respuesta);
            }
        }
    }

    public function updateOriginal(Request $request, $id)
    {
        if($request->opcion == '1'){
            $flujos = Flujos::find($id);
            $flujos->estado = 6;
            $flujos->nivel = 0;
            $flujos->ConDuda = 0;
            $flujos->save();
            return response()->json("OK"); 
        }else if($request->opcion == '2'){
            $arrayPagos = array();
            $respuesta = "";
            $i = 0;
            foreach($request->pagos as $pago){
                $datosFlujo = Flujos::where('id_flujo',$pago)->first();
                if($datosFlujo->tipo == "BANCARIO" || $datosFlujo->tipo == "INTERNA"){
                    $arrayPagos[$i] = $pago;
                    $i++;
                }else{
                    $flujos = Flujos::join('Banco', function($join){
                        $join->on('Flujo.bank_code', 'Banco.codigo_SAP');
                    })
                    ->where('Flujo.id_flujo', $pago)
                    ->where('Banco.eliminado', 0)
                    ->where('Banco.activo', 1)->get();
                    if($flujos->count() > 0){
                        $arrayPagos[$i] = $pago;
                        $i++;
                    }else{
    
                        $pagos = Flujos::select('doc_num', 'bank_code')->where('id_flujo', $pago)->get();
                        foreach($pagos as $item){
                            if($item['bank_code'] == null){
                                $arrayPagos[$i] = $pago;
                                $i++;
                            }else{
                                $respuesta.=$item['doc_num'].", "; 
                            }
                        }
                    }
                }
            }
            $flujos = Flujos::selectRaw(
				"Flujo.id_flujo as IdFlujo,
                 Flujo.id_tipoflujo as IdTipoFlujo,
				 Flujo.doc_num as DocNum,
				 Flujo.tipo as Tipo,
				 DATE_FORMAT(Flujo.tax_date,'%Y-%m-%d') as TaxDate,
				 DATE_FORMAT(Flujo.doc_date,'%Y-%m-%d') as DocDate,
				 Flujo.card_code as CardCode,
				 Flujo.card_name as CardName,
				 Flujo.comments as Comments,
				 Flujo.doc_total as DocTotal,
				 Flujo.doc_curr as DocCurr,
				 Flujo.bank_code as BankCode,
				 Flujo.dfl_account as DflAccount,
				 Flujo.tipo_cuenta_destino as TipoCuentaDestino,
				 Flujo.cuenta_orgien as CuentaOrigen,
				 Flujo.empresa_codigo as EmpresaCodigo,
				 Flujo.empresa_nombre as EmpresaNombre,
				 Flujo.cheque as Cheque,
				 Flujo.en_favor_de as EnFavorDe,
				 Flujo.email as Email,
                 Flujo.dias_credito as DiasCredito,
                 Flujo.nombre_condicion_pago_dias as NombreCondicionPagoDias,
                 Flujo.NombreXML,
                 (select DATE_FORMAT(MAX(fd.Fecha),'%Y-%m-%d') from FlujoDetalle as fd where fd.IdEstadoFlujo = 5
                and fd.IdFlujo = Flujo.id_flujo) as aut_date"
			)
            ->whereIn('Flujo.id_flujo', $arrayPagos)
            ->where('Flujo.estado', '=',5)
            ->where('Flujo.activo', '=',1)
            ->where('Flujo.eliminado', '=',0)
            ->orderBy('Flujo.id_flujo', 'ASC')->get()->toArray(); 

            if(count($flujos)>0){
                if($flujos[0]['Tipo'] == "BANCARIO" || $flujos[0]['Tipo'] == "INTERNA"){
                    foreach($flujos as $pago){
                        Flujos::where('id_flujo', $pago['IdFlujo'])
                        ->update(['estado' => 7, 'nivel' => 0,'ConDuda' => 0]);
                    }
                }else{
                    foreach($flujos as $pago){
                        $validamosPago = Flujos::where('id_flujo', $pago['IdFlujo'])
                        ->where('estado', '=',5)
                        ->first();

                        if($validamosPago){
                            
                            Flujos::where('id_flujo', $pago['IdFlujo'])
                            ->update(['estado' => 19]);

                            $pagos = array();
                            $j = 0;
                            $NumPagos = 1;
                            //Cambio previo a Producción
                            //$TotalPagos = 1;
                            $TotalPagos = $pago['DocTotal'];
                            $FechaDoc = date("Y-m-d H:i",strtotime('-6 hour',strtotime(now())));
                            $Codigo = FlujosController::generarCodigo(13);
                            $CodigoFecha = date("YmdHis",strtotime('-6 hour',strtotime(now())));
                            $FileName = 'PAIN.001.001.03_GRUPOSIONXX_AMCNGTGTXXX_DOC'.$CodigoFecha.'.xml';
                
                            $encabezado = array(
                                "GrpHdr" => array(
                                    "MsgId" => $Codigo,
                                    "CreDtTm" => $FechaDoc,
                                    "NbOfTxs" => $NumPagos,
                                    "CtrlSum" => $TotalPagos,
                                    "InitgPty" => array(
                                        "Nm" => "Grupo Sion",
                                        "Id" => array(
                                            "OrgId" => array(
                                                "BICOrBEI" => "BICCLIENTE",
                                            )
                                        )
                                    )
                                )
                            );
                            //Inicio de detalle
                            $pagos[$j] = array(
                                "PmtInfId" => $Codigo,
                                "PmtMtd" => FlujosController::obtenerTipo($pago['Tipo'], 1),
                                "BtchBookg" => "",
                                "NbOfTxs" => $NumPagos,
                                "CtrlSum" => $TotalPagos,
                                "PmtTpInf" => array(
                                    "InstrPrty" => "",
                                    "CtgyPurp" => array(
                                        "Cd" => "",
                                    ), 
                                    "SvcLvl" => array(
                                        "Cd" => "",
                                    ), 
                                ),
                                "ReqdExctnDt" => $pago['DocDate'],
                                "Dbtr" => array(
                                    "Nm" => FlujosController::obtenerDatos($pago['IdFlujo'], 6),
                                    "PstlAdr" => array(
                                        "StrtNm" => FlujosController::obtenerDatos($pago['IdFlujo'], 7),
                                        "TwnNm" => "",
                                        "Ctry" => "",
                                    ),
                                    "CtryOfRes" => "", 
                                    "Id" => array(
                                        "OrgId" => array( 
                                            "Othr" => array(
                                                "Id" => "",
                                                "SchmeNm" => array(
                                                    "Cd" => "CNA",
                                                ),
                                            ),
                                        ),
                                    ), 
                                ),
                                "DbtrAcct" => array(
                                    "Id" => array(
                                        "Othr" => array(
                                            "Id" => $pago['CuentaOrigen'],
                                            "SchmeNm" => array(
                                                "Prtry" => "",
                                                "Cd" => "",
                                            ),
                                        ),
                                    ), 
                                    "Ccy" => $pago['DocCurr'],                        
                                ),
                                "DbtrAgt" => array(
                                    "FinInstnId" => array(
                                        "BIC" => FlujosController::obtenerDatos($pago['BankCode'], 3),
                                        "PstlAdr" => array(
                                            "Ctry" => FlujosController::obtenerDatos($pago['BankCode'], 2),
                                        ),
                                    ),
                                ),
                                //Este es el detalle por cada pago
                                "CdtTrfTxInf" => array(
                                    "PmtId" => array(
                                        "InstrId" => "", 
                                        "EndToEndId" => $pago['DocNum'],
                                    ), 
                                    "Amt" => array(
                                        "InstdAmt Ccy=".$pago['DocCurr'] => $TotalPagos,
                                    ),
                                    "ChrgBr" => FlujosController::obtenerTipo($pago['Tipo'], 2),
                                    "ChqInstr" => array(
                                        "ChqTp" => "",
                                        "DlvryMtd" => array(
                                            "Cd" => "",
                                        ),
                                        "InstrPrty" => "",
                                        "ChqMtrtyDt" => "",
                                    ),
                                    "CdtrAgt" => array(
                                        "FinInstnId" => array(
                                            "BIC" => FlujosController::obtenerDatos($pago['BankCode'], 3),
                                            "ClrSysMmbId" => array(
                                                "ClrSysId" => array(
                                                    "Prtry" => "",
                                                ),
                                                "MmbId" => FlujosController::obtenerDatos($pago['BankCode'], 1),
                                            ),
                                            "Nm" => FlujosController::obtenerDatos($pago['BankCode'], 4),
                                            "PstlAdr" => array(
                                                "Ctry" => FlujosController::obtenerDatos($pago['BankCode'], 2),
                                            ),
                                        ),
                                    ),
                                    "Cdtr" => array(
                                        "Nm" => $pago['EnFavorDe'],
                                        "PstlAdr" => array(
                                            "PstCd" => "",
                                            "CtrySubDvsn" => "",
                                            "Ctry" => "",
                                            "AdrLine" => "",
                                        ),
                                        "Id" => array(
                                            "OrgId" => array(
                                                "Othr"  => array(
                                                    "Id" => FlujosController::obtenerDatos($pago['IdFlujo'], 5),
                                                    "SchmeNm"  => array(
                                                        "Cd" => "",
                                                    ),
                                                ),
                                            ),
                                        ),
                                    ),
                                    "CdtrAcct" => array(
                                        "Id"  => array(
                                            "Othr"  => array(
                                                "Id" => $pago['DflAccount'],
                                            ),
                                        ),
                                        "Nm" => $pago['EnFavorDe'],
                                        "Tp" => array(
                                            "Cd" => FlujosController::esAhorro($pago['TipoCuentaDestino']),
                                        ),
                                    ),
                                    "Tax" => array(
                                        "Cdtr"  => array(
                                            "TaxId"  => "",
                                        ),
                                        "Dbtr"  => array(
                                            "TaxId"  => "",
                                        ),
                                        "Dt" => "",
                                    ),
                                    "RmtInf" => array(
                                        "Strd"  => array(
                                            "RfrdDocInf"  => array(
                                                "Tp" => array(
                                                    "CdOrPrtry" => array(
                                                        "Cd" => "",
                                                    ),
                                                ),
                                                "Nb"  => $pago['Comments'],
                                                "RltdDt"  => "",
                                            ),
                                            "RfrdDocAmt" => array(
                                                "DuePyblAmt" => "0",
                                                "DscntApldAmt" => "0",
                                                "CdtNoteAmt" => "0",
                                                "RmtdAmt" => "0",
                                            ),
                                            "CdtrRefInf" => array(
                                                "Tp" => array(
                                                    "Issr" => "",
                                                ),
                                            ),
                                            "AddtlRmtInf"  => "",
                                        ),
                                    ),
                                )                    
                            );
                            $j++;
                            if($respuesta == ""){
                                $xmlDoc = new \SimpleXMLElement(
                                    "<?xml version=\"1.0\" encoding=\"UTF-8\"?><Document></Document>"
                                );
                                $xmlDoc = $xmlDoc->addChild('CstmrCdtTrfInitn');
                                FlujosController::generarXML($encabezado, $pagos, $xmlDoc);
                                $dom = dom_import_simplexml($xmlDoc)->ownerDocument;
                                $dom->formatOutput = true;
                                $xml_file=$dom->save(storage_path('app/'.$FileName));
                                //Carga archivo xml a banco
                                if($xml_file){
                                    $pathFinalXml = storage_path('app/'.$FileName);
                                    Flujos::where('id_flujo', $pago['IdFlujo'])
                                    ->update(['NombreXML' => $FileName]);
                                    //Cambio previo a Producción
                                    //$file_sftp = File::copy($pathFinalXml, '/home/test2/out/'.$FileName);
                                    $file_sftp = File::copy($pathFinalXml, '/home/prd/out/'.$FileName);
                                    
                                    Flujos::where('id_flujo', $pago['IdFlujo'])
                                    ->update(['ArchivoSubido' => 1]);
                                    Flujos::where('id_flujo', $pago['IdFlujo'])
                                    ->update(['estado' => 17, 'nivel' => 0,'ConDuda' => 0]);
                                }else{
                                    Flujos::where('id_flujo', $pago['IdFlujo'])
                                    ->update(['estado' => 5]);
                                    //Buscar pago en array $flujos y quitarlo
                                    $keyObj = array_search($pago, $flujos);
                                    unset($flujos[$keyObj]);
                                }
                            }else{
                                Flujos::where('id_flujo', $pago['IdFlujo'])
                                ->update(['estado' => 5]);
                                //Buscar pago en array $flujos y quitarlo
                                $keyObj = array_search($pago, $flujos);
                                unset($flujos[$keyObj]);
                            }
                        }else{
                            //Buscar pago en array $flujos y quitarlo
                            $keyObj = array_search($pago, $flujos);
                            unset($flujos[$keyObj]);
                        }
                    }
                }

                $LotePago = new LotePago;
                $LotePago->tipo = $flujos[0]['Tipo'];
                $LotePago->fecha_hora = date("Y-m-d H:i",strtotime('-6 hour',strtotime(now())));
                $LotePago->id_usuario = $request->id_usuario;
                $LotePago->Activo = 1;
                $LotePago->Eliminado = 0;
                $LotePago->save();
                $contadorSinCheque = 0;
                $registrosParaInsertar = [];
                foreach($flujos as $pago){
                    $FlujoLotePago = new FlujoLotePago;
                    $FlujoLotePago->id_lotepago = $LotePago->id_lotepago;
                    $FlujoLotePago->id_flujo = $pago['IdFlujo'];
                    $registrosParaInsertar[] = $FlujoLotePago->attributesToArray();
                }
                FlujoLotePago::insert($registrosParaInsertar);

                $nombreArchivoPdf = 'PagosLote'.$LotePago->id_lotepago.'.pdf';
                $fechaActual = Carbon::now('America/Guatemala');
                $qrcode = base64_encode(QrCode::format('svg')->size(100)->errorCorrection('H')->generate('https://pagos.sion.com.gt/pagos/#/descargararchivos/'.$LotePago->id_lotepago));
                $dataArchivo = [
                    'CodigoQR' => $qrcode,
                    'fecha' => $fechaActual->toDateString(), 
                    'hora' => $fechaActual->toTimeString(),
                    'flujos' => $flujos
                ];
                //Crear archivo PDF
                $pdf = PDF::loadView('plantilla-pdf', compact('dataArchivo'))->setPaper('letter');
                $pathArchivoPdf = base_path('archivosPdf');
                $pdf->save($pathArchivoPdf.'/'.$nombreArchivoPdf);
                //Crear archivo Excel
                $nombreArchivoExcel = 'PagosLote'.$LotePago->id_lotepago.'.xlsx';
                $pagosExcel = array();
                foreach($flujos as $pago){
                    $partesFecha = explode("-", $pago['aut_date']);
                    $fechaTmp = "";
                    if(count($partesFecha) > 2){
                        $fechaTmp = $partesFecha[2]."/".$partesFecha[1]."/".$partesFecha[0];
                    }
                    $pagosExcel[] = [
                        'CuentaOrigen' => $pago['CuentaOrigen'],
                        'Cheque' => $pago['Cheque'],
                        'aut_date' => $fechaTmp,
                        'EnFavorDe' => $pago['EnFavorDe'],
                        'DocTotal' => $pago['DocTotal']
                    ];
                }
                Excel::store(new ArchivoPrimarioExport($pagosExcel),$nombreArchivoExcel);
                $pathFinal = storage_path($nombreArchivoExcel);
                $pathFinal = str_replace('PagosLote'.$LotePago->id_lotepago.'.xlsx','app/PagosLote'.$LotePago->id_lotepago.'.xlsx',$pathFinal);
                LotePago::where('id_lotepago', $LotePago->id_lotepago)
                ->update(['PathDocumentoPDF' => $pathArchivoPdf.'/'.$nombreArchivoPdf, 'PathDocumentoExcel' => $pathFinal]);
                $usuarioNotificacionTransaccion = UsuarioNotificacionTransaccion::where('Activo','=',1)
                ->where('Eliminado','=',0)->where('TipoTransaccion','=',$flujos[0]['Tipo'])->get();

                foreach($usuarioNotificacionTransaccion as $itemUsuarioNotificacion){
                    $datosUsuario = Usuarios::where('activo',1)->where('eliminado',0)
                    ->where('id_usuario', '=', $itemUsuarioNotificacion->id_usuario)->first();
                    if($datosUsuario){
                        $documentosEnviar = NotificacionTipoDocumentoLote::where('Activo',1)
                        ->where('Eliminado',0)->where('id_usuarionotificaciontransaccion',$itemUsuarioNotificacion->id_usuarionotificaciontransaccion)->get();
                        $contadorDocumentos = 0;

                        $details=['id_lotepago' => $LotePago->id_lotepago];
                        foreach($documentosEnviar as $itemDocumentos){
                            switch ($itemDocumentos->id_tipodocumentolote) {
                                case 1:
                                    $details+=['archivoPDF' => $pathArchivoPdf.'/'.$nombreArchivoPdf];
                                    break;
                                case 2:
                                    $details+=['archivoExcel' => $pathFinal];
                                    break;
                            }
                            $contadorDocumentos++;
                        }

                        if($contadorDocumentos > 0){
                            Mail::to($datosUsuario->correo)->send(new EnvioArchivos($details));
                        }
                    }
                }
                File::copy($pathArchivoPdf.'/'.$nombreArchivoPdf,'/var/www/html/proyectopagos/pagos/archivos/PagosLote'.$LotePago->id_lotepago.'.pdf');
                File::copy($pathFinal,'/var/www/html/proyectopagos/pagos/archivos/PagosLote'.$LotePago->id_lotepago.'.xlsx');
                return response()->json("OK"); 
            }else{
                return response()->json("No se encontraron pagos para compensar, es posible que otro usuario ya realizó esta acción."); 
            }
        }
    }

    //Es igual a updateOriginal pero tiene el cambio para enviar todos los pagos en el mismo xml
    public function update(Request $request, $id)
    {
        if($request->opcion == '1'){
            $flujos = Flujos::find($id);
            $flujos->estado = 6;
            $flujos->nivel = 0;
            $flujos->ConDuda = 0;
            $flujos->save();
            
            return response()->json("OK"); 
        }else if($request->opcion == '2'){
            $arrayPagos = array();
            $arrayPagosCompensados = array();
            $i = 0;
            foreach($request->pagos as $pago){
                $datosFlujo = Flujos::where('id_flujo',$pago)->first();
                if($datosFlujo->tipo == "BANCARIO" || $datosFlujo->tipo == "INTERNA"){
                    $arrayPagos[$i] = $pago;
                    $i++;
                }else{
                    $flujos = Flujos::join('Banco', function($join){
                        $join->on('Flujo.bank_code', 'Banco.codigo_SAP');
                    })
                    ->where('Flujo.id_flujo', $pago)
                    ->where('Banco.eliminado', 0)
                    ->where('Banco.activo', 1)->get();
                    if($flujos->count() > 0){
                        $arrayPagos[$i] = $pago;
                        $i++;
                    }
                }
            }
            $flujos = Flujos::selectRaw(
				"Flujo.id_flujo as IdFlujo,
                 Flujo.id_tipoflujo as IdTipoFlujo,
				 Flujo.doc_num as DocNum,
				 Flujo.tipo as Tipo,
				 DATE_FORMAT(Flujo.tax_date,'%Y-%m-%d') as TaxDate,
				 DATE_FORMAT(Flujo.doc_date,'%Y-%m-%d') as DocDate,
				 Flujo.card_code as CardCode,
				 Flujo.card_name as CardName,
				 Flujo.comments as Comments,
				 Flujo.doc_total as DocTotal,
				 Flujo.doc_curr as DocCurr,
				 Flujo.bank_code as BankCode,
				 Flujo.dfl_account as DflAccount,
				 Flujo.tipo_cuenta_destino as TipoCuentaDestino,
				 Flujo.cuenta_orgien as CuentaOrigen,
				 Flujo.empresa_codigo as EmpresaCodigo,
				 Flujo.empresa_nombre as EmpresaNombre,
				 Flujo.cheque as Cheque,
				 Flujo.en_favor_de as EnFavorDe,
				 Flujo.email as Email,
                 Flujo.dias_credito as DiasCredito,
                 Flujo.nombre_condicion_pago_dias as NombreCondicionPagoDias,
                 Flujo.NombreXML,
                 (select DATE_FORMAT(MAX(fd.Fecha),'%Y-%m-%d') from FlujoDetalle as fd where fd.IdEstadoFlujo = 5
                and fd.IdFlujo = Flujo.id_flujo) as aut_date"
			)
            ->whereIn('Flujo.id_flujo', $arrayPagos)
            ->where('Flujo.estado', '=',5)
            ->where('Flujo.activo', '=',1)
            ->where('Flujo.eliminado', '=',0)
            ->orderBy('Flujo.id_flujo', 'ASC')->get()->toArray(); 

            if(count($flujos)>0){
                $pagosDetalle = array();
                if($flujos[0]['Tipo'] == "BANCARIO" || $flujos[0]['Tipo'] == "INTERNA"){
                    foreach($flujos as $pago){
                        Flujos::where('id_flujo', $pago['IdFlujo'])
                        ->update(['estado' => 7, 'nivel' => 0,'ConDuda' => 0]);
                        $arrayPagosCompensados[] = $pago['IdFlujo'];
                    }
                    self::EnvioLotes($flujos, $request->id_usuario);
                }else{
                    $ListaCuentaOrigen = array();
                    foreach($flujos as $pago){
                        if(!in_array($pago['CuentaOrigen'],$ListaCuentaOrigen)){
                            $ListaCuentaOrigen[] = $pago['CuentaOrigen'];
                        }
                    }

                    foreach($ListaCuentaOrigen as $cuenta){

                        $pagosDeCuenta = array();
                        $contadorDePagos = 0;
                        $totalMontoDePagos = 0;
                        $FechaDoc = date("Y-m-d H:i",strtotime('-6 hour',strtotime(now())));
                        $Codigo = FlujosController::generarCodigo(13);
                        $CodigoFecha = date("YmdHis",strtotime('-6 hour',strtotime(now())));
                        $FileName = 'PAIN.001.001.03_GRUPOSIONXX_AMCNGTGTXXX_DOC'.$CodigoFecha.'.xml';
    
                        $pagos = array();
                        $j = 0;
    
                        foreach($flujos as $pago){
                            if($pago['CuentaOrigen'] == $cuenta){
                                $validamosPago = Flujos::where('id_flujo', $pago['IdFlujo'])
                                ->where('estado', '=',5)
                                ->first();
        
                                if($validamosPago){
    
                                    $pagosDeCuenta[] = $pago;
                                    $arrayPagosCompensados[] = $pago['IdFlujo'];

                                    Flujos::where('id_flujo', $pago['IdFlujo'])
                                    ->update(['estado' => 19]);
        
                                    $contadorDePagos++;
                                    //Cambio previo a Producción
                                    $totalMontoDePagos = $totalMontoDePagos + $pago['DocTotal'];
                                    $MontoPago = $pago['DocTotal'];
                                    //$totalMontoDePagos = $totalMontoDePagos + 1;
                                    //$MontoPago = 1;
                                    //Inicio de detalle
                                    $pagos[$j] = array(
                                        "PmtId" => array(
                                            "InstrId" => "", 
                                            "EndToEndId" => $pago['DocNum'],
                                        ), 
                                        "Amt" => array(
                                            "InstdAmt Ccy=".$pago['DocCurr'] => $MontoPago,
                                        ),
                                        "ChrgBr" => FlujosController::obtenerTipo($pago['Tipo'], 2),
                                        "ChqInstr" => array(
                                            "ChqTp" => "",
                                            "DlvryMtd" => array(
                                                "Cd" => "",
                                            ),
                                            "InstrPrty" => "",
                                            "ChqMtrtyDt" => "",
                                        ),
                                        "CdtrAgt" => array(
                                            "FinInstnId" => array(
                                                "BIC" => FlujosController::obtenerDatos($pago['BankCode'], 3),
                                                "ClrSysMmbId" => array(
                                                    "ClrSysId" => array(
                                                        "Prtry" => "",
                                                    ),
                                                    "MmbId" => FlujosController::obtenerDatos($pago['BankCode'], 1),
                                                ),
                                                "Nm" => FlujosController::obtenerDatos($pago['BankCode'], 4),
                                                "PstlAdr" => array(
                                                    "Ctry" => FlujosController::obtenerDatos($pago['BankCode'], 2),
                                                ),
                                            ),
                                        ),
                                        "Cdtr" => array(
                                            "Nm" => $pago['EnFavorDe'],
                                            "PstlAdr" => array(
                                                "PstCd" => "",
                                                "CtrySubDvsn" => "",
                                                "Ctry" => "",
                                                "AdrLine" => "",
                                            ),
                                            "Id" => array(
                                                "OrgId" => array(
                                                    "Othr"  => array(
                                                        "Id" => FlujosController::obtenerDatos($pago['IdFlujo'], 5),
                                                        "SchmeNm"  => array(
                                                            "Cd" => "",
                                                        ),
                                                    ),
                                                ),
                                            ),
                                        ),
                                        "CdtrAcct" => array(
                                            "Id"  => array(
                                                "Othr"  => array(
                                                    "Id" => $pago['DflAccount'],
                                                ),
                                            ),
                                            "Nm" => $pago['EnFavorDe'],
                                            "Tp" => array(
                                                "Cd" => FlujosController::esAhorro($pago['TipoCuentaDestino']),
                                            ),
                                        ),
                                        "Tax" => array(
                                            "Cdtr"  => array(
                                                "TaxId"  => "",
                                            ),
                                            "Dbtr"  => array(
                                                "TaxId"  => "",
                                            ),
                                            "Dt" => "",
                                        ),
                                        "RmtInf" => array(
                                            "Strd"  => array(
                                                "RfrdDocInf"  => array(
                                                    "Tp" => array(
                                                        "CdOrPrtry" => array(
                                                            "Cd" => "",
                                                        ),
                                                    ),
                                                    "Nb"  => $pago['Comments'],
                                                    "RltdDt"  => "",
                                                ),
                                                "RfrdDocAmt" => array(
                                                    "DuePyblAmt" => "0",
                                                    "DscntApldAmt" => "0",
                                                    "CdtNoteAmt" => "0",
                                                    "RmtdAmt" => "0",
                                                ),
                                                "CdtrRefInf" => array(
                                                    "Tp" => array(
                                                        "Issr" => "",
                                                    ),
                                                ),
                                                "AddtlRmtInf"  => "",
                                            ),
                                        )           
                                    );
                                    $j++;
                                }else{
                                    //Buscar pago en array $flujos y quitarlo
                                    $keyObj = array_search($pago, $flujos);
                                    unset($flujos[$keyObj]);
                                }
                            }
                        }

                        if(count($pagosDeCuenta)>0){
                    
                            $encabezado = array(
                                "GrpHdr" => array(
                                    "MsgId" => $Codigo,
                                    "CreDtTm" => $FechaDoc,
                                    "NbOfTxs" => $contadorDePagos,
                                    "CtrlSum" => $totalMontoDePagos,
                                    "InitgPty" => array(
                                        "Nm" => "Grupo Sion",
                                        "Id" => array(
                                            "OrgId" => array(
                                                "BICOrBEI" => "BICCLIENTE",
                                            )
                                        )
                                    )
                                )
                            );
        
                            $encabezadoDetalle = array(
                                "PmtInfId" => $Codigo,
                                "PmtMtd" => FlujosController::obtenerTipo($pagosDeCuenta[0]['Tipo'], 1),
                                "BtchBookg" => "",
                                "NbOfTxs" => $contadorDePagos,
                                "CtrlSum" => $totalMontoDePagos,
                                "PmtTpInf" => array(
                                    "InstrPrty" => "",
                                    "CtgyPurp" => array(
                                        "Cd" => "",
                                    ), 
                                    "SvcLvl" => array(
                                        "Cd" => "",
                                    ), 
                                ),
                                "ReqdExctnDt" => $pagosDeCuenta[0]['DocDate'],
                                "Dbtr" => array(
                                    "Nm" => FlujosController::obtenerDatos($pagosDeCuenta[0]['IdFlujo'], 6),
                                    "PstlAdr" => array(
                                        "StrtNm" => FlujosController::obtenerDatos($pagosDeCuenta[0]['IdFlujo'], 7),
                                        "TwnNm" => "",
                                        "Ctry" => "",
                                    ),
                                    "CtryOfRes" => "", 
                                    "Id" => array(
                                        "OrgId" => array( 
                                            "Othr" => array(
                                                "Id" => "",
                                                "SchmeNm" => array(
                                                    "Cd" => "CNA",
                                                ),
                                            ),
                                        ),
                                    ), 
                                ),
                                "DbtrAcct" => array(
                                    "Id" => array(
                                        "Othr" => array(
                                            "Id" => $pagosDeCuenta[0]['CuentaOrigen'],
                                            "SchmeNm" => array(
                                                "Prtry" => "",
                                                "Cd" => "",
                                            ),
                                        ),
                                    ), 
                                    "Ccy" => $pagosDeCuenta[0]['DocCurr'],                        
                                ),
                                "DbtrAgt" => array(
                                    "FinInstnId" => array(
                                        "BIC" => FlujosController::obtenerDatos($pagosDeCuenta[0]['BankCode'], 3),
                                        "PstlAdr" => array(
                                            "Ctry" => FlujosController::obtenerDatos($pagosDeCuenta[0]['BankCode'], 2),
                                        ),
                                    ),
                                )
                            );
        
                            foreach($pagos as $items){
                                $encabezadoDetalle[]["CdtTrfTxInf"] = $items;
                            }
                            $pagosDetalle[0] = $encabezadoDetalle;
        
                            $xmlDoc = new \SimpleXMLElement(
                                "<?xml version=\"1.0\" encoding=\"UTF-8\"?><Document></Document>"
                            );
                            $xmlDoc = $xmlDoc->addChild('CstmrCdtTrfInitn');
                            FlujosController::generarXML($encabezado, $pagosDetalle, $xmlDoc);
                            $dom = dom_import_simplexml($xmlDoc)->ownerDocument;
                            $dom->formatOutput = true;
                            $xml_file=$dom->save(storage_path('app/'.$FileName));
                            //Carga archivo xml a banco
                            if($xml_file){
                                $pathFinalXml = storage_path('app/'.$FileName);
                                //Cambio previo a Producción
                                //$file_sftp = File::copy($pathFinalXml, '/home/test2/out/'.$FileName);
                                $file_sftp = File::copy($pathFinalXml, '/home/prd/out/'.$FileName);
                                foreach($pagosDeCuenta as $pago){
                                    Flujos::where('id_flujo', $pago['IdFlujo'])
                                    ->update(['ArchivoSubido' => 1, 'NombreXML' => $FileName, 'estado' => 17, 'nivel' => 0,'ConDuda' => 0]);
                                }
                            }else{
                                foreach($pagosDeCuenta as $pago){
                                    Flujos::where('id_flujo', $pago['IdFlujo'])
                                    ->update(['estado' => 5]);
                                    //Buscar pago en array $flujos y quitarlo
                                    $keyObj = array_search($pago, $flujos);
                                    unset($flujos[$keyObj]);
                                }
                            }
        
                            self::EnvioLotes($pagosDeCuenta, $request->id_usuario);
                        }

                    }
                }
                $respuestaFinal = array();
                $respuestaFinal["mensaje"] = "OK";
                $respuestaFinal["pagos"] = $arrayPagosCompensados;
                return response()->json($respuestaFinal); 
            }else{
                
                $respuestaFinal = array();
                $respuestaFinal["mensaje"] = "No se encontraron pagos para compensar, es posible que otro usuario ya realizó esta acción.";
                return response()->json($respuestaFinal); 
            }
        }
    }

    function EnvioLotes($flujos, $id_usuario){
        $LotePago = new LotePago;
        $LotePago->tipo = $flujos[0]['Tipo'];
        $LotePago->fecha_hora = date("Y-m-d H:i",strtotime('-6 hour',strtotime(now())));
        $LotePago->id_usuario = $id_usuario;
        $LotePago->Activo = 1;
        $LotePago->Eliminado = 0;
        $LotePago->save();
        $contadorSinCheque = 0;
        $registrosParaInsertar = [];
        foreach($flujos as $pago){
            $FlujoLotePago = new FlujoLotePago;
            $FlujoLotePago->id_lotepago = $LotePago->id_lotepago;
            $FlujoLotePago->id_flujo = $pago['IdFlujo'];
            $registrosParaInsertar[] = $FlujoLotePago->attributesToArray();
        }
        FlujoLotePago::insert($registrosParaInsertar);

        $nombreArchivoPdf = 'PagosLote'.$LotePago->id_lotepago.'.pdf';
        $fechaActual = Carbon::now('America/Guatemala');
        $qrcode = base64_encode(QrCode::format('svg')->size(100)->errorCorrection('H')->generate('https://pagos.sion.com.gt/pagos/#/descargararchivos/'.$LotePago->id_lotepago));
        $dataArchivo = [
            'CodigoQR' => $qrcode,
            'fecha' => $fechaActual->toDateString(), 
            'hora' => $fechaActual->toTimeString(),
            'flujos' => $flujos
        ];
        //Crear archivo PDF
        $pdf = PDF::loadView('plantilla-pdf', compact('dataArchivo'))->setPaper('letter');
        $pathArchivoPdf = base_path('archivosPdf');
        $pdf->save($pathArchivoPdf.'/'.$nombreArchivoPdf);
        //Crear archivo Excel
        $nombreArchivoExcel = 'PagosLote'.$LotePago->id_lotepago.'.xlsx';
        $pagosExcel = array();
        foreach($flujos as $pago){
            $partesFecha = explode("-", $pago['aut_date']);
            $fechaTmp = "";
            if(count($partesFecha) > 2){
                $fechaTmp = $partesFecha[2]."/".$partesFecha[1]."/".$partesFecha[0];
            }
            $pagosExcel[] = [
                'CuentaOrigen' => $pago['CuentaOrigen'],
                'Cheque' => $pago['Cheque'],
                'aut_date' => $fechaTmp,
                'EnFavorDe' => $pago['EnFavorDe'],
                'DocTotal' => $pago['DocTotal']
            ];
        }
        Excel::store(new ArchivoPrimarioExport($pagosExcel),$nombreArchivoExcel);
        $pathFinal = storage_path($nombreArchivoExcel);
        $pathFinal = str_replace('PagosLote'.$LotePago->id_lotepago.'.xlsx','app/PagosLote'.$LotePago->id_lotepago.'.xlsx',$pathFinal);
        LotePago::where('id_lotepago', $LotePago->id_lotepago)
        ->update(['PathDocumentoPDF' => $pathArchivoPdf.'/'.$nombreArchivoPdf, 'PathDocumentoExcel' => $pathFinal]);
        $usuarioNotificacionTransaccion = UsuarioNotificacionTransaccion::where('Activo','=',1)
        ->where('Eliminado','=',0)->where('TipoTransaccion','=',$flujos[0]['Tipo'])->get();

        foreach($usuarioNotificacionTransaccion as $itemUsuarioNotificacion){
            $datosUsuario = Usuarios::where('activo',1)->where('eliminado',0)
            ->where('id_usuario', '=', $itemUsuarioNotificacion->id_usuario)->first();
            if($datosUsuario){
                $documentosEnviar = NotificacionTipoDocumentoLote::where('Activo',1)
                ->where('Eliminado',0)->where('id_usuarionotificaciontransaccion',$itemUsuarioNotificacion->id_usuarionotificaciontransaccion)->get();
                $contadorDocumentos = 0;

                $details=['id_lotepago' => $LotePago->id_lotepago];
                foreach($documentosEnviar as $itemDocumentos){
                    switch ($itemDocumentos->id_tipodocumentolote) {
                        case 1:
                            $details+=['archivoPDF' => $pathArchivoPdf.'/'.$nombreArchivoPdf];
                            break;
                        case 2:
                            $details+=['archivoExcel' => $pathFinal];
                            break;
                    }
                    $contadorDocumentos++;
                }

                if($contadorDocumentos > 0){
                    Mail::to($datosUsuario->correo)->send(new EnvioArchivos($details));
                }
            }
        }
        File::copy($pathArchivoPdf.'/'.$nombreArchivoPdf,'/var/www/html/proyectopagos/pagos/archivos/PagosLote'.$LotePago->id_lotepago.'.pdf');
        File::copy($pathFinal,'/var/www/html/proyectopagos/pagos/archivos/PagosLote'.$LotePago->id_lotepago.'.xlsx');
    }

    public function delete(Request $request)
    {
    }

    public function flujoarchivo(Request $request)
    {
        $id_lotepago = $request->id_lotepago;
        $tipo_archivo = $request->tipo_archivo;
        $datosLote = LotePago::where('id_lotepago',$id_lotepago)->first();

        if($tipo_archivo == 'PDF'){
            $nombreArchivoBase = 'PagosLote'.$datosLote->id_lotepago.'.pdf';
            $headers = [
                'Content-Type' => 'application/pdf',
            ];
            return response()->download($datosLote->PathDocumentoPDF, $nombreArchivoBase, $headers);
        }else{
            $nombreArchivoBase = 'PagosLote'.$datosLote->id_lotepago.'.xlsx';
            $headers = [
                'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            ];
            return response()->download($datosLote->PathDocumentoExcel, $nombreArchivoBase, $headers);
        }
    }

    public function ActualizarDatosOrigenCompensar($id_flujo)
    {
        try
        {
            ini_set('memory_limit', '1024M');
            $flujoOriginal = Flujos::where('id_flujo','=',$id_flujo)->first();
            if($flujoOriginal)
            {
                if($flujoOriginal->origen_datos == 'SAP'){
                    $fechaFlujoOriginal = strtotime($flujoOriginal->doc_date);
                    $docNumOriginal = $flujoOriginal->doc_num;
                    $fecha_fin = date('Y-m-d', strtotime("+1 days", $fechaFlujoOriginal));
                    $fecha_inicio = date('Y-m-d', strtotime("-1 days", $fechaFlujoOriginal));
                    $client = new \nusoap_client('http://10.20.30.144/GSION_WS/WSGetFromSAP.asmx?wsdl',true);
                    $param = array('sFechaInicial'=>$fecha_inicio , 'sFechaFinal'=>$fecha_fin);
                    $resultado = $client->call('Get_PAGOEFECTUADO_XML',$param);
                    if($client->fault)
                    {
                        $error = $client->getError();;
                        if($error)
                        {
                            echo 'Error:' . $client->faultstring;
                        }
                        die();
                    }
                    $lineas = $resultado['Get_PAGOEFECTUADO_XMLResult']['BOM']['BO']['Recordset']['row'];
                    if(count($lineas) == count($lineas, COUNT_RECURSIVE))
                    {
                        if($lineas['DocNum'] == $docNumOriginal){
                            $existeFlujo = Flujos::where('doc_num',$lineas['DocNum'])
                            ->where('activo','=',1)
                            ->where('eliminado','=',0)->first();
                            if($existeFlujo)
                            {
                                /* $existeFlujo->doc_num = $lineas['DocNum'];
                                $existeFlujo->tipo = utf8_encode($lineas['TIPO']);
                                $existeFlujo->tax_date = $lineas['TaxDate'];
                                $existeFlujo->doc_date = $lineas['DocDate'];
                                $existeFlujo->card_code = utf8_encode($lineas['CardCode']);
                                $existeFlujo->card_name = utf8_encode($lineas['CardName']); */
                                $existeFlujo->comments = utf8_encode($lineas['Comments']);
                                /* $existeFlujo->doc_total = $lineas['DocTotal'];
                                $existeFlujo->doc_curr = utf8_encode($lineas['DocCurr']); */
                                $existeFlujo->bank_code = utf8_encode($lineas['BankCode']);
                                $existeFlujo->dfl_account = utf8_encode($lineas['DflAccount']);
                                $existeFlujo->tipo_cuenta_destino = utf8_encode($lineas['Tipo_Cuenta_Destino']);
                                /* $existeFlujo->cuenta_orgien = utf8_encode($lineas['Cuenta_Origen']);
                                $existeFlujo->empresa_codigo = $lineas['Empresa_codigo'];
                                $existeFlujo->empresa_nombre = utf8_encode($lineas['Empresa_nombre']);*/
                                $existeFlujo->cheque = $lineas['Cheque'];
                                /*$existeFlujo->en_favor_de = utf8_encode($lineas['EnFavorDe']);
                                $existeFlujo->email = utf8_encode($lineas['E_Mail']);
                                $existeFlujo->dias_credito = $lineas['Dias'];
                                $existeFlujo->nombre_condicion_pago_dias = utf8_encode($lineas['NombreCondicionPagoDias']); */
                                $existeFlujo->save();        
    
                                //Consulta de datos adicionales
                                
                                self::GetFlujoFacturaCantidad($existeFlujo->id_flujo,$lineas['DocNum']);
                                self::GetFlujoFacturaDocumento($existeFlujo->id_flujo,$lineas['DocNum']);
                                self::GetFlujoIngreso($existeFlujo->id_flujo,$lineas['DocNum']);
                                self::GetFlujoOferta($existeFlujo->id_flujo,$lineas['DocNum']);
                                self::GetFlujoOrden($existeFlujo->id_flujo,$lineas['DocNum']);
                                self::GetFlujoSolicitud($existeFlujo->id_flujo,$lineas['DocNum']); 
                                self::GetFlujoNumeroCheque($existeFlujo->id_flujo,$lineas['DocNum']);                   
                            }
                        }
                    }
                    else
                    {
                        for($i=0; $i< count($lineas);$i++)
                        {
                            if($lineas[$i]['DocNum'] == $docNumOriginal){
                                $existeFlujo = Flujos::where('doc_num',$lineas[$i]['DocNum'])
                                ->where('activo','=',1)
                                ->where('eliminado','=',0)->first();
                                if($existeFlujo)
                                {
                                    /* $existeFlujo->doc_num = $lineas[$i]['DocNum'];
                                    $existeFlujo->tipo = utf8_encode($lineas[$i]['TIPO']);
                                    $existeFlujo->tax_date = $lineas[$i]['TaxDate'];
                                    $existeFlujo->doc_date = $lineas[$i]['DocDate'];
                                    $existeFlujo->card_code = utf8_encode($lineas[$i]['CardCode']);
                                    $existeFlujo->card_name = utf8_encode($lineas[$i]['CardName']); */
                                    $existeFlujo->comments = utf8_encode($lineas[$i]['Comments']);
                                    /* $existeFlujo->doc_total = $lineas[$i]['DocTotal'];
                                    $existeFlujo->doc_curr = utf8_encode($lineas[$i]['DocCurr']); */
                                    $existeFlujo->bank_code = utf8_encode($lineas[$i]['BankCode']);
                                    $existeFlujo->dfl_account = utf8_encode($lineas[$i]['DflAccount']);
                                    $existeFlujo->tipo_cuenta_destino = utf8_encode($lineas[$i]['Tipo_Cuenta_Destino']);
                                    /* $existeFlujo->cuenta_orgien = utf8_encode($lineas[$i]['Cuenta_Origen']);
                                    $existeFlujo->empresa_codigo = $lineas[$i]['Empresa_codigo'];
                                    $existeFlujo->empresa_nombre = utf8_encode($lineas[$i]['Empresa_nombre']);*/
                                    $existeFlujo->cheque = $lineas[$i]['Cheque'];
                                    /*$existeFlujo->en_favor_de = utf8_encode($lineas[$i]['EnFavorDe']);
                                    $existeFlujo->email = utf8_encode($lineas[$i]['E_Mail']);
                                    $existeFlujo->dias_credito = $lineas[$i]['Dias'];
                                    $existeFlujo->nombre_condicion_pago_dias = utf8_encode($lineas[$i]['NombreCondicionPagoDias']); */
                                    $existeFlujo->save();
    
                                    //Consulta de datos adicionales
                                    
                                    self::GetFlujoFacturaCantidad($existeFlujo->id_flujo,$lineas[$i]['DocNum']);
                                    self::GetFlujoFacturaDocumento($existeFlujo->id_flujo,$lineas[$i]['DocNum']);
                                    self::GetFlujoIngreso($existeFlujo->id_flujo,$lineas[$i]['DocNum']);
                                    self::GetFlujoOferta($existeFlujo->id_flujo,$lineas[$i]['DocNum']);
                                    self::GetFlujoOrden($existeFlujo->id_flujo,$lineas[$i]['DocNum']);
                                    self::GetFlujoSolicitud($existeFlujo->id_flujo,$lineas[$i]['DocNum']);  
                                    self::GetFlujoNumeroCheque($existeFlujo->id_flujo,$lineas[$i]['DocNum']);  
                                }
                            }
                        }
                    }
                }

                if($flujoOriginal->origen_datos == 'ITS'){
                    self::cargaits($flujoOriginal->doc_num);
                }
            }
        }catch(Exception $e){
            Log::error($e->getMessage());
        }
    }

    public function ActualizarDatosOrigen($id_flujo)
    {
        try
        {
            ini_set('memory_limit', '1024M');
            $flujoOriginal = Flujos::where('id_flujo','=',$id_flujo)->first();
            if($flujoOriginal)
            {
                if($flujoOriginal->origen_datos == 'SAP'){
                    $fechaFlujoOriginal = strtotime($flujoOriginal->doc_date);
                    $docNumOriginal = $flujoOriginal->doc_num;
                    $fecha_fin = date('Y-m-d', strtotime("+1 days", $fechaFlujoOriginal));
                    $fecha_inicio = date('Y-m-d', strtotime("-1 days", $fechaFlujoOriginal));
                    $client = new \nusoap_client('http://10.20.30.144/GSION_WS/WSGetFromSAP.asmx?wsdl',true);
                    $param = array('sFechaInicial'=>$fecha_inicio , 'sFechaFinal'=>$fecha_fin);
                    $resultado = $client->call('Get_PAGOEFECTUADO_XML',$param);
                    if($client->fault)
                    {
                        $error = $client->getError();;
                        if($error)
                        {
                            echo 'Error:' . $client->faultstring;
                        }
                        die();
                    }
                    $lineas = $resultado['Get_PAGOEFECTUADO_XMLResult']['BOM']['BO']['Recordset']['row'];
                    if(count($lineas) == count($lineas, COUNT_RECURSIVE))
                    {
                        if($lineas['DocNum'] == $docNumOriginal){
                            $existeFlujo = Flujos::where('doc_num',$lineas['DocNum'])
                            ->where('activo','=',1)
                            ->where('eliminado','=',0)->first();
                            if($existeFlujo)
                            {
                                $existeFlujo->doc_num = $lineas['DocNum'];
                                $existeFlujo->tipo = utf8_encode($lineas['TIPO']);
                                $existeFlujo->tax_date = $lineas['TaxDate'];
                                $existeFlujo->doc_date = $lineas['DocDate'];
                                $existeFlujo->card_code = utf8_encode($lineas['CardCode']);
                                $existeFlujo->card_name = utf8_encode($lineas['CardName']);
                                $existeFlujo->comments = utf8_encode($lineas['Comments']);
                                $existeFlujo->doc_total = $lineas['DocTotal'];
                                $existeFlujo->doc_curr = utf8_encode($lineas['DocCurr']);
                                $existeFlujo->bank_code = utf8_encode($lineas['BankCode']);
                                $existeFlujo->dfl_account = utf8_encode($lineas['DflAccount']);
                                $existeFlujo->tipo_cuenta_destino = utf8_encode($lineas['Tipo_Cuenta_Destino']);
                                $existeFlujo->cuenta_orgien = utf8_encode($lineas['Cuenta_Origen']);
                                $existeFlujo->empresa_codigo = $lineas['Empresa_codigo'];
                                $existeFlujo->empresa_nombre = utf8_encode($lineas['Empresa_nombre']);
                                $existeFlujo->cheque = $lineas['Cheque'];
                                $existeFlujo->en_favor_de = utf8_encode($lineas['EnFavorDe']);
                                $existeFlujo->email = utf8_encode($lineas['E_Mail']);
                                $existeFlujo->dias_credito = $lineas['Dias'];
                                $existeFlujo->nombre_condicion_pago_dias = utf8_encode($lineas['NombreCondicionPagoDias']);
                                $existeFlujo->save();
    
                                //Consulta de datos adicionales
                                
                                self::GetFlujoFacturaCantidad($existeFlujo->id_flujo,$lineas['DocNum']);
                                self::GetFlujoFacturaDocumento($existeFlujo->id_flujo,$lineas['DocNum']);
                                self::GetFlujoIngreso($existeFlujo->id_flujo,$lineas['DocNum']);
                                self::GetFlujoOferta($existeFlujo->id_flujo,$lineas['DocNum']);
                                self::GetFlujoOrden($existeFlujo->id_flujo,$lineas['DocNum']);
                                self::GetFlujoSolicitud($existeFlujo->id_flujo,$lineas['DocNum']); 
                                self::GetFlujoNumeroCheque($existeFlujo->id_flujo,$lineas['DocNum']);                       
                            }
                        }
                    }
                    else
                    {
                        for($i=0; $i< count($lineas);$i++)
                        {
                            if($lineas[$i]['DocNum'] == $docNumOriginal){
                                $existeFlujo = Flujos::where('doc_num',$lineas[$i]['DocNum'])
                                ->where('activo','=',1)
                                ->where('eliminado','=',0)->first();
                                if($existeFlujo)
                                {
                                    $existeFlujo->doc_num = $lineas[$i]['DocNum'];
                                    $existeFlujo->tipo = utf8_encode($lineas[$i]['TIPO']);
                                    $existeFlujo->tax_date = $lineas[$i]['TaxDate'];
                                    $existeFlujo->doc_date = $lineas[$i]['DocDate'];
                                    $existeFlujo->card_code = utf8_encode($lineas[$i]['CardCode']);
                                    $existeFlujo->card_name = utf8_encode($lineas[$i]['CardName']);
                                    $existeFlujo->comments = utf8_encode($lineas[$i]['Comments']);
                                    $existeFlujo->doc_total = $lineas[$i]['DocTotal'];
                                    $existeFlujo->doc_curr = utf8_encode($lineas[$i]['DocCurr']);
                                    $existeFlujo->bank_code = utf8_encode($lineas[$i]['BankCode']);
                                    $existeFlujo->dfl_account = utf8_encode($lineas[$i]['DflAccount']);
                                    $existeFlujo->tipo_cuenta_destino = utf8_encode($lineas[$i]['Tipo_Cuenta_Destino']);
                                    $existeFlujo->cuenta_orgien = utf8_encode($lineas[$i]['Cuenta_Origen']);
                                    $existeFlujo->empresa_codigo = $lineas[$i]['Empresa_codigo'];
                                    $existeFlujo->empresa_nombre = utf8_encode($lineas[$i]['Empresa_nombre']);
                                    $existeFlujo->cheque = $lineas[$i]['Cheque'];
                                    $existeFlujo->en_favor_de = utf8_encode($lineas[$i]['EnFavorDe']);
                                    $existeFlujo->email = utf8_encode($lineas[$i]['E_Mail']);
                                    $existeFlujo->dias_credito = $lineas[$i]['Dias'];
                                    $existeFlujo->nombre_condicion_pago_dias = utf8_encode($lineas[$i]['NombreCondicionPagoDias']);
                                    $existeFlujo->cuenta_contable = utf8_encode($lineas[$i]['CuentaContable']);
                                    $existeFlujo->save();
    
                                    //Consulta de datos adicionales
                                    
                                    self::GetFlujoFacturaCantidad($existeFlujo->id_flujo,$lineas[$i]['DocNum']);
                                    self::GetFlujoFacturaDocumento($existeFlujo->id_flujo,$lineas[$i]['DocNum']);
                                    self::GetFlujoIngreso($existeFlujo->id_flujo,$lineas[$i]['DocNum']);
                                    self::GetFlujoOferta($existeFlujo->id_flujo,$lineas[$i]['DocNum']);
                                    self::GetFlujoOrden($existeFlujo->id_flujo,$lineas[$i]['DocNum']);
                                    self::GetFlujoSolicitud($existeFlujo->id_flujo,$lineas[$i]['DocNum']);  
                                    self::GetFlujoNumeroCheque($existeFlujo->id_flujo,$lineas[$i]['DocNum']);  
                                }
                            }
                        }
                    }
                }
                
                if($flujoOriginal->origen_datos == 'ITS'){
                    self::cargaits($flujoOriginal->doc_num);
                }

                
                $Detalle = FlujoDetalle::where('IdFlujo', $id_flujo)
                ->where('IdEstadoFlujo','<',10)
                ->orderBy('Fecha','DESC')->first();
                Flujos::where('id_flujo', $id_flujo)
                ->update([
                    'estado' => $Detalle->IdEstadoFlujo
                ]);
            }
        }catch(Exception $e){
            Log::error($e->getMessage());
        }
    }

    public function ActualizarDatosOrigenValidacion($id_flujo, $id_usuario)
    {
        try
        {
            ini_set('memory_limit', '1024M');
            $reiniciar = 0;
            $motivoCambio = '';
            $flujoOriginal = Flujos::where('id_flujo','=',$id_flujo)->first();
            if($flujoOriginal)
            {
                if($flujoOriginal->origen_datos == 'SAP'){
                    $fechaFlujoOriginal = strtotime($flujoOriginal->doc_date);
                    $docNumOriginal = $flujoOriginal->doc_num;
                    $fecha_fin = date('Y-m-d', strtotime("+1 days", $fechaFlujoOriginal));
                    $fecha_inicio = date('Y-m-d', strtotime("-1 days", $fechaFlujoOriginal));
                    $client = new \nusoap_client('http://10.20.30.144/GSION_WS/WSGetFromSAP.asmx?wsdl',true);
                    $param = array('sFechaInicial'=>$fecha_inicio , 'sFechaFinal'=>$fecha_fin);
                    $resultado = $client->call('Get_PAGOEFECTUADO_XML',$param);
                    if($client->fault)
                    {
                        $error = $client->getError();;
                        if($error)
                        {
                            echo 'Error:' . $client->faultstring;
                        }
                        die();
                    }
                    $lineas = $resultado['Get_PAGOEFECTUADO_XMLResult']['BOM']['BO']['Recordset']['row'];
                    if(count($lineas) == count($lineas, COUNT_RECURSIVE))
                    {
                        if($lineas['DocNum'] == $docNumOriginal){
                            $existeFlujo = Flujos::where('doc_num',$lineas['DocNum'])
                            ->where('activo','=',1)
                            ->where('eliminado','=',0)->first();
                            if($existeFlujo)
                            {
                                if($existeFlujo->dfl_account != utf8_encode($lineas['DflAccount'])){
                                    if($motivoCambio==''){
                                        $motivoCambio='Cuenta';
                                    }else{
                                        $motivoCambio.=', cuenta';
                                    }
                                    $reiniciar = 1;
                                }
                                if($existeFlujo->bank_code != utf8_encode($lineas['BankCode'])){
                                    if($motivoCambio==''){
                                        $motivoCambio='Banco';
                                    }else{
                                        $motivoCambio.=', banco';
                                    }
                                    $reiniciar = 1;
                                }
                                if($existeFlujo->tipo_cuenta_destino != utf8_encode($lineas['Tipo_Cuenta_Destino'])){
                                    if($motivoCambio==''){
                                        $motivoCambio='Tipo de cuenta';
                                    }else{
                                        $motivoCambio.=', tipo de cuenta';
                                    }
                                    $reiniciar = 1;
                                }
                                /* $existeFlujo->doc_num = $lineas['DocNum'];
                                $existeFlujo->tipo = utf8_encode($lineas['TIPO']);
                                $existeFlujo->tax_date = $lineas['TaxDate'];
                                $existeFlujo->doc_date = $lineas['DocDate'];
                                $existeFlujo->card_code = utf8_encode($lineas['CardCode']);
                                $existeFlujo->card_name = utf8_encode($lineas['CardName']); */
                                $existeFlujo->comments = utf8_encode($lineas['Comments']);
                                /* $existeFlujo->doc_total = $lineas['DocTotal'];
                                $existeFlujo->doc_curr = utf8_encode($lineas['DocCurr']); */
                                $existeFlujo->bank_code = utf8_encode($lineas['BankCode']);
                                $existeFlujo->dfl_account = utf8_encode($lineas['DflAccount']);
                                $existeFlujo->tipo_cuenta_destino = utf8_encode($lineas['Tipo_Cuenta_Destino']);
                                /* $existeFlujo->cuenta_orgien = utf8_encode($lineas['Cuenta_Origen']);
                                $existeFlujo->empresa_codigo = $lineas['Empresa_codigo'];
                                $existeFlujo->empresa_nombre = utf8_encode($lineas['Empresa_nombre']); */
                                $existeFlujo->cheque = $lineas['Cheque'];
                                /* $existeFlujo->en_favor_de = utf8_encode($lineas['EnFavorDe']);
                                $existeFlujo->email = utf8_encode($lineas['E_Mail']);
                                $existeFlujo->dias_credito = $lineas['Dias'];
                                $existeFlujo->nombre_condicion_pago_dias = utf8_encode($lineas['NombreCondicionPagoDias']); */
                                $existeFlujo->save();
    
                                //Consulta de datos adicionales
                                self::GetFlujoFacturaCantidad($existeFlujo->id_flujo,$lineas['DocNum']);
                                self::GetFlujoFacturaDocumento($existeFlujo->id_flujo,$lineas['DocNum']);
                                self::GetFlujoIngreso($existeFlujo->id_flujo,$lineas['DocNum']);
                                self::GetFlujoOferta($existeFlujo->id_flujo,$lineas['DocNum']);
                                self::GetFlujoOrden($existeFlujo->id_flujo,$lineas['DocNum']);
                                self::GetFlujoSolicitud($existeFlujo->id_flujo,$lineas['DocNum']);        
                                self::GetFlujoNumeroCheque($existeFlujo->id_flujo,$lineas['DocNum']);                  
                            }
                        }
                    }
                    else
                    {
                        for($i=0; $i< count($lineas);$i++)
                        {
                            if($lineas[$i]['DocNum'] == $docNumOriginal){
                                $existeFlujo = Flujos::where('doc_num',$lineas[$i]['DocNum'])
                                ->where('activo','=',1)
                                ->where('eliminado','=',0)->first();
                                if($existeFlujo)
                                {
                                    if($existeFlujo->dfl_account != utf8_encode($lineas[$i]['DflAccount'])){
                                        if($motivoCambio==''){
                                            $motivoCambio='Cuenta';
                                        }else{
                                            $motivoCambio.=', cuenta';
                                        }
                                        $reiniciar = 1;
                                    }
                                    if($existeFlujo->bank_code != utf8_encode($lineas[$i]['BankCode'])){
                                        if($motivoCambio==''){
                                            $motivoCambio='Banco';
                                        }else{
                                            $motivoCambio.=', banco';
                                        }
                                        $reiniciar = 1;
                                    }
                                    if($existeFlujo->tipo_cuenta_destino != utf8_encode($lineas[$i]['Tipo_Cuenta_Destino'])){
                                        if($motivoCambio==''){
                                            $motivoCambio='Tipo de cuenta';
                                        }else{
                                            $motivoCambio.=', tipo de cuenta';
                                        }
                                        $reiniciar = 1;
                                    }
                                    /* $existeFlujo->doc_num = $lineas[$i]['DocNum'];
                                    $existeFlujo->tipo = utf8_encode($lineas[$i]['TIPO']);
                                    $existeFlujo->tax_date = $lineas[$i]['TaxDate'];
                                    $existeFlujo->doc_date = $lineas[$i]['DocDate'];
                                    $existeFlujo->card_code = utf8_encode($lineas[$i]['CardCode']);
                                    $existeFlujo->card_name = utf8_encode($lineas[$i]['CardName']); */
                                    $existeFlujo->comments = utf8_encode($lineas[$i]['Comments']);
                                    /* $existeFlujo->doc_total = $lineas[$i]['DocTotal'];
                                    $existeFlujo->doc_curr = utf8_encode($lineas[$i]['DocCurr']); */
                                    $existeFlujo->bank_code = utf8_encode($lineas[$i]['BankCode']);
                                    $existeFlujo->dfl_account = utf8_encode($lineas[$i]['DflAccount']);
                                    $existeFlujo->tipo_cuenta_destino = utf8_encode($lineas[$i]['Tipo_Cuenta_Destino']);
                                    /* $existeFlujo->cuenta_orgien = utf8_encode($lineas[$i]['Cuenta_Origen']);
                                    $existeFlujo->empresa_codigo = $lineas[$i]['Empresa_codigo'];
                                    $existeFlujo->empresa_nombre = utf8_encode($lineas[$i]['Empresa_nombre']); */
                                    $existeFlujo->cheque = $lineas[$i]['Cheque'];
                                    /* $existeFlujo->en_favor_de = utf8_encode($lineas[$i]['EnFavorDe']);
                                    $existeFlujo->email = utf8_encode($lineas[$i]['E_Mail']);
                                    $existeFlujo->dias_credito = $lineas[$i]['Dias'];
                                    $existeFlujo->nombre_condicion_pago_dias = utf8_encode($lineas[$i]['NombreCondicionPagoDias']); */
                                    $existeFlujo->save();
    
                                    //Consulta de datos adicionales
                                    
                                    self::GetFlujoFacturaCantidad($existeFlujo->id_flujo,$lineas[$i]['DocNum']);
                                    self::GetFlujoFacturaDocumento($existeFlujo->id_flujo,$lineas[$i]['DocNum']);
                                    self::GetFlujoIngreso($existeFlujo->id_flujo,$lineas[$i]['DocNum']);
                                    self::GetFlujoOferta($existeFlujo->id_flujo,$lineas[$i]['DocNum']);
                                    self::GetFlujoOrden($existeFlujo->id_flujo,$lineas[$i]['DocNum']);
                                    self::GetFlujoSolicitud($existeFlujo->id_flujo,$lineas[$i]['DocNum']);  
                                    self::GetFlujoNumeroCheque($existeFlujo->id_flujo,$lineas[$i]['DocNum']);  
                                }
                            }
                        }
                    }
                }
                if($flujoOriginal->origen_datos == 'ITS'){
                    $datos = ZEMPRESA::join('BANCO MAESTRO as BM', function($join){
                        $join->on('EMPRESA.Codigo', '=', 'BM.EMPRESA');
                    })->join('MONEDA as M', function($join){
                        $join->on('BM.Moneda', '=', 'M.Codigo');
                    })
                    ->selectRaw(
                        "BM.comentario_aprobacion,
                        BM.estado_aprobacion,
                        EMPRESA.Nombre,
                        BM.Documento,
                        BM.Cuenta,
                        BM.Tipo,
                        BM.Fecha,
                        BM.Pagador,
                        BM.Concepto,
                        BM.Valor,
                        BM.Empresa,
                        BM.validacion_estado,
                        BM.validacion_usuario,
                        CASE
                            WHEN BM.Tipo = 2 THEN 'BANCARIO'
                            WHEN CHARINDEX('TRANSFERENCIA', LTRIM(BM.Concepto)) = 1 THEN 'TRANSFERENCIA'
                            ELSE 'INTERNA'
                        END as TipoD,
                        M.Simbolo
                        "
                    )
                    ->whereIn('BM.Tipo', [2,4])
                    ->where('BM.Documento', '=', $flujoOriginal->num_doc)
                    ->orderBy('BM.Fecha', 'ASC')  
                    ->get();
        
                    foreach($datos as $item)
                    {
                        $existeFlujo = Flujos::where('doc_num',$item->Documento)
                        ->where('activo','=',1)
                        ->where('eliminado','=',0)->first();
                        if($existeFlujo)
                        {
                            if($existeFlujo->dfl_account != utf8_encode($item->Cuenta)){
                                if($motivoCambio==''){
                                    $motivoCambio='Cuenta';
                                }else{
                                    $motivoCambio.=', cuenta';
                                }
                                $reiniciar = 1;
                            }
                            //$existeFlujo->doc_num = $item->Documento;
                            //$existeFlujo->tipo = utf8_encode($item->TipoD);
                            //$existeFlujo->tax_date = $item->TaxDate;
                            //$existeFlujo->doc_date = $item->Fecha;
                            //$existeFlujo->card_code = utf8_encode($item->CardCode);
                            //$existeFlujo->card_name = utf8_encode($item->CardName);
                            $existeFlujo->comments = utf8_encode($item->Concepto);
                            //$existeFlujo->doc_total = $item->Valor;
                            //$existeFlujo->doc_curr = utf8_encode($item->DocCurr);
                            ////$existeFlujo->bank_code = utf8_encode($item->BankCode);
                            $existeFlujo->dfl_account = utf8_encode($item->Cuenta);
                            ////$existeFlujo->tipo_cuenta_destino = utf8_encode($item->Tipo_Cuenta_Destino);
                            //$existeFlujo->cuenta_orgien = utf8_encode($item->Cuenta_Origen);
                            //$existeFlujo->empresa_codigo = $item->Empresa_codigo;
                            //$existeFlujo->empresa_nombre = utf8_encode($item->Empresa_nombre);
                            ////$existeFlujo->cheque = $item->Cheque;
                            //$existeFlujo->en_favor_de = utf8_encode($item->EnFavorDe);
                            //$existeFlujo->email = utf8_encode($item->E_Mail);
                            //$existeFlujo->dias_credito = $item->Dias;
                            //$existeFlujo->nombre_condicion_pago_dias = utf8_encode($item->NombreCondicionPagoDias);
                            //$existeFlujo->activo = 1;
                            //$existeFlujo->eliminado = 0;
                            //$existeFlujo->estado = 1;
                            //$existeFlujo->nivel = 0;
                            $existeFlujo->save();
                            
                        }
                    }
                }
            }
            if($reiniciar > 0){
                $flujoDetalle = new FlujoDetalle;
                $flujoDetalle->IdFlujo = $id_flujo;
                $flujoDetalle->IdEstadoFlujo = 3;
                $flujoDetalle->IdUsuario = $id_usuario;
                $flujoDetalle->Fecha = date("Y-m-d H:i",strtotime('-6 hour',strtotime(now())));
                $flujoDetalle->Comentario = "Reinicio de autorización por cambio en: ".$motivoCambio;
                $flujoDetalle->NivelAutorizo = 0;
                $flujoDetalle->save();
                
                FlujoDetalle::where('IdFlujo', $id_flujo)
                ->where('IdEstadoFlujo', '>',2)
                ->update([
                    'FlujoActivo' => 0
                ]);
                Flujos::where('id_flujo', $id_flujo)
                ->update([
                    'estado' => 3,
                    'nivel' => 0
                ]);
            }else{
                $Detalle = FlujoDetalle::where('IdFlujo', $id_flujo)
                ->where('IdEstadoFlujo','<',10)
                ->orderBy('Fecha','DESC')->first();
                Flujos::where('id_flujo', $id_flujo)
                ->update([
                    'estado' => $Detalle->IdEstadoFlujo
                ]);
            }
        }catch(Exception $e){
            Log::error($e->getMessage());
        }
    }

    function GetFlujoFacturaCantidad($id_flujo, $num_doc)
    {
        try{
            $client = new \nusoap_client('http://10.20.30.144/GSION_WS/WSGetFromSAP.asmx?wsdl',true);
            $param = array('iPagoEfectuadoNumero'=>$num_doc);
            $resultado = $client->call('Get_FACTURAS_POR_PAGO_XML',$param);
            if($client->fault){
                $error = $client->getError();;
                if($error){
                    echo 'Error:' . $client->faultstring;
                }
                die();
            }
            $lineas = $resultado['Get_FACTURAS_POR_PAGO_XMLResult']['BOM']['BO']['Recordset']['row'];
            if(count($lineas) == count($lineas, COUNT_RECURSIVE)){
                if($lineas['DocNum'] != 0){
                    $existeFlujo = FlujoFacturaCantidad::where('doc_num',$lineas['DocNum'])
                                                ->where('id_flujo', $id_flujo) 
                                                ->first();
                    if(!$existeFlujo)
                    {
                        $flujo = new FlujoFacturaCantidad;
                        $flujo->id_flujo = $id_flujo;
                        $flujo->doc_num = $lineas['DocNum'];
                        $flujo->cant_facturas = $lineas['CANT_FACTURAS'];
                        $flujo->save();
                    }
                    else
                    {
                        $existeFlujo->id_flujo = $id_flujo;
                        $existeFlujo->doc_num = $lineas['DocNum'];
                        $existeFlujo->cant_facturas = $lineas['CANT_FACTURAS'];
                        $existeFlujo->save();
                    }
                }
            }
            else
            {
                for($i=0; $i< count($lineas);$i++){
                    if($lineas[$i]['DocNum'] != 0){
                        $existeFlujo = FlujoFacturaCantidad::where('doc_num',$lineas[$i]['DocNum'])
                                                ->where('id_flujo', $id_flujo) 
                                                ->first();
                        if(!$existeFlujo)
                        {
                            $flujo = new FlujoFacturaCantidad;
                            $flujo->id_flujo = $id_flujo;
                            $flujo->doc_num = $lineas[$i]['DocNum'];
                            $flujo->cant_facturas = $lineas[$i]['CANT_FACTURAS'];
                            $flujo->save();
                        }
                        else
                        {
                            $existeFlujo->id_flujo = $id_flujo;
                            $existeFlujo->doc_num = $lineas[$i]['DocNum'];
                            $existeFlujo->cant_facturas = $lineas[$i]['CANT_FACTURAS'];
                            $existeFlujo->save();
                        }
                    }
                }
            }
            return true;
        }catch(Exception $e){
            return $e->getMessage();
        }
    }

    function GetFlujoFacturaDocumento($id_flujo, $num_doc)
    {
        try{
            $client = new \nusoap_client('http://10.20.30.144/GSION_WS/WSGetFromSAP.asmx?wsdl',true);
            $param = array('iPagoEfectuadoNumero'=>$num_doc);
            $resultado = $client->call('Get_FACTURA_XML',$param);
            if($client->fault){
                $error = $client->getError();;
                if($error){
                    echo 'Error:' . $client->faultstring;
                }
                die();
            }

            $lineas = $resultado['Get_FACTURA_XMLResult']['BOM']['BO']['Recordset']['row'];
            if(count($lineas) == count($lineas, COUNT_RECURSIVE)){
                if($lineas['srcPath'] != ""){
                    $existeFlujo = FlujoFacturaDocumento::where('src_path',$lineas['srcPath'])
                                            ->where('id_flujo', $id_flujo) 
                                            ->first();
                    if(!$existeFlujo)
                    {
                        $flujo = new FlujoFacturaDocumento;
                        $flujo->id_flujo = $id_flujo;
                        $flujo->src_path = utf8_encode($lineas['srcPath']);
                        $flujo->file_name = utf8_encode($lineas['FileName']);
                        $flujo->file_ext = utf8_encode($lineas['FileExt']);
                        $flujo->save();
                    }
                    else
                    {
                        $existeFlujo->id_flujo = $id_flujo;
                        $existeFlujo->src_path = utf8_encode($lineas['srcPath']);
                        $existeFlujo->file_name = utf8_encode($lineas['FileName']);
                        $existeFlujo->file_ext = utf8_encode($lineas['FileExt']);
                        $existeFlujo->save();
                    }
                }
            }
            else
            {
                for($i=0; $i< count($lineas);$i++){
                    if($lineas[$i]['srcPath'] != ""){
                        $existeFlujo = FlujoFacturaDocumento::where('src_path',$lineas[$i]['srcPath'])
                                                ->where('id_flujo', $id_flujo) 
                                                ->first();
                        if(!$existeFlujo)
                        {
                            $flujo = new FlujoFacturaDocumento;
                            $flujo->id_flujo = $id_flujo;
                            $flujo->src_path = utf8_encode($lineas[$i]['srcPath']);
                            $flujo->file_name = utf8_encode($lineas[$i]['FileName']);
                            $flujo->file_ext = utf8_encode($lineas[$i]['FileExt']);
                            $flujo->save();
                        }
                        else
                        {
                            $existeFlujo->id_flujo = $id_flujo;
                            $existeFlujo->src_path = utf8_encode($lineas[$i]['srcPath']);
                            $existeFlujo->file_name = utf8_encode($lineas[$i]['FileName']);
                            $existeFlujo->file_ext = utf8_encode($lineas[$i]['FileExt']);
                            $existeFlujo->save();
                        }
                    }
                }
            }
            return true;
        }catch(Exception $e){
            return $e->getMessage();
        }
    }

    function GetFlujoIngreso($id_flujo, $num_doc)
    {
        try{
            $client = new \nusoap_client('http://10.20.30.144/GSION_WS/WSGetFromSAP.asmx?wsdl',true);
            $param = array('iPagoEfectuadoNumero'=>$num_doc);
            $resultado = $client->call('Get_INGRESOCOMPRA_XML',$param);
            if($client->fault){
                $error = $client->getError();;
                if($error){
                    echo 'Error:' . $client->faultstring;
                }
                die();
            }
            $lineas = $resultado['Get_INGRESOCOMPRA_XMLResult']['BOM']['BO']['Recordset']['row'];
            if(count($lineas) == count($lineas, COUNT_RECURSIVE)){
                if($lineas['DocNum'] != 0){
                    $existeFlujo = FlujoIngreso::where('doc_num',$lineas['DocNum'])
                                            ->where('id_flujo', $id_flujo) 
                                            ->first();
                    if(!$existeFlujo)
                    {
                        $flujo = new FlujoIngreso;
                        $flujo->id_flujo = $id_flujo;
                        $flujo->doc_num = $lineas['DocNum'];
                        $flujo->tax_date = $lineas['TaxDate'];
                        $flujo->doc_date = $lineas['DocDate'];
                        $flujo->whs_name = utf8_encode($lineas['WhsName']);
                        $flujo->user = utf8_encode($lineas['User']);
                        $flujo->item_code = utf8_encode($lineas['ItemCode']);
                        $flujo->dscription = utf8_encode($lineas['Dscription']);
                        $flujo->uom_code = utf8_encode($lineas['UomCode']);
                        $flujo->quantity = $lineas['Quantity'];
                        $flujo->comments = utf8_encode($lineas['Comments']);
                        $flujo->save();
                    }
                    else
                    {
                        $existeFlujo->id_flujo = $id_flujo;
                        $existeFlujo->doc_num = $lineas['DocNum'];
                        $existeFlujo->tax_date = $lineas['TaxDate'];
                        $existeFlujo->doc_date = $lineas['DocDate'];
                        $existeFlujo->whs_name = utf8_encode($lineas['WhsName']);
                        $existeFlujo->user = utf8_encode($lineas['User']);
                        $existeFlujo->item_code = utf8_encode($lineas['ItemCode']);
                        $existeFlujo->dscription = utf8_encode($lineas['Dscription']);
                        $existeFlujo->uom_code = utf8_encode($lineas['UomCode']);
                        $existeFlujo->quantity = $lineas['Quantity'];
                        $existeFlujo->comments = utf8_encode($lineas['Comments']);
                        $existeFlujo->save();
                    }
                }
            }
            else
            {
                for($i=0; $i< count($lineas);$i++){
                    if($lineas[$i]['DocNum'] != 0){
                        $existeFlujo = FlujoIngreso::where('doc_num',$lineas[$i]['DocNum'])
                                                ->where('id_flujo', $id_flujo) 
                                                ->first();
                        if(!$existeFlujo)
                        {
                            $flujo = new FlujoIngreso;
                            $flujo->id_flujo = $id_flujo;
                            $flujo->doc_num = $lineas[$i]['DocNum'];
                            $flujo->tax_date = $lineas[$i]['TaxDate'];
                            $flujo->doc_date = $lineas[$i]['DocDate'];
                            $flujo->whs_name = utf8_encode($lineas[$i]['WhsName']);
                            $flujo->user = utf8_encode($lineas[$i]['User']);
                            $flujo->item_code = utf8_encode($lineas[$i]['ItemCode']);
                            $flujo->dscription = utf8_encode($lineas[$i]['Dscription']);
                            $flujo->uom_code = utf8_encode($lineas[$i]['UomCode']);
                            $flujo->quantity = $lineas[$i]['Quantity'];
                            $flujo->comments = utf8_encode($lineas[$i]['Comments']);
                            $flujo->save();
                        }
                        else
                        {
                            $existeFlujo->id_flujo = $id_flujo;
                            $existeFlujo->doc_num = $lineas[$i]['DocNum'];
                            $existeFlujo->tax_date = $lineas[$i]['TaxDate'];
                            $existeFlujo->doc_date = $lineas[$i]['DocDate'];
                            $existeFlujo->whs_name = utf8_encode($lineas[$i]['WhsName']);
                            $existeFlujo->user = utf8_encode($lineas[$i]['User']);
                            $existeFlujo->item_code = utf8_encode($lineas[$i]['ItemCode']);
                            $existeFlujo->dscription = utf8_encode($lineas[$i]['Dscription']);
                            $existeFlujo->uom_code = utf8_encode($lineas[$i]['UomCode']);
                            $existeFlujo->quantity = $lineas[$i]['Quantity'];
                            $existeFlujo->comments = utf8_encode($lineas[$i]['Comments']);
                            $existeFlujo->save();
                        }
                    }
                }
            }
            return true;
        }catch(Exception $e){
            return $e->getMessage();
        }
    }

    function GetFlujoOferta($id_flujo, $num_doc)
    {
        try{
            $client = new \nusoap_client('http://10.20.30.144/GSION_WS/WSGetFromSAP.asmx?wsdl',true);
            $param = array('iPagoEfectuadoNumero'=>$num_doc);
            $resultado = $client->call('Get_COTIZACION_XML',$param);
            if($client->fault){
                $error = $client->getError();;
                if($error){
                    echo 'Error:' . $client->faultstring;
                }
                die();
            }
            $lineas = $resultado['Get_COTIZACION_XMLResult']['BOM']['BO']['Recordset']['row'];
            if(count($lineas) == count($lineas, COUNT_RECURSIVE)){
                if($lineas['DocNum'] != 0){
                    $existeFlujo = FlujoOferta::where('doc_num',$lineas['DocNum'])
                                            ->where('id_flujo', $id_flujo) 
                                            ->first();
                    if(!$existeFlujo)
                    {
                        $flujo = new FlujoOferta;
                        $flujo->id_flujo = $id_flujo;
                        $flujo->doc_num = $lineas['DocNum'];
                        $flujo->doc_date = $lineas['DocDate'];
                        $flujo->card_code = utf8_encode($lineas['CardCode']);
                        $flujo->card_name = utf8_encode($lineas['CardName']);
                        $flujo->item_code = utf8_encode($lineas['ItemCode']);
                        $flujo->dscription = utf8_encode($lineas['Dscription']);
                        $flujo->uom_code = utf8_encode($lineas['UomCode']);
                        $flujo->price = $lineas['Price'];
                        $flujo->quantity = $lineas['Quantity'];
                        $flujo->save();
                    }
                    else
                    {
                        $existeFlujo->id_flujo = $id_flujo;
                        $existeFlujo->doc_num = $lineas['DocNum'];
                        $existeFlujo->doc_date = $lineas['DocDate'];
                        $existeFlujo->card_code = utf8_encode($lineas['CardCode']);
                        $existeFlujo->card_name = utf8_encode($lineas['CardName']);
                        $existeFlujo->item_code = utf8_encode($lineas['ItemCode']);
                        $existeFlujo->dscription = utf8_encode($lineas['Dscription']);
                        $existeFlujo->uom_code = utf8_encode($lineas['UomCode']);
                        $existeFlujo->price = $lineas['Price'];
                        $existeFlujo->quantity = $lineas['Quantity'];
                        $existeFlujo->save();
                    }
                }
            }
            else
            {
                for($i=0; $i< count($lineas);$i++){
                    if($lineas[$i]['DocNum'] != 0){
                        $existeFlujo = FlujoOferta::where('doc_num',$lineas[$i]['DocNum'])
                                                ->where('id_flujo', $id_flujo) 
                                                ->first();
                        if(!$existeFlujo)
                        {
                            $flujo = new FlujoOferta;
                            $flujo->id_flujo = $id_flujo;
                            $flujo->doc_num = $lineas[$i]['DocNum'];
                            $flujo->doc_date = $lineas[$i]['DocDate'];
                            $flujo->card_code = utf8_encode($lineas[$i]['CardCode']);
                            $flujo->card_name = utf8_encode($lineas[$i]['CardName']);
                            $flujo->item_code = utf8_encode($lineas[$i]['ItemCode']);
                            $flujo->dscription = utf8_encode($lineas[$i]['Dscription']);
                            $flujo->uom_code = utf8_encode($lineas[$i]['UomCode']);
                            $flujo->price = $lineas[$i]['Price'];
                            $flujo->quantity = $lineas[$i]['Quantity'];
                            $flujo->save();
                        }
                        else
                        {
                            $existeFlujo->id_flujo = $id_flujo;
                            $existeFlujo->doc_num = $lineas[$i]['DocNum'];
                            $existeFlujo->doc_date = $lineas[$i]['DocDate'];
                            $existeFlujo->card_code = utf8_encode($lineas[$i]['CardCode']);
                            $existeFlujo->card_name = utf8_encode($lineas[$i]['CardName']);
                            $existeFlujo->item_code = utf8_encode($lineas[$i]['ItemCode']);
                            $existeFlujo->dscription = utf8_encode($lineas[$i]['Dscription']);
                            $existeFlujo->uom_code = utf8_encode($lineas[$i]['UomCode']);
                            $existeFlujo->price = $lineas[$i]['Price'];
                            $existeFlujo->quantity = $lineas[$i]['Quantity'];
                            $existeFlujo->save();
                        }
                    }
                }
            }
            return true;
        } catch(\Illuminate\Database\QueryException $ex){ 
            echo $ex->getMessage();
            return 0; 
            // Note any method of class PDOException can be called on $ex.
        }//catch(Exception $e){
           // return $e->getMessage();
        //}
    }

    function GetFlujoOrden($id_flujo, $num_doc)
    {
        try{
            $client = new \nusoap_client('http://10.20.30.144/GSION_WS/WSGetFromSAP.asmx?wsdl',true);
            $param = array('iPagoEfectuadoNumero'=>$num_doc);
            $resultado = $client->call('Get_ORDENCOMPRA_XML',$param);
            if($client->fault){
                $error = $client->getError();;
                if($error){
                    echo 'Error:' . $client->faultstring;
                }
                die();
            }
            $lineas = $resultado['Get_ORDENCOMPRA_XMLResult']['BOM']['BO']['Recordset']['row'];

            if(count($lineas) == count($lineas, COUNT_RECURSIVE)){
                if($lineas['DocNum'] != 0){
                    $existeFlujo = FlujoOrden::where('docu_num',$lineas['DocNum'])
                                                ->where('id_flujo', $id_flujo) 
                                                ->first();
                    if(!$existeFlujo)
                    {
                        $flujo = new FlujoOrden;
                        $flujo->id_flujo = $id_flujo;
                        $flujo->docu_num = $lineas['DocNum'];
                        $flujo->tax_date = $lineas['TaxDate'];
                        $flujo->doc_date = $lineas['DocDate'];
                        $flujo->card_code = utf8_encode($lineas['CardCode']);
                        $flujo->card_name = utf8_encode($lineas['CardName']);
                        $flujo->fac_nit = utf8_encode($lineas['U_FacNit']);
                        $flujo->phone1 = utf8_encode($lineas['Phone1']);
                        $flujo->termino_pago = utf8_encode($lineas['Termino_Pago']);
                        $flujo->address = utf8_encode($lineas['Address']);
                        $flujo->user = utf8_encode($lineas['User']);
                        $flujo->item_code = utf8_encode($lineas['ItemCode']);
                        $flujo->price = $lineas['Price'];
                        $flujo->quantity = $lineas['Quantity'];
                        $flujo->line_total = $lineas['LineTotal'];
                        $flujo->doc_total = $lineas['DocTotal'];
                        $flujo->comment = utf8_encode($lineas['Comments']);
                        $flujo->crea_usuario = utf8_encode($lineas['Crea_Usuario']);
                        if(!empty($lineas['Crea_Fecha']))
                        {
                            $flujo->crea_fecha = date('Y-m-d', strtotime($lineas['Crea_Fecha']));
                        }
                        $flujo->autoriza_usuario = utf8_encode($lineas['Autoriza_Usuario']);
                        if(!empty($lineas['Autoriza_Fecha']))
                        {
                            $flujo->autoriza_fecha = date('Y-m-d', strtotime($lineas['Autoriza_Fecha']));
                        }
                        $flujo->dscription = utf8_encode($lineas['Dscription']);
                        $flujo->uom_code = utf8_encode($lineas['UomCode']);
                        $flujo->save();
                    }
                    else
                    {
                        $existeFlujo->id_flujo = $id_flujo;
                        $existeFlujo->docu_num = $lineas['DocNum'];
                        $existeFlujo->tax_date = $lineas['TaxDate'];
                        $existeFlujo->doc_date = $lineas['DocDate'];
                        $existeFlujo->card_code = utf8_encode($lineas['CardCode']);
                        $existeFlujo->card_name = utf8_encode($lineas['CardName']);
                        $existeFlujo->fac_nit = utf8_encode($lineas['U_FacNit']);
                        $existeFlujo->phone1 = utf8_encode($lineas['Phone1']);
                        $existeFlujo->termino_pago = utf8_encode($lineas['Termino_Pago']);
                        $existeFlujo->address = utf8_encode($lineas['Address']);
                        $existeFlujo->user = utf8_encode($lineas['User']);
                        $existeFlujo->item_code = utf8_encode($lineas['ItemCode']);
                        $existeFlujo->price = $lineas['Price'];
                        $existeFlujo->quantity = $lineas['Quantity'];
                        $existeFlujo->line_total = $lineas['LineTotal'];
                        $existeFlujo->doc_total = $lineas['DocTotal'];
                        $existeFlujo->comment = utf8_encode($lineas['Comments']);
                        $existeFlujo->crea_usuario = utf8_encode($lineas['Crea_Usuario']);
                        if(!empty($lineas['Crea_Fecha']))
                        {
                            $existeFlujo->crea_fecha = date('Y-m-d', strtotime($lineas['Crea_Fecha']));
                        }
                        $existeFlujo->autoriza_usuario = utf8_encode($lineas['Autoriza_Usuario']);
                        if(!empty($lineas['Autoriza_Fecha']))
                        {
                            $existeFlujo->autoriza_fecha = date('Y-m-d', strtotime($lineas['Autoriza_Fecha']));
                        }
                        $existeFlujo->dscription = utf8_encode($lineas['Dscription']);
                        $existeFlujo->uom_code = utf8_encode($lineas['UomCode']);
                        
                        $existeFlujo->save();
                    }
                }
            }
            else
            {
                for($i=0; $i< count($lineas);$i++){
                    if($lineas[$i]['DocNum'] != 0){
                        $existeFlujo = FlujoOrden::where('docu_num',$lineas[$i]['DocNum'])
                                                ->where('id_flujo', $id_flujo) 
                                                ->first();
                        if(!$existeFlujo)
                        {
                            $flujo = new FlujoOrden;
                            $flujo->id_flujo = $id_flujo;
                            $flujo->docu_num = $lineas[$i]['DocNum'];
                            $flujo->tax_date = $lineas[$i]['TaxDate'];
                            $flujo->doc_date = $lineas[$i]['DocDate'];
                            $flujo->card_code = utf8_encode($lineas[$i]['CardCode']);
                            $flujo->card_name = utf8_encode($lineas[$i]['CardName']);
                            $flujo->fac_nit = utf8_encode($lineas[$i]['U_FacNit']);
                            $flujo->phone1 = utf8_encode($lineas[$i]['Phone1']);
                            $flujo->termino_pago = utf8_encode($lineas[$i]['Termino_Pago']);
                            $flujo->address = utf8_encode($lineas[$i]['Address']);
                            $flujo->user = utf8_encode($lineas[$i]['User']);
                            $flujo->item_code = utf8_encode($lineas[$i]['ItemCode']);
                            $flujo->price = $lineas[$i]['Price'];
                            $flujo->quantity = $lineas[$i]['Quantity'];
                            $flujo->line_total = $lineas[$i]['LineTotal'];
                            $flujo->doc_total = $lineas[$i]['DocTotal'];
                            $flujo->comment = utf8_encode($lineas[$i]['Comments']);
                            $flujo->crea_usuario = utf8_encode($lineas[$i]['Crea_Usuario']);
                            if(!empty($lineas[$i]['Crea_Fecha']))
                            {
                                $flujo->crea_fecha = date('Y-m-d', strtotime($lineas[$i]['Crea_Fecha']));
                            }
                            $flujo->autoriza_usuario = utf8_encode($lineas[$i]['Autoriza_Usuario']);
                            if(!empty($lineas[$i]['Autoriza_Fecha']))
                            {
                                $flujo->autoriza_fecha = date('Y-m-d', strtotime($lineas[$i]['Autoriza_Fecha']));
                            }
                            $flujo->dscription = utf8_encode($lineas[$i]['Dscription']);
                            $flujo->uom_code = utf8_encode($lineas[$i]['UomCode']);
                            $flujo->save();
                        }
                        else
                        {
                            $existeFlujo->id_flujo = $id_flujo;
                            $existeFlujo->docu_num = $lineas[$i]['DocNum'];
                            $existeFlujo->tax_date = $lineas[$i]['TaxDate'];
                            $existeFlujo->doc_date = $lineas[$i]['DocDate'];
                            $existeFlujo->card_code = utf8_encode($lineas[$i]['CardCode']);
                            $existeFlujo->card_name = utf8_encode($lineas[$i]['CardName']);
                            $existeFlujo->fac_nit = utf8_encode($lineas[$i]['U_FacNit']);
                            $existeFlujo->phone1 = utf8_encode($lineas[$i]['Phone1']);
                            $existeFlujo->termino_pago = utf8_encode($lineas[$i]['Termino_Pago']);
                            $existeFlujo->address = utf8_encode($lineas[$i]['Address']);
                            $existeFlujo->user = utf8_encode($lineas[$i]['User']);
                            $existeFlujo->item_code = utf8_encode($lineas[$i]['ItemCode']);
                            $existeFlujo->price = $lineas[$i]['Price'];
                            $existeFlujo->quantity = $lineas[$i]['Quantity'];
                            $existeFlujo->line_total = $lineas[$i]['LineTotal'];
                            $existeFlujo->doc_total = $lineas[$i]['DocTotal'];
                            $existeFlujo->comment = utf8_encode($lineas[$i]['Comments']);
                            $existeFlujo->crea_usuario = utf8_encode($lineas[$i]['Crea_Usuario']);
                            if(!empty($lineas[$i]['Crea_Fecha']))
                            {
                                $existeFlujo->crea_fecha = date('Y-m-d', strtotime($lineas[$i]['Crea_Fecha']));
                            }
                            $existeFlujo->autoriza_usuario = utf8_encode($lineas[$i]['Autoriza_Usuario']);
                            if(!empty($lineas[$i]['Autoriza_Fecha']))
                            {
                                $existeFlujo->autoriza_fecha = date('Y-m-d', strtotime($lineas[$i]['Autoriza_Fecha']));
                            }
                            $existeFlujo->dscription = utf8_encode($lineas[$i]['Dscription']);
                            $existeFlujo->uom_code = utf8_encode($lineas[$i]['UomCode']);
                            
                            $existeFlujo->save();
                            
                        }
                    }
                }
            }
            return true;
        } catch(\Illuminate\Database\QueryException $ex){ 
            echo $ex->getMessage();
            return 0; 
            // Note any method of class PDOException can be called on $ex.
        }//catch(Exception $e){
           // return $e->getMessage();
        //}
    }

    function GetFlujoSolicitud($id_flujo, $num_doc)
    {
        try{
            $client = new \nusoap_client('http://10.20.30.144/GSION_WS/WSGetFromSAP.asmx?wsdl',true);
            $param = array('iPagoEfectuadoNumero'=>$num_doc);
            $resultado = $client->call('Get_SOLICITUDCOMPRA_XML',$param);
            if($client->fault){
                $error = $client->getError();;
                if($error){
                    echo 'Error:' . $client->faultstring;
                }
                die();
            }
            $lineas = $resultado['Get_SOLICITUDCOMPRA_XMLResult']['BOM']['BO']['Recordset']['row'];
            if(count($lineas) == count($lineas, COUNT_RECURSIVE)){
                if($lineas['DocNum'] != 0){
                    $existeFlujo = FlujoSolicitud::where('doc_num',$lineas['DocNum'])
                                                ->where('id_flujo', $id_flujo) 
                                                ->first();
                    if(!$existeFlujo)
                    {
                        $flujo = new FlujoSolicitud;
                        $flujo->id_flujo = $id_flujo;
                        $flujo->doc_num = $lineas['DocNum'];
                        $flujo->req_name = utf8_encode($lineas['ReqName']);
                        $flujo->doc_date = $lineas['DocDate'];
                        $flujo->item_code = utf8_encode($lineas['ItemCode']);
                        $flujo->dscription = utf8_encode($lineas['Dscription']);
                        $flujo->uom_code = utf8_encode($lineas['UomCode']);
                        $flujo->price = $lineas['Price'];
                        $flujo->quantity = $lineas['Quantity'];
                        $flujo->unidades_totales = $lineas['Unidades_Totales'];
                        $flujo->unidades_por_caja = $lineas['Unidades_X_Caja'];
                        $flujo->comments = utf8_encode($lineas['Comments']);
                        $flujo->autorizador1 = utf8_encode($lineas['U_AUTORIZADOR1']);
                        $flujo->autorizador2 = utf8_encode($lineas['U_AUTORIZADOR2']);
                        $flujo->autorizador3 = utf8_encode($lineas['U_AUTORIZADOR3']);

                        if(!empty($lineas['U_FECHA_AUT1']))
                        {
                            $flujo->fecha_aut1 = date('Y-m-d H:i:s', strtotime($lineas['U_FECHA_AUT1']));
                        }

                        if(!empty($lineas['U_FECHA_AUT2']))
                        {
                            $flujo->fecha_aut2 = date('Y-m-d H:i:s', strtotime($lineas['U_FECHA_AUT2']));
                        }

                        if(!empty($lineas['U_FECHA_AUT3']))
                        {
                            $flujo->fecha_aut3 = date('Y-m-d H:i:s', strtotime($lineas['U_FECHA_AUT3']));
                        }
                        $flujo->save();
                    }
                    else
                    {
                        $existeFlujo->id_flujo = $id_flujo;
                        $existeFlujo->doc_num = $lineas['DocNum'];
                        $existeFlujo->req_name = utf8_encode($lineas['ReqName']);
                        $existeFlujo->doc_date = $lineas['DocDate'];
                        $existeFlujo->item_code = utf8_encode($lineas['ItemCode']);
                        $existeFlujo->dscription = utf8_encode($lineas['Dscription']);
                        $existeFlujo->uom_code = utf8_encode($lineas['UomCode']);
                        $existeFlujo->price = $lineas['Price'];
                        $existeFlujo->quantity = $lineas['Quantity'];
                        $existeFlujo->unidades_totales = $lineas['Unidades_Totales'];
                        $existeFlujo->unidades_por_caja = $lineas['Unidades_X_Caja'];
                        $existeFlujo->comments = utf8_encode($lineas['Comments']);
                        $existeFlujo->autorizador1 = utf8_encode($lineas['U_AUTORIZADOR1']);
                        $existeFlujo->autorizador2 = utf8_encode($lineas['U_AUTORIZADOR2']);
                        $existeFlujo->autorizador3 = utf8_encode($lineas['U_AUTORIZADOR3']);
                        if(!empty($lineas['U_FECHA_AUT1']))
                        {
                            $existeFlujo->fecha_aut1 = date('Y-m-d H:i:s', strtotime($lineas['U_FECHA_AUT1']));
                        }

                        if(!empty($lineas['U_FECHA_AUT2']))
                        {
                            $existeFlujo->fecha_aut2 = date('Y-m-d H:i:s', strtotime($lineas['U_FECHA_AUT2']));
                        }

                        if(!empty($lineas['U_FECHA_AUT3']))
                        {
                            $existeFlujo->fecha_aut3 = date('Y-m-d H:i:s', strtotime($lineas['U_FECHA_AUT3']));
                        }
                        $existeFlujo->save();
                    }
                }
            }
            else
            {
                for($i=0; $i< count($lineas);$i++){
                    if($lineas[$i]['DocNum'] != 0){
                        $existeFlujo = FlujoSolicitud::where('doc_num',$lineas[$i]['DocNum'])
                                                ->where('id_flujo', $id_flujo) 
                                                ->first();
                        if(!$existeFlujo)
                        {
                            $flujo = new FlujoSolicitud;
                            $flujo->id_flujo = $id_flujo;
                            $flujo->doc_num = $lineas[$i]['DocNum'];
                            $flujo->req_name = utf8_encode($lineas[$i]['ReqName']);
                            $flujo->doc_date = $lineas[$i]['DocDate'];
                            $flujo->item_code = utf8_encode($lineas[$i]['ItemCode']);
                            $flujo->dscription = utf8_encode($lineas[$i]['Dscription']);
                            $flujo->uom_code = utf8_encode($lineas[$i]['UomCode']);
                            $flujo->price = $lineas[$i]['Price'];
                            $flujo->quantity = $lineas[$i]['Quantity'];
                            $flujo->unidades_totales = $lineas[$i]['Unidades_Totales'];
                            $flujo->unidades_por_caja = $lineas[$i]['Unidades_X_Caja'];
                            $flujo->comments = utf8_encode($lineas[$i]['Comments']);
                            $flujo->autorizador1 = utf8_encode($lineas[$i]['U_AUTORIZADOR1']);
                            $flujo->autorizador2 = utf8_encode($lineas[$i]['U_AUTORIZADOR2']);
                            $flujo->autorizador3 = utf8_encode($lineas[$i]['U_AUTORIZADOR3']);

                            if(!empty($lineas[$i]['U_FECHA_AUT1']))
                            {
                                $flujo->fecha_aut1 = date('Y-m-d H:i:s', strtotime($lineas[$i]['U_FECHA_AUT1']));
                            }

                            if(!empty($lineas[$i]['U_FECHA_AUT2']))
                            {
                                $flujo->fecha_aut2 = date('Y-m-d H:i:s', strtotime($lineas[$i]['U_FECHA_AUT2']));
                            }

                            if(!empty($lineas[$i]['U_FECHA_AUT3']))
                            {
                                $flujo->fecha_aut3 = date('Y-m-d H:i:s', strtotime($lineas[$i]['U_FECHA_AUT3']));
                            }
                            $flujo->save();
                        }
                        else
                        {
                            $existeFlujo->id_flujo = $id_flujo;
                            $existeFlujo->doc_num = $lineas[$i]['DocNum'];
                            $existeFlujo->req_name = utf8_encode($lineas[$i]['ReqName']);
                            $existeFlujo->doc_date = $lineas[$i]['DocDate'];
                            $existeFlujo->item_code = utf8_encode($lineas[$i]['ItemCode']);
                            $existeFlujo->dscription = utf8_encode($lineas[$i]['Dscription']);
                            $existeFlujo->uom_code = utf8_encode($lineas[$i]['UomCode']);
                            $existeFlujo->price = $lineas[$i]['Price'];
                            $existeFlujo->quantity = $lineas[$i]['Quantity'];
                            $existeFlujo->unidades_totales = $lineas[$i]['Unidades_Totales'];
                            $existeFlujo->unidades_por_caja = $lineas[$i]['Unidades_X_Caja'];
                            $existeFlujo->comments = utf8_encode($lineas[$i]['Comments']);
                            $existeFlujo->autorizador1 = utf8_encode($lineas[$i]['U_AUTORIZADOR1']);
                            $existeFlujo->autorizador2 = utf8_encode($lineas[$i]['U_AUTORIZADOR2']);
                            $existeFlujo->autorizador3 = utf8_encode($lineas[$i]['U_AUTORIZADOR3']);
                            if(!empty($lineas[$i]['U_FECHA_AUT1']))
                            {
                                $existeFlujo->fecha_aut1 = date('Y-m-d H:i:s', strtotime($lineas[$i]['U_FECHA_AUT1']));
                            }
    
                            if(!empty($lineas[$i]['U_FECHA_AUT2']))
                            {
                                $existeFlujo->fecha_aut2 = date('Y-m-d H:i:s', strtotime($lineas[$i]['U_FECHA_AUT2']));
                            }
    
                            if(!empty($lineas[$i]['U_FECHA_AUT3']))
                            {
                                $existeFlujo->fecha_aut3 = date('Y-m-d H:i:s', strtotime($lineas[$i]['U_FECHA_AUT3']));
                            }
                            $existeFlujo->save();
                        }
                    }
                }
            }
            return true;
        }catch(Exception $e){
            return $e->getMessage();
        }
    }

    function GetFlujoNumeroCheque($id_flujo, $num_doc)
    {
        try{
            $client = new \nusoap_client('http://10.20.30.144/GSION_WS/WSGetFromSAP.asmx?wsdl',true);
            $param = array('iPagoEfectuadoNumero'=>$num_doc);
            $resultado = $client->call('Get_NUMERO_CHEQUE_XML',$param);
            if($client->fault){
                $error = $client->getError();;
                if($error){
                    echo 'Error:' . $client->faultstring;
                }
                die();
            }

            $lineas = $resultado['Get_NUMERO_CHEQUE_XMLResult']['BOM']['BO']['Recordset']['row'];
            if(count($lineas) == count($lineas, COUNT_RECURSIVE)){
                if($lineas['Numero_Cheque'] != ""){
                    $existeFlujo = FlujoNumeroCheque::where('Numero_Cheque',$lineas['Numero_Cheque'])
                                            ->where('id_flujo', $id_flujo) 
                                            ->first();
                    if(!$existeFlujo)
                    {
                        $flujo = new FlujoNumeroCheque;
                        $flujo->id_flujo = $id_flujo;
                        $flujo->Numero_Cheque = utf8_encode($lineas['Numero_Cheque']);
                        $flujo->save();
                    }
                }
            }
            else
            {
                for($i=0; $i< count($lineas);$i++){
                    if($lineas[$i]['Numero_Cheque'] != ""){
                        $existeFlujo = FlujoNumeroCheque::where('Numero_Cheque',$lineas[$i]['Numero_Cheque'])
                                                ->where('id_flujo', $id_flujo) 
                                                ->first();
                        if(!$existeFlujo)
                        {
                            $flujo = new FlujoNumeroCheque;
                            $flujo->id_flujo = $id_flujo;
                            $flujo->Numero_Cheque = utf8_encode($lineas[$i]['Numero_Cheque']);
                            $flujo->save();
                        }
                    }
                }
            }
            return true;
        }catch(Exception $e){
            return $e->getMessage();
        }
    }

    function cargaits($num_doc)
    {
        try{
            $ItemPolitica = Politicas::where('identificador','=','_DIAS_BASE_CREDITO_')
            ->where('activo',1)->where('eliminado',0)->first();
            $valorDiasCreditoBase = intval($ItemPolitica->valor);
            $datos = ZEMPRESA::join('BANCO MAESTRO as BM', function($join){
                $join->on('EMPRESA.Codigo', '=', 'BM.EMPRESA');
            })->join('MONEDA as M', function($join){
                $join->on('BM.Moneda', '=', 'M.Codigo');
            })
            ->selectRaw(
                "BM.comentario_aprobacion,
                BM.estado_aprobacion,
                EMPRESA.Nombre,
                BM.Documento,
                BM.Cuenta,
                BM.Tipo,
                BM.Fecha,
                BM.Pagador,
                BM.Concepto,
                BM.Valor,
                BM.Empresa,
                BM.validacion_estado,
                BM.validacion_usuario,
                CASE
                    WHEN BM.Tipo = 2 THEN 'BANCARIO'
                    WHEN CHARINDEX('TRANSFERENCIA', LTRIM(BM.Concepto)) = 1 THEN 'TRANSFERENCIA'
                    ELSE 'INTERNA'
                END as TipoD,
                M.Simbolo
                "
            )
            ->whereIn('BM.Tipo', [2,4])
            ->where('BM.Documento', '=', $num_doc)
            ->orderBy('BM.Fecha', 'ASC')  
            ->get();

            foreach($datos as $item)
            {
                $existeFlujo = Flujos::where('doc_num',$item->Documento)
                ->where('activo','=',1)
                ->where('eliminado','=',0)->first();
                if(!$existeFlujo)
                {
                    $flujo = new Flujos;
                    $flujo->id_tipoflujo = 1;
                    $flujo->doc_num = $item->Documento;
                    $flujo->tipo = utf8_encode($item->TipoD);
                    //$flujo->tax_date = $item->TaxDate;
                    $flujo->doc_date = $item->Fecha;
                    //$flujo->card_code = utf8_encode($item->CardCode);
                    //$flujo->card_name = utf8_encode($item->CardName);
                    $flujo->comments = utf8_encode($item->Concepto);
                    $flujo->doc_total = $item->Valor;
                    if($item->Simbolo == 'Q'){
                        $flujo->doc_curr = 'QTZ';
                    }else{
                        $flujo->doc_curr = utf8_encode($item->Simbolo);
                    }
                    //$flujo->bank_code = utf8_encode($item->BankCode);
                    //$flujo->dfl_account = utf8_encode($item->Cuenta);
                    //$flujo->tipo_cuenta_destino = utf8_encode($item->Tipo_Cuenta_Destino);
                    $flujo->cuenta_orgien = utf8_encode($item->Cuenta);
                    $flujo->empresa_codigo = $item->Empresa;
                    $flujo->empresa_nombre = utf8_encode($item->Nombre);
                    $flujo->cheque = $item->Documento;
                    $flujo->en_favor_de = utf8_encode($item->Pagador);
                    //$flujo->email = utf8_encode($item->E_Mail);
                    //$flujo->dias_credito = $item->Dias;
                    //$flujo->nombre_condicion_pago_dias = utf8_encode($item->NombreCondicionPagoDias);
                    $flujo->activo = 1;
                    $flujo->eliminado = 0;
                    $flujo->estado = 1;
                    $flujo->nivel = 0;
                    $flujo->origen_datos = 'ITS';
                    $flujo->save();

                    $flujoDetalle = new FlujoDetalle;
                    $flujoDetalle->IdFlujo = $flujo->id_flujo;
                    $flujoDetalle->IdEstadoFlujo = 1;
                    $flujoDetalle->IdUsuario = 11;
                    $flujoDetalle->Fecha = date("Y-m-d H:i",strtotime('-6 hour',strtotime(now())));
                    $flujoDetalle->Comentario = 'Cargado desde sistema origen';
                    $flujoDetalle->NivelAutorizo = 0;
                    $flujoDetalle->save();

                    $valorDeFlujo = intval($flujo->dias_credito);
                    if($valorDeFlujo == 0){
                        $flujo->dias_credito = $valorDiasCreditoBase;
                        $flujo->save();

                        $flujoCambioDias = new FlujoCambioDias;
                        $flujoCambioDias->id_flujo = $flujo->id_flujo;
                        $flujoCambioDias->activo = 1;
                        $flujoCambioDias->eliminado = 0;
                        $flujoCambioDias->save();
                    }
                    
                }
                else
                {
                    //$existeFlujo->doc_num = $item->Documento;
                    //$existeFlujo->tipo = utf8_encode($item->TipoD);
                    //$existeFlujo->tax_date = $item->TaxDate;
                    //$existeFlujo->doc_date = $item->Fecha;
                    //$existeFlujo->card_code = utf8_encode($item->CardCode);
                    //$existeFlujo->card_name = utf8_encode($item->CardName);
                    $existeFlujo->comments = utf8_encode($item->Concepto);
                    //$existeFlujo->doc_total = $item->Valor;
                    //$existeFlujo->doc_curr = utf8_encode($item->DocCurr);
                    ////$existeFlujo->bank_code = utf8_encode($item->BankCode);
                    $existeFlujo->dfl_account = utf8_encode($item->Cuenta);
                    ////$existeFlujo->tipo_cuenta_destino = utf8_encode($item->Tipo_Cuenta_Destino);
                    //$existeFlujo->cuenta_orgien = utf8_encode($item->Cuenta_Origen);
                    //$existeFlujo->empresa_codigo = $item->Empresa_codigo;
                    //$existeFlujo->empresa_nombre = utf8_encode($item->Empresa_nombre);
                    ////$existeFlujo->cheque = $item->Cheque;
                    //$existeFlujo->en_favor_de = utf8_encode($item->EnFavorDe);
                    //$existeFlujo->email = utf8_encode($item->E_Mail);
                    //$existeFlujo->dias_credito = $item->Dias;
                    //$existeFlujo->nombre_condicion_pago_dias = utf8_encode($item->NombreCondicionPagoDias);
                    //$existeFlujo->activo = 1;
                    //$existeFlujo->eliminado = 0;
                    //$existeFlujo->estado = 1;
                    //$existeFlujo->nivel = 0;
                    $existeFlujo->save();
                    
                }
            }
            return true;
        }catch(Exception $e){
            return $e->getMessage();
        }
    }
}
