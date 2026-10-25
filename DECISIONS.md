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
  2. `CompetitionSettings` wajib menggunakan cache bersama yang persisten antar-proses (driver cache Laravel seperti `file`, `database`, atau `redis`, bukan static array in-memory per-proses).
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

---

## 12. Konvensi Tunggal Zona Waktu WIB (Asia/Jakarta)
- **Keputusan**: 
  1. Seluruh aplikasi menggunakan konvensi tunggal: **WIB (Asia/Jakarta)** di seluruh lapisan sistem (aplikasi, CLI, web request, Blade, Filament, dan Queue).
  2. `APP_TIMEZONE=Asia/Jakarta` pada konfigurasi aplikasi (`config/app.php`).
  3. Koneksi MySQL (`mysql` dan `mysql_test` pada `config/database.php`) menggunakan `'timezone' => '+07:00'`.
  4. Semua kolom datetime (`reports.submitted_at`, `announcements.published_at`, `teams.created_at`, dan nilai di tabel `competition_settings`) disimpan dan dibaca secara konsisten dalam format WIB (`Y-m-d H:i:s`), tanpa konversi bolak-balik UTC.
  5. Pengujian jendela pendaftaran dan pengiriman laporan serta transisi mode papan peringkat dievaluasi tepat pada tepi detik menggunakan waktu lokal WIB (`now()`).
- **Alasan**: Mencegah kebingungan operasional panitia dan peserta, menghilangkan risiko *off-by-hours* akibat konversi zona waktu parsial, dan memastikan audit trail basis data selaras dengan jam resmi lomba di Indonesia.

---

## 13. Sanitasi Markdown Pengumuman (League CommonMark Bawaan Laravel)
- **Keputusan**: 
  1. Seluruh sanitasi regex kustom buatan sendiri pada MarkdownSanitizer dihapus dan digantikan secara penuh oleh pustaka resmi **League CommonMark** bawaan Laravel framework melalui facade `Illuminate\Support\Str::markdown`.
  2. Opsi konfigurasi ditetapkan secara ketat:
     - `'html_input' => 'strip'`: Membuang seluruh tag HTML mentah seperti `<script>`, `<img>`, `<iframe>`, `<svg>`, `<object>`, `<embed>` sehingga tidak ada tag HTML yang lolos ke DOM output.
     - `'allow_unsafe_links' => false`: Menetralkan skema URL berbahaya (seperti `javascript:`, `data:`, `vbscript:`) pada sintaks tautan markdown inline `[text](url)`, reference link `[text][ref]`, autolink `<url>`, dan image `![text](url)` dengan membuang atribut href atau src.
  3. Hasil sanitasi diverifikasi secara menyeluruh menggunakan test suite otomatis terhadap berbagai variasi payload XSS, obfuscation case (`JaVaScRiPt:`), entity encoding (`&#106;avascript:`), dan attribute event injection.
- **Alasan**: Standar keamanan industri melarang regex kustom untuk parsing HTML/Markdown karena rawan bypass parser differential. Pustaka League CommonMark telah teruji luas dan terintegrasi langsung di Laravel.

---

