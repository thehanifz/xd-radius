# 02 — FreeRADIUS + PostgreSQL Setup

## Cara Setup (Direkomendasikan): `setup.sh setup`

Sejak revisi ini, seluruh proses install schema FreeRADIUS, konfigurasi SQL module, symlink, validasi, dan restart service **tidak lagi dilakukan manual**. Semuanya dijalankan otomatis lewat:

```bash
sudo ./setup.sh setup
```

Pastikan sebelum menjalankan ini:

1. `./setup.sh check` sudah lolos tanpa `FAIL` (lihat [`01-server-setup.md`](./01-server-setup.md)).
2. `.env` sudah berisi variabel `RADIUS_DB_*` yang benar (host, port, database, username, password sesuai role PostgreSQL yang sudah dibuat).
3. FreeRADIUS package sudah terinstall (`apt install freeradius freeradius-postgresql` — atau lewat `sudo ./setup.sh install` jika terdeteksi `FAIL` pada tahap check).

### Apa yang Terjadi di Balik `setup.sh setup`

Mode `setup` menjalankan orkestrasi 7 langkah:

1. **Preflight** — jalankan ulang seluruh pengecekan `check`.
2. **Privileged helper** — memasang `scripts/xd-radius-freeradius-privileged` ke `/usr/local/sbin/xd-radius-freeradius`, membuat direktori staging `/var/lib/xd-radius-freeradius/staging` (owner `www-data`), dan menulis entri sudoers scoped di `/etc/sudoers.d/xd-radius-freeradius`. Helper ini divalidasi langsung (`visudo -cf` + test invoke sebagai `www-data`) sebelum lanjut.
3. **Migrasi database Laravel** — `php artisan migrate --force`, lalu verifikasi tidak ada migration yang masih `Pending`.
4. **Cek konektivitas RADIUS DB** — memastikan koneksi Laravel bernama `radius` (didefinisikan di `config/database.php`, terpisah dari koneksi aplikasi utama) benar-benar bisa diakses.
5. **`FreeRadiusSetupService::run()`** — service Laravel ini yang menggantikan seluruh langkah manual lama:
   - Deteksi environment FreeRADIUS (binary, versi, config dir).
   - `RadiusDatabaseBootstrapper::ensureDatabase()` — cek/buat database RADIUS (jika privilege `CREATEDB` tersedia).
   - Bootstrap schema vendor PostgreSQL dari `{config_dir}/mods-config/sql/main/postgresql/schema.sql` (persis file yang dulu di-import manual via `psql -f`), plus kolom ekstensi `radacct.is_stale` dan `radacct.stale_detected_at`.
   - `FreeRadiusConfigurationPipeline::run('SETUP')` — backup config → generate `mods-enabled/sql_app`, `mods-available/sql` (dialect, driver, `radius_db`, `read_clients=yes`), buat symlink `mods-enabled/sql` → validasi (`freeradius -XC`) → sinkronisasi tabel `nas` → reload/restart FreeRADIUS → health check 6 kriteria.
6. **Validasi konfigurasi** — `freeradius -XC` dijalankan sekali lagi sebagai konfirmasi akhir.
7. **Verifikasi service & health check aplikasi** — `systemctl is-active freeradius` dan `FreeRadiusHealthChecker::check()` (service aktif, config valid, database reachable, SQL module bisa query, tabel wajib ada, listener port 1812/1813 terbuka).

Setiap langkah kritikal (backup, generate, validate, apply, NAS sync, health check) tercatat di **audit trail** (`activity_log` dan tabel `radius_management_operations`) — bisa dilihat di halaman `/settings/freeradius` pada aplikasi.

### Jika `setup.sh setup` Gagal

Baca pesan error yang ditampilkan — script akan `die` dengan pesan spesifik di langkah mana ia berhenti (contoh: `RADIUS database connection failed`, `FreeRADIUS setup service failed`, `FreeRADIUS service is not healthy`). Karena pipeline melakukan backup sebelum apply dan rollback otomatis saat validasi/health check gagal, **konfigurasi FreeRADIUS yang sudah berjalan sebelumnya tidak akan rusak** — kegagalan berarti perubahan baru belum diterapkan, bukan sistem lama ikut rusak.

Untuk debug lebih detail, jalankan manual salah satu langkah lewat `artisan tinker`, misalnya:

