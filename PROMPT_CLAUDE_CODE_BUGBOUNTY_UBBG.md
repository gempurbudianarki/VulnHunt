# Prompt Proyek: Platform Lomba Bug Bounty Kampus UBBG

Dokumen ini adalah spesifikasi lengkap. Baca seluruhnya sebelum menulis kode.

## 0. Cara kerja yang diminta dari Claude Code

1. Kerjakan per fase (lihat bagian 15). Selesaikan satu fase, jalankan test, baru lanjut ke fase berikutnya.
2. Jika ada keputusan yang tidak tercantum di dokumen, pilih opsi yang paling sederhana dan paling aman, lalu catat di file `DECISIONS.md` (isi: keputusan, alasan, alternatif).
3. Platform ini HANYA untuk pendaftaran, verifikasi, dan pelaporan. Jangan membuat fitur pemindai, penyerang otomatis, atau alat uji apa pun.
4. Semua teks antarmuka berbahasa Indonesia. Nama tabel, kolom, class, dan variabel berbahasa Inggris.
5. Jangan hardcode angka aturan lomba (batas laporan, ukuran tim, bobot poin, jadwal). Semuanya harus bisa diubah admin lewat panel pengaturan (bagian 8.1).
6. Tulis test untuk setiap aturan akses dan setiap algoritma. Keamanan platform ini sama pentingnya dengan fiturnya, karena isinya adalah laporan kerentanan yang belum diperbaiki.

---

## 1. Ringkasan

Pihak kampus (UBBG) mengadakan lomba bug bounty internal. Mahasiswa, secara berkelompok, mencari celah keamanan pada web milik UBBG sesuai daftar scope yang ditetapkan panitia. Platform ini menangani:

- Halaman pembuka program (visi, misi, tujuan, benefit, hadiah)
- Pendaftaran akun dan tim
- Verifikasi peserta lewat upload surat perjanjian kerahasiaan (NDA) yang dicek admin
- Dasbor peserta: scope, aturan, form laporan, status laporan, papan peringkat
- Panel admin: verifikasi NDA, kelola scope, triage dan validasi laporan, pemberian poin, pengaturan lomba, pengumuman, ekspor data

Keputusan yang sudah dikunci oleh pemilik proyek:

- Lomba berbasis tim.
- NDA wajib per anggota, bukan per tim.
- Tidak ada deteksi duplikat otomatis pada versi pertama. Admin memutuskan duplikat secara manual. Namun data pendukung (target, CWE, endpoint, waktu kirim dari server) tetap wajib disimpan agar mudah ditambah nanti.
- Batas jumlah laporan dihitung per tim dan angkanya diatur admin.
- Tampilan landing page dibuat simpel.

## 2. Tujuan dan non-tujuan

Tujuan: sistem yang matang, aman, dan terasa seperti platform bug bounty resmi (HackerOne, Bugcrowd) dalam skala lomba kampus.

Non-tujuan versi pertama: deteksi duplikat otomatis, sistem pembayaran hadiah, CVSS v4.0, pembuatan sertifikat otomatis, chat real-time.

## 3. Stack teknologi

- Backend: Laravel (versi stabil terbaru), PHP 8.3+
- Database: MySQL 8
- Frontend portal peserta: Blade + Tailwind CSS + Alpine.js (tanpa SPA)
- Panel admin dan triage: Filament v3
- Autentikasi: Laravel Fortify (login, verifikasi email, reset password, 2FA TOTP)
- Peran dan izin: spatie/laravel-permission
- Log aktivitas: spatie/laravel-activitylog
- PDF template NDA: barryvdh/laravel-dompdf
- Antrean: driver `database` (email notifikasi lewat queue)
- Manipulasi gambar (strip EXIF): intervention/image
- Test: Pest
- Opsional: ClamAV untuk scan upload (jika server mendukung)

## 4. Peran

| Peran | Hak |
|---|---|
| `participant` | Daftar, buat atau gabung tim, upload NDA, lihat scope setelah disetujui, kirim dan pantau laporan tim, lihat papan peringkat |
| `admin` | Semua hal di panel admin: verifikasi NDA, triage dan validasi laporan, kelola scope, pengaturan, pengumuman, log |
| `triager` (opsional) | Hanya bisa triage laporan dan komentar, tidak bisa ubah pengaturan atau verifikasi NDA |

Admin dan triager wajib mengaktifkan 2FA sebelum bisa membuka panel.

## 5. Alur sistem

### 5.1 Alur peserta

```
Kunjungi landing page
  -> Daftar akun (nama, NIM, email kampus, HP, prodi, angkatan, password)
  -> Verifikasi email (tautan bertanda tangan)
  -> Buat tim baru (jadi ketua) ATAU gabung tim lewat kode undangan
  -> Setujui Aturan Main (versi aturan dan waktu persetujuan dicatat)
  -> Unduh template NDA yang sudah terisi otomatis (nama, NIM, tim, nomor dokumen unik)
  -> Tanda tangan, upload hasil (PDF/JPG/PNG)
  -> Status NDA: Menunggu review
        -> Admin menyetujui  -> akses dasbor penuh
        -> Admin menolak     -> alasan ditampilkan, peserta upload ulang
  -> Dasbor: lihat scope, aturan, kirim laporan, pantau status, papan peringkat
```

