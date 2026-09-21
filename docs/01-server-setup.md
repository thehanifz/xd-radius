# 01 — Server Setup

## Environment

| Komponen | Detail |
|---|---|
| OS | Debian GNU/Linux 12 (Bookworm) / Armbian jammy (teruji) |
| Platform | LXC Container di Proxmox, atau server ARM/x86 sejenis |
| PHP | 8.2.x |
| PostgreSQL | 14+ (native atau Docker container) |
| Composer | 2.x |

## Cara Setup (Direkomendasikan): `setup.sh`

Sejak revisi ini, seluruh pengecekan dan instalasi prasyarat server dikonsolidasikan ke satu script: **`setup.sh`** di root repository. Jangan lagi menginstall paket satu-satu secara manual — gunakan mode `check` dan `install` di bawah ini.

### 1. Cek Prasyarat (tanpa mengubah apa pun)

```bash
cd /path/ke/xd-radius
chmod +x setup.sh
./setup.sh check
```

Script ini memvalidasi: OS, PHP 8.2+ beserta ekstensi wajib (`pdo`, `pdo_pgsql`, `mbstring`, `openssl`, `tokenizer`, `xml`, `ctype`, `json`, `bcmath`, `fileinfo`, `curl`, `zip`, `intl`), Composer, Node.js/npm, PostgreSQL client + server (native atau Docker), FreeRADIUS binary + versi + validasi config, file aplikasi (`artisan`, `composer.lock`), dan privilege `sudo` untuk user `www-data`.

Contoh output yang sehat:

```
Summary
-------
  OK   : 18
  WARN : 0
  FAIL : 0

  [OK] Environment passed prerequisite validation.
```

Kalau ada `[WARN] freeradius.service exists but is not active`, jalankan `sudo systemctl start freeradius` lalu `./setup.sh check` ulang sebelum lanjut — jangan lanjut ke tahap berikutnya selama masih ada `FAIL`.

### 2. Install Paket yang Belum Ada (jika ada FAIL)

```bash
sudo ./setup.sh install
```

Mode ini menjalankan `check` lagi, menampilkan daftar paket apt yang akan dipasang, lalu meminta konfirmasi `[y/N]` sebelum benar-benar menjalankan `apt-get install`. Gunakan `--yes` untuk skip konfirmasi (misalnya di script CI/provisioning otomatis):

```bash
sudo ./setup.sh install --yes
```

Instalasi otomatis hanya didukung di Debian/Ubuntu (`apt-get`). Untuk distribusi lain, `setup.sh` akan menampilkan `[FAIL] Automatic installation is currently supported only on Debian/Ubuntu.` — instal manual sesuai daftar paket yang ditampilkan.

## PostgreSQL — Native atau Docker

`setup.sh check` mendeteksi PostgreSQL baik yang berjalan native (systemd service) maupun sebagai container Docker. Kalau Anda punya lebih dari satu container PostgreSQL di server yang sama (misalnya dipakai aplikasi lain seperti n8n), **pastikan `.env` Anda mengarah ke container/port yang benar-benar diperuntukkan untuk xd-radius**, jangan sampai tertukar.

### Membuat Role dan Database (Manual, di Luar `setup.sh`)

`setup.sh` **tidak** membuat PostgreSQL role/user — ini tetap langkah manual karena menyangkut kredensial admin database Anda. Yang otomatis dibuat oleh aplikasi (lewat `RadiusDatabaseBootstrapper` saat `setup.sh setup` dijalankan) hanyalah **database** RADIUS, dan itu pun hanya jika role yang dipakai punya privilege `CREATEDB`.

Jika Anda memakai PostgreSQL via Docker:

```bash
docker exec -it <nama-container-postgres> psql -U <admin_user> -d <database_admin>
```

```sql
CREATE USER radius_user WITH PASSWORD 'ganti-dengan-password-kuat' CREATEDB;
-- CREATEDB opsional: hanya perlu jika Anda ingin xd-radius membuat database secara otomatis.
-- Jika tidak diberi CREATEDB, buat database secara manual:
CREATE DATABASE radius_db OWNER radius_user;
GRANT ALL PRIVILEGES ON DATABASE radius_db TO radius_user;
\q
```

Verifikasi koneksi:

```bash
PGPASSWORD='ganti-dengan-password-kuat' psql -h 127.0.0.1 -p <port> -U radius_user -d radius_db -c "SELECT version();"
```

> ⚠️ **Catatan Keamanan:** Jangan commit password asli ke repository. Isi kredensial ini ke `.env` (`RADIUS_DB_USERNAME`, `RADIUS_DB_PASSWORD`, `RADIUS_DB_HOST`, `RADIUS_DB_PORT`, `RADIUS_DB_DATABASE`) sebelum menjalankan `setup.sh setup`.

## Langkah Berikutnya

Setelah `./setup.sh check` bersih dan role/database PostgreSQL siap, lanjut ke [`02-freeradius-setup.md`](./02-freeradius-setup.md) untuk menjalankan `sudo ./setup.sh setup`.
