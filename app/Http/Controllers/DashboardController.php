<?php

namespace App\Http\Controllers;

use App\Models\BillingInvoice;
use App\Models\Member;
use App\Models\Plan;
use App\Models\Voucher;
use App\Models\VoucherBatch;
use App\Services\BillingService;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function __construct(protected BillingService $billing) {}

    public function index()
    {
        // Mark overdue dulu
        $this->billing->markOverdue();

        $stats = [
            'voucher_active'   => Voucher::where('status', 'active')->count(),
            'member_active'    => Member::where('status', 'active')->count(),
            'member_isolated'  => Member::where('status', 'isolated')->count(),
            'session_online'   => DB::table('radacct')->whereNull('acctstoptime')->count(),
            'plan_active'      => Plan::where('is_active', true)->count(),
            'invoice_overdue'  => BillingInvoice::where('status', 'overdue')->count(),
            'invoice_pending'  => BillingInvoice::where('status', 'pending')->count(),
            'revenue_month'    => BillingInvoice::where('status', 'paid')
                ->whereMonth('updated_at', now()->month)
                ->whereYear('updated_at', now()->year)
                ->sum('amount'),
        ];

        $recentBatches = VoucherBatch::with('plan')
            ->latest('generated_at')
            ->limit(5)
            ->get();

        $overdueInvoices = BillingInvoice::with('member.plan')
            ->where('status', 'overdue')
            ->latest('due_date')
            ->limit(5)
            ->get();

        $recentInvoices = BillingInvoice::with('member.plan')
            ->latest()
            ->limit(5)
            ->get();

        return view('dashboard', compact(
            'stats',
            'recentBatches',
            'overdueInvoices',
            'recentInvoices'
        ));
    }
}
