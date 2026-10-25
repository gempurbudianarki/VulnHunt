# Platform Lomba Bug Bounty UBBG (UBBG VulnHunt)

Platform resmi pelaksanaan kompetisi bug bounty kampus Universitas Bina Bangsa Getsempena (UBBG). Platform ini mengelola seluruh siklus kompetisi mulai dari pendaftaran tim mahasiswa, verifikasi NDA, pelaporan celah keamanan berbasis CVSS v3.1, sistem triage laporan oleh panitia, hingga papan peringkat (*leaderboard*).

---

## 1. Kebutuhan Server (Server Requirements)

Untuk menjalankan platform ini dengan stabil di lingkungan produksi maupun pengujian beban penuh, konfigurasi PHP (`php.ini`) minimal berikut wajib dipenuhi:

| Parameter Konfigurasi | Nilai Minimal | Nilai Rekomendasi | Keterangan |
| :--- | :--- | :--- | :--- |
| `post_max_size` | **256M** | **512M** | Menerima payload laporan temuan beserta hingga 8 berkas lampiran (PoC video/gambar/dokumen). |
| `upload_max_filesize` | **25M** | **50M** | Batas maksimal per berkas lampiran sesuai spesifikasi lomba (25 MB per berkas). |
| `memory_limit` | **512M** | **1024M** | Pemrosesan citra bukti eksploitasi beresolusi tinggi (hingga 4096x4096px) menggunakan Intervention Image / GD. |
| `max_execution_time` | **120s** | **300s** | Waktu eksekusi yang cukup untuk upload berkas berukuran besar dan pemrosesan resize/strip EXIF. |
| `max_input_time` | **120s** | **300s** | Waktu parsing data input formulir upload. |

> [!WARNING]
> **Catatan Konfigurasi Lingkungan Lokal (Laragon):**
> Pada lingkungan pengembangan lokal bawaan Laragon, nilai konfigurasi umumnya jauh lebih longgar (`post_max_size = 2G`, `upload_max_filesize = 2G`, `memory_limit = 512M`). Pastikan server produksi dikonfigurasi secara eksplisit sesuai tabel di atas dan tidak mengandalkan default php-fpm minimal (seperti default 2M/8M) yang akan menyebabkan unggahan terpotong.

---

## 2. Catatan Keamanan Kritis: Kunci Enkripsi (`APP_KEY`)

Seluruh muatan teknis eksploitasi dan rincian celah keamanan pada tabel `reports`:
- `description` (Deskripsi celah)
- `steps_to_reproduce` (Langkah-langkah reproduksi eksploitasi)
- `impact` (Dampak kerentanan)
- `remediation` (Rekomendasi perbaikan)

disimpan di database dalam bentuk **ciphertext terenkripsi** menggunakan algoritma AES-256-CBC melalui Laravel Cryptographic Services dengan basis kunci `APP_KEY` pada file `.env`.

> [!CAUTION]
> **PERINGATAN INTEGRITAS & KETERSEDIAAN DATA:**
> Jika `APP_KEY` hilang, ter-reset (`php artisan key:generate`), atau berganti, **seluruh data laporan kerentanan yang telah dikirim oleh peserta TIDAK AKAN BISA DIDEKRIPSI LAGI SECARA PERMANEN**.
> 
> **Wajib bagi Panitia / Sysadmin:**
> 1. Simpan salinan cadangan (*backup*) `APP_KEY` di tempat terpisah yang aman (misalnya: *secure vault*, password manager terenkripsi panitia, atau *cold storage* fisik).
> 2. Jangan pernah melakukan `php artisan key:generate` setelah platform mulai menerima laporan peserta!

---

## 3. Catatan Keamanan untuk Triager & Juri (Pemeriksa Laporan)

Berkas lampiran yang diunggah oleh peserta (gambar, rekaman video PoC, dokumen PDF, teks, atau markdown) berasal dari lingkungan peserta eksternal dan berpotensi memuat berkas berbahaya, eksploitasi *parser*, atau malware.

> [!CAUTION]
> **PROTOKOL PEMERIKSAAN BERKAS LAMPIRAN BAGI TRIAGER:**
> 1. **Gunakan Lingkungan Terisolasi**: Selalu unduh dan buka berkas lampiran di dalam **Virtual Machine (VM) terisolasi** (*sandbox*) atau browser dengan profil terisolasi (misalnya disposable browser instance).
> 2. **Jangan Eksekusi Langsung di Host Utama**: Jangan pernah mengekstrak, mengeksekusi, atau membuka dokumen lampiran peserta secara langsung di komputer kerja utama Anda tanpa isolasi.
> 3. Meskipun server telah membersihkan metadata EXIF, melarang berkas SVG, dan memaksa unduhan sebagai attachment (`nosniff`), risiko *zero-day* pada penampil berkas pihak ketiga (PDF viewer/video player) tetap ada.

---

## 4. Alur Autentikasi Staf & Keamanan Panel Filament

