<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    /**
     * Define the application's command schedule.
     *
     * @param  \Illuminate\Console\Scheduling\Schedule  $schedule
     * @return void
     */
    protected function schedule(Schedule $schedule)
    {
        // $schedule->command('inspire')->hourly();
        $schedule->command('CronJob:updatedo')
        ->everyMinute();

        $schedule->command('invoices:sync-default-driver-id')
        ->dailyAt('00:00')
        ->withoutOverlapping();

        $schedule->command('assigns:auto-assign')
        ->dailyAt('01:00')
        ->timezone('Asia/Kuala_Lumpur')
        ->withoutOverlapping();
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
