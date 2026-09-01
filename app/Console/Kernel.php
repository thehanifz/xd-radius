<?php

namespace App\Console;

use App\Jobs\AutoIsolateOverdueMembersJob;
use App\Jobs\GenerateOverdueInvoicesJob;
use App\Jobs\ReconcileStaleSessionsJob;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    protected function schedule(Schedule $schedule): void
    {
        // Tandai sesi stale setiap 15 menit
        $schedule->job(new ReconcileStaleSessionsJob)->everyFifteenMinutes();

        // Generate & mark invoice overdue setiap hari jam 01:00
        $schedule->job(new GenerateOverdueInvoicesJob)->dailyAt('01:00');

        // Auto isolir member overdue setiap hari jam 02:00
        $schedule->job(new AutoIsolateOverdueMembersJob)->dailyAt('02:00');
    }

    protected function commands(): void
    {
        $this->load(__DIR__ . '/Commands');
        require base_path('routes/console.php');
    }
}
