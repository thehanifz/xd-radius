@forelse($sessions as $s)
@php
    $isStale = $s->is_stale;
    $isVoucher = isset($voucherUsers[$s->username]);
    $type = $isVoucher ? 'Voucher' : 'Member';
@endphp
<tr class="online-session-row {{ $isStale ? 'is-stale' : '' }}">
    <td><span class="online-username">{{ $s->username }}</span></td>
    <td><span class="online-type-pill {{ $isVoucher ? 'is-voucher' : 'is-member' }}">{{ $type }}</span></td>
    <td><span class="online-cell-muted">{{ $routerNames->get($s->nasipaddress, 'MikroTik tidak terdaftar') }}</span></td>
    <td><span class="online-mono">{{ $s->framedipaddress ?? '-' }}</span></td>
    <td><span class="online-cell-muted">{{ $s->acctstarttime?->format('d/m H:i') ?? '-' }}</span></td>
    <td><span class="online-cell-muted">{{ $s->duration }}</span></td>
    <td><span class="online-traffic"><span class="upload">↑ {{ $s->upload_label }}</span><span class="download">↓ {{ $s->download_label }}</span></span></td>
    <td>
        @if($isStale)
            <span class="online-status-pill is-stale"><span></span> Diduga putus</span>
        @else
            <span class="online-status-pill is-online"><span></span> Online</span>
        @endif
    </td>
    <td class="text-right whitespace-nowrap"><a href="{{ route('online.show', $s) }}" class="btn-sm-secondary">Detail</a></td>
</tr>
@empty
<tr><td colspan="9"><div class="online-empty-state">
    <span class="online-empty-icon" aria-hidden="true">∅</span>
    <strong>Tidak ada sesi yang cocok</strong>
    <span>Coba ubah filter tipe, NAS, atau status sesi.</span>
</div></td></tr>
@endforelse
