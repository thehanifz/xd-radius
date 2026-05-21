<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Print Voucher — {{ $voucherBatch->batch_code }}</title>
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }

        body {
            font-family: 'Arial', sans-serif;
            background: #f3f4f6;
            padding: 20px;
        }

        /* Toolbar */
        .toolbar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            background: #1e293b;
            color: #f8fafc;
            padding: 12px 20px;
            border-radius: 10px;
            margin-bottom: 20px;
            max-width: 210mm;
            margin-left: auto;
            margin-right: auto;
        }
        .toolbar-info strong { font-size: 16px; display: block; }
        .toolbar-info span { color: #94a3b8; font-size: 12px; }
        .toolbar-actions { display: flex; gap: 8px; align-items: center; }
        .btn {
            padding: 7px 14px;
            border-radius: 6px;
            font-size: 13px;
            font-weight: 600;
            cursor: pointer;
            text-decoration: none;
            border: none;
            display: inline-flex;
            align-items: center;
            gap: 6px;
        }
        .btn-print { background: #4f46e5; color: white; }
        .btn-print:hover { background: #4338ca; }
        .btn-secondary { background: #374151; color: #e2e8f0; }
        .btn-secondary:hover { background: #4b5563; }

        /* A4 Sheet */
        .a4-sheet {
            width: 210mm;
            min-height: 297mm;
            background: white;
            margin: 0 auto;
            padding: 12mm 10mm;
            box-shadow: 0 4px 20px rgba(0,0,0,0.12);
            border-radius: 4px;
        }

        /* Grid */
        .voucher-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 6mm;
        }

        /* Voucher Card */
        .voucher-card {
            border: 1.5px dashed #94a3b8;
            border-radius: 6px;
            padding: 8px;
            page-break-inside: avoid;
        }
        .card-header {
            text-align: center;
            border-bottom: 1px solid #e2e8f0;
            padding-bottom: 6px;
            margin-bottom: 6px;
        }
        .card-brand {
            font-size: 11px;
            font-weight: 800;
            letter-spacing: 1px;
            text-transform: uppercase;
            color: #1e293b;
        }
        .card-plan {
            font-size: 13px;
            font-weight: 700;
            color: #334155;
            margin-top: 2px;
        }
        .card-speed {
            font-size: 10px;
            color: #64748b;
        }
        .card-credentials {
            text-align: center;
            margin: 6px 0;
        }
        .cred-label {
            font-size: 8px;
            color: #94a3b8;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        .cred-value {
            font-family: 'Courier New', monospace;
            font-size: 15px;
            font-weight: 900;
            letter-spacing: 2px;
            color: #0f172a;
        }
        .card-footer {
            display: flex;
            justify-content: space-between;
            align-items: flex-end;
            border-top: 1px solid #e2e8f0;
            padding-top: 5px;
            margin-top: 5px;
        }
        .footer-price {
            font-size: 13px;
            font-weight: 800;
            color: #1e293b;
        }
        .footer-validity {
            font-size: 9px;
            color: #64748b;
            text-align: right;
        }

        /* Print */
        @media print {
            body { background: white; padding: 0; }
            .toolbar { display: none !important; }
            .a4-sheet {
                box-shadow: none;
                margin: 0;
                padding: 10mm 8mm;
                width: 100%;
                min-height: unset;
            }
            .voucher-grid { grid-template-columns: repeat(3, 1fr); gap: 5mm; }
        }
    </style>
</head>
<body>

    <div class="toolbar">
        <div class="toolbar-info">
            <strong>{{ $voucherBatch->batch_code }}</strong>
            <span>{{ $vouchers->count() }} voucher &middot; {{ $voucherBatch->plan->name ?? '-' }} &middot; Layout A4</span>
        </div>
        <div class="toolbar-actions">
            <a href="{{ route('vouchers.print', $voucherBatch->id) }}?type=thermal" class="btn btn-secondary">
                <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="2" y="7" width="20" height="14" rx="2"/><path d="M16 7V5a2 2 0 0 0-2-2h-4a2 2 0 0 0-2 2v2"/></svg>
                Thermal
            </a>
            <a href="{{ route('vouchers.index') }}" class="btn btn-secondary">&larr; Kembali</a>
            <button onclick="window.print()" class="btn btn-print">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="6 9 6 2 18 2 18 9"/><path d="M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2"/><rect x="6" y="14" width="12" height="8"/></svg>
                Print A4
            </button>
        </div>
    </div>

    <div class="a4-sheet">
        <div class="voucher-grid">
            @foreach($vouchers as $voucher)
            <div class="voucher-card">
                <div class="card-header">
                    <div class="card-brand">{{ config('app.name', 'RadiusManager') }}</div>
                    <div class="card-plan">{{ $voucher->plan->name ?? '-' }}</div>
                    @if($voucher->plan)
                    <div class="card-speed">{{ $voucher->plan->duration_value }} {{ $voucher->plan->duration_unit }}</div>
                    @endif
                </div>

                <div class="card-credentials">
                    <div class="cred-label">USERNAME</div>
                    <div class="cred-value">{{ $voucher->username }}</div>
                    <div class="cred-label" style="margin-top:4px">PASSWORD</div>
                    <div class="cred-value">{{ $voucher->password_plain ?? $voucher->username }}</div>
                </div>

                <div class="card-footer">
                    <div class="footer-price">Rp {{ number_format($voucher->price_snapshot, 0, ',', '.') }}</div>
                    <div class="footer-validity">
                        Valid: {{ $voucher->plan?->duration_value }} {{ $voucher->plan?->duration_unit }} sejak login
                    </div>
                </div>
            </div>
            @endforeach
        </div>
    </div>

</body>
</html>