```bash
php artisan tinker --execute='dd(app(\App\Services\Radius\FreeRadiusHealthChecker::class)->check());'
```

## Konfigurasi MikroTik sebagai NAS

Bagian ini **tidak berubah** — tetap dilakukan manual di sisi MikroTik, karena `setup.sh` hanya mengatur sisi server FreeRADIUS/Laravel, bukan router.

```
/radius add \
  address=<IP_SERVER_FREERADIUS> \
  secret=<RADIUS_SECRET_ROUTER_INI> \
  service=hotspot,ppp \
  authentication-port=1812 \
  accounting-port=1813
```

`secret` di atas harus **sama** dengan `radius_secret` yang diisi pada halaman **Router / NAS** di aplikasi — nilai ini yang disinkronkan otomatis ke tabel `nas` oleh `FreeRadiusNasManager` setiap kali router dibuat/diubah/dihapus, tanpa perlu insert manual ke `nas` lagi.

Aktifkan Interim-Update accounting (rekomendasi: setiap 1–5 menit):

```
/ip hotspot profile set [find] interim-update=5m
/ppp profile set [find] interim-update=5m
```

> Interim-Update diperlukan agar rekonsiliasi sesi stale di Laravel Scheduler (`ReconcileStaleSessionsJob`) bekerja akurat.

## Troubleshooting (Referensi — Jika Pipeline Gagal)

Tabel ini dipertahankan sebagai referensi untuk memahami *root cause* jika `setup.sh setup` melaporkan gagal di langkah FreeRADIUS:

| Error | Penyebab | Solusi |
|---|---|---|
| `server does not support SSL, but SSL was required` | `RADIUS_DB_SSLMODE` di `.env` diset `verify-full`/`require` padahal PostgreSQL lokal tidak pakai SSL | Set `RADIUS_DB_SSLMODE=disable` atau `prefer` di `.env`, lalu jalankan ulang `sudo ./setup.sh setup` |
| `fe_sendauth: no password supplied` (saat debug manual via `psql`) | psql CLI tidak mendapat password | Gunakan `PGPASSWORD='...' psql ...` saat debug manual |
| `Access-Reject` saat test autentikasi | User tidak ada di `radcheck`, atau voucher/member belum tersinkron | Pastikan pembuatan voucher/member lewat aplikasi (bukan insert manual), cek `MemberService`/`VoucherService` |
| FreeRADIUS hang di debug mode manual | Normal behavior (daemon) | Gunakan `timeout 10 freeradius -X` untuk debug manual, bukan indikasi bug |
| `Privileged helper verification failed` saat `setup.sh setup` | `www-data` tidak bisa `sudo -n` ke helper | Cek `sudo -n -u www-data sudo -n /usr/local/sbin/xd-radius-freeradius is-active freeradius` manual, pastikan tidak ada `requiretty` di sudoers global |

### Test Autentikasi Manual (Opsional, untuk Debug)

```bash
# Insert test user langsung ke radcheck (HANYA untuk debug, jangan untuk data produksi)
PGPASSWORD='...' psql -h 127.0.0.1 -p <port> -U radius_user -d radius_db -c "
INSERT INTO radcheck (username, attribute, op, value)
VALUES ('testuser', 'Cleartext-Password', ':=', 'testpass123');
"

radtest testuser testpass123 127.0.0.1 0 <radius_secret_dari_router>
# Output sukses: Received Access-Accept

# Cleanup
PGPASSWORD='...' psql -h 127.0.0.1 -p <port> -U radius_user -d radius_db -c "
DELETE FROM radcheck WHERE username = 'testuser';
"
```

## Voucher Validity & Dynamic Session-Timeout

Voucher validity is anchored to the first successful login. The Laravel application stores `first_login_at` and `expired_at`. For strict enforcement, the FreeRADIUS `authorize` section should calculate the remaining seconds from PostgreSQL and set `Session-Timeout` on every login.

Conceptual SQL used by the RADIUS authorize logic:

```sql
SELECT GREATEST(EXTRACT(EPOCH FROM (expired_at - NOW()))::integer, 0)
FROM vouchers
WHERE username = '%{SQL-User-Name}'
  AND status = 'active'
  AND expired_at IS NOT NULL;
```

