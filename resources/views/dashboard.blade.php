@extends('layouts.app')
@section('title', 'Dashboard')

@section('topbar-actions')
    <a href="{{ route('vouchers.create') }}" class="btn-primary text-xs px-3 py-2">
        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" aria-hidden="true"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
        Generate Voucher
    </a>
@endsection

@section('content')
@php
    $user = auth('app')->user();
    $hour = now()->format('G');
    $greeting = $hour < 12 ? 'Selamat pagi' : ($hour < 17 ? 'Selamat siang' : 'Selamat malam');
    $operationalStats = [
        ['label' => 'Sesi hari ini', 'value' => number_format($stats['session_today']), 'detail' => 'Accounting sessions', 'icon' => 'activity', 'tone' => 'blue'],
        ['label' => 'Online sekarang', 'value' => number_format($stats['session_online']), 'detail' => 'Sesi aktif', 'icon' => 'wifi', 'tone' => 'green'],
        ['label' => 'Upload hari ini', 'value' => \App\Models\Radacct::formatBytes($stats['traffic_upload_today']), 'detail' => 'Total upload', 'icon' => 'upload', 'tone' => 'cyan'],
        ['label' => 'Download hari ini', 'value' => \App\Models\Radacct::formatBytes($stats['traffic_download_today']), 'detail' => 'Total download', 'icon' => 'download', 'tone' => 'violet'],
    ];
    $resourceStats = [
        ['label' => 'Voucher aktif', 'value' => number_format($stats['voucher_active']), 'route' => route('vouchers.index', ['status' => 'active']), 'icon' => 'ticket', 'tone' => 'indigo'],
        ['label' => 'Member aktif', 'value' => number_format($stats['member_active']), 'route' => route('members.index', ['status' => 'active']), 'icon' => 'users', 'tone' => 'green'],
        ['label' => 'Paket aktif', 'value' => number_format($stats['plan_active']), 'route' => route('plans.index'), 'icon' => 'package', 'tone' => 'violet'],
        ['label' => 'Voucher tersedia', 'value' => number_format($stats['voucher_available']), 'route' => route('vouchers.index', ['status' => 'active']), 'icon' => 'ticket', 'tone' => 'blue'],
    ];
@endphp

