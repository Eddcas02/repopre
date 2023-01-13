<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use App\Models\SesionUsuario;
use Carbon\Carbon;

class CerrarSesion extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'datos:cierresesion';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Cierra sesiones activas';

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
        Log::info('Ejecución de proceso cierre de sesión '.date('Y-m-d h:i:s'));
        try
        {
            $fechaHora = Carbon::now('America/Guatemala');
            SesionUsuario::where('Activo','=',1)
            ->update([
                'FechaHoraFinal' => $fechaHora,
                'Activo' => 0
            ]);
        }catch(Exception $e){
            Log::error($e->getMessage());
        }
        return 0;
    }
}
