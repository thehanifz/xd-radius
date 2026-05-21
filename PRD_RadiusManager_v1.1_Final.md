# Product Requirements Document
# RadiusManager — Web App FreeRADIUS Management

**Versi:** 1.1 Final  
**Tanggal:** 21 Mei 2026  
**Status:** Final — Siap Development  
**Platform:** Web App (Desktop & Mobile Responsive)  
**Framework:** Laravel 11 + PostgreSQL  

---

## 1. Overview & Executive Summary

RadiusManager adalah web application berbasis Laravel yang berfungsi sebagai panel administrasi terpusat untuk mengelola FreeRADIUS, voucher hotspot, member PPP, billing, monitoring sesi aktif, laporan bulanan, dan konfigurasi multi-router MikroTik dalam satu jaringan lokal. Aplikasi diakses melalui tunnel Cloudflare yang diproksikan ke subdomain khusus, dapat dioperasikan dari desktop maupun smartphone.

Database tunggal PostgreSQL digunakan sebagai backend SQL FreeRADIUS sekaligus data store seluruh aplikasi — tidak ada MySQL. Arsitektur utama menempatkan MikroTik sebagai NAS yang mengambil data autentikasi dari FreeRADIUS, sehingga Laravel cukup menulis ke PostgreSQL tanpa selalu perlu koneksi langsung ke router. Aksi langsung ke MikroTik melalui API (disconnect paksa, sinkronisasi) ditunda ke **Tahap 2** setelah core sistem stabil.

---

## 2. Problem Statement

Pengelolaan user voucher, member PPP, status layanan, dan konfigurasi banyak router MikroTik selama ini dilakukan manual melalui Winbox, CLI, atau tool terpisah seperti Mikhmon. Tidak ada satu panel terpusat yang mencakup autentikasi (FreeRADIUS), manajemen user, profil layanan, billing, laporan bulanan, dan monitoring sesi aktif.

Risiko dari kondisi saat ini:
- Duplikasi pekerjaan antara tool berbeda, tidak ada audit trail.
- Sulit melacak riwayat voucher per batch generate — tidak ada pembeda antar sesi generate.
- Tidak ada laporan bulanan terstruktur (pertama login, expired, harga, pendapatan).
- Tidak ada fondasi untuk billing otomatis dan payment gateway di masa depan.
- Sulitnya skala operasi saat jumlah user dan router bertambah.

---

## 3. Goals & Success Metrics (KPI/OKR)

### Tujuan Utama
- Memusatkan manajemen voucher, member, status layanan, billing, dan konfigurasi router dalam satu panel web yang responsif.
- Mengurangi ketergantungan operator pada Winbox/CLI untuk tugas harian.
- Menyediakan laporan bulanan yang actionable untuk keputusan bisnis.
- Menyiapkan fondasi arsitektur yang solid untuk billing otomatis dan payment gateway.

### KPI

| Metrik | Target |
|--------|--------|
| Generate batch voucher 100 pcs | < 15 detik |
| Ubah status isolir/aktif | < 5 detik |
| Waktu load halaman utama | < 2 detik |
| Kredensial router tersimpan terenkripsi | 100% |
| Tugas harian operator tanpa Winbox/CLI | ≥ 90% |
| Waktu setup super user pertama (onboarding) | < 10 menit |
| Laporan bulanan dapat digenerate & didownload | < 10 detik |
| UI fungsional di smartphone (375px+) | 100% fitur inti |

---

## 4. Target Users & Personas

### Persona 1 — Super User (Admin Utama)
Dibuat pertama kali saat proses setup/onboarding sistem. Super user memiliki akses penuh ke seluruh fitur termasuk manajemen operator, konfigurasi router, billing, laporan, integrasi sistem, dan pengaturan global. Pain point utamanya adalah konfigurasi tersebar, tidak ada panel audit, dan sulitnya melihat ringkasan bisnis bulanan secara cepat.

### Persona 2 — Operator
Dibuat dan dikelola oleh super user. Operator mengakses panel dari desktop atau smartphone untuk kelola voucher, kelola member, lihat user online, dan ubah status layanan. Operator tidak dapat mengakses konfigurasi router, laporan bisnis advance, atau pengaturan sistem sensitif. Pain point-nya adalah kebutuhan workflow cepat, minim risiko kesalahan, dan antarmuka yang nyaman di layar kecil saat bertugas di lapangan.

---

## 5. Scope

### In-Scope — Tahap 1 (v1 MVP)

**Sistem & Infrastruktur**
- Onboarding super user pertama saat setup.
- Login, autentikasi, RBAC dua role: Super User dan Operator.
- Manajemen akun operator oleh super user.
- Konfigurasi berjalan di belakang Cloudflare Tunnel.
- UI responsif — desktop (1280px+) dan smartphone (375px+).
- Audit log untuk semua aksi sensitif.

**User Management**
- CRUD profil/paket (speed, durasi, harga, simultaneous-use).
- Generate voucher satuan dan bulk dengan konfigurasi prefix, panjang digit, jenis karakter.
- Pengelompokan voucher per batch generate (batch_id, batch_code).
- CRUD member dengan username/password berbeda.
- Pencatatan `first_login_at`, `expired_at`, dan `price_snapshot` per user/voucher.
- Status layanan: aktif, isolir, expired, nonaktif.
- Isolir/aktif di level RADIUS (radcheck).
- Konfirmasi aksi sensitif dengan preferensi auto-confirm per akun.

**Monitoring & Laporan**
- Monitoring user online (dari tabel `radacct`).
- Rekonsiliasi sesi stale via Laravel Scheduler.
- Laporan bulanan: first login, expired, harga, profil, status, ringkasan billing.
- Export laporan bulanan ke PDF (download manual).
- Export invoice billing member ke PDF (download manual).

