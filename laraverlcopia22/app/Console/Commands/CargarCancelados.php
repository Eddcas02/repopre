<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use App\Models\Flujos;
use App\Models\FlujoDetalle;
use App\Models\FlujoFacturaCantidad;
use App\Models\FlujoFacturaDocumento;
use App\Models\FlujoIngreso;
use App\Models\FlujoOferta;
use App\Models\FlujoOrden;
use App\Models\FlujoSolicitud;
use App\Models\FlujoGrupo;

class CargarCancelados extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'datos:cancelados';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Cargar datos cancelados';

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
        Log::info('Ejecución de proceso carga cancelados '.date('Y-m-d h:i:s'));
        try
        {
            ini_set('memory_limit', '1024M');
            $fecha_fin = date('Y-m-d');
            $fecha_inicio = date('Y-m-d', strtotime("-30 days"));
            $client = new \nusoap_client('http://10.20.30.144/GSION_WS/WSGetFromSAP.asmx?wsdl',true);
            $param = array('sFechaDel'=>$fecha_inicio , 'sFechaAl'=>$fecha_fin);
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
            }
        }catch(Exception $e){
            Log::error($e->getMessage());
        }
        return 0;
    }
}
