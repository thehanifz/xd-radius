<?php

namespace App\Http\Controllers;

use App\Models\Plan;
use App\Models\Voucher;
use App\Models\VoucherBatch;
use App\Services\VoucherService;
use Illuminate\Http\Request;

class VoucherController extends Controller
{
    public function __construct(protected VoucherService $service) {}

    public function index(Request $request)
    {
        $query = Voucher::with(['batch', 'plan']);

        if ($search = $request->search) {
            $query->where('username', 'ilike', "%{$search}%");
        }
        if ($status = $request->status) {
            $query->where('status', $status);
        }
        if ($planId = $request->plan_id) {
            $query->where('plan_id', $planId);
        }
        if ($batchId = $request->batch_id) {
            $query->where('batch_id', $batchId);
        }

        $vouchers = $query->orderByDesc('created_at')->paginate(50)->withQueryString();
        $plans    = Plan::active()->orderBy('name')->get();
        $batches  = VoucherBatch::orderByDesc('created_at')->get(['id', 'batch_code', 'created_at']);

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

        $voucherBatch = $this->service->generateBatch($data);

        // generateBatch bisa return VoucherBatch object atau batch_code string
        if ($voucherBatch instanceof VoucherBatch) {
            $batchId   = $voucherBatch->id;
            $batchCode = $voucherBatch->batch_code;
        } else {
            // fallback: jika service return batch_code string
            $batchCode    = $voucherBatch;
            $voucherBatch = VoucherBatch::where('batch_code', $batchCode)->first();
            $batchId      = $voucherBatch?->id;
        }

        $vouchers = Voucher::where('batch_id', $batchId)->with('plan')->get();
        $plans    = Plan::active()->orderBy('name')->get();

        return view('vouchers.create', compact('plans', 'vouchers', 'batchId', 'batchCode'))
            ->with('success', count($vouchers) . ' voucher berhasil digenerate. Batch: ' . $batchCode);
    }

    public function preview(Request $request)
    {
        $batchId = $request->batch_id;
        $batch   = VoucherBatch::with(['plan', 'vouchers.plan'])->findOrFail($batchId);
        $vouchers = $batch->vouchers;
        return view('vouchers.preview', compact('vouchers', 'batch'));
    }

    public function print(Request $request, string $batch)
    {
        // $batch bisa berupa ID atau batch_code
        $voucherBatch = is_numeric($batch)
            ? VoucherBatch::with('plan')->findOrFail($batch)
            : VoucherBatch::with('plan')->where('batch_code', $batch)->firstOrFail();

        $vouchers = Voucher::where('batch_id', $voucherBatch->id)->with('plan')->get();
        $batchCode = $voucherBatch->batch_code;

        $type = $request->get('type', 'a4');
        $view = $type === 'thermal' ? 'vouchers.print-thermal' : 'vouchers.print';

        return view($view, compact('vouchers', 'batch', 'batchCode', 'voucherBatch'));
    }

    public function show(Voucher $voucher)
    {
        $voucher->load('plan');
        return view('vouchers.show', compact('voucher'));
    }
}
