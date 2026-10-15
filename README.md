# RadiusManager — xd-radius

RadiusManager adalah panel manajemen jaringan untuk ISP dan RT/RW Net. Aplikasi dibangun dengan Laravel 11 dan terintegrasi dengan FreeRADIUS, PostgreSQL, serta MikroTik RouterOS.

## Fitur

| Modul | Fitur |
|---|---|
| Voucher | Generate batch, cetak kartu, dan masa berlaku |
| Member | Paket internet, masa aktif, serta isolir layanan |
| Billing | Invoice, pencatatan pembayaran, dan PDF |
| Monitoring | Sesi online, accounting, serta deteksi sesi stale |
| Router / NAS | Kelola MikroTik, tes koneksi, probe status, dan sinkronisasi FreeRADIUS |
| Operator | Akun operator dengan hak akses |
| Laporan | Laporan bulanan dan PDF |

## Arsitektur

- **MikroTik** mengirim autentikasi dan accounting ke FreeRADIUS pada port UDP 1812/1813.
- **FreeRADIUS** membaca data autentikasi dan menulis accounting ke PostgreSQL.
- **Laravel** menyediakan panel web dan mengelola data aplikasi serta konfigurasi NAS.
- PostgreSQL dapat memakai satu database atau koneksi database terpisah untuk aplikasi dan FreeRADIUS. Atur koneksi `DB_*` dan `RADIUS_DB_*` pada `.env` sesuai deployment.

## Instalasi satu perintah

Untuk server baru, clone repository lalu jalankan `setup.sh`. Script mendeteksi cluster PostgreSQL native dan container Docker yang ada, lalu memberi pilihan memakai salah satunya atau membuat instance PostgreSQL baru di Docker. Jika tidak ada instance terdeteksi, setup otomatis menyiapkan PostgreSQL Docker baru. Pada pilihan instance yang sudah ada, script membuat database dan role khusus aplikasi tanpa mengambil alih database/role yang sudah ada. Setelah itu script membuat `.env` serta `APP_KEY`, memasang dependency aplikasi, mengonfigurasi FreeRADIUS, menjalankan migration dan build frontend, membuat superadmin, lalu mengaktifkan service web, queue, dan scheduler.

Target otomatis adalah **Debian 12, Ubuntu 24.04+, atau Armbian berbasis Ubuntu Jammy** dengan systemd dan koneksi internet. Jalankan sebagai root (atau gunakan `sudo`).

```bash
sudo git clone https://github.com/thehanifz/xd-radius.git /opt/xd-radius
cd /opt/xd-radius
sudo bash setup.sh
```

Saat setup, pilih instance PostgreSQL yang ingin digunakan, atau pilih PostgreSQL baru di Docker. Jika instance yang dipilih belum aktif, setup akan mencoba menyalakannya. Untuk PostgreSQL baru, container memakai volume Docker persisten dan port localhost kosong yang dipilih otomatis. Masukkan email/username dan password superadmin saat diminta. Password diminta dua kali untuk verifikasi; nama tampilan dibuat dari username. Password disimpan sebagai hash di database. Password database aplikasi dibuat otomatis dan disimpan di `.env`; password login superadmin tidak disimpan sebagai teks biasa di `.env`.

Setelah selesai, buka alamat IP server yang ditampilkan setup pada port `8000`. RADIUS menerima autentikasi dan accounting pada UDP `1812` dan `1813`. Pastikan firewall jaringan mengizinkan port yang diperlukan.

Setup otomatis mengonfigurasi aplikasi untuk alamat IP lokal yang terdeteksi. Jika akan diakses memakai domain atau Cloudflare Tunnel, sesuaikan `APP_URL` di `.env` setelah instalasi, lalu jalankan `php artisan config:cache` dan restart service aplikasi.

Setup menyediakan web server bawaan Laravel pada port 8000 agar instalasi langsung dapat dibuka. Untuk deployment publik permanen, gunakan reverse proxy HTTPS atau Cloudflare Tunnel sesuai [panduan tunnel](docs/04-cloudflare-tunnel.md).

### Instalasi ulang / server yang sudah berisi data

Bootstrap dibuat untuk clone baru. Bila `.env` sudah berisi `APP_KEY` tetapi bukan `.env` yang sebelumnya dibuat bootstrap, script berhenti agar tidak menimpa database/kredensial yang ada. Pada instalasi lama, gunakan `check` untuk diagnosis dan ikuti langkah update di [Panduan Operasional](docs/05-operational-guide.md); jangan gunakan bootstrap fresh.

```bash
sudo ./setup.sh check
```

Bootstrap mengelola tiga proses pada `ecosystem.config.cjs` dengan PM2: `xd-radius-app`, `xd-radius-queue`, dan `xd-radius-scheduler`. PM2 memulihkan proses setelah reboot melalui `pm2-root.service`.

Mode `check` memvalidasi semua prasyarat termasuk PHP, Node.js (≥18), Composer, PM2, Docker, PostgreSQL, FreeRADIUS, dan permission direktori — tanpa mengubah apapun di server:

### Hubungkan MikroTik

Tambahkan router melalui menu **Router / NAS**. Setelah itu konfigurasikan RADIUS pada MikroTik dengan IP server, shared secret, port autentikasi `1812`, dan port accounting `1813`. Tombol **Tes API** di detail router juga menerapkan accounting PPP dan HotSpot dengan interim update 5 menit. Lihat [panduan FreeRADIUS dan MikroTik](docs/02-freeradius-setup.md) untuk detail.

## Alur penggunaan

1. Buat paket layanan.
2. Generate dan cetak voucher, atau tambahkan member.
3. Pantau sesi di **User Online** dan tagihan di **Billing & Tagihan**.
4. Gunakan **Laporan Bulanan** untuk ringkasan operasional.

## Perintah operasional

```bash
# Status proses aplikasi
pm2 status
systemctl status pm2-root

# Periksa migration
php artisan migrate:status

# Jalankan scheduler satu kali
php artisan schedule:run

# Lihat log Laravel
tail -f storage/logs/laravel.log

# Lihat log aplikasi
pm2 logs xd-radius-app
```

Dokumentasi lengkap:

- [Server Setup](docs/01-server-setup.md)
- [FreeRADIUS + PostgreSQL](docs/02-freeradius-setup.md)
- [Panduan Operasional](docs/05-operational-guide.md)
- [Cloudflare Tunnel](docs/04-cloudflare-tunnel.md)

## Tech stack

- Laravel 11, PHP 8.2+
- Blade, Tailwind CSS
- PostgreSQL 14+
- FreeRADIUS 3.x
- MikroTik RouterOS API
- DomPDF dan Spatie Activity Log

## Lisensi

MIT License.
