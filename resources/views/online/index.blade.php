@extends('layouts.app')
@section('title', 'Online Sessions')

@section('topbar-actions')
<div class="online-topbar-actions">
    <span class="online-live-indicator" aria-live="polite">
        <span id="live-dot" class="online-live-dot"></span>
        <span id="live-status">Live · update otomatis 10 detik</span>
        <span class="online-live-separator">·</span>
        <span id="last-refresh">{{ now()->format('H:i:s') }}</span>
    </span>
    <button type="button" id="refresh-online" class="btn-sm-secondary inline-flex items-center gap-1.5">
        <svg id="refresh-icon" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" aria-hidden="true">
            <polyline points="23 4 23 10 17 10"/>
            <path d="M20.49 15a9 9 0 1 1-2.12-9.36L23 10"/>
        </svg>
        Refresh data
    </button>
</div>
@endsection

@section('content')
<div class="online-page space-y-5">
    <section class="online-page-heading">
        <div>
            <p class="online-eyebrow">NETWORK OPERATIONS</p>
            <h2 class="online-heading">Online Sessions</h2>
            <p class="online-subheading">Pantau sesi RADIUS aktif, penggunaan data, dan status accounting secara berkala.</p>
        </div>
        <div class="online-heading-meta">
            <span class="online-live-pill"><span class="online-live-dot"></span> Live monitoring</span>
            <span class="online-refresh-note">Refresh setiap 10 detik</span>
        </div>
    </section>

    <section class="online-stats-grid" aria-label="Ringkasan sesi online">
        <article class="online-stat-card online-stat-active">
            <div class="online-stat-topline"><span class="online-stat-label">Online aktif</span><span class="online-stat-icon">●</span></div>
            <p id="stat-active" class="online-stat-value">{{ number_format($totalActive) }}</p>
            <p class="online-stat-footnote">Accounting masih berjalan</p>
        </article>
        <article class="online-stat-card online-stat-stale">
            <div class="online-stat-topline"><span class="online-stat-label">Diduga putus</span><span class="online-stat-icon">◷</span></div>
            <p id="stat-stale" class="online-stat-value">{{ number_format($totalStale) }}</p>
            <p class="online-stat-footnote">Perlu pemeriksaan accounting</p>
        </article>
        <article class="online-stat-card online-stat-traffic">
            <div class="online-stat-topline"><span class="online-stat-label">Total upload</span><span class="online-stat-icon">↑</span></div>
            <p id="stat-upload" class="online-stat-value online-stat-data">{{ \App\Models\Radacct::formatBytes($totalUpload) }}</p>
            <p class="online-stat-footnote">Akumulasi sesi aktif</p>
        </article>
        <article class="online-stat-card online-stat-traffic">
            <div class="online-stat-topline"><span class="online-stat-label">Total download</span><span class="online-stat-icon">↓</span></div>
            <p id="stat-download" class="online-stat-value online-stat-data">{{ \App\Models\Radacct::formatBytes($totalDownload) }}</p>
            <p class="online-stat-footnote">Akumulasi sesi aktif</p>
        </article>
    </section>

    <section class="online-panel">
        <div class="online-panel-heading">
            <div>
                <h3 class="online-panel-title">Session inventory</h3>
                <p class="online-panel-description">Filter berdasarkan jenis akun, NAS, atau status sesi.</p>
            </div>
            <span class="online-result-count">{{ number_format($sessions->total()) }} sesi</span>
        </div>
        <form method="GET" class="online-filter-form" id="online-filter-form">
            <label class="online-filter-field"><span>Tipe akun</span>
                <select name="type" class="form-select-sm">
                    <option value="">Semua tipe</option>
                    <option value="voucher" @selected(request('type')==='voucher')>Voucher</option>
                    <option value="member" @selected(request('type')==='member')>Member</option>
                </select>
            </label>
            <label class="online-filter-field"><span>NAS / Router IP</span>
                <select name="nas" class="form-select-sm">
                    <option value="">Semua NAS</option>
                    @foreach($nasIps as $ip)
                    <option value="{{ $ip }}" @selected(request('nas')===$ip)>{{ $ip }}</option>
                    @endforeach
                </select>
            </label>
            <label class="online-filter-field"><span>Status sesi</span>
                <select name="filter" class="form-select-sm">
                    <option value="">Semua status</option>
                    <option value="active" @selected(request('filter')==='active')>Aktif</option>
                    <option value="stale" @selected(request('filter')==='stale')>Diduga putus</option>
                </select>
            </label>
            <div class="online-filter-actions">
                <button type="submit" class="btn-sm-primary">Terapkan filter</button>
                <a href="{{ route('online.index') }}" class="btn-sm-secondary">Reset</a>
            </div>
        </form>
        <div class="online-table-wrap">
            <div class="table-scroll table-compact">
                <table class="data-table online-table">
                    <thead><tr>
                        <th>Username</th><th>Tipe</th><th>NAS IP</th><th>IP client</th><th>Login</th><th>Durasi</th><th>Data ↑ / ↓</th><th>Status</th><th class="text-right">Aksi</th>
                    </tr></thead>
                    <tbody id="online-rows">@include('online.partials.rows')</tbody>
                </table>
            </div>
        </div>
        @if($sessions->hasPages())
        <div class="online-pagination">{{ $sessions->links() }}</div>
        @endif
    </section>