Gerbang akses per anggota (middleware `EnsureParticipantApproved`): halaman scope, aturan lengkap, dan form laporan hanya terbuka jika anggota tersebut sudah email terverifikasi, sudah menyetujui aturan versi terbaru, dan NDA-nya berstatus `approved`. Satu tim baru bisa mengirim laporan oleh anggota yang sudah approved saja.

### 5.2 Alur laporan

```
Peserta isi form laporan -> validasi server -> cek jendela waktu kirim
  -> cek kuota tim (dalam transaksi) -> simpan dengan status "submitted"
  -> notifikasi ke admin/triager
Admin membuka antrean triage -> mengubah status ke "in_review"
  -> kalau kurang jelas: "needs_info" + komentar (peserta membalas dan merevisi)
  -> kalau selesai: pilih "valid", "invalid", atau "duplicate" (+ pilih laporan asli)
  -> untuk "valid": tetapkan severity final, faktor kualitas, poin
  -> notifikasi ke tim
Papan peringkat diperbarui dari laporan berstatus valid
```

### 5.3 State machine laporan

Status: `submitted`, `in_review`, `needs_info`, `valid`, `invalid`, `duplicate`, `rewarded` (opsional, penanda hadiah sudah diserahkan).

Transisi yang diizinkan:

- `submitted` -> `in_review`
- `in_review` -> `needs_info`, `valid`, `invalid`, `duplicate`
- `needs_info` -> `in_review` (otomatis saat tim membalas atau merevisi)
- `valid` -> `rewarded`
- Admin boleh membuka kembali keputusan (`valid`/`invalid`/`duplicate` -> `in_review`) dengan alasan wajib, tercatat di riwayat.

Status yang dihitung sebagai "aktif" untuk kuota: `submitted` dan `in_review`. Status `needs_info` TIDAK dihitung, supaya admin bisa mengembalikan laporan tanpa merugikan tim. Status `valid`, `invalid`, `duplicate`, `rewarded` membebaskan kuota.

Setiap perubahan status dicatat di `report_status_histories` (dari, ke, oleh siapa, kapan, catatan).

---

## 6. Skema database

Gunakan migrasi Laravel, foreign key dengan index, soft delete hanya jika disebutkan.

### 6.1 Pengguna dan tim

`users`
- id, name, nim (unique), email (unique), phone, study_program, cohort_year, password, email_verified_at, two_factor_* (bawaan Fortify), rules_accepted_version (nullable), rules_accepted_at (nullable), rules_accepted_ip (nullable), is_active (bool), timestamps

`teams`
- id, name (unique), slug (unique), invite_code (unique, acak 8 karakter), leader_id (FK users), report_quota_override (nullable int), status (`active`, `disqualified`), timestamps

`team_members`
- id, team_id (FK), user_id (FK, UNIQUE agar satu orang hanya satu tim), role (`leader`, `member`), joined_at

`nda_submissions`
- id, user_id (FK), team_id (FK), document_number (unique), template_version, generated_pdf_path, signed_file_path, signed_file_sha256, status (`pending`, `approved`, `rejected`), reject_reason (nullable), reviewed_by (nullable FK users), reviewed_at (nullable), submitted_at, timestamps
- Satu pengguna boleh punya banyak riwayat, tetapi hanya satu yang aktif (yang terbaru).

### 6.2 Scope dan katalog

`scopes`
- id, name, target (domain atau URL dasar), asset_type (`web`, `api`, `other`), scope_status (`in_scope`, `out_of_scope`), criticality_weight (decimal 3,2, default 1.00, rentang 1.00 sampai 2.00), description, notes (batasan khusus, misalnya jam uji), is_active, timestamps

`vulnerability_types` (katalog CWE untuk dropdown)
- id, cwe_id (contoh `CWE-79`), name, category, is_active
- Isi lewat seeder (daftar di bagian 14).

### 6.3 Laporan

`reports`
- id, code (unique, format `BB-{tahun}-{id 4 digit}`, diisi setelah insert), team_id (FK), reported_by (FK users), scope_id (FK), vulnerability_type_id (FK)
- title, endpoint (string), parameter (nullable)
- cvss_vector_claimed, cvss_score_claimed (decimal 3,1), severity_claimed
- cvss_vector_final (nullable), cvss_score_final (nullable), severity_final (nullable)
- description, steps_to_reproduce, impact, remediation (nullable) -> keempat kolom ini memakai cast `encrypted`
- status, quality_factor (decimal 3,2, nullable), points_awarded (int, default 0), points_override_note (nullable)
- duplicate_of (nullable FK reports)
- revision_no (int, default 1)
- submitted_at (waktu server, tidak pernah diubah), triaged_by (nullable), triaged_at (nullable), closed_at (nullable), timestamps
- Index: (team_id, status), (scope_id, vulnerability_type_id), (status, submitted_at)

