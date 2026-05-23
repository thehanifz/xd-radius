@extends('layouts.app')
@section('title', 'Manajemen Router')

@section('topbar-actions')
    <a href="{{ route('routers.create') }}" class="btn-primary">
        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
        Tambah Router
    </a>
@endsection

@section('content')
<div class="space-y-5">

    <div class="card overflow-hidden">
        @if($routers->isEmpty())
            <div class="py-16 flex flex-col items-center text-center">
                <svg class="text-slate-300 mb-3" width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                    <rect x="2" y="2" width="20" height="8" rx="2" ry="2"/>
                    <rect x="2" y="14" width="20" height="8" rx="2" ry="2"/>
                    <line x1="6" y1="6" x2="6.01" y2="6"/><line x1="6" y1="18" x2="6.01" y2="18"/>
                </svg>
                <p class="text-slate-500 font-medium">Belum ada router</p>
                <p class="text-slate-400 text-sm mt-1 mb-4">Tambah router/NAS pertama untuk manajemen jaringan.</p>
                <a href="{{ route('routers.create') }}" class="btn-primary">Tambah Router</a>
            </div>
        @else
            <table class="w-full text-sm">
                <thead class="bg-slate-50 border-b border-slate-200">
                    <tr>
                        <th class="text-left px-4 py-3 font-medium text-slate-600">NAMA</th>
                        <th class="text-left px-4 py-3 font-medium text-slate-600">IP ADDRESS</th>
                        <th class="text-left px-4 py-3 font-medium text-slate-600">PORT</th>
                        <th class="text-left px-4 py-3 font-medium text-slate-600">LOKASI</th>
                        <th class="text-left px-4 py-3 font-medium text-slate-600">KONEKSI</th>
                        <th class="text-left px-4 py-3 font-medium text-slate-600">STATUS</th>
                        <th class="px-4 py-3"></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @foreach ($routers as $router)
                    <tr class="hover:bg-slate-50 transition-colors">
                        <td class="px-4 py-3">
                            <a href="{{ route('routers.show', $router) }}" class="font-medium text-blue-600 hover:underline">
                                {{ $router->name }}
                            </a>
                        </td>
                        <td class="px-4 py-3 font-mono text-slate-600">{{ $router->ip_address }}</td>
                        <td class="px-4 py-3 text-slate-500">{{ $router->api_port }}</td>
                        <td class="px-4 py-3 text-slate-500">{{ $router->location ?? '-' }}</td>
                        <td class="px-4 py-3">
                            @php
                                $connColor = match($router->last_connection_status) {
                                    'ok'    => 'bg-green-100 text-green-700',
                                    'error' => 'bg-red-100 text-red-700',
                                    default => 'bg-slate-100 text-slate-500',
                                };
                            @endphp
                            <span class="px-2 py-0.5 rounded-full text-xs font-medium {{ $connColor }}">
                                {{ $router->connection_status_label }}
                            </span>
                            @if($router->last_connected_at)
                                <p class="text-[10px] text-slate-400 mt-0.5">{{ $router->last_connected_at->diffForHumans() }}</p>
                            @endif
                        </td>
                        <td class="px-4 py-3">
                            @php
                                $statusColor = $router->is_active
                                    ? 'bg-green-100 text-green-700'
                                    : 'bg-slate-100 text-slate-500';
                            @endphp
                            <span class="px-2 py-0.5 rounded-full text-xs font-medium {{ $statusColor }}">
                                {{ $router->status_label }}
                            </span>
                        </td>
                        <td class="px-4 py-3">
                            <div class="flex items-center gap-2 justify-end">
                                <a href="{{ route('routers.edit', $router) }}" class="text-slate-400 hover:text-blue-600" title="Edit">
                                    <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/></svg>
                                </a>
                                <form method="POST" action="{{ route('routers.toggle', $router) }}">
                                    @csrf @method('PATCH')
                                    <button type="submit"
                                        title="{{ $router->is_active ? 'Nonaktifkan' : 'Aktifkan' }}"
                                        class="{{ $router->is_active ? 'text-slate-400 hover:text-amber-500' : 'text-slate-400 hover:text-green-600' }}">
                                        @if($router->is_active)
                                            <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="4.93" y1="4.93" x2="19.07" y2="19.07"/></svg>
                                        @else
                                            <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg>
                                        @endif
                                    </button>
                                </form>
                                <form method="POST" action="{{ route('routers.destroy', $router) }}"
                                    onsubmit="return confirm('Hapus router {{ addslashes($router->name) }}?')">
                                    @csrf @method('DELETE')
                                    <button type="submit" class="text-slate-400 hover:text-red-500" title="Hapus">
                                        <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="3 6 5 6 21 6"/><path d="M19 6l-1 14a2 2 0 0 1-2 2H8a2 2 0 0 1-2-2L5 6"/><path d="M10 11v6"/><path d="M14 11v6"/><path d="M9 6V4h6v2"/></svg>
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
            @if($routers->hasPages())
            <div class="px-4 py-3 border-t border-slate-200">
                {{ $routers->links() }}
            </div>
            @endif
        @endif
    </div>

</div>
@endsection
