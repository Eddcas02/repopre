<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use App\Models\Flujos;

class DesactivarDuplicados extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'datos:duplicados';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Desactiva pagos duplicados';

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
        Log::info('Ejecución de proceso desactivar duplicados '.date('Y-m-d h:i:s'));
        try
        {
            $datos = Flujos::select('doc_num')
            ->where('activo',1)->where('eliminado',0)
            ->groupBy('doc_num')
            ->orderBy('doc_num')
            ->having(\DB::raw('count(doc_num)'),'>',1)
            ->get()->toArray();
            
            foreach($datos as $item){
                $pagoQueQueda = 0;
                $pagos = Flujos::where('doc_num','=',$item['doc_num'])
                ->orderBy('estado','DESC')
                ->orderBy('id_flujo','DESC')->get();
                foreach($pagos as $itemPagos){
                    if($pagoQueQueda == 0 && $itemPagos->activo == 1 && $itemPagos->eliminado == 0){
                        $pagoQueQueda = $itemPagos->id_flujo;
                    }else{
                        Flujos::where('id_flujo', $itemPagos->id_flujo)
                                ->update(['activo' => 0, 'eliminado' => 1]);
                    }
                }
            }
        }catch(Exception $e){
            Log::error($e->getMessage());
        }
        return 0;
    }
}
