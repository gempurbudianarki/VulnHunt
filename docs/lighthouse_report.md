# Laporan Hasil Audit Lighthouse Lengkap (Mobile Mode)

Pengujian dilakukan menggunakan Google Chrome Headless dengan profil emulasi perangkat mobile (375x812, DPR 2.0).

| Halaman yang Diuji | URL | Performance | Accessibility | Best Practices | SEO |
|---|---|---|---|---|---|
| **landing-mobile (median 3x)** | `http://127.0.0.1:8000/` | **95** | **100** | **100** | **100** |
| **login-mobile** | `http://127.0.0.1:8000/login` | **98** | **100** | **100** | **100** |
| **dasbor-mobile** | `http://127.0.0.1:8000/app` | **97** | **100** | **100** | **100** |
| **tim-mobile** | `http://127.0.0.1:8000/app/tim` | **96** | **95** | **100** | **100** |
| **verifikasi-mobile** | `http://127.0.0.1:8000/app/verifikasi` | **96** | **100** | **100** | **100** |
| **scope-mobile** | `http://127.0.0.1:8000/app/scope` | **97** | **100** | **100** | **100** |
| **lapor-form-mobile** | `http://127.0.0.1:8000/app/laporan/baru` | **95** | **100** | **100** | **100** |
| **lapor-detail-mobile** | `http://127.0.0.1:8000/app/laporan/BB-2026-0001` | **97** | **95** | **100** | **100** |
| **peringkat-mobile** | `http://127.0.0.1:8000/app/peringkat` | **97** | **100** | **100** | **100** |
| **admin-panel-mobile** | `http://127.0.0.1:8000/admin` | **96** | **100** | **100** | **100** |

## Ringkasan Metrik Core Web Vitals

| Halaman | First Contentful Paint (FCP) | Largest Contentful Paint (LCP) | Cumulative Layout Shift (CLS) | Total Blocking Time (TBT) |
|---|---|---|---|---|
| **landing-mobile (median 3x)** | 2.2 s | 2.5 s | 0 | 30 ms |
| **login-mobile** | 1.9 s | 2.2 s | 0.01 | 0 ms |
| **dasbor-mobile** | 2.0 s | 2.3 s | 0 | 10 ms |
| **tim-mobile** | 2.0 s | 2.3 s | 0 | 20 ms |
| **verifikasi-mobile** | 2.1 s | 2.4 s | 0 | 20 ms |
| **scope-mobile** | 2.0 s | 2.3 s | 0 | 20 ms |
| **lapor-form-mobile** | 2.2 s | 2.5 s | 0 | 20 ms |
| **lapor-detail-mobile** | 2.0 s | 2.3 s | 0 | 10 ms |
| **peringkat-mobile** | 2.0 s | 2.3 s | 0 | 10 ms |
| **admin-panel-mobile** | 2.0 s | 2.4 s | 0 | 10 ms |

## Analisis Penurunan Skor Performance Landing Page (97 ke 79) dan Solusi

- **Penyebab Utama Penurunan dari 97 ke 79 pada Audit Sebelumnya**:
  1. **Cold Start & Uncached Assets**: Pada eksekusi pertama, server development melayani HTML 61 KB dan font eksternal secara dingin (cold request). Hal ini meningkatkan Total Blocking Time (TBT) hingga 650 ms.
  2. **Vite Development vs Production Bundle**: Menjalankan pengujian saat aset belum di-bundle produksi (`npm run build`) memuat modul JS secara granular tanpa minifikasi dan tree-shaking.
- **Hasil Setelah Optimasi (`npm run build` + Server Warm-up)**:
  - Tiga kali pengujian berturut-turut pada landing menghasilkan skor: 93, 95, 95 (Median: **95**).
  - Target minimal 85 terlampaui dengan skor **95**.
  - Seluruh halaman berhasil mencapai target Accessibility minimal 95.
