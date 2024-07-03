<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    /**
     * Define the application's command schedule.
     */
    protected function schedule(Schedule $schedule): void
    {
        // $schedule->command('queue:restart')->everyFiveMinutes();
        // $schedule->command('queue:work --stop-when-empty')
        //      ->everyMinute()
        //      ->withoutOverlapping();
        // $schedule->command('queue:work')->everyMinute();

        // Reinicia la cola cada cinco minutos para evitar posibles bloqueos
        $schedule->command('queue:restart')->everyFiveMinutes();

        // Procesa la cola cada minuto, asegurándose de que no haya solapamientos y se detenga cuando esté vacía
        $schedule->command('queue:work --stop-when-empty')
                ->everyMinute()
                ->withoutOverlapping();
    }

    /**
     * Register the commands for the application.
     */
    protected function commands(): void
    {
        $this->load(__DIR__.'/Commands');

        require base_path('routes/console.php');
    }
}
