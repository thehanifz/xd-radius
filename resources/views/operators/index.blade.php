@extends('layouts.app')
@section('title', 'Manajemen Operator')

@section('topbar-actions')
<a href="{{ route('operators.create') }}" class="btn-primary">
    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
    Tambah Operator
</a>
@endsection

@section('content')
<div class="card">
    <div class="card-header">
        <div>
            <h2 class="card-title">Daftar Operator</h2>
            <p class="card-subtitle">Total {{ $operators->total() }} operator terdaftar</p>
        </div>
    </div>

    <div class="overflow-x-auto">
        <table class="data-table">
            <thead>
                <tr>
                    <th>Nama</th>
                    <th>Email</th>
                    <th>Status</th>
                    <th>Dibuat</th>
                    <th class="text-right pr-5">Aksi</th>
                </tr>
            </thead>
            <tbody>
            @forelse($operators as $op)
            <tr>
                <td>
                    <div class="flex items-center gap-3">
                        <div class="w-8 h-8 rounded-xl flex-shrink-0 flex items-center justify-center font-bold text-xs text-white"
                             style="background: linear-gradient(135deg, #6366f1, #8b5cf6);">
                            {{ strtoupper(substr($op->name, 0, 1)) }}
                        </div>
                        <span class="font-semibold text-slate-800">{{ $op->name }}</span>
                    </div>
                </td>
                <td class="text-slate-500 text-sm">{{ $op->email }}</td>
                <td>
                    @if($op->is_active)
                        <span class="badge-active">Aktif</span>
                    @else
                        <span class="badge-inactive">Nonaktif</span>
                    @endif
                </td>
                <td class="text-slate-400 text-sm">{{ $op->created_at->format('d M Y') }}</td>
                <td class="text-right">
                    <div class="flex items-center justify-end gap-2">
                        <a href="{{ route('operators.edit', $op) }}" class="btn-sm-secondary">Edit</a>
                        <form method="POST" action="{{ route('operators.toggle', $op) }}">
                            @csrf @method('PATCH')
                            <button type="submit" class="{{ $op->is_active ? 'btn-sm-danger' : 'btn-sm-success' }}">
                                {{ $op->is_active ? 'Nonaktifkan' : 'Aktifkan' }}
                            </button>
                        </form>
                        <form method="POST" action="{{ route('operators.destroy', $op) }}"
                              onsubmit="return confirm('Hapus operator {{ addslashes($op->name) }}?')">
                            @csrf @method('DELETE')
                            <button type="submit" class="btn-sm-danger">Hapus</button>
                        </form>
                    </div>
                </td>
            </tr>
            @empty
            <tr>
                <td colspan="5">
                    <div class="py-14 flex flex-col items-center text-center">
                        <svg class="text-slate-300 mb-3" width="36" height="36" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>
                        <p class="text-slate-500 font-medium text-sm">Belum ada operator</p>
                        <p class="text-slate-400 text-xs mt-1 mb-4">Tambah operator untuk memberikan akses ke panel ini.</p>
                        <a href="{{ route('operators.create') }}" class="btn-primary">Tambah Operator</a>
                    </div>
                </td>
            </tr>
            @endforelse
            </tbody>
        </table>
    </div>

    @if($operators->hasPages())
    <div class="p-4 border-t border-slate-100">
        {{ $operators->links() }}
    </div>
    @endif
</div>
@endsection