**Billing**
- Invoice member bulanan.
- Pencatatan pembayaran manual.
- Perpanjangan masa aktif (dihitung dari tanggal jatuh tempo lama).
- Status invoice: pending, paid, overdue, cancelled.
- Hook isolir manual/otomatis untuk tagihan overdue.
- Fondasi arsitektur payment gateway (fields siap: method, external_ref, gateway_status).

**Router MikroTik**
- CRUD konfigurasi router (nama, IP, port API, username, secret terenkripsi, status).
- Test connection ke MikroTik API.
- Data router dipakai sebagai referensi NAS; aksi operasional ke router ditunda ke Tahap 2.

**Print Voucher**
- Template mirip Mikhmon: A4 multi-per-halaman dan thermal 58/80mm.
- Filter cetak berdasarkan batch generate.
- Preview sebelum cetak via browser.

### Out-of-Scope — Ditunda ke Tahap 2
- Disconnect paksa user online via MikroTik API.
- Isolir via firewall rule / address-list MikroTik.
- Sinkronisasi status sesi realtime ke MikroTik.
- Notifikasi WhatsApp/email otomatis.
- Implementasi payment gateway live (Tripay, Midtrans, dll).
- Grafik analitik bandwidth mendalam.
- Multi-tenant antar-ISP berbeda.
- Mobile app native.
- Integrasi NMS pihak ketiga (Zabbix, The Dude).

---

## 6. User Stories & Use Cases

### Story 1 — Onboarding Super User
**As a** pemilik sistem, **I want to** membuat akun super user pertama saat setup awal, **so that** sistem siap dipakai tanpa perlu insert manual ke database.

**Acceptance Criteria:**
- Jika belum ada super user, sistem menampilkan halaman setup onboarding.
- Super user pertama dibuat dengan nama, email, dan password.
- Setelah setup selesai, halaman onboarding tidak dapat diakses lagi.
- Super user pertama tidak dapat dihapus siapapun.

**Kompleksitas:** Low — fondasi setup, tidak ada logika bisnis kompleks.

---

### Story 2 — Manajemen Operator
**As a** super user, **I want to** membuat, mengubah, dan menonaktifkan akun operator, **so that** akses staf dapat dikontrol sepenuhnya.

**Acceptance Criteria:**
- Super user dapat CRUD akun operator (nama, email, password, status aktif).
- Operator yang dinonaktifkan tidak bisa login; sesi aktif langsung dicabut.
- Riwayat pembuatan/perubahan akun tercatat di audit log.
- Super user tidak dapat mengubah role dirinya sendiri menjadi operator.

**Kompleksitas:** Low

---

### Story 3 — Manajemen Profil/Paket
**As a** super user, **I want to** membuat profil layanan dengan parameter lengkap, **so that** voucher dan member dapat ditetapkan ke profil yang sesuai kebutuhan.

**Acceptance Criteria:**
- Profil mencakup: nama, kecepatan upload/download (Mbps/Kbps), durasi (jam/hari/bulan), harga, jumlah sesi bersamaan (simultaneous-use).
- Voucher: simultaneous-use wajib = 1, dikunci sistem.
- Member: simultaneous-use dapat dikonfigurasi per akun member.
- Perubahan harga profil tidak mengubah `price_snapshot` pada voucher/member yang sudah dibuat.
- FreeRADIUS `radreply` diperbarui otomatis saat profil diubah (kecuali `price_snapshot`).
- Profil yang masih digunakan tidak bisa dihapus, hanya dinonaktifkan.

**Kompleksitas:** Medium

---

### Story 4 — Generate Voucher dengan Konfigurasi Format & Batch
**As a** operator, **I want to** mengatur format voucher dan melakukan generate satuan atau bulk, **so that** hasil generate terorganisir per sesi dan mudah dicetak ulang.

**Acceptance Criteria:**
- Form konfigurasi mencakup: prefix (opsional), panjang total karakter, jenis karakter (huruf besar, huruf kecil, angka, atau campuran), jumlah (untuk bulk).
- Preview format contoh ditampilkan realtime sebelum generate.
- Setiap sesi generate menghasilkan satu record `voucher_batches` dengan: batch_code unik, prefix, panjang, jenis karakter, jumlah, paket, waktu generate, dan operator yang generate.
- Setiap voucher yang dihasilkan memiliki `batch_id` foreign key ke batch tersebut.
- Sistem memastikan tidak ada duplikasi username di seluruh database (lintas voucher dan member).
- Username = password untuk semua voucher.
- Batch dapat difilter dan dipilih untuk cetak ulang kapan saja.
- Data batch tidak bisa dihapus jika ada voucher aktif di dalamnya.

**Kompleksitas:** Medium

---

### Story 5 — Kelola Member
**As a** operator, **I want to** membuat dan mengubah akun member, **so that** pelanggan berlangganan dapat dikelola dengan paket dan pengaturan yang tepat.

**Acceptance Criteria:**
- Username dan password member harus berbeda, validasi di sisi server.
- Member terhubung ke satu profil/paket.
- Member memiliki: tanggal aktif, tanggal jatuh tempo, harga saat dibuat (`price_snapshot`), simultaneous-use, dan status layanan.
- Simultaneous-use dapat diatur per member, tidak dibatasi ke 1.
- Sistem mencegah duplikasi username di seluruh sistem.
- Perubahan member dicatat di audit log.

**Kompleksitas:** Medium

---

### Story 6 — Pencatatan First Login & Expired
**As a** super user, **I want to** melihat kapan pertama kali voucher/member digunakan dan kapan expired, **so that** data dapat digunakan untuk laporan dan rekonsiliasi.