`report_versions` (snapshot tiap revisi)
- id, report_id, revision_no, snapshot (JSON, terenkripsi), created_by, created_at

`report_attachments`
- id, report_id, uploaded_by, original_name, stored_name (UUID acak), mime, size, sha256, disk_path, timestamps

`report_comments`
- id, report_id, user_id, body, is_internal (bool; true = catatan khusus admin, tidak terlihat peserta), timestamps

`report_status_histories`
- id, report_id, from_status, to_status, changed_by, note, created_at

### 6.4 Pengaturan dan lainnya

`competition_settings` (key-value, value berupa JSON)
- id, key (unique), value (JSON), updated_by, updated_at

`announcements`
- id, title, body, is_pinned, published_at, created_by, timestamps

Tabel pendukung: `notifications` (bawaan Laravel), `activity_log` (spatie), `jobs`, `failed_jobs`, tabel spatie permission.

---

## 7. Gambaran halaman dan rute

Publik: `/` (landing), `/login`, `/register`, `/lupa-password`.

Portal peserta (prefix `/app`, middleware `auth`, `verified`):
- `/app` ringkasan
- `/app/tim` kelola tim, kode undangan, daftar anggota dan status NDA masing-masing
- `/app/verifikasi` unduh template NDA, upload, lihat status
- `/app/aturan` aturan main dan persetujuan (versi aturan)
- `/app/scope` daftar scope (gerbang approved)
- `/app/laporan` daftar laporan tim, `/app/laporan/baru`, `/app/laporan/{code}` detail, komentar, revisi
- `/app/peringkat` papan peringkat
- `/app/pengumuman`

Panel admin (Filament, prefix `/admin`):
- Verifikasi NDA, Peserta, Tim, Scope, Katalog CWE, Laporan (antrean triage), Pengaturan Lomba, Pengumuman, Log Aktivitas, Ekspor (CSV laporan dan peringkat)

---

## 8. Algoritma dan aturan inti

### 8.1 Pengaturan lomba (semua dapat diubah admin)

Simpan di `competition_settings`. Default awal:

| Key | Default | Keterangan |
|---|---|---|
| `max_active_reports_per_team` | 5 | Batas laporan aktif per tim |
| `max_total_reports_per_team` | null | Opsional, null = tanpa batas |
| `min_team_size` / `max_team_size` | 2 / 4 | |
| `registration_open_at` / `registration_close_at` | null | Jendela pendaftaran |
| `submission_open_at` / `submission_close_at` | null | Jendela pengiriman laporan |
| `points_per_severity` | `{"critical":100,"high":60,"medium":30,"low":10,"info":2}` | |
| `duplicate_points` | 0 | Poin untuk laporan duplikat |
| `diminishing_enabled` | false | Penurunan poin untuk temuan sejenis |
| `diminishing_steps` | `[1.0,0.5,0.25]` | Pengali per temuan ke-1, ke-2, ke-3 dan seterusnya |
| `leaderboard_mode` | `visible` | `visible`, `hidden`, atau `hidden_until_end` |
| `triage_weights` | `{"critical":40,"high":25,"medium":10,"low":5,"info":1}` | |
| `triage_age_factor` | 0.5 | Tambahan prioritas per jam menunggu |
| `rules_version` | `1` | Naikkan agar semua peserta wajib setuju ulang |

Buat class `CompetitionSettings` dengan cache (invalidasi saat disimpan). Validasi di sisi server: batas laporan bilangan bulat 1 sampai 50, ukuran tim 1 sampai 10, faktor poin tidak negatif. Setiap perubahan pengaturan dicatat di log aktivitas (siapa, kapan, nilai lama, nilai baru).

### 8.2 Cek kuota laporan per tim (anti race condition)

```
DB::transaction:
  team = Team::lockForUpdate()->find(team_id)
  kuota = team.report_quota_override ?? settings.max_active_reports_per_team
  aktif = count(reports where team_id = team.id and status in [submitted, in_review])
  jika aktif >= kuota -> tolak (pesan jelas: "Kuota laporan aktif tim penuh (x dari y)")
  jika settings.max_total_reports_per_team tidak null dan total >= batas -> tolak
  jika sekarang di luar jendela submission -> tolak
  simpan laporan, set submitted_at = now() server, code = BB-{tahun}-{id}
```

Aturan tambahan:
- Menurunkan batas di tengah lomba tidak menghapus laporan yang sudah ada. Aturan baru hanya menghalangi pengiriman baru.
- Override kuota per tim (`teams.report_quota_override`) mengalahkan batas global.
- Tim berstatus `disqualified` tidak bisa mengirim laporan.

### 8.3 Kalkulator CVSS v3.1 (hitung di server)

