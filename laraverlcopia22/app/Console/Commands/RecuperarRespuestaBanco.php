<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use File;

class RecuperarRespuestaBanco extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'banco:respuesta';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Recupera respuesta de banco';

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
        Log::info('Ejecución de proceso recuperación de respuesta '.date('Y-m-d h:i:s'));
        try
        {
            $pathDestino = storage_path('app/respuestaBanco/pendientes');
            $pathTerminado = storage_path('app/respuestaBanco/procesados/');
            $files_sftp = File::allFiles('/home/prd/in/PAIN002/');
            foreach($files_sftp as $item){
                $datosArchivo = pathinfo($item);
                //Storage::disk('local')->move($item,$pathDestino.'/'.$datosArchivo['basename']);
                File::move($item,$pathDestino.'/'.$datosArchivo['basename']);
            }
        }catch(Exception $e){
            Log::error($e->getMessage());
        }
        return 0;
    }
}
