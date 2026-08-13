<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Koneksi Terisolir - Menunggu Pembayaran</title>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
    @vite(['resources/css/app.css'])
    <style>
        body {
            font-family: 'Plus Jakarta Sans', sans-serif;
            background-color: #f8fafc;
            display: flex;
            align-items: center;
            justify-content: center;
            min-height: 100vh;
            margin: 0;
            padding: 1rem;
        }
        .isolir-card {
            background: white;
            border-radius: 24px;
            box-shadow: 0 10px 40px -10px rgba(0,0,0,0.1);
            max-width: 460px;
            width: 100%;
            padding: 2.5rem 2rem;
            text-align: center;
            border-top: 6px solid #ef4444;
        }
        .icon-container {
            width: 80px;
            height: 80px;
            background: #fee2e2;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 1.5rem;
        }
        .icon-container svg {
            width: 40px;
            height: 40px;
            color: #ef4444;
        }
        h1 {
            font-size: 1.5rem;
            font-weight: 700;
            color: #1e293b;
            margin-bottom: 0.75rem;
            line-height: 1.3;
        }
        p {
            color: #64748b;
            font-size: 0.95rem;
            line-height: 1.6;
            margin-bottom: 2rem;
        }
        .btn-refresh {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
            background: #1e293b;
            color: white;
            font-weight: 600;
            padding: 0.75rem 1.5rem;
            border-radius: 12px;
            text-decoration: none;
            transition: all 0.2s;
            width: 100%;
        }
        .btn-refresh:hover {
            background: #0f172a;
            transform: translateY(-1px);
        }
        .support-contact {
            margin-top: 2rem;
            font-size: 0.85rem;
            color: #94a3b8;
        }
        .support-contact a {
            color: #3b82f6;
            text-decoration: none;
            font-weight: 500;
        }
    </style>
</head>
<body>
    <div class="isolir-card">
        <div class="icon-container">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <rect x="2" y="5" width="20" height="14" rx="2"></rect>
                <line x1="2" y1="10" x2="22" y2="10"></line>
                <path d="M7 15h.01"></path>
                <path d="M11 15h2"></path>
            </svg>
        </div>
        <h1>Layanan Internet<br>Sedang Terisolir</h1>
        <p>Maaf, akses internet Anda sementara diblokir karena terdapat tagihan yang belum diselesaikan. Silakan lakukan pembayaran tagihan untuk menikmati kembali layanan internet Anda.</p>
        
        <a href="http://192.168.100.3/isolir" class="btn-refresh">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <polyline points="23 4 23 10 17 10"></polyline>
                <path d="M20.49 15a9 9 0 1 1-2.12-9.36L23 10"></path>
            </svg>
            Cek Status Pembayaran
        </a>
        
        <div class="support-contact">
            Jika Anda sudah membayar namun internet belum aktif, silakan hubungi <a href="#">Customer Service</a>.
        </div>
    </div>
</body>
</html>
