<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Helvetica', sans-serif; font-size: 12px; color: #1e293b; padding: 40px; }
        .header { display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 32px; padding-bottom: 24px; border-bottom: 2px solid #e2e8f0; }
        .brand { font-size: 20px; font-weight: 700; color: #1e1b4b; }
        .brand small { display: block; font-size: 11px; font-weight: 400; color: #64748b; margin-top: 2px; }
        .invoice-meta { text-align: right; }
        .invoice-meta h2 { font-size: 18px; font-weight: 700; color: #4f46e5; }
        .invoice-meta p { font-size: 11px; color: #64748b; margin-top: 2px; }
        .section { margin-bottom: 20px; }
        .section-title { font-size: 10px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.05em; color: #94a3b8; margin-bottom: 8px; }
        .info-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 16px; }
        .info-item label { font-size: 10px; color: #94a3b8; text-transform: uppercase; font-weight: 600; display: block; margin-bottom: 2px; }
        .info-item p { font-size: 13px; font-weight: 600; color: #1e293b; }
        .amount-box { background: #eef2ff; border-radius: 8px; padding: 16px 20px; margin: 20px 0; display: flex; justify-content: space-between; align-items: center; }
        .amount-box .label { font-size: 11px; color: #6366f1; font-weight: 600; }
        .amount-box .value { font-size: 22px; font-weight: 700; color: #4338ca; }
        .status-badge { display: inline-block; padding: 3px 10px; border-radius: 999px; font-size: 11px; font-weight: 600; }
        .status-paid { background: #dcfce7; color: #166534; }
        .status-pending { background: #fef9c3; color: #854d0e; }
        .status-overdue { background: #fee2e2; color: #991b1b; }
        .status-cancelled { background: #f1f5f9; color: #475569; }
        table { width: 100%; border-collapse: collapse; margin-top: 10px; }
        thead { background: #f8fafc; }
        th { padding: 8px 12px; text-align: left; font-size: 10px; font-weight: 700; text-transform: uppercase; color: #94a3b8; border-bottom: 1px solid #e2e8f0; }
        td { padding: 10px 12px; font-size: 11px; border-bottom: 1px solid #f1f5f9; }
        .footer { margin-top: 40px; padding-top: 20px; border-top: 1px solid #e2e8f0; text-align: center; font-size: 10px; color: #94a3b8; }
    </style>
</head>
<body>
    <div class="header">
        <div class="brand">
            RadiusManager
            <small>Panel Administrasi FreeRADIUS</small>
        </div>
        <div class="invoice-meta">
            <h2>INVOICE</h2>
            <p>#{{ str_pad($invoice->id, 6, '0', STR_PAD_LEFT) }}</p>
            <p>Diterbitkan: {{ $invoice->created_at->format('d M Y') }}</p>
        </div>
    </div>

    <div class="section">
        <div class="info-grid">
            <div class="info-item">
                <label>Member</label>
                <p>{{ $invoice->member->username }}</p>
            </div>
            <div class="info-item">
                <label>Paket</label>
                <p>{{ $invoice->member->plan->name ?? '-' }}</p>
            </div>
            <div class="info-item">
                <label>Periode Tagihan</label>
                <p>{{ $invoice->period_start->format('d M Y') }} – {{ $invoice->period_end->format('d M Y') }}</p>
            </div>
            <div class="info-item">
                <label>Jatuh Tempo</label>
                <p>{{ $invoice->due_date->format('d M Y') }}</p>
            </div>
        </div>
    </div>

    <div class="amount-box">
        <span class="label">Total Tagihan</span>
        <span class="value">{{ $invoice->amount_label }}</span>
    </div>

    <div class="section">
        <div class="info-item">
            <label>Status</label>
            <span class="status-badge status-{{ $invoice->status }}">{{ $invoice->status_label }}</span>
        </div>
    </div>

    @if($invoice->payments->isNotEmpty())
    <div class="section">
        <div class="section-title">Riwayat Pembayaran</div>
        <table>
            <thead>
                <tr>
                    <th>Tanggal</th>
                    <th>Nominal</th>
                    <th>Metode</th>
                    <th>Catatan</th>
                </tr>
            </thead>
            <tbody>
                @foreach($invoice->payments as $pay)
                <tr>
                    <td>{{ $pay->paid_at->format('d M Y H:i') }}</td>
                    <td>{{ $pay->amount_label }}</td>
                    <td>{{ $pay->method_label }}</td>
                    <td>{{ $pay->notes ?? '-' }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    @endif

    <div class="footer">
        Dokumen ini digenerate otomatis oleh RadiusManager &bull; {{ now()->format('d M Y H:i') }}
    </div>
</body>
</html>
