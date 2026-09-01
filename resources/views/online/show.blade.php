@extends('layouts.app')
@section('title', 'Detail Session')

@section('topbar-actions')
<a href="{{ route('online.index') }}" class="btn-sm-secondary">← Kembali</a>
@endsection

@section('content')
<div class="space-y-6">
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
        <div class="stat-card"><p class="stat-label">Total Session</p><p class="stat-value">{{ number_format($summary['sessions']) }}</p></div>
        <div class="stat-card"><p class="stat-label">Total Durasi</p><p class="stat-value text-sm lg:text-xl">{{ \App\Models\Radacct::formatDuration($summary['duration']) }}</p></div>
        <div class="stat-card"><p class="stat-label">Upload</p><p class="stat-value text-sm lg:text-xl">{{ \App\Models\Radacct::formatBytes($summary['upload']) }}</p></div>
        <div class="stat-card"><p class="stat-label">Download</p><p class="stat-value text-sm lg:text-xl">{{ \App\Models\Radacct::formatBytes($summary['download']) }}</p></div>
    </div>

    <div class="card">
        <div class="card-header">
            <div>
                <h2 class="card-title font-mono">{{ $session->username }}</h2>
                <p class="text-xs text-slate-400 mt-1">Session {{ $session->acctsessionid ?? '-' }}</p>
            </div>
            @if($session->acctstoptime)
                <span class="badge-isolated text-xs">Offline</span>
            @else
                <span class="badge-active text-xs">Online</span>
            @endif
        </div>
        <div class="p-5 grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-5">
            <div><p class="form-label">NAS IP</p><p class="text-sm font-medium">{{ $session->nasipaddress ?? '-' }}</p></div>
            <div><p class="form-label">IP Client</p><p class="text-sm font-mono font-medium">{{ $session->framedipaddress ?? '-' }}</p></div>
            <div><p class="form-label">MAC / Calling Station</p><p class="text-sm font-mono font-medium">{{ $session->callingstationid ?? '-' }}</p></div>
            <div><p class="form-label">Login</p><p class="text-sm font-medium">{{ $session->acctstarttime?->format('d/m/Y H:i:s') ?? '-' }}</p></div>
            <div><p class="form-label">Logout</p><p class="text-sm font-medium">{{ $session->acctstoptime?->format('d/m/Y H:i:s') ?? 'Masih online' }}</p></div>
            <div><p class="form-label">Durasi</p><p class="text-sm font-medium">{{ $session->duration }}</p></div>
            <div><p class="form-label">Upload</p><p class="text-sm font-medium">{{ $session->upload_label }}</p></div>
            <div><p class="form-label">Download</p><p class="text-sm font-medium">{{ $session->download_label }}</p></div>
            <div><p class="form-label">Terminate Cause</p><p class="text-sm font-medium">{{ $session->acctterminatecause ?? '-' }}</p></div>
        </div>
    </div>

    @if($voucher)
    <div class="card">
        <div class="card-header"><h2 class="card-title">Voucher</h2></div>
        <div class="p-5 grid grid-cols-2 md:grid-cols-4 gap-5">
            <div><p class="form-label">Paket</p><p class="text-sm font-semibold">{{ $voucher->plan?->name ?? '-' }}</p></div>
            <div><p class="form-label">First Login</p><p class="text-sm">{{ $voucher->first_login_at?->format('d/m/Y H:i:s') ?? '-' }}</p></div>
            <div><p class="form-label">Expired</p><p class="text-sm">{{ $voucher->expired_at?->format('d/m/Y H:i:s') ?? '-' }}</p></div>
            <div><p class="form-label">Remaining</p><p class="text-sm font-semibold">{{ $voucher->remaining_seconds !== null ? gmdate('z\\d H\\j i\\m s\\d', $voucher->remaining_seconds) : '-' }}</p></div>
        </div>
    </div>
    @endif

    <div class="card">
        <div class="card-header"><h2 class="card-title">Riwayat Session</h2><span class="text-xs text-slate-400">Maks. 30 terakhir</span></div>
        <div class="overflow-x-auto">
            <table class="data-table">
                <thead><tr><th>Start</th><th>Stop</th><th>Durasi</th><th>IP</th><th>Upload</th><th>Download</th><th>Status</th></tr></thead>
                <tbody>
                @foreach($history as $item)
                <tr>
                    <td class="text-sm">{{ $item->acctstarttime?->format('d/m/Y H:i') ?? '-' }}</td>
                    <td class="text-sm text-slate-500">{{ $item->acctstoptime?->format('d/m/Y H:i') ?? '-' }}</td>
                    <td class="text-sm">{{ $item->duration }}</td>
                    <td class="text-xs font-mono text-slate-500">{{ $item->framedipaddress ?? '-' }}</td>
                    <td class="text-xs">{{ $item->upload_label }}</td>
                    <td class="text-xs">{{ $item->download_label }}</td>
                    <td>@if($item->acctstoptime)<span class="badge-isolated text-xs">Selesai</span>@else<span class="badge-active text-xs">Online</span>@endif</td>
                </tr>
                @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
