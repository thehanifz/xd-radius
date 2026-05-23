@extends('layouts.app')
@section('title', 'Manajemen Operator')

@section('topbar-actions')
<a href="{{ route('operators.create') }}" class="btn-primary flex items-center gap-2 text-sm">
    <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
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
                    <th class="text-right">Aksi</th>
                </tr>
            </thead>
            <tbody>
            @forelse($operators as $op)
            <tr>
                <td class="font-semibold text-slate-800">{{ $op->name }}</td>
                <td class="text-slate-500 text-sm">{{ $op->email }}</td>
                <td>
                    @if($op->is_active)
                        <span class="badge-active">Aktif</span>
                    @else
                        <span class="badge-isolated">Nonaktif</span>
                    @endif
                </td>
                <td class="text-slate-400 text-sm">{{ $op->created_at->format('d M Y') }}</td>
                <td class="text-right">
                    <div class="flex items-center justify-end gap-2">
                        <a href="{{ route('operators.edit', $op) }}" class="btn-sm-secondary">Edit</a>
                        <form method="POST" action="{{ route('operators.toggle', $op) }}">
                            @csrf @method('PATCH')
                            <button class="btn-sm-{{ $op->is_active ? 'danger' : 'success' }}">
                                {{ $op->is_active ? 'Nonaktifkan' : 'Aktifkan' }}
                            </button>
                        </form>
                        <form method="POST" action="{{ route('operators.destroy', $op) }}"
                              onsubmit="return confirm('Hapus operator {{ $op->name }}?')">
                            @csrf @method('DELETE')
                            <button class="btn-sm-danger">Hapus</button>
                        </form>
                    </div>
                </td>
            </tr>
            @empty
            <tr><td colspan="5" class="text-center text-slate-400 py-10">Belum ada operator</td></tr>
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
