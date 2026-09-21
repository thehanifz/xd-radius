# Panduan Operasional RadiusManager

Panduan ini ditujukan untuk superuser dan operator pada RadiusManager.

## 1. Akses dan peran

Masuk melalui URL panel yang diberikan administrator. Jangan membagikan akun; setiap operator harus memiliki akun sendiri agar riwayat aksi dapat ditelusuri.

- **Superuser** mengelola router, paket, operator, pengaturan, laporan, dan seluruh operasi pelanggan.
- **Operator** mengelola voucher/member dan memantau sesi online sesuai wewenang yang diberikan.

Jika akun dinonaktifkan, login dan sesi aktif akan ditolak.

## 2. Urutan setup pertama (superuser)

0. **Prasyarat server & FreeRADIUS:** sebelum membuat akun superuser, pastikan administrator sudah menjalankan `./setup.sh check` dan `sudo ./setup.sh setup` di server (lihat [`01-server-setup.md`](./01-server-setup.md) dan [`02-freeradius-setup.md`](./02-freeradius-setup.md)). Tanpa langkah ini, FreeRADIUS belum terhubung ke database aplikasi dan router tidak akan bisa autentikasi meski data sudah dibuat di panel.
1. Buka `/setup` dan buat akun superuser pertama.
2. Login lalu buka **Pengaturan Sistem**.
3. Isi nama aplikasi, SSID yang akan tercetak di voucher, threshold stale session, dan kebijakan isolir invoice overdue.
4. Tambahkan router pada menu **Router / NAS**:
   - Nama dan lokasi router.
   - IP/FQDN, port API, username, dan password API.
   - RADIUS shared secret yang sama dengan konfigurasi RADIUS di MikroTik.
5. Setelah router disimpan, sistem otomatis menjadwalkan sinkronisasi ke FreeRADIUS (status `pending` → `in_sync`/`failed`). Buka `/settings/freeradius` untuk memantau status sinkronisasi ini — tidak perlu edit tabel `nas` atau restart FreeRADIUS secara manual lagi.
6. Aktifkan Interim Update pada MikroTik, misalnya setiap 1–5 menit. Set threshold stale minimal dua kali interval tersebut.
7. Buat paket layanan sebelum membuat voucher atau member.

## 3. Menambah paket

Pada menu **Paket**, buat paket sesuai layanan:

- Nama dan tipe paket.
- Harga.
- Kecepatan download/upload dalam Kbps.
- Durasi aktif dalam hari.
- Nama RADIUS group yang unik.

Nonaktifkan paket yang sudah tidak dijual. Jangan menghapus paket yang masih dipakai akun pelanggan.

## 4. Membuat dan mencetak voucher Hotspot

1. Buka **Voucher → Generate Voucher**.
2. Pilih paket voucher, lalu isi prefix (opsional), panjang total, jenis karakter, dan jumlah voucher.
3. Periksa preview format sebelum menyimpan.
4. Setelah batch berhasil dibuat, buka daftar voucher dan filter berdasarkan batch.
5. Pilih **Print A4** atau **Print Thermal**, periksa preview, lalu gunakan tombol print browser.

Username voucher sama dengan password. Perlakukan kartu voucher seperti uang tunai dan jangan membagikannya melalui kanal publik.

## 5. Membuat member Hotspot atau PPPoE

1. Buka **Member → Tambah Member**.
2. Isi username unik, password yang berbeda dari username, paket, jumlah sesi bersamaan, dan catatan pelanggan.
3. Periksa tanggal aktif serta jatuh tempo yang dihasilkan.
4. Simpan. Sistem menulis atribut autentikasi dan limit layanan ke FreeRADIUS.

Jangan mengubah data RADIUS langsung dari database/CLI kecuali melalui prosedur pemulihan; tindakan tersebut dapat membuat status panel dan RADIUS tidak sinkron, dan akan terdeteksi sebagai **drift** oleh sistem (lihat Bagian 9.2).

## 6. Mengisolir atau mengaktifkan layanan

1. Buka detail member lalu pilih **Isolir** atau **Aktifkan**.
2. Pastikan username dan perubahan status benar di dialog konfirmasi.
3. Konfirmasi aksi. Pilihan "jangan tanya lagi" hanya berlaku untuk akun operator yang sedang login dan dapat direset dari preferensi akun.

Pada rilis awal, isolir memblokir autentikasi pada RADIUS. Sesi yang sudah terhubung mungkin baru berhenti setelah sesi berakhir, kecuali disconnect/CoA telah dikonfigurasi dan berhasil dijalankan.

## 7. Monitoring sesi online

Menu **User Online** menampilkan sesi dengan `acctstoptime` kosong dari FreeRADIUS.

- Gunakan filter tipe user, NAS, dan status.
- Badge **Diduga Putus** berarti accounting tidak menerima pembaruan melebihi threshold. Data tidak dihapus.
- Halaman refresh otomatis setiap 30 detik; tombol refresh tersedia untuk pembaruan manual.

Jika banyak sesi stale muncul, periksa konektivitas router ke FreeRADIUS dan konfigurasi Interim Update pada MikroTik.

## 8. Billing

1. Buka **Billing** dan pilih invoice member.
2. Periksa periode, nominal, dan status sebelum mencatat pembayaran.
3. Gunakan **Bayar** untuk mencatat pembayaran atau pilih perpanjangan bila masa aktif harus diperbarui dari tanggal jatuh tempo sebelumnya.
4. Unduh PDF invoice bila perlu dikirim atau diarsipkan.

Jangan mencatat pembayaran yang sama dua kali. Invoice yang sudah lunas/cancelled harus diverifikasi oleh superuser sebelum tindakan lanjutan.

## 9. Manajemen FreeRADIUS (superuser)

