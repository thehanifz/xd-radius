# 📡 RadiusManager — xd-radius

**RadiusManager** adalah panel manajemen jaringan hotspot berbasis web yang dibangun di atas Laravel 11 + FreeRADIUS + PostgreSQL. Dirancang untuk ISP kecil dan RT/RW Net yang membutuhkan manajemen voucher, member, billing, dan monitoring sesi secara terpusat.

---

## ✨ Fitur Utama

| Modul | Fitur |
|---|---|
| **Voucher** | Generate batch, cetak kartu, expire otomatis |
| **Member** | Paket internet, masa aktif, toggle isolir |
| **Billing** | Invoice otomatis, pembayaran, PDF |
| **Monitoring** | Sesi online real-time, deteksi stale session |
| **Router / NAS** | Manajemen MikroTik, sync ke FreeRADIUS otomatis |
| **Operator** | Multi-user dengan role Superuser / Operator |
| **Laporan** | Laporan bulanan, export PDF |
| **Pengaturan** | Nama app, SSID, threshold, billing config |

---

## 🏗️ Arsitektur

```
MikroTik (NAS)
  │
  ├──► FreeRADIUS :1812/:1813   ← Auth & Accounting
  │         │
  │         └── Baca: radcheck, radreply, nas
  │         └── Tulis: radacct, radpostauth
  │
  └──► ◄── xd-radius (Laravel) ← Web Management Panel
                │
                └── PostgreSQL (satu DB, dua schema)
```

- **FreeRADIUS** menangani semua autentikasi dan accounting
- **xd-radius** mengelola data user, billing, dan konfigurasi
- **MikroTik** dikonfigurasi sebagai RADIUS client dan dikontrol via RouterOS API untuk CoA

---

## ⚙️ Kebutuhan Sistem

| Komponen | Versi Minimum |
|---|---|
| PHP | 8.2+ |
| Laravel | 11.x |
| PostgreSQL | 14+ |
| FreeRADIUS | 3.x |
| Composer | 2.x |
| Node.js | 18+ (untuk asset build) |

---

## 🚀 Instalasi

### 1. Clone Repository

```bash
git clone https://github.com/thehanifz/xd-radius.git
cd xd-radius
```

### 2. Install Dependencies

```bash
composer install
npm install && npm run build
```

### 3. Konfigurasi Environment

```bash
cp .env.example .env
php artisan key:generate
```

Edit `.env`, sesuaikan konfigurasi database:

```env
APP_NAME=RadiusManager
APP_URL=http://your-domain.com

DB_CONNECTION=pgsql
DB_HOST=127.0.0.1
DB_PORT=5433
DB_DATABASE=radius_db
DB_USERNAME=radius_user
DB_PASSWORD=your_password
```

> ⚠️ **Pastikan** database yang digunakan **sama** dengan yang dikonfigurasi di FreeRADIUS (`radius_db`), agar tabel `radcheck`, `radacct`, `nas`, dll dapat diakses bersama.

### 4. Migrasi Database

```bash
php artisan migrate
php artisan db:seed --class=SystemSettingSeeder
```

### 5. Setup Sudoers untuk Auto-Reload FreeRADIUS

Agar xd-radius dapat reload FreeRADIUS otomatis setelah perubahan router/NAS:

```bash
echo "www-data ALL=(ALL) NOPASSWD: /bin/systemctl reload freeradius" \
  | sudo tee /etc/sudoers.d/freeradius-reload
chmod 440 /etc/sudoers.d/freeradius-reload
```

### 6. Konfigurasi FreeRADIUS (baca NAS dari database)

Pastikan di `/etc/freeradius/3.0/mods-enabled/sql`:

```
read_clients = yes
client_table = "nas"
```

Dan `radius_db` mengarah ke database yang sama dengan xd-radius.

### 7. Queue Worker (opsional, untuk job scheduler)

```bash
php artisan queue:work --daemon
```

Atau gunakan Supervisor. Scheduler dijalankan via cron:

```bash
* * * * * cd /path/to/xd-radius && php artisan schedule:run >> /dev/null 2>&1
```

### 8. Web Server

Arahkan document root ke `/public`. Contoh Nginx:

```nginx
server {
    listen 80;
    server_name your-domain.com;
    root /var/www/xd-radius/public;

    add_header X-Frame-Options "SAMEORIGIN";
    index index.php;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php8.2-fpm.sock;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        include fastcgi_params;
    }
}
```

---

## 🔧 Setup Awal (Onboarding)

### Langkah 1 — Buat Akun Super Administrator

Buka browser ke:
```
https://your-domain.com/setup
```

