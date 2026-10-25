# Platform Lomba Bug Bounty UBBG (UBBG VulnHunt)

Platform resmi pelaksanaan sayembara bug bounty kampus Universitas Bina Bangsa Getsempena (UBBG). Platform ini mengelola seluruh siklus kompetisi mulai dari pendaftaran tim mahasiswa, verifikasi dokumen NDA bermaterai, pelaporan celah keamanan berbasis kalkulator CVSS v3.1, antrean triage dan penilaian berbobot oleh panitia, hingga papan peringkat (*leaderboard*) publik dan ekspor audit.

---

## 1. Kebutuhan Sistem & Konfigurasi Server

| Parameter | Nilai Minimal | Rekomendasi Produksi | Catatan Kritis |
| :--- | :--- | :--- | :--- |
| **PHP** | 8.2+ | 8.2 atau 8.3 | Ekstensi: `pdo_mysql`, `gd`, `intl`, `mbstring`, `openssl`, `bcmath`, `fileinfo` |
| **MySQL / MariaDB** | MySQL 8.0+ / MariaDB 10.6+ | MySQL 8.0.30+ | Wajib InnoDB storage engine untuk mendukung `SELECT ... FOR UPDATE` |
| **Node.js** | 18+ | 20+ LTS | Untuk kompilasi Vite frontend |
| `post_max_size` | **256M** | **512M** | Menerima payload laporan temuan beserta hingga 8 berkas lampiran (PoC) |
| `upload_max_filesize` | **25M** | **50M** | Batas maksimal per berkas lampiran (25 MB per berkas) |
| `memory_limit` | **512M** | **1024M** | Pemrosesan sanitasi gambar beresolusi hingga 4096x4096px via Intervention Image |
| `max_execution_time` | **120s** | **300s** | Waktu pemrosesan upload dan penghapusan EXIF |

---

## 2. Instalasi & Setup Cepat (Quickstart)

```bash
# 1. Clone repositori & masuk ke direktori
cd /path/to/lombasaiber

# 2. Salin environment file
cp .env.example .env

# 3. Pasang dependensi PHP & Node.js
composer install --no-dev --optimize-autoloader
npm ci

# 4. Generate Application Key (KUNCI INI HARUS DICADANGKAN TERPISAH)
php artisan key:generate

# 5. Jalankan migrasi basis data
php artisan migrate --force

# 6. Kompilasi asset frontend produksi
npm run build

# 7. Hubungkan storage publik (hanya untuk aset statis non-privat)
php artisan storage:link

# 8. Verifikasi kesiapan server produksi
php artisan vulnhunt:preflight
```

---

## 3. Daftar Lengkap Variabel Lingkungan (`.env`)

