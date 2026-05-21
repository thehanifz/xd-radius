<?php

namespace App\Jobs;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use App\Models\Voucher;
use App\Models\Member;

class SyncFirstLoginAtJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Create a new job instance.
     */
    public function __construct()
    {
        //
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        // Vouchers
        $vouchers = Voucher::whereNull('first_login_at')->pluck('username', 'id');
        foreach ($vouchers as $id => $username) {
            $firstLogin = DB::table('radacct')
                ->where('username', $username)
                ->orderBy('acctstarttime', 'asc')
                ->value('acctstarttime');
            
            if ($firstLogin) {
                Voucher::where('id', $id)->update(['first_login_at' => $firstLogin]);
            }
        }

        // Members
        $members = Member::whereNull('first_login_at')->pluck('username', 'id');
        foreach ($members as $id => $username) {
            $firstLogin = DB::table('radacct')
                ->where('username', $username)
                ->orderBy('acctstarttime', 'asc')
                ->value('acctstarttime');
            
            if ($firstLogin) {
                Member::where('id', $id)->update(['first_login_at' => $firstLogin]);
            }
        }
    }
}