<div class="dashboard-page space-y-6">
    <section class="dashboard-welcome">
        <div class="dashboard-welcome-pattern" aria-hidden="true"></div>
        <div class="dashboard-welcome-content">
            <div class="min-w-0">
                <p class="dashboard-eyebrow">Network operations</p>
                <h2 class="dashboard-welcome-title">{{ $greeting }}, {{ $user->name }}</h2>
                <p class="dashboard-welcome-subtitle">Ringkasan operasional jaringan untuk {{ now()->isoFormat('dddd, D MMMM YYYY') }}.</p>
            </div>
            <div class="dashboard-role">
                <span class="dashboard-role-label">Signed in as</span>
                <span class="dashboard-role-value">{{ ucfirst($user->role) }}</span>
                <span class="dashboard-avatar" aria-hidden="true">{{ strtoupper(substr($user->name, 0, 1)) }}</span>
            </div>
        </div>
    </section>

    @if($stats['invoice_overdue'] > 0)
    <a href="{{ route('billing.index', ['status' => 'overdue']) }}" class="dashboard-alert">
        <span class="dashboard-alert-icon" aria-hidden="true">
            <svg width="19" height="19" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M10.29 3.86 1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0Z"/><line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="17" x2="12.01" y2="17"/></svg>
        </span>
        <span><strong>{{ number_format($stats['invoice_overdue']) }} invoice jatuh tempo</strong> belum dibayar. Tinjau invoice overdue.</span>
        <svg class="dashboard-alert-arrow" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><polyline points="9 18 15 12 9 6"/></svg>
    </a>
    @endif

    <section aria-labelledby="dashboard-operations-heading">
        <div class="dashboard-section-heading">
            <div>
                <p class="dashboard-section-kicker">Live overview</p>
                <h3 id="dashboard-operations-heading" class="dashboard-section-title">Ringkasan operasional</h3>
            </div>
            <span class="dashboard-updated"><span class="dashboard-live-dot"></span> Data dari sistem</span>
        </div>
        <div class="dashboard-stat-grid">
            @foreach($operationalStats as $stat)
            <article class="dashboard-stat-card">
                <div class="dashboard-stat-topline">
                    <span class="dashboard-stat-label">{{ $stat['label'] }}</span>
                    <span class="dashboard-stat-icon dashboard-tone-{{ $stat['tone'] }}" aria-hidden="true">
                        @if($stat['icon'] === 'activity')
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><polyline points="22 12 18 12 15 21 9 3 6 12 2 12"/></svg>
                        @elseif($stat['icon'] === 'wifi')
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M5 12.55a11 11 0 0 1 14.08 0M1.42 9a16 16 0 0 1 21.16 0M8.53 16.11a6 6 0 0 1 6.95 0"/><line x1="12" y1="20" x2="12.01" y2="20"/></svg>
                        @elseif($stat['icon'] === 'upload')
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M12 16V4m-5 5 5-5 5 5"/><path d="M4 16v4h16v-4"/></svg>
                        @else
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M12 4v12m-5-5 5 5 5-5"/><path d="M4 16v4h16v-4"/></svg>
                        @endif
                    </span>
                </div>
                <p class="dashboard-stat-value {{ in_array($stat['icon'], ['upload', 'download']) ? 'dashboard-stat-value-compact' : '' }}">{{ $stat['value'] }}</p>
                <p class="dashboard-stat-detail">{{ $stat['detail'] }}</p>
            </article>
            @endforeach
        </div>
    </section>

    <section aria-labelledby="dashboard-resources-heading">
        <div class="dashboard-section-heading">
            <div>
                <p class="dashboard-section-kicker">Access management</p>
                <h3 id="dashboard-resources-heading" class="dashboard-section-title">Resource overview</h3>
            </div>
        </div>
        <div class="dashboard-resource-grid">
            @foreach($resourceStats as $stat)
            <a href="{{ $stat['route'] }}" class="dashboard-resource-card">
                <span class="dashboard-resource-icon dashboard-tone-{{ $stat['tone'] }}" aria-hidden="true">
                    @if($stat['icon'] === 'users')
                        <svg width="19" height="19" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M22 21v-2a4 4 0 0 0-3-3.87M16 3.13a4 4 0 0 1 0 7.75"/></svg>
                    @elseif($stat['icon'] === 'package')
                        <svg width="19" height="19" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="m12 3 9 5-9 5-9-5 9-5Z"/><path d="M3 8v9l9 5 9-5V8M12 13v9"/></svg>
                    @else
                        <svg width="19" height="19" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M2 9a3 3 0 0 1 0 6v2a2 2 0 0 0 2 2h16a2 2 0 0 0 2-2v-2a3 3 0 0 1 0-6V7a2 2 0 0 0-2-2H4a2 2 0 0 0-2 2v2Z"/></svg>
                    @endif
                </span>
                <span class="dashboard-resource-copy">
                    <span class="dashboard-resource-label">{{ $stat['label'] }}</span>
                    <span class="dashboard-resource-value">{{ $stat['value'] }}</span>
                </span>
                <svg class="dashboard-resource-arrow" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><polyline points="9 18 15 12 9 6"/></svg>
            </a>
            @endforeach
        </div>
    </section>

    <section aria-labelledby="dashboard-billing-heading">
        <div class="dashboard-section-heading">
            <div>
                <p class="dashboard-section-kicker">Finance & attention</p>
                <h3 id="dashboard-billing-heading" class="dashboard-section-title">Billing overview</h3>
            </div>
        </div>
        <div class="dashboard-billing-grid">
            <article class="dashboard-billing-card dashboard-billing-revenue">
                <span class="dashboard-billing-label">Revenue {{ now()->isoFormat('MMMM YYYY') }}</span>
                <p class="dashboard-billing-value">Rp {{ number_format($stats['revenue_month'], 0, ',', '.') }}</p>
                <a href="{{ route('billing.index', ['status' => 'paid']) }}" class="dashboard-inline-link">Lihat invoice lunas <span aria-hidden="true">→</span></a>
            </article>
            <article class="dashboard-billing-card dashboard-billing-pending">
                <span class="dashboard-billing-label">Invoice pending</span>
                <p class="dashboard-billing-value">{{ number_format($stats['invoice_pending']) }}</p>
                <a href="{{ route('billing.index', ['status' => 'pending']) }}" class="dashboard-inline-link">Tinjau pending <span aria-hidden="true">→</span></a>
            </article>
            <article class="dashboard-billing-card dashboard-billing-isolated">
                <span class="dashboard-billing-label">Member isolir</span>
                <p class="dashboard-billing-value">{{ number_format($stats['member_isolated']) }}</p>
                <a href="{{ route('members.index', ['status' => 'isolated']) }}" class="dashboard-inline-link">Lihat member <span aria-hidden="true">→</span></a>
            </article>
        </div>
    </section>

    <section class="dashboard-activity-grid" aria-label="Aktivitas terbaru">
        <article class="dashboard-panel">
            <header class="dashboard-panel-header">
                <div class="dashboard-panel-heading">
                    <span class="dashboard-panel-icon dashboard-tone-indigo" aria-hidden="true"><svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="m12 3 9 5-9 5-9-5 9-5Z"/><path d="M3 8v9l9 5 9-5V8M12 13v9"/></svg></span>
                    <div><h3 class="dashboard-panel-title">Batch voucher terbaru</h3><p class="dashboard-panel-subtitle">Aktivitas pembuatan voucher</p></div>
                </div>
                <a href="{{ route('vouchers.index') }}" class="dashboard-panel-link">Lihat semua <span aria-hidden="true">→</span></a>
            </header>
            <div class="dashboard-list">
                @forelse($recentBatches as $batch)
                <div class="dashboard-list-row">
                    <span class="dashboard-list-avatar dashboard-tone-indigo" aria-hidden="true"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M2 9a3 3 0 0 1 0 6v2a2 2 0 0 0 2 2h16a2 2 0 0 0 2-2v-2a3 3 0 0 1 0-6V7a2 2 0 0 0-2-2H4a2 2 0 0 0-2 2v2Z"/></svg></span>
                    <div class="dashboard-list-main"><p class="dashboard-list-title font-mono">{{ $batch->batch_code }}</p><p class="dashboard-list-meta">{{ $batch->plan->name ?? '-' }} · {{ $batch->quantity }} pcs</p></div>
                    <time class="dashboard-list-time" datetime="{{ $batch->generated_at?->toIso8601String() }}">{{ $batch->generated_at?->diffForHumans() }}</time>
                </div>
                @empty
                <div class="dashboard-empty"><p>Belum ada batch voucher.</p><a href="{{ route('vouchers.create') }}">Generate voucher <span aria-hidden="true">→</span></a></div>
                @endforelse
            </div>
        </article>

        <article class="dashboard-panel">
            <header class="dashboard-panel-header">
                <div class="dashboard-panel-heading">
                    <span class="dashboard-panel-icon dashboard-tone-cyan" aria-hidden="true"><svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><rect x="2" y="4" width="20" height="16" rx="2"/><path d="M2 10h20"/></svg></span>
                    <div><h3 class="dashboard-panel-title">Invoice terbaru</h3><p class="dashboard-panel-subtitle">Status penagihan terkini</p></div>
                </div>
                <a href="{{ route('billing.index') }}" class="dashboard-panel-link">Lihat semua <span aria-hidden="true">→</span></a>
            </header>
            <div class="dashboard-list">
                @forelse($recentInvoices as $inv)
                @php
                    $statusTone = match($inv->status) {
                        'paid' => 'paid',
                        'overdue' => 'overdue',
                        'cancelled' => 'cancelled',
                        default => 'pending',
                    };
                @endphp
                <div class="dashboard-list-row">
                    <div class="dashboard-list-main">
                        <div class="dashboard-invoice-title-row">
                            @if($inv->member)
                                <a href="{{ route('members.show', $inv->member) }}" class="dashboard-list-title">{{ $inv->member->username }}</a>
                            @else
                                <span class="dashboard-list-title">Member tidak tersedia</span>
                            @endif
                            <span class="dashboard-invoice-status dashboard-invoice-{{ $statusTone }}">{{ $inv->status_label }}</span>
                        </div>
                        <p class="dashboard-list-meta">{{ $inv->member?->plan?->name ?? '-' }} · Jatuh tempo {{ $inv->due_date->format('d M Y') }}</p>
                    </div>
                    <div class="dashboard-invoice-amount"><span>{{ $inv->amount_label }}</span>@if(in_array($inv->status, ['pending', 'overdue']))<a href="{{ route('billing.pay.form', $inv) }}">Bayar</a>@else<a href="{{ route('billing.show', $inv) }}">Detail</a>@endif</div>
                </div>
                @empty
                <div class="dashboard-empty"><p>Belum ada invoice.</p><a href="{{ route('members.index') }}">Buka halaman member <span aria-hidden="true">→</span></a></div>
                @endforelse
            </div>
        </article>
    </section>
</div>
@endsection
