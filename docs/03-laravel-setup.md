# 03 — Laravel Setup & Development Journal

Catatan lengkap perjalanan setup Laravel hingga siap development UI.

---

## Stack

| Komponen | Versi |
|---|---|
| Laravel | 11.x |
| PHP | 8.2 |
| PostgreSQL | 18.1 (Docker, port 5433) |
| FreeRADIUS | 3.2.1 |
| Node.js / NPM | (sistem) |
| Tailwind CSS | 3.x |
| Vite | 6.x |

---

## Packages Terinstall

| Package | Versi | Fungsi |
|---|---|---|
| `barryvdh/laravel-dompdf` | ^3.1 | Generate PDF laporan & invoice |
| `spatie/laravel-activitylog` | ^4.0 | Audit log semua aktivitas |
| `evilfreelancer/routeros-api-php` | ^1.7 | Koneksi MikroTik RouterOS API |

> ⚠️ `spatie/laravel-activitylog` v5 butuh PHP 8.4, gunakan v4 untuk PHP 8.2
> ⚠️ `mikrotik/routeros-api` tidak ada di Packagist, gunakan `evilfreelancer/routeros-api-php`

---

## Environment (.env)

```env
APP_NAME=RadiusManager
APP_ENV=production
APP_DEBUG=false
APP_URL=http://localhost:8000
APP_TIMEZONE=Asia/Jakarta
APP_LOCALE=id

DB_CONNECTION=pgsql
DB_HOST=127.0.0.1
DB_PORT=5433
DB_DATABASE=radius_db
DB_USERNAME=radius_user
DB_PASSWORD=RadiusManager@2026

SESSION_DRIVER=database
CACHE_STORE=database
QUEUE_CONNECTION=database

TRUSTED_PROXIES=*
SESSION_SECURE_COOKIE=false
```

---

## Database Schema

### Tabel FreeRADIUS (schema standar)
| Tabel | Keterangan |
|---|---|
| `radcheck` | Kredensial user untuk autentikasi |
| `radreply` | Atribut reply ke NAS setelah autentikasi |
| `radusergroup` | Mapping user ke group |
| `radgroupcheck` | Policy autentikasi per group |
| `radgroupreply` | Atribut reply per group |
| `radacct` | Accounting session (+ kolom custom `is_stale`, `stale_detected_at`) |
| `radpostauth` | Log post-authentication |
| `nas` | Daftar NAS (router MikroTik) |
| `nasreload` | Trigger reload NAS |

### Tabel App Custom
| Tabel | Keterangan |
|---|---|
| `app_users` | User aplikasi (superuser & operator) |
| `app_user_preferences` | Preferensi per user (key-value) |
| `plans` | Paket internet (kecepatan, durasi, harga) |
| `voucher_batches` | Batch generate voucher |
| `vouchers` | Voucher hotspot individual |
| `members` | Member berlangganan bulanan |
| `routers` | Data router MikroTik |
| `billing_invoices` | Invoice tagihan member |
| `payments` | Record pembayaran invoice |
| `service_action_logs` | Log aksi manual (isolate, activate, extend) |

### Tabel Laravel Core
| Tabel | Keterangan |
|---|---|
| `users` | User Laravel default (tidak dipakai aktif) |
| `sessions` | Session database |
| `cache` / `cache_locks` | Cache database |
| `jobs` / `job_batches` / `failed_jobs` | Queue jobs |
| `migrations` | Tracking migrasi |
| `password_reset_tokens` | Token reset password |
| `activity_log` | Log aktivitas (spatie) |

---

## Auth System

### Guard Custom
File: `config/auth.php`

- Guard utama: `app` (menggunakan `AppUser` model)
- Default guard diubah dari `web` ke `app`
- Provider: `app_users` → model `App\Models\AppUser`

### Models
- `App\Models\AppUser` — model utama auth, implements `Authenticatable`
  - Role: `superuser` | `operator`
  - Soft deletes, activity log
  - Password auto-hash via mutator
- `App\Models\AppUserPreference` — key-value preferences per user

