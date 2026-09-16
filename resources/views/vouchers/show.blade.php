@extends('layouts.app')
@section('title', 'Detail Voucher')

@section('topbar-actions')
<div class="flex items-center gap-2">
    @if($voucher->batch_id)
    <a href="{{ route('vouchers.print', $voucher->batch_id) }}?type=thermal" target="_blank" class="btn-sm-secondary">Print</a>
    @endif
    <a href="{{ route('vouchers.index') }}" class="btn-sm-secondary">Kembali</a>
</div>
@endsection

@section('content')
<div class="max-w-5xl mx-auto space-y-5">
    <div class="card overflow-hidden">
        <div class="px-6 py-6 bg-gradient-to-r from-indigo-600 to-violet-600 text-white">
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                <div>
                    <p class="text-xs uppercase tracking-widest text-indigo-100 font-semibold">Voucher</p>
                    <h2 class="mt-1 text-2xl sm:text-3xl font-bold font-mono tracking-tight">{{ $voucher->username }}</h2>
                    <p class="text-sm text-indigo-100 mt-1">{{ $voucher->plan->name ?? 'Tanpa paket' }} · {{ $voucher->batch->batch_code ?? '-' }}</p>
                </div>
                <span class="inline-flex items-center self-start sm:self-auto px-3 py-1.5 rounded-full bg-white/15 border border-white/20 text-sm font-semibold">
                    {{ $voucher->status_label }}
                </span>
            </div>
        </div>

        <div class="grid grid-cols-2 lg:grid-cols-4 divide-x divide-y lg:divide-y-0 divide-slate-100">
            <div class="p-5"><p class="stat-label">Harga</p><p class="text-lg font-bold text-slate-800">{{ $voucher->price_label }}</p></div>
            <div class="p-5"><p class="stat-label">Validity</p><p class="text-lg font-bold text-slate-800">{{ $voucher->plan?->duration_label ?? '-' }}</p></div>
            <div class="p-5"><p class="stat-label">First Login</p><p class="text-sm font-semibold text-slate-700 mt-1">{{ $voucher->first_login_at?->format('d M Y H:i:s') ?? 'Belum digunakan' }}</p></div>
            <div class="p-5"><p class="stat-label">Expired</p><p class="text-sm font-semibold text-slate-700 mt-1">{{ $voucher->expired_at?->format('d M Y H:i:s') ?? '-' }}</p></div>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-5">
        <div class="card">
            <div class="card-header"><span class="card-title">Akses Voucher</span></div>
            <div class="card-body space-y-4">
                <div class="grid grid-cols-2 gap-4">
                    <div><p class="stat-label">Username</p><p class="font-mono font-semibold text-slate-800 mt-1">{{ $voucher->username }}</p></div>
                    <div><p class="stat-label">Password</p><p class="font-mono font-semibold text-slate-800 mt-1">{{ $voucher->password_plain }}</p></div>
                </div>
                <div class="rounded-xl bg-slate-50 border border-slate-100 p-4">
                    <div class="flex justify-between items-center">
                        <span class="text-xs font-semibold text-slate-500">Sisa Validity</span>
                        <span class="font-mono font-bold text-indigo-600">{{ $voucher->remaining_seconds !== null ? gmdate('H:i:s', $voucher->remaining_seconds) : '-' }}</span>
                    </div>
                    @if($voucher->expired_at && $voucher->first_login_at)
                    @php
                        $total = max(1, $voucher->first_login_at->diffInSeconds($voucher->expired_at));
                        $used = min($total, max(0, $total - ($voucher->remaining_seconds ?? 0)));
                        $pct = min(100, round(($used / $total) * 100));
                    @endphp
                    <div class="mt-3 h-2 rounded-full bg-slate-200 overflow-hidden"><div class="h-full rounded-full bg-indigo-500" style="width:{{ $pct }}%"></div></div>
                    @endif
                </div>
            </div>
        </div>

        <div class="card">
            <div class="card-header"><span class="card-title">QoS / MikroTik</span></div>
            <div class="card-body">
                @php $plan = $voucher->plan; @endphp
                <div class="text-sm">
                    <p class="stat-label">MikroTik Rate Limit</p>
                    <p class="font-mono font-semibold text-slate-800 mt-1">{{ $plan?->mikrotik_rate_limit ?: ($plan ? app(\App\Services\QosService::class)->rateLimit($plan) : '-') }}</p>
                </div>
            </div>
        </div>
    </div>

    <div class="card">
        <div class="card-header flex items-center justify-between">
            <span class="card-title">Informasi Paket</span>
            @if($voucher->plan)
            <a href="{{ route('plans.edit', $voucher->plan) }}" class="text-xs text-indigo-600 font-semibold hover:underline">Edit Paket →</a>
            @endif
        </div>
        <div class="card-body grid grid-cols-2 md:grid-cols-4 gap-5">
            <div><p class="stat-label">Paket</p><p class="font-semibold text-slate-800 mt-1">{{ $plan?->name ?? '-' }}</p></div>
            <div><p class="stat-label">Kuota Data</p><p class="font-semibold text-slate-800 mt-1">{{ $plan?->quota_label ?? 'Unlimited' }}</p></div>
            <div><p class="stat-label">Simultaneous</p><p class="font-semibold text-slate-800 mt-1">1 sesi</p></div>
            <div><p class="stat-label">Dibuat</p><p class="font-semibold text-slate-800 mt-1">{{ $voucher->created_at?->format('d M Y H:i') ?? '-' }}</p></div>
        </div>
    </div>
</div>
@endsection
