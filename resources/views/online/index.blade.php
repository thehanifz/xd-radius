@extends('layouts.app')
@section('title', 'Online Sessions')
@section('hide-topbar-title', '1')

@section('content')
<div class="online-page space-y-5">
    <nav class="online-type-tabs" aria-label="Jenis sesi">
        <a href="{{ route('online.index', request()->except(['type', 'page'])) }}"
           class="online-type-tab {{ request('type') ? '' : 'is-active' }}"
           @if(!request('type')) aria-current="page" @endif>
            <span>Semua sesi</span><strong id="type-count-all">{{ number_format($typeCounts['all']) }}</strong>
        </a>
        <a href="{{ route('online.index', array_merge(request()->except(['type', 'page']), ['type' => 'voucher'])) }}"
           class="online-type-tab online-type-tab-voucher {{ request('type') === 'voucher' ? 'is-active' : '' }}"
           @if(request('type') === 'voucher') aria-current="page" @endif>
            <span>Voucher</span><strong id="type-count-voucher">{{ number_format($typeCounts['voucher']) }}</strong>
        </a>
        <a href="{{ route('online.index', array_merge(request()->except(['type', 'page']), ['type' => 'member'])) }}"
           class="online-type-tab online-type-tab-member {{ in_array(request('type'), ['member', 'pppoe'], true) ? 'is-active' : '' }}"
           @if(in_array(request('type'), ['member', 'pppoe'], true)) aria-current="page" @endif>
            <span>Member</span><strong id="type-count-member">{{ number_format($typeCounts['member']) }}</strong>
        </a>
    </nav>

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
            <span class="online-result-count"><span id="online-result-count">{{ number_format($sessions->total()) }}</span> sesi</span>
        </div>
        <form method="GET" class="online-filter-form" id="online-filter-form">
            @if(request('type'))
                <input type="hidden" name="type" value="{{ request('type') }}">
            @endif
            <label class="online-filter-field"><span>Router / MikroTik</span>
                <select name="nas" class="form-select-sm">
                    <option value="">Semua NAS</option>
                    @foreach($nasOptions as $nas)
                    <option value="{{ $nas['ip'] }}" @selected(request('nas')===$nas['ip'])>{{ $nas['name'] }}</option>
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
                        <th>Username</th><th>Tipe</th><th>MikroTik</th><th>IP client</th><th>Login</th><th>Durasi</th><th>Data ↑ / ↓</th><th>Status</th><th class="text-right">Aksi</th>
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
    let timer = null;
    let controller = null;
    let busy = false;

    function currentParams() {
        return new URLSearchParams(window.location.search);
    }

    async function refreshOnline() {
        if (busy || document.hidden) return;
        busy = true;
        controller?.abort();
        controller = new AbortController();

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
            document.getElementById('type-count-all').textContent = Number(data.typeCounts.all).toLocaleString('id-ID');
            document.getElementById('type-count-voucher').textContent = Number(data.typeCounts.voucher).toLocaleString('id-ID');
            document.getElementById('type-count-member').textContent = Number(data.typeCounts.member).toLocaleString('id-ID');
            document.getElementById('online-result-count').textContent = Number(data.resultCount).toLocaleString('id-ID');
            rows.innerHTML = data.rows;
        } catch (error) {
            // Keep polling silently when the live request fails.
        } finally {
            busy = false;
        }
    }

    function schedule() {
        clearTimeout(timer);
        timer = setTimeout(async () => {
            await refreshOnline();
            schedule();
        }, intervalMs);
    }

    document.addEventListener('visibilitychange', () => {
        if (!document.hidden) { refreshOnline(); schedule(); }
        else clearTimeout(timer);
    });
    schedule();
})();
</script>
@endpush
