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
        $this->applyIndexFilters($query, $request);

        $vouchers = $query->orderByDesc('created_at')->paginate(50)->withQueryString();
        $plans    = Plan::active()->orderBy('name')->get();
        $batches  = VoucherBatch::with('plan')->withCount('vouchers')->orderByDesc('generated_at')->get();

        $voucherStats = $this->currentVoucherStats();

        return view('vouchers.index', compact('vouchers', 'plans', 'batches', 'voucherStats'));
    }

    /**
     * Lightweight polling endpoint for live voucher status/KPI updates.
     *
     * The UI derives expiry from expired_at so it does not have to wait for
     * the queue worker to persist status=expired before showing the correct
     * state to the operator.
     */
    public function realtime(Request $request)
    {
        $now = now();
        $voucherStats = $this->currentVoucherStats($now);

        $query = Voucher::query()->select(['id', 'status', 'first_login_at', 'expired_at']);
        $this->applyIndexFilters($query, $request);

        $vouchers = $query->orderByDesc('created_at')->paginate(50);

        return response()->json([
            'server_time' => $now->toIso8601String(),
            'stats' => $voucherStats,
            'vouchers' => $vouchers->getCollection()->map(function (Voucher $voucher) use ($now) {
                return [
                    'id' => $voucher->id,
                    'status' => $this->effectiveVoucherStatus($voucher, $now),
                ];
            })->values(),
        ]);
    }

    private function currentVoucherStats($now = null): array
    {
        $now ??= now();
        $statsBase = Voucher::query();

        return [
            'total' => (clone $statsBase)->count(),
            'available' => (clone $statsBase)
                ->where('status', 'active')
                ->whereNull('first_login_at')
                ->count(),
            'used' => (clone $statsBase)
                ->whereIn('status', ['active', 'used'])
                ->whereNotNull('first_login_at')
                ->where(function ($query) use ($now) {
                    $query->whereNull('expired_at')->orWhere('expired_at', '>', $now);
                })
                ->count(),
            'expired' => (clone $statsBase)
                ->where(function ($query) use ($now) {
                    $query->where('status', 'expired')
                        ->orWhere(function ($q) use ($now) {
                            $q->whereNotNull('first_login_at')
                                ->whereNotNull('expired_at')
                                ->where('expired_at', '<=', $now);
                        });
                })
                ->count(),
            'isolated' => (clone $statsBase)->where('status', 'isolated')->count(),
        ];
    }

    /**
     * Keep index and realtime endpoint filters identical.
     */
    private function applyIndexFilters($query, Request $request): void
    {
        if ($search = $request->search) {
            $query->where('username', 'ilike', "%{$search}%");
        }
        if ($status = $request->status) {
            $now = now();

            if ($status === 'expired') {
                $query->where(function ($q) use ($now) {
                    $q->where('status', 'expired')
                        ->orWhere(function ($q2) use ($now) {
                            $q2->whereIn('status', ['active', 'used'])
                                ->whereNotNull('first_login_at')
                                ->whereNotNull('expired_at')
                                ->where('expired_at', '<=', $now);
                        });
                });
            } elseif ($status === 'used') {
                $query->whereIn('status', ['active', 'used'])
                    ->whereNotNull('first_login_at')
                    ->where(function ($q) use ($now) {
                        $q->whereNull('expired_at')->orWhere('expired_at', '>', $now);
                    });
            } elseif ($status === 'active') {
                $query->where('status', 'active')
                    ->where(function ($q) use ($now) {
                        $q->whereNull('first_login_at')
                            ->orWhereNull('expired_at')
                            ->orWhere('expired_at', '>', $now);
                    });
            } else {
                $query->where('status', $status);
            }
        }
        if ($planId = $request->plan_id) {
            $query->where('plan_id', $planId);
        }
        if ($batchId = $request->batch_id) {
            $query->where('batch_id', $batchId);
        }
    }

    private function effectiveVoucherStatus(Voucher $voucher, $now): string
    {
        if ($voucher->expired_at !== null && $voucher->first_login_at !== null && $voucher->expired_at->lte($now)) {
            return 'expired';
        }

        if (in_array($voucher->status, ['isolated', 'inactive', 'expired'], true)) {
            return $voucher->status;
        }

        if ($voucher->first_login_at !== null) {
            return 'used';
        }

        return 'active';
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
     * Print voucher yang dipilih dari daftar, tanpa harus mencetak seluruh batch.
     */
    public function printSelected(Request $request)
    {
        $ids = collect($request->input('ids', []))
            ->map(fn ($id) => (int) $id)
            ->filter()
            ->unique()
            ->take(100)
            ->values();

        if ($ids->isEmpty()) {
            return redirect()->route('vouchers.index')->with('warning', 'Pilih minimal satu voucher untuk dicetak.');
        }

        $vouchers = Voucher::with(['plan', 'batch'])
            ->whereIn('id', $ids)
            ->orderBy('id')
            ->get();

        if ($vouchers->isEmpty()) {
            return redirect()->route('vouchers.index')->with('warning', 'Voucher yang dipilih tidak ditemukan.');
        }

        $type = $request->string('type')->toString() === 'thermal' ? 'thermal' : 'a4';
        $batchLabel = $vouchers->pluck('batch.batch_code')->filter()->unique()->implode(', ');
        $batchCode = $batchLabel ?: 'Selected';
        $voucherBatch = $vouchers->first()->batch;
        $view = $type === 'thermal' ? 'vouchers.print-thermal' : 'vouchers.print';

        return view($view, compact('vouchers', 'batchCode', 'voucherBatch'));
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
