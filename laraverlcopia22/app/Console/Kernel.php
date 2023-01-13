<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    /**
     * The Artisan commands provided by your application.
     *
     * @var array
     */
    protected $commands = [
        Commands\CargarDatos::class,
        Commands\CargarCancelados::class,
        Commands\CerrarSesion::class,
        Commands\RecuperarRespuestaBanco::class,
        Commands\ProcesarRespuestaBanco::class,
        Commands\CargarDatosITS::class,
        Commands\CargarDatosITSCancelados::class,
        Commands\DesactivarDuplicados::class,
        Commands\ActualizarNoCheques::class,
    ];

    /**
     * Define the application's command schedule.
     *
     * @param  \Illuminate\Console\Scheduling\Schedule  $schedule
     * @return void
     */
    protected function schedule(Schedule $schedule)
    {
        $schedule->command('datos:cargar')->everyMinute()->runInBackground();
        $schedule->command('datos:cancelados')->everyMinute()->runInBackground();
        $schedule->command('datos:cierresesion')->dailyAt('23:59')->timezone('America/Guatemala')->runInBackground();
        $schedule->command('banco:respuesta')->everyFiveMinutes()->runInBackground();
        $schedule->command('banco:procesar')->everyFiveMinutes()->runInBackground();
        $schedule->command('datos:its')->everyMinute()->runInBackground();
        $schedule->command('datos:duplicados')->everyMinute()->runInBackground();
        $schedule->command('datos:itscancelados')->everyMinute()->runInBackground();
        $schedule->command('datos:cheques')->everyMinute()->runInBackground();
    }

    /**
     * Register the commands for the application.
     *
     * @return void
     */
    protected function commands()
    {
        $this->load(__DIR__.'/Commands');

        require base_path('routes/console.php');
    }
}
