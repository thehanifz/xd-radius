@extends('layouts.app')
@section('title', 'Detail Member — ' . $member->username)

@section('topbar-actions')
    <div class="flex gap-2">
        <a href="{{ route('billing.create', ['member_id' => $member->id]) }}" class="btn-secondary">+ Invoice</a>
        <a href="{{ route('members.edit', $member) }}" class="btn-secondary">Edit</a>

        {{-- Toggle Status dengan modal konfirmasi --}}
        @if($member->status === 'active')
            <button type="button" onclick="openIsolirModal()" class="btn-danger">Isolir</button>
        @else
            <form method="POST" action="{{ route('members.toggle-status', $member) }}">
                @csrf @method('PATCH')
                <button type="submit" class="px-3 py-1.5 bg-green-600 hover:bg-green-700 text-white text-sm font-medium rounded-lg transition-colors">Aktifkan</button>
            </form>
        @endif

        <a href="{{ route('members.index') }}" class="btn-ghost">Kembali</a>
    </div>
@endsection

@section('content')
<div class="max-w-4xl space-y-5">

    {{-- Info Utama --}}
    <div class="card">
        <div class="card-header">Informasi Member</div>
        <div class="card-body">
            <dl class="grid grid-cols-2 md:grid-cols-3 gap-x-6 gap-y-4 text-sm">
                <div>
                    <dt class="text-slate-400 text-xs uppercase tracking-wide">Username</dt>
                    <dd class="font-mono font-semibold text-slate-800 mt-1">{{ $member->username }}</dd>
                </div>
                <div>
                    <dt class="text-slate-400 text-xs uppercase tracking-wide">Paket</dt>
                    <dd class="font-medium mt-1">{{ $member->plan?->name ?? '-' }}</dd>
                </div>
                <div>
                    <dt class="text-slate-400 text-xs uppercase tracking-wide">Status</dt>
                    <dd class="mt-1">
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
                    </dd>
                </div>
                <div>
                    <dt class="text-slate-400 text-xs uppercase tracking-wide">Harga</dt>
                    <dd class="font-medium mt-1">{{ $member->price_snapshot_label }}</dd>
                </div>
                <div>
                    <dt class="text-slate-400 text-xs uppercase tracking-wide">Simultaneous Use</dt>
                    <dd class="mt-1">{{ $member->simultaneous_use }}x</dd>
                </div>
                <div>
                    <dt class="text-slate-400 text-xs uppercase tracking-wide">Aktif Sejak</dt>
                    <dd class="mt-1">{{ $member->activated_at?->format('d M Y') ?? '-' }}</dd>
                </div>
                <div>
                    <dt class="text-slate-400 text-xs uppercase tracking-wide">Pertama Login</dt>
                    <dd class="mt-1">{{ $member->first_login_at?->format('d M Y H:i') ?? '-' }}</dd>
                </div>
                <div>
                    <dt class="text-slate-400 text-xs uppercase tracking-wide">Expired</dt>
                    <dd class="mt-1 {{ $member->expired_at?->isPast() ? 'text-red-600 font-medium' : '' }}">
                        {{ $member->expired_at?->format('d M Y H:i') ?? '-' }}
                    </dd>
                </div>
                <div>
                    <dt class="text-slate-400 text-xs uppercase tracking-wide">Dibuat</dt>
                    <dd class="mt-1">{{ $member->created_at?->format('d M Y') ?? '-' }}</dd>
                </div>
            </dl>
            @if($member->notes)
            <div class="mt-4 pt-4 border-t border-slate-100">
                <p class="text-xs text-slate-400 uppercase tracking-wide">Catatan</p>
                <p class="mt-1 text-sm text-slate-600">{{ $member->notes }}</p>
            </div>
            @endif
        </div>
    </div>

    {{-- Riwayat Invoice --}}
    <div class="card">
        <div class="card-header flex items-center justify-between">
            <span>Riwayat Invoice</span>
            <a href="{{ route('billing.create', ['member_id' => $member->id]) }}"
               class="text-xs font-medium text-blue-600 hover:text-blue-800 transition-colors">+ Buat Invoice</a>
        </div>
        @if($invoices->isEmpty())
            <div class="py-10 text-center text-slate-400 text-sm">Belum ada invoice untuk member ini.</div>
        @else
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead class="bg-slate-50 border-b border-slate-200">
                        <tr>
                            <th class="text-left px-4 py-3 font-medium text-slate-600">PERIODE</th>
                            <th class="text-left px-4 py-3 font-medium text-slate-600">NOMINAL</th>
                            <th class="text-left px-4 py-3 font-medium text-slate-600">JATUH TEMPO</th>
                            <th class="text-left px-4 py-3 font-medium text-slate-600">STATUS</th>
                            <th class="text-right px-4 py-3 font-medium text-slate-600">AKSI</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @foreach ($invoices as $invoice)
                        @php
                            $sc = match($invoice->status) {
                                'paid'      => 'bg-green-100 text-green-700',
                                'overdue'   => 'bg-red-100 text-red-700',
                                'cancelled' => 'bg-gray-100 text-gray-500',
                                default     => 'bg-yellow-100 text-yellow-700',
                            };
                            $sl = match($invoice->status) {
                                'paid' => 'Lunas', 'overdue' => 'Overdue',
                                'cancelled' => 'Batal', default => 'Pending',
                            };
                        @endphp
                        <tr class="hover:bg-slate-50">
                            <td class="px-4 py-2.5 text-xs text-slate-600">
                                {{ \Carbon\Carbon::parse($invoice->period_start)->format('d M Y') }}
                                &ndash;
                                {{ \Carbon\Carbon::parse($invoice->period_end)->format('d M Y') }}
                            </td>
                            <td class="px-4 py-2.5 font-medium tabular-nums">
                                Rp {{ number_format($invoice->amount, 0, ',', '.') }}
                            </td>
                            <td class="px-4 py-2.5 text-xs {{ \Carbon\Carbon::parse($invoice->due_date)->isPast() && $invoice->status !== 'paid' ? 'text-red-600 font-medium' : 'text-slate-500' }}">
                                {{ \Carbon\Carbon::parse($invoice->due_date)->format('d M Y') }}
                            </td>
                            <td class="px-4 py-2.5">
                                <span class="px-2 py-0.5 rounded-full text-xs font-medium {{ $sc }}">{{ $sl }}</span>
                            </td>
                            <td class="px-4 py-2.5 text-right">
                                <div class="flex justify-end gap-3">
                                    <a href="{{ route('billing.show', $invoice) }}" class="text-xs text-blue-600 hover:text-blue-800">Detail</a>
                                    @if(in_array($invoice->status, ['pending', 'overdue']))
                                    <a href="{{ route('billing.pay.form', $invoice) }}" class="text-xs text-green-600 hover:text-green-800">Bayar</a>
                                    @endif
                                    <a href="{{ route('billing.pdf', $invoice) }}" class="text-xs text-slate-500 hover:text-slate-700" target="_blank">PDF</a>
                                </div>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>

    {{-- Sesi Terakhir --}}
    <div class="card">
        <div class="card-header">Riwayat Sesi (20 Terakhir)</div>
        @if($sessions->isEmpty())
            <div class="py-10 text-center text-slate-400 text-sm">Belum ada sesi tercatat di radacct.</div>
        @else
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead class="bg-slate-50 border-b border-slate-200">
                    <tr>
                        <th class="text-left px-4 py-3 font-medium text-slate-600">LOGIN</th>
                        <th class="text-left px-4 py-3 font-medium text-slate-600">LOGOUT</th>
                        <th class="text-left px-4 py-3 font-medium text-slate-600">DURASI</th>
                        <th class="text-left px-4 py-3 font-medium text-slate-600">NAS / IP</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @foreach ($sessions as $s)
                    <tr class="hover:bg-slate-50">
                        <td class="px-4 py-2 text-xs">{{ $s->acctstarttime }}</td>
                        <td class="px-4 py-2 text-xs text-slate-500">
                            {{ $s->acctstoptime ?? '—' }}
                            @if(!$s->acctstoptime)
                                <span class="ml-1 px-1.5 py-0.5 bg-green-100 text-green-700 text-xs rounded-full">Online</span>
                            @endif
                        </td>
                        <td class="px-4 py-2 text-xs text-slate-500">
                            @php $sec = $s->acctsessiontime ?? 0; @endphp
                            {{ $sec >= 3600 ? floor($sec/3600).'j '.floor(($sec%3600)/60).'m' : floor($sec/60).'m '.($sec%60).'d' }}
                        </td>
                        <td class="px-4 py-2 text-xs text-slate-500">{{ $s->nasipaddress ?? '-' }} / {{ $s->framedipaddress ?? '-' }}</td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        @endif
    </div>

    {{-- Danger Zone --}}
    <div class="card border border-red-100">
        <div class="card-body">
            <div class="flex items-center justify-between">
                <div>
                    <p class="font-medium text-slate-700">Hapus Member</p>
                    <p class="text-sm text-slate-400 mt-0.5">Menghapus member dan semua data RADIUS terkait.</p>
                </div>
                <form method="POST" action="{{ route('members.destroy', $member) }}"
                    onsubmit="return confirm('Yakin hapus member {{ $member->username }}?')">
                    @csrf @method('DELETE')
                    <button type="submit" class="btn-danger">Hapus Member</button>
                </form>
            </div>
        </div>
    </div>

