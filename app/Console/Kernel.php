<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;
use App\Models\Lease;

class Kernel extends ConsoleKernel
{
    protected function schedule(Schedule $schedule)
    {
        // Automatically check lease status every minute
        $schedule->call(function () {
            Lease::with(['tenant', 'property', 'landlord'])->get()
                ->each->autoUpdateStatus();
        })->everyMinute();
    }

    protected function commands()
    {
        $this->load(__DIR__.'/Commands');
        require base_path('routes/console.php');
    }
}
