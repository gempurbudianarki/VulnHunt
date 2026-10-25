# Log Keputusan Teknis (DECISIONS.md)

Dokumen ini mencatat semua keputusan teknis, arsitektural, dan keamanan yang diambil untuk hal-hal yang tidak dirinci secara eksplisit di dalam spesifikasi utama (`PROMPT_CLAUDE_CODE_BUGBOUNTY_UBBG.md`). Prinsip yang digunakan adalah opsi paling sederhana, paling aman, dan teruji.

---

## 1. Format Kode Laporan (`reports.code`)
- **Keputusan**: Format `BB-{YYYY}-{NNNN}` dibuat dengan padding 4 digit dari `reports.id` (contoh: ID 1 menjadi `BB-2026-0001`). Kode di-generate otomatis di dalam event model `created` atau callback database transaction segera setelah ID diperoleh.
- **Alasan**: Sederhana, deterministik, unik, dan tidak memerlukan tabel counter sequence terpisah.
- **Alternatif**: Nomor urut per-tahun independen (membutuhkan tabel sekuens terpisah atau lock query agregat yang rawan contention).

---

## 2. Regenerasi Dokumen NDA pada Penolakan (`nda_submissions`) & Imutabilitas Status Approved
- **Keputusan**: 
  1. Saat NDA ditolak oleh admin dan peserta mengunduh/mengunggah ulang, sistem membuat record baru di `nda_submissions` dengan nomor dokumen baru (`NDA-{tahun}-{user_id 4 digit}-{4 karakter hex baru}`). Record lama dipertahankan dengan status `rejected` sebagai riwayat audit.
  2. Gerbang akses NDA wajib memeriksa status dari record NDA terbaru peserta.
  3. **Imutabilitas Status Approved**: Peserta yang NDA-nya sudah berstatus `approved` **dilarang keras** mengunggah ulang dokumen NDA agar status kelayakannya tidak dapat dijatuhkan kembali menjadi `pending` atau `rejected` oleh tindakan peserta sendiri.
- **Alasan**: Mencegah tabrakan hash SHA-256, menjaga integritas verifikasi admin, dan mencegah peserta merusak status akses dirinya atau timnya secara tidak sengaja.
- **Alternatif**: Menimpa record lama atau mengizinkan upload bebas saat sudah approved.

---

## 3. Isolasi Log Aktivitas Terhadap Data Terenkripsi (Whitelist / `logOnly`)
- **Keputusan**: 
  1. Spatie Activity Log dikonfigurasi menggunakan **whitelist eksplisit (`logOnly`)**, BUKAN blacklist. Hanya atribut metadata non-sensitif (`status`, `severity_final`, `points_awarded`, `quality_factor`, `duplicate_of`) yang boleh dicatat.
  2. Seluruh kolom payload kerentanan (`description`, `steps_to_reproduce`, `impact`, `remediation`) tidak pernah masuk ke log aktivitas.
  3. Komentar internal admin dan komentar tim tidak boleh mencantumkan payload mentah, dan email notifikasi hanya memuat kode laporan, judul, dan status, tanpa menyertakan deskripsi PoC kerentanan.
  4. Snapshot di tabel `report_versions` disimpan dalam bentuk ciphertext terenkripsi (`encrypted:array` cast).
- **Alasan**: Mencegah kerentanan *data leakage* di mana isi laporan yang belum di-patch tersimpan dalam bentuk plaintext di tabel `activity_log`, `notifications`, atau email log.
- **Alternatif**: Mencatat seluruh atribut model dengan pengecualian manual (rawan bocor jika ada kolom baru).

---

## 4. Perlindungan Upload File, Pelarangan SVG, & Batasan Dimensi Gambar
- **Keputusan**: 
  1. Bukti eksploitasi dan dokumen NDA hanya menerima MIME type aman: gambar (`png, jpg, jpeg, webp`), video (`mp4, webm`), dokumen teks (`pdf, txt, md`). Format SVG ditolak secara mutlak meskipun berstatus gambar.
  2. Batas dimensi gambar ditetapkan maksimal 4096x4096 piksel dengan pemeriksaan `getimagesize()` sebelum *decode* ke memori. Jika melebihi batas 4096 piksel, sistem mengembalikan pesan error yang jelas berisi dimensi berkas asli vs batas maksimal untuk mencegah serangan *decompression bomb / pixel flood DoS*.
  3. Berkas gambar yang lolos verifikasi dimensi diproses otomatis dengan `scaleDown(2500, 2500)` via Intervention Image untuk mereduksi beban memori dan storage tanpa mengurangi keterbacaan bukti, serta membuang seluruh metadata EXIF dan GPS.
  4. Hash SHA-256 berkas dihitung dari berkas akhir hasil olahan (setelah resize dan EXIF dibuang) dan dicatat di `report_attachments.sha256` atau `nda_submissions.signed_file_sha256`.
  5. Seluruh unduhan berkas lampiran (termasuk `.txt`, `.md`, dan `.pdf`) **selalu** dikirim dengan header HTTP `Content-Disposition: attachment; filename="..."` dan `X-Content-Type-Options: nosniff`. Nama file unduhan disanitasi menggunakan `basename` dan regex pembersih karakter berbahaya. Berkas **tidak pernah** disajikan secara `inline` di browser.
