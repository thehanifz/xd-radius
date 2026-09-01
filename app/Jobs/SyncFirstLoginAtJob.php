<?php

namespace App\Jobs;

use App\Models\Member;
use App\Models\Voucher;
use App\Services\VoucherValidityService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;

class SyncFirstLoginAtJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function handle(VoucherValidityService $validity): void
    {
        Voucher::with('plan')->whereNull('first_login_at')->chunkById(200, function ($vouchers) use ($validity) {
            foreach ($vouchers as $voucher) {
                $firstSession = DB::table('radacct')
                    ->where('username', $voucher->username)
                    ->whereNotNull('acctstarttime')
                    ->orderBy('acctstarttime')
                    ->value('acctstarttime');

                if ($firstSession) {
                    $validity->activateFromFirstLogin($voucher, \Carbon\Carbon::parse($firstSession));
                }
            }
        });

        Member::whereNull('first_login_at')->chunkById(200, function ($members) {
            foreach ($members as $member) {
                $firstSession = DB::table('radacct')
                    ->where('username', $member->username)
                    ->whereNotNull('acctstarttime')
                    ->orderBy('acctstarttime')
                    ->value('acctstarttime');

                if ($firstSession) {
                    $member->update(['first_login_at' => $firstSession]);
                }
            }
        });

        $validity->markExpired();
    }
}
