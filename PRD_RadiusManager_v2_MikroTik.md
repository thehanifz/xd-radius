# Product Requirements Document
# RadiusManager v2 — Manajemen Hotspot, Voucher, dan PPPoE MikroTik

**Status:** Draft implementasi  
**Platform:** Laravel 11, PostgreSQL, FreeRADIUS 3, MikroTik RouterOS  
**Target pengguna:** ISP kecil, RT/RW Net, dan operator jaringan lokal

## 1. Ringkasan Produk

RadiusManager adalah panel web untuk mengelola pelanggan **Hotspot voucher**, **Hotspot member**, dan **PPPoE member** pada satu atau banyak router MikroTik yang didaftarkan lewat UI. Aplikasi menjadi sumber data layanan: operator membuat paket dan akun dari panel, aplikasi menyinkronkan atribut autentikasi ke FreeRADIUS, lalu MikroTik memakai FreeRADIUS untuk autentikasi dan accounting.

MikroTik tetap menjadi NAS. FreeRADIUS menjadi otoritas autentikasi. Laravel tidak menulis akun secara langsung ke database internal MikroTik pada rilis awal; integrasi RouterOS API dipakai untuk verifikasi router, disconnect/CoA terkontrol, dan operasi jaringan yang memang memerlukannya.

## 2. Tujuan dan Non-Tujuan

### Tujuan utama

- Mendaftarkan dan mengelola router MikroTik/NAS melalui UI secara aman.
- Mengelola paket layanan, voucher Hotspot, member Hotspot, dan member PPPoE dari satu panel.
- Menjaga status aplikasi dan atribut FreeRADIUS selalu konsisten.
- Memberikan monitoring sesi online serta histori dasar penggunaan dan pembayaran.
- Memungkinkan operator mengerjakan tugas harian tanpa Winbox untuk alur yang didukung.

### Non-tujuan rilis v1

- Multi-tenant antar ISP.
- Provisioning VLAN, interface, pool IP, firewall, atau hotspot server MikroTik secara otomatis.
- Payment gateway otomatis dan notifikasi WhatsApp.
- Analitik bandwidth mendalam atau aplikasi mobile native.

## 3. Prinsip Desain

1. **Satu sumber kebenaran:** data pelanggan dan status layanan disimpan di database aplikasi/FreeRADIUS yang sama.
2. **RADIUS-first:** autentikasi dan atribut layanan ditulis ke tabel FreeRADIUS; operasi RouterOS API hanya pelengkap.
3. **Atomic dan idempoten:** perubahan akun/status harus berlangsung dalam transaction; job sinkronisasi harus aman dijalankan ulang.
4. **Least privilege:** operator hanya mendapat akses operasional; konfigurasi router, paket, role, dan pengaturan global hanya superuser.
5. **Audit by default:** aksi yang mengubah layanan, kredensial, paket, router, atau pembayaran selalu mencatat aktor, waktu, dan nilai sebelum/sesudah.
6. **No plaintext in app tables:** kredensial yang perlu ditampilkan ulang disimpan dengan Laravel `encrypted` cast; akses ke `radcheck` dibatasi ketat karena FreeRADIUS memerlukan `Cleartext-Password`.

## 4. Peran dan Hak Akses

| Fitur | Superuser | Operator |
|---|---:|---:|
| Dashboard dan monitoring | Ya | Ya |
| Voucher dan member Hotspot/PPPoE | Ya | Ya |
| Isolir/aktif dan disconnect | Ya | Ya |
| Lihat histori billing | Ya | Ya |
| Catat pembayaran | Ya | Opsional, ditentukan konfigurasi |
| Paket/profil layanan | CRUD | Lihat saja |
| Router/NAS | CRUD dan test connection | Tidak |
| Operator, pengaturan, audit log, laporan bisnis | Ya | Tidak |

Semua pembatasan diterapkan oleh middleware/policy di server, bukan hanya dengan menyembunyikan menu.

## 5. Arsitektur

```
[Browser]
    │ HTTPS / Cloudflare Tunnel
[Laravel RadiusManager]
    │ transaction read/write
[PostgreSQL: aplikasi + tabel FreeRADIUS] ←→ [FreeRADIUS]
                                             │ RADIUS auth/accounting
                                      [MikroTik NAS]
```

- Setiap router terdaftar sebagai NAS di tabel `nas` dengan RADIUS shared secret.
- RouterOS API credentials disimpan terenkripsi dan digunakan untuk **test connection**, disconnect sesi, serta CoA bila dikonfigurasi.
- Kegagalan API MikroTik tidak boleh membatalkan perubahan data RADIUS yang sudah valid; error ditampilkan dan dicatat untuk retry.

