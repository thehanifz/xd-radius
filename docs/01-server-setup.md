# 01 — Server Setup

## Instalasi server baru

Untuk instalasi baru, `setup.sh` memasang dan mengonfigurasi aplikasi dari satu perintah:

```bash
sudo git clone https://github.com/thehanifz/xd-radius.git /opt/xd-radius
cd /opt/xd-radius
sudo bash setup.sh
```

Script mendeteksi PostgreSQL lokal, lalu menawarkan setiap instance yang ditemukan atau pembuatan instance PostgreSQL baru dalam Docker. Deteksi mencakup cluster native (`pg_lsclusters`), container PostgreSQL Docker (termasuk port host yang dipublikasikan maupun IP jaringan container), dan endpoint TCP lokal pada port 5432–5499. Server PostgreSQL remote tidak dicari otomatis. Jika tidak ada instance lokal terdeteksi, instance Docker baru dibuat otomatis. Jika menggunakan instance lama, script membuat role/database aplikasi baru dan tidak mengubah role/database yang telah ada. Komponen yang disiapkan otomatis:

- PHP 8.2+, ekstensi PHP, Composer, Node.js/npm, PostgreSQL client, dan FreeRADIUS dengan modul PostgreSQL. Server PostgreSQL native tidak dipasang otomatis.
- Instance PostgreSQL baru (jika dipilih) berjalan di Docker dengan volume persisten dan hanya dipublikasikan ke localhost. Container tanpa port publik dapat dipakai lewat IP jaringan Docker apabila IP tersebut dapat dijangkau dari host.
- `.env`, `APP_KEY`, kredensial database acak, database aplikasi/RADIUS, serta timezone `Asia/Jakarta`.
- Dependency Composer/npm, asset production (`npm run build`), migration Laravel, dan schema FreeRADIUS.
- Konfigurasi SQL FreeRADIUS, privileged helper, kunci RSA DOKU, akun superadmin, dan proses PM2 untuk aplikasi, queue, serta scheduler.

Untuk cluster native, provisioning memakai akun sistem `postgres`. Untuk container Docker, setup membaca kredensial admin dari environment container atau meminta kredensial bila tidak tersedia. Database dan role bernama `xd_radius` harus belum ada; jika salah satunya sudah ada, proses berhenti tanpa mengubahnya.

Target bootstrap adalah **Debian 12, Ubuntu 24.04+, atau Armbian Ubuntu Jammy** dengan systemd dan akses internet. Jalankan sebagai root. Aplikasi dibuka pada IP server yang terdeteksi, port `8000`; RADIUS menggunakan UDP `1812` (authentication) dan `1813` (accounting). Pastikan firewall memperbolehkan port yang dibutuhkan.

Password database dibuat otomatis dan dicatat di `.env` dengan permission terbatas untuk `root:www-data`. Password superadmin diminta secara interaktif dan disimpan sebagai hash di database, bukan sebagai teks biasa di `.env`.

## Pemeriksaan dan layanan

```bash
sudo ./setup.sh check
pm2 status
systemctl status pm2-root freeradius
docker ps --filter name=xd-radius-postgres
pm2 logs xd-radius-app
```

`check` hanya memeriksa prasyarat. Bootstrap menjalankan ketiga proses yang tercantum di `ecosystem.config.cjs` melalui PM2 dan memulihkan proses saat reboot. Bootstrap `setup` menolak `.env` yang sudah memiliki `APP_KEY` tetapi tidak dibuat oleh bootstrap ini, untuk melindungi kredensial dan data instalasi lama. Untuk update aplikasi yang sudah berjalan, gunakan prosedur deploy pada [`05-operational-guide.md`](./05-operational-guide.md), bukan bootstrap fresh.

Setelah instalasi, ubah `APP_URL` bila aplikasi akan diakses melalui domain atau Cloudflare Tunnel, kemudian jalankan `php artisan config:cache` dan restart `xd-radius-app`.

Lihat [`02-freeradius-setup.md`](./02-freeradius-setup.md) untuk integrasi MikroTik dan [`04-cloudflare-tunnel.md`](./04-cloudflare-tunnel.md) untuk publikasi melalui tunnel.