```ini
# ==============================================================================
# IDENTITAS APLIKASI & KEAMANAN DASAR
# ==============================================================================
APP_NAME="VulnHunt UBBG"
APP_ENV=production                  # local, testing, atau production
APP_KEY=                            # Kunci AES-256 (Dilarang dirotasi sembarangan)
APP_DEBUG=false                     # WAJIB false di lingkungan produksi
APP_TIMEZONE=Asia/Jakarta           # WAJIB Asia/Jakarta (WIB)
APP_URL=https://vulnhunt.ubbg.ac.id # WAJIB HTTPS di produksi

# ==============================================================================
# PENGATURAN LOGGING & PEMELIHARAAN
# ==============================================================================
LOG_CHANNEL=stack
LOG_DEPRECATIONS_CHANNEL=null
LOG_LEVEL=error

# ==============================================================================
# KONEKSI BASIS DATA (MYSQL DENGAN TIMEZONE +07:00)
# ==============================================================================
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=lombasaiber
DB_USERNAME=vulnhunt_user
DB_PASSWORD=SuperSecretPasswordSecure123!

# Koneksi Database Pengujian (Digunakan oleh Pest/PHPUnit & MySqlConcurrencyTest)
DB_TEST_DATABASE=lombasaiber_test
DB_TEST_USERNAME=vulnhunt_test
DB_TEST_PASSWORD=SecretTestPass123!

# ==============================================================================
# SESI & COOKIE KEAMANAN
# ==============================================================================
SESSION_DRIVER=database             # database atau redis (hindari 'file' di multi-node)
SESSION_LIFETIME=120                # 120 menit (idle timeout dikontrol via middleware)
SESSION_ENCRYPT=false
SESSION_PATH=/
SESSION_DOMAIN=null
SESSION_SECURE_COOKIE=true          # WAJIB true di HTTPS (Secure Cookie Flag)
SESSION_HTTP_ONLY=true              # WAJIB true (HttpOnly Flag)
SESSION_SAME_SITE=lax               # Lax (SameSite Cookie Defense)

# ==============================================================================
# SISTEM KEAMANAN VULNHUNT (SECURITY HEADERS, CSP, & IDLE TIMEOUT)
# ==============================================================================
CSP_REPORT_ONLY=false               # true saat peluncuran uji coba, false saat produksi aktif
IDLE_TIMEOUT_STAFF=30               # 30 menit sesi idle untuk Admin & Triager
IDLE_TIMEOUT_PARTICIPANT=120        # 120 menit sesi idle untuk Peserta Mahasiswa

# ==============================================================================
# SISTEM ANTREAN & CACHE
# ==============================================================================
QUEUE_CONNECTION=database           # WAJIB database atau redis (DILARANG sync di produksi)
CACHE_STORE=database                # database atau redis (DILARANG array di produksi)
CACHE_PREFIX=vulnhunt_cache_

# ==============================================================================
# SISTEM SURAT ELEKTRONIK (SMTP RESMI KAMPUS)
# ==============================================================================
MAIL_MAILER=smtp                    # DILARANG 'log' di lingkungan produksi
MAIL_HOST=smtp.ubbg.ac.id
MAIL_PORT=587
MAIL_USERNAME=no-reply@ubbg.ac.id
MAIL_PASSWORD=SmtpPasswordSecure456!
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS="no-reply@ubbg.ac.id"
MAIL_FROM_NAME="Panitia VulnHunt UBBG"

# ==============================================================================
# KREDENSIAL DEMO SEEDER (HANYA UNTUK LOCAL & TESTING)
# ==============================================================================
# Variabel ini diabaikan dan dilempar exception di production
DEMO_ADMIN_PASSWORD=
DEMO_TRIAGER_PASSWORD=
DEMO_PARTICIPANT_PASSWORD=
```

---

## 4. Panduan Deploy Produksi

### A. Menjalankan Queue Worker (Supervisor)
Platform mengantrekan seluruh email notifikasi (`ReportStatusChangedNotification` dan `NdaStatusChangedNotification`) pada antrean latar belakang. Jika worker mati, email tidak akan pernah sampai.

Konfigurasi Supervisor (`/etc/supervisor/conf.d/vulnhunt-worker.conf`):
```ini
[program:vulnhunt-worker]
process_name=%(program_name)s_%(process_num)02d
command=php /var/www/vulnhunt/artisan queue:work database --sleep=3 --tries=3 --max-time=3600
autostart=true
autorestart=true
stopasgroup=true
killasgroup=true
user=www-data
numprocs=2
redirect_stderr=true
stdout_logfile=/var/www/vulnhunt/storage/logs/worker.log
stopwaitsecs=3600
```
Muat dan jalankan:
```bash
sudo supervisorctl reread
sudo supervisorctl update
sudo supervisorctl start vulnhunt-worker:*
```

### B. Menjadwalkan Laravel Cron Scheduler
Jadwalkan cron bawaan server Linux untuk mengeksekusi scheduler Laravel setiap menit:
```bash
* * * * * cd /var/www/vulnhunt && php artisan schedule:run >> /dev/null 2>&1
```

### C. Izin Berkas Storage & Isolasi Privat
Berkas bukti eksploitasi dan formulir NDA tersimpan pada disk privat (`storage/app/private`).
```bash
# Pastikan kepemilikan oleh pengguna web server
sudo chown -R www-data:www-data /var/www/vulnhunt/storage /var/www/vulnhunt/bootstrap/cache

# Izin direktori privat: hanya pemilik yang berhak membaca/menulis
sudo chmod -R 750 /var/www/vulnhunt/storage
sudo chmod -R 700 /var/www/vulnhunt/storage/app/private
sudo find /var/www/vulnhunt/storage/app/private -type f -exec chmod 600 {} +
```

### D. Opsi Integrasi Antivirus ClamAV
Untuk memindai setiap lampiran PoC terhadap virus/trojan secara *real-time*:
1. Pasang ClamAV daemon di server:
   ```bash
   sudo apt-get install clamav clamav-daemon
   sudo systemctl enable clamav-daemon
   sudo systemctl start clamav-daemon
   ```
