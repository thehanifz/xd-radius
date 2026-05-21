# XD-Radius — RadiusManager

Web application berbasis Laravel 11 untuk manajemen FreeRADIUS, voucher hotspot, member PPP, billing, monitoring sesi aktif, dan konfigurasi multi-router MikroTik.

## Tech Stack

| Layer | Teknologi |
|---|---|
| Framework | Laravel 11 |
| Language | PHP 8.2+ |
| Database | PostgreSQL 18 (Docker) |
| RADIUS | FreeRADIUS 3.2.x |
| Frontend | Blade + Tailwind CSS + Alpine.js |
| PDF | barryvdh/laravel-dompdf |
| Audit Log | spatie/laravel-activitylog |
| Tunnel | Cloudflare Tunnel |

## Status Setup

- [x] PHP 8.2+ + extensions
- [x] Composer
- [x] PostgreSQL (Docker port 5433)
- [x] FreeRADIUS 3.2.1 + SQL module
- [x] FreeRADIUS ↔ PostgreSQL connection
- [ ] Laravel 11 installation
- [ ] Database migrations
- [ ] Cloudflare Tunnel configuration

## Dokumentasi

- [Setup Server](docs/01-server-setup.md)
- [FreeRADIUS + PostgreSQL](docs/02-freeradius-setup.md)
- [Laravel Installation](docs/03-laravel-setup.md) _(coming soon)_
- [Cloudflare Tunnel](docs/04-cloudflare-tunnel.md) _(coming soon)_

## Arsitektur

```
[MikroTik NAS] ──RADIUS Auth──► [FreeRADIUS 3.2.1] ──SQL──► [PostgreSQL 18 Docker :5433]
                                                                        ▲
                                                               [Laravel App]
                                                            (baca/tulis langsung)

[Laravel App] ──RouterOS API (test connection, Tahap 1)──► [MikroTik NAS]
```

## Lisensi

Private project.