## 14. Papan Peringkat Agregat Bebas N+1 & Keamanan Mode Tersembunyi
- **Keputusan**: 
  1. Peringkat dihitung menggunakan satu query SQL agregat efisien (`SUM(reports.points_awarded)`, `COUNT(reports.id)`, dll) dengan pengurutan tie-break 4 tingkat:
     - Tingkat 1: Total Poin DESC
     - Tingkat 2: Jumlah Temuan Critical DESC
     - Tingkat 3: Jumlah Temuan High DESC
     - Tingkat 4: Waktu Validasi Terakhir ASC (paling awal menang)
     - Tingkat 5: Team ID ASC (deterministik mutlak)
  2. Tim berstatus diskualifikasi (`teams.status = 'disqualified'`) disaring secara mutlak di tingkat query SQL.
  3. Kolom yang disajikan publik ke peserta dibatasi secara ketat: nama tim, total poin, dan jumlah temuan valid (tanpa mengungkap identitas personal anggota tim atau NIM).
  4. Mode visibilitas mendukung 3 kondisi:
     - `visible`: Terbuka bebas untuk umum.
     - `hidden`: Tertutup penuh untuk umum (hanya Admin yang dapat melihat).
     - `hidden_until_end`: Otomatis terbuka tepat pada saat `submission_close_at` terlewati (jika `submission_close_at` bernilai null, mode tetap tertutup permanen).
  5. Saat mode hidden aktif, request JSON / API mengembalikan status 403 Forbidden dengan payload data kosong `[]`, dan tampilan HTML tidak memuat data tabel tersembunyi.
  6. Cache hasil peringkat (`vulnhunt_leaderboard_rankings`) disentralisasi pada satu titik invalidasi tunggal: `LeaderboardService::invalidateCache()`, dipanggil otomatis saat ada mutasi status laporan, perhitungan poin, diskualifikasi tim, atau perubahan pengaturan lomba.
- **Alasan**: Mencegah kebocoran kompetitif sebelum waktu pengumuman resmi dan menjaga performa tinggi database tanpa beban kalkulasi berulang.

---

## 15. Keamanan Ekspor CSV & Mitigasi Formula Injection (DDE)
- **Keputusan**: 
  1. Fitur ekspor CSV untuk laporan dan papan peringkat adalah hak akses eksklusif Admin (Triager dan Peserta mendapat respons 403 Forbidden).
  2. Mitigasi CSV Formula Injection (DDE Attack) diterapkan pada seluruh sel data teks: setiap sel yang diawali karakter formula berbahaya (`=`, `+`, `-`, `@`, `\t`, `\r`) otomatis diberi prefix karakter kutip tunggal (`'`).
  3. Aliran unduhan berkas menggunakan `streamDownload` Laravel dengan memori efisien (`O(1)`) untuk mencegah *memory exhaustion* pada ribuan baris data.
  4. Berkas diawali dengan Byte Order Mark UTF-8 (`\xEF\xBB\xBF`) agar karakter non-ASCII ditampilkan sempurna di Microsoft Excel dan LibreOffice Calc.
  5. Setiap unduhan ekspor dicatat ke Spatie Activity Log mencakup nama user pengunduh, IP, waktu, jenis data yang diekspor, dan jumlah baris data.
- **Alasan**: Melindungi perangkat workstation administrator dari serangan eksekusi perintah arbitrer via spreadsheet aplikasi yang mengolah data laporan kiriman peserta.

---

## 16. Pengelolaan Pengaturan Lomba & Peniadaan Kredensial Hardcode
- **Keputusan**: 
  1. Panel Filament `CompetitionSettingResource` terhubung langsung ke service singleton `CompetitionSettings` sebagai single source of truth validasi dan fallback default resmi.
  2. Seluruh kredensial hardcode dihapus dari repositori. DemoAccountsSeeder membaca password dari variabel lingkungan (`DEMO_ADMIN_PASSWORD`, `DEMO_TRIAGER_PASSWORD`, `DEMO_PARTICIPANT_PASSWORD`) atau menghasilkan password acak kriptografis 16 karakter (`Str::password(16)`) yang hanya ditampilkan sekali di konsol CLI saat seeder dijalankan.
  3. DemoAccountsSeeder dilindungi dengan *environment guard*: melempar `RuntimeException` jika dijalankan di environment `production`, dan `DatabaseSeeder` tidak memanggil seeder demo di lingkungan produksi.
- **Alasan**: Mencegah pembocoran kredensial statis ke repositori Git publik/privat dan menegakkan prinsip pertahanan berlapis (defense-in-depth).

---

