<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Pembayaran Invoice #{{ $attempt->invoice_id }} — XD Radius</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-slate-50 flex items-center justify-center p-4">
<div class="w-full max-w-lg">
    <div class="card overflow-hidden">
        <div class="card-body p-6 sm:p-8">
            <div class="text-center mb-6">
                <p class="text-xs uppercase tracking-widest font-semibold text-indigo-600">XD Radius</p>
                <h1 class="text-2xl font-bold text-slate-900 mt-1">Pembayaran Invoice</h1>
                <p class="text-sm text-slate-500 mt-1">Invoice #{{ $attempt->invoice_id }}</p>
            </div>

            <div class="rounded-2xl bg-slate-50 border border-slate-200 p-5 space-y-3">
                <div class="flex justify-between gap-4">
                    <span class="text-sm text-slate-500">Member</span>
                    <span class="text-sm font-semibold text-slate-800">{{ $attempt->invoice->member->username }}</span>
                </div>
                <div class="flex justify-between gap-4">
                    <span class="text-sm text-slate-500">Paket</span>
                    <span class="text-sm font-semibold text-slate-800">{{ $attempt->invoice->member->plan->name ?? '-' }}</span>
                </div>
                <div class="border-t border-slate-200 pt-3 flex justify-between gap-4">
                    <span class="text-sm text-slate-500">Total</span>
                    <span class="text-xl font-bold text-slate-900">{{ $attempt->amount_label }}</span>
                </div>
            </div>

            @if($attempt->status === 'paid')
                <div class="mt-6 rounded-xl bg-emerald-50 border border-emerald-200 p-4 text-center">
                    <p class="font-semibold text-emerald-700">Pembayaran sudah diterima.</p>
                    <p class="text-sm text-emerald-600 mt-1">Terima kasih. Status layanan akan diperbarui oleh sistem.</p>
                </div>
            @elseif($attempt->status !== 'pending')
                <div class="mt-6 rounded-xl bg-red-50 border border-red-200 p-4 text-center">
                    <p class="font-semibold text-red-700">Pembayaran tidak dapat diproses.</p>
                    <p class="text-sm text-red-600 mt-1">Status: {{ strtoupper($attempt->status) }}</p>
                </div>
            @else
                <div class="mt-6 text-center">
                    @if($attempt->channel === 'qris' && $attempt->qr_content)
                        <p class="text-sm font-semibold text-slate-700 mb-3">Scan QRIS untuk membayar</p>
                        <div class="inline-block rounded-xl bg-white border border-slate-200 p-4">
                            <div id="qris-placeholder" class="w-56 h-56 flex items-center justify-center text-xs text-slate-400 text-center">
                                QRIS content tersedia.<br>QR renderer akan digunakan pada tahap UI QRIS.
                            </div>
                        </div>
                        <p class="text-xs text-slate-400 mt-3 break-all">{{ $attempt->qr_content }}</p>
                    @elseif(str_starts_with($attempt->channel, 'va_'))
                        <p class="text-sm font-semibold text-slate-700 mb-2">Virtual Account</p>
                        @php
                            $account = $attempt->invoice->member->paymentAccounts->where('status', 'active')->sortByDesc('is_default')->first();
                        @endphp
                        @if($account)
                            <div class="rounded-xl bg-indigo-50 border border-indigo-100 p-5">
                                <p class="text-xs uppercase tracking-wide text-indigo-500 font-semibold">{{ $account->bank }}</p>
                                <p class="text-2xl font-bold tracking-wider text-slate-900 mt-1">{{ $account->account_number }}</p>
                                <p class="text-xs text-slate-500 mt-2">Gunakan nomor VA tersebut dan bayar sesuai nominal invoice.</p>
                            </div>
                        @endif
                    @else
                        <p class="text-sm text-slate-500">Ikuti instruksi pembayaran pada metode yang dipilih.</p>
                    @endif
                </div>
            @endif
        </div>
    </div>
    <p class="text-center text-xs text-slate-400 mt-4">Tautan pembayaran ini bersifat privat. Jangan dibagikan ke pihak lain.</p>
</div>
</body>
</html>