**Acceptance Criteria:**
- `first_login_at` diisi otomatis saat pertama kali ada `radacct` dengan username tersebut (Accounting-Start pertama).
- Pengisian `first_login_at` dilakukan via Scheduler yang memvalidasi dari `radacct` secara berkala, bukan real-time hook agar tidak membebani flow RADIUS.
- `expired_at` mencerminkan batas masa aktif sesuai paket.
- Keduanya tampil di daftar voucher, daftar member, dan laporan bulanan.

**Kompleksitas:** Medium

---

### Story 7 — Isolir / Aktifkan Layanan
**As a** operator, **I want to** mengisolir atau mengaktifkan user dengan konfirmasi yang dapat dikustomisasi, **so that** perubahan status terkontrol dan tidak terjadi secara tidak sengaja.

**Acceptance Criteria:**
- Aksi isolir/aktif menampilkan modal konfirmasi sebelum dieksekusi.
- Modal menampilkan: nama user, status saat ini, status baru, router yang terdampak (informasi saja).
- Checkbox "Ingat pilihan ini, jangan tanya lagi" tersimpan di database per akun login (bukan sesi browser).
- Preferensi auto-confirm dapat direset dari halaman pengaturan profil akun.
- Isolir = tambah entry `Auth-Type := Reject` di `radcheck`.
- Aktif = hapus entry reject, pulihkan autentikasi.
- Seluruh histori perubahan status disimpan di `service_action_logs` (siapa, kapan, dari status apa, ke status apa).
- Di Tahap 1: disconnect user yang sedang online saat isolir dilakukan manual karena MikroTik API belum aktif; sistem menampilkan informasi bahwa user mungkin masih terkoneksi sampai sesi berakhir sendiri.

**Kompleksitas:** High — sinkronisasi antara tabel app dan tabel RADIUS harus atomic.

---

### Story 8 — Monitoring User Online
**As a** operator, **I want to** melihat daftar user yang sedang terkoneksi, **so that** saya dapat memantau kondisi jaringan dan mengambil tindakan jika diperlukan.

**Acceptance Criteria:**
- Tabel menampilkan: username, tipe (voucher/member), NAS/router, IP assigned, waktu login, durasi aktif, data usage (jika tersedia dari `radacct`).
- Data diambil dari `radacct` di mana `acctstoptime` IS NULL.
- Sesi yang terdeteksi stale (tidak ada update accounting dalam X menit) ditandai dengan badge "Diduga Putus".
- Filter berdasarkan tipe user dan router.
- Auto-refresh setiap 30 detik dengan indikator timestamp refresh terakhir.
- Tombol manual refresh tersedia.
- Di Tahap 1: tombol disconnect ditampilkan namun dinonaktifkan dengan tooltip "Tersedia di Tahap 2".

**Kompleksitas:** Medium

---

### Story 9 — Rekonsiliasi Sesi Stale
**As a** sistem, **I want to** mendeteksi dan menandai sesi yang tidak pernah menerima Accounting-Stop, **so that** laporan dan monitoring tidak menampilkan data palsu.

**Acceptance Criteria:**
- Laravel Scheduler berjalan periodik (setiap 15–30 menit) untuk memeriksa sesi di `radacct` yang tidak diperbarui melebihi threshold (default: 2× interval Interim-Update NAS, minimal 10 menit).
- Sesi stale ditandai dengan flag `is_stale = true` dan `stale_detected_at`.
- Data mentah `radacct` tidak dihapus, hanya ditandai.
- Sesi stale tidak dihitung sebagai sesi aktif di monitoring dan laporan.
- Threshold rekonsiliasi dapat dikonfigurasi oleh super user dari pengaturan.
- Rekomendasi konfigurasi NAS untuk mengaktifkan Interim-Update ditampilkan di halaman pengaturan sistem.

**Kompleksitas:** Medium

---

### Story 10 — Laporan Bulanan
**As a** super user, **I want to** melihat dan mengunduh laporan bulanan, **so that** saya dapat memantau kinerja bisnis dan rekonsiliasi penjualan.

**Acceptance Criteria:**
- Filter laporan: bulan, tahun, tipe user (semua/voucher/member), paket.
- Laporan voucher mencakup per baris: batch_code, username, paket, harga (price_snapshot), tanggal dibuat, first_login_at, expired_at, status.
- Laporan member mencakup per baris: username, paket, harga (price_snapshot), tanggal aktif, expired_at, status, tagihan bulan ini.
- Ringkasan laporan: total voucher aktif, total voucher expired, total member aktif, total pendapatan dari price_snapshot.
- Laporan dapat diunduh sebagai PDF menggunakan DomPDF via Blade view.
- Tampilan laporan di web juga tersedia (tidak harus download saja).
- Laporan dibuat on-demand (generate saat diminta), bukan dijadwalkan otomatis di v1.

**Kompleksitas:** Medium

---

### Story 11 — Billing Member Bulanan
**As a** super user, **I want to** mengelola siklus tagihan member, **so that** status pembayaran dan jatuh tempo layanan bisa dipantau dan dikontrol.

**Acceptance Criteria:**
- Setiap member memiliki siklus billing bulanan dengan invoice otomatis terbuat setiap periode.
- Invoice dibuat otomatis via Laravel Scheduler H-7 sebelum jatuh tempo.
- Operator dapat mencatat pembayaran secara manual: nominal, tanggal bayar, metode pembayaran, catatan.
- Member dapat diperpanjang sebelum masa aktif habis; masa aktif baru dihitung dari tanggal jatuh tempo lama (bukan tanggal bayar).
- Status invoice: `pending`, `paid`, `overdue`, `cancelled`.
- Member dengan status `overdue` dapat ditandai untuk isolir otomatis atau manual oleh operator.
- Riwayat seluruh invoice dan pembayaran member tersimpan dan dapat dilihat di halaman detail member.
- Invoice dapat diunduh sebagai PDF.
- Fondasi payment gateway tersedia: kolom `payment_method`, `external_transaction_id`, `gateway_status` sudah ada di tabel `payments`, siap digunakan di Tahap 2.

