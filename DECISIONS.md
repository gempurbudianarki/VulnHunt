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
  2. Batas dimensi gambar diperluas hingga maksimal 6000x6000 piksel dengan pemeriksaan `getimagesize()` sebelum *decode* ke memori. Jika melebihi batas 6000 piksel, sistem mengembalikan pesan error yang jelas berisi dimensi berkas asli vs batas maksimal untuk mencegah serangan *decompression bomb / pixel flood DoS*.
  3. Berkas gambar yang lolos verifikasi dimensi diproses otomatis dengan `scaleDown(2500, 2500)` via Intervention Image untuk mereduksi beban memori dan storage tanpa mengurangi keterbacaan tanda tangan fisik, serta membuang seluruh metadata EXIF dan GPS.
  4. Hash SHA-256 berkas dihitung dari berkas akhir hasil olahan (setelah resize dan EXIF dibuang) dan dicatat di `report_attachments.sha256` atau `nda_submissions.signed_file_sha256`.
  5. Seluruh unduhan berkas lampiran (termasuk `.txt`, `.md`, dan `.pdf`) **selalu** dikirim dengan header HTTP `Content-Disposition: attachment; filename="..."` dan `X-Content-Type-Options: nosniff`. Berkas **tidak pernah** disajikan secara `inline` di browser.
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
- **Alasan**: Menghargai hak tim untuk melengkapi informasi temuan yang diminta panitia tanpa tersandera oleh keterbatasan slot laporan baru.
- **Alternatif**: Memblokir revisi saat kuota penuh (merugikan tim yang ingin merespons permintaan admin).

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

## 9. Penanganan Memori & Dimensi Gambar Unggahan (5900x5900 Benchmark)
- **Keputusan**: 
  1. Batas maksimal dimensi piksel gambar ditetapkan pada 6000x6000px.
  2. Berdasarkan benchmark riil pada PHP 8.4 GD, pemrosesan citra 5900x5900 truecolor mengonsumsi memori puncak sekitar 246–274 MB dan waktu proses ~2,9 detik. Laragon dengan `memory_limit: 512M` mampu memproses ini secara aman.
  3. Namun, untuk mengantisipasi lonjakan beban konkuren atau lingkungan produksi dengan `memory_limit` lebih rendah, seluruh proses `ImageManager::read()` dan `scaleDown(2500, 2500)` dibungkus dalam blok `try ... catch (\Throwable $e)` yang menangkap *memory exhaustion* atau kerusakan berkas, lalu mengembalikan respons validasi 422 yang ramah pengguna alih-alih error 500 fatal.
- **Alasan**: Mencegah crash PHP-FPM dan memberikan informasi diagnostik yang jelas kepada pengguna.