Peserta mengisi vector lewat kalkulator interaktif di form. Server WAJIB menghitung ulang skor dari vector dan mengabaikan angka dari klien.

Parse vector `CVSS:3.1/AV:N/AC:L/PR:N/UI:N/S:U/C:H/I:H/A:H`. Bobot:

- AV: N 0.85, A 0.62, L 0.55, P 0.2
- AC: L 0.77, H 0.44
- PR: N 0.85; L 0.62 (0.68 jika Scope Changed); H 0.27 (0.5 jika Scope Changed)
- UI: N 0.85, R 0.62
- C, I, A: H 0.56, L 0.22, N 0

Rumus:

```
ISS = 1 - (1 - C) * (1 - I) * (1 - A)
Impact:
  jika S = Unchanged: 6.42 * ISS
  jika S = Changed:   7.52 * (ISS - 0.029) - 3.25 * (ISS - 0.02)^15
Exploitability = 8.22 * AV * AC * PR * UI
Base:
  jika Impact <= 0: 0
  jika S = Unchanged: Roundup(min(Impact + Exploitability, 10))
  jika S = Changed:   Roundup(min(1.08 * (Impact + Exploitability), 10))
```

Fungsi Roundup (sesuai spesifikasi CVSS 3.1):

```php
function roundup(float $x): float {
    $i = (int) round($x * 100000);
    if ($i % 10000 === 0) {
        return $i / 100000.0;
    }
    return (floor($i / 10000) + 1) / 10.0;
}
```

Peta skor ke severity: 0.0 = `info`, 0.1 sampai 3.9 = `low`, 4.0 sampai 6.9 = `medium`, 7.0 sampai 8.9 = `high`, 9.0 sampai 10.0 = `critical`.

Nilai uji wajib lolos:
- `AV:N/AC:L/PR:N/UI:N/S:U/C:H/I:H/A:H` = 9.8
- `AV:N/AC:L/PR:N/UI:R/S:C/C:L/I:L/A:N` = 6.1
- `AV:N/AC:L/PR:L/UI:N/S:U/C:H/I:N/A:N` = 6.5
- `AV:L/AC:L/PR:L/UI:N/S:U/C:H/I:H/A:H` = 7.8

Peserta hanya mengusulkan. Admin menetapkan `cvss_vector_final`, `cvss_score_final`, `severity_final`. Jika admin mengubah, wajib mengisi catatan alasan.

### 8.4 Penilaian poin

```
poin = round( base(severity_final) * scope.criticality_weight * quality_factor * diminishing )
```

- `base` dari `points_per_severity`.
- `quality_factor`: 0.50 sampai 1.20, diisi admin berdasarkan kejelasan laporan, kualitas PoC, dan saran perbaikan.
- `diminishing`: 1.0 jika `diminishing_enabled` false. Jika true, untuk laporan valid ke-n dari tim yang sama dengan scope dan CWE yang sama, pakai `diminishing_steps[min(n-1, len-1)]`. Urutan n ditentukan oleh `submitted_at`.
- Laporan `duplicate` mendapat `duplicate_points` (default 0).
- Laporan `invalid` mendapat 0.
- Admin boleh mengganti poin secara manual (`points_override_note` wajib diisi) dan tindakan itu tercatat.
- Saat laporan dibuka kembali, poin dihitung ulang dari awal.

### 8.5 Papan peringkat

Skor tim = jumlah `points_awarded` dari laporan berstatus `valid` atau `rewarded` (dan `duplicate` jika `duplicate_points` > 0).

Urutan:
1. Total poin (besar ke kecil)
2. Jumlah temuan `critical` (besar ke kecil)
3. Jumlah temuan `high` (besar ke kecil)
4. Waktu validasi temuan terakhir (paling awal menang)

Hormati `leaderboard_mode`: bila `hidden` atau `hidden_until_end`, peserta tidak melihat peringkat (admin tetap melihat). Cache hasil dan invalidasi saat ada perubahan status atau poin.

### 8.6 Bantuan duplikat manual untuk admin (bukan algoritma otomatis)

Tidak ada penolakan atau penandaan otomatis. Di halaman detail laporan di panel admin, tampilkan kartu "Laporan lain yang mungkin terkait":

```
SELECT laporan lain WHERE scope_id sama AND vulnerability_type_id sama
UNION
SELECT laporan lain WHERE scope_id sama AND endpoint (dinormalisasi) sama
ORDER BY submitted_at ASC
```

Normalisasi endpoint hanya untuk pencocokan tampilan: huruf kecil, buang query string dan fragment, ganti segmen angka atau UUID menjadi `{id}`.

Aturan penentuan: laporan valid pertama berdasarkan `submitted_at` (waktu server) yang mendapat poin penuh. Saat admin memilih status `duplicate`, wajib memilih `duplicate_of` (laporan asli). `submitted_at` tidak boleh diubah oleh siapa pun.

### 8.7 Antrean triage

Urutan default daftar laporan di panel admin:

