@extends('layouts.app')
@section('title', 'Daftar Member')

@section('topbar-actions')
    <a href="{{ route('members.create') }}" class="btn-primary">
        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
        Tambah Member
    </a>
@endsection

@section('content')
<div class="space-y-5">

    <div class="card">
        <div class="card-body py-3">
            <form method="GET" class="flex flex-wrap gap-3 items-center">
                <input type="text" name="search" value="{{ request('search') }}"
                    placeholder="Cari username..."
                    class="form-input w-52">
                <select name="plan_id" class="form-input w-52">
                    <option value="">Semua Paket</option>
                    @foreach ($plans as $plan)
                        <option value="{{ $plan->id }}" {{ request('plan_id') == $plan->id ? 'selected' : '' }}>
                            {{ $plan->name }}
                        </option>
                    @endforeach
                </select>
                <select name="status" class="form-input w-40">
                    <option value="">Semua Status</option>
                    <option value="active"   {{ request('status') === 'active'   ? 'selected' : '' }}>Aktif</option>
                    <option value="isolated" {{ request('status') === 'isolated' ? 'selected' : '' }}>Isolir</option>
                    <option value="expired"  {{ request('status') === 'expired'  ? 'selected' : '' }}>Expired</option>
                    <option value="inactive" {{ request('status') === 'inactive' ? 'selected' : '' }}>Nonaktif</option>
                </select>
                <button type="submit" class="btn-secondary">Filter</button>
                @if(request()->anyFilled(['search','plan_id','status']))
                    <a href="{{ route('members.index') }}" class="btn-ghost">Reset</a>
                @endif
            </form>
        </div>
    </div>

    <div class="card overflow-hidden">
        @if($members->isEmpty())
            <div class="py-16 flex flex-col items-center text-center">
                <svg class="text-slate-300 mb-3" width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/></svg>
                <p class="text-slate-500 font-medium">Belum ada member</p>
                <p class="text-slate-400 text-sm mt-1 mb-4">Tambah member pertama untuk mulai layanan berlangganan.</p>
                <a href="{{ route('members.create') }}" class="btn-primary">Tambah Member</a>
            </div>
        @else
            <table class="w-full text-sm">
                <thead class="bg-slate-50 border-b border-slate-200">
                    <tr>
                        <th class="text-left px-4 py-3 font-medium text-slate-600">USERNAME</th>
                        <th class="text-left px-4 py-3 font-medium text-slate-600">PAKET</th>
                        <th class="text-left px-4 py-3 font-medium text-slate-600">HARGA</th>
                        <th class="text-left px-4 py-3 font-medium text-slate-600">AKTIF SEJAK</th>
                        <th class="text-left px-4 py-3 font-medium text-slate-600">EXPIRED</th>
                        <th class="text-left px-4 py-3 font-medium text-slate-600">STATUS</th>
                        <th class="px-4 py-3"></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @foreach ($members as $member)
                    <tr class="hover:bg-slate-50 transition-colors">
                        <td class="px-4 py-3">
                            <a href="{{ route('members.show', $member) }}" class="font-mono font-medium text-blue-600 hover:underline">
                                {{ $member->username }}
                            </a>
                        </td>
                        <td class="px-4 py-3 text-slate-600">{{ $member->plan?->name ?? '-' }}</td>
                        <td class="px-4 py-3 text-slate-600">{{ $member->price_snapshot_label }}</td>
                        <td class="px-4 py-3 text-slate-500 text-xs">{{ $member->activated_at?->format('d M Y') ?? '-' }}</td>
                        <td class="px-4 py-3 text-slate-500 text-xs">
                            @if($member->expired_at)
                                <span class="{{ $member->expired_at->isPast() ? 'text-red-500 font-medium' : '' }}">
                                    {{ $member->expired_at->format('d M Y') }}
                                </span>
                            @else
                                -
                            @endif
                        </td>
                        <td class="px-4 py-3">
                            @php
                                $color = match($member->status) {
                                    'active'   => 'bg-green-100 text-green-700',
                                    'isolated' => 'bg-red-100 text-red-700',
                                    'expired'  => 'bg-gray-100 text-gray-600',
                                    default    => 'bg-yellow-100 text-yellow-700',
                                };
                            @endphp
                            <span class="px-2 py-0.5 rounded-full text-xs font-medium {{ $color }}">
                                {{ $member->status_label }}
                            </span>
                        </td>
                        <td class="px-4 py-3">
                            <div class="flex items-center gap-2 justify-end">
                                <a href="{{ route('members.edit', $member) }}" class="text-slate-400 hover:text-blue-600" title="Edit">
                                    <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/></svg>
                                </a>

                                {{-- Toggle Isolir/Aktif — pakai modal konfirmasi --}}
                                @if($member->status === 'active')
                                    <button type="button"
                                        data-modal-open="modal-isolir-{{ $member->id }}"
                                        title="Isolir"
                                        class="text-slate-400 hover:text-red-500">
                                        <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="4.93" y1="4.93" x2="19.07" y2="19.07"/></svg>
                                    </button>
                                    <x-confirm-modal
                                        id="modal-isolir-{{ $member->id }}"
                                        title="Isolir Member?"
                                        message="Member {{ $member->username }} tidak bisa login sampai Anda aktifkan kembali."
                                        confirm-label="Ya, Isolir"
                                        confirm-class="btn-danger"
                                        action-url="{{ route('members.toggle-status', $member) }}"
                                        preference-key="confirm_isolir"
                                    />
                                @else
                                    {{-- Non-active: langsung aktifkan tanpa modal --}}
                                    <form method="POST" action="{{ route('members.toggle-status', $member) }}">
                                        @csrf @method('PATCH')
                                        <button type="submit"
                                            title="Aktifkan"
                                            class="text-slate-400 hover:text-green-600">
                                            <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg>
                                        </button>
                                    </form>
                                @endif

                                <form method="POST" action="{{ route('members.destroy', $member) }}"
                                    onsubmit="return confirm('Hapus member {{ addslashes($member->username) }}?')">
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
            @if($members->hasPages())
            <div class="px-4 py-3 border-t border-slate-200">
                {{ $members->links() }}
            </div>
            @endif
        @endif
    </div>

</div>
@endsection
