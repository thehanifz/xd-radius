<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;
use App\Jobs\SyncFirstLoginAtJob;
use App\Jobs\ReconcileStaleSessionsJob;
use App\Jobs\GenerateOverdueInvoicesJob;
use App\Jobs\AutoIsolateOverdueMembersJob;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// ─── Scheduler Jobs ──────────────────────────────────────────────────────────

// Sync first_login_at dari radacct ke vouchers/members — setiap jam
Schedule::job(new SyncFirstLoginAtJob)->hourly()->name('sync-first-login');

// Rekonsiliasi sesi stale di radacct — setiap 15 menit
Schedule::job(new ReconcileStaleSessionsJob)->everyFifteenMinutes()->name('reconcile-stale-sessions');

// Generate invoice untuk member yang jatuh tempo H-7 — setiap hari jam 01:00
Schedule::job(new GenerateOverdueInvoicesJob)->dailyAt('01:00')->name('generate-overdue-invoices');

// Auto-isolir member dengan invoice overdue — setiap hari jam 02:00
Schedule::job(new AutoIsolateOverdueMembersJob)->dailyAt('02:00')->name('auto-isolate-overdue-members');