**Kompleksitas:** Medium

---

### Story 12 — Invoice PDF & Download
**As a** super user, **I want to** mengunduh invoice member sebagai PDF, **so that** dokumentasi tagihan dapat disimpan atau dibagikan ke member secara manual.

**Acceptance Criteria:**
- Setiap invoice memiliki tombol "Download PDF".
- PDF dirender dari Blade view menggunakan Laravel DomPDF.
- PDF mencakup: nama member, paket, periode tagihan, nominal, status, dan riwayat pembayaran.
- Nama file PDF otomatis: `invoice-{username}-{bulan}-{tahun}.pdf`.
- Download bersifat on-demand (tidak perlu simpan file di server).

**Kompleksitas:** Low — implementasi DomPDF dari Blade view cukup straight-forward.

---

### Story 13 — Print Voucher
**As a** operator, **I want to** mencetak voucher yang sudah di-generate berdasarkan batch, **so that** voucher dapat diserahkan ke pelanggan sesuai sesi generate.

**Acceptance Criteria:**
- Halaman print voucher dapat difilter berdasarkan batch_code.
- Operator memilih template: A4 (multi-voucher per halaman) atau thermal (58mm / 80mm).
- Preview layout ditampilkan di layar sebelum cetak.
- Setiap kartu voucher menampilkan: nama paket, SSID/jaringan (dapat dikonfigurasi), username, password, masa aktif, dan (opsional) harga.
- Print menggunakan CSS `@media print` yang dirender di browser, tanpa library tambahan.
- Operator bisa tandai voucher sebagai "sudah dicetak" untuk tracking.

**Kompleksitas:** Medium

---

### Story 14 — Manajemen Router MikroTik
**As a** super user, **I want to** mendokumentasikan dan mengatur data router MikroTik dari frontend, **so that** konfigurasi jaringan terpusat dan siap digunakan untuk operasi Tahap 2.

**Acceptance Criteria:**
- Data router: nama/label, IP, port API (default 8728), username, password/secret (terenkripsi), lokasi, status aktif.
- Kredensial disimpan terenkripsi menggunakan Laravel `encrypted` cast (AES-256-CBC via APP_KEY).
- Tombol "Test Connection" memverifikasi koneksi API dari server Laravel ke router.
- Hasil test connection ditampilkan: sukses + versi RouterOS, atau gagal + pesan error.
- Router yang dinonaktifkan tidak diikutsertakan dalam test connection otomatis.
- Semua perubahan konfigurasi router dicatat di audit log.
- Di Tahap 1: data router dipakai sebagai referensi saja (informasi NAS); aksi operasional (disconnect, CoA) ditunda ke Tahap 2.

**Kompleksitas:** Medium

---

### Story 15 — UI Responsif Desktop & Smartphone
**As a** operator, **I want to** menggunakan panel dari smartphone, **so that** saya bisa mengelola user dan monitoring tanpa harus ke depan komputer.

**Acceptance Criteria:**
- Aplikasi berfungsi penuh di viewport 375px (iPhone SE) hingga 1440px+ (desktop).
- Breakpoint utama: 375px (mobile), 768px (tablet), 1024px (desktop).
- **Desktop:** sidebar tetap tampil, tabel data penuh, filter di samping, bulk actions tersedia.
- **Smartphone:** sidebar menjadi drawer (hamburger), tabel berubah menjadi card list, tombol aksi berukuran ≥ 44×44px, filter dalam bottom sheet atau modal.
- Halaman prioritas mobile: daftar user (voucher/member), monitoring online, status isolir/aktif, billing ringkas.
- Tidak ada teks yang terpotong atau elemen yang overflow di layar kecil.
- Form input tidak memicu zoom otomatis (font-size ≥ 16px pada input).

**Kompleksitas:** Medium — memerlukan desain sistem responsif yang konsisten dari awal.

---

### Story 16 — Deployment via Cloudflare Tunnel
**As a** super user, **I want to** mengakses panel melalui subdomain publik via Cloudflare Tunnel, **so that** panel dapat diakses dari luar jaringan lokal tanpa membuka port di router.

**Acceptance Criteria:**
- Aplikasi Laravel dikonfigurasi dengan `TrustProxies` untuk menerima header dari Cloudflare.
- HTTPS dihandle oleh Cloudflare; koneksi internal server menggunakan HTTP lokal.
- Session, cookie, dan CSRF tetap berfungsi di belakang proxy.
- `SESSION_SECURE_COOKIE=true` dan `APP_URL` ditetapkan ke URL subdomain.
- Tidak ada redirect loop HTTP↔HTTPS.
- Rate limiting login tetap berfungsi berbasis IP asli (dari header `CF-Connecting-IP`).

**Kompleksitas:** Low — konfigurasi, bukan fitur.

---

## 7. Functional Requirements

