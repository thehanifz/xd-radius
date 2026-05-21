@extends('layouts.app')
@section('title', 'Daftar Voucher')

@section('topbar-actions')
    <a href="{{ route('vouchers.create') }}" class="btn-primary">
        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
        Generate Voucher
    </a>
@endsection

@section('content')
<div class="space-y-5">

    {{-- Flash --}}
    @if(session('success'))
    <div class="flex items-center gap-3 px-4 py-3 bg-green-50 border border-green-200 text-green-700 rounded-lg text-sm">
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="flex-shrink-0"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg>
        <span>{{ session('success') }}</span>
    </div>
    @endif

    {{-- Filter --}}
    <div class="card">
        <div class="card-body py-3">
            <form method="GET" class="flex flex-wrap gap-3 items-center">
                <input type="text" name="search" value="{{ request('search') }}"
                    placeholder="Cari username..."
                    class="form-input w-52">

                <select name="batch_id" class="form-input w-64">
                    <option value="">Semua Batch</option>
                    @foreach($batches as $batch)
                    <option value="{{ $batch->id }}" {{ request('batch_id') == $batch->id ? 'selected' : '' }}>
                        {{ $batch->batch_code }} &mdash; {{ $batch->plan->name ?? '-' }} ({{ $batch->quantity }} pcs)
                    </option>
                    @endforeach
                </select>

                <select name="status" class="form-input w-40">
                    <option value="">Semua Status</option>
                    <option value="active"   {{ request('status') === 'active'   ? 'selected' : '' }}>Aktif</option>
                    <option value="used"     {{ request('status') === 'used'     ? 'selected' : '' }}>Digunakan</option>
                    <option value="expired"  {{ request('status') === 'expired'  ? 'selected' : '' }}>Expired</option>
                    <option value="isolated" {{ request('status') === 'isolated' ? 'selected' : '' }}>Isolir</option>
                </select>

                <button type="submit" class="btn-secondary">Filter</button>
                @if(request()->hasAny(['search','batch_id','status']))
                    <a href="{{ route('vouchers.index') }}" class="text-sm text-slate-500 hover:text-slate-700">Reset</a>
                @endif
                
                @if(request('batch_id'))
                    <div class="border-l border-slate-300 mx-2 h-6"></div>
                    <a href="{{ route('vouchers.print', request('batch_id')) }}" target="_blank" class="btn-primary flex items-center gap-1 bg-indigo-600 hover:bg-indigo-700 text-white px-3 py-1.5 rounded">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="6 9 6 2 18 2 18 9"></polyline><path d="M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2"></path><rect x="6" y="14" width="12" height="8"></rect></svg>
                        Print Batch
                    </a>
                @endif
            </form>
        </div>
    </div>

    {{-- Tabel --}}
    <div class="card overflow-hidden">
        @if($vouchers->isEmpty())
        <div class="py-16 flex flex-col items-center text-center">
            <svg class="text-slate-300 mb-3" width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><rect x="2" y="5" width="20" height="14" rx="2"/><line x1="2" y1="10" x2="22" y2="10"/></svg>
            <p class="text-slate-500 font-medium">Belum ada voucher</p>
            <p class="text-slate-400 text-sm mt-1 mb-4">Generate voucher pertama untuk mulai distribusi ke pelanggan.</p>
            <a href="{{ route('vouchers.create') }}" class="btn-primary">Generate Sekarang</a>
        </div>
        @else
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead class="bg-slate-50 border-b border-slate-200">
                    <tr>
                        <th class="text-left px-5 py-3 text-xs font-semibold text-slate-500 uppercase tracking-wide">Username / Password</th>
                        <th class="text-left px-5 py-3 text-xs font-semibold text-slate-500 uppercase tracking-wide">Batch</th>
                        <th class="text-left px-5 py-3 text-xs font-semibold text-slate-500 uppercase tracking-wide">Paket</th>
                        <th class="text-left px-5 py-3 text-xs font-semibold text-slate-500 uppercase tracking-wide">Harga</th>
                        <th class="text-left px-5 py-3 text-xs font-semibold text-slate-500 uppercase tracking-wide">First Login</th>
                        <th class="text-left px-5 py-3 text-xs font-semibold text-slate-500 uppercase tracking-wide">Expired</th>
                        <th class="text-left px-5 py-3 text-xs font-semibold text-slate-500 uppercase tracking-wide">Status</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @foreach($vouchers as $voucher)
                    <tr class="hover:bg-slate-50 transition-colors">
                        <td class="px-5 py-3.5">
                            <span class="font-mono font-medium text-slate-800">{{ $voucher->username }}</span>
                            @if($voucher->is_printed)
                            <span class="ml-2 text-xs px-1.5 py-0.5 bg-slate-100 text-slate-500 rounded">Dicetak</span>
                            @endif
                        </td>
                        <td class="px-5 py-3.5 text-slate-500 text-xs font-mono">{{ $voucher->batch->batch_code ?? '-' }}</td>
                        <td class="px-5 py-3.5 text-slate-600">{{ $voucher->plan->name ?? '-' }}</td>
                        <td class="px-5 py-3.5 text-slate-700 tabular-nums">{{ $voucher->price_label }}</td>
                        <td class="px-5 py-3.5 text-slate-500">
                            {{ $voucher->first_login_at ? $voucher->first_login_at->format('d M Y H:i') : '-' }}
                        </td>
                        <td class="px-5 py-3.5 text-slate-500">
                            {{ $voucher->expired_at ? $voucher->expired_at->format('d M Y') : '-' }}
                        </td>
                        <td class="px-5 py-3.5">
                            @php
                                $colors = ['active'=>'green','used'=>'blue','expired'=>'slate','isolated'=>'red','inactive'=>'yellow'];
                                $color = $colors[$voucher->status] ?? 'slate';
                            @endphp
                            <span class="badge badge-{{ $color }}">{{ $voucher->status_label }}</span>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        @if($vouchers->hasPages())
        <div class="px-5 py-3 border-t border-slate-100">
            {{ $vouchers->links() }}
        </div>
        @endif
        @endif
    </div>

</div>
@endsection
