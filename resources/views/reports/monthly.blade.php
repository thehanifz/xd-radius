@extends('layouts.app')
@section('title', 'Laporan Bulanan')

@section('topbar-actions')
<a href="{{ route('reports.pdf', request()->all()) }}" target="_blank"
   class="btn-primary flex items-center gap-2 text-sm">
    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round">
        <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/>
        <polyline points="7 10 12 15 17 10"/>
        <line x1="12" y1="15" x2="12" y2="3"/>
    </svg>
    Download PDF
</a>
@endsection

@section('content')

{{-- Filter Form --}}
<div class="card mb-6">
    <div class="p-5">
        <form method="GET" class="flex flex-wrap gap-4 items-end">
            <div>
                <label class="form-label">Bulan</label>
                <select name="month" class="form-select-sm">
                    @for($m = 1; $m <= 12; $m++)
                    <option value="{{ $m }}" @selected($m == $month)>{{ \Carbon\Carbon::create(null, $m)->translatedFormat('F') }}</option>
                    @endfor
                </select>
            </div>
            <div>
                <label class="form-label">Tahun</label>
                <select name="year" class="form-select-sm">
                    @for($y = now()->year; $y >= now()->year - 3; $y--)
                    <option value="{{ $y }}" @selected($y == $year)>{{ $y }}</option>
                    @endfor
                </select>
            </div>
            <div>
                <label class="form-label">Tipe User</label>
                <select name="type" class="form-select-sm">
                    <option value="all" @selected($type==='all')>Semua</option>
                    <option value="voucher" @selected($type==='voucher')>Voucher</option>
                    <option value="member" @selected($type==='member')>Member</option>
                </select>
            </div>
            <div>
                <label class="form-label">Paket</label>
                <select name="plan_id" class="form-select-sm">
                    <option value="">Semua Paket</option>
                    @foreach($plans as $p)
                    <option value="{{ $p->id }}" @selected($planId==$p->id)>{{ $p->name }}</option>
                    @endforeach
                </select>
            </div>
            @if(auth('app')->user()->isSuperUser())
            <div>
                <label class="form-label">Operator</label>
                <select name="operator_id" class="form-select-sm">
                    <option value="">Semua Operator</option>
                    @foreach($operators as $op)
                    <option value="{{ $op->id }}" @selected($operatorId==$op->id)>{{ $op->name }}</option>
                    @endforeach
                </select>
            </div>
            @endif
            <button type="submit" class="btn-primary-sm">Tampilkan</button>
        </form>
    </div>
</div>

{{-- Summary --}}
<div class="grid grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
    <div class="stat-card">
        <p class="stat-label">Voucher Aktif</p>
        <p class="stat-value text-green-600">{{ number_format($summary['total_voucher_active']) }}</p>
    </div>
    <div class="stat-card">
        <p class="stat-label">Voucher Expired</p>
        <p class="stat-value text-slate-400">{{ number_format($summary['total_voucher_expired']) }}</p>
    </div>
    <div class="stat-card">
        <p class="stat-label">Member Aktif</p>
        <p class="stat-value text-blue-600">{{ number_format($summary['total_member_active']) }}</p>
    </div>
    <div class="stat-card">
        <p class="stat-label">Est. Pendapatan</p>
        <p class="stat-value text-indigo-600">Rp {{ number_format($summary['total_revenue']) }}</p>
    </div>
</div>

{{-- Voucher Table --}}
@if($vouchers->isNotEmpty())
<div class="card mb-6">
    <div class="card-header">
        <h2 class="card-title">Voucher ({{ $vouchers->count() }})</h2>
    </div>
    <div class="overflow-x-auto">
        <table class="data-table">
            <thead><tr>
                <th>Batch</th>
                <th>Username</th>
                <th>Operator</th>
                <th>Paket</th>
                <th>Harga</th>
                <th>Dibuat</th>
                <th>First Login</th>
                <th>Expired</th>
                <th>Status</th>
            </tr></thead>
            <tbody>
            @foreach($vouchers as $v)
            <tr>
                <td class="font-mono text-xs text-slate-500">{{ $v->batch?->batch_code ?? '-' }}</td>
                <td class="font-mono text-sm font-semibold">{{ $v->username }}</td>
                <td class="text-slate-500 text-sm">{{ $v->batch?->generatedBy?->name ?? '-' }}</td>
                <td class="text-slate-600 text-sm">{{ $v->plan?->name ?? '-' }}</td>
                <td class="text-slate-700 text-sm">Rp {{ number_format($v->price_snapshot) }}</td>
                <td class="text-slate-400 text-xs">{{ $v->created_at?->format('d/m/Y') }}</td>
                <td class="text-slate-400 text-xs">{{ $v->first_login_at?->format('d/m/Y H:i') ?? '-' }}</td>
                <td class="text-slate-400 text-xs">{{ $v->expired_at?->format('d/m/Y') ?? '-' }}</td>
                <td><span class="badge-{{ $v->status === 'active' ? 'active' : ($v->status === 'expired' ? 'expired' : 'isolated') }} text-xs">{{ ucfirst($v->status) }}</span></td>
            </tr>
            @endforeach
            </tbody>
        </table>
    </div>
</div>
@endif

{{-- Member Table --}}
@if($members->isNotEmpty())
<div class="card">
    <div class="card-header">
        <h2 class="card-title">Member ({{ $members->count() }})</h2>
    </div>
    <div class="overflow-x-auto">
        <table class="data-table">
            <thead><tr>
                <th>Username</th>
                <th>Paket</th>
                <th>Harga</th>
                <th>Aktif Sejak</th>
                <th>Expired</th>
                <th>Tagihan Bulan Ini</th>
                <th>Status</th>
            </tr></thead>
            <tbody>
            @foreach($members as $m)
            @php $invoice = $m->invoices->first(); @endphp
            <tr>
                <td class="font-mono text-sm font-semibold">{{ $m->username }}</td>
                <td class="text-slate-600 text-sm">{{ $m->plan?->name ?? '-' }}</td>
                <td class="text-slate-700 text-sm">Rp {{ number_format($m->price_snapshot) }}</td>
                <td class="text-slate-400 text-xs">{{ $m->activated_at?->format('d/m/Y') ?? '-' }}</td>
                <td class="text-slate-400 text-xs">{{ $m->expired_at?->format('d/m/Y') ?? '-' }}</td>
                <td class="text-sm">
                    @if($invoice)
                        <span class="badge-{{ $invoice->status === 'paid' ? 'active' : ($invoice->status === 'overdue' ? 'isolated' : 'pending') }} text-xs">
                            {{ ucfirst($invoice->status) }} — Rp {{ number_format($invoice->amount) }}
                        </span>
                    @else
                        <span class="text-slate-300">-</span>
                    @endif
                </td>
                <td><span class="badge-{{ $m->status === 'active' ? 'active' : ($m->status === 'isolated' ? 'isolated' : 'expired') }} text-xs">{{ ucfirst($m->status) }}</span></td>
            </tr>
            @endforeach
            </tbody>
        </table>
    </div>
</div>
@endif

@if($vouchers->isEmpty() && $members->isEmpty())
<div class="card p-16 text-center">
    <svg class="mx-auto mb-3 w-12 h-12 text-slate-200" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/></svg>
    <p class="text-slate-400">Tidak ada data untuk periode ini</p>
</div>
@endif

@endsection
