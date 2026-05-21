<?php

namespace App\Http\Controllers;

use App\Http\Requests\GenerateVoucherRequest;
use App\Models\Plan;
use App\Models\Voucher;
use App\Models\VoucherBatch;
use App\Services\VoucherService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class VoucherController extends Controller
{
    public function __construct(protected VoucherService $voucherService) {}

    /**
     * Daftar voucher dengan filter
     */
    public function index(Request $request)
    {
        $batches = VoucherBatch::with('plan')
            ->orderByDesc('generated_at')
            ->get();

        $query = Voucher::with(['plan', 'batch'])
            ->orderByDesc('created_at');

        if ($request->filled('batch_id')) {
            $query->where('batch_id', $request->batch_id);
        }
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }
        if ($request->filled('search')) {
            $query->where('username', 'ilike', '%' . $request->search . '%');
        }

        $vouchers = $query->paginate(50)->withQueryString();

        return view('vouchers.index', compact('vouchers', 'batches'));
    }

    /**
     * Form generate voucher
     */
    public function create()
    {
        $plans = Plan::active()->voucher()->orderBy('name')->get();
        return view('vouchers.create', compact('plans'));
    }

    /**
     * Proses generate voucher
     */
    public function generate(GenerateVoucherRequest $request)
    {
        $batch = $this->voucherService->generate([
            ...$request->validated(),
            'generated_by' => Auth::guard('app')->id(),
        ]);

        return redirect()
            ->route('vouchers.index', ['batch_id' => $batch->id])
            ->with('success', "Batch {$batch->batch_code} berhasil dibuat — {$batch->quantity} voucher.");
    }

    /**
     * Preview format username (AJAX)
     */
    public function preview(Request $request)
    {
        $request->validate([
            'prefix'       => ['nullable', 'string', 'max:10'],
            'length'       => ['required', 'integer', 'min:4', 'max:20'],
            'charset_mode' => ['required', 'string'],
        ]);

        $examples = [];
        for ($i = 0; $i < 3; $i++) {
            $examples[] = VoucherService::previewExample(
                $request->prefix ?? '',
                (int) $request->length,
                $request->charset_mode
            );
        }

        return response()->json(['examples' => $examples]);
    }
}
