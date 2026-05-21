@extends('layouts.app')
@section('title', 'Detail Member — ' . $member->username)

@section('topbar-actions')
    <div class="flex gap-2">
        <a href="{{ route('billing.create', ['member_id' => $member->id]) }}" class="btn-secondary">+ Invoice</a>
        <a href="{{ route('members.edit', $member) }}" class="btn-secondary">Edit</a>
        <form method="POST" action="{{ route('members.toggle-status', $member) }}">
            @csrf @method('PATCH')
            @if($member->status === 'active')
                <button type="submit" class="btn-danger">Isolir</button>
            @else
                <button type="submit" class="px-3 py-1.5 bg-green-600 hover:bg-green-700 text-white text-sm font-medium rounded-lg transition-colors">Aktifkan</button>
            @endif
        </form>
        <a href="{{ route('members.index') }}" class="btn-ghost">Kembali</a>
    </div>
@endsection

@section('content')
<div class="max-w-4xl space-y-5">

    {{-- Flash message --}}
    @if(session('success'))
    <div class="flex items-center gap-3 px-4 py-3 bg-green-50 border border-green-200 rounded-lg text-green-700 text-sm">
        <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
        {{ session('success') }}
    </div>
    @endif

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

    {{-- Riwayat Invoice Billing --}}
    <div class="card">
        <div class="card-header flex items-center justify-between">
            <span>Riwayat Invoice</span>
            <a href="{{ route('billing.create', ['member_id' => $member->id]) }}"
               class="text-xs font-medium text-blue-600 hover:text-blue-800 transition-colors">
                + Buat Invoice
            </a>
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
                            $statusColor = match($invoice->status) {
                                'paid'      => 'bg-green-100 text-green-700',
                                'overdue'   => 'bg-red-100 text-red-700',
                                'cancelled' => 'bg-gray-100 text-gray-500',
                                default     => 'bg-yellow-100 text-yellow-700',
                            };
                            $statusLabel = match($invoice->status) {
                                'paid'      => 'Lunas',
                                'overdue'   => 'Overdue',
                                'cancelled' => 'Batal',
                                default     => 'Pending',
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
                                <span class="px-2 py-0.5 rounded-full text-xs font-medium {{ $statusColor }}">
                                    {{ $statusLabel }}
                                </span>
                            </td>
                            <td class="px-4 py-2.5 text-right">
                                <div class="flex justify-end gap-2">
                                    <a href="{{ route('billing.show', $invoice) }}"
                                       class="text-xs text-blue-600 hover:text-blue-800 transition-colors">Detail</a>
                                    @if(in_array($invoice->status, ['pending', 'overdue']))
                                    <a href="{{ route('billing.pay.form', $invoice) }}"
                                       class="text-xs text-green-600 hover:text-green-800 transition-colors">Bayar</a>
                                    @endif
                                    <a href="{{ route('billing.pdf', $invoice) }}"
                                       class="text-xs text-slate-500 hover:text-slate-700 transition-colors" target="_blank">PDF</a>
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
                        @foreach ($sessions as $session)
                        <tr class="hover:bg-slate-50">
                            <td class="px-4 py-2 text-xs">{{ $session->acctstarttime }}</td>
                            <td class="px-4 py-2 text-xs text-slate-500">
                                {{ $session->acctstoptime ?? '—' }}
                                @if(!$session->acctstoptime)
                                    <span class="ml-1 px-1.5 py-0.5 bg-green-100 text-green-700 text-xs rounded-full">Online</span>
                                @endif
                            </td>
                            <td class="px-4 py-2 text-xs text-slate-500">
                                @php
                                    $secs = $session->acctsessiontime ?? 0;
                                    echo $secs >= 3600
                                        ? floor($secs/3600).'j '.floor(($secs%3600)/60).'m'
                                        : floor($secs/60).'m '.($secs%60).'d';
                                @endphp
                            </td>
                            <td class="px-4 py-2 text-xs text-slate-500">
                                {{ $session->nasipaddress ?? '-' }} / {{ $session->framedipaddress ?? '-' }}
                            </td>
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
                    <p class="text-sm text-slate-400 mt-0.5">Menghapus member dan semua data RADIUS terkait. Tidak dapat dibatalkan.</p>
                </div>
                <form method="POST" action="{{ route('members.destroy', $member) }}"
                    onsubmit="return confirm('Yakin hapus member {{ $member->username }}? Aksi ini tidak dapat dibatalkan.')">
                    @csrf @method('DELETE')
                    <button type="submit" class="btn-danger">Hapus Member</button>
                </form>
            </div>
        </div>
    </div>

</div>
@endsection
