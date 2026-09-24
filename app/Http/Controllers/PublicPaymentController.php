<?php

namespace App\Http\Controllers;

use App\Models\PaymentAttempt;
use Illuminate\Http\Request;

class PublicPaymentController extends Controller
{
    public function show(Request $request, string $token)
    {
        $attempt = PaymentAttempt::with(['invoice.member.plan'])
            ->where('public_token_hash', hash('sha256', $token))
            ->firstOrFail();

        abort_if($attempt->public_token_expires_at?->isPast(), 410, 'Tautan pembayaran sudah kedaluwarsa.');
        abort_if($attempt->status === 'pending' && $attempt->expires_at?->isPast(), 410, 'Instruksi pembayaran sudah kedaluwarsa.');

        return view('pay.show', compact('attempt'));
    }
}