The resulting value should be assigned to `reply:Session-Timeout` only when it is greater than zero. If the value is zero, authentication must be rejected. Do not store a fixed `Session-Timeout` in `radreply`: it becomes stale after logout/re-login. The existing `radacct` accounting remains the source for reporting and a reconciliation fallback.

For exact first-login activation, place the activation UPDATE in the FreeRADIUS `post-auth` section (after successful authentication), not in `authorize`; otherwise a failed password attempt could consume the voucher. The application scheduler remains a fallback for sessions that were not captured by the normal accounting flow.

### Exact First-Login Activation in FreeRADIUS

Untuk menghindari delay scheduler, voucher dapat diaktifkan tepat setelah autentikasi berhasil. Jangan melakukan UPDATE ini di `authorize`, karena password yang salah dapat mengaktifkan voucher.

Di PostgreSQL, konsep query `post-auth`:

```sql
UPDATE vouchers
SET first_login_at = COALESCE(first_login_at, NOW()),
    activated_at   = COALESCE(activated_at, NOW()),
    expired_at     = COALESCE(
        expired_at,
        NOW() + CASE
            WHEN (SELECT duration_unit FROM plans WHERE plans.id = vouchers.plan_id) = 'minutes'
                THEN make_interval(mins => (SELECT duration_value FROM plans WHERE plans.id = vouchers.plan_id))
            WHEN (SELECT duration_unit FROM plans WHERE plans.id = vouchers.plan_id) = 'hours'
                THEN make_interval(hours => (SELECT duration_value FROM plans WHERE plans.id = vouchers.plan_id))
            ELSE make_interval(days => (SELECT duration_value FROM plans WHERE plans.id = vouchers.plan_id))
        END
    )
WHERE username = '%{SQL-User-Name}'
  AND first_login_at IS NULL
  AND status = 'active';
```

Untuk Access-Accept berikutnya, gunakan query `authorize` untuk menghitung sisa waktu dari `expired_at` dan set `reply:Session-Timeout`. Dengan cara ini nilai timeout selalu mengikuti expiry aktual dan tidak tersangkut pada durasi awal voucher.

Contoh query:

```sql
SELECT GREATEST(EXTRACT(EPOCH FROM (expired_at - NOW()))::integer, 0)
FROM vouchers
WHERE username = '%{SQL-User-Name}'
  AND status = 'active'
  AND first_login_at IS NOT NULL
  AND expired_at IS NOT NULL;
```

Jika hasil `0`, reject authentication. Jika hasil positif, assign ke `reply:Session-Timeout`.

> Implementasi query `post-auth` harus menggunakan modul SQL FreeRADIUS yang terhubung ke database aplikasi. Uji dulu dengan `freeradius -X` pada staging.

## MikroTik QoS

`Mikrotik-Rate-Limit` is generated centrally by Laravel from the plan QoS fields. RouterOS documents the format as:

`rx/tx burst-rx/burst-tx threshold-rx/threshold-tx burst-time-rx/burst-time-tx priority rx-min/tx-min`

In RouterOS, **rx = client upload** and **tx = client download**. The application therefore writes upload first and download second. `Limit At` is mapped to the final minimum-rate pair. Priority 1 is highest and 8 is lowest. RouterOS also requires `limit-at` not to exceed `max-limit`; burst threshold should be between `limit-at` and `max-limit` for the intended burst behavior.

`Mikrotik-Total-Limit` is used when `data_quota_mb` is set; the value is sent in bytes.

`qos_queue_type` is currently stored as profile metadata. Standard HotSpot `Mikrotik-Rate-Limit` does not carry a queue-type field, so the application does **not** pretend to apply it through RADIUS. Queue-type control can be handled later through a dedicated RouterOS API/queue strategy if required. This avoids silently writing an attribute that RouterOS will ignore.

Recommended test profile:

- Max: 10M / 5M
- Limit At: 2M / 1M
- Burst Limit: 20M / 10M
- Burst Threshold: 5M / 2M
- Burst Time: 10s / 10s
- Priority: 8

## Langkah Berikutnya

Setelah `sudo ./setup.sh setup` sukses dan health check `Healthy/Ready`, lanjut ke [`04-cloudflare-tunnel.md`](./04-cloudflare-tunnel.md) untuk expose aplikasi, atau langsung ke [`05-operational-guide.md`](./05-operational-guide.md) untuk mulai konfigurasi router/paket/voucher dari UI.
