@extends('layouts.app')
@section('title', 'Dashboard')

@section('content')
<div class="dashboard-page space-y-5">
    <section class="dashboard-noc-metrics" aria-label="Metrik operasional">
        <article class="dashboard-noc-metric metric-cyan">
            <div class="dashboard-noc-metric-top"><span>Voucher online</span><span class="dashboard-noc-metric-icon" aria-hidden="true">▤</span></div>
            <p>{{ number_format($stats['voucher_active']) }}</p>
            <small>Sesi online dari akun voucher</small>
        </article>
        <article class="dashboard-noc-metric metric-green">
            <div class="dashboard-noc-metric-top"><span>Member aktif</span><span class="dashboard-noc-metric-icon" aria-hidden="true">⌁</span></div>
            <p>{{ number_format($stats['member_active']) }}</p>
            <small>Member dengan layanan aktif</small>
        </article>
        <article class="dashboard-noc-metric metric-violet">
            <div class="dashboard-noc-metric-top"><span>Status Mikrotik</span><span class="dashboard-noc-metric-icon" aria-hidden="true">⌘</span></div>
            <p>{{ $routerStats['total'] > 0 ? $routerStats['connected'] . ' / ' . $routerStats['total'] : '—' }}</p>
            <small>{{ $routerStats['total'] === 0 ? 'Belum ada router aktif' : ($routerStats['attention'] > 0 ? $routerStats['attention'] . ' router perlu dicek' : 'Semua router aktif terhubung') }}</small>
        </article>
        <article class="dashboard-noc-metric metric-green">
            <div class="dashboard-noc-metric-top"><span>Pendapatan bulan ini</span><span class="dashboard-noc-metric-icon" aria-hidden="true">Rp</span></div>
            <p>Rp {{ number_format($stats['revenue_month'], 0, ',', '.') }}</p>
            <small>{{ now()->isoFormat('MMMM YYYY') }}</small>
        </article>
    </section>

    @if($stats['invoice_overdue'] > 0)
    <a href="{{ route('billing.index', ['status' => 'overdue']) }}" class="dashboard-alert">
        <span class="dashboard-alert-icon" aria-hidden="true">
            <svg width="19" height="19" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M10.29 3.86 1.82 18a2 2 0 0 1 1.71-3h16.94a2 2 0 0 1 1.71 3L13.71 3.86a2 2 0 0 0-3.42 0Z"/><line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="17" x2="12.01" y2="17"/></svg>
        </span>
        <span><strong>{{ number_format($stats['invoice_overdue']) }} invoice jatuh tempo</strong> perlu ditinjau.</span>
        <svg class="dashboard-alert-arrow" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><polyline points="9 18 15 12 9 6"/></svg>
    </a>
    @endif

    <section class="dashboard-noc-grid" aria-label="Status jaringan">
        <article class="dashboard-panel dashboard-sessions-panel">
            <header class="dashboard-panel-header">
                <div>
                    <h3 class="dashboard-panel-title">Sesi Pengguna Sedang Berjalan</h3>
                    <p class="dashboard-panel-subtitle">Accounting RADIUS terbaru yang masih aktif</p>
                </div>
                <div class="dashboard-panel-actions">
                    <span class="dashboard-count-chip">{{ number_format($stats['session_online']) }} aktif</span>
                    <a href="{{ route('online.index') }}" class="dashboard-panel-link">Lihat semua <span aria-hidden="true">→</span></a>
                </div>
            </header>

            <div class="dashboard-session-table-wrap">
                <table class="dashboard-session-table">
                    <thead>
                        <tr><th>Akun</th><th>IP address</th><th>NAS</th><th>Uptime</th><th>Traffic ↓ / ↑</th><th>Status</th></tr>
                    </thead>
                    <tbody>
                    @forelse($onlineSessions as $session)
                        <tr>
                            <td><span class="dashboard-session-dot {{ $session->is_stale ? 'is-stale' : '' }}"></span><span class="dashboard-session-user">{{ $session->username }}</span></td>
                            <td class="dashboard-session-mono">{{ $session->framedipaddress ?: '—' }}</td>
                            <td class="dashboard-session-mono">{{ $session->nasipaddress ?: '—' }}</td>
                            <td class="dashboard-session-mono">{{ $session->duration }}</td>
                            <td class="dashboard-session-traffic"><span>{{ $session->download_label }}</span><span>{{ $session->upload_label }}</span></td>
                            <td><span class="dashboard-session-status {{ $session->is_stale ? 'is-stale' : '' }}">{{ $session->is_stale ? 'Stale' : 'Online' }}</span></td>
                        </tr>
                    @empty
                        <tr><td colspan="6"><div class="dashboard-table-empty">Belum ada sesi aktif.</div></td></tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
        </article>

        @if($isSuperUser)
        <article class="dashboard-panel dashboard-router-panel">
            <header class="dashboard-panel-header">
                <div>
                    <h3 class="dashboard-panel-title">Status Router NAS / Gateway</h3>
                    <p class="dashboard-panel-subtitle">Status koneksi dari pemeriksaan terakhir</p>
                </div>
                <a href="{{ route('routers.index') }}" class="dashboard-panel-link">Kelola <span aria-hidden="true">→</span></a>
            </header>
            <div class="dashboard-router-summary">
                <span><strong>{{ $routerStats['connected'] }}</strong> terhubung</span>
                <span><strong>{{ $routerStats['attention'] }}</strong> perlu dicek</span>
                <span class="dashboard-router-total">{{ $routerStats['total'] }} aktif</span>
            </div>
            <div class="dashboard-router-list">
                @forelse($dashboardRouters as $router)
                    @php($routerConnected = $router->last_connection_status === 'ok')
                    <a href="{{ route('routers.show', $router) }}" class="dashboard-router-row">
                        <span class="dashboard-session-dot {{ $routerConnected ? '' : 'is-stale' }}"></span>
                        <span class="dashboard-router-copy"><strong>{{ $router->name }}</strong><small>{{ $router->ip_address }} · {{ $router->location ?: 'Lokasi belum diisi' }}</small></span>
                        <span class="dashboard-session-status {{ $routerConnected ? '' : 'is-stale' }}">{{ $router->connection_status_label }}</span>
                    </a>
                @empty
                    <div class="dashboard-table-empty">Belum ada router aktif.</div>
                @endforelse
            </div>
        </article>
        @else
        <article class="dashboard-panel dashboard-resource-panel">
            <header class="dashboard-panel-header">
                <div>
                    <h3 class="dashboard-panel-title">Ringkasan Akses</h3>
                    <p class="dashboard-panel-subtitle">Resource yang dikelola sistem</p>
                </div>
            </header>
            <div class="dashboard-access-list">
                <a href="{{ route('members.index') }}"><span>Member aktif</span><strong>{{ number_format($stats['member_active']) }}</strong></a>
                <a href="{{ route('plans.index') }}"><span>Paket aktif</span><strong>{{ number_format($stats['plan_active']) }}</strong></a>
                <a href="{{ route('vouchers.index', ['status' => 'active']) }}"><span>Voucher tersedia</span><strong>{{ number_format($stats['voucher_available']) }}</strong></a>
            </div>
        </article>
        @endif
    </section>

    <section class="dashboard-finance-strip" aria-label="Ringkasan keuangan">
        <a href="{{ route('billing.index', ['status' => 'pending']) }}"><span>Invoice menunggu</span><strong>{{ number_format($stats['invoice_pending']) }}</strong><small>Tinjau tagihan →</small></a>
        <a href="{{ route('members.index', ['status' => 'isolated']) }}"><span>Member isolir</span><strong>{{ number_format($stats['member_isolated']) }}</strong><small>Lihat member →</small></a>
        <a href="{{ route('vouchers.index', ['status' => 'active']) }}"><span>Voucher tersedia</span><strong>{{ number_format($stats['voucher_available']) }}</strong><small>Lihat voucher →</small></a>
    </section>

    <section class="dashboard-activity-grid" aria-label="Aktivitas terbaru">
        <article class="dashboard-panel">
            <header class="dashboard-panel-header">
                <div><h3 class="dashboard-panel-title">Batch voucher terbaru</h3><p class="dashboard-panel-subtitle">Aktivitas pembuatan voucher</p></div>
                <a href="{{ route('vouchers.index') }}" class="dashboard-panel-link">Lihat semua <span aria-hidden="true">→</span></a>
            </header>
            <div class="dashboard-list">
                @forelse($recentBatches as $batch)
                    <div class="dashboard-list-row">
                        <span class="dashboard-list-avatar dashboard-tone-indigo" aria-hidden="true">▦</span>
                        <div class="dashboard-list-main"><p class="dashboard-list-title font-mono">{{ $batch->batch_code }}</p><p class="dashboard-list-meta">{{ $batch->plan->name ?? 'Paket tidak tersedia' }} · {{ $batch->quantity }} voucher</p></div>
                        <time class="dashboard-list-time" datetime="{{ $batch->generated_at?->toIso8601String() }}">{{ $batch->generated_at?->diffForHumans() }}</time>
                    </div>
                @empty
                    <div class="dashboard-empty"><p>Belum ada batch voucher.</p><a href="{{ route('vouchers.create') }}">Generate voucher →</a></div>
                @endforelse
            </div>
        </article>

        <article class="dashboard-panel">
            <header class="dashboard-panel-header">
                <div><h3 class="dashboard-panel-title">Invoice terbaru</h3><p class="dashboard-panel-subtitle">Status penagihan terkini</p></div>
                <a href="{{ route('billing.index') }}" class="dashboard-panel-link">Lihat semua <span aria-hidden="true">→</span></a>
            </header>
            <div class="dashboard-list">
                @forelse($recentInvoices as $inv)
                    @php($statusTone = match($inv->status) { 'paid' => 'paid', 'overdue' => 'overdue', 'cancelled' => 'cancelled', default => 'pending' })
                    <div class="dashboard-list-row">
                        <div class="dashboard-list-main">
                            <div class="dashboard-invoice-title-row">
                                @if($inv->member && ! $inv->member->trashed())<a href="{{ route('members.show', $inv->member) }}" class="dashboard-list-title">{{ $inv->member->username }}</a>@elseif($inv->member)<span class="dashboard-list-title">{{ $inv->member->username }} <small class="text-slate-400">(Arsip)</small></span>@else<span class="dashboard-list-title">Member #{{ $inv->member_id }} tidak tersedia</span>@endif
                                <span class="dashboard-invoice-status dashboard-invoice-{{ $statusTone }}">{{ $inv->status_label }}</span>
                            </div>
                            <p class="dashboard-list-meta">{{ $inv->member?->plan?->name ?? '—' }} · Jatuh tempo {{ $inv->due_date->format('d M Y') }}</p>
                        </div>
                                <div class="dashboard-invoice-amount"><span>{{ $inv->amount_label }}</span>@if(in_array($inv->status, ['pending', 'overdue']) && $inv->member && ! $inv->member->trashed())<a href="{{ route('billing.pay.form', $inv) }}">Bayar</a>@else<a href="{{ route('billing.show', $inv) }}">Detail</a>@endif</div>
                    </div>
                @empty
                    <div class="dashboard-empty"><p>Belum ada invoice.</p><a href="{{ route('billing.index') }}">Buka billing →</a></div>
                @endforelse
            </div>
        </article>
    </section>
</div>
@endsection