```
prioritas = triage_weights[severity_claimed] + (jam_menunggu * triage_age_factor)
```

- `jam_menunggu` dihitung dari `submitted_at` (atau dari waktu balasan terakhir tim untuk laporan yang kembali dari `needs_info`).
- Laporan `needs_info` tidak muncul di antrean aktif sampai tim membalas.
- Admin bebas mengganti urutan dengan filter dan sortir manual. Sediakan filter status, scope, CWE, tim, severity, dan tanggal.

### 8.8 Pembuatan nomor dokumen NDA

`NDA-{tahun}-{user_id 4 digit}-{4 karakter heksadesimal acak}`. Nomor dicetak di PDF template dan di-cek admin saat verifikasi. Simpan SHA-256 file hasil upload.

### 8.9 Batas laju (rate limiting)

- Login: 5 percobaan per menit per kombinasi email dan IP
- Registrasi: 10 per jam per IP
- Kirim laporan: 10 per jam per tim (di luar kuota aktif)
- Upload file: 20 per menit per pengguna
- Komentar: 30 per menit per pengguna

---

## 9. Kebijakan akses (Laravel Policies)

- `ReportPolicy`: peserta hanya bisa melihat dan mengomentari laporan milik timnya. Hanya anggota tim berstatus approved yang bisa membuat atau merevisi laporan. Revisi hanya saat status `needs_info`. Komentar `is_internal` tidak pernah dikirim ke peserta.
- `AttachmentPolicy`: unduhan lampiran hanya untuk anggota tim pemilik laporan dan admin/triager.
- `TeamPolicy`: hanya ketua yang bisa mengeluarkan anggota atau membuat ulang kode undangan. Anggota bisa keluar dari tim selama tim belum memiliki laporan.
- `NdaPolicy`: peserta hanya melihat NDA miliknya sendiri.
- Admin/triager mengakses data lewat panel Filament dengan izin spatie yang berbeda.

Wajib ada test IDOR: tim A tidak boleh membuka laporan, lampiran, NDA, atau komentar tim B lewat manipulasi ID di URL maupun API.

## 10. Keamanan platform

Platform ini menyimpan data paling sensitif di lingkungan lomba, jadi perlakukan sebagai target utama.

- Hash password dengan bcrypt atau argon2 (bawaan Laravel). Kebijakan password minimal 10 karakter.
- 2FA TOTP wajib untuk admin dan triager. Opsional untuk peserta.
- Kolom isi laporan memakai cast `encrypted`. Konsekuensi: pencarian teks hanya pada `title`, `endpoint`, `code`, dan metadata, bukan isi laporan.
- File upload disimpan di disk privat (`storage/app/private`), BUKAN di `public`. Nama file diganti UUID. Unduhan lewat controller yang memeriksa policy, dengan header `Content-Disposition: attachment` dan `X-Content-Type-Options: nosniff`.
- Validasi file berdasarkan MIME asli (finfo), bukan ekstensi.
  - Bukti laporan: png, jpg, jpeg, webp, mp4, webm, pdf, txt, md. Maksimal 25 MB per file, maksimal 8 file per laporan.
  - NDA: pdf, jpg, png. Maksimal 5 MB.
- Gambar diproses ulang untuk membuang metadata EXIF. Hitung SHA-256 untuk setiap file.
- Jangan pernah menjalankan atau mem-parsing isi lampiran di server. Tampilkan teks lampiran hanya sebagai teks ter-escape.
- Header keamanan: Content-Security-Policy ketat, X-Frame-Options DENY, Referrer-Policy, HSTS di produksi.
- Proteksi CSRF bawaan, validasi lewat FormRequest, `$fillable` eksplisit, tidak ada mass assignment liar.
- Output di Blade selalu ter-escape. Hati-hati pada fitur komentar dan pengumuman (jika memakai Markdown, bersihkan HTML).
- Log aktivitas untuk: login admin, perubahan pengaturan, verifikasi NDA, perubahan status laporan, perubahan poin, unduh lampiran oleh admin.
- Tautan verifikasi email dan tautan unduh NDA memakai signed URL berkedaluwarsa.
- Jangan commit `.env`. Sediakan `.env.example`. Aktifkan `APP_DEBUG=false` di produksi.
- Backup database dan storage terenkripsi, jadwal harian (dokumentasikan di README).

---

## 11. Persyaratan fitur per modul

### 11.1 Autentikasi dan tim
- Registrasi dengan validasi NIM unik dan email kampus (domain yang diizinkan diatur di pengaturan, default `ubbg.ac.id`; jika admin mengosongkan, terima email apa pun).
- Buat tim: nama unik, otomatis menjadi ketua, kode undangan acak.
- Gabung tim lewat kode undangan, tolak jika tim penuh atau pengguna sudah punya tim.
- Halaman tim menampilkan setiap anggota beserta status verifikasi masing-masing (email, aturan, NDA).

