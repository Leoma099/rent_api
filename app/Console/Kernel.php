<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    protected function schedule(Schedule $schedule)
    {
        // Lease update: run once daily at 8 AM
        $schedule->command('lease:update-status')
            ->dailyAt('08:00')
            ->withoutOverlapping();

        // Properties set inactive: run once daily at 8:10 AM
        $schedule->command('properties:set-inactive')
            ->dailyAt('08:10')
            ->withoutOverlapping();

        // Queue emails: run every 5 minutes
        $schedule->command('queue:work --once --tries=3')
            ->everyFiveMinutes();

        // Optional: keep a small log for debugging
        $schedule->call(function () {
            \Log::info('CRON RAN AT: ' . now());
        })->everyFiveMinutes();
    }

    protected function commands()
    {
        $this->load(__DIR__.'/Commands');
        require base_path('routes/console.php');
    }
}
