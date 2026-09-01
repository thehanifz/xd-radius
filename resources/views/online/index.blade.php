@extends('layouts.app')
@section('title', 'User Online')

@section('topbar-actions')
<div class="flex items-center gap-3">
    <span class="text-xs text-slate-400" id="last-refresh">Refresh: {{ now()->format('H:i:s') }}</span>
    <button onclick="location.reload()" class="btn-sm-secondary flex items-center gap-1.5">
        <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round">
            <polyline points="23 4 23 10 17 10"/>
            <path d="M20.49 15a9 9 0 1 1-2.12-9.36L23 10"/>
        </svg>
        Refresh
    </button>
</div>
@endsection

@section('content')

{{-- Summary Cards --}}
<div class="grid grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
    <div class="stat-card">
        <p class="stat-label">Online Aktif</p>
        <p class="stat-value text-green-600">{{ $totalActive }}</p>
    </div>
    <div class="stat-card">
        <p class="stat-label">Diduga Putus</p>
        <p class="stat-value text-slate-400">{{ $totalStale }}</p>
    </div>
    <div class="stat-card">
        <p class="stat-label">Upload Aktif</p>
        <p class="stat-value text-sm lg:text-xl">{{ \App\Models\Radacct::formatBytes($totalUpload) }}</p>
    </div>
    <div class="stat-card">
        <p class="stat-label">Download Aktif</p>
        <p class="stat-value text-sm lg:text-xl">{{ \App\Models\Radacct::formatBytes($totalDownload) }}</p>
    </div>
</div>

{{-- Filter --}}
<form method="GET" class="flex flex-wrap gap-3 mb-5">
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
    <button type="submit" class="btn-primary-sm">Filter</button>
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
            <tbody>
            @forelse($sessions as $s)
            @php
                $isStale    = $s->is_stale;
                $isVoucher  = isset($voucherUsers[$s->username]);
                $type       = $isVoucher ? 'Voucher' : 'Member';
                $uploadMB   = round(($s->acctoutputoctets ?? 0) / 1048576, 2);
                $downloadMB = round(($s->acctinputoctets ?? 0) / 1048576, 2);
            @endphp
            <tr class="{{ $isStale ? 'opacity-60' : '' }}">
                <td class="font-mono text-sm font-semibold">{{ $s->username }}</td>
                <td><span class="badge-{{ $isVoucher ? 'info' : 'purple' }} text-xs">{{ $type }}</span></td>
                <td class="text-slate-500 text-sm">{{ $s->nasipaddress }}</td>
                <td class="text-slate-500 text-sm font-mono">{{ $s->framedipaddress ?? '-' }}</td>
                <td class="text-slate-500 text-sm">{{ $s->acctstarttime?->format('d/m H:i') ?? '-' }}</td>
                <td class="text-slate-500 text-sm">{{ $s->duration }}</td>
                <td class="text-slate-500 text-xs whitespace-nowrap">↑ {{ $s->upload_label }} / ↓ {{ $s->download_label }}</td>
                <td>
                    @if($isStale)
                        <span class="badge-isolated text-xs">Diduga Putus</span>
                    @else
                        <span class="badge-active text-xs">Online</span>
                    @endif
                </td>
                <td class="text-right whitespace-nowrap">
                    <a href="{{ route('online.show', $s) }}" class="btn-sm-secondary mr-1">Detail</a>
                    <button disabled
                        title="Disconnect akan tersedia setelah integrasi RouterOS"
                        class="btn-sm-danger opacity-40 cursor-not-allowed">
                        Disconnect
                    </button>
                </td>
            </tr>
            @empty
            <tr><td colspan="9" class="text-center text-slate-400 py-12">
                <svg class="mx-auto mb-3 w-10 h-10 text-slate-200" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><circle cx="12" cy="12" r="10"/><path d="M8 12h8"/></svg>
                Tidak ada sesi aktif
            </td></tr>
            @endforelse
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
// Auto-refresh setiap 30 detik
setTimeout(() => location.reload(), 30000);
</script>
@endpush