- **Alasan**: File SVG dan file teks/PDF inline dapat memicu Stored XSS di browser panitia atau peserta. Dimensi raksasa dapat menguras memori server jika didecode langsung tanpa pengecekan dimensi awal.
- **Alternatif**: Mengizinkan SVG dengan sanitasi XML, menyajikan PDF secara inline, atau decode gambar tanpa limit dimensi.

---

## 5. Penyimpanan Pengaturan Lomba (`competition_settings`) dengan Cache Bersama
- **Keputusan**: 
  1. Nilai pengaturan disimpan sebagai JSON valid di kolom `value` (`{"value": ...}`) dengan fallback default terdefinisi di class `App\Services\CompetitionSettings`.
  2. `CompetitionSettings` wajib menggunakan cache bersama yang persisten antar-proses (driver cache Laravel seperti `file` atau `database`, bukan static array in-memory per-proses).
  3. Setiap pembaruan pengaturan di Filament otomatis meng-invalidasi cache bersama ini sehingga perubahan langsung terbaca di seluruh worker dan web request.
  4. Form panel pengaturan memvalidasi tipe dan rentang nilai tiap key secara ketat sesuai spesifikasi (kuota 1-50, tim 1-10, bobot non-negatif).
- **Alasan**: Konsistensi status antar multi-worker dan web request tanpa jeda cache yang salah.

---

## 6. Penanganan Kuota Laporan Saat Status `needs_info` (Invarian Opsi B)
- **Keputusan**: 
  1. **Invarian Kuota**: Kuota laporan aktif (`submitted` + `in_review`) **HANYA menghalangi pengiriman laporan baru**.
  2. Transisi status `needs_info -> in_review` yang dipicu oleh balasan atau revisi tim atas permintaan admin **dikecualikan (exempt)** dari pemeriksaan kuota laporan aktif tim.
  3. Jika tim sedang memiliki jumlah laporan aktif $\ge$ kuota (misal 5 laporan), dan memiliki 1 laporan berstatus `needs_info`, tim **tetap diizinkan** membalas atau merevisi laporan `needs_info` tersebut sehingga menjadi `in_review` (jumlah aktif menjadi 6 sementara).
  4. Pengiriman laporan baru (`/app/laporan/baru`) tetap **wajib ditolak** jika jumlah laporan aktif saat itu $\ge$ kuota.
  5. Peserta / tim **dilarang keras** mengubah status laporannya sendiri menjadi `needs_info` (transisi ke `needs_info` adalah hak eksklusif admin/triager).
  6. Peserta tidak memiliki hak untuk mengedit bebas laporan yang telah dikirim (`submitted`, `in_review`, `triaged`, dll). Revisi langkah reproduksi hanya diizinkan pada saat laporan berstatus `needs_info` melalui formulir balasan `reports.reply`.
- **Alasan**: Menghargai hak tim untuk melengkapi informasi temuan yang diminta panitia tanpa tersandera oleh keterbatasan slot laporan baru, sekaligus menjaga imutabilitas laporan yang sedang diuji.

---

## 7. Rate Limiter Registrasi Dinamis untuk NAT Kampus
- **Keputusan**: Batas laju pendaftaran akun baru dinaikkan dari 10 menjadi 60 per jam per IP, dan dikonfigurasi secara dinamis melalui `competition_settings` dengan kunci `registration_rate_limit_per_hour`.
- **Alasan**: Di lingkungan kampus, ratusan mahasiswa sering kali mengakses internet dari balik satu IP publik/NAT WiFi kampus yang sama. Batas 10 per jam terlalu ketat dan dapat memblokir pendaftar yang sah, sementara batas 60 per jam yang dapat disesuaikan panitia memberikan fleksibilitas tinggi tanpa mengorbankan perlindungan dari spam massal.
- **Alternatif**: Nilai statis hardcode di middleware atau tanpa rate limit.

---

