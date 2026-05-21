@extends('layouts.app')
@section('title', 'Daftar Voucher')

@section('topbar-actions')
    <a href="{{ route('vouchers.create') }}" class="btn-primary flex items-center gap-1.5">
        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
        Generate Voucher
    </a>
@endsection

@section('content')
<div class="space-y-5">

    {{-- Filter --}}
    <div class="card">
        <div class="card-body py-3">
            <form method="GET" class="flex flex-wrap gap-3 items-center">
                <input type="text" name="search" value="{{ request('search') }}"
                    placeholder="Cari username..."
                    class="form-input w-48">

                <select name="batch_id" id="batch-select" class="form-input w-72"
                    onchange="updateBatchButtons(this.value, this.options[this.selectedIndex].text)">
                    <option value="">Semua Batch</option>
                    @foreach($batches as $b)
                    <option value="{{ $b->id }}" {{ request('batch_id') == $b->id ? 'selected' : '' }}>
                        {{ $b->batch_code }} ({{ $b->quantity }} pcs)
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
            </form>
        </div>
    </div>

    {{-- Batch Print Actions (muncul jika ada batch dipilih) --}}
    <div id="batch-actions" class="{{ request('batch_id') ? '' : 'hidden' }} bg-indigo-50 border border-indigo-200 rounded-xl px-5 py-3.5 flex flex-wrap items-center justify-between gap-3">
        <div class="flex items-center gap-3">
            <div class="w-9 h-9 bg-indigo-100 rounded-lg flex items-center justify-center">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#6366f1" stroke-width="2" stroke-linecap="round">
                    <polyline points="6 9 6 2 18 2 18 9"/>
                    <path d="M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2"/>
                    <rect x="6" y="14" width="12" height="8"/>
                </svg>
            </div>
            <div>
                <p class="text-sm font-semibold text-indigo-900" id="batch-action-label">
                    @if(request('batch_id'))
                        {{ $batches->firstWhere('id', request('batch_id'))?->batch_code ?? 'Batch dipilih' }}
                    @else
                        Pilih batch untuk print
                    @endif
                </p>
                <p class="text-xs text-indigo-600">Cetak voucher batch ini</p>
            </div>
        </div>
        <div class="flex gap-2">
            <a id="btn-print-a4"
               href="{{ request('batch_id') ? route('vouchers.print', request('batch_id')).'?type=a4' : '#' }}"
               target="_blank"
               class="flex items-center gap-1.5 px-3 py-2 text-sm font-medium bg-white border border-indigo-300 text-indigo-700 rounded-lg hover:bg-indigo-50 transition-colors">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="6 9 6 2 18 2 18 9"/><path d="M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2"/><rect x="6" y="14" width="12" height="8"/></svg>
                Print A4
            </a>
            <a id="btn-print-thermal"
               href="{{ request('batch_id') ? route('vouchers.print', request('batch_id')).'?type=thermal' : '#' }}"
               target="_blank"
               class="flex items-center gap-1.5 px-3 py-2 text-sm font-medium bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 transition-colors">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="2" y="7" width="20" height="14" rx="2"/><path d="M16 7V5a2 2 0 0 0-2-2h-4a2 2 0 0 0-2 2v2"/><line x1="12" y1="12" x2="12" y2="16"/><line x1="10" y1="14" x2="14" y2="14"/></svg>
                Print Thermal
            </a>
        </div>
    </div>

    {{-- Semua Batch (quick print list) --}}
    @if($batches->isNotEmpty() && !request('batch_id'))
    <div class="card">
        <div class="card-header flex items-center justify-between">
            <span>Semua Batch</span>
            <span class="text-xs text-slate-400">{{ $batches->count() }} batch</span>
        </div>
        <div class="divide-y divide-slate-100">
            @foreach($batches->take(8) as $b)
            <div class="flex items-center justify-between px-5 py-3 hover:bg-slate-50">
                <div class="flex items-center gap-3">
                    <div class="w-8 h-8 rounded-lg bg-slate-100 flex items-center justify-center">
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="#64748b" stroke-width="2"><rect x="2" y="5" width="20" height="14" rx="2"/><line x1="2" y1="10" x2="22" y2="10"/></svg>
                    </div>
                    <div>
                        <p class="text-sm font-mono font-semibold text-slate-700">{{ $b->batch_code }}</p>
                        <p class="text-xs text-slate-400">{{ $b->plan->name ?? '-' }} &middot; {{ $b->quantity }} pcs &middot; {{ $b->created_at->format('d M Y') }}</p>
                    </div>
                </div>
                <div class="flex items-center gap-2">
                    <a href="{{ route('vouchers.index', ['batch_id' => $b->id]) }}"
                       class="text-xs text-slate-500 hover:text-slate-700 px-2 py-1 rounded-lg hover:bg-slate-100 transition-colors">Lihat</a>
                    <a href="{{ route('vouchers.print', $b->id) }}?type=a4" target="_blank"
                       class="text-xs text-indigo-600 hover:text-indigo-800 px-2 py-1 rounded-lg hover:bg-indigo-50 transition-colors flex items-center gap-1">
                        <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="6 9 6 2 18 2 18 9"/><path d="M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2"/><rect x="6" y="14" width="12" height="8"/></svg>
                        A4
                    </a>
                    <a href="{{ route('vouchers.print', $b->id) }}?type=thermal" target="_blank"
                       class="text-xs text-white bg-indigo-500 hover:bg-indigo-600 px-2 py-1 rounded-lg transition-colors flex items-center gap-1">
                        <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="2" y="7" width="20" height="14" rx="2"/><path d="M16 7V5a2 2 0 0 0-2-2h-4a2 2 0 0 0-2 2v2"/></svg>
                        Thermal
                    </a>
                </div>
            </div>
            @endforeach
        </div>
    </div>
    @endif

    {{-- Tabel Voucher --}}
    <div class="card overflow-hidden">
        <div class="card-header flex items-center justify-between">
            <span>
                @if(request('batch_id'))
                    Voucher &mdash; {{ $batches->firstWhere('id', request('batch_id'))?->batch_code }}
                @else
                    Semua Voucher
                @endif
            </span>
            <span class="text-xs text-slate-400">{{ $vouchers->total() }} total</span>
        </div>

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
                        <th class="text-right px-5 py-3 text-xs font-semibold text-slate-500 uppercase tracking-wide">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @foreach($vouchers as $voucher)
                    <tr class="hover:bg-slate-50 transition-colors">
                        <td class="px-5 py-3.5">
                            <div class="flex items-center gap-2">
                                <span class="font-mono font-semibold text-slate-800">{{ $voucher->username }}</span>
                                @if($voucher->is_printed)
                                <span class="text-[10px] px-1.5 py-0.5 bg-slate-100 text-slate-400 rounded">Dicetak</span>
                                @endif
                            </div>
                            <div class="text-xs text-slate-400 font-mono mt-0.5">{{ $voucher->password_plain ?? '••••••' }}</div>
                        </td>
                        <td class="px-5 py-3.5 text-slate-500 text-xs font-mono">{{ $voucher->batch->batch_code ?? '-' }}</td>
                        <td class="px-5 py-3.5 text-slate-600">{{ $voucher->plan->name ?? '-' }}</td>
                        <td class="px-5 py-3.5 text-slate-700 tabular-nums">{{ $voucher->price_label }}</td>
                        <td class="px-5 py-3.5 text-slate-500 text-xs">
                            {{ $voucher->first_login_at?->format('d M Y H:i') ?? '-' }}
                        </td>
                        <td class="px-5 py-3.5 text-slate-500 text-xs">
                            {{ $voucher->expired_at?->format('d M Y') ?? '-' }}
                        </td>
                        <td class="px-5 py-3.5">
                            @php
                                $colors = ['active'=>'green','used'=>'blue','expired'=>'slate','isolated'=>'red','inactive'=>'yellow'];
                                $color  = $colors[$voucher->status] ?? 'slate';
                            @endphp
                            <span class="badge badge-{{ $color }}">{{ $voucher->status_label }}</span>
                        </td>
                        <td class="px-5 py-3.5 text-right">
                            <div class="flex justify-end items-center gap-2">
                                <a href="{{ route('vouchers.show', $voucher) }}"
                                   class="text-xs text-slate-500 hover:text-slate-800 px-2 py-1 rounded hover:bg-slate-100 transition-colors">Detail</a>
                                @if($voucher->batch_id)
                                <a href="{{ route('vouchers.print', $voucher->batch_id) }}?type=thermal"
                                   target="_blank"
                                   class="text-xs text-indigo-600 hover:text-indigo-800 px-2 py-1 rounded hover:bg-indigo-50 transition-colors flex items-center gap-1">
                                    <svg width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="6 9 6 2 18 2 18 9"/><path d="M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2"/><rect x="6" y="14" width="12" height="8"/></svg>
                                    Print
                                </a>
                                @endif
                            </div>
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

@push('scripts')
<script>
const BASE_PRINT_URL = '{{ url("/vouchers/batch") }}';

function updateBatchButtons(batchId, batchText) {
    const actions = document.getElementById('batch-actions');
    const label   = document.getElementById('batch-action-label');
    const btnA4   = document.getElementById('btn-print-a4');
    const btnThm  = document.getElementById('btn-print-thermal');

    if (batchId) {
        actions.classList.remove('hidden');
        // Ambil batch_code dari teks option (sebelum " (")
        label.textContent = batchText.split(' (')[0].trim();
        btnA4.href  = BASE_PRINT_URL + '/' + batchId + '?type=a4';
        btnThm.href = BASE_PRINT_URL + '/' + batchId + '?type=thermal';
    } else {
        actions.classList.add('hidden');
    }
}

// Init: jika sudah ada batch_id dari query string
document.addEventListener('DOMContentLoaded', function() {
    const sel = document.getElementById('batch-select');
    if (sel && sel.value) {
        updateBatchButtons(sel.value, sel.options[sel.selectedIndex].text);
    }
});
</script>
@endpush
@endsection
