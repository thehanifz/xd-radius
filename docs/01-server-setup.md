# 01 — Server Setup

## Environment

| Komponen | Detail |
|---|---|
| OS | Debian GNU/Linux 12 (Bookworm) |
| Platform | LXC Container di Proxmox |
| PHP | 8.2.x |
| PostgreSQL | 18.x (Docker container `postgres-global`, port 5433) |
| Composer | 2.x |

## 1. Install PHP Extensions

PHP 8.2 sudah terinstall. Install ekstensi yang dibutuhkan Laravel + PostgreSQL:

```bash
apt update && apt install -y \
  php8.2-pdo \
  php8.2-pgsql \
  php8.2-mbstring \
  php8.2-xml \
  php8.2-curl \
  php8.2-zip \
  php8.2-bcmath \
  php8.2-intl \
  php8.2-cli \
  unzip \
  git \
  curl \
  postgresql-client
```

Verifikasi:

```bash
php -m | grep -E "pdo|pgsql|mbstring|xml|curl|zip|bcmath"
```

Output yang diharapkan:
```
bcmath
curl
libxml
mbstring
pdo_pgsql
pgsql
xml
xmlreader
xmlwriter
zip
```

## 2. Install Composer

```bash
curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer
```

Verifikasi:

```bash
composer --version
```

## 3. PostgreSQL Docker

PostgreSQL sudah berjalan sebagai Docker container:

```bash
# Cek container
docker ps | grep postgres-global
```

```
CONTAINER: postgres-global
PORT: 0.0.0.0:5433->5432/tcp
IMAGE: postgres:latest (v18)
```

### Buat Database untuk RadiusManager

```bash
docker exec -it postgres-global psql -U admin_global -d database_utama
```

```sql
CREATE USER radius_user WITH PASSWORD 'your_password_here';
CREATE DATABASE radius_db OWNER radius_user;
GRANT ALL PRIVILEGES ON DATABASE radius_db TO radius_user;
\q
```

Verifikasi:

```bash
PGPASSWORD='your_password_here' psql -h 127.0.0.1 -p 5433 -U radius_user -d radius_db -c "SELECT version();"
```

> ⚠️ **Catatan Keamanan:** Ganti `your_password_here` dengan password yang kuat. Jangan commit password asli ke repository.
