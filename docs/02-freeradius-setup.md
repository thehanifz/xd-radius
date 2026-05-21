# 02 — FreeRADIUS + PostgreSQL Setup

## 1. Install FreeRADIUS

```bash
apt install -y freeradius freeradius-postgresql
```

Verifikasi:

```bash
freeradius -v
# FreeRADIUS Version 3.2.x
```

## 2. Import Schema FreeRADIUS ke PostgreSQL

```bash
PGPASSWORD='your_password_here' psql -h 127.0.0.1 -p 5433 -U radius_user -d radius_db \
  -f /etc/freeradius/3.0/mods-config/sql/main/postgresql/schema.sql
```

Verifikasi tabel:

```bash
PGPASSWORD='your_password_here' psql -h 127.0.0.1 -p 5433 -U radius_user -d radius_db -c "\dt"
```

Output yang diharapkan:

```
 Schema |     Name      | Type  |    Owner    
--------+---------------+-------+-------------
 public | nas           | table | radius_user
 public | nasreload     | table | radius_user
 public | radacct       | table | radius_user
 public | radcheck      | table | radius_user
 public | radgroupcheck | table | radius_user
 public | radgroupreply | table | radius_user
 public | radpostauth   | table | radius_user
 public | radreply      | table | radius_user
 public | radusergroup  | table | radius_user
```

## 3. Konfigurasi SQL Module

### Enable SQL Module

```bash
ln -s /etc/freeradius/3.0/mods-available/sql /etc/freeradius/3.0/mods-enabled/sql
```

### Edit `/etc/freeradius/3.0/mods-available/sql`

Perubahan yang diperlukan:

```bash
# 1. Ubah dialect
dialect = "postgresql"

# 2. Ubah driver
driver = "rlm_sql_postgresql"

# 3. Ubah radius_db — gunakan sslmode=disable karena PostgreSQL Docker tidak pakai SSL
radius_db = "host=127.0.0.1 port=5433 dbname=radius_db user=radius_user password=your_password_here sslmode=disable"

# 4. Enable read_clients
read_clients = yes
```

> ⚠️ **Penting:** Gunakan `sslmode=disable` karena PostgreSQL yang berjalan di Docker container lokal tidak dikonfigurasi dengan SSL. Jika menggunakan `sslmode=verify-full` (default), FreeRADIUS akan gagal terhubung.

### Enable SQL di Default Site

Edit `/etc/freeradius/3.0/sites-available/default`, pastikan `sql` aktif (tidak di-comment) di bagian:
- `authorize { ... }`
- `accounting { ... }`
- `session { ... }`

## 4. Test Konfigurasi

```bash
# Stop service
service freeradius stop

# Test debug mode
timeout 10 freeradius -X 2>&1 | grep -E "Ready|Listening|failed|Error|connect"
```

Output sukses yang diharapkan:

```
rlm_sql (sql): Attempting to connect to database "host=127.0.0.1 port=5433 ... sslmode=disable"
rlm_sql_postgresql: Connecting using parameters: host=127.0.0.1 port=5433 ... sslmode=disable
Listening on auth address * port 1812 bound to server default
Listening on acct address * port 1813 bound to server default
Ready to process requests
```

## 5. Start Service

```bash
service freeradius start
service freeradius status
```

## 6. Test Autentikasi

```bash
# Insert test user
PGPASSWORD='your_password_here' psql -h 127.0.0.1 -p 5433 -U radius_user -d radius_db -c "
INSERT INTO radcheck (username, attribute, op, value)
VALUES ('testuser', 'Cleartext-Password', ':=', 'testpass123');
"

# Test auth
radtest testuser testpass123 127.0.0.1 0 testing123

# Output sukses: Received Access-Accept

# Cleanup test user
PGPASSWORD='your_password_here' psql -h 127.0.0.1 -p 5433 -U radius_user -d radius_db -c "
DELETE FROM radcheck WHERE username = 'testuser';
"
```

## 7. Konfigurasi MikroTik sebagai NAS

Di MikroTik, konfigurasi RADIUS client mengarah ke server FreeRADIUS:

```
/radius add \
  address=<IP_SERVER_FREERADIUS> \
  secret=testing123 \
  service=hotspot,ppp \
  authentication-port=1812 \
  accounting-port=1813
```

Dan aktifkan Interim-Update accounting (rekomendasi: setiap 1–5 menit):

```
/ip hotspot profile set [find] interim-update=5m
/ppp profile set [find] interim-update=5m
```

> Interim-Update diperlukan agar rekonsiliasi sesi stale di Laravel Scheduler bekerja akurat.

## Troubleshooting

| Error | Penyebab | Solusi |
|---|---|---|
| `server does not support SSL, but SSL was required` | `sslmode=verify-full` di konfigurasi | Ganti ke `sslmode=disable` |
| `fe_sendauth: no password supplied` | psql CLI tidak mendapat password | Gunakan `PGPASSWORD='...' psql ...` |
| `Access-Reject` | User tidak ada di `radcheck` | Insert user ke tabel `radcheck` terlebih dahulu |
| FreeRADIUS hang di debug mode | Normal behavior (daemon) | Gunakan `timeout 10 freeradius -X` |
