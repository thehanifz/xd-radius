<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Print Voucher - {{ $batch->batch_code }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <style>
        @media print {
            body {
                margin: 0;
                padding: 0;
                background-color: white;
            }
            .no-print {
                display: none !important;
            }
            /* A4 Template Settings */
            .template-a4 {
                width: 210mm;
                margin: 0 auto;
                padding: 10mm;
            }
            .template-a4 .voucher-grid {
                display: grid;
                grid-template-columns: repeat(3, 1fr);
                gap: 5mm;
            }
            .template-a4 .voucher-card {
                border: 1px solid #000;
                border-radius: 4px;
                padding: 10px;
                page-break-inside: avoid;
            }

            /* Thermal Template Settings (58mm width typical) */
            .template-thermal {
                width: 58mm;
                margin: 0;
                padding: 0;
                font-size: 12px;
            }
            .template-thermal .voucher-grid {
                display: flex;
                flex-direction: column;
                gap: 10mm;
            }
            .template-thermal .voucher-card {
                border-bottom: 1px dashed #000;
                padding-bottom: 5mm;
                page-break-after: always;
            }
        }

        /* Screen Preview Settings */
        @media screen {
            body {
                background-color: #f3f4f6;
                padding: 20px;
            }
            .template-a4 {
                width: 210mm;
                background: white;
                margin: 20px auto;
                padding: 10mm;
                box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
            }
            .template-a4 .voucher-grid {
                display: grid;
                grid-template-columns: repeat(3, 1fr);
                gap: 5mm;
            }
            
            .template-thermal {
                width: 58mm;
                background: white;
                margin: 20px auto;
                padding: 5mm;
                box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
                font-size: 12px;
            }
            .template-thermal .voucher-grid {
                display: flex;
                flex-direction: column;
                gap: 10mm;
            }
            
            .voucher-card {
                border: 1px solid #d1d5db;
                border-radius: 4px;
                padding: 10px;
            }
        }
    </style>
</head>
<body>
    <div class="no-print flex justify-between items-center mb-6 max-w-4xl mx-auto bg-white p-4 rounded-lg shadow">
        <div>
            <h1 class="text-xl font-bold">Print Preview: {{ $batch->batch_code }}</h1>
            <p class="text-gray-500">Total: {{ $vouchers->count() }} vouchers | Template: {{ strtoupper($template) }}</p>
        </div>
        <div class="flex gap-2">
            <a href="{{ route('vouchers.print', ['batch' => $batch->id, 'template' => 'a4']) }}" class="px-4 py-2 bg-gray-200 rounded hover:bg-gray-300 {{ $template == 'a4' ? 'ring-2 ring-blue-500' : '' }}">A4</a>
            <a href="{{ route('vouchers.print', ['batch' => $batch->id, 'template' => 'thermal']) }}" class="px-4 py-2 bg-gray-200 rounded hover:bg-gray-300 {{ $template == 'thermal' ? 'ring-2 ring-blue-500' : '' }}">Thermal</a>
            <button onclick="window.print()" class="px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700 ml-4">
                <i class="fas fa-print mr-1"></i> Print Sekarang
            </button>
            <a href="{{ route('vouchers.index') }}" class="px-4 py-2 bg-red-600 text-white rounded hover:bg-red-700">Tutup</a>
        </div>
    </div>

    <div class="template-{{ $template }}">
        <div class="voucher-grid">
            @foreach($vouchers as $voucher)
            <div class="voucher-card">
                <div class="text-center border-b pb-2 mb-2">
                    <div class="font-bold text-lg">{{ env('APP_NAME', 'Hotspot Network') }}</div>
                    <div class="text-xs">{{ $voucher->plan->name }}</div>
                </div>
                
                <div class="mb-2 text-center">
                    <div class="text-xs text-gray-500">Username / Password</div>
                    <div class="font-mono text-xl font-bold tracking-wider">{{ $voucher->username }}</div>
                </div>
                
                <div class="text-xs grid grid-cols-2 gap-1 pt-2 border-t">
                    <div>
                        <span class="text-gray-500">Durasi:</span><br>
                        <strong>{{ $voucher->plan->duration_value }} {{ $voucher->plan->duration_unit }}</strong>
                    </div>
                    <div class="text-right">
                        <span class="text-gray-500">Harga:</span><br>
                        <strong>Rp{{ number_format($voucher->price_snapshot, 0, ',', '.') }}</strong>
                    </div>
                </div>
            </div>
            @endforeach
        </div>
    </div>
</body>
</html>
