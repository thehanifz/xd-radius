# 02 — FreeRADIUS + PostgreSQL Setup

## Instalasi dan konfigurasi otomatis

Di server baru, proses berikut sudah menjadi bagian dari `sudo bash setup.sh` (lihat [`01-server-setup.md`](./01-server-setup.md)). Script memasang FreeRADIUS, memilih PostgreSQL native/Docker yang tersedia atau membuat container PostgreSQL baru, mengimpor schema sebelum migration Laravel, menjalankan `freeradius:setup`, memvalidasi konfigurasi, dan memeriksa health check.

Untuk diagnosis setelah instalasi, gunakan:

```bash
sudo ./setup.sh check
sudo freeradius -XC
sudo systemctl status freeradius
docker ps --filter name=xd-radius-postgres
php artisan freeradius:health-check
php artisan app:configure-jakarta-timezone   # pastikan timezone DB tetap Asia/Jakarta
```

Jika pipeline gagal, baca pesan terminal dan log servicenya:

```bash
journalctl -u freeradius -n 100 --no-pager
tail -n 100 storage/logs/laravel.log
```

Konfigurasi FreeRADIUS memakai backup dan rollback saat validasi atau health check gagal. Bootstrap fresh menolak `.env` yang sudah dikonfigurasi agar tidak mengganti database instalasi lama. Untuk debug health check lebih rinci:

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

Bootstrap `sudo bash setup.sh` menyetel timezone host, Laravel, koneksi PostgreSQL, serta default timezone database RADIUS ke `Asia/Jakarta` (UTC+7). Setup akan memuat ulang FreeRADIUS agar koneksi baru memakai timezone tersebut. Kolom `radacct` bertipe `timestamp without time zone`, jadi nilai lama tidak menyimpan offset UTC dan tidak digeser oleh setup.

Aktifkan RADIUS Interim-Update agar byte counter sesi aktif terus diperbarui (rekomendasi: setiap 5 menit):

```
/ppp aaa set accounting=yes interim-update=5m
/ip hotspot profile set [find] radius-accounting=yes radius-interim-update=5m
```

> Perintah PPP mengatur interval di `/ppp aaa`, bukan di `/ppp profile`. RouterOS menyimpan byte dari sudut pandang pelanggan: `Acct-Output-Octets` adalah upload pelanggan dan `Acct-Input-Octets` adalah download. Nilai `radacct` untuk sesi aktif akan tetap 0 sampai router mengirim Accounting Interim-Update; setelah interval diaktifkan, tunggu satu interval atau sambungkan ulang sesi untuk melihat nilainya.
>
> Interim-Update juga diperlukan agar rekonsiliasi sesi stale di Laravel Scheduler (`ReconcileStaleSessionsJob`) bekerja akurat.

Tombol **Tes API** di halaman detail router menerapkan pengaturan accounting yang sama melalui RouterOS API: accounting PPP diaktifkan, dan interval accounting 5 menit diterapkan ke semua profil HotSpot. Akun API router harus memiliki izin menulis konfigurasi. Jika penerapan gagal, UI menampilkan kegagalan tes agar perubahan yang belum berhasil tidak dianggap selesai.

## Troubleshooting (Referensi — Jika Pipeline Gagal)

Tabel ini dipertahankan sebagai referensi untuk memahami *root cause* jika bootstrap melaporkan gagal di langkah FreeRADIUS:

| Error | Penyebab | Solusi |
|---|---|---|
| `server does not support SSL, but SSL was required` | `RADIUS_DB_SSLMODE` di `.env` diset `verify-full`/`require` padahal PostgreSQL lokal tidak pakai SSL | Set `RADIUS_DB_SSLMODE=disable` atau `prefer` di `.env`, jalankan `php artisan config:clear`, lalu `php artisan freeradius:setup` |
| `fe_sendauth: no password supplied` (saat debug manual via `psql`) | psql CLI tidak mendapat password | Gunakan `PGPASSWORD='...' psql ...` saat debug manual |
| `Access-Reject` saat test autentikasi | User tidak ada di `radcheck`, atau voucher/member belum tersinkron | Pastikan pembuatan voucher/member lewat aplikasi (bukan insert manual), cek `MemberService`/`VoucherService` |
| FreeRADIUS hang di debug mode manual | Normal behavior (daemon) | Gunakan `timeout 10 freeradius -X` untuk debug manual, bukan indikasi bug |
| `Privileged helper verification failed` saat bootstrap | `www-data` tidak bisa `sudo -n` ke helper | Cek `sudo -n -u www-data sudo -n /usr/local/sbin/xd-radius-freeradius is-active freeradius` manual, pastikan tidak ada `requiretty` di sudoers global |

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

Setelah `sudo bash setup.sh` sukses dan health check `Healthy/Ready`, lanjut ke [`04-cloudflare-tunnel.md`](./04-cloudflare-tunnel.md) untuk expose aplikasi, atau langsung ke [`05-operational-guide.md`](./05-operational-guide.md) untuk mulai konfigurasi router/paket/voucher dari UI.
