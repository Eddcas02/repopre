<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use App\Models\Flujos;
use App\Models\FlujoDetalle;
use App\Models\ZBancoMaestro;
use App\Models\ZEmpresa;
use App\Models\FlujoCambioDias;
use App\Models\Politicas;

class CargarDatosITSCancelados extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'datos:itscancelados';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Carga automática de datos ITS cancelados';

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
        Log::info('Ejecución de proceso carga datos ITS cancelados '.date('Y-m-d h:i:s'));
        try
        {
            ini_set('memory_limit', '1024M');

            $ItemPolitica = Politicas::where('identificador','=','_DIAS_BASE_CREDITO_')
            ->where('activo',1)->where('eliminado',0)->first();
            $valorDiasCreditoBase = intval($ItemPolitica->valor);

            $fecha_fin = date('Ymd');
            $fecha_inicio = date('Ymd', strtotime("-30 days"));
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
        }catch(Exception $e){
            Log::error($e->getMessage());
        }
        return 0;
    }
}