### Middleware
| Alias | Class | Fungsi |
|---|---|---|
| `superuser` | `EnsureSuperUser` | Hanya superuser yang bisa akses |
| `operator` | `EnsureOperator` | Semua user login bisa akses (superuser + operator) |
| `active` | `EnsureUserIsActive` | Cek user aktif, logout jika dinonaktifkan |

### Default Superuser
| Field | Value |
|---|---|
| Name | Super Administrator |
| Email | admin@radius.local |
| Password | RadiusAdmin@2026 |
| Role | superuser |

> ⚠️ **Ganti password setelah login pertama!**

Untuk membuat ulang superuser:
```bash
php artisan db:seed --class=SuperUserSeeder
```

---

## Frontend Build

| Tool | Versi | Status |
|---|---|---|
| Node.js NPM | sistem | ✅ |
| Tailwind CSS | 3.x | ✅ |
| PostCSS + Autoprefixer | latest | ✅ |
| Vite | 6.x | ✅ |

Output build:
```
public/build/manifest.json
public/build/assets/app-*.css   (~19KB)
public/build/assets/app-*.js    (~42KB)
```

Command:
```bash
npm run dev    # development dengan HMR
npm run build  # production build
```

---

## Troubleshooting yang Ditemukan

### 1. `artisan make:migration` menghasilkan file stub kosong
**Masalah:** File migration dari `php artisan make:migration` hanya berisi stub kosong `$table->id(); $table->timestamps();`

**Solusi:** Isi konten migration via GitHub langsung, lalu:
```bash
git fetch origin
git checkout origin/main -- database/migrations/
php artisan migrate:fresh
```

### 2. `spatie/laravel-activitylog` v5 tidak kompatibel PHP 8.2
**Solusi:** Gunakan versi 4.x:
```bash
composer require spatie/laravel-activitylog:"^4.0"
```

### 3. `migrate:fresh` menghapus tabel FreeRADIUS
**Masalah:** `migrate:fresh` drop SEMUA tabel termasuk tabel FreeRADIUS standar.

**Solusi:** Setelah `migrate:fresh`, jalankan ulang schema FreeRADIUS:
```bash
PGPASSWORD='RadiusManager@2026' psql -h 127.0.0.1 -p 5433 -U radius_user -d radius_db \
  -f /etc/freeradius/3.0/mods-config/sql/main/postgresql/schema.sql
php artisan migrate --step
```

### 4. Git conflict — file lokal vs remote
**Masalah:** File lokal (hasil artisan make) belum di-commit, repo remote sudah punya versi berbeda.

**Solusi:**
```bash
# Hapus file konflik
rm <conflicting-files>
git pull origin main
```
Atau force push jika local lebih update:
```bash
git push origin main --force
```

---

## Step Log

| Step | Deskripsi | Status |
|---|---|---|
| 01 | Install PHP 8.2 + extensions | ✅ |
| 02 | Install Composer 2.x | ✅ |
| 03 | Setup PostgreSQL via Docker (port 5433) | ✅ |
| 04 | Setup database `radius_db` + user `radius_user` | ✅ |
| 05 | Install FreeRADIUS 3.2.1 | ✅ |
| 06 | Konfigurasi FreeRADIUS SQL module | ✅ |
| 07 | Test FreeRADIUS → `Access-Accept` | ✅ |
| 08 | Install Laravel 11 | ✅ |
| 09 | Konfigurasi `.env` + koneksi PostgreSQL | ✅ |
| 10 | Install packages (dompdf, activitylog, routeros) | ✅ |
| 11 | Publish migrations + config packages | ✅ |
| 12 | Buat semua migration custom (11 tabel) | ✅ |
| 13 | Jalankan `migrate:fresh` — 29 tabel aktif | ✅ |
| 14 | Setup Auth guard `app` + RBAC middleware | ✅ |
| 15 | SuperUserSeeder — default superuser terbuat | ✅ |
| 16 | Install Tailwind CSS v3 + Vite build | ✅ |
| 17 | **Next: Build halaman Login** | 🔄 |