### 11.2 Verifikasi NDA
- Template PDF dibuat otomatis (dompdf) berisi: nama, NIM, tim, tanggal, nomor dokumen, isi perjanjian, dan pernyataan safe harbor dari pihak kampus (teks isi dapat diedit admin dari panel, dengan nomor versi template).
- Peserta upload hasil tanda tangan. Status `pending`.
- Admin melihat pratinjau file berdampingan dengan data peserta, lalu `approve` atau `reject` (alasan wajib untuk reject).
- Notifikasi email saat status berubah.

### 11.3 Scope dan aturan
- Halaman scope menampilkan tabel target (in scope) dan daftar yang dilarang (out of scope), lengkap dengan catatan khusus.
- Halaman aturan memuat Rules of Engagement: larangan DoS, social engineering, akses atau pengunduhan data pribadi, pengujian di luar scope, ketentuan kerahasiaan dan pengungkapan. Persetujuan tercatat per versi.
- Jika `rules_version` naik, peserta dipaksa menyetujui ulang sebelum bisa melanjutkan.

### 11.4 Form laporan
Kolom:
- Target (dropdown dari scope `in_scope` dan `is_active`, bukan input bebas)
- Endpoint, parameter
- Jenis kerentanan (dropdown CWE yang bisa dicari)
- Kalkulator CVSS v3.1 interaktif
- Judul, deskripsi, langkah reproduksi (wajib, minimal 100 karakter), dampak (wajib), saran perbaikan (opsional)
- Upload bukti (drag and drop, pratinjau nama dan ukuran)
- Centang pernyataan: tidak mengakses atau mengunduh data pribadi, tidak merusak layanan, hanya menguji target dalam scope

Perilaku:
- Setelah dikirim, laporan tidak bisa diedit bebas. Revisi hanya saat `needs_info`, disimpan sebagai `report_versions`.
- Tampilkan sisa kuota tim sebelum dan sesudah kirim.
- Laporan terkirim mendapat kode `BB-...` yang tampil jelas.

### 11.5 Panel triage (admin)
- Daftar laporan dengan filter, sortir, dan kolom prioritas.
- Halaman detail: isi laporan, lampiran, riwayat status, komentar (publik dan internal), kartu "laporan terkait", kontrol keputusan (status, severity final, faktor kualitas, poin, `duplicate_of`).
- Aksi massal terbatas (misalnya tandai `in_review` untuk beberapa laporan).

### 11.6 Pengumuman, notifikasi, ekspor
- Pengumuman bisa disematkan, tampil di dasbor.
- Email notifikasi (queue): NDA berubah, laporan berubah status, komentar baru, pengumuman penting.
- Ekspor CSV: seluruh laporan (tanpa isi terenkripsi, kecuali admin memilih) dan papan peringkat akhir.

---

## 12. Tema dan desain antarmuka

### 12.1 Konsep: "Buku Lapangan Pemburu Bug"

Bug bounty adalah berburu bug. Pinjam bahasa visual dari buku lapangan peneliti serangga (entomologi): kartu spesimen, label taksonomi, nomor koleksi. Setiap laporan ditampilkan sebagai Kartu Spesimen. Ini unsur khas yang diberi perhatian paling besar. Elemen lain dibuat tenang dan rapi.

Pemetaan istilah (hanya untuk elemen visual dan label kecil, istilah teknis tetap jelas):
- Kode laporan = nomor koleksi (`BB-2026-0012`)
- Target = habitat
- CWE = taksonomi
- Severity = tingkat kelangkaan (diwarnai, lihat tabel warna)
- Tim = ekspedisi (hanya di teks sapaan ringan, bukan di navigasi)

Arah rasa: bergaya gim dan RPG ringan sesuai selera pemilik proyek (kelangkaan, lencana, animasi apresiasi), dengan sentuhan claymorphism pada kontrol interaktif saja (tombol dan kartu tim): sudut membulat, bayangan ganda lembut (satu bayangan luar, satu sorotan dalam). Jangan menerapkan claymorphism ke seluruh halaman.

### 12.2 Token warna

| Nama | Hex | Peran |
|---|---|---|
| Lumut Gelap | `#17302A` | Teks utama, header gelap, navigasi |
| Sage Muda | `#E9F0E6` | Latar halaman |
| Putih Daun | `#FBFDFA` | Permukaan kartu dan panel |
| Daun | `#2E7A58` | Aksi utama (tombol utama, tautan aktif) |
| Ambar | `#E2A83A` | Sorotan merek, lencana, label spesimen |
| Abu Batu | `#5E6D68` | Teks sekunder, garis tipis |

Warna kelangkaan severity (dipakai konsisten di badge, strip kartu, dan grafik):

| Severity | Hex |
|---|---|
| Info | `#8A9690` |
| Low | `#3E8E63` |
| Medium | `#2F6FA8` |
| High | `#D9822B` |
| Critical | `#C93A2E` |

Pastikan kontras teks memenuhi WCAG AA. Jangan mengandalkan warna saja: badge severity selalu memuat teks.

