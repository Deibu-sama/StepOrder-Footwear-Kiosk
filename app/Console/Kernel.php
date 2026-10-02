<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    protected $commands = [
        Commands\SeedFirestore::class,
        Commands\CancelExpiredOrders::class,
    ];

    protected function schedule(Schedule $schedule): void
    {
        $schedule->command('orders:cancel-expired')->dailyAt('02:00')->timezone(config('app.timezone'));
    }
}
