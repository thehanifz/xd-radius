<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<style>
  body { font-family: DejaVu Sans, sans-serif; font-size: 9px; color: #1e293b; margin: 0; padding: 20px; }
  h1 { font-size: 14px; margin: 0 0 4px; color: #1e293b; }
  .subtitle { font-size: 9px; color: #64748b; margin-bottom: 16px; }
  .summary { display: flex; gap: 16px; margin-bottom: 16px; }
  .summary-box { background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 6px; padding: 8px 12px; }
  .summary-box .label { font-size: 8px; color: #94a3b8; margin-bottom: 2px; }
  .summary-box .value { font-size: 12px; font-weight: 700; color: #334155; }
  table { width: 100%; border-collapse: collapse; margin-bottom: 16px; }
  thead tr { background: #6366f1; color: white; }
  thead th { padding: 5px 6px; text-align: left; font-size: 8px; font-weight: 600; }
  tbody tr:nth-child(even) { background: #f8fafc; }
  tbody td { padding: 4px 6px; border-bottom: 1px solid #e2e8f0; font-size: 8px; }
  .badge { padding: 2px 6px; border-radius: 99px; font-size: 7px; font-weight: 600; }
  .badge-active { background: #dcfce7; color: #166534; }
  .badge-expired { background: #f1f5f9; color: #64748b; }
  .badge-isolated { background: #fee2e2; color: #991b1b; }
  .badge-pending { background: #fef9c3; color: #854d0e; }
  .section-title { font-size: 11px; font-weight: 700; margin: 12px 0 6px; color: #334155; border-bottom: 2px solid #6366f1; padding-bottom: 3px; }
  .footer { margin-top: 20px; font-size: 7px; color: #94a3b8; text-align: right; }
</style>
</head>
<body>

<h1>Laporan Bulanan — RadiusManager</h1>
<p class="subtitle">
    Periode: {{ \Carbon\Carbon::create(null, $month)->translatedFormat('F') }} {{ $year }}
    &nbsp;|&nbsp; Digenerate: {{ now()->format('d/m/Y H:i') }}
    &nbsp;|&nbsp; Tipe: {{ ucfirst($type) }}
</p>

<div class="summary">
    <div class="summary-box">
        <div class="label">Voucher Aktif</div>
        <div class="value">{{ $summary['total_voucher_active'] }}</div>
    </div>
    <div class="summary-box">
        <div class="label">Voucher Expired</div>
        <div class="value">{{ $summary['total_voucher_expired'] }}</div>
    </div>
    <div class="summary-box">
        <div class="label">Member Aktif</div>
        <div class="value">{{ $summary['total_member_active'] }}</div>
    </div>
    <div class="summary-box">
        <div class="label">Est. Pendapatan</div>
        <div class="value">Rp {{ number_format($summary['total_revenue']) }}</div>
    </div>
</div>

@if($vouchers->isNotEmpty())
<div class="section-title">Voucher ({{ $vouchers->count() }})</div>
<table>
    <thead><tr>
        <th>Batch</th><th>Username</th><th>Operator</th><th>Paket</th>
        <th>Harga</th><th>Dibuat</th><th>First Login</th><th>Expired</th><th>Status</th>
    </tr></thead>
    <tbody>
    @foreach($vouchers as $v)
    <tr>
        <td>{{ $v->batch?->batch_code ?? '-' }}</td>
        <td>{{ $v->username }}</td>
        <td>{{ $v->batch?->generatedBy?->name ?? '-' }}</td>
        <td>{{ $v->plan?->name ?? '-' }}</td>
        <td>Rp {{ number_format($v->price_snapshot) }}</td>
        <td>{{ $v->created_at?->format('d/m/Y') }}</td>
        <td>{{ $v->first_login_at?->format('d/m/Y H:i') ?? '-' }}</td>
        <td>{{ $v->expired_at?->format('d/m/Y') ?? '-' }}</td>
        <td><span class="badge badge-{{ $v->status === 'active' ? 'active' : ($v->status === 'expired' ? 'expired' : 'isolated') }}">{{ ucfirst($v->status) }}</span></td>
    </tr>
    @endforeach
    </tbody>
</table>
@endif

@if($members->isNotEmpty())
<div class="section-title">Member ({{ $members->count() }})</div>
<table>
    <thead><tr>
        <th>Username</th><th>Paket</th><th>Harga</th>
        <th>Aktif Sejak</th><th>Expired</th><th>Status</th>
    </tr></thead>
    <tbody>
    @foreach($members as $m)
    <tr>
        <td>{{ $m->username }}</td>
        <td>{{ $m->plan?->name ?? '-' }}</td>
        <td>Rp {{ number_format($m->price_snapshot) }}</td>
        <td>{{ $m->activated_at?->format('d/m/Y') ?? '-' }}</td>
        <td>{{ $m->expired_at?->format('d/m/Y') ?? '-' }}</td>
        <td><span class="badge badge-{{ $m->status === 'active' ? 'active' : ($m->status === 'isolated' ? 'isolated' : 'expired') }}">{{ ucfirst($m->status) }}</span></td>
    </tr>
    @endforeach
    </tbody>
</table>
@endif

<div class="footer">RadiusManager — Digenerate otomatis {{ now()->format('d/m/Y H:i:s') }}</div>
</body>
</html>
