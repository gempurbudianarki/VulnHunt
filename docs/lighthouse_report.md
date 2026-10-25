# Laporan Hasil Audit Lighthouse (Mobile Mode)

Pengujian dilakukan menggunakan Google Chrome Headless dengan profil emulasi perangkat mobile (375x812, DPR 2.0).

| Halaman yang Diuji | URL | Performance | Accessibility | Best Practices | SEO |
|---|---|---|---|---|---|
| **landing-mobile** | `http://127.0.0.1:8000/` | **79** | **100** | **100** | **100** |
| **login-mobile** | `http://127.0.0.1:8000/login` | **97** | **100** | **100** | **100** |
| **dasbor-mobile** | `http://127.0.0.1:8000/app` | **97** | **100** | **100** | **100** |
| **lapor-form-mobile** | `http://127.0.0.1:8000/app/laporan/baru` | **94** | **100** | **100** | **100** |

## Ringkasan Metrik Core Web Vitals

| Halaman | First Contentful Paint (FCP) | Largest Contentful Paint (LCP) | Cumulative Layout Shift (CLS) | Total Blocking Time (TBT) |
|---|---|---|---|---|
| **landing-mobile** | 2.1 s | 2.5 s | 0 | 650 ms |
| **login-mobile** | 1.9 s | 2.4 s | 0 | 50 ms |
| **dasbor-mobile** | 1.9 s | 2.2 s | 0 | 40 ms |
| **lapor-form-mobile** | 2.0 s | 2.4 s | 0 | 80 ms |
