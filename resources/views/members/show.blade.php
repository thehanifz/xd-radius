@extends('layouts.app')
@section('title', 'Detail Member — ' . $member->username)

@section('topbar-actions')
    <div class="flex gap-2">
        <a href="{{ route('billing.create', ['member_id' => $member->id]) }}" class="btn-secondary">
            + Invoice
        </a>
        <a href="{{ route('members.edit', $member) }}" class="btn-secondary">Edit</a>

        {{-- Toggle Status --}}
        @if($member->status === 'active')
            <button type="button"
                    data-modal-open="modal-isolir-{{ $member->id }}"
                    class="btn-danger">
                Isolir
            </button>
        @elseif($member->status === 'isolated')
            <button type="button"
                    data-modal-open="modal-aktif-{{ $member->id }}"
                    class="px-4 py-2.5 bg-gradient-to-r from-emerald-500 to-green-600 text-white text-sm font-semibold rounded-xl shadow-md hover:from-emerald-400 hover:to-green-500 transition-all">
                Aktifkan
            </button>
        @endif

        <a href="{{ route('members.index') }}" class="btn-ghost">Kembali</a>
    </div>
@endsection

@section('content')
<div class="max-w-4xl space-y-5">

    {{-- Info Utama --}}
    <div class="card">
        <div class="card-header">
            <span class="card-title">Informasi Member</span>
            {{-- Status badge --}}
            @php
                $statusColor = match($member->status) {
                    'active'   => 'badge-green',
                    'isolated' => 'badge-red',
                    'expired'  => 'badge-slate',
                    default    => 'badge-yellow',
                };
            @endphp
            <span class="badge {{ $statusColor }}">{{ $member->status_label }}</span>
        </div>
        <div class="card-body">
            <dl class="grid grid-cols-2 md:grid-cols-3 gap-x-6 gap-y-4 text-sm">
                <div>
                    <dt class="text-slate-400 dark:text-slate-500 text-xs uppercase tracking-wide font-semibold">Username</dt>
                    <dd class="font-mono font-semibold text-slate-800 dark:text-slate-100 mt-1">{{ $member->username }}</dd>
                </div>
                <div>
                    <dt class="text-slate-400 dark:text-slate-500 text-xs uppercase tracking-wide font-semibold">Paket</dt>
                    <dd class="font-medium mt-1">{{ $member->plan?->name ?? '-' }}</dd>
                </div>
                <div>
                    <dt class="text-slate-400 dark:text-slate-500 text-xs uppercase tracking-wide font-semibold">Harga</dt>
                    <dd class="font-medium mt-1 tabular-nums">{{ $member->price_snapshot_label }}</dd>
                </div>
                <div>
                    <dt class="text-slate-400 dark:text-slate-500 text-xs uppercase tracking-wide font-semibold">Simultaneous Use</dt>
                    <dd class="mt-1">{{ $member->simultaneous_use }}x sesi</dd>
                </div>
                <div>
                    <dt class="text-slate-400 dark:text-slate-500 text-xs uppercase tracking-wide font-semibold">Aktif Sejak</dt>
                    <dd class="mt-1">{{ $member->activated_at?->format('d M Y') ?? '-' }}</dd>
                </div>
                <div>
                    <dt class="text-slate-400 dark:text-slate-500 text-xs uppercase tracking-wide font-semibold">Pertama Login</dt>
                    <dd class="mt-1">{{ $member->first_login_at?->format('d M Y H:i') ?? '-' }}</dd>
                </div>
                <div>
                    <dt class="text-slate-400 dark:text-slate-500 text-xs uppercase tracking-wide font-semibold">Expired</dt>
                    <dd class="mt-1 {{ $member->expired_at?->isPast() ? 'text-red-600 font-semibold' : '' }}">
                        {{ $member->expired_at?->format('d M Y H:i') ?? '-' }}
                    </dd>
                </div>
                <div>
                    <dt class="text-slate-400 dark:text-slate-500 text-xs uppercase tracking-wide font-semibold">Dibuat</dt>
                    <dd class="mt-1">{{ $member->created_at?->format('d M Y') ?? '-' }}</dd>
                </div>
            </dl>

            @if($member->notes)
            <div class="mt-4 pt-4 border-t border-slate-100 dark:border-slate-700">
                <p class="text-xs text-slate-400 dark:text-slate-500 uppercase tracking-wide font-semibold mb-1">Catatan</p>
                <p class="text-sm text-slate-600 dark:text-slate-300">{{ $member->notes }}</p>
            </div>
            @endif
        </div>
    </div>

    {{-- Payment Accounts --}}
    <div class="card">
        <div class="card-header">
            <span class="card-title">Payment Account</span>
        </div>
        <div class="card-body">
            @if(!config('doku.enabled'))
                <div class="rounded-xl border border-slate-200 bg-slate-50/70 p-4 text-xs text-slate-500 dark:border-slate-700 dark:bg-slate-800/40 dark:text-slate-400">
                    Integrasi pembayaran DOKU belum diaktifkan. Aktifkan di
                    <a href="{{ route('settings.doku.index') }}" class="font-semibold text-indigo-600 hover:underline dark:text-indigo-400">Pengaturan → Pembayaran DOKU</a>
                    agar Virtual Account dibuat otomatis saat member didaftarkan atau dibuka.
                </div>
            @elseif($dokuVaChannels->isEmpty())
                <div class="rounded-xl border border-amber-200 bg-amber-50 p-3.5 text-xs text-amber-800 dark:border-amber-800/60 dark:bg-amber-950/30 dark:text-amber-300">
                    Belum ada channel bank VA yang aktif. Aktifkan channel bank di
                    <a href="{{ route('settings.doku.index') }}" class="font-semibold underline">Pengaturan → Pembayaran DOKU</a>.
                </div>
            @endif

            @if(!empty($dokuVaSyncErrors))
                <div class="mt-3 text-xs text-amber-800 dark:text-amber-200 bg-amber-50 dark:bg-amber-950/40 border border-amber-200 dark:border-amber-800 rounded-xl p-3.5">
                    <p class="font-semibold">Sebagian Virtual Account belum berhasil dibuat otomatis:</p>
                    <ul class="list-disc ml-5 mt-1.5 space-y-0.5">
                        @foreach($dokuVaSyncErrors as $syncError)
                            <li>{{ $syncError['channel'] }}: {{ $syncError['error'] }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            @if($member->paymentAccounts->isEmpty() && config('doku.enabled') && $dokuVaChannels->isNotEmpty())
                <p class="text-xs text-slate-500 dark:text-slate-400 mt-2">Belum ada VA yang berhasil dibuat. Periksa konfigurasi kredensial / BIN channel DOKU lalu refresh halaman ini.</p>
            @endif

            @if($member->paymentAccounts->isNotEmpty())
                <div class="space-y-3">
                    @foreach($member->paymentAccounts as $account)
                        <div class="rounded-xl border border-slate-200 dark:border-slate-700 p-4 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
                            <div>
                                <div class="flex items-center gap-2">
                                    <span class="font-semibold text-slate-800 dark:text-slate-100">{{ $account->bank }}</span>
                                    @if($account->is_default)<span class="badge badge-green">Default</span>@endif
                                    <span class="badge badge-blue">{{ strtoupper($account->status) }}</span>
                                </div>
                                <p class="font-mono text-lg tracking-wider text-slate-900 dark:text-slate-100 mt-1">{{ $account->account_number }}</p>
                                <p class="text-xs text-slate-400 dark:text-slate-500 mt-1">Gateway: {{ strtoupper($account->gateway) }}</p>
                            </div>
                        </div>
                    @endforeach
                </div>
            @endif
        </div>
    </div>

    {{-- Riwayat Invoice --}}
    <div class="card">
        <div class="card-header">
            <span class="card-title">Riwayat Invoice</span>
            <a href="{{ route('billing.create', ['member_id' => $member->id]) }}"
               class="text-xs font-semibold text-indigo-600 dark:text-indigo-400 hover:text-indigo-800 dark:hover:text-indigo-300 transition-colors">
                + Buat Invoice
            </a>
        </div>
        @if($invoices->isEmpty())
            <div class="py-10 text-center text-slate-400 dark:text-slate-500 text-sm">
                Belum ada invoice untuk member ini.
            </div>
        @else
            <div class="overflow-x-auto">
                <div class="table-scroll">
<table class="data-table w-full text-sm">
                    <thead class="bg-slate-50 dark:bg-slate-800 border-b border-slate-200 dark:border-slate-700">
                        <tr>
                            <th class="text-left px-4 py-3 font-semibold text-slate-500 dark:text-slate-400 text-xs uppercase tracking-wide">Periode</th>
                            <th class="text-left px-4 py-3 font-semibold text-slate-500 dark:text-slate-400 text-xs uppercase tracking-wide">Nominal</th>
                            <th class="text-left px-4 py-3 font-semibold text-slate-500 dark:text-slate-400 text-xs uppercase tracking-wide">Jatuh Tempo Bayar</th>
                            <th class="text-left px-4 py-3 font-semibold text-slate-500 dark:text-slate-400 text-xs uppercase tracking-wide">Status</th>
                            <th class="text-right px-4 py-3 font-semibold text-slate-500 dark:text-slate-400 text-xs uppercase tracking-wide">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 dark:divide-slate-700">
                        @foreach ($invoices as $invoice)
                        <tr class="hover:bg-slate-50 dark:hover:bg-slate-800/70 transition-colors">
                            <td class="px-4 py-3 text-xs text-slate-600 dark:text-slate-300 tabular-nums">
                                {{ $invoice->period_start->format('d M Y') }}
                                <span class="text-slate-400 dark:text-slate-500">–</span>
                                {{ $invoice->period_end->format('d M Y') }}
                            </td>
                            <td class="px-4 py-3 font-semibold tabular-nums">{{ $invoice->amount_label }}</td>
                            <td class="px-4 py-3 text-xs tabular-nums {{ $invoice->due_date->isPast() && $invoice->status !== 'paid' ? 'text-red-600 font-semibold' : 'text-slate-500 dark:text-slate-400' }}">
                                {{ $invoice->due_date->format('d M Y') }}
                            </td>
                            <td class="px-4 py-3">
                                <span class="badge {{ $invoice->status_badge_class }}">{{ $invoice->status_label }}</span>
                            </td>
                            <td class="px-4 py-3 text-right">
                                <div class="flex justify-end gap-3">
                                    <a href="{{ route('billing.show', $invoice) }}"
                                       class="text-xs text-indigo-600 dark:text-indigo-400 hover:text-indigo-800 dark:hover:text-indigo-300">Detail</a>
                                    @if(in_array($invoice->status, ['pending', 'overdue']))
                                    <a href="{{ route('billing.pay.form', $invoice) }}"
                                       class="text-xs text-green-600 dark:text-green-400 hover:text-green-800 dark:hover:text-green-300">Bayar</a>
                                    @endif
                                    <a href="{{ route('billing.pdf', $invoice) }}"
                                       class="text-xs text-slate-500 dark:text-slate-400 hover:text-slate-700 dark:hover:text-slate-200" target="_blank">PDF</a>
                                </div>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
</div>
            </div>
        @endif
    </div>

    {{-- Riwayat Sesi (radacct) --}}
    <div class="card">
        <div class="card-header">
            <span class="card-title">Riwayat Sesi (20 Terakhir)</span>
        </div>
        @if($sessions->isEmpty())
            <div class="py-10 text-center text-slate-400 dark:text-slate-500 text-sm">
                Belum ada sesi tercatat di radacct.
            </div>
        @else
        <div class="overflow-x-auto">
            <div class="table-scroll">
<table class="data-table w-full text-sm">
                <thead class="bg-slate-50 dark:bg-slate-800 border-b border-slate-200 dark:border-slate-700">
                    <tr>
                        <th class="text-left px-4 py-3 font-semibold text-slate-500 dark:text-slate-400 text-xs uppercase tracking-wide">Login</th>
                        <th class="text-left px-4 py-3 font-semibold text-slate-500 dark:text-slate-400 text-xs uppercase tracking-wide">Logout</th>
                        <th class="text-left px-4 py-3 font-semibold text-slate-500 dark:text-slate-400 text-xs uppercase tracking-wide">Durasi</th>
                        <th class="text-left px-4 py-3 font-semibold text-slate-500 dark:text-slate-400 text-xs uppercase tracking-wide">NAS / IP</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 dark:divide-slate-700">
                    @foreach ($sessions as $s)
                    <tr class="hover:bg-slate-50 dark:hover:bg-slate-800/70 transition-colors">
                        <td class="px-4 py-2.5 text-xs tabular-nums">{{ $s->acctstarttime }}</td>
                        <td class="px-4 py-2.5 text-xs text-slate-500 dark:text-slate-400">
                            {{ $s->acctstoptime ?? '—' }}
                            @if(!$s->acctstoptime)
                                <span class="ml-1 badge badge-green text-[10px] py-0">Online</span>
                            @endif
                        </td>
                        <td class="px-4 py-2.5 text-xs text-slate-500 dark:text-slate-400">
                            @php $sec = $s->acctsessiontime ?? 0; @endphp
                            {{ $sec >= 3600 ? floor($sec/3600).'j '.floor(($sec%3600)/60).'m' : floor($sec/60).'m '.($sec%60).'d' }}
                        </td>
                        <td class="px-4 py-2.5 text-xs text-slate-500 dark:text-slate-400 font-mono">
                            {{ $s->nasipaddress ?? '-' }} / {{ $s->framedipaddress ?? '-' }}
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
</div>
        </div>
        @endif
    </div>

    {{-- Danger Zone --}}
    <div class="card border border-red-100">
        <div class="card-body">
            <div class="flex items-center justify-between">
                <div>
                    <p class="font-semibold text-slate-700 dark:text-slate-200">Hapus Member</p>
                    <p class="text-sm text-slate-400 dark:text-slate-500 mt-0.5">
                        Menghapus member dan semua data RADIUS terkait. Tidak bisa dibatalkan.
                    </p>
                </div>
                <form method="POST" action="{{ route('members.destroy', $member) }}"
                      onsubmit="return confirm('Yakin hapus member {{ $member->username }}? Aksi ini tidak bisa dibatalkan.')">
                    @csrf @method('DELETE')
                    <button type="submit" class="btn-danger">Hapus Member</button>
                </form>
            </div>
        </div>
    </div>

</div>

{{-- Modal Konfirmasi Isolir (pakai x-confirm-modal component dengan DB preference) --}}
@if($member->status === 'active')
<x-confirm-modal
    id="modal-isolir-{{ $member->id }}"
    title="Isolir Member?"
    message="Member {{ $member->username }} akan diblokir dari jaringan."
    confirm-label="Ya, Isolir"
    confirm-class="btn-danger"
    action-url="{{ route('members.toggle-status', $member) }}"
    method="PATCH"
    preference-key="confirm_isolir_member_{{ $member->id }}"
    info-text="ℹ️  Tahap 1: user yang sedang online mungkin tetap terkoneksi hingga sesi berakhir sendiri."
/>
@endif

{{-- Modal Konfirmasi Aktifkan --}}
@if($member->status === 'isolated')
<x-confirm-modal
    id="modal-aktif-{{ $member->id }}"
    title="Aktifkan Member?"
    message="Member {{ $member->username }} akan dapat login kembali ke jaringan."
    confirm-label="Ya, Aktifkan"
    confirm-class="btn-primary"
    action-url="{{ route('members.toggle-status', $member) }}"
    method="PATCH"
    preference-key="confirm_aktif_member_{{ $member->id }}"
/>
@endif
@endsection
