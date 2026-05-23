<?php

namespace App\Http\Controllers;

use App\Models\Member;
use App\Models\Plan;
use App\Models\Voucher;
use App\Models\VoucherBatch;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class ReportController extends Controller
{
    public function index(Request $request)
    {
        Gate::authorize('superuser-only');

        $month  = $request->integer('month', now()->month);
        $year   = $request->integer('year',  now()->year);
        $type   = $request->get('type',   'all');   // all, voucher, member
        $planId = $request->get('plan_id', null);
        $operatorId = $request->get('operator_id', null); // null = all (superuser sees all)

        $plans     = Plan::orderBy('name')->get();
        $operators = \App\Models\AppUser::where('role', 'operator')->orderBy('name')->get();
        $currentUser = auth('app')->user();

        // Build date range
        $start = \Carbon\Carbon::create($year, $month, 1)->startOfMonth();
        $end   = $start->copy()->endOfMonth();

        // ── Vouchers ──────────────────────────────────────────────────────────
        $vouchers = collect();
        if ($type === 'all' || $type === 'voucher') {
            $vq = Voucher::with(['plan', 'batch.generatedBy'])
                ->whereBetween('created_at', [$start, $end]);

            if ($planId) $vq->where('plan_id', $planId);

            // Filter per operator: operator hanya lihat miliknya, superuser bisa filter
            if ($currentUser->isOperator()) {
                $vq->whereHas('batch', fn($q) => $q->where('generated_by', $currentUser->id));
            } elseif ($operatorId) {
                $vq->whereHas('batch', fn($q) => $q->where('generated_by', $operatorId));
            }

            $vouchers = $vq->get();
        }

        // ── Members ───────────────────────────────────────────────────────────
        $members = collect();
        if ($type === 'all' || $type === 'member') {
            $mq = Member::with(['plan', 'invoices' => fn($q) => $q->whereYear('period_start', $year)->whereMonth('period_start', $month)])
                ->where(fn($q) => $q
                    ->whereBetween('activated_at', [$start, $end])
                    ->orWhere(fn($q2) => $q2->where('expired_at', '>=', $start)->where('activated_at', '<=', $end))
                );

            if ($planId) $mq->where('plan_id', $planId);

            // Operator hanya lihat member yang dia buat (created_by jika ada) — fallback semua
            $members = $mq->get();
        }

        // ── Summary ───────────────────────────────────────────────────────────
        $summary = [
            'total_voucher_active'  => $vouchers->where('status', 'active')->count(),
            'total_voucher_expired' => $vouchers->where('status', 'expired')->count(),
            'total_member_active'   => $members->where('status', 'active')->count(),
            'total_revenue'         => $vouchers->sum('price_snapshot') + $members->sum('price_snapshot'),
        ];

        return view('reports.monthly', compact(
            'vouchers', 'members', 'summary', 'plans', 'operators',
            'month', 'year', 'type', 'planId', 'operatorId', 'start', 'end'
        ));
    }

    public function pdf(Request $request)
    {
        Gate::authorize('superuser-only');

        // Re-use same logic
        $month  = $request->integer('month', now()->month);
        $year   = $request->integer('year',  now()->year);
        $type   = $request->get('type',   'all');
        $planId = $request->get('plan_id', null);
        $operatorId = $request->get('operator_id', null);

        $start = \Carbon\Carbon::create($year, $month, 1)->startOfMonth();
        $end   = $start->copy()->endOfMonth();

        $vouchers = collect();
        if ($type === 'all' || $type === 'voucher') {
            $vq = Voucher::with(['plan', 'batch.generatedBy'])->whereBetween('created_at', [$start, $end]);
            if ($planId) $vq->where('plan_id', $planId);
            if ($operatorId) $vq->whereHas('batch', fn($q) => $q->where('generated_by', $operatorId));
            $vouchers = $vq->get();
        }

        $members = collect();
        if ($type === 'all' || $type === 'member') {
            $mq = Member::with('plan')
                ->where(fn($q) => $q
                    ->whereBetween('activated_at', [$start, $end])
                    ->orWhere(fn($q2) => $q2->where('expired_at', '>=', $start)->where('activated_at', '<=', $end))
                );
            if ($planId) $mq->where('plan_id', $planId);
            $members = $mq->get();
        }

        $summary = [
            'total_voucher_active'  => $vouchers->where('status', 'active')->count(),
            'total_voucher_expired' => $vouchers->where('status', 'expired')->count(),
            'total_member_active'   => $members->where('status', 'active')->count(),
            'total_revenue'         => $vouchers->sum('price_snapshot') + $members->sum('price_snapshot'),
        ];

        $pdf = Pdf::loadView('reports.monthly-pdf', compact(
            'vouchers', 'members', 'summary', 'month', 'year', 'type', 'start', 'end'
        ))->setPaper('a4', 'landscape');

        $filename = 'laporan-' . str_pad($month, 2, '0', STR_PAD_LEFT) . '-' . $year . '.pdf';
        return $pdf->download($filename);
    }
}