### 12.3 Tipografi

- Judul: Bricolage Grotesque (bobot 600 sampai 800)
- Isi: IBM Plex Sans (400, 500, 600)
- Kode laporan, vector CVSS, dan hash: JetBrains Mono (hanya untuk data seperti itu, bukan untuk label dekoratif)
- Siapkan fallback `system-ui, sans-serif` untuk semua. Panjang baris isi maksimal sekitar 70 karakter.
- Hindari: huruf kapital semua untuk label, eyebrow kecil di atas setiap judul, menyorot satu kata di judul dengan warna berbeda.

### 12.4 Bentuk dan komponen

- Hierarki radius dibedakan: panel besar 24px, input 12px, badge penuh bulat, tombol clay 20px. Jangan satu radius untuk semua.
- Bayangan hanya pada elemen yang bisa ditekan atau yang perlu diangkat. Kartu isi cukup memakai garis tipis Abu Batu.
- Kartu Spesimen laporan: garis kiri berwarna sesuai severity, kode dalam mono di pojok, judul besar, baris "Habitat" (target) dan "Taksonomi" (CWE), badge status, tanggal kirim. Kartu daftar dan kartu detail memakai struktur yang sama.
- Penanda angka (01, 02, ...) hanya dipakai di bagian "Alur ikut lomba" karena itu memang urutan langkah.
- Tidak memakai gradien sebagai hiasan, tidak memakai deretan kartu identik untuk semua konten.

### 12.5 Gerak (motion)

- Satu momen pembuka di landing page (hero tersusun masuk sekali saat dimuat), selebihnya tenang.
- Gerak yang menjawab aksi pengguna boleh: stempel "Diterima" saat laporan terkirim, kartu berdenyut sekali saat status berubah jadi valid, hitungan naik pada poin tim.
- Hormati `prefers-reduced-motion`.

### 12.6 Landing page (simpel)

Urutan bagian:
1. Hero: nama lomba, tanggal, tombol "Daftar sekarang" dan "Masuk". Di sisi kanan, komposisi Kartu Spesimen besar (dibuat dengan CSS atau SVG, tanpa foto stok) sebagai satu-satunya elemen dramatis.
2. Tentang program (pembukaan, 2 sampai 3 kalimat)
3. Visi
4. Misi
5. Tujuan
6. Benefit untuk peserta
7. Hadiah
8. Alur ikut lomba (langkah bernomor, karena memang berurutan)
9. Jadwal singkat
10. Pertanyaan umum (maksimal 6)
11. Footer: kontak panitia, tautan aturan

Teks placeholder ditulis dalam bahasa Indonesia yang natural, bukan bahasa promosi berlebihan. Tandai semua bagian yang harus diisi panitia dengan `[ISI]` agar mudah dicari. Contoh judul hero (boleh diganti): "Temukan celah sebelum orang lain menemukannya."

Wireframe:

```
+------------------------------------------------------------+
| Logo lomba                                [Masuk] [Daftar]  |
+------------------------------------------------------------+
| Judul lomba besar                      +------------------+ |
| Satu kalimat penjelasan                | Kartu Spesimen   | |
| [Daftar sekarang]  tanggal pelaksanaan | BB-2026-0001     | |
|                                        | Habitat / Takson | |
|                                        +------------------+ |
+------------------------------------------------------------+
| Tentang | Visi | Misi | Tujuan | Benefit | Hadiah           |
| (satu kolom teks, rata kiri, baris pendek)                  |
+------------------------------------------------------------+
| Alur ikut lomba (langkah 1 sampai 6)                        |
| Jadwal | FAQ | Footer                                       |
+------------------------------------------------------------+
```

### 12.7 Dasbor peserta

```
+----------+-------------------------------------------------+
| Sidebar  | Ringkasan                                       |
| Ringkasan|  [Status verifikasi anda dan tim]               |
| Scope    |  [Kuota laporan aktif: 2 dari 5]                |
| Aturan   |  [Pengumuman tersemat]                          |
| Laporan  |  [Laporan terbaru: daftar Kartu Spesimen]       |
| Tim      |                                                 |
| Peringkat|                                                 |
| Pengumuman                                                 |
+----------+-------------------------------------------------+
```

- Sebelum NDA disetujui, menu Scope, Laporan, dan form kirim tampil terkunci dengan penjelasan jelas apa yang kurang dan tombol menuju langkah itu.
- Salin teks antarmuka mengikuti prinsip: kata kerja aktif, kalimat biasa, konsisten. Tombol: "Kirim laporan", "Simpan perubahan", "Unggah NDA". Pesan error menyebut apa yang salah dan cara memperbaikinya. Keadaan kosong berisi ajakan bertindak.

### 12.8 Responsif dan aksesibilitas
- Nyaman dipakai di ponsel (banyak mahasiswa membuka lewat HP).
- Fokus keyboard terlihat, label form terhubung ke input, target sentuh minimal 44px.

---

## 13. Konten awal yang perlu disiapkan lewat seeder

