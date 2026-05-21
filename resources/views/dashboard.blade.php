@extends('layouts.app')
@section('title', 'Dashboard')

@section('topbar-actions')
    <a href="{{ route('vouchers.create') }}" class="btn-primary text-xs px-3 py-2">
        <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
        Generate Voucher
    </a>
@endsection

@section('content')
@php
    $user     = auth('app')->user();
    $hour     = now()->format('G');
    $greeting = $hour < 12 ? 'Selamat pagi' : ($hour < 17 ? 'Selamat siang' : 'Selamat malam');
@endphp

<div class="space-y-6">

    {{-- Welcome hero --}}
    <div class="rounded-2xl overflow-hidden relative"
         style="background: linear-gradient(135deg, #1e1b4b 0%, #312e81 40%, #4c1d95 100%); min-height:140px;">
        <div class="absolute inset-0 opacity-10"
             style="background-image: radial-gradient(circle, rgba(255,255,255,0.4) 1px, transparent 1px); background-size: 28px 28px;"></div>
        <div class="absolute top-0 right-20 w-64 h-64 rounded-full opacity-10 blur-3xl" style="background:#818cf8;"></div>
        <div class="absolute bottom-0 left-20 w-48 h-48 rounded-full opacity-10 blur-3xl" style="background:#a78bfa;"></div>
        <div class="relative z-10 px-8 py-7 flex items-center justify-between">
            <div>
                <p class="text-indigo-300 text-sm font-medium mb-1">{{ $greeting }}, 👋</p>
                <h2 class="text-2xl font-bold text-white tracking-tight">{{ $user->name }}</h2>
                <p class="text-indigo-300/70 text-sm mt-1">{{ now()->isoFormat('dddd, D MMMM YYYY') }}</p>
            </div>
            <div class="hidden md:flex items-center gap-4 text-right">
                <div>
                    <p class="text-indigo-300 text-xs uppercase tracking-wider font-semibold">Role</p>
                    <p class="text-white font-bold capitalize mt-0.5">{{ $user->role }}</p>
                </div>
                <div class="w-12 h-12 rounded-2xl flex items-center justify-center text-white font-bold text-lg"
                     style="background:rgba(255,255,255,0.15);backdrop-filter:blur(8px);border:1px solid rgba(255,255,255,0.2);">
                    {{ strtoupper(substr($user->name, 0, 1)) }}
                </div>
            </div>
        </div>
    </div>

    {{-- Alert overdue --}}
    @if($stats['invoice_overdue'] > 0)
    <a href="{{ route('billing.index', ['status' => 'overdue']) }}"
       class="flex items-center gap-3 px-5 py-3.5 rounded-xl text-red-700 font-medium text-sm cursor-pointer hover:bg-red-100 transition-colors"
       style="background:#fff1f2;border:1px solid #fecdd3;">
        <svg class="flex-shrink-0 text-red-500" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"/>
            <line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="17" x2="12.01" y2="17"/>
        </svg>
        <span>Ada <strong>{{ $stats['invoice_overdue'] }} invoice jatuh tempo</strong> yang belum dibayar — klik untuk lihat</span>
        <svg class="ml-auto text-red-400" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="9 18 15 12 9 6"/></svg>
    </a>
    @endif

    {{-- KPI row 1: operasional --}}
    @php
    $kpis = [
        [
            'label'  => 'Voucher Aktif',
            'value'  => number_format($stats['voucher_active']),
            'icon'   => 'ticket',
            'bg'     => 'linear-gradient(135deg,#eef2ff,#e0e7ff)',
            'iconbg' => 'linear-gradient(135deg,#6366f1,#818cf8)',
        ],
        [
            'label'  => 'Member Aktif',
            'value'  => number_format($stats['member_active']),
            'icon'   => 'users',
            'bg'     => 'linear-gradient(135deg,#ecfdf5,#d1fae5)',
            'iconbg' => 'linear-gradient(135deg,#059669,#34d399)',
        ],
        [
            'label'  => 'Sesi Online',
            'value'  => number_format($stats['session_online']),
            'icon'   => 'wifi',
            'bg'     => 'linear-gradient(135deg,#eff6ff,#dbeafe)',
            'iconbg' => 'linear-gradient(135deg,#2563eb,#60a5fa)',
        ],
        [
            'label'  => 'Paket Tersedia',
            'value'  => number_format($stats['plan_active']),
            'icon'   => 'package',
            'bg'     => 'linear-gradient(135deg,#f5f3ff,#ede9fe)',
            'iconbg' => 'linear-gradient(135deg,#7c3aed,#a78bfa)',
        ],
    ];
    @endphp
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
        @foreach($kpis as $k)
        <div class="rounded-2xl p-5 relative overflow-hidden hover:-translate-y-0.5 transition-all duration-200"
             style="background:{{ $k['bg'] }};box-shadow:0 1px 3px rgba(0,0,0,0.06);">
            <div class="w-10 h-10 rounded-xl flex items-center justify-center mb-4"
                 style="background:{{ $k['iconbg'] }};box-shadow:0 4px 12px rgba(0,0,0,0.15);">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="white" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    @switch($k['icon'])
                        @case('ticket')<path d="M2 9a3 3 0 0 1 0 6v2a2 2 0 0 0 2 2h16a2 2 0 0 0 2-2v-2a3 3 0 0 1 0-6V7a2 2 0 0 0-2-2H4a2 2 0 0 0-2 2v2z"/>@break
                        @case('users')<path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/>@break
                        @case('wifi')<path d="M5 12.55a11 11 0 0 1 14.08 0"/><path d="M1.42 9a16 16 0 0 1 21.16 0"/><path d="M8.53 16.11a6 6 0 0 1 6.95 0"/><line x1="12" y1="20" x2="12.01" y2="20"/>@break
                        @case('package')<path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"/>@break
                    @endswitch
                </svg>
            </div>
            <p class="text-2xl font-bold text-slate-800 tracking-tight">{{ $k['value'] }}</p>
            <p class="text-xs font-semibold text-slate-500 mt-0.5">{{ $k['label'] }}</p>
        </div>
        @endforeach
    </div>

    {{-- KPI row 2: billing --}}
    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
        {{-- Revenue bulan ini --}}
        <div class="rounded-2xl p-5 col-span-1 sm:col-span-1"
             style="background:linear-gradient(135deg,#f0fdf4,#dcfce7);box-shadow:0 1px 3px rgba(0,0,0,0.06);">
            <p class="text-xs font-semibold text-green-600 uppercase tracking-wider mb-1">Revenue {{ now()->isoFormat('MMMM YYYY') }}</p>
            <p class="text-2xl font-bold text-green-800 tabular-nums">
                Rp {{ number_format($stats['revenue_month'], 0, ',', '.') }}
            </p>
            <a href="{{ route('billing.index', ['status' => 'paid']) }}" class="text-xs text-green-600 font-medium mt-1 inline-block hover:underline">Lihat invoice lunas →</a>
        </div>
        {{-- Invoice pending --}}
        <div class="rounded-2xl p-5"
             style="background:linear-gradient(135deg,#fffbeb,#fef3c7);box-shadow:0 1px 3px rgba(0,0,0,0.06);">
            <p class="text-xs font-semibold text-amber-600 uppercase tracking-wider mb-1">Invoice Pending</p>
            <p class="text-2xl font-bold text-amber-800 tabular-nums">{{ number_format($stats['invoice_pending']) }}</p>
            <a href="{{ route('billing.index', ['status' => 'pending']) }}" class="text-xs text-amber-600 font-medium mt-1 inline-block hover:underline">Lihat semua →</a>
        </div>
        {{-- Member isolir --}}
        <div class="rounded-2xl p-5"
             style="background:linear-gradient(135deg,#fff1f2,#ffe4e6);box-shadow:0 1px 3px rgba(0,0,0,0.06);">
            <p class="text-xs font-semibold text-red-500 uppercase tracking-wider mb-1">Member Isolir</p>
            <p class="text-2xl font-bold text-red-700 tabular-nums">{{ number_format($stats['member_isolated']) }}</p>
            <a href="{{ route('members.index', ['status' => 'isolated']) }}" class="text-xs text-red-500 font-medium mt-1 inline-block hover:underline">Lihat member →</a>
        </div>
    </div>

    {{-- Bottom grid --}}
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-4">

        {{-- Recent batches --}}
        <div class="card">
            <div class="card-header">
                <h3 class="card-title flex items-center gap-2">
                    <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="text-indigo-500"><polygon points="12 2 2 7 12 12 22 7 12 2"/><polyline points="2 17 12 22 22 17"/><polyline points="2 12 12 17 22 12"/></svg>
                    Batch Voucher Terbaru
                </h3>
                <a href="{{ route('vouchers.index') }}" class="text-xs text-indigo-600 font-semibold hover:text-indigo-700">Lihat semua →</a>
            </div>
            <div class="divide-y divide-slate-50">
                @forelse($recentBatches as $batch)
                <div class="px-6 py-3.5 flex items-center gap-3 hover:bg-slate-50/60 transition-colors">
                    <div class="w-8 h-8 rounded-lg flex-shrink-0 flex items-center justify-center text-indigo-600" style="background:#eef2ff;">
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M2 9a3 3 0 0 1 0 6v2a2 2 0 0 0 2 2h16a2 2 0 0 0 2-2v-2a3 3 0 0 1 0-6V7a2 2 0 0 0-2-2H4a2 2 0 0 0-2 2v2z"/></svg>
                    </div>
                    <div class="flex-1 min-w-0">
                        <p class="text-sm font-semibold text-slate-800 font-mono truncate">{{ $batch->batch_code }}</p>
                        <p class="text-xs text-slate-400">{{ $batch->plan->name ?? '-' }} · {{ $batch->quantity }} pcs</p>
                    </div>
                    <span class="text-xs text-slate-400 flex-shrink-0">{{ $batch->generated_at?->diffForHumans() }}</span>
                </div>
                @empty
                <div class="px-6 py-8 text-center">
                    <p class="text-sm text-slate-400">Belum ada batch</p>
                    <a href="{{ route('vouchers.create') }}" class="text-xs text-indigo-600 font-semibold mt-1 inline-block">Generate sekarang →</a>
                </div>
                @endforelse
            </div>
        </div>

        {{-- Recent / overdue invoices --}}
        <div class="card">
            <div class="card-header">
                <h3 class="card-title flex items-center gap-2">
                    <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="text-rose-500"><rect x="1" y="4" width="22" height="16" rx="2" ry="2"/><line x1="1" y1="10" x2="23" y2="10"/></svg>
                    Invoice Terbaru
                </h3>
                <a href="{{ route('billing.index') }}" class="text-xs text-indigo-600 font-semibold hover:text-indigo-700">Lihat semua →</a>
            </div>
            <div class="divide-y divide-slate-50">
                @forelse($recentInvoices as $inv)
                @php
                    $sc = match($inv->status) {
                        'paid'      => 'badge-green',
                        'overdue'   => 'badge-red',
                        'cancelled' => 'badge-slate',
                        default     => 'badge-yellow',
                    };
                @endphp
                <div class="px-6 py-3.5 flex items-center gap-3 hover:bg-slate-50/60 transition-colors">
                    <div class="flex-1 min-w-0">
                        <div class="flex items-center gap-2">
                            <a href="{{ route('members.show', $inv->member) }}" class="text-sm font-semibold text-slate-800 hover:text-indigo-600 font-mono">{{ $inv->member->username }}</a>
                            <span class="badge {{ $sc }} text-[10px] py-0">{{ $inv->status_label }}</span>
                        </div>
                        <p class="text-xs text-slate-400">{{ $inv->member->plan->name ?? '-' }} · Jatuh tempo {{ $inv->due_date->format('d M Y') }}</p>
                    </div>
                    <div class="text-right flex-shrink-0">
                        <p class="text-sm font-bold text-slate-700 tabular-nums">{{ $inv->amount_label }}</p>
                        @if(in_array($inv->status, ['pending','overdue']))
                        <a href="{{ route('billing.pay.form', $inv) }}" class="text-xs text-green-600 font-semibold hover:underline">Bayar</a>
                        @else
                        <a href="{{ route('billing.show', $inv) }}" class="text-xs text-slate-400 hover:underline">Detail</a>
                        @endif
                    </div>
                </div>
                @empty
                <div class="px-6 py-8 text-center">
                    <p class="text-sm text-slate-400">Belum ada invoice</p>
                    <a href="{{ route('members.index') }}" class="text-xs text-indigo-600 font-semibold mt-1 inline-block">Buat dari halaman member →</a>
                </div>
                @endforelse
            </div>
        </div>

    </div>

</div>
@endsection
