<?php

namespace App\Http\Controllers;

use App\Models\BillingInvoice;
use App\Models\Member;
use App\Services\BillingService;
use App\Services\PaymentService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;

class BillingController extends Controller
{
    public function __construct(
        protected BillingService $billing,
        protected PaymentService $payments,
    ) {}

    /**
     * Daftar semua invoice (global, bisa filter per member / status).
     */
    public function index(Request $request)
    {
        $query = BillingInvoice::with(['member.plan'])->latest();

        if ($request->filled('member_id')) {
            $query->where('member_id', $request->member_id);
        }
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        // Mark overdue on-the-fly
        $this->billing->markOverdue();

        $invoices = $query->paginate(20)->withQueryString();
        $members  = Member::orderBy('username')->get(['id', 'username']);

        $stats = [
            'pending' => BillingInvoice::where('status', 'pending')->count(),
            'overdue' => BillingInvoice::where('status', 'overdue')->count(),
            'paid'    => BillingInvoice::where('status', 'paid')->count(),
        ];

        return view('billing.index', compact('invoices', 'members', 'stats'));
    }

    /**
     * Form buat invoice manual untuk member.
     */
    public function create(Request $request)
    {
        $member  = Member::with('plan')->findOrFail($request->member_id);
        return view('billing.create', compact('member'));
    }

    /**
     * Simpan invoice baru.
     */
    public function store(Request $request)
    {
        $data = $request->validate([
            'member_id' => ['required', 'exists:members,id'],
            'amount'    => ['required', 'integer', 'min:1'],
            'due_date'  => ['required', 'date'],
            'notes'     => ['nullable', 'string', 'max:500'],
        ]);

        $member  = Member::findOrFail($data['member_id']);
        $invoice = $this->billing->createInvoice($member, $data);

        return redirect()->route('billing.show', $invoice)
            ->with('success', "Invoice untuk {$member->username} berhasil dibuat.");
    }

    /**
     * Detail invoice + riwayat pembayaran.
     */
    public function show(BillingInvoice $billing)
    {
        $billing->load(['member.plan', 'payments', 'paymentAttempts', 'member.paymentAccounts']);
        return view('billing.show', ['invoice' => $billing]);
    }

    /**
     * Form catat pembayaran.
     */
    public function payForm(BillingInvoice $billing)
    {
        $billing->load('member');
        return view('billing.pay', ['invoice' => $billing]);
    }

    /**
     * Simpan pembayaran manual.
     */
    public function pay(Request $request, BillingInvoice $billing)
    {
        $data = $request->validate([
            'amount'         => ['required', 'integer', 'min:1'],
            'payment_method' => ['required', 'in:cash,transfer,qris'],
            'paid_at'        => ['nullable', 'date'],
            'notes'          => ['nullable', 'string', 'max:500'],
            'renew'          => ['nullable', 'boolean'],
        ]);

        if ($request->boolean('renew')) {
            $this->billing->renewMember($billing->member, $billing, $data);
            return redirect()->route('members.show', $billing->member)
                ->with('success', "Member {$billing->member->username} berhasil diperpanjang.");
        }

        $this->billing->recordPayment($billing, $data);
        return redirect()->route('billing.show', $billing)
            ->with('success', 'Pembayaran berhasil dicatat.');
    }


    public function createDokuPayment(Request $request, BillingInvoice $billing)
    {
        $data = $request->validate([
            'method' => ['required', 'in:va,qris'],
        ]);

        try {
            $attempt = $this->payments->createAttempt($billing, $data['method']);
        } catch (\Throwable $e) {
            report($e);
            return back()->with('error', $e->getMessage());
        }

        $url = $attempt->public_token
            ? route('public.payment.show', ['token' => $attempt->public_token])
            : null;

        return redirect()->route('billing.show', $billing)
            ->with('success', 'Instruksi pembayaran berhasil dibuat.')
            ->with('payment_url', $url);
    }

    public function createMemberVa(Request $request, Member $member)
    {
        $data = $request->validate([
            'bank' => ['required', 'string', 'exists:doku_va_channels,code'],
        ]);

        try {
            $account = $this->payments->createReusableVa($member, $data['bank']);
        } catch (\Throwable $e) {
            report($e);
            return back()->with('error', $e->getMessage());
        }

        return back()->with('success', 'Virtual Account DOKU berhasil dibuat: ' . $account->account_number);
    }

    /**
     * Batalkan invoice.
     */
    public function cancel(BillingInvoice $billing)
    {
        $this->billing->updateStatus($billing, 'cancelled');
        return back()->with('success', 'Invoice dibatalkan.');
    }

    /**
     * Download PDF invoice.
     */
    public function pdf(BillingInvoice $billing)
    {
        $billing->load(['member.plan', 'payments', 'paymentAttempts', 'member.paymentAccounts']);
        $pdf = Pdf::loadView('billing.pdf', ['invoice' => $billing]);
        $filename = 'invoice-' . $billing->member->username
            . '-' . $billing->period_start->format('Y-m')
            . '.pdf';
        return $pdf->download($filename);
    }
}