1. Sistem harus menyediakan halaman onboarding super user pertama yang terkunci setelah digunakan.
2. Sistem harus mendukung autentikasi login/logout dengan rate limiting (brute-force protection).
3. Sistem harus membatasi akses fitur berdasarkan role menggunakan middleware Laravel Gate/Policy.
4. Super user harus dapat CRUD akun operator dan menonaktifkan sesi aktif operator.
5. Sistem harus mendukung CRUD profil/paket dengan parameter speed, durasi, harga, dan simultaneous-use.
6. Sistem harus mendukung generate voucher satuan dan bulk dengan konfigurasi prefix, panjang, dan jenis karakter.
7. Setiap sesi generate harus menghasilkan satu record batch dengan identifikasi unik.
8. Sistem harus memastikan username unik di seluruh database (lintas voucher dan member).
9. Untuk voucher: username = password; simultaneous-use = 1; disimpan ke `radcheck`.
10. Untuk member: username ≠ password; simultaneous-use dapat dikonfigurasi.
11. Sistem harus mencatat `first_login_at`, `expired_at`, dan `price_snapshot` per entitas user.
12. Sistem harus terhubung ke PostgreSQL yang digunakan FreeRADIUS sebagai SQL backend.
13. Perubahan status isolir/aktif harus menulis ke `radcheck` dalam satu database transaction.
14. Aksi sensitif harus menampilkan konfirmasi, dengan preferensi auto-confirm per akun user di database.
15. Sistem harus menampilkan daftar sesi online dari `radacct` dengan filter dan auto-refresh.
16. Sistem harus menjalankan rekonsiliasi sesi stale secara periodik via Laravel Scheduler.
17. Threshold rekonsiliasi sesi stale harus dapat dikonfigurasi oleh super user.
18. Sistem harus menyediakan laporan bulanan yang dapat diunduh sebagai PDF.
19. Laporan harus mencakup: batch_code, username, paket, price_snapshot, first_login_at, expired_at, status.
20. Sistem harus menyediakan billing invoice member dengan siklus bulanan.
21. Invoice harus dapat diunduh sebagai PDF menggunakan DomPDF.
22. Sistem harus mendukung perpanjangan member dari tanggal jatuh tempo lama.
23. Sistem harus mendukung print voucher dengan template A4 dan thermal, difilter per batch.
24. Konfigurasi router MikroTik harus disimpan terenkripsi dengan Laravel `encrypted` cast.
25. Sistem harus mendukung test connection ke router MikroTik.
26. UI harus responsif dan berfungsi penuh di 375px–1440px+.
27. Sistem harus dikonfigurasi untuk berjalan dengan benar di belakang Cloudflare Tunnel.
28. Semua aksi sensitif harus dicatat di audit log dengan timestamp, aktor, dan detail perubahan.

---

## 8. Non-Functional Requirements

| Kategori | Requirement |
|----------|-------------|
| **Security** | Password di-hash bcrypt, kredensial router dienkripsi AES-256-CBC via APP_KEY, CSRF protection, rate limiting login, HTTPS via Cloudflare, tidak ada data sensitif di log |
| **Performance** | Halaman daftar user/voucher responsif sampai ribuan record dengan pagination dan database indexing yang tepat |
| **Reliability** | Kegagalan koneksi ke MikroTik tidak memblokir operasi RADIUS; isolir tetap berhasil walaupun test connection router gagal |
| **Scalability** | Struktur database dan service class siap menambah router, paket, billing, dan integrasi payment gateway |
| **Auditability** | Semua aksi kritis tercatat di audit log: aktor, timestamp, entitas yang diubah, nilai sebelum dan sesudah |
| **Maintainability** | Kode dipisah jelas dalam domain: App, RADIUS, MikroTik Integration, Billing, Reporting |
| **Usability** | Operator dapat menyelesaikan tugas utama tanpa pemahaman teknis jaringan; UI jelas dan konsisten |
| **Responsiveness** | UI berfungsi optimal di desktop (1280px+) dan smartphone (375px+) |
| **Proxy Compatibility** | Aplikasi berjalan benar di belakang Cloudflare Tunnel: trusted proxies, HTTPS detection, session cookie, rate limiting berbasis IP asli |

---

## 9. Technical Considerations

### Stack yang Direkomendasikan

| Layer | Teknologi | Catatan |
|-------|-----------|---------|
| Framework | Laravel 11 | Auth, RBAC, Queue, Encryption, Scheduler bawaan |
| Bahasa | PHP 8.2+ | Native Laravel |
| Database | PostgreSQL (single instance) | Backend FreeRADIUS + App DB |
| Frontend | Blade + Tailwind CSS + Alpine.js | SSR, responsif, tidak perlu SPA untuk admin panel |
| Queue | Laravel Queue (DB driver) | Async job: rekonsiliasi sesi, generate invoice, notifikasi |
| Encryption | Laravel `encrypted` cast | Untuk secret router; kunci = APP_KEY |
| PDF | `barryvdh/laravel-dompdf` | Generate invoice dan laporan bulanan |
| MikroTik API | RouterOS PHP library | Hanya untuk test connection di Tahap 1 |
| Scheduler | Laravel Scheduler | Rekonsiliasi sesi stale, generate invoice otomatis, auto-isolir overdue |
| Audit Log | `spatie/laravel-activitylog` | Integrasi mudah dengan Eloquent model |
| Rate Limiting | Laravel built-in throttle middleware | Login endpoint, aksi sensitif |
| Proxy Config | `TrustProxies` middleware | Cloudflare Tunnel |
| Print Voucher | HTML + CSS `@media print` | Render di browser, tidak perlu server-side rendering khusus |

### Arsitektur Integrasi

```
[MikroTik NAS] ──RADIUS Auth──► [FreeRADIUS] ──SQL──► [PostgreSQL]
                                                            ▲
                                                     [Laravel App]
                                                  (baca/tulis langsung)

[Laravel App] ──RouterOS API (test connection saja, Tahap 1)──► [MikroTik NAS]
              ──RouterOS API (disconnect, CoA — Tahap 2)──────► [MikroTik NAS]
```