## 6. Ruang Lingkup Fitur

### 6.1 Onboarding, autentikasi, dan keamanan

- Onboarding hanya tersedia ketika belum ada superuser; pembuatan superuser harus atomik.
- Login, logout, remember-me opsional, regenerasi session saat login, CSRF, serta rate limit berbasis IP asli di balik Cloudflare.
- Akun nonaktif langsung logout pada request berikutnya; sesi aktif dapat dicabut saat superuser menonaktifkan operator.
- Semua secret router dan password yang disimpan untuk cetak dienkripsi dengan `APP_KEY`; `.env` dan backup database wajib dilindungi.

### 6.2 Router MikroTik/NAS

Superuser dapat membuat, mengubah, menonaktifkan, dan menghapus router lewat UI.

Data minimum: nama, IP/FQDN, port API, username API, password API, RADIUS shared secret, lokasi, tipe layanan yang didukung (`hotspot`, `pppoe`, atau keduanya), dan status aktif.

Acceptance criteria:

- IP/FQDN dan kombinasi router harus unik; port divalidasi 1–65535.
- `api_password` dan `radius_secret` disimpan encrypted; UI tidak pernah menampilkan nilai tersimpan secara penuh.
- Tombol **Test Connection** memiliki timeout, tidak menjalankan command shell, dan menyimpan hasil/versi RouterOS/error aman.
- Router aktif tersinkron secara idempoten ke tabel `nas`; perubahan NAS menghasilkan reload/restart FreeRADIUS melalui service yang dibatasi, diaudit, dan gagal secara non-blocking.
- Router tidak dapat dihapus selama masih menjadi referensi sesi atau konfigurasi pelanggan aktif; gunakan nonaktif/soft delete.

### 6.3 Paket layanan

Paket mempunyai: nama, tipe (`hotspot-voucher`, `hotspot-member`, `pppoe-member`), upload/download Kbps, durasi, harga, concurrent sessions, RADIUS group, dan status aktif.

Acceptance criteria:

- Paket voucher selalu `simultaneous_use = 1`; nilai member dapat diatur per akun sesuai batas paket.
- Harga saat paket dipakai disalin sebagai `price_snapshot` dan tidak berubah oleh edit paket berikutnya.
- Mengubah rate limit atau RADIUS group paket memicu job sinkronisasi untuk seluruh akun aktif pengguna paket tersebut.
- Paket yang dipakai tidak boleh dihapus; hanya boleh dinonaktifkan.
- Durasi harus eksplisit: `duration_value` dan `duration_unit` (`hours`, `days`, `calendar_months`); logika kalender terdokumentasi dan teruji.

### 6.4 Voucher Hotspot

Operator dapat membuat batch voucher berdasarkan paket Hotspot Voucher: prefix, panjang total, charset, jumlah, dan catatan.

Acceptance criteria:

- Username unik di seluruh voucher, member Hotspot, dan member PPPoE.
- Username = password hanya untuk voucher; password aplikasi disimpan encrypted.
- Satu request membuat satu `voucher_batch` dan tepat sejumlah voucher yang diminta, atau seluruh transaction gagal—tidak ada batch parsial.
- Voucher berstatus `available`, `active`, `expired`, `isolated`, atau `disabled`; definisinya terdokumentasi.
- Masa aktif voucher dimulai pada login pertama; `first_login_at` diperoleh dari Accounting-Start pertama dan `expired_at` dihitung deterministik dari paket.
- Setelah expired/isolated, atribut RADIUS harus memblokir autentikasi. Sistem harus mempunyai job expiry idempoten.
- Voucher dapat dicetak ulang per batch dalam A4 dan thermal; aksi print menandai `printed_at` tanpa mengubah kredensial.

### 6.5 Member Hotspot dan PPPoE

Member adalah akun pelanggan berlangganan dengan username dan password berbeda. Saat membuat member, operator memilih tipe layanan Hotspot atau PPPoE, paket yang kompatibel, serta router/NAS yang diizinkan bila dibutuhkan.

Acceptance criteria:

