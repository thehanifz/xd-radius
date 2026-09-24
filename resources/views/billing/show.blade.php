@extends('layouts.app')
@section('title', 'Detail Invoice')

@section('topbar-actions')
    <a href="{{ route('billing.pdf', $invoice) }}" class="btn-secondary" target="_blank">
        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/></svg>
        Download PDF
    </a>
    @if(in_array($invoice->status, ['pending','overdue']))
    <a href="{{ route('billing.pay.form', $invoice) }}" class="btn-primary">
        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="12" y1="1" x2="12" y2="23"/><path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/></svg>
        Catat Bayar
    </a>
    @endif
@endsection

@section('content')
<div class="max-w-3xl space-y-5">

    @if(session('payment_url'))
    <div class="card border border-indigo-200 bg-indigo-50">
        <div class="card-body">
            <p class="text-sm font-semibold text-indigo-800">Link pembayaran berhasil dibuat</p>
            <div class="mt-2 flex flex-col sm:flex-row gap-2">
                <input readonly value="{{ session('payment_url') }}" class="form-input flex-1 bg-white text-xs" onclick="this.select()">
                <a href="{{ session('payment_url') }}" target="_blank" class="btn-primary whitespace-nowrap">Buka Pembayaran</a>
            </div>
        </div>
    </div>
    @endif


    {{-- Header invoice --}}
    <div class="card">
        <div class="card-body">
            <div class="flex items-start justify-between gap-4">
                <div>
                    <p class="text-xs text-slate-500 uppercase tracking-wide font-semibold mb-1">Invoice #{{ $invoice->id }}</p>
                    <h2 class="text-xl font-bold text-slate-800">{{ $invoice->member->username }}</h2>
                    <p class="text-sm text-slate-500 mt-0.5">{{ $invoice->member->plan->name ?? '-' }}</p>
                </div>
                <span class="badge {{ $invoice->status_badge_class }} text-sm px-3 py-1">{{ $invoice->status_label }}</span>
            </div>
            <div class="grid grid-cols-2 sm:grid-cols-4 gap-4 mt-5 pt-5 border-t border-slate-100">
                <div>
                    <p class="text-xs text-slate-400 uppercase font-semibold">Periode</p>
                    <p class="text-sm font-medium text-slate-700 mt-0.5">
                        {{ $invoice->period_start->format('d M Y') }} – {{ $invoice->period_end->format('d M Y') }}
                    </p>
                </div>
                <div>
                    <p class="text-xs text-slate-400 uppercase font-semibold">Nominal</p>
                    <p class="text-lg font-bold text-slate-800 mt-0.5 tabular-nums">{{ $invoice->amount_label }}</p>
                </div>
                <div>
                    <p class="text-xs text-slate-400 uppercase font-semibold">{{ $invoice->status === 'paid' ? 'Dibayar' : 'Jatuh Tempo Bayar' }}</p>
                    <p class="text-sm font-medium text-slate-700 mt-0.5">
                        @if($invoice->status === 'paid' && $invoice->payments->isNotEmpty())
                            {{ $invoice->payments->sortByDesc('paid_at')->first()->paid_at->format('d M Y') }}
                        @else
                            {{ $invoice->due_date->format('d M Y') }}
                        @endif
                    </p>
                </div>
                <div>
                    <p class="text-xs text-slate-400 uppercase font-semibold">Dibuat</p>
                    <p class="text-sm font-medium text-slate-700 mt-0.5">{{ $invoice->created_at->format('d M Y') }}</p>
                </div>
            </div>
            @if($invoice->notes)
            <p class="mt-4 text-sm text-slate-500 bg-slate-50 rounded-lg px-4 py-2.5">{{ $invoice->notes }}</p>
            @endif
        </div>
    </div>

    {{-- DOKU payment --}}
    @if(in_array($invoice->status, ['pending','overdue']))
    <div class="card">
        <div class="card-header">
            <h3 class="card-title">Pembayaran DOKU</h3>
        </div>
        <div class="card-body">
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                <form method="POST" action="{{ route('billing.doku-payment', $invoice) }}">
                    @csrf
                    <input type="hidden" name="method" value="va">
                    <button class="w-full btn-secondary" type="submit">Buat / Perbarui VA</button>
                </form>
                <form method="POST" action="{{ route('billing.doku-payment', $invoice) }}">
                    @csrf
                    <input type="hidden" name="method" value="qris">
                    <button class="w-full btn-primary" type="submit">Generate QRIS</button>
                </form>
            </div>
            <p class="text-xs text-slate-400 mt-3">Pembayaran DOKU tidak memerlukan login member.</p>
        </div>
    </div>
    @endif

    {{-- Payment attempts --}}
    <div class="card overflow-hidden">
        <div class="card-header">
            <h3 class="card-title">Payment Attempts</h3>
        </div>
        @if($invoice->paymentAttempts->isEmpty())
            <div class="py-8 text-center text-sm text-slate-400">Belum ada attempt pembayaran gateway.</div>
        @else
            <div class="table-scroll">
                <table class="w-full text-sm">
                    <thead class="bg-slate-50 border-b border-slate-200">
                        <tr>
                            <th class="text-left px-5 py-3 text-xs font-semibold text-slate-500 uppercase">Channel</th>
                            <th class="text-left px-5 py-3 text-xs font-semibold text-slate-500 uppercase">Status</th>
                            <th class="text-left px-5 py-3 text-xs font-semibold text-slate-500 uppercase">Provisioning</th>
                            <th class="text-left px-5 py-3 text-xs font-semibold text-slate-500 uppercase">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                    @foreach($invoice->paymentAttempts as $attempt)
                        <tr>
                            <td class="px-5 py-3 font-medium text-slate-700">{{ strtoupper(str_replace('_',' ', $attempt->channel)) }}</td>
                            <td class="px-5 py-3"><span class="badge badge-blue">{{ strtoupper($attempt->status) }}</span></td>
                            <td class="px-5 py-3 text-xs text-slate-500">{{ strtoupper($attempt->provisioning_status) }}</td>
                            <td class="px-5 py-3">
                                @if($attempt->public_token_encrypted)
                                    @php($publicUrl = route('public.payment.show', ['token' => decrypt($attempt->public_token_encrypted)]))
                                    <a href="{{ $publicUrl }}" target="_blank" class="text-indigo-600 hover:text-indigo-800 font-medium">Buka</a>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>

    {{-- Riwayat Pembayaran --}}
    <div class="card overflow-hidden">
        <div class="card-header">
            <h3 class="card-title">Riwayat Pembayaran</h3>
        </div>
        @if($invoice->payments->isEmpty())
        <div class="py-10 flex flex-col items-center text-center">
            <svg class="text-slate-300 mb-2" width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><rect x="2" y="5" width="20" height="14" rx="2"/><line x1="2" y1="10" x2="22" y2="10"/></svg>
            <p class="text-slate-400 text-sm">Belum ada pembayaran</p>
        </div>
        @else
        <div class="table-scroll">
<table class="w-full text-sm">
            <thead class="bg-slate-50 border-b border-slate-200">
                <tr>
                    <th class="text-left px-5 py-3 text-xs font-semibold text-slate-500 uppercase">Tanggal</th>
                    <th class="text-left px-5 py-3 text-xs font-semibold text-slate-500 uppercase">Nominal</th>
                    <th class="text-left px-5 py-3 text-xs font-semibold text-slate-500 uppercase">Metode</th>
                    <th class="text-left px-5 py-3 text-xs font-semibold text-slate-500 uppercase">Catatan</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @foreach($invoice->payments as $pay)
                <tr class="hover:bg-slate-50">
                    <td class="px-5 py-3 tabular-nums text-slate-600">{{ $pay->paid_at->format('d M Y H:i') }}</td>
                    <td class="px-5 py-3 font-semibold text-slate-800 tabular-nums">{{ $pay->amount_label }}</td>
                    <td class="px-5 py-3">
                        <span class="badge badge-blue">{{ $pay->method_label }}</span>
                    </td>
                    <td class="px-5 py-3 text-slate-400 text-xs">{{ $pay->notes ?? '-' }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
</div>
        @endif
    </div>

    {{-- Batalkan invoice --}}
    @if(in_array($invoice->status, ['pending','overdue']))
    <div class="flex justify-end">
        <form method="POST" action="{{ route('billing.cancel', $invoice) }}"
              onsubmit="return confirm('Batalkan invoice ini?')">
            @csrf @method('PATCH')
            <button type="submit" class="text-sm text-red-500 hover:text-red-700 font-medium">Batalkan Invoice</button>
        </form>
    </div>
    @endif

</div>
@endsection
