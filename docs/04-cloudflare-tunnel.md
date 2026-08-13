# 04 — Cloudflare Tunnel Setup

Cloudflare Tunnel meneruskan trafik HTTPS publik ke RadiusManager yang berjalan melalui PM2 pada port `8000`. Aplikasi listen pada `0.0.0.0:8000` agar dapat dijangkau oleh cloudflared, termasuk bila cloudflared berjalan pada container terpisah.

## Konfigurasi ingress

Atur tunnel agar origin mengarah ke loopback server:

```yaml
ingress:
  - hostname: radius.contoh-domain.com
    service: http://127.0.0.1:8000
  - service: http_status:404
```

Sesuaikan nama host, lalu restart layanan cloudflared sesuai metode instalasi Anda.

## Konfigurasi aplikasi

Pada `.env` production, gunakan nilai berikut dan sesuaikan domain:

```env
APP_ENV=production
APP_DEBUG=false
APP_URL=https://radius.contoh-domain.com
TRUSTED_PROXIES=*
SESSION_SECURE_COOKIE=true
```

`bootstrap/app.php` telah mengaktifkan trusted proxies. Ini membuat Laravel menghormati informasi HTTPS dan IP klien yang diteruskan Cloudflare, sehingga session cookie serta rate limiting bekerja semestinya.

## Verifikasi

1. Jalankan `pm2 start ecosystem.config.cjs` lalu cek `pm2 status`.
2. Dari server, buka `http://127.0.0.1:8000/up`.
3. Buka hostname publik HTTPS dan login.
4. Jika akses langsung ke port 8000 tidak diperlukan, batasi melalui firewall hanya untuk jaringan internal/tunnel.
