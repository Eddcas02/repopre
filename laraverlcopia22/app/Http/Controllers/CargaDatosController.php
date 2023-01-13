<?php

namespace App\Http\Controllers;

use App\Models\Flujos;
use App\Models\FlujoDetalle;
use App\Models\FlujoFacturaCantidad;
use App\Models\FlujoFacturaDocumento;
use App\Models\FlujoIngreso;
use App\Models\FlujoOferta;
use App\Models\FlujoOrden;
use App\Models\FlujoSolicitud;
use App\Models\CuentaGrupoAutorizacion;
use App\Models\ReferenciaGrupoAutorizacion;
use App\Models\FlujoGrupo;
use App\Models\ZBancoMaestro;
use App\Models\ZEmpresa;
use App\Models\FlujoNumeroCheque;
use App\Models\SugerenciaAsignacionGrupo;
use App\Models\FlujoCambioDias;
use App\Models\Politicas;

class CargaDatosController extends Controller
{
    public function index()
    {
        try
        {
            ini_set('memory_limit', '1024M');
            /* $listadoCuentas = array('11251002','11251003','11253005','11253002','11254003','11254009','11299002','11299003','21203001','21203004',
            '21203005','21205001','21205002','21205003','21205004','21205005','21205006','21205007','21205008','21205012','21205013','21205014',
            '21205015','21205016','21301001','21301002','21301003','21301004','21301005','21301006','21301010','21302001','21302002','21302003',
            '21302005','21302006','21302007','21302020','21501001','21501002','21501003','22101003'); */
            $ItemPolitica = Politicas::where('identificador','=','_DIAS_BASE_CREDITO_')
            ->where('activo',1)->where('eliminado',0)->first();
            $valorDiasCreditoBase = intval($ItemPolitica->valor);
            $fecha_fin = date('Y-m-d');
            $fecha_inicio = date('Y-m-d', strtotime("-1 days"));
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
                if($lineas['DocNum'] != 0){
                    $existeFlujo = Flujos::where('doc_num',$lineas['DocNum'])
                    ->where('activo','=',1)
                    ->where('eliminado','=',0)->first();
                    if(!$existeFlujo)
                    {
                        try{
                            $flujo = new Flujos;
                            $flujo->id_tipoflujo = 1;
                            $flujo->doc_num = $lineas['DocNum'];
                            $flujo->tipo = utf8_encode($lineas['TIPO']);
                            $flujo->tax_date = $lineas['TaxDate'];
                            $flujo->doc_date = $lineas['DocDate'];
                            $flujo->card_code = utf8_encode($lineas['CardCode']);
                            $flujo->card_name = utf8_encode($lineas['CardName']);
                            $flujo->comments = utf8_encode($lineas['Comments']);
                            $flujo->doc_total = $lineas['DocTotal'];
                            $flujo->doc_curr = utf8_encode($lineas['DocCurr']);
                            $flujo->bank_code = utf8_encode($lineas['BankCode']);
                            $flujo->dfl_account = utf8_encode($lineas['DflAccount']);
                            $flujo->tipo_cuenta_destino = utf8_encode($lineas['Tipo_Cuenta_Destino']);
                            $flujo->cuenta_orgien = utf8_encode($lineas['Cuenta_Origen']);
                            $flujo->empresa_codigo = $lineas['Empresa_codigo'];
                            $flujo->empresa_nombre = utf8_encode($lineas['Empresa_nombre']);
                            $flujo->cheque = $lineas['Cheque'];
                            $flujo->en_favor_de = utf8_encode($lineas['EnFavorDe']);
                            $flujo->email = utf8_encode($lineas['E_Mail']);
                            $flujo->dias_credito = $lineas['Dias'];
                            $flujo->nombre_condicion_pago_dias = utf8_encode($lineas['NombreCondicionPagoDias']);
                            $flujo->cuenta_contable = utf8_encode($lineas['CuentaContable']);
                            $flujo->activo = 1;
                            $flujo->eliminado = 0;
                            $flujo->estado = 1;
                            $flujo->nivel = 0;
                            $flujo->origen_datos = 'SAP';
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

                            //Consulta de datos adicionales
                            
                            self::GetFlujoFacturaCantidad($flujo->id_flujo,$lineas['DocNum']);
                            self::GetFlujoFacturaDocumento($flujo->id_flujo,$lineas['DocNum']);
                            self::GetFlujoIngreso($flujo->id_flujo,$lineas['DocNum']);
                            self::GetFlujoOferta($flujo->id_flujo,$lineas['DocNum']);
                            self::GetFlujoOrden($flujo->id_flujo,$lineas['DocNum']);
                            self::GetFlujoSolicitud($flujo->id_flujo,$lineas['DocNum']);
                            self::GetFlujoNumeroCheque($flujo->id_flujo,$lineas['DocNum']);
                        }
                        catch(Exception $ex){
                            Log::error($e->getMessage());
                        }
                        

                        //Buscamos si el flujo no está asignado
                        /* 
                        $datosFlujoGrupo = FlujoGrupo::where('id_flujo',$flujo->id_flujo)->first();
                        if(!$datosFlujoGrupo)
                        {
                            $asignacionPorReferencia = 0;
                            $datosSolicitud = FlujoSolicitud::where('id_flujo',$flujo->id_flujo)->get();
                            if($datosSolicitud)
                            {
                                foreach($datosSolicitud as $itemSolicitud)
                                {
                                    $auth_solicitud1 = strtoupper($itemSolicitud->autorizador1);
                                    $auth_solicitud2 = strtoupper($itemSolicitud->autorizador2);
                                    $auth_solicitud3 = strtoupper($itemSolicitud->autorizador3);
                                    $datosReferencia = ReferenciaGrupoAutorizacion::where('usuario_referencia1', $auth_solicitud1)
                                                        ->where('usuario_referencia2', $auth_solicitud2)
                                                        ->where('usuario_referencia3', $auth_solicitud3)
                                                        ->where('activo',1)->where('eliminado',0)->first();
                                    if($datosReferencia)
                                    {
                                        $flujoGrupo = new FlujoGrupo;
                                        $flujoGrupo->id_flujo = $flujo->id_flujo;
                                        $flujoGrupo->id_grupoautorizacion = $datosReferencia->id_grupoautorizacion;
                                        $flujoGrupo->activo = 1;
                                        $flujoGrupo->eliminado = 0;
                                        $flujoGrupo->save();
                                        $flujo->id_grupoautorizacion = $datosReferencia->id_grupoautorizacion;
                                        $flujo->save();
                                        $asignacionPorReferencia = 1;
                                        break;
                                    }
                                }
                            }

                            if($asignacionPorReferencia == 0){
                                $datosFlujo = Flujos::where('id_flujo',$flujo->id_flujo)->first();
                                if($datosFlujo->cuenta_contable != null)
                                {
                                    //Buscamos si la cuenta contable hace match en la tabla de cuentas

                                    //Validamos si la cuenta guardada es un grupo de cuentas
                                    $grupo_cuentas = explode(",",$datosFlujo->cuenta_contable);
                                    $numero_cuentas = count($grupo_cuentas);
                                    if($numero_cuentas > 1){
                                        //Si es un grupo de cuentas
                                        $listadoCuentas = array();
                                        //Buscamos los grupos relacionas a cada cuenta
                                        foreach($grupo_cuentas as $itemGrupo){
                                            $datosCuentaGrupo = CuentaGrupoAutorizacion::where('activo',1)->where('eliminado',0)
                                            ->where('CodigoCuenta',$itemGrupo)->get();
                                            foreach($datosCuentaGrupo as $itemGrupo){
                                                $listadoCuentas[] = $itemGrupo->id_grupoautorizacion;
                                            }
                                        }
                                        //Contamos cuantos encontraron para sacar promedio
                                        $contadorDatosCompletos = count($listadoCuentas);
                                        //Contamos cuantos registros encontramos por grupo de autorización
                                        $cuentaDuplicados = array_count_values($listadoCuentas);
                                        $arrayPromedios = array();
                                        //Sacamos promedios por grupo de autorización
                                        foreach($cuentaDuplicados as $key => $value){
                                            $promedio = ($value * 100)/$contadorDatosCompletos;
                                            $arrayPromedios[] = [
                                                "grupo" => $key,
                                                "promedio" => $promedio
                                            ];
                                        }
                                        $grupoSugerido = array();
                                        $valorMayor = 0;
                                        //Validamos si hay uno con peso mayor a los demás, sino sacamos sugerencias
                                        foreach($arrayPromedios as $itemPromedio){
                                            if($itemPromedio["promedio"] > $valorMayor){
                                                $grupoSugerido = array();
                                                $grupoSugerido[] = $itemPromedio["grupo"];
                                                $valorMayor = $itemPromedio["promedio"];
                                            }else if($itemPromedio["promedio"] == $valorMayor){
                                                $grupoSugerido[] = $itemPromedio["grupo"];
                                            }
                                        }
                                        //Validamos si hay 1 asignamos, si hay más agregamos sugerencias
                                        if(count($grupoSugerido)>1){
                                            foreach($grupoSugerido as $itemSugerencia){
                                                $SugerenciaAsignacionGrupo = new SugerenciaAsignacionGrupo;
                                                $SugerenciaAsignacionGrupo->id_flujo = $flujo->id_flujo;
                                                $SugerenciaAsignacionGrupo->id_grupoautorizacion = $itemSugerencia;
                                                $SugerenciaAsignacionGrupo->activo = 1;
                                                $SugerenciaAsignacionGrupo->eliminado = 0;
                                                $SugerenciaAsignacionGrupo->save();
                                            }
                                        }else{
                                            foreach($grupoSugerido as $itemSugerencia){
                                                $flujoGrupo = new FlujoGrupo;
                                                $flujoGrupo->id_flujo = $flujo->id_flujo;
                                                $flujoGrupo->id_grupoautorizacion = $itemSugerencia;
                                                $flujoGrupo->activo = 1;
                                                $flujoGrupo->eliminado = 0;
                                                $flujoGrupo->save();
                                                $flujo->id_grupoautorizacion = $itemSugerencia;
                                                $flujo->save();
                                            }
                                        }
                                    }else{
                                        //Si es una sola cuenta
                                        $datosCuentaGrupo = CuentaGrupoAutorizacion::where('activo',1)->where('eliminado',0)
                                        ->where('CodigoCuenta',$datosFlujo->cuenta_contable)->get();
                                        $numero_encontrado = count($datosCuentaGrupo);
                                        if($numero_encontrado > 1){
                                            //Si tiene varias coincidencias
                                            foreach($datosCuentaGrupo as $itemSugerencia){
                                                $SugerenciaAsignacionGrupo = new SugerenciaAsignacionGrupo;
                                                $SugerenciaAsignacionGrupo->id_flujo = $flujo->id_flujo;
                                                $SugerenciaAsignacionGrupo->id_grupoautorizacion = $itemSugerencia->id_grupoautorizacion;
                                                $SugerenciaAsignacionGrupo->activo = 1;
                                                $SugerenciaAsignacionGrupo->eliminado = 0;
                                                $SugerenciaAsignacionGrupo->save();
                                            }
                                        }else{
                                            //Si solo tiene una
                                            if($datosCuentaGrupo){
                                                foreach($datosCuentaGrupo as $itemSugerencia){
                                                    $flujoGrupo = new FlujoGrupo;
                                                    $flujoGrupo->id_flujo = $flujo->id_flujo;
                                                    $flujoGrupo->id_grupoautorizacion = $itemSugerencia->id_grupoautorizacion;
                                                    $flujoGrupo->activo = 1;
                                                    $flujoGrupo->eliminado = 0;
                                                    $flujoGrupo->save();
                                                    $flujo->id_grupoautorizacion = $itemSugerencia->id_grupoautorizacion;
                                                    $flujo->save();
                                                }
                                            }
                                        }
                                    }
                                }
                            }
                        }
                         */
                    }
                }
            }
            else
            {
                for($i=0; $i< count($lineas);$i++)
                {
                    $existeFlujo = Flujos::where('doc_num',$lineas[$i]['DocNum'])
                    ->where('activo','=',1)
                    ->where('eliminado','=',0)->first();
                    if(!$existeFlujo)
                    {
                        try{
                            $flujo = new Flujos;
                            $flujo->id_tipoflujo = 1;
                            $flujo->doc_num = $lineas[$i]['DocNum'];
                            $flujo->tipo = utf8_encode($lineas[$i]['TIPO']);
                            $flujo->tax_date = $lineas[$i]['TaxDate'];
                            $flujo->doc_date = $lineas[$i]['DocDate'];
                            $flujo->card_code = utf8_encode($lineas[$i]['CardCode']);
                            $flujo->card_name = utf8_encode($lineas[$i]['CardName']);
                            $flujo->comments = utf8_encode($lineas[$i]['Comments']);
                            $flujo->doc_total = $lineas[$i]['DocTotal'];
                            $flujo->doc_curr = utf8_encode($lineas[$i]['DocCurr']);
                            $flujo->bank_code = utf8_encode($lineas[$i]['BankCode']);
                            $flujo->dfl_account = utf8_encode($lineas[$i]['DflAccount']);
                            $flujo->tipo_cuenta_destino = utf8_encode($lineas[$i]['Tipo_Cuenta_Destino']);
                            $flujo->cuenta_orgien = utf8_encode($lineas[$i]['Cuenta_Origen']);
                            $flujo->empresa_codigo = $lineas[$i]['Empresa_codigo'];
                            $flujo->empresa_nombre = utf8_encode($lineas[$i]['Empresa_nombre']);
                            $flujo->cheque = $lineas[$i]['Cheque'];
                            $flujo->en_favor_de = utf8_encode($lineas[$i]['EnFavorDe']);
                            $flujo->email = utf8_encode($lineas[$i]['E_Mail']);
                            $flujo->dias_credito = $lineas[$i]['Dias'];
                            $flujo->nombre_condicion_pago_dias = utf8_encode($lineas[$i]['NombreCondicionPagoDias']);
                            $flujo->cuenta_contable = utf8_encode($lineas[$i]['CuentaContable']);
                            $flujo->activo = 1;
                            $flujo->eliminado = 0;
                            $flujo->estado = 1;
                            $flujo->nivel = 0;
                            $flujo->origen_datos = 'SAP';
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

                            //Consulta de datos adicionales
                            
                            self::GetFlujoFacturaCantidad($flujo->id_flujo,$lineas[$i]['DocNum']);
                            self::GetFlujoFacturaDocumento($flujo->id_flujo,$lineas[$i]['DocNum']);
                            self::GetFlujoIngreso($flujo->id_flujo,$lineas[$i]['DocNum']);
                            self::GetFlujoOferta($flujo->id_flujo,$lineas[$i]['DocNum']);
                            self::GetFlujoOrden($flujo->id_flujo,$lineas[$i]['DocNum']);
                            self::GetFlujoSolicitud($flujo->id_flujo,$lineas[$i]['DocNum']);
                            self::GetFlujoNumeroCheque($flujo->id_flujo,$lineas[$i]['DocNum']);
                        }
                        catch(Exception $ex){
                            Log::error($e->getMessage());
                            continue;
                        }

                        //Buscamos si el flujo no está asignado
                        /* 
                        $datosFlujoGrupo = FlujoGrupo::where('id_flujo',$flujo->id_flujo)->first();
                        if(!$datosFlujoGrupo)
                        {
                            $asignacionPorReferencia = 0;
                            //Buscamos si hace match con los autorizadores en la tabla de referencias
                            $datosSolicitud = FlujoSolicitud::where('id_flujo',$flujo->id_flujo)->get();
                            if($datosSolicitud)
                            {
                                foreach($datosSolicitud as $itemSolicitud)
                                {
                                    $auth_solicitud1 = strtoupper($itemSolicitud->autorizador1);
                                    $auth_solicitud2 = strtoupper($itemSolicitud->autorizador2);
                                    $auth_solicitud3 = strtoupper($itemSolicitud->autorizador3);
                                    $datosReferencia = ReferenciaGrupoAutorizacion::where('usuario_referencia1', $auth_solicitud1)
                                                        ->where('usuario_referencia2', $auth_solicitud2)
                                                        ->where('usuario_referencia3', $auth_solicitud3)
                                                        ->where('activo',1)->where('eliminado',0)->first();
                                    if($datosReferencia)
                                    {
                                        $flujoGrupo = new FlujoGrupo;
                                        $flujoGrupo->id_flujo = $flujo->id_flujo;
                                        $flujoGrupo->id_grupoautorizacion = $datosReferencia->id_grupoautorizacion;
                                        $flujoGrupo->activo = 1;
                                        $flujoGrupo->eliminado = 0;
                                        $flujoGrupo->save();
                                        $flujo->id_grupoautorizacion = $datosReferencia->id_grupoautorizacion;
                                        $flujo->save();
                                        $asignacionPorReferencia = 1;
                                        break;
                                    }
                                }
                            }

                            if($asignacionPorReferencia == 0){
                                //Buscamos si tiene datos en el campo cuenta_contable
                                $datosFlujo = Flujos::where('id_flujo',$flujo->id_flujo)->first();
                                if($datosFlujo->cuenta_contable != null)
                                {
                                    //Buscamos si la cuenta contable hace match en la tabla de cuentas

                                    //Validamos si la cuenta guardada es un grupo de cuentas
                                    $grupo_cuentas = explode(",",$datosFlujo->cuenta_contable);
                                    $numero_cuentas = count($grupo_cuentas);
                                    if($numero_cuentas > 1){
                                        //Si es un grupo de cuentas
                                        $listadoCuentas = array();
                                        //Buscamos los grupos relacionas a cada cuenta
                                        foreach($grupo_cuentas as $itemGrupo){
                                            $datosCuentaGrupo = CuentaGrupoAutorizacion::where('activo',1)->where('eliminado',0)
                                            ->where('CodigoCuenta',$itemGrupo)->get();
                                            foreach($datosCuentaGrupo as $itemGrupo){
                                                $listadoCuentas[] = $itemGrupo->id_grupoautorizacion;
                                            }
                                        }
                                        //Contamos cuantos encontraron para sacar promedio
                                        $contadorDatosCompletos = count($listadoCuentas);
                                        //Contamos cuantos registros encontramos por grupo de autorización
                                        $cuentaDuplicados = array_count_values($listadoCuentas);
                                        $arrayPromedios = array();
                                        //Sacamos promedios por grupo de autorización
                                        foreach($cuentaDuplicados as $key => $value){
                                            $promedio = ($value * 100)/$contadorDatosCompletos;
                                            $arrayPromedios[] = [
                                                "grupo" => $key,
                                                "promedio" => $promedio
                                            ];
                                        }
                                        $grupoSugerido = array();
                                        $valorMayor = 0;
                                        //Validamos si hay uno con peso mayor a los demás, sino sacamos sugerencias
                                        foreach($arrayPromedios as $itemPromedio){
                                            if($itemPromedio["promedio"] > $valorMayor){
                                                $grupoSugerido = array();
                                                $grupoSugerido[] = $itemPromedio["grupo"];
                                                $valorMayor = $itemPromedio["promedio"];
                                            }else if($itemPromedio["promedio"] == $valorMayor){
                                                $grupoSugerido[] = $itemPromedio["grupo"];
                                            }
                                        }
                                        //Validamos si hay 1 asignamos, si hay más agregamos sugerencias
                                        if(count($grupoSugerido)>1){
                                            foreach($grupoSugerido as $itemSugerencia){
                                                $SugerenciaAsignacionGrupo = new SugerenciaAsignacionGrupo;
                                                $SugerenciaAsignacionGrupo->id_flujo = $flujo->id_flujo;
                                                $SugerenciaAsignacionGrupo->id_grupoautorizacion = $itemSugerencia;
                                                $SugerenciaAsignacionGrupo->activo = 1;
                                                $SugerenciaAsignacionGrupo->eliminado = 0;
                                                $SugerenciaAsignacionGrupo->save();
                                            }
                                        }else{
                                            foreach($grupoSugerido as $itemSugerencia){
                                                $flujoGrupo = new FlujoGrupo;
                                                $flujoGrupo->id_flujo = $flujo->id_flujo;
                                                $flujoGrupo->id_grupoautorizacion = $itemSugerencia;
                                                $flujoGrupo->activo = 1;
                                                $flujoGrupo->eliminado = 0;
                                                $flujoGrupo->save();
                                                $flujo->id_grupoautorizacion = $itemSugerencia;
                                                $flujo->save();
                                            }
                                        }
                                    }else{
                                        //Si es una sola cuenta
                                        $datosCuentaGrupo = CuentaGrupoAutorizacion::where('activo',1)->where('eliminado',0)
                                        ->where('CodigoCuenta',$datosFlujo->cuenta_contable)->get();
                                        $numero_encontrado = count($datosCuentaGrupo);
                                        if($numero_encontrado > 1){
                                            //Si tiene varias coincidencias
                                            foreach($datosCuentaGrupo as $itemSugerencia){
                                                $SugerenciaAsignacionGrupo = new SugerenciaAsignacionGrupo;
                                                $SugerenciaAsignacionGrupo->id_flujo = $flujo->id_flujo;
                                                $SugerenciaAsignacionGrupo->id_grupoautorizacion = $itemSugerencia->id_grupoautorizacion;
                                                $SugerenciaAsignacionGrupo->activo = 1;
                                                $SugerenciaAsignacionGrupo->eliminado = 0;
                                                $SugerenciaAsignacionGrupo->save();
                                            }
                                        }else{
                                            //Si solo tiene una
                                            if($datosCuentaGrupo){
                                                foreach($datosCuentaGrupo as $itemSugerencia){
                                                    $flujoGrupo = new FlujoGrupo;
                                                    $flujoGrupo->id_flujo = $flujo->id_flujo;
                                                    $flujoGrupo->id_grupoautorizacion = $itemSugerencia->id_grupoautorizacion;
                                                    $flujoGrupo->activo = 1;
                                                    $flujoGrupo->eliminado = 0;
                                                    $flujoGrupo->save();
                                                    $flujo->id_grupoautorizacion = $itemSugerencia->id_grupoautorizacion;
                                                    $flujo->save();
                                                }
                                            }
                                        }
                                    }
                                }
                            }
                        }
                         */
                    }
                }
            }
            self::cancelados();
            self::cargaits();
            self::cargaitscancelados();
            return true;
        }catch(Exception $e){
            return $e->getMessage();
        }
    }

    function GetFlujoFacturaCantidad($id_flujo, $num_doc){
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

    function GetFlujoFacturaDocumento($id_flujo, $num_doc){
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

    function GetFlujoNumeroCheque($id_flujo, $num_doc){
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

    function GetFlujoIngreso($id_flujo, $num_doc){
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

    function GetFlujoOferta($id_flujo, $num_doc){
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

    function GetFlujoOrden($id_flujo, $num_doc){
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

    function GetFlujoSolicitud($id_flujo, $num_doc){
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

    public function cancelados()
    {
        try
        {
            ini_set('memory_limit', '1024M');
            $fecha_fin = date('Y-m-d');
            $fecha_inicio = date('Y-m-d', strtotime("-2 days"));
            $client = new \nusoap_client('http://10.20.30.144/GSION_WS/WSGetFromSAP.asmx?wsdl',true);
            //$param = array('sFechaDel'=>$fecha_inicio , 'sFechaAl'=>$fecha_fin);
            $param = array('sFechaDel'=>'2021-11-01' , 'sFechaAl'=>'2021-11-05');
            $resultado = $client->call('Get_PAGOEFECTUADO_CANCELADO_XML',$param);
            if($client->fault)
            {
                $error = $client->getError();;
                if($error)
                {
                    echo 'Error:' . $client->faultstring;
                }
                die();
            }
            $lineas = $resultado['Get_PAGOEFECTUADO_CANCELADO_XMLResult']['BOM']['BO']['Recordset']['row'];
            
            if(count($lineas) == count($lineas, COUNT_RECURSIVE))
            {
                if($lineas['DocNum'] != 0){
                    $existeFlujo = Flujos::where('doc_num',$lineas['DocNum'])
                    ->where('activo','=',1)
                    ->where('eliminado','=',0)->first();
                    if($existeFlujo)
                    {
                        $existeFlujo->estado = 8;
                        $existeFlujo->save();
                        $flujoDetalle = new FlujoDetalle;
                        $flujoDetalle->IdFlujo = $existeFlujo->id_flujo;
                        $flujoDetalle->IdEstadoFlujo = 8;
                        $flujoDetalle->IdUsuario = 11;
                        $flujoDetalle->Fecha = date("Y-m-d H:i",strtotime('-6 hour',strtotime(now())));
                        $flujoDetalle->Comentario = 'Cancelado desde sistema origen';
                        $flujoDetalle->NivelAutorizo = 0;
                        $flujoDetalle->save();
                    }
                }
            }
            else
            {
                for($i=0; $i< count($lineas);$i++)
                {
                    $existeFlujo = Flujos::where('doc_num',$lineas[$i]['DocNum'])
                    ->where('activo','=',1)
                    ->where('eliminado','=',0)->first();
                    if($existeFlujo)
                    {
                        $existeFlujo->estado = 8;
                        $existeFlujo->save();

                        $flujoDetalle = new FlujoDetalle;
                        $flujoDetalle->IdFlujo = $existeFlujo->id_flujo;
                        $flujoDetalle->IdEstadoFlujo = 8;
                        $flujoDetalle->IdUsuario = 11;
                        $flujoDetalle->Fecha = date("Y-m-d H:i",strtotime('-6 hour',strtotime(now())));
                        $flujoDetalle->Comentario = 'Cancelado desde sistema origen';
                        $flujoDetalle->NivelAutorizo = 0;
                        $flujoDetalle->save();
                    }
                }
            }
            return true;
        }catch(Exception $e){
            return $e->getMessage();
        }
    }

    public function cargaits(){
        try
        {
            $ItemPolitica = Politicas::where('identificador','=','_DIAS_BASE_CREDITO_')
            ->where('activo',1)->where('eliminado',0)->first();
            $valorDiasCreditoBase = intval($ItemPolitica->valor);
            $fecha_fin = date('Ymd');
            $fecha_inicio = date('Ymd', strtotime("-1 days"));
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
            ->where('BM.Fecha', '>=', $fecha_inicio)
            ->where('BM.Fecha', '<=', $fecha_fin)
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
                    //$existeFlujo->cheque = $item->Cheque;
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

    public function cargaitscancelados(){
        try
        {
            $ItemPolitica = Politicas::where('identificador','=','_DIAS_BASE_CREDITO_')
            ->where('activo',1)->where('eliminado',0)->first();
            $valorDiasCreditoBase = intval($ItemPolitica->valor);

            $fecha_fin = date('Ymd');
            $fecha_inicio = date('Ymd', strtotime("-1 days"));
            $datos = ZEMPRESA::join('BANCO MAESTRO as BM', function($join){
                $join->on('EMPRESA.Codigo', '=', 'BM.EMPRESA');
            })->join('MONEDA as M', function($join){
                $join->on('BM.Moneda', '=', 'M.Codigo');
            })
            ->selectRaw(
                "BM.comentario_aprobacion,
                BM.estado_aprobacion,
                EMPRESA.Nombre,
                BM.Estatus,
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
            ->where('BM.Fecha', '>=', $fecha_inicio)
            ->where('BM.Fecha', '<=', $fecha_fin)
            ->where('BM.Estatus', '=', 'A')
            ->orderBy('BM.Fecha', 'ASC')  
            ->get();

            foreach($datos as $item)
            {
                $existeFlujo = Flujos::where('doc_num',$item->Documento)
                ->where('empresa_codigo',$item->Empresa)
                ->where('activo','=',1)
                ->where('eliminado','=',0)
                //->whereIn('estado', [1,2,3,4,10,11])
                ->first();
                if($existeFlujo)
                {
                    $existeFlujo->estado = 8;
                    $existeFlujo->save();
                    $existeFlujoDetalle = FlujoDetalle::where('IdFlujo',$existeFlujo->id_flujo)
                    ->where('IdEstadoFlujo',8)->first();
                    if(!$existeFlujoDetalle){
                        $flujoDetalle = new FlujoDetalle;
                        $flujoDetalle->IdFlujo = $existeFlujo->id_flujo;
                        $flujoDetalle->IdEstadoFlujo = 8;
                        $flujoDetalle->IdUsuario = 11;
                        $flujoDetalle->Fecha = date("Y-m-d H:i",strtotime('-6 hour',strtotime(now())));
                        $flujoDetalle->Comentario = 'Cancelado desde sistema origen';
                        $flujoDetalle->NivelAutorizo = 0;
                        $flujoDetalle->save();
                    }
                }
            }
            return true;
        }catch(Exception $e){
            return $e->getMessage();
        }
    }
}