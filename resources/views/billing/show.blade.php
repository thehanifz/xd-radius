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
                    <p class="text-xs text-slate-400 uppercase font-semibold">Jatuh Tempo</p>
                    <p class="text-sm font-medium text-slate-700 mt-0.5">{{ $invoice->due_date->format('d M Y') }}</p>
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
