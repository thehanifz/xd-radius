@extends('layouts.app')
@section('title', 'User Online')

@section('topbar-actions')
<div class="flex items-center gap-3">
    <span class="inline-flex items-center gap-1.5 text-xs text-slate-500" aria-live="polite">
        <span id="live-dot" class="w-1.5 h-1.5 rounded-full bg-green-500"></span>
        <span id="live-status">Live · update otomatis 10 detik</span>
        <span class="text-slate-300">·</span>
        <span id="last-refresh">{{ now()->format('H:i:s') }}</span>
    </span>
    <button type="button" id="refresh-online" class="btn-sm-secondary flex items-center gap-1.5">
        <svg id="refresh-icon" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round">
            <polyline points="23 4 23 10 17 10"/>
            <path d="M20.49 15a9 9 0 1 1-2.12-9.36L23 10"/>
        </svg>
        Refresh data
    </button>
</div>
@endsection

@section('content')

{{-- Compact live stats --}}
<div class="card mb-5 overflow-hidden">
    <div class="grid grid-cols-2 lg:grid-cols-4 divide-x divide-slate-100">
        <div class="px-4 py-3.5">
            <p class="stat-label">Online Aktif</p>
            <p id="stat-active" class="text-xl font-semibold leading-6 text-green-600">{{ $totalActive }}</p>
        </div>
        <div class="px-4 py-3.5">
            <p class="stat-label">Diduga Putus</p>
            <p id="stat-stale" class="text-xl font-semibold leading-6 text-slate-400">{{ $totalStale }}</p>
        </div>
        <div class="px-4 py-3.5">
            <p class="stat-label">Upload Aktif</p>
            <p id="stat-upload" class="text-xl font-semibold leading-6 text-slate-900">{{ \App\Models\Radacct::formatBytes($totalUpload) }}</p>
        </div>
        <div class="px-4 py-3.5">
            <p class="stat-label">Download Aktif</p>
            <p id="stat-download" class="text-xl font-semibold leading-6 text-slate-900">{{ \App\Models\Radacct::formatBytes($totalDownload) }}</p>
        </div>
    </div>
</div>

{{-- Filter --}}
<form method="GET" class="flex flex-wrap gap-3 mb-5" id="online-filter-form">
    <select name="type" class="form-select-sm">
        <option value="">Semua Tipe</option>
        <option value="voucher" @selected(request('type')==='voucher')>Voucher</option>
        <option value="member" @selected(request('type')==='member')>Member</option>
    </select>
    <select name="nas" class="form-select-sm">
        <option value="">Semua NAS</option>
        @foreach($nasIps as $ip)
        <option value="{{ $ip }}" @selected(request('nas')===$ip)>{{ $ip }}</option>
        @endforeach
    </select>
    <select name="filter" class="form-select-sm">
        <option value="">Semua Status</option>
        <option value="active" @selected(request('filter')==='active')>Aktif Saja</option>
        <option value="stale" @selected(request('filter')==='stale')>Diduga Putus</option>
    </select>
    <button type="submit" class="btn-sm-primary">Filter</button>
    <a href="{{ route('online.index') }}" class="btn-sm-secondary">Reset</a>
</form>

{{-- Table --}}
<div class="card">
    <div class="overflow-x-auto">
        <div class="table-scroll table-compact">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Username</th>
                        <th>Tipe</th>
                        <th>NAS IP</th>
                        <th>IP Client</th>
                        <th>Login</th>
                        <th>Durasi</th>
                        <th>Data ↑/↓</th>
                        <th>Status</th>
                        <th class="text-right">Aksi</th>
                    </tr>
                </thead>
                <tbody id="online-rows">
                    @include('online.partials.rows')
                </tbody>
            </table>
        </div>
    </div>
    @if($sessions->hasPages())
    <div class="p-4 border-t border-slate-100">{{ $sessions->links() }}</div>
    @endif
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
        dot.className = ok
            ? 'w-1.5 h-1.5 rounded-full bg-green-500'
            : 'w-1.5 h-1.5 rounded-full bg-amber-500';
    }

    function currentParams() {
        const params = new URLSearchParams(window.location.search);
        return params;
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
            if (error.name !== 'AbortError') {
                setLiveState(false, 'Live · gagal update, coba lagi');
            }
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
        if (!document.hidden) {
            refreshOnline();
            schedule();
        } else {
            clearTimeout(timer);
        }
    });

    // First background sync shortly after page load, then every 10 seconds.
    schedule();
})();
</script>
@endpush