Isi nama, email, dan password untuk akun Superuser pertama.

### Langkah 2 — Pengaturan Sistem

Masuk ke **Pengaturan Sistem** (`/settings`) dan sesuaikan:

| Key | Keterangan |
|---|---|
| `app_name` | Nama aplikasi (tampil di header & PDF) |
| `ssid_name` | Nama WiFi yang dicetak di voucher |
| `stale_threshold_minutes` | Menit sebelum sesi dianggap stale (default: 30) |
| `invoice_days_before` | H-N generate invoice sebelum expire (default: 7) |
| `overdue_isolate_auto` | Auto isolir member overdue (default: off) |

### Langkah 3 — Tambah Router / NAS

Masuk ke **Router / NAS** (`/routers/create`) dan isi:

**Kredensial RouterOS API** (untuk CoA & kontrol MikroTik):
- IP Address MikroTik
- API Port (default: `8728`)
- Username & Password API

**RADIUS Shared Secret** (untuk autentikasi FreeRADIUS):
- Isi secret yang sama persis dengan yang akan diisi di MikroTik

Setelah simpan, xd-radius otomatis:
1. Insert/update tabel `nas` di PostgreSQL
2. Reload FreeRADIUS (tanpa memutus sesi aktif)

### Langkah 4 — Konfigurasi MikroTik

Di **Winbox** → **Radius** → **Add**:

| Field | Value |
|---|---|
| Service | `hotspot` (dan/atau `ppp`) |
| Address | IP server FreeRADIUS |
| Secret | Secret yang diisi di xd-radius |
| Authentication Port | `1812` |
| Accounting Port | `1813` |
| Timeout | `3000` ms |

Aktifkan RADIUS di **Hotspot** → **Server Profiles** → centang **Use RADIUS**.

---

## 📋 Contoh Penggunaan

### Generate Voucher

1. Buat **Paket Internet** dulu di `/plans/create`
   - Nama paket, harga, masa aktif (hari), batas data/kecepatan
2. Buka `/vouchers/create`
3. Pilih paket, isi jumlah voucher, prefix kode
4. Klik **Generate** → voucher otomatis masuk `radcheck`
5. Cetak via tombol **Print Batch**

### Tambah Member Pascabayar

1. Buka `/members/create`
2. Isi nama, username RADIUS, paket, tanggal aktif
3. Simpan → user otomatis masuk `radcheck` & `radreply`
4. Invoice akan dibuat otomatis H-7 sebelum expire

### Monitoring Sesi Online

Buka `/online` → tampil semua sesi aktif dari `radacct`:
- Username, IP, NAS, durasi, upload/download
- Sesi yang tidak update lebih dari `stale_threshold_minutes` ditandai **Diduga Putus**

### Laporan Bulanan

Buka `/reports/monthly` → pilih bulan/tahun → tampil:
- Total pendapatan, jumlah member aktif, voucher terjual
- Export PDF siap cetak

---

## 🔒 Role & Akses

| Role | Akses |
|---|---|
| **Superuser** | Semua fitur termasuk Operator, Router, Pengaturan |
| **Operator** | Voucher, Member, Billing, Monitoring, Laporan |

---

## 📅 Scheduled Jobs

| Job | Jadwal | Fungsi |
|---|---|---|
| `ReconcileStaleSessionsJob` | Setiap 5 menit | Tandai sesi stale |
| `GenerateOverdueInvoicesJob` | Setiap hari 08:00 | Generate invoice jatuh tempo |
| `AutoIsolateOverdueMembersJob` | Setiap hari 02:00 | Isolir member overdue (jika aktif) |
| `SyncFirstLoginAtJob` | Setiap jam | Sinkronisasi first login member |

---

## 🛠️ Perintah Berguna

```bash
# Clear semua cache
php artisan config:clear && php artisan cache:clear && php artisan view:clear

# Cek status migration
php artisan migrate:status

# Jalankan scheduler manual
php artisan schedule:run

# Cek semua route
php artisan route:list

# Tail log aplikasi
tail -f storage/logs/laravel.log
```

---

## 📦 Tech Stack

- **Backend**: Laravel 11, PHP 8.2
- **Frontend**: Tailwind CSS (via CDN), Blade Templates
- **Database**: PostgreSQL 14+
- **AAA Server**: FreeRADIUS 3.x
- **Activity Log**: spatie/laravel-activitylog
- **PDF**: barryvdh/laravel-dompdf

---

## 📄 Lisensi

MIT License — bebas digunakan dan dimodifikasi.

---

> Dibuat untuk kebutuhan manajemen jaringan RT/RW Net & ISP lokal Indonesia 🇮🇩