- Username unik lintas semua tipe akun; password minimal 8 karakter dan tidak boleh sama dengan username.
- Record menyimpan paket, `price_snapshot`, tanggal aktif, tanggal jatuh tempo, concurrent sessions, status, catatan, dan relasi router bila pembatasan NAS digunakan.
- Untuk Hotspot dan PPPoE, atribut RADIUS yang tepat dibuat (`Cleartext-Password`, group, rate limit, simultaneous-use, dan pembatasan NAS bila dikonfigurasi).
- Edit username dilakukan sebagai operasi migrasi terkontrol yang memindahkan seluruh atribut RADIUS/riwayat terkait, atau dinonaktifkan di v1. Jangan melakukan update parsial.
- Penghapusan akun adalah soft delete di aplikasi dan deprovision tertransaksi pada tabel RADIUS; histori billing/audit tetap ada.

### 6.6 Status layanan dan isolir

Status kanonik: `active`, `isolated`, `expired`, `disabled`.

- **Active:** autentikasi diizinkan sesuai paket.
- **Isolated:** autentikasi ditolak melalui `Auth-Type := Reject` di `radcheck` pada rilis v1.
- **Expired/disabled:** autentikasi ditolak dengan mekanisme yang sama dan alasan tercatat.

Acceptance criteria:

- Perubahan status aplikasi dan tabel RADIUS berada dalam satu database transaction.
- UI meminta konfirmasi; preferensi auto-confirm per pengguna tersimpan di database dan bisa direset.
- Setelah isolir, aplikasi dapat mengirim Disconnect/CoA hanya jika router aktif, API tersedia, dan fitur ini diaktifkan. Jika gagal, status RADIUS tetap valid dan retry dapat dilakukan.
- Isolir firewall/address-list MikroTik merupakan fitur terpisah dan tidak dimasukkan tanpa spesifikasi kebijakan akses terbatas yang jelas.

### 6.7 Monitoring dan accounting

- Daftar sesi dari `radacct` dengan username, layanan, router/NAS, IP, waktu mulai, durasi, upload/download, dan status stale.
- Filter menurut tipe layanan, router, status, dan pencarian username.
- Refresh otomatis 30 detik, tombol manual refresh, serta timestamp refresh terakhir.
- Job stale berjalan setiap 15 menit; threshold superuser-configurable dan minimal 10 menit.
- Sesi stale ditandai, bukan dihapus, dan tidak dihitung sebagai sesi aktif.

### 6.8 Billing dan laporan

- Invoice periodik member, pembayaran manual, perpanjangan dari tanggal jatuh tempo lama, PDF invoice, dan laporan bulanan PDF.
- Invoice otomatis dibuat secara idempoten H-N; ada unique constraint untuk member + periode.
- Pembayaran hanya dapat diterapkan pada invoice payable; nominal, overpayment, partial payment, pembatalan, dan renew kedua harus memiliki aturan eksplisit dan tervalidasi.
- Laporan mencakup tipe layanan, router, paket, price snapshot, first login, expiry, status, batch voucher, dan ringkasan pendapatan berdasarkan pembayaran (bukan sekadar nominal katalog).

## 7. Model Data Minimum

- `routers`: konfigurasi NAS/API terenkripsi, status, hasil test koneksi.
- `plans`: tipe layanan, rate, durasi, harga, group, concurrent session, aktif.
- `voucher_batches`, `vouchers`: format batch dan lifecycle voucher.
- `members`: tipe Hotspot/PPPoE, router scope, lifecycle, snapshot harga.
- `radcheck`, `radreply`, `radusergroup`, `radacct`, `nas`: tabel FreeRADIUS standar.
- `billing_invoices`, `payments`: billing dan referensi payment gateway masa depan.
- `service_action_logs`, `activity_log`: audit domain dan audit model.

Database harus memiliki unique/index untuk username, batch code, RADIUS group, NAS name, invoice periode, dan kolom filter utama (`status`, `router_id`, `expired_at`, `acctstoptime`). Semua migrasi harus satu versi final; jangan mempertahankan dua migration yang membuat tabel sama.

## 8. Kebutuhan Non-Fungsional

| Area | Target |
|---|---|
| Kinerja | Generate 100 voucher < 15 detik; daftar dipaginasi dan indexed. |
| Reliabilitas | Tidak ada write parsial antara data aplikasi dan tabel RADIUS. |
| Keamanan | HTTPS, CSRF, session aman, rate limit, secret encrypted, log tersanitasi. |
| Ketersediaan | Job queue memiliki retry/backoff, failed-job monitoring, dan idempotency key. |
| Audit | Semua aksi sensitif memiliki aktor, timestamp, entitas, before/after, dan correlation ID. |
| Responsif | Fitur inti berfungsi pada 375px, 768px, dan 1280px+. |
| Observabilitas | Error RouterOS/FreeRADIUS tercatat tanpa password/secret; dashboard kesehatan job tersedia. |

