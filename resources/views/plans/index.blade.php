@extends('layouts.app')
@section('title', 'Paket Internet')

@section('topbar-actions')
    <a href="{{ route('plans.create') }}" class="btn-primary">
        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
        Tambah Paket
    </a>
@endsection

@section('content')
<div class="space-y-5">

    {{-- Stats --}}
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
        @foreach([
            ['label' => 'Total Paket',   'value' => $stats['total'],   'color' => 'text-slate-800'],
            ['label' => 'Paket Aktif',   'value' => $stats['active'],  'color' => 'text-green-600'],
            ['label' => 'Tipe Voucher',  'value' => $stats['voucher'], 'color' => 'text-blue-600'],
            ['label' => 'Tipe Member',   'value' => $stats['member'],  'color' => 'text-indigo-600'],
        ] as $s)
        <div class="card">
            <div class="card-body py-4">
                <p class="text-xs text-slate-500">{{ $s['label'] }}</p>
                <p class="text-2xl font-bold {{ $s['color'] }}">{{ $s['value'] }}</p>
            </div>
        </div>
        @endforeach
    </div>

    {{-- Filter & Search --}}
    <div class="card">
        <div class="card-body py-3">
            <form method="GET" class="flex flex-wrap gap-3 items-center">
                <input type="text" name="search" value="{{ request('search') }}"
                    placeholder="Cari nama paket atau group..."
                    class="form-input w-64">

                <select name="type" class="form-input w-40">
                    <option value="">Semua Tipe</option>
                    <option value="voucher" @selected(request('type') === 'voucher')>Voucher</option>
                    <option value="member"  @selected(request('type') === 'member')>Member</option>
                </select>

                <select name="status" class="form-input w-40">
                    <option value="">Semua Status</option>
                    <option value="active"   @selected(request('status') === 'active')>Aktif</option>
                    <option value="inactive" @selected(request('status') === 'inactive')>Nonaktif</option>
                </select>

                <button type="submit" class="btn-secondary">Filter</button>
                @if(request()->hasAny(['search','type','status']))
                    <a href="{{ route('plans.index') }}" class="text-sm text-slate-500 hover:text-slate-700">Reset</a>
                @endif
            </form>
        </div>
    </div>

    {{-- Table --}}
    <div class="card overflow-hidden">
        @if($plans->isEmpty())
        <div class="py-16 flex flex-col items-center text-center">
            <svg class="text-slate-300 mb-3" width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"/></svg>
            <p class="text-slate-500 font-medium">Belum ada paket internet</p>
            <p class="text-slate-400 text-sm mt-1 mb-4">Buat paket pertama untuk mulai generate voucher atau daftarkan member.</p>
            <a href="{{ route('plans.create') }}" class="btn-primary">Tambah Paket Pertama</a>
        </div>
        @else
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead class="bg-slate-50 border-b border-slate-200">
                    <tr>
                        <th class="text-left px-5 py-3 text-xs font-semibold text-slate-500 uppercase tracking-wide">Nama Paket</th>
                        <th class="text-left px-5 py-3 text-xs font-semibold text-slate-500 uppercase tracking-wide">Tipe</th>
                        <th class="text-left px-5 py-3 text-xs font-semibold text-slate-500 uppercase tracking-wide">Kecepatan</th>
                        <th class="text-left px-5 py-3 text-xs font-semibold text-slate-500 uppercase tracking-wide">Durasi</th>
                        <th class="text-left px-5 py-3 text-xs font-semibold text-slate-500 uppercase tracking-wide">Harga</th>
                        <th class="text-left px-5 py-3 text-xs font-semibold text-slate-500 uppercase tracking-wide">RADIUS Group</th>
                        <th class="text-left px-5 py-3 text-xs font-semibold text-slate-500 uppercase tracking-wide">Status</th>
                        <th class="px-5 py-3"></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @foreach($plans as $plan)
                    <tr class="hover:bg-slate-50 transition-colors">
                        <td class="px-5 py-3.5">
                            <p class="font-medium text-slate-800">{{ $plan->name }}</p>
                            @if($plan->description)
                            <p class="text-xs text-slate-400 mt-0.5 truncate max-w-[200px]">{{ $plan->description }}</p>
                            @endif
                        </td>
                        <td class="px-5 py-3.5">
                            @if($plan->type === 'voucher')
                                <span class="badge badge-blue">Voucher</span>
                            @else
                                <span class="badge badge-slate">Member</span>
                            @endif
                        </td>
                        <td class="px-5 py-3.5">
                            <p class="text-slate-700 font-medium tabular-nums">↓ {{ $plan->download_label }}</p>
                            <p class="text-slate-400 text-xs tabular-nums">↑ {{ $plan->upload_label }}</p>
                        </td>
                        <td class="px-5 py-3.5 text-slate-600 tabular-nums">{{ $plan->duration_days }} hari</td>
                        <td class="px-5 py-3.5">
                            <span class="font-semibold text-slate-800 tabular-nums">{{ $plan->price_label }}</span>
                        </td>
                        <td class="px-5 py-3.5">
                            <code class="text-xs bg-slate-100 text-slate-600 px-2 py-0.5 rounded">{{ $plan->radius_group_name }}</code>
                        </td>
                        <td class="px-5 py-3.5">
                            <form method="POST" action="{{ route('plans.toggle', $plan) }}">
                                @csrf @method('PATCH')
                                <button type="submit" class="{{ $plan->is_active ? 'badge-green' : 'badge-red' }} badge cursor-pointer hover:opacity-80 transition-opacity">
                                    {{ $plan->is_active ? 'Aktif' : 'Nonaktif' }}
                                </button>
                            </form>
                        </td>
                        <td class="px-5 py-3.5">
                            <div class="flex items-center gap-2 justify-end">
                                <a href="{{ route('plans.edit', $plan) }}" class="text-slate-400 hover:text-blue-600 transition-colors" title="Edit">
                                    <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/></svg>
                                </a>
                                <form method="POST" action="{{ route('plans.destroy', $plan) }}" onsubmit="return confirm('Hapus paket {{ addslashes($plan->name) }}?')">
                                    @csrf @method('DELETE')
                                    <button type="submit" class="text-slate-400 hover:text-red-600 transition-colors" title="Hapus">
                                        <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="3 6 5 6 21 6"/><path d="M19 6l-1 14a2 2 0 0 1-2 2H8a2 2 0 0 1-2-2L5 6"/><path d="M10 11v6"/><path d="M14 11v6"/><path d="M9 6V4h6v2"/></svg>
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        @if($plans->hasPages())
        <div class="px-5 py-3 border-t border-slate-100">
            {{ $plans->links() }}
        </div>
        @endif
        @endif
    </div>

</div>
@endsection