## 8. Deaktivasi Login Native Filament & Sentralisasi Autentikasi ke Fortify
- **Keputusan**: Halaman login bawaan Filament (`/admin/login`) dinonaktifkan secara penuh (`->login()` dihapus). Seluruh akses staf dan admin diarahkan melalui alur Fortify (`/login`).
- **Alasan**: Halaman login bawaan Filament secara default hanya memverifikasi email dan password tanpa mengeksekusi pipeline tantangan TOTP 2FA Fortify (`RedirectIfTwoFactorAuthenticatable`). Dengan menonaktifkan `/admin/login` dan mengalihkan pengunjung panel yang belum terautentikasi ke route Fortify, staf diwajibkan menyelesaikan tantangan TOTP 6 digit sebelum sesi login tercipta.
- **Alternatif**: Mengizinkan login Filament terpisah (membuka celah bypass 2FA bagi staf yang memiliki 2FA aktif).

---

## 9. Penanganan Memori & Dimensi Gambar Unggahan (Maksimal 4096x4096px)
- **Keputusan**: 
  1. Batas maksimal dimensi piksel gambar diturunkan ke 4096x4096px sesuai arahan keamanan.
  2. Seluruh proses `ImageManager::read()` dan `scaleDown(2500, 2500)` dibungkus dalam blok `try ... catch (\Throwable $e)` yang menangkap *memory exhaustion* atau kerusakan berkas, lalu mengembalikan respons validasi 422 yang ramah pengguna alih-alih error 500 fatal.
- **Alasan**: Mencegah crash PHP-FPM dan memberikan informasi diagnostik yang jelas kepada pengguna.

---

## 10. Kapasitas Unggah Lampiran (php.ini Laragon vs Aturan 8 Berkas x 25 MB)
- **Keputusan**: 
  1. Pemeriksaan konfigurasi lingkungan Laragon menunjukkan:
     - `upload_max_filesize = 2G`
     - `post_max_size = 2G`
     - `memory_limit = 512M`
  2. Aturan spesifikasi lomba membatasi pengunggahan lampiran hingga maksimal 8 berkas x 25 MB = 200 MB per laporan temuan.
  3. Karena total batas muatan lomba (200 MB) jauh di bawah batas server Laragon (2 GB), pengunggahan seluruh berkas lampiran secara langsung dalam satu request formulir (`multipart/form-data`) berjalan aman, tanpa risiko terpotong atau gagal akibat `ValidatePostSize`.
  4. Pengamanan di sisi aplikasi tetap ditegakkan:
     - Pemeriksaan `attachments.* => 'max:25600'` (maks 25 MB per berkas) dan `attachments => 'max:8'` (maksimal 8 berkas).
     - Deteksi MIME fisik berbasis `finfo` (melarang manipulasi ekstensi palsu).
     - Pelarangan mutlak berkas berformat SVG (`image/svg+xml`).
     - Pemeriksaan dimensi `getimagesize()` (maks 4096x4096px) sebelum manipulasi citra untuk mencegah *decompression bomb*.
     - Pembersihan seluruh metadata EXIF dan GPS serta normalisasi ukuran dengan `scaleDown(2500, 2500)` via Intervention Image v3.
     - Seluruh berkas lampiran disimpan di disk `private` dan disajikan dengan header `Content-Disposition: attachment; filename="..."` serta `X-Content-Type-Options: nosniff`.
- **Alasan**: Efisiensi alur pengguna (tidak memerlukan upload async yang rumit) dengan tetap menjamin keamanan mutlak dan isolasi IDOR antar-tim.

---

## 11. Rate Limiting Fase 2 (Spesifikasi Bagian 8.9) & Jendela Waktu Laporan
- **Keputusan**:
  1. **Batas Laju Fase 2**:
     - Pengiriman Laporan: **10 per jam per tim** (`report_rate_limit_per_hour`).
     - Pengunggahan Berkas: **20 per menit per pengguna** (`upload_rate_limit_per_minute`). Jika request formulir tidak menyertakan berkas, rate limiter upload mengembalikan `Limit::none()` sehingga tidak mengurangi kuota pengguna.
     - Komentar / Balasan Laporan: **30 per menit per pengguna** (`comment_rate_limit_per_minute`).
     - Seluruh nilai batas dibaca dinamis dari `CompetitionSettings` dengan fallback nilai default di atas.
  2. **Jendela Waktu Pengiriman Laporan (`submission_open_at` & `submission_close_at`)**:
     - Kunci pengaturan disinkronkan tepat sesuai spesifikasi: `submission_open_at` dan `submission_close_at`.
     - **Perilaku saat bernilai `null`**:
       - `submission_open_at = null`: Tidak ada batas awal (pengiriman terbuka sejak awal).
       - `submission_close_at = null`: Tidak ada batas akhir (pengiriman dibuka tanpa batas waktu/deadline).
       - Kedua kunci bernilai `null`: Jendela pengiriman laporan berstatus terbuka bebas (unrestricted).
     - Jika waktu saat ini (`now()`) berada sebelum `submission_open_at` atau sesudah `submission_close_at`, pengiriman laporan baru ditolak oleh server dengan pesan validasi error.
