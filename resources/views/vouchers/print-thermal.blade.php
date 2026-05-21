<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="utf-8">
<title>Print Voucher Thermal — Batch #{{ $batch }}</title>
<style>
* { margin: 0; padding: 0; box-sizing: border-box; }

@media print {
    @page {
        /* Ganti ke 80mm jika printer 80mm */
        size: 58mm auto;
        margin: 0;
    }
    body { background: white; }
    .no-print { display: none !important; }
    .page-break { page-break-after: always; }
}

body {
    font-family: 'Courier New', Courier, monospace;
    font-size: 9pt;
    background: #f5f5f5;
    padding: 8px;
}

.toolbar {
    background: #1e293b;
    color: white;
    padding: 10px 16px;
    border-radius: 8px;
    margin-bottom: 16px;
    display: flex;
    align-items: center;
    gap: 12px;
    flex-wrap: wrap;
}
.toolbar label { font-size: 12px; color: #94a3b8; }
.toolbar select, .toolbar button {
    padding: 5px 10px; border-radius: 6px;
    border: none; cursor: pointer; font-size: 12px;
}
.toolbar .btn-print {
    background: #6366f1; color: white;
    padding: 6px 16px; font-weight: 600;
    margin-left: auto;
}
.toolbar .btn-print:hover { background: #4f46e5; }

.voucher-grid {
    display: flex;
    flex-wrap: wrap;
    gap: 6px;
    justify-content: flex-start;
}

.voucher {
    width: 54mm;
    background: white;
    border: 1px dashed #ccc;
    border-radius: 4px;
    padding: 4mm 3mm;
    text-align: center;
    break-inside: avoid;
    page-break-inside: avoid;
}

.voucher .store-name {
    font-size: 8pt;
    font-weight: bold;
    text-transform: uppercase;
    letter-spacing: 1px;
    border-bottom: 1px dashed #999;
    padding-bottom: 2mm;
    margin-bottom: 2mm;
}

.voucher .plan-name {
    font-size: 10pt;
    font-weight: bold;
    margin-bottom: 1mm;
}

.voucher .plan-desc {
    font-size: 7.5pt;
    color: #555;
    margin-bottom: 2mm;
}

.voucher .code-label {
    font-size: 7pt;
    color: #888;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.voucher .code {
    font-size: 12pt;
    font-weight: bold;
    letter-spacing: 2px;
    margin: 1mm 0;
    word-break: break-all;
}

.voucher .divider {
    border-top: 1px dashed #ccc;
    margin: 2mm 0;
}

.voucher .price {
    font-size: 9pt;
    font-weight: bold;
}

.voucher .valid {
    font-size: 7pt;
    color: #777;
    margin-top: 1mm;
}

.voucher .footer-note {
    font-size: 6.5pt;
    color: #aaa;
    margin-top: 2mm;
    border-top: 1px dashed #eee;
    padding-top: 1.5mm;
}

/* 2-per-row mode */
.layout-2col .voucher-grid { gap: 4px; }
.layout-2col .voucher { width: calc(50% - 4px); }

/* A4 mode */
.layout-a4 .voucher-grid { gap: 6px; }
.layout-a4 .voucher { width: 54mm; }
@media print {
    .layout-a4 @page { size: A4 portrait; margin: 8mm; }
}
</style>
</head>
<body>

<div class="toolbar no-print">
    <label>Layout:</label>
    <select onchange="changeLayout(this.value)">
        <option value="thermal">Thermal 58mm (1 kolom)</option>
        <option value="2col">Thermal 80mm (2 kolom)</option>
        <option value="a4">A4 (multi)</option>
    </select>
    <span style="color:#64748b;font-size:11px;">{{ count($vouchers) }} voucher — Batch #{{ $batch }}</span>
    <button class="btn-print" onclick="window.print()">🖨 Print</button>
    <a href="{{ url()->previous() }}" style="color:#94a3b8;font-size:12px;">← Kembali</a>
</div>

<div id="page-wrapper" class="">
    <div class="voucher-grid">
        @foreach($vouchers as $v)
        <div class="voucher">
            <div class="store-name">{{ config('app.name', 'Hotspot Voucher') }}</div>
            <div class="plan-name">{{ $v->plan->name ?? 'Voucher' }}</div>
            @if($v->plan)
            <div class="plan-desc">
                {{ $v->plan->speed_label ?? '' }}
                @if($v->plan->duration_days) · {{ $v->plan->duration_days }} hari @endif
            </div>
            @endif
            <div class="divider"></div>
            <div class="code-label">Username</div>
            <div class="code">{{ $v->username }}</div>
            <div class="code-label" style="margin-top:1mm">Password</div>
            <div class="code">{{ $v->password_plain }}</div>
            <div class="divider"></div>
            <div class="price">Rp {{ number_format($v->plan->price ?? 0, 0, ',', '.') }}</div>
            <div class="valid">
                @if($v->expired_at)
                    Exp: {{ \Carbon\Carbon::parse($v->expired_at)->format('d/m/Y') }}
                @else
                    Valid: {{ $v->plan->duration_days ?? '?' }} hari sejak login
                @endif
            </div>
            <div class="footer-note">Simpan kode ini. Satu kode untuk satu perangkat.</div>
        </div>
        @endforeach
    </div>
</div>

<script>
function changeLayout(val) {
    const wrapper = document.getElementById('page-wrapper');
    wrapper.className = val === '2col' ? 'layout-2col' : val === 'a4' ? 'layout-a4' : '';

    const style = document.querySelector('style');
    if (val === '2col') {
        document.title = 'Print Thermal 80mm';
        // update @page size via meta (tidak bisa JS, user set manual)
    } else if (val === 'a4') {
        document.title = 'Print A4';
    } else {
        document.title = 'Print Thermal 58mm';
    }
}
</script>
</body>
</html>