Halaman **`/settings/freeradius`** adalah pusat kendali management plane FreeRADIUS. Gunakan ini sebagai pengganti SSH untuk operasional rutin.

### 9.1 Membaca status di halaman ini

- **Environment** — menampilkan apakah FreeRADIUS terdeteksi, versi, status service, config directory, konektivitas RADIUS DB, dan status akses privileged Laravel (`Ready`/`Not Ready`).
- **Health** — enam kriteria wajib: service aktif, config valid (`-XC`), database reachable, SQL module bisa query, tabel wajib (`radcheck`, `radreply`, `radacct`, `nas`) tersedia, listener port 1812/1813 terbuka. Semua harus `PASS` agar status keseluruhan `Healthy/Ready`.
- **Configuration Reconciliation** — tombol **Run Reconciliation** menjalankan ulang pipeline Generate → Validate → Apply → Health Check tanpa perlu SSH, berguna setelah mengubah banyak router sekaligus atau setelah drift terdeteksi.

### 9.2 Kapan menjalankan "Run Reconciliation" vs "Setup / Re-setup"

| Situasi | Aksi |
|---|---|
| Router baru ditambahkan/diubah/dihapus | Otomatis — sistem memicu `RunConfigurationJob` sendiri, tidak perlu aksi manual |
| Status router `pending` terlalu lama atau `failed` | Klik **Run Reconciliation** di `/settings/freeradius` |
| Drift terdeteksi (file config berubah manual, atau data `nas` tidak sesuai `routers`) | Periksa detail drift di audit trail, lalu **Run Reconciliation** setelah penyebab drift dipahami — jangan langsung timpa tanpa tahu sebabnya |
| Server baru, FreeRADIUS belum pernah di-setup aplikasi ini | Klik **Setup / Re-setup**, atau jalankan `sudo ./setup.sh setup` dari terminal |
| Upgrade/reinstall FreeRADIUS di level OS | Jalankan ulang `sudo ./setup.sh setup` dari terminal (bukan cukup tombol UI), karena ini melibatkan deteksi versi dan path config baru |

### 9.3 Audit trail

Setiap operasi (`SETUP`, `CONFIG_UPDATE`, `NAS_SYNC`, `VALIDATION`, `APPLY`, `RESTART`/`RELOAD`, `ROLLBACK`, `DRIFT_DETECTED`, `MIGRATION`) tercatat di tabel `radius_management_operations` dan activity log — tidak menyimpan secret/password. Gunakan ini untuk investigasi jika ada laporan router tidak bisa autentikasi setelah perubahan konfigurasi.

## 10. Operasional server (administrator)

Cloudflare Tunnel meneruskan HTTPS publik ke Laravel pada `0.0.0.0:8000`. PM2 menjalankan aplikasi, worker, dan scheduler:

```bash
pm2 start ecosystem.config.cjs
pm2 save
pm2 status
```

Pastikan konfigurasi cloudflared memakai origin berikut:

```yaml
ingress:
  - hostname: radius.contoh-domain.com
    service: http://127.0.0.1:8000
  - service: http_status:404
```

Ganti hostname dengan domain Anda, kemudian pastikan `APP_URL` di `.env` memakai URL HTTPS yang sama. Jika akses langsung ke port 8000 tidak diperlukan, batasi melalui firewall/router.

Setelah deploy kode baru:

```bash
composer install --no-dev --optimize-autoloader
npm ci
npm run build
php artisan migrate --force
php artisan optimize
php artisan queue:restart
pm2 reload ecosystem.config.cjs --update-env
```

Jika deploy menyertakan perubahan pada management plane FreeRADIUS (service/job baru di bawah `app/Services/Radius` atau `app/Jobs/Radius`), jalankan juga `sudo ./setup.sh setup` setelah migrasi untuk memastikan konfigurasi FreeRADIUS ikut disinkronkan ulang — jangan hanya mengandalkan restart PM2.

Sebelum deploy, backup PostgreSQL dan pastikan migration telah diuji pada salinan database. Periksa juga endpoint `/up`, `pm2 status`, `/settings/freeradius` (Health harus tetap `Healthy/Ready`), dan failed jobs setelah deploy.

## 11. Penanganan masalah cepat

| Gejala | Pemeriksaan awal |
|---|---|
| Tidak bisa login | Status akun, URL HTTPS, cookie secure, dan log Laravel. |
| User tidak bisa autentikasi | Username, `radcheck`, paket/group, shared secret NAS, dan log FreeRADIUS. Cek juga status `radius_sync_status` router terkait di `/settings/freeradius` — kalau `failed`/`pending`, autentikasi lewat router itu belum tentu berfungsi. |
| Sesi online stale | Interim Update MikroTik, koneksi ke FreeRADIUS, dan threshold stale. |
| Job tidak berjalan | `pm2 status`, tabel `jobs`/`failed_jobs`, serta log worker. |
| Router gagal dihubungi | IP/port/API credential, firewall router, dan hasil test connection. |
| FreeRADIUS berstatus `Not Healthy` di `/settings/freeradius` | Lihat kriteria mana yang `FAIL` di panel Health, cocokkan dengan tabel troubleshooting di [`02-freeradius-setup.md`](./02-freeradius-setup.md), lalu klik **Run Reconciliation** setelah penyebabnya diperbaiki. |
| Drift terdeteksi berulang | Periksa apakah ada perubahan manual via SSH ke file config FreeRADIUS atau tabel `nas` — hentikan kebiasaan itu, gunakan panel/`setup.sh` sebagai satu-satunya jalur yang didukung. |

Jangan mencantumkan password pelanggan, secret RADIUS, API password router, atau `APP_KEY` pada tiket bantuan maupun log eksternal.
