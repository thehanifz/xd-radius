@extends('layouts.app')
@section('title', $router->name)

@section('topbar-actions')
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
<div class="max-w-2xl space-y-5">

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
            <h3 class="font-semibold text-slate-700 text-sm">Status Koneksi API</h3>
        </div>
        <div class="card-body">
            @php
                $connColor = match($router->last_connection_status) {
                    'ok'    => 'bg-green-50 border-green-200 text-green-800',
                    'error' => 'bg-red-50 border-red-200 text-red-800',
                    default => 'bg-slate-50 border-slate-200 text-slate-600',
                };
            @endphp
            <div class="px-4 py-3 rounded-lg border {{ $connColor }} text-sm">
                <p class="font-medium">{{ $router->connection_status_label }}</p>
                @if($router->last_connection_error)
                    <p class="mt-1 font-mono text-xs opacity-75">{{ $router->last_connection_error }}</p>
                @endif
            </div>

            <p class="text-xs text-slate-400 mt-3">
                Koneksi uji otomatis akan tersedia saat fitur Multi-NAS diaktifkan.
                Pastikan API MikroTik diaktifkan di IP → Services → API.
            </p>
        </div>
    </div>

    {{-- Toggle Aktif --}}
    <div class="card">
        <div class="card-body flex items-center justify-between">
            <div>
                <p class="text-sm font-medium text-slate-700">Status Router</p>
                <p class="text-xs text-slate-500 mt-0.5">
                    Router nonaktif tidak akan digunakan untuk CoA/disconnect.
                </p>
            </div>
            <form method="POST" action="{{ route('routers.toggle', $router) }}">
                @csrf @method('PATCH')
                <button type="submit"
                    class="{{ $router->is_active ? 'btn-danger' : 'btn-primary' }}">
                    {{ $router->is_active ? 'Nonaktifkan' : 'Aktifkan' }}
                </button>
            </form>
        </div>
    </div>

</div>
@endsection