2. Pada middleware upload atau form request, Anda dapat mengaktifkan socket scan via `clamdscan`:
   ```bash
   clamdscan --fdpass --no-summary /path/to/uploaded/file
   ```
   Jika kode keluar bukan 0, batalkan upload dan tolak berkas dengan pesan infeksi.

---

## 5. Panduan Administrator

1. **Akses Panel Admin**:
   - URL: `https://vulnhunt.ubbg.ac.id/admin`
   - Login menggunakan akun ber-role `admin`. Autentikasi 2FA TOTP wajib diaktifkan pada login perdana.
2. **Pengaturan Lomba (`Competition Settings`)**:
   - Buka menu **Konfigurasi Kompetisi**.
   - Atur jendela pendaftaran (`registration_open_at` & `registration_close_at`) dan pengiriman laporan (`submission_open_at` & `submission_close_at`) dalam waktu WIB.
   - Tetapkan batas kuota laporan aktif tim (default: 5) dan ukuran tim (min: 2, max: 4).
   - Tentukan mode papan peringkat:
     - `visible`: Terbuka bebas untuk publik.
     - `hidden`: Disembunyikan total dari peserta umum (hanya admin yang dapat melihat).
     - `hidden_until_end`: Otomatis tertutup hingga jendela waktu pengiriman laporan berakhir.
3. **Verifikasi NDA Peserta**:
   - Buka menu **Verifikasi NDA**.
   - Periksa keabsahan identitas KTP/KTM, tanda tangan, dan materai. Setujui (*Approve*) atau tolak (*Reject*) dengan alasan yang jelas.
4. **Ekspor Data & Audit**:
   - URL Ekspor Resmi: `/admin/export/reports` dan `/admin/export/leaderboard`.
   - Data otomatis disanitasi dari formula injection (DDE injection).

---

## 6. Panduan Triager (Pemeriksa Laporan)

1. **Tugas & Tanggung Jawab**:
   - Triager bertugas memvalidasi keaslian, keparahan (CVSS v3.1), dan kelayakan laporan celah keamanan.
   - Triager **DILARANG** memverifikasi NDA atau mengubah pengaturan lomba (hak eksklusif Admin).
2. **SOP WAJIB: Membuka Lampiran di Virtual Machine (VM) Terisolasi**:
   > [!CAUTION]
   > Berkas lampiran dikirim oleh pihak luar dan berpotensi memuat muatan eksploitasi zero-day, script berbahaya, atau malware parser.
   > 
   > 1. **Dilarang keras** membuka lampiran langsung di komputer kerja utama tanpa isolasi.
   > 2. Unduh lampiran melalui panel triage, lalu pindahkan dan buka berkas di dalam **Virtual Machine (VM) terisolasi** (misal: VirtualBox/VMware/QEMU) atau sandbox browser terpisah.
   > 3. Jangan membuka tautan eksternal yang disertakan peserta tanpa proxy investigasi terisolasi.
3. **Siklus Hidup Triage**:
   - Ubah status dari `submitted` ke `in_review` saat mulai menganalisis temuan.
   - Jika butuh info tambahan, ubah status ke `needs_info`. Peserta dapat membalas tanpa mengurangi kuota aktif.
   - Tetapkan status akhir: `valid` (dengan poin), `duplicate` (merujuk ID laporan utama), atau `invalid`.
   - Laporan yang telah berstatus `rewarded` hanya dapat dibuka kembali (*reopen*) oleh Administrator.

---

## 7. Panduan Peserta Singkat

1. **Pendaftaran Akun**:
   - Wajib menggunakan email resmi kampus berdomain `@ubbg.ac.id`.
   - Lakukan verifikasi email melalui tautan yang dikirimkan.
2. **Pembentukan Tim**:
   - Buat tim baru atau gabung menggunakan kode undangan 8 digit dari ketua tim Anda.
   - Satu mahasiswa hanya dapat bergabung dalam satu tim.
3. **Verifikasi NDA**:
   - Unduh template NDA resmi di menu **Verifikasi Dokumen**.
   - Tanda tangani di atas materai, unggah pindaian PDF dokumen Anda, dan tunggu persetujuan panitia.
4. **Pelaporan Temuan**:
   - Pastikan target berada dalam ruang lingkup (*In-Scope*).
   - Tuliskan langkah reproduksi secara detail (minimal 100 karakter).
   - Lampirkan bukti (gambar maksimal 4096px, video MP4/WebM, atau dokumen pendukung non-SVG).
   - Pantau status temuan Anda pada menu **Daftar Laporan**.