## 17. Pengerasan Header Keamanan & CSP Nonce Dinamis (`SecurityHeaders`)
- **Keputusan**:
  1. Middleware `SecurityHeaders` dipasang secara global pada grup `web` dan panel Filament `AdminPanelProvider`.
  2. Menyertakan header standar industri:
     - `X-Content-Type-Options: nosniff`
     - `Referrer-Policy: strict-origin-when-cross-origin`
     - `Permissions-Policy: camera=(), microphone=(), geolocation=(), payment=()`
     - `X-Frame-Options: SAMEORIGIN`
     - `Strict-Transport-Security: max-age=31536000; includeSubDomains` (hanya diaktifkan pada HTTPS di lingkungan produksi).
  3. Content Security Policy (CSP) menggunakan kriptografis nonce dinamis per request (`Vite::useCspNonce($nonce)`).
  4. Pengendalian CSP dimulai dalam mode `Content-Security-Policy-Report-Only` (dikontrol melalui konfigurasi `config('security.csp_report_only')`), dan dapat diubah menjadi `Content-Security-Policy` pemblokiran aktif tanpa mengubah kode.
- **Alasan**: Memitigasi serangan XSS, Clickjacking, dan MIME confusion tanpa mematahkan pustaka UI modern seperti Alpine.js, Livewire, dan Filament.

---

## 18. Kebijakan Batas Waktu Idle Sesi & Pembatalan Sesi Perangkat Lain
- **Keputusan**:
  1. Batas inaktivitas (*idle timeout*) dibedakan secara tegas berdasarkan hak akses:
     - Staf Panitia (Admin & Triager): **30 menit**.
     - Peserta Mahasiswa: **120 menit**.
  2. Sesi diperiksa pada middleware `EnforceSessionIdleTimeout` setelah `StartSession`. Jika waktu idle terlampaui, sesi otomatis dihapus, user di-logout, dan dialihkan ke login dengan pesan informatif.
  3. Setiap pembaruan kata sandi oleh pengguna (`UpdateUserPassword` dan `SecuritySetupController`) wajib memanggil `Auth::logoutOtherDevices($password)` untuk membatalkan seluruh sesi login di perangkat lain secara seketika.
- **Alasan**: Mengurangi jendela kerentanan pembajakan sesi pada perangkat panitia yang tertinggal tanpa pengawasan.

---

## 19. Halaman Galat Kustom Bergaya Claymorphism Tanpa Jejak Informasi Internal
- **Keputusan**:
  1. Seluruh halaman galat standar (403, 404, 419, 429, 500, 503) diimplementasikan secara terpadu di `resources/views/errors/` menggunakan bahasa desain Claymorphism yang selaras dengan tema platform.
  2. Pada kondisi `APP_DEBUG=false`, halaman galat tidak memuat jejak stack trace, konfigurasi server, versi PHP, query SQL, maupun informasi sensitif laporan.
  3. Galat 500 menyajikan kode insiden unik dan mengarahkan pengguna melapor ke panitia tanpa membocorkan rincian teknis backend.
- **Alasan**: Mencegah kebocoran informasi internal (*information disclosure*) kepada aktor jahat.

---

## 20. Perlindungan Akses Langsung Berkas Privat Storage (HTTP 404)
- **Keputusan**:
  1. Seluruh berkas bukti eksploitasi dan formulir pendaftaran NDA disimpan pada disk `private` (`storage/app/private`), BUKAN di `storage/app/public`.
  2. Symlink `public/storage` hanya mengarah ke `storage/app/public` (hanya untuk asset publik yang diizinkan).
  3. Akses HTTP langsung ke URL `/storage/private/*` atau upaya path traversal mengembalikan respons HTTP 404 Not Found.
  4. Pengunduhan berkas privat wajib melalui rute controller terautentikasi (`reports.attachment.download` dan `verification.download`) yang memverifikasi izin tim dan pencatatan audit log staf.
- **Alasan**: Mencegah akses tidak terotorisasi terhadap temuan kerentanan kritis kampus.

