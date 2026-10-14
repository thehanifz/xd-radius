<?php

namespace App\Http\Controllers;

use App\Models\BillingInvoice;
use App\Models\Member;
use App\Models\Plan;
use App\Models\Voucher;
use App\Models\VoucherBatch;
use App\Services\BillingService;
use App\Models\Radacct;
use App\Models\Router;

class DashboardController extends Controller
{
    public function __construct(protected BillingService $billing) {}

    public function index()
    {
        // Mark overdue dulu
        $this->billing->markOverdue();

        $todayStart = now()->startOfDay();
        $todayEnd = now()->endOfDay();
        $todaySessions = Radacct::whereBetween('acctstarttime', [$todayStart, $todayEnd]);
        $onlineSessionCountsByUsername = Radacct::active()
            ->selectRaw('username, COUNT(*) AS session_count')
            ->groupBy('username')
            ->pluck('session_count', 'username');
        $onlineVoucherUsernames = Voucher::whereIn('username', $onlineSessionCountsByUsername->keys())
            ->pluck('username');
        $onlineVoucherSessionCount = $onlineVoucherUsernames->sum(
            fn ($username) => (int) $onlineSessionCountsByUsername->get($username)
        );

        $stats = [
            'voucher_active'   => $onlineVoucherSessionCount,
            'voucher_available'=> Voucher::available()->count(),
            'voucher_used'     => Voucher::used()->count(),
            'voucher_expired'  => Voucher::where('status', 'expired')->count(),
            'member_active'    => Member::where('status', 'active')->count(),
            'member_isolated'  => Member::where('status', 'isolated')->count(),
            'session_online'   => Radacct::active()->count(),
            'session_today'    => (clone $todaySessions)->count(),
            'traffic_upload_today' => (int) (clone $todaySessions)->sum('acctoutputoctets'),
            'traffic_download_today' => (int) (clone $todaySessions)->sum('acctinputoctets'),
            'plan_active'      => Plan::where('is_active', true)->count(),
            'invoice_overdue'  => BillingInvoice::forCurrentMembers()->where('status', 'overdue')->count(),
            'invoice_pending'  => BillingInvoice::forCurrentMembers()->where('status', 'pending')->count(),
            'revenue_month'    => BillingInvoice::where('status', 'paid')
                ->whereMonth('updated_at', now()->month)
                ->whereYear('updated_at', now()->year)
                ->sum('amount'),
        ];

        $recentBatches = VoucherBatch::with('plan')
            ->latest('generated_at')
            ->limit(5)
            ->get();

        $recentInvoices = BillingInvoice::with('member.plan')
            ->latest()
            ->limit(5)
            ->get();

        $onlineSessions = Radacct::active()
            ->orderByDesc('acctstarttime')
            ->limit(8)
            ->get();

        $isSuperUser = auth('app')->user()?->isSuperUser() ?? false;
        $routerStats = [
            'total' => 0,
            'connected' => 0,
            'attention' => 0,
        ];
        $dashboardRouters = collect();
        $activeRouters = Router::active();
        $routerStats['total'] = (clone $activeRouters)->count();
        $routerStats['connected'] = (clone $activeRouters)->where('last_connection_status', 'ok')->count();
        $routerStats['attention'] = $routerStats['total'] - $routerStats['connected'];

        if ($isSuperUser) {
            $dashboardRouters = (clone $activeRouters)
                ->orderByRaw("CASE WHEN last_connection_status = 'error' THEN 0 WHEN last_connection_status = 'ok' THEN 1 ELSE 2 END")
                ->orderBy('name')
                ->limit(5)
                ->get();
        }

        return view('dashboard', compact(
            'stats',
            'recentBatches',
            'recentInvoices',
            'onlineSessions',
            'routerStats',
            'dashboardRouters',
            'isSuperUser'
        ));
    }
}
