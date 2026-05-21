@extends('layouts.app')
@section('title', 'Billing & Invoice')

@section('topbar-actions')
    {{-- Tombol tambah invoice ada di halaman member --}}
@endsection

@section('content')
<div class="space-y-5">

    {{-- Stats --}}
    <div class="grid grid-cols-3 gap-4">
        @foreach([
            ['label'=>'Menunggu','value'=>$stats['pending'],'color'=>'text-amber-600','bg'=>'bg-amber-50'],
            ['label'=>'Jatuh Tempo','value'=>$stats['overdue'],'color'=>'text-red-600','bg'=>'bg-red-50'],
            ['label'=>'Lunas','value'=>$stats['paid'],'color'=>'text-green-600','bg'=>'bg-green-50'],
        ] as $s)
        <div class="card">
            <div class="card-body py-4 flex items-center gap-3">
                <div class="w-10 h-10 rounded-xl {{ $s['bg'] }} flex items-center justify-center">
                    <span class="text-lg font-bold {{ $s['color'] }}">{{ $s['value'] }}</span>
                </div>
                <p class="text-sm font-medium text-slate-600">{{ $s['label'] }}</p>
            </div>
        </div>
        @endforeach
    </div>

    {{-- Filter --}}
    <div class="card">
        <div class="card-body py-3">
            <form method="GET" class="flex flex-wrap gap-3 items-center">
                <select name="member_id" class="form-input w-48">
                    <option value="">Semua Member</option>
                    @foreach($members as $m)
                        <option value="{{ $m->id }}" @selected(request('member_id') == $m->id)>{{ $m->username }}</option>
                    @endforeach
                </select>
                <select name="status" class="form-input w-40">
                    <option value="">Semua Status</option>
                    <option value="pending" @selected(request('status')==='pending')>Menunggu</option>
                    <option value="paid" @selected(request('status')==='paid')>Lunas</option>
                    <option value="overdue" @selected(request('status')==='overdue')>Jatuh Tempo</option>
                    <option value="cancelled" @selected(request('status')==='cancelled')>Dibatalkan</option>
                </select>
                <button type="submit" class="btn-secondary">Filter</button>
                @if(request()->hasAny(['member_id','status']))
                    <a href="{{ route('billing.index') }}" class="text-sm text-slate-500 hover:text-slate-700">Reset</a>
                @endif
            </form>
        </div>
    </div>

    {{-- Table --}}
    <div class="card overflow-hidden">
        @if($invoices->isEmpty())
        <div class="py-16 flex flex-col items-center text-center">
            <svg class="text-slate-300 mb-3" width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/><polyline points="10 9 9 9 8 9"/></svg>
            <p class="text-slate-500 font-medium">Belum ada invoice</p>
            <p class="text-slate-400 text-sm mt-1">Invoice akan muncul setelah dibuat untuk member.</p>
        </div>
        @else
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead class="bg-slate-50 border-b border-slate-200">
                    <tr>
                        <th class="text-left px-5 py-3 text-xs font-semibold text-slate-500 uppercase tracking-wide">Member</th>
                        <th class="text-left px-5 py-3 text-xs font-semibold text-slate-500 uppercase tracking-wide">Periode</th>
                        <th class="text-left px-5 py-3 text-xs font-semibold text-slate-500 uppercase tracking-wide">Nominal</th>
                        <th class="text-left px-5 py-3 text-xs font-semibold text-slate-500 uppercase tracking-wide">Jatuh Tempo</th>
                        <th class="text-left px-5 py-3 text-xs font-semibold text-slate-500 uppercase tracking-wide">Status</th>
                        <th class="px-5 py-3"></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @foreach($invoices as $invoice)
                    <tr class="hover:bg-slate-50 transition-colors">
                        <td class="px-5 py-3.5">
                            <a href="{{ route('members.show', $invoice->member) }}" class="font-medium text-slate-800 hover:text-indigo-600">{{ $invoice->member->username }}</a>
                            <p class="text-xs text-slate-400">{{ $invoice->member->plan->name ?? '-' }}</p>
                        </td>
                        <td class="px-5 py-3.5 text-slate-600 tabular-nums text-xs">
                            {{ $invoice->period_start->format('d M Y') }}
                            <span class="text-slate-400">–</span>
                            {{ $invoice->period_end->format('d M Y') }}
                        </td>
                        <td class="px-5 py-3.5 font-semibold text-slate-800 tabular-nums">{{ $invoice->amount_label }}</td>
                        <td class="px-5 py-3.5 tabular-nums text-slate-600 text-xs">{{ $invoice->due_date->format('d M Y') }}</td>
                        <td class="px-5 py-3.5">
                            <span class="badge {{ $invoice->status_badge_class }}">{{ $invoice->status_label }}</span>
                        </td>
                        <td class="px-5 py-3.5">
                            <div class="flex items-center gap-2 justify-end">
                                <a href="{{ route('billing.show', $invoice) }}" class="text-slate-400 hover:text-indigo-600 transition-colors" title="Detail">
                                    <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
                                </a>
                                @if(in_array($invoice->status, ['pending','overdue']))
                                <a href="{{ route('billing.pay.form', $invoice) }}" class="text-slate-400 hover:text-green-600 transition-colors" title="Catat Bayar">
                                    <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="12" y1="1" x2="12" y2="23"/><path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/></svg>
                                </a>
                                @endif
                                <a href="{{ route('billing.pdf', $invoice) }}" class="text-slate-400 hover:text-red-600 transition-colors" title="Download PDF">
                                    <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/></svg>
                                </a>
                            </div>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        @if($invoices->hasPages())
        <div class="px-5 py-3 border-t border-slate-100">{{ $invoices->links() }}</div>
        @endif
        @endif
    </div>

</div>
@endsection