- Akun admin awal (email dan password dibaca dari `.env`, wajib ganti dan aktifkan 2FA saat login pertama)
- Peran dan izin
- Pengaturan default (bagian 8.1)
- Katalog CWE (bagian 14)
- Contoh scope (domain placeholder, jangan memakai domain asli di seeder)
- Contoh pengumuman

## 14. Seeder katalog CWE (minimal)

CWE-79 Cross-Site Scripting; CWE-89 SQL Injection; CWE-352 Cross-Site Request Forgery; CWE-639 Authorization Bypass Through User-Controlled Key (IDOR); CWE-284 Improper Access Control; CWE-285 Improper Authorization; CWE-287 Improper Authentication; CWE-798 Hard-coded Credentials; CWE-200 Exposure of Sensitive Information; CWE-538 Insertion of Sensitive Information into Externally-Accessible File; CWE-548 Exposure of Information Through Directory Listing; CWE-22 Path Traversal; CWE-434 Unrestricted File Upload; CWE-918 Server-Side Request Forgery; CWE-601 Open Redirect; CWE-611 XML External Entity; CWE-78 OS Command Injection; CWE-94 Code Injection; CWE-502 Deserialization of Untrusted Data; CWE-1021 Improper Restriction of Rendered UI Layers (Clickjacking); CWE-693 Protection Mechanism Failure (header keamanan hilang); CWE-942 Permissive Cross-domain Policy; CWE-307 Improper Restriction of Excessive Authentication Attempts; CWE-521 Weak Password Requirements; CWE-319 Cleartext Transmission of Sensitive Information; CWE-327 Use of a Broken or Risky Cryptographic Algorithm; CWE-16 Configuration; entri `Lainnya` untuk jenis yang tidak terdaftar (admin yang mengklasifikasi ulang).

## 15. Struktur kerja per fase

**Fase 0, fondasi**
- Inisialisasi proyek, paket, konfigurasi `.env.example`, token desain Tailwind (warna, font, radius), layout dasar, seeder awal, CI sederhana (test dan lint).

**Fase 1, akun, tim, verifikasi**
- Landing page, registrasi, login, verifikasi email, profil
- Tim (buat, gabung, kelola), persetujuan aturan
- Alur NDA (template PDF, upload, review admin), middleware gerbang akses
- Test: gerbang akses, aturan satu orang satu tim, NDA per anggota

**Fase 2, scope dan pelaporan**
- Halaman scope dan aturan
- Form laporan lengkap, kalkulator CVSS (klien dan server), upload lampiran aman
- Cek kuota dengan transaksi dan jendela waktu
- Test: kalkulator CVSS dengan nilai uji, kuota termasuk skenario kirim bersamaan, validasi file, IDOR

**Fase 3, triage dan penilaian**
- Panel admin (Filament): antrean, detail, keputusan, riwayat status, komentar internal dan publik
- Penilaian poin, `duplicate_of`, kartu laporan terkait, revisi laporan dan versi
- Notifikasi email
- Test: state machine, perhitungan poin, hak akses komentar internal

**Fase 4, peringkat dan pengaturan**
- Papan peringkat dengan tie-break dan mode tampil
- Panel Pengaturan Lomba, override kuota per tim, pengumuman, log aktivitas, ekspor CSV

**Fase 5, pengerasan dan dokumentasi**
- Header keamanan, rate limit, audit akhir semua policy, scan upload (jika tersedia)
- README: instalasi, konfigurasi, backup, panduan admin, panduan peserta singkat
- `DECISIONS.md` dirapikan

## 16. Kriteria selesai (acceptance)

- Peserta yang belum disetujui NDA-nya tidak bisa melihat scope atau mengirim laporan (diuji otomatis).
- Tim tidak bisa melebihi kuota laporan aktif, termasuk saat dua anggota menekan kirim bersamaan.
- Mengubah batas di panel admin langsung berlaku tanpa deploy dan tercatat di log.
- Skor CVSS dihitung server dan cocok dengan nilai uji.
- Tim A tidak bisa mengakses laporan, lampiran, atau NDA tim B dengan cara apa pun.
- Setiap perubahan status laporan tercatat di riwayat.
- Papan peringkat mengikuti urutan dan tie-break yang ditentukan, serta menghormati mode tampil.
- File upload tidak pernah dapat diakses lewat URL publik langsung.
- Semua test lulus dan README memungkinkan orang lain menjalankan proyek dari nol.

## 17. Rencana masa depan (jangan dikerjakan sekarang, tetapi jangan dihalangi desainnya)

- Deteksi duplikat otomatis (fingerprint dari scope, CWE, endpoint ternormalisasi, parameter, ditambah kemiripan teks) sebagai petunjuk untuk admin, bukan penolakan otomatis
- CVSS v4.0
- Pembuatan sertifikat peserta dan pemenang
- Lencana dan pencapaian gamifikasi tambahan
- Banding (appeal) atas keputusan laporan