## 9. Deployment dan Operasional PM2

Untuk deployment ini, **Cloudflare Tunnel** meneruskan HTTPS publik ke aplikasi Laravel yang berjalan pada `0.0.0.0:8000`. PM2 mengelola proses aplikasi, worker, dan scheduler. Akses langsung ke port tersebut perlu dibatasi oleh firewall bila hanya Cloudflare Tunnel yang diinginkan.

| Proses PM2 | Perintah | Tanggung jawab |
|---|---|---|
| `xd-radius-app` | `php artisan serve --host=0.0.0.0 --port=8000` | Origin HTTP untuk Cloudflare Tunnel. |
| `xd-radius-queue` | `php artisan queue:work --sleep=3 --tries=3 --timeout=60 --max-jobs=1000 --max-time=3600` | Menjalankan job sinkronisasi, billing, stale session, dan retry. |
| `xd-radius-scheduler` | `php artisan schedule:work` | Memicu jadwal aplikasi tepat satu kali. |

Ketentuan operasional:

- Scheduler hanya didefinisikan pada **satu lokasi** (`app/Console/Kernel.php` atau `routes/console.php`, pilih satu), agar job tidak berjalan ganda.
- `APP_ENV=production`, `APP_DEBUG=false`, `APP_URL` HTTPS publik, `SESSION_SECURE_COOKIE=true`, dan konfigurasi database/queue production wajib ada di `.env` server; secret tidak disalin ke `ecosystem.config.cjs`.
- Konfigurasi ingress cloudflared harus mengarah ke `http://127.0.0.1:8000` atau IP host yang dapat dijangkau cloudflared. Laravel mempercayai proxy agar URL HTTPS, cookie secure, dan IP client diterjemahkan dengan benar.
- Worker dibatasi `max-jobs`, `max-time`, dan memori agar secara berkala dimulai ulang dengan kode baru dan tidak bocor memori.
- Setiap deploy menjalankan: `composer install --no-dev --optimize-autoloader`, build asset, migration yang sudah dibackup dan diuji, `php artisan optimize`, lalu `php artisan queue:restart`.
- PM2 harus disetel startup/persist (`pm2 startup`, `pm2 save`) dan log dipantau/dirotasi menggunakan `pm2-logrotate` atau logrotate sistem.
- Health check `/up`, status queue failed jobs, dan status PM2 dipantau. Deployment gagal bila migration, cache warmup, atau health check gagal.

## 10. Test dan Definition of Done

Sebuah fitur dianggap selesai hanya bila:

1. Policy/role dan validasi server-side diterapkan.
2. Migration baru dapat dijalankan dari database kosong dan upgrade database lama teruji.
3. Unit test mencakup aturan domain; feature test mencakup akses role dan endpoint utama.
4. Skenario gagal diuji: duplikasi username, router tidak terhubung, job diulang, pembayaran ulang, dan rollback transaction.
5. Aksi sensitif menghasilkan audit log dan tidak menulis secret ke log.
6. Tampilan desktop serta mobile diverifikasi pada viewport target.

## 11. Prioritas Rilis

### Rilis 1 — Fondasi aman

Onboarding, RBAC, CRUD dan test router, NAS sync, paket, audit log, serta migration bersih.

### Rilis 2 — Operasi pelanggan

Voucher batch/print, member Hotspot/PPPoE, provisioning RADIUS, lifecycle expiry, isolir/aktif, monitoring `radacct` dan stale reconciliation.

### Rilis 3 — Komersial dan otomasi

Billing, PDF, laporan, disconnect/CoA dengan retry, serta dashboard operasional.

## 12. Keputusan yang Perlu Dikonfirmasi

1. Apakah akun PPPoE harus dibatasi ke router tertentu, atau boleh autentikasi pada semua NAS aktif?
2. Apakah status `isolated` harus menolak total (RADIUS reject) atau mengizinkan akses ke halaman pembayaran melalui walled garden? Jika opsi kedua dipilih, desain firewall harus menjadi requirement eksplisit.
3. Untuk voucher, durasi dimulai saat login pertama atau saat dicetak/dijual?
4. Apakah operator boleh mencatat pembayaran, dan apakah pembayaran parsial diperlukan?
5. Apakah RouterOS API/CoA wajib pada rilis awal, atau disconnect manual cukup sebagai fallback?
