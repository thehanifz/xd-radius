<?php

namespace App\Console\Commands;

use App\Jobs\SyncFirstLoginAtJob;
use Illuminate\Console\Command;

class ReconcileVouchersCommand extends Command
{
    protected $signature = 'vouchers:reconcile';
    protected $description = 'Reconcile first login, expiry, and RADIUS voucher state';

    public function handle(): int
    {
        SyncFirstLoginAtJob::dispatchSync();
        $this->info('Voucher reconciliation selesai.');

        return self::SUCCESS;
    }
}