---

## 8. Prosedur Backup & Restore

### A. Prinsip Pemisahan Kunci (`APP_KEY`)
Seluruh payload kerentanan (`description`, `steps_to_reproduce`, `impact`, `remediation`) tersimpan sebagai ciphertext terenkripsi menggunakan kunci `APP_KEY`.

> [!IMPORTANT]
> **ATURAN MUTLAK BACKUP:**
> Salinan `APP_KEY` **WAJIB DISIMPAN SECARA TERPISAH** dari berkas dump database SQL.
> Jika file backup database bocor, penyerang tidak akan dapat membaca isi laporan tanpa `APP_KEY`. Sebaliknya, jika server rusak, Anda wajib memiliki salinan `APP_KEY` yang sama untuk merestore data.

### B. Prosedur Pencadangan Harian (Backup Routine)
```bash
#!/bin/bash
TIMESTAMP=$(date +"%Y%m%d_%H%M%S")
BACKUP_DIR="/var/backups/vulnhunt"

# 1. Dump database MySQL
mysqldump -u vulnhunt_user -p --single-transaction --quick --lock-tables=false lombasaiber > "$BACKUP_DIR/db_$TIMESTAMP.sql"

# 2. Arsipkan berkas privat storage
tar -czf "$BACKUP_DIR/storage_private_$TIMESTAMP.tar.gz" -C /var/www/vulnhunt/storage/app/private .

# 3. Enkripsi berkas backup sebelum dipindahkan ke offsite storage
gpg --symmetric --cipher-algo AES256 "$BACKUP_DIR/db_$TIMESTAMP.sql"
gpg --symmetric --cipher-algo AES256 "$BACKUP_DIR/storage_private_$TIMESTAMP.tar.gz"

# Hapus plaintext lokal
rm "$BACKUP_DIR/db_$TIMESTAMP.sql" "$BACKUP_DIR/storage_private_$TIMESTAMP.tar.gz"
```

### C. Prosedur Pemulihan Bencana (Disaster Recovery)
1. Siapkan server target dengan dependensi PHP dan MySQL yang identik.
2. Pasang repositori kode dan salin berkas `.env` yang memuat nilai `APP_KEY` asli.
3. Pulihkan database MySQL:
   ```bash
   mysql -u vulnhunt_user -p lombasaiber < db_restored.sql
   ```
4. Ekstrak kembali berkas privat ke `storage/app/private`:
   ```bash
   tar -xzf storage_private_restored.tar.gz -C /var/www/vulnhunt/storage/app/private/
   chown -R www-data:www-data /var/www/vulnhunt/storage/app/private
   ```
5. Jalankan `php artisan vulnhunt:preflight` untuk memverifikasi integritas sistem pasca pemulihan.

---

## 9. Prosedur Darurat Tanggap Kebocoran / Insiden Keamanan

Jika terjadi indikasi kompromi server atau kebocoran kredensial:

1. **Karantina Akses Publik Seketika**:
   Aktifkan mode pemeliharaan darurat:
   ```bash
   php artisan down --secret="emergency-admin-token-2026" --render="errors.503"
   ```
2. **Rotasi Kunci & Pembatalan Sesi Massal**:
   - Bersihkan seluruh tabel sesi untuk mengeluarkan paksa semua pengguna:
     ```bash
     php artisan db:table sessions --truncate
     ```
   - Flush seluruh cache dan token otorisasi:
     ```bash
     php artisan cache:clear
     ```
   - Ganti password basis data MySQL dan perbarui file `.env`.
3. **Audit Jejak Aktivitas**:
   Periksa tabel `activity_log` untuk mengidentifikasi akun yang disusupi atau aktivitas tidak wajar:
   ```bash
   php artisan tinker --execute="App\Models\Activity::latest()->take(50)->get(['causer_id', 'description', 'created_at']);"
   ```
4. **Notifikasi Resmi Sivitas**:
   Kirimkan pemberitahuan resmi kepada seluruh peserta melalui pengumuman internal kampus dan instruksikan penggantian kata sandi email UBBG.

---

## 10. Menjalankan Uji Otomatis & Preflight

```bash
# Jalankan seluruh 152 automated test suite (Pest PHP)
php artisan test

# Jalankan pengujian konkurensi database nyata (MySQL 10-way parallel test)
php scripts/test_real_concurrency.php

# Jalankan command preflight produksi komprehensif
php artisan vulnhunt:preflight
```