**Prinsip desain Tahap 1:** Laravel adalah satu-satunya yang menulis ke PostgreSQL untuk keperluan RADIUS. FreeRADIUS hanya membaca. MikroTik hanya melakukan query ke FreeRADIUS saat autentikasi. Tidak ada coupling langsung Laravel → MikroTik untuk operasi inti autentikasi.

### Desain Database

**Schema `public` — FreeRADIUS Standard**

| Tabel | Fungsi |
|-------|--------|
| `radcheck` | Atribut autentikasi per user (password, Auth-Type Reject untuk isolir) |
| `radreply` | Atribut reply per user (speed limit, session timeout) |
| `radusergroup` | Mapping user ke grup |
| `radgroupcheck` | Atribut autentikasi per grup |
| `radgroupreply` | Atribut reply per grup |
| `radacct` | Accounting — sesi aktif dan histori koneksi |
| `nas` | Daftar NAS/router yang diizinkan mengirim request ke FreeRADIUS |

**Schema `app` — Custom Application**

| Tabel | Kolom Penting |
|-------|---------------|
| `app_users` | id, name, email, password, role, is_active |
| `app_user_preferences` | user_id, key, value (untuk auto-confirm dan preferensi lain) |
| `plans` | id, name, upload_speed, download_speed, duration_value, duration_unit, price, simultaneous_use, is_active |
| `voucher_batches` | id, batch_code, prefix, length, charset_mode, quantity, plan_id, generated_by, generated_at, notes |
| `vouchers` | id, batch_id, username, password_plain (encrypted), plan_id, price_snapshot, status, first_login_at, expired_at, created_at |
| `members` | id, username, password_plain (encrypted), plan_id, price_snapshot, simultaneous_use, status, activated_at, expired_at, notes |
| `routers` | id, name, ip_address, api_port, api_username, api_secret (encrypted), location, is_active |
| `billing_invoices` | id, member_id, period_start, period_end, amount, status, due_date, created_at |
| `payments` | id, invoice_id, amount, paid_at, payment_method, external_transaction_id, gateway_status, notes |
| `service_action_logs` | id, entity_type, entity_id, action, previous_status, new_status, performed_by, performed_at, notes |
| `activity_logs` | (via spatie/laravel-activitylog) |

**Catatan desain penting:**
- `price_snapshot` disimpan saat voucher/member dibuat; tidak berubah jika harga paket diupdate belakangan. Ini memastikan laporan historis tetap akurat.
- `vouchers.password_plain` dan `members.password_plain` disimpan terenkripsi (untuk keperluan print voucher). Password yang dikirim ke `radcheck` adalah plaintext yang dibaca FreeRADIUS untuk `Cleartext-Password` — ini adalah behavior standar FreeRADIUS SQL module.
- `billing_invoices.status` menggunakan PHP Enum: `pending`, `paid`, `overdue`, `cancelled`.
- `voucher_batches.batch_code` di-generate otomatis: format `BATCH-YYYYMMDD-XXXXXX`.

### Enkripsi Kredensial Router

```php
// Model Router
protected $casts = [
    'api_secret'   => 'encrypted',
    'api_password' => 'encrypted',
];
```

APP_KEY Laravel (AES-256-CBC) digunakan sebagai kunci enkripsi. Jika APP_KEY dirotasi, seluruh nilai `encrypted` cast harus di-re-enkripsi — prosedur ini harus didokumentasikan sebagai runbook operasional.

### Konfigurasi Cloudflare Tunnel

```php
// app/Http/Middleware/TrustProxies.php
protected $proxies = '*';
protected $headers =
    Request::HEADER_X_FORWARDED_FOR   |
    Request::HEADER_X_FORWARDED_HOST  |
    Request::HEADER_X_FORWARDED_PORT  |
    Request::HEADER_X_FORWARDED_PROTO;
```

```env
APP_URL=https://radius.yourdomain.com
TRUSTED_PROXIES=*
SESSION_SECURE_COOKIE=true
SESSION_DOMAIN=.yourdomain.com
```

Rate limiting login menggunakan `RateLimiter::for('login')` dengan key berbasis IP dari `$request->ip()` yang sudah terforward dengan benar oleh TrustProxies.

### Rekonsiliasi Sesi Stale

```php
// Scheduler — app/Console/Kernel.php
$schedule->job(new ReconcileStaleSessionsJob)->everyFifteenMinutes();
$schedule->job(new GenerateOverdueInvoicesJob)->dailyAt('01:00');
$schedule->job(new AutoIsolateOverdueMembersJob)->dailyAt('02:00');
$schedule->job(new SyncFirstLoginAtJob)->hourly();
```

`ReconcileStaleSessionsJob` menandai baris `radacct` dengan `is_stale = true` bila `acctupdatetime` + threshold < now. Threshold default = 2× interval Interim-Update, dapat dikonfigurasi dari pengaturan sistem.

### PDF Generation

```php
// Menggunakan barryvdh/laravel-dompdf
use Barryvdh\DomPDF\Facade\Pdf;

$pdf = Pdf::loadView('reports.monthly', compact('data'));
return $pdf->download("laporan-{$bulan}-{$tahun}.pdf");
```

Template PDF dibuat dalam Blade view terpisah di `resources/views/reports/` dan `resources/views/invoices/`.

---

## 10. UI/UX Requirements & Wireframe Notes

### Navigasi & Layout

**Super User — Sidebar Penuh:**
- Dashboard
- Voucher (Generate, Daftar, Batch, Print)
- Member
- Profil / Paket
- User Online
- Billing & Invoice
- Laporan Bulanan
- Manajemen Operator
- Router MikroTik
- Audit Log
- Pengaturan Sistem

**Operator — Sidebar Terbatas:**
- Dashboard (ringkas)
- Voucher
- Member
- User Online

