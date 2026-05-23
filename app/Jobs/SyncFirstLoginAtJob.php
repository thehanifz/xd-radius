<?php

namespace App\Jobs;

use App\Models\Member;
use App\Models\Voucher;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;

class SyncFirstLoginAtJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function handle(): void
    {
        // Sync vouchers without first_login_at
        Voucher::whereNull('first_login_at')->chunk(200, function ($vouchers) {
            foreach ($vouchers as $voucher) {
                $firstSession = DB::table('radacct')
                    ->where('username', $voucher->username)
                    ->orderBy('acctstarttime')
                    ->value('acctstarttime');

                if ($firstSession) {
                    $voucher->update(['first_login_at' => $firstSession]);
                }
            }
        });

        // Sync members without first_login_at
        Member::whereNull('first_login_at')->chunk(200, function ($members) {
            foreach ($members as $member) {
                $firstSession = DB::table('radacct')
                    ->where('username', $member->username)
                    ->orderBy('acctstarttime')
                    ->value('acctstarttime');

                if ($firstSession) {
                    $member->update(['first_login_at' => $firstSession]);
                }
            }
        });
    }
}
