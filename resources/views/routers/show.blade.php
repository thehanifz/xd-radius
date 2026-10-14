@extends('layouts.app')
@section('title', $router->name)

@section('topbar-actions')
    <form method="POST" action="{{ route('routers.test-connection', $router) }}" class="inline">
        @csrf
        <button type="submit" class="btn-secondary" title="Uji login dan query RouterOS API">
            <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M4 12h16M12 4v16"/><circle cx="12" cy="12" r="9"/></svg>
            <span class="hidden sm:inline">Tes API</span><span class="sm:hidden">API</span>
        </button>
    </form>
    <a href="{{ route('routers.edit', $router) }}" class="btn-secondary">
        <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/></svg>
        Edit
    </a>
    <form method="POST" action="{{ route('routers.destroy', $router) }}"
          onsubmit="return confirm('Hapus router ini?')" class="inline">
        @csrf @method('DELETE')
        <button type="submit" class="btn-danger">
            <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="3 6 5 6 21 6"/><path d="M19 6l-1 14a2 2 0 0 1-2 2H8a2 2 0 0 1-2-2L5 6"/></svg>
            Hapus
        </button>
    </form>
@endsection

@section('content')
<div class="router-page max-w-4xl space-y-5">

    {{-- Ringkasan resource --}}
    @if($router->last_api_status === 'ok')
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-3">
        <div class="card p-4"><p class="stat-label">RouterOS</p><p class="font-semibold text-slate-800 mt-1">{{ $router->routeros_version ?? '-' }}</p></div>
        <div class="card p-4"><p class="stat-label">CPU</p><p class="font-semibold text-slate-800 mt-1">{{ $router->connection_cpu_load ?? '-' }}{{ $router->connection_cpu_load !== null ? '%' : '' }}</p></div>
        <div class="card p-4"><p class="stat-label">Memory</p><p class="font-semibold text-slate-800 mt-1">{{ $router->connection_memory_label ?? '-' }}</p></div>
        <div class="card p-4"><p class="stat-label">Uptime</p><p class="font-semibold text-slate-800 mt-1">{{ $router->connection_uptime ?? '-' }}</p></div>
    </div>
    @endif

    {{-- Info Utama --}}
    <div class="card">
        <div class="card-header flex items-center justify-between">
            <div>
                <h2 class="font-semibold text-slate-800">{{ $router->name }}</h2>
                <p class="text-sm text-slate-500 mt-0.5">{{ $router->location ?? 'Lokasi tidak ditentukan' }}</p>
            </div>
            @php
                $statusColor = $router->is_active
                    ? 'bg-green-100 text-green-700'
                    : 'bg-slate-100 text-slate-500';
            @endphp
            <span class="px-3 py-1 rounded-full text-xs font-semibold {{ $statusColor }}">
                {{ $router->status_label }}
            </span>
        </div>
        <div class="card-body">
            <dl class="grid grid-cols-2 gap-x-6 gap-y-4 text-sm">
                <div>
                    <dt class="text-xs font-medium text-slate-500 uppercase tracking-wide">IP Address</dt>
                    <dd class="font-mono font-medium text-slate-800 mt-1">{{ $router->ip_address }}</dd>
                </div>
                <div>
                    <dt class="text-xs font-medium text-slate-500 uppercase tracking-wide">API Port</dt>
                    <dd class="font-mono text-slate-800 mt-1">{{ $router->api_port }}</dd>
                </div>
                <div>
                    <dt class="text-xs font-medium text-slate-500 uppercase tracking-wide">Username API</dt>
                    <dd class="font-mono text-slate-800 mt-1">{{ $router->api_username }}</dd>
                </div>
                <div>
                    <dt class="text-xs font-medium text-slate-500 uppercase tracking-wide">Password API</dt>
                    <dd class="text-slate-400 mt-1 italic">••••••••</dd>
                </div>
                <div>
                    <dt class="text-xs font-medium text-slate-500 uppercase tracking-wide">RouterOS Version</dt>
                    <dd class="text-slate-800 mt-1">{{ $router->routeros_version ?? '-' }}</dd>
                </div>
                <div>
                    <dt class="text-xs font-medium text-slate-500 uppercase tracking-wide">Terakhir Terhubung</dt>
                    <dd class="text-slate-800 mt-1">
                        @if($router->last_connected_at)
                            {{ $router->last_connected_at->format('d M Y H:i') }}
                            <span class="text-xs text-slate-400">({{ $router->last_connected_at->diffForHumans() }})</span>
                        @else
                            <span class="text-slate-400">Belum pernah</span>
                        @endif
                    </dd>
                </div>
            </dl>
        </div>
    </div>

    {{-- Status Koneksi --}}
    <div class="card">
        <div class="card-header">
            <h3 class="font-semibold text-slate-700 text-sm">Pemeriksaan Koneksi</h3>
        </div>
        <div class="card-body">
            <div class="grid gap-3 sm:grid-cols-2">
                <div class="rounded-lg border px-4 py-3 {{ $router->last_ping_status === 'ok' && $router->ping_is_fresh ? 'border-green-200 bg-green-50 text-green-800' : ($router->last_ping_status === 'error' && $router->ping_is_fresh ? 'border-red-200 bg-red-50 text-red-800' : 'border-slate-200 bg-slate-50 text-slate-600') }}">
                    <p class="text-xs font-semibold uppercase tracking-wide">Ping · 2 paket</p>
                    <p class="mt-1 text-sm font-medium">
                        {{ $router->ping_status_label }}
                        @if($router->last_ping_status === 'ok' && $router->ping_is_fresh && $router->last_ping_latency_ms !== null)
                            · {{ $router->last_ping_latency_ms }} ms
                        @endif
                    </p>
                    @if($router->last_ping_at)
                        <p class="mt-1 text-xs opacity-70">{{ $router->last_ping_at->diffForHumans() }}</p>
                    @endif
                    @if($router->last_ping_error)
                        <p class="mt-1 break-words font-mono text-xs opacity-75">{{ $router->last_ping_error }}</p>
                    @endif
                </div>
                <div class="rounded-lg border px-4 py-3 {{ $router->last_api_status === 'ok' ? 'border-green-200 bg-green-50 text-green-800' : ($router->last_api_status === 'error' ? 'border-red-200 bg-red-50 text-red-800' : 'border-slate-200 bg-slate-50 text-slate-600') }}">
                    <p class="text-xs font-semibold uppercase tracking-wide">RouterOS API</p>
                    <p class="mt-1 text-sm font-medium">{{ $router->api_status_label }}</p>
                    @if($router->last_api_at)
                        <p class="mt-1 text-xs opacity-70">{{ $router->last_api_at->diffForHumans() }}</p>
                    @endif
                    @if($router->last_api_error)
                        <p class="mt-1 break-words font-mono text-xs opacity-75">{{ $router->last_api_error }}</p>
                    @endif
                </div>
            </div>
            <p class="mt-3 text-xs text-slate-500">Status koneksi: <strong>{{ $router->connection_status_label }}</strong>. Ping otomatis berjalan setiap menit; hasil berumur lebih dari 3 menit ditandai usang.</p>
            <p class="text-xs text-slate-400 mt-2">Tes API hanya berjalan saat tombol Tes API ditekan. Tes ini memeriksa login dan membaca resource router.</p>
        </div>
    </div>

    {{-- Status Operasional --}}
    <div class="card">
        <div class="card-body flex items-center justify-between gap-4">
            <div>
                <p class="text-sm font-medium text-slate-700">Status Router & FreeRADIUS</p>
                <p class="text-xs text-slate-500 mt-0.5">Satu status mengontrol RouterOS API dan client FreeRADIUS.</p>
            </div>
            <form method="POST" action="{{ route('routers.toggle', $router) }}">
                @csrf @method('PATCH')
                <button type="submit" class="{{ $router->is_active && $router->radius_enabled ? 'btn-danger' : 'btn-primary' }}">
                    {{ $router->is_active && $router->radius_enabled ? 'Nonaktifkan' : 'Aktifkan' }}
                </button>
            </form>
        </div>
    </div>

</div>
@endsection