</div>

{{-- Modal Konfirmasi Isolir --}}
@if($member->status === 'active')
<div id="modal-isolir" class="fixed inset-0 z-50 hidden" aria-modal="true" role="dialog">
    {{-- Backdrop --}}
    <div class="absolute inset-0 bg-black/50 backdrop-blur-sm" onclick="closeIsolirModal()"></div>

    {{-- Panel --}}
    <div class="absolute inset-0 flex items-center justify-center p-4">
        <div class="relative bg-white rounded-2xl shadow-2xl w-full max-w-md p-6 animate-slide-up">

            {{-- Icon warning --}}
            <div class="flex items-center justify-center w-12 h-12 rounded-full bg-red-50 mx-auto mb-4">
                <svg class="w-6 h-6 text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v4m0 4h.01M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z"/>
                </svg>
            </div>

            <h3 class="text-base font-bold text-slate-800 text-center">Isolir Member?</h3>
            <p class="mt-2 text-sm text-slate-500 text-center">
                Member <span class="font-semibold text-slate-700 font-mono">{{ $member->username }}</span> akan diblokir
                dari jaringan. Koneksi aktif akan terputus.
            </p>

            <div class="mt-4 flex items-start gap-2.5">
                <input type="checkbox" id="no-confirm-isolir" class="mt-0.5 accent-red-500 cursor-pointer">
                <label for="no-confirm-isolir" class="text-xs text-slate-500 cursor-pointer leading-relaxed">
                    Jangan tanya lagi untuk member ini di sesi ini
                </label>
            </div>

            <div class="mt-5 flex gap-3">
                <button type="button" onclick="closeIsolirModal()"
                    class="flex-1 px-4 py-2 text-sm font-medium text-slate-600 bg-slate-100 hover:bg-slate-200 rounded-lg transition-colors">
                    Batal
                </button>
                <form method="POST" action="{{ route('members.toggle-status', $member) }}" class="flex-1">
                    @csrf @method('PATCH')
                    <button type="submit"
                        class="w-full px-4 py-2 text-sm font-medium text-white bg-red-500 hover:bg-red-600 rounded-lg transition-colors">
                        Ya, Isolir Sekarang
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
const SKIP_KEY = 'skip_isolir_confirm_{{ $member->id }}';

function openIsolirModal() {
    if (sessionStorage.getItem(SKIP_KEY) === '1') {
        document.querySelector('#modal-isolir form').submit();
        return;
    }
    document.getElementById('modal-isolir').classList.remove('hidden');
    document.body.style.overflow = 'hidden';
}

function closeIsolirModal() {
    const skip = document.getElementById('no-confirm-isolir')?.checked;
    if (skip) sessionStorage.setItem(SKIP_KEY, '1');
    document.getElementById('modal-isolir').classList.add('hidden');
    document.body.style.overflow = '';
}

document.addEventListener('keydown', e => {
    if (e.key === 'Escape') closeIsolirModal();
});
</script>
@endpush
@endif
@endsection