---

## 21. Pemeriksaan Kesiapan Produksi Otomatis (`php artisan vulnhunt:preflight`)
- **Keputusan**:
  1. Dibuat perintah CLI bawaan `php artisan vulnhunt:preflight` yang memverifikasi 12 kriteria keamanan dan operasional sebelum platform dideploy ke publik:
     - `APP_DEBUG` bernilai false.
     - `APP_ENV` bernilai `production`.
     - `APP_URL` menggunakan protokol `https://`.
     - Tidak ada akun demo atau email pengujian pada basis data.
     - Seluruh akun staf telah mengganti kata sandi bawaan (`must_change_password = false`).
     - `MAIL_MAILER` bukan `log`.
     - `QUEUE_CONNECTION` bukan `sync`.
     - `CACHE_STORE` bukan `array`.
     - `APP_KEY` terisi dan valid.
     - `SESSION_SECURE_COOKIE` bernilai `true`.
     - Berkas privat di `storage/app/private` tidak dapat diakses lewat symlink `public/storage`.
     - Mode CSP telah dinaikkan dari Report-Only ke Active Enforcement (`CSP_REPORT_ONLY=false`).
  2. Perintah mengembalikan kode keluar **0** jika seluruh 12 kriteria lulus, dan **1** jika ada kriteria yang gagal.
- **Alasan**: Menghilangkan kelalaian konfigurasi (*human error*) saat peralihan dari lingkungan staging ke produksi.

---

## 22. Pencegahan N+1 Query Melalui `Model::preventLazyLoading(! app()->isProduction())`
- **Keputusan**:
  1. Diaktifkan `Model::preventLazyLoading(! app()->isProduction())` pada `AppServiceProvider::boot()`.
  2. Seluruh kueri pada portal peserta, controller, kueri peringkat, kalkulator diminishing returns, dan panel Filament dipastikan menggunakan eager loading (`with(...)`) untuk semua relasi.
  3. Pelanggaran lazy loading langsung melempar `LazyLoadingViolationException` pada tahap pengembangan dan automated test, sehingga masalah performa terdeteksi dini sebelum menyentuh produksi.
- **Alasan**: Menjamin skalabilitas sistem dan kecepatan respon saat ratusan peserta mengakses portal secara bersamaan.

---

## 23. Kebijakan CSP (Content-Security-Policy), Evaluasi Alpine.js, Livewire & Filament
- **Keputusan**:
  1. Halaman publik (`/`, `/login`), portal peserta (`/app/*`), dan panel admin (`/admin/*`) dilindungi dengan middleware `SecurityHeaders`.
  2. Implementasi menyertakan cryptographic nonce dinamis per request (`'nonce-...'`) yang diinjeksikan ke `<script>` via `Vite::useCspNonce()`.
  3. Direktif `script-src` menyertakan `'unsafe-eval'` untuk panel admin karena Filament v3 dan Livewire v3 secara fundamental membutuhkan evaluasi runtime JavaScript untuk hidrasi state komponen dan evaluasi ekspresi wire.
  4. Pengujian browser aktual dengan mode CSP Enforce membuktikan bahwa evaluasi inline event handler (`@click`, `x-on`) dan dynamic execution di Alpine/Livewire memicu pelanggaran konsol browser (`Refused to evaluate a string as JavaScript because 'unsafe-eval' is not an allowed source of script in the following Content Security Policy directive`).
  5. Oleh karena itu, kebijakan CSP dijalankan dalam mode terdokumentasi dengan perlindungan nonce ketat untuk script eksternal, dan mode *enforce* aktif di produksi tidak diaktifkan sebelum terbukti tidak merusak fungsionalitas interaktif halaman.
- **Alasan**: Mencegah pemblokiran fungsionalitas inti aplikasi (form submission, interactive CVSS calculator, triage actions di Filament) oleh browser pengguna.

