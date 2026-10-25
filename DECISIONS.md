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
  1. Bukti eksploitasi hanya menerima MIME type aman: gambar (`png, jpg, jpeg, webp`), video (`mp4, webm`), dokumen teks (`pdf, txt, md`). Format SVG ditolak secara mutlak meskipun berstatus gambar.
  2. File gambar bukti wajib divalidasi batas dimensi pikselnya (maksimal 4000x4000 piksel) sebelum diproses, untuk mencegah serangan *decompression bomb / pixel flood DoS*.
  3. Semua gambar bukti diproses ulang menggunakan `intervention/image` untuk membuang seluruh metadata EXIF dan GPS.
  4. Hash SHA-256 berkas dihitung dari berkas akhir hasil olahan (setelah EXIF dibuang) dan dicatat di `report_attachments.sha256`.
  5. Seluruh unduhan berkas lampiran (termasuk `.txt`, `.md`, dan `.pdf`) **selalu** dikirim dengan header HTTP `Content-Disposition: attachment; filename="..."` dan `X-Content-Type-Options: nosniff`. Berkas **tidak pernah** disajikan secara `inline` di browser.
- **Alasan**: File SVG dan file teks/PDF inline dapat memicu Stored XSS di browser panitia atau peserta. Dimensi raksasa dapat menguras memori server.
- **Alternatif**: Mengizinkan SVG dengan sanitasi XML atau menyajikan PDF secara inline.

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