1. **Akses Panel Admin & Triager (`/admin`)**:
   - Hanya akun dengan peran `admin` atau `triager` yang memiliki izin akses.
   - Hak akses ditegakkan melalui implementasi `FilamentUser::canAccessPanel()`.
2. **Wajib Two-Factor Authentication (2FA TOTP)**:
   - Panel admin mewajibkan 2FA TOTP aktif untuk seluruh staf, termasuk di lingkungan `local`.
   - Halaman login bawaan Filament (`/admin/login`) dinonaktifkan demi mencegah bypass tantangan 2FA. Seluruh staf diarahkan login melalui pipeline Fortify (`/login`).
   - Akun staf yang baru dibuat wajib mengganti password awal dan mengaktifkan TOTP 2FA pada login pertama sebelum dapat membuka panel admin.

---

## 5. Menjalankan Pengujian (Testing)

Pengujian unit dan fitur dijalankan menggunakan Pest PHP:

```bash
# Jalankan seluruh rangkaian pengujian fitur
php artisan test

# Jalankan pengujian konkurensi database nyata (MySQL InnoDB Locking)
php artisan test --filter=MySqlConcurrencyTest
```

---

## 6. Kebutuhan Menjalankan Queue Worker di Produksi (Supervisor / Cron)

Sistem notifikasi email platform (perubahan status laporan `ReportStatusChangedNotification` dan pembaruan verifikasi NDA `NdaStatusChangedNotification`) mengimplementasikan kontrak `ShouldQueue` agar performa request HTTP tetap cepat dan responsif tanpa terhambat latensi server SMTP pihak ketiga.

> [!IMPORTANT]
> **PERINGATAN KRITIS OPERASIONAL PRODUKSI:**
> Tanpa adanya proses *queue worker* yang berjalan aktif di latar belakang server, **seluruh email notifikasi kepada peserta dan panitia TIDAK AKAN PERNAH TERKIRIM** (pekerjaan pengiriman email akan terus tertumpuk di tabel antrean database `jobs`).
> 
> Di lingkungan pengembangan lokal, antrean dapat disetel langsung memproses dengan `QUEUE_CONNECTION=sync` pada file `.env`. Namun di server produksi dengan driver `database` atau `redis`, Anda **wajib** menjalankan proses daemon *queue worker* secara persisten menggunakan **Supervisor** atau **Systemd**:
> 
> ```bash
> php artisan queue:work --sleep=3 --tries=3 --max-time=3600
> ```
> 
> Contoh konfigurasi **Supervisor** (`/etc/supervisor/conf.d/vulnhunt-worker.conf`):
> ```ini
> [program:vulnhunt-worker]
> process_name=%(program_name)s_%(process_num)02d
> command=php /var/www/vulnhunt/artisan queue:work database --sleep=3 --tries=3 --max-time=3600
> autostart=true
> autorestart=true
> stopasgroup=true
> killasgroup=true
> user=www-data
> numprocs=2
> redirect_stderr=true
> stdout_logfile=/var/www/vulnhunt/storage/logs/worker.log
> stopwaitsecs=3600
> ```


---

## 7. Konfigurasi Zona Waktu Tunggal (WIB - Asia/Jakarta)

Platform VulnHunt menggunakan konvensi zona waktu tunggal: **WIB (Asia/Jakarta / UTC+7)** di seluruh lapisan sistem (aplikasi, database, CLI, web, dan antrean).

- Konfigurasi pada .env:
  `ini
  APP_TIMEZONE=Asia/Jakarta
  `
- Konfigurasi koneksi MySQL (config/database.php):
  `php
  'timezone' => '+07:00',
  `
- **Penyimpanan Database**: Seluruh kolom waktu (
eports.submitted_at, nnouncements.published_at, 	eams.created_at, dan konfigurasi competition_settings) disimpan langsung dalam zona waktu WIB murni (Y-m-d H:i:s), tanpa konversi bolak-balik UTC. Hal ini memudahkan query langsung oleh panitia di database dan mencegah kesalahan kalkulasi tenggat waktu lomba.

---

## 8. Akun Demo & Perlindungan Lingkungan Produksi

Untuk mempermudah pengujian di lingkungan lokal dan testing, tersedia DemoAccountsSeeder:

`ash
# Hanya jalankan di local / testing
php artisan db:seed --class=DemoAccountsSeeder
`

> [!CAUTION]
> **PERLINDUNGAN LINGKUNGAN PRODUKSI:**
> 1. DemoAccountsSeeder **DILARANG KERAS** dan otomatis melempar RuntimeException jika dijalankan pada APP_ENV=production.
> 2. DatabaseSeeder utama tidak memanggil DemoAccountsSeeder secara default.
> 3. **Tidak ada kredensial hardcode**: Password akun demo dibaca dari file .env (DEMO_ADMIN_PASSWORD, DEMO_TRIAGER_PASSWORD, DEMO_PARTICIPANT_PASSWORD). Jika variabel tidak diisi, seeder akan membuat password acak 16 karakter yang kuat secara kriptografis (Str::password(16)) dan mencetaknya **satu kali saja** ke tabel konsol CLI.