### Responsif — Perbedaan Desktop vs Smartphone

| Elemen | Desktop | Smartphone |
|--------|---------|------------|
| Sidebar | Tetap tampil di kiri | Drawer, buka via hamburger |
| Tabel data | Kolom penuh, sort & filter inline | Card list per row, swipe actions |
| Form generate | Dua kolom | Single column, full width |
| Tombol aksi | Normal size | ≥ 44×44px, jarak antar tombol cukup |
| Filter | Di atas tabel | Bottom sheet atau accordion |
| Modal konfirmasi | Center dialog | Full-bottom sheet |
| Print voucher | Preview multi-kolom | Preview single kolom, scroll |
| Dashboard KPI | Grid 4 kolom | Grid 2 kolom, scroll |

### Wireframe Notes Per Halaman Utama

**Dashboard:**
- KPI cards: user aktif, user isolir, user online saat ini, tagihan overdue bulan ini.
- Tabel ringkas: 5 user terbaru, 5 batch voucher terbaru.
- Tombol cepat: Generate Voucher, Tambah Member.

**Generate Voucher:**
- Form kiri: prefix, panjang, jenis karakter, paket, jumlah.
- Preview kanan: contoh format hasil generate (realtime update).
- Setelah generate: tampilkan tabel hasil batch, tombol Print dan Download.

**Daftar Voucher / Member:**
- Pencarian realtime, filter status, filter paket, filter batch (voucher).
- Setiap baris: username, paket, status badge (warna), first_login_at, expired_at, price_snapshot, tombol aksi.
- Bulk action: isolir/aktif banyak user sekaligus.

**Modal Konfirmasi Isolir/Aktif:**
- Header: judul aksi + ikon status.
- Body: nama user, status saat ini → status baru.
- Info: "User mungkin tetap terkoneksi sampai sesi berakhir sendiri (Tahap 1)."
- Checkbox: "Ingat pilihan ini, jangan konfirmasi lagi untuk akun saya."
- Footer: tombol Batal (secondary) + Konfirmasi (primary, warna sesuai aksi).

**User Online:**
- Tabel auto-refresh 30 detik, timestamp refresh terakhir di pojok kanan.
- Badge "Diduga Putus" berwarna abu untuk sesi stale.
- Tombol Disconnect ditampilkan disable dengan tooltip "Tersedia di Tahap 2."
- Filter: tipe user, router.

**Laporan Bulanan:**
- Filter: bulan, tahun, tipe user, paket.
- Ringkasan di atas: total aktif, total expired, total pendapatan estimasi.
- Tabel detail di bawah dengan semua kolom laporan.
- Tombol "Download PDF" di atas kanan.

**Billing Member:**
- List invoice per member: periode, nominal, due date, status badge.
- Tombol: Tandai Bayar, Perpanjang, Download PDF Invoice.
- Panel riwayat di bawah: semua pembayaran historis.

**Router MikroTik:**
- Kartu per router: nama, IP, status badge (aktif/nonaktif), waktu test terakhir.
- Tombol: Edit, Test Connection, Aktif/Nonaktif.
- Hasil test connection ditampilkan inline di kartu: sukses (versi RouterOS) atau gagal (pesan error).

### Aturan UX Penting

- **Warna status konsisten:** Aktif = hijau, Isolir = merah/oranye, Expired = abu, Pending = kuning.
- **Feedback setiap aksi:** Loading state saat proses, sukses/gagal toast notification setelah selesai.
- **Koneksi router gagal = warning non-blocking,** bukan error fatal yang memblokir halaman.
- **Form input font-size ≥ 16px** untuk mencegah auto-zoom di iOS Safari.
- **Sesi stale ditandai jelas** agar operator tidak salah tafsir bahwa user masih aktif.
- **Print voucher hanya via browser** — tidak perlu plugin atau software tambahan di sisi operator.

---

## 11. Dependencies & Risks

### Dependencies Teknis

- PostgreSQL aktif dan schema FreeRADIUS sudah terpasang (radcheck, radreply, radacct, nas, dll).
- FreeRADIUS dikonfigurasi menggunakan SQL module dengan backend PostgreSQL.
- MikroTik dikonfigurasi sebagai NAS yang mengarah ke FreeRADIUS (untuk autentikasi/akuntansi).
- Port MikroTik API (8728) dapat dijangkau dari server Laravel di jaringan lokal (untuk test connection).
- Cloudflare Tunnel aktif dan subdomain sudah dipointing ke server.
- `APP_KEY` Laravel dijaga kerahasiaannya karena dipakai untuk enkripsi router credentials.
- Composer package: `barryvdh/laravel-dompdf`, `spatie/laravel-activitylog`, RouterOS PHP library.
- MikroTik dikonfigurasi mengirimkan Interim-Update accounting (rekomendasi: setiap 1–5 menit) agar rekonsiliasi sesi stale akurat.

### Top 3 Risiko Terbesar & Mitigasi

| # | Risiko | Dampak | Mitigasi |
|---|--------|--------|----------|
| 1 | **Desync status** antara tabel app dan `radcheck` RADIUS saat transaksi gagal di tengah jalan | User di-isolir di app tapi masih bisa autentikasi via RADIUS, atau sebaliknya | Gunakan `DB::transaction()` yang mencakup write ke app table dan radcheck sekaligus. Tambahkan job reconciliation harian yang membandingkan status di kedua sisi. |
| 2 | **Credential router bocor** jika `APP_KEY` ter-expose atau backup database tidak dienkripsi | Akses penuh ke semua router MikroTik di jaringan | Dokumentasikan prosedur rotasi APP_KEY (harus diikuti re-enkripsi semua data encrypted). Backup database harus dienkripsi. Batasi akses file `.env`. |
| 3 | **Sesi stale mengganggu akurasi laporan dan monitoring** jika NAS tidak mengirim Accounting-Stop secara konsisten | Data user online dan laporan bulanan tidak akurat | Aktifkan Interim-Update di NAS/MikroTik. Implementasikan job rekonsiliasi sesi stale dengan threshold yang dapat dikonfigurasi. Tampilkan badge "Diduga Putus" di UI agar operator aware. |

