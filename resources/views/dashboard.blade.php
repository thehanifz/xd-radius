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
    $user = auth('app')->user();
    $hour = now()->format('G');
    $greeting = $hour < 12 ? 'Selamat pagi' : ($hour < 17 ? 'Selamat siang' : 'Selamat malam');
@endphp

<div class="space-y-6">

    {{-- Welcome hero --}}
    <div class="rounded-2xl overflow-hidden relative"
         style="background: linear-gradient(135deg, #1e1b4b 0%, #312e81 40%, #4c1d95 100%); min-height:140px;">

        {{-- Subtle grid pattern --}}
        <div class="absolute inset-0 opacity-10"
             style="background-image: radial-gradient(circle, rgba(255,255,255,0.4) 1px, transparent 1px); background-size: 28px 28px;"></div>

        {{-- Glow blobs --}}
        <div class="absolute top-0 right-20 w-64 h-64 rounded-full opacity-10 blur-3xl"
             style="background: #818cf8;"></div>
        <div class="absolute bottom-0 left-20 w-48 h-48 rounded-full opacity-10 blur-3xl"
             style="background: #a78bfa;"></div>

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
                     style="background: rgba(255,255,255,0.15); backdrop-filter: blur(8px); border: 1px solid rgba(255,255,255,0.2);">
                    {{ strtoupper(substr($user->name, 0, 1)) }}
                </div>
            </div>
        </div>
    </div>

    {{-- KPI Cards --}}
    @php
        $kpis = [
            [
                'label'   => 'Total Voucher Aktif',
                'value'   => \App\Models\Voucher::where('status', 'active')->count(),
                'icon'    => 'ticket',
                'color'   => 'indigo',
                'bg'      => 'linear-gradient(135deg, #eef2ff, #e0e7ff)',
                'iconbg'  => 'linear-gradient(135deg, #6366f1, #818cf8)',
            ],
            [
                'label'   => 'Member Aktif',
                'value'   => \App\Models\Member::where('status', 'active')->count(),
                'icon'    => 'users',
                'color'   => 'emerald',
                'bg'      => 'linear-gradient(135deg, #ecfdf5, #d1fae5)',
                'iconbg'  => 'linear-gradient(135deg, #059669, #34d399)',
            ],
            [
                'label'   => 'Sesi Online',
                'value'   => \Illuminate\Support\Facades\DB::table('radacct')->whereNull('acctstoptime')->count(),
                'icon'    => 'wifi',
                'color'   => 'blue',
                'bg'      => 'linear-gradient(135deg, #eff6ff, #dbeafe)',
                'iconbg'  => 'linear-gradient(135deg, #2563eb, #60a5fa)',
            ],
            [
                'label'   => 'Paket Tersedia',
                'value'   => \App\Models\Plan::where('is_active', true)->count(),
                'icon'    => 'package',
                'color'   => 'violet',
                'bg'      => 'linear-gradient(135deg, #f5f3ff, #ede9fe)',
                'iconbg'  => 'linear-gradient(135deg, #7c3aed, #a78bfa)',
            ],
        ];
    @endphp

    <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
        @foreach($kpis as $kpi)
        <div class="rounded-2xl p-5 relative overflow-hidden cursor-default hover:-translate-y-0.5 transition-all duration-200"
             style="background: {{ $kpi['bg'] }}; box-shadow: 0 1px 3px rgba(0,0,0,0.06);">

            {{-- Icon --}}
            <div class="w-10 h-10 rounded-xl flex items-center justify-center mb-4"
                 style="background: {{ $kpi['iconbg'] }}; box-shadow: 0 4px 12px rgba(0,0,0,0.15);">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="white" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    @switch($kpi['icon'])
                        @case('ticket') <path d="M2 9a3 3 0 0 1 0 6v2a2 2 0 0 0 2 2h16a2 2 0 0 0 2-2v-2a3 3 0 0 1 0-6V7a2 2 0 0 0-2-2H4a2 2 0 0 0-2 2v2z"/> @break
                        @case('users') <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/> @break
                        @case('wifi') <path d="M5 12.55a11 11 0 0 1 14.08 0"/><path d="M1.42 9a16 16 0 0 1 21.16 0"/><path d="M8.53 16.11a6 6 0 0 1 6.95 0"/><line x1="12" y1="20" x2="12.01" y2="20"/> @break
                        @case('package') <path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"/> @break
                    @endswitch
                </svg>
            </div>

            <p class="text-2xl font-bold text-slate-800 tracking-tight">{{ number_format($kpi['value']) }}</p>
            <p class="text-xs font-semibold text-slate-500 mt-0.5">{{ $kpi['label'] }}</p>
        </div>
        @endforeach
    </div>

    {{-- Quick actions --}}
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
                @forelse(\App\Models\VoucherBatch::with('plan')->latest('generated_at')->limit(5)->get() as $batch)
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

        {{-- Quick actions panel --}}
        <div class="card">
            <div class="card-header">
                <h3 class="card-title flex items-center gap-2">
                    <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="text-violet-500"><polygon points="13 2 3 14 12 14 11 22 21 10 12 10 13 2"/></svg>
                    Aksi Cepat
                </h3>
            </div>
            <div class="card-body grid grid-cols-2 gap-3">
                @php
                    $actions = [
                        ['href' => route('vouchers.create'), 'icon' => 'plus-circle', 'label' => 'Generate Voucher', 'color' => '#6366f1', 'bg' => '#eef2ff'],
                        ['href' => route('members.create'), 'icon' => 'user-plus', 'label' => 'Tambah Member', 'color' => '#059669', 'bg' => '#ecfdf5'],
                        ['href' => route('vouchers.index'), 'icon' => 'list', 'label' => 'Daftar Voucher', 'color' => '#2563eb', 'bg' => '#eff6ff'],
                        ['href' => route('plans.index'), 'icon' => 'package', 'label' => 'Kelola Paket', 'color' => '#7c3aed', 'bg' => '#f5f3ff'],
                    ];
                @endphp
                @foreach($actions as $action)
                <a href="{{ $action['href'] }}"
                   class="flex items-center gap-3 p-3.5 rounded-xl hover:-translate-y-0.5 transition-all duration-200 group"
                   style="background: {{ $action['bg'] }};">
                    <div class="w-9 h-9 rounded-xl flex items-center justify-center flex-shrink-0"
                         style="background: {{ $action['color'] }}; box-shadow: 0 4px 10px {{ $action['color'] }}40;">
                        <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="white" stroke-width="2" stroke-linecap="round">
                            @switch($action['icon'])
                                @case('plus-circle') <circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="16"/><line x1="8" y1="12" x2="16" y2="12"/> @break
                                @case('user-plus') <path d="M16 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="8.5" cy="7" r="4"/><line x1="20" y1="8" x2="20" y2="14"/><line x1="23" y1="11" x2="17" y2="11"/> @break
                                @case('list') <line x1="8" y1="6" x2="21" y2="6"/><line x1="8" y1="12" x2="21" y2="12"/><line x1="8" y1="18" x2="21" y2="18"/><line x1="3" y1="6" x2="3.01" y2="6"/><line x1="3" y1="12" x2="3.01" y2="12"/><line x1="3" y1="18" x2="3.01" y2="18"/> @break
                                @case('package') <path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"/> @break
                            @endswitch
                        </svg>
                    </div>
                    <span class="text-sm font-semibold text-slate-700 group-hover:text-slate-900 transition-colors">{{ $action['label'] }}</span>
                </a>
                @endforeach
            </div>
        </div>
    </div>

</div>
@endsection
