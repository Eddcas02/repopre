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

class CargarDatosITS extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'datos:its';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Carga automática de datos ITS';

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
        Log::info('Ejecución de proceso carga datos ITS '.date('Y-m-d h:i:s'));
        try
        {
            ini_set('memory_limit', '1024M');

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
        }catch(Exception $e){
            Log::error($e->getMessage());
        }
        return 0;
    }
}
