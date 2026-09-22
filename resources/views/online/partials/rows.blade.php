@forelse($sessions as $s)
@php
    $isStale    = $s->is_stale;
    $isVoucher  = isset($voucherUsers[$s->username]);
    $type       = $isVoucher ? 'Voucher' : 'Member';
@endphp
<tr class="{{ $isStale ? 'opacity-60' : '' }}">
    <td class="font-mono text-sm font-semibold">{{ $s->username }}</td>
    <td><span class="badge-{{ $isVoucher ? 'info' : 'purple' }} text-xs">{{ $type }}</span></td>
    <td class="text-slate-500 text-sm">{{ $s->nasipaddress }}</td>
    <td class="text-slate-500 text-sm font-mono">{{ $s->framedipaddress ?? '-' }}</td>
    <td class="text-slate-500 text-sm">{{ $s->acctstarttime?->format('d/m H:i') ?? '-' }}</td>
    <td class="text-slate-500 text-sm">{{ $s->duration }}</td>
    <td class="text-slate-500 text-xs whitespace-nowrap">↑ {{ $s->upload_label }} / ↓ {{ $s->download_label }}</td>
    <td>
        @if($isStale)
            <span class="badge-isolated text-xs">Diduga Putus</span>
        @else
            <span class="badge-active text-xs">Online</span>
        @endif
    </td>
    <td class="text-right whitespace-nowrap">
        <a href="{{ route('online.show', $s) }}" class="btn-sm-secondary mr-1">Detail</a>
        <button disabled
            title="Disconnect akan tersedia setelah integrasi RouterOS"
            class="btn-sm-danger opacity-40 cursor-not-allowed">
            Disconnect
        </button>
    </td>
</tr>
@empty
<tr><td colspan="9" class="text-center text-slate-400 py-12">
    <svg class="mx-auto mb-3 w-10 h-10 text-slate-200" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><circle cx="12" cy="12" r="10"/><path d="M8 12h8"/></svg>
    Tidak ada sesi aktif
</td></tr>
@endforelse
