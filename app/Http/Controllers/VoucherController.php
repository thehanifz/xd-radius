<?php

namespace App\Http\Controllers;

use App\Models\Plan;
use App\Models\Voucher;
use App\Services\VoucherService;
use Illuminate\Http\Request;

class VoucherController extends Controller
{
    public function __construct(protected VoucherService $service) {}

    public function index(Request $request)
    {
        $query = Voucher::with('plan');

        if ($search = $request->search) {
            $query->where(function ($q) use ($search) {
                $q->where('username', 'ilike', "%{$search}%")
                  ->orWhere('batch', 'ilike', "%{$search}%");
            });
        }
        if ($status = $request->status) {
            $query->where('status', $status);
        }
        if ($planId = $request->plan_id) {
            $query->where('plan_id', $planId);
        }
        if ($batch = $request->batch) {
            $query->where('batch', $batch);
        }

        $vouchers = $query->orderByDesc('created_at')->paginate(50)->withQueryString();
        $plans    = Plan::active()->orderBy('name')->get();
        $batches  = Voucher::select('batch')->distinct()->orderByDesc('batch')->pluck('batch');

        return view('vouchers.index', compact('vouchers', 'plans', 'batches'));
    }

    public function create()
    {
        $plans = Plan::active()->orderBy('name')->get();
        return view('vouchers.create', compact('plans'));
    }

    public function generate(Request $request)
    {
        $data = $request->validate([
            'plan_id'  => 'required|exists:plans,id',
            'quantity' => 'required|integer|min:1|max:500',
            'prefix'   => 'nullable|string|max:10',
        ]);

        $batch    = $this->service->generateBatch($data);
        $vouchers = Voucher::where('batch', $batch)->with('plan')->get();
        $plans    = Plan::active()->orderBy('name')->get();

        return view('vouchers.create', compact('plans', 'vouchers', 'batch'))
            ->with('success', count($vouchers) . ' voucher berhasil digenerate. Batch: ' . $batch);
    }

    public function preview(Request $request)
    {
        $batch    = $request->batch;
        $vouchers = Voucher::where('batch', $batch)->with('plan')->get();
        return view('vouchers.preview', compact('vouchers', 'batch'));
    }

    public function print(Request $request, string $batch)
    {
        $vouchers = Voucher::where('batch', $batch)->with('plan')->get();

        $type = $request->get('type', 'a4'); // a4 | thermal
        $view = $type === 'thermal'
            ? 'vouchers.print-thermal'
            : 'vouchers.print';

        return view($view, compact('vouchers', 'batch'));
    }

    public function show(Voucher $voucher)
    {
        $voucher->load('plan');
        return view('vouchers.show', compact('voucher'));
    }
}