</div>
@endsection

@push('scripts')
<script>
(() => {
    const intervalMs = 10000;
    const liveUrl = @json(route('online.live'));
    const rows = document.getElementById('online-rows');
    const status = document.getElementById('live-status');
    const dot = document.getElementById('live-dot');
    const lastRefresh = document.getElementById('last-refresh');
    const refreshButton = document.getElementById('refresh-online');
    const refreshIcon = document.getElementById('refresh-icon');
    let timer = null;
    let controller = null;
    let busy = false;

    function setLiveState(ok, text) {
        status.textContent = text;
        dot.classList.toggle('is-warning', !ok);
    }

    function currentParams() {
        return new URLSearchParams(window.location.search);
    }

    async function refreshOnline() {
        if (busy || document.hidden) return;
        busy = true;
        controller?.abort();
        controller = new AbortController();
        refreshIcon.classList.add('animate-spin');

        try {
            const params = currentParams();
            const response = await fetch(`${liveUrl}?${params.toString()}`, {
                headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                cache: 'no-store',
                signal: controller.signal,
            });
            if (!response.ok) throw new Error(`HTTP ${response.status}`);
            const data = await response.json();
            document.getElementById('stat-active').textContent = data.stats.active;
            document.getElementById('stat-stale').textContent = data.stats.stale;
            document.getElementById('stat-upload').textContent = data.stats.upload;
            document.getElementById('stat-download').textContent = data.stats.download;
            rows.innerHTML = data.rows;
            lastRefresh.textContent = new Date().toLocaleTimeString('id-ID', {
                hour: '2-digit', minute: '2-digit', second: '2-digit', hour12: false
            });
            setLiveState(true, 'Live · update otomatis 10 detik');
        } catch (error) {
            if (error.name !== 'AbortError') setLiveState(false, 'Live · gagal update, coba lagi');
        } finally {
            busy = false;
            refreshIcon.classList.remove('animate-spin');
        }
    }

    function schedule() {
        clearTimeout(timer);
        timer = setTimeout(async () => {
            await refreshOnline();
            schedule();
        }, intervalMs);
    }

    refreshButton?.addEventListener('click', async () => {
        await refreshOnline();
        schedule();
    });
    document.addEventListener('visibilitychange', () => {
        if (!document.hidden) { refreshOnline(); schedule(); }
        else clearTimeout(timer);
    });
    schedule();
})();
</script>
@endpush
