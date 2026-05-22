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
    public function __construct(protected VoucherService $service) {}

    /**
     * Daftar voucher dengan filter.
     */
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
        $batches  = VoucherBatch::with('plan')->orderByDesc('generated_at')->get();

        return view('vouchers.index', compact('vouchers', 'plans', 'batches'));
    }

    /**
     * Form generate voucher baru.
     */
    public function create()
    {
        $plans = Plan::active()->voucher()->orderBy('name')->get();
        return view('vouchers.create', compact('plans'));
    }

    /**
     * Proses generate voucher (pakai GenerateVoucherRequest untuk validasi lengkap).
     */
    public function generate(GenerateVoucherRequest $request)
    {
        $batch = $this->service->generateBatch([
            ...$request->validated(),
            'generated_by' => Auth::guard('app')->id(),
        ]);

        return redirect()
            ->route('vouchers.index', ['batch_id' => $batch->id])
            ->with('success', "Batch {$batch->batch_code} berhasil dibuat — {$batch->quantity} voucher.");
    }

    /**
     * Preview contoh format username (AJAX — dipanggil dari Alpine.js di form generate).
     * Route: GET /vouchers/preview-format
     */
    public function previewFormat(Request $request)
    {
        $request->validate([
            'prefix'       => ['nullable', 'string', 'max:10'],
            'length'       => ['required', 'integer', 'min:4', 'max:20'],
            'charset_mode' => ['required', 'string', 'in:numeric,uppercase,lowercase,mixed'],
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

    /**
     * Halaman print voucher berdasarkan batch ID.
     * Route: GET /vouchers/batch/{batch}/print
     */
    public function print(Request $request, string $batch)
    {
        // $batch bisa berupa ID (integer) atau batch_code (string)
        $voucherBatch = is_numeric($batch)
            ? VoucherBatch::with('plan')->findOrFail($batch)
            : VoucherBatch::with('plan')->where('batch_code', $batch)->firstOrFail();

        $vouchers  = Voucher::where('batch_id', $voucherBatch->id)->with('plan')->get();
        $batchCode = $voucherBatch->batch_code;

        $type = $request->get('type', 'a4');
        $view = $type === 'thermal' ? 'vouchers.print-thermal' : 'vouchers.print';

        return view($view, compact('vouchers', 'batch', 'batchCode', 'voucherBatch'));
    }

    /**
     * Detail satu voucher.
     */
    public function show(Voucher $voucher)
    {
        $voucher->load('plan', 'batch');
        return view('vouchers.show', compact('voucher'));
    }
}
