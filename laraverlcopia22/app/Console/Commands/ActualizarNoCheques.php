<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use App\Models\Flujos;
use App\Models\FlujoNumeroCheque;
use App\Models\ZEMPRESA;

class ActualizarNoCheques extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'datos:cheques';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Actualizar número de cheque';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        Log::info('Ejecución de actualización de cheques '.date('Y-m-d h:i:s'));
        try
        {
            ini_set('memory_limit', '1024M');
            $flujosSinCheques = Flujos::where('tipo','=','BANCARIO')
            ->where('estado','<',6)
            ->where('cheque','<',2)
            ->where('activo',1)
            ->where('eliminado',0)->get();

            foreach($flujosSinCheques as $pago){
                if($pago->origen_datos == 'SAP'){
                    $fechaFlujoOriginal = strtotime($pago->doc_date);
                    $docNumOriginal = $pago->doc_num;
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
                                $existeFlujo->cheque = $lineas['Cheque'];
                                $existeFlujo->save();        
    
                                //Consulta de datos adicionales
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
                                    $existeFlujo->cheque = $lineas[$i]['Cheque'];
                                    $existeFlujo->save();
    
                                    //Consulta de datos adicionales  
                                    self::GetFlujoNumeroCheque($existeFlujo->id_flujo,$lineas[$i]['DocNum']);  
                                }
                            }
                        }
                    }
                }

                if($pago->origen_datos == 'ITS'){
                    self::cargaits($pago->doc_num);
                }
            }

        }catch(Exception $e){
            Log::error($e->getMessage());
        }
        return 0;
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
                if($existeFlujo)
                {
                    $existeFlujo->cheque = $item->Documento;
                    $existeFlujo->save();
                }
            }
            return true;
        }catch(Exception $e){
            return $e->getMessage();
        }
    }
}