---

## 12. Timeline & Milestones

*Project personal tanpa deadline ketat — timeline bersifat rekomendasi fase bertahap.*

### Fase 1 — Foundation & Infrastruktur *(estimasi 2–3 minggu)*
- [ ] Setup Laravel 11, PostgreSQL, autentikasi.
- [ ] Halaman onboarding super user pertama.
- [ ] RBAC dua role + middleware.
- [ ] Manajemen akun operator.
- [ ] Layout admin panel responsif (sidebar + drawer mobile).
- [ ] Integrasi schema FreeRADIUS di PostgreSQL.
- [ ] Konfigurasi Cloudflare Tunnel & TrustProxies.
- [ ] Setup `spatie/laravel-activitylog`.

### Fase 2 — Core User Management *(estimasi 2–3 minggu)*
- [ ] CRUD profil/paket.
- [ ] Generate voucher (satuan + bulk) dengan konfigurasi format dan batch.
- [ ] CRUD member.
- [ ] Sinkronisasi otomatis ke `radcheck` dan `radreply`.
- [ ] Fitur isolir/aktif dengan konfirmasi dan auto-confirm preference.
- [ ] Print voucher (A4 + thermal) dengan filter batch.
- [ ] Scheduler: `SyncFirstLoginAtJob`.

### Fase 3 — Monitoring & Laporan *(estimasi 1–2 minggu)*
- [ ] Monitoring user online dari `radacct`.
- [ ] Auto-refresh dan filter sesi online.
- [ ] Rekonsiliasi sesi stale + konfigurasi threshold.
- [ ] Laporan bulanan: web view + download PDF.
- [ ] Setup `barryvdh/laravel-dompdf`.

### Fase 4 — Billing *(estimasi 1–2 minggu)*
- [ ] Invoice member bulanan.
- [ ] Scheduler: generate invoice otomatis + auto-isolir overdue.
- [ ] Pencatatan pembayaran manual.
- [ ] Perpanjangan masa aktif.
- [ ] Download PDF invoice.

### Fase 5 — Router & Foundation Tahap 2 *(estimasi 1–2 minggu)*
- [ ] CRUD router MikroTik + enkripsi kredensial.
- [ ] Test connection MikroTik API.
- [ ] Desain abstraksi `PaymentGateway` interface (foundation Tahap 2).
- [ ] Desain `NotificationService` interface (foundation Tahap 2).
- [ ] Dokumentasi runbook: rotasi APP_KEY, backup database.

---

## 13. Open Questions — Status Final

| # | Pertanyaan | Status | Keputusan |
|---|-----------|--------|-----------|
| 1 | Mekanisme isolir di MikroTik? | ✅ Ditutup | Tahap 1: RADIUS saja. Tahap 2: tambah firewall rule MikroTik. |
| 2 | Notifikasi jatuh tempo? | ✅ Ditutup | Tahap 2: desain channel (email/WhatsApp) dibahas saat mulai Fase 5. |
| 3 | Format billing invoice? | ✅ Ditutup | Tahap 1: PDF download manual via DomPDF. |
| 4 | Keamanan akses panel? | ✅ Ditutup | Laravel login + rate limiting. Cloudflare Access ditambahkan opsional saat produksi. |
| 5 | Rekonsiliasi sesi stale? | ✅ Ditutup | Interim-Update dari NAS + Laravel Scheduler + threshold konfigurasi + badge "Diduga Putus" di UI. |

### Pertanyaan Baru — Perlu Jawaban Sebelum Development

1. **Nama aplikasi / branding?** Akan dipakai untuk title, logo, dan nama file export.
2. **SSID/nama jaringan** yang dicetak di voucher — statis (dikonfigurasi di pengaturan) atau per router?
3. **Interval Interim-Update** yang akan dikonfigurasi di MikroTik — berapa menit? Ini menentukan default threshold rekonsiliasi sesi stale.
4. **Format durasi paket:** apakah "30 hari" dan "1 bulan" diperlakukan sama, atau perlu pembedaan (kalender vs hari tetap)?
5. **Apakah laporan bulanan perlu per-operator** (siapa yang generate voucher tersebut), atau hanya global?

---

## Ringkasan Eksekutif

### Rekomendasi MVP v1 — Wajib Ada
Onboarding super user → RBAC → manajemen operator → profil paket → generate voucher dengan batch → manajemen member → sinkronisasi RADIUS → isolir/aktif (RADIUS only) → monitoring online + rekonsiliasi stale → laporan bulanan PDF → billing dasar → invoice PDF → print voucher per batch → manajemen router (dokumentasi + enkripsi) → UI responsif desktop + mobile → Cloudflare Tunnel.

### Ditunda ke Tahap 2
Disconnect paksa via MikroTik API, isolir via firewall rule MikroTik, notifikasi WhatsApp/email, integrasi payment gateway live, grafik analitik bandwidth.

### Ditunda ke Tahap 3
Auto-billing berbasis payment gateway, multi-tenant, analitik advanced, integrasi NMS.

---

*PRD Versi 1.1 Final — Siap untuk development.*  
*Konfirmasi sebelum memulai: "Apakah ada bagian yang perlu diubah atau ditambahkan?"*
