<!DOCTYPE html>
<html lang="id" class="h-full scroll-smooth">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ $title ?? 'VulnHunt - Platform Lomba Bug Bounty Kampus' }}</title>
    <meta name="description" content="VulnHunt: Platform resmi kompetisi bug bounty internal Universitas Bina Bangsa Getsempena (UBBG). Temukan celah keamanan secara etis dan aman.">

    <!-- Favicon & Brand Icons -->
    <link rel="icon" type="image/x-icon" href="{{ asset('brand/favicon.ico') }}">
    <link rel="icon" type="image/png" sizes="32x32" href="{{ asset('brand/favicon-32x32.png') }}">
    <link rel="apple-touch-icon" sizes="180x180" href="{{ asset('brand/apple-touch-icon.png') }}">

    <!-- OpenGraph Metadata -->
    <meta property="og:type" content="website">
    <meta property="og:title" content="{{ $title ?? 'VulnHunt - Platform Lomba Bug Bounty Kampus' }}">
    <meta property="og:description" content="Platform resmi pengujian keamanan siber kampus Universitas Bina Bangsa Getsempena (UBBG).">
    <meta property="og:image" content="{{ asset('brand/og-image.png') }}">
    <meta name="twitter:card" content="summary_large_image">

    <!-- Fonts & Vite Assets -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Plus+Jakarta+Sans:wght@600;700;800&family=JetBrains+Mono:wght@500;700&display=swap" media="print" onload="this.media='all'">
    <noscript>
        <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Plus+Jakarta+Sans:wght@600;700;800&family=JetBrains+Mono:wght@500;700&display=swap">
    </noscript>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-full bg-brand-canvas text-brand-dark font-sans antialiased flex flex-col justify-between selection:bg-brand-primary/20 selection:text-brand-primary">
    <!-- Navbar Claymorphism Publik -->
    <header class="sticky top-0 z-40 bg-white/85 backdrop-blur-md border-b border-slate-200/80 shadow-sm transition-all duration-200">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 h-20 flex items-center justify-between">
            <a href="{{ url('/') }}" class="flex items-center gap-3.5 group focus:outline-none focus:ring-4 focus:ring-brand-primary/20 rounded-clay-pill p-1">
                <div class="w-12 h-12 rounded-clay-md bg-white border border-slate-200/80 shadow-clay-card-light flex items-center justify-center p-1 group-hover:scale-105 transition-transform duration-200 shrink-0">
                    <img src="{{ asset('brand/logo-nav.webp') }}" alt="Logo VulnHunt" width="48" height="48" class="w-full h-full object-contain" decoding="async">
                </div>
                <div>
                    <div class="flex items-center gap-2">
                        <span class="font-display font-extrabold text-xl text-brand-dark tracking-tight leading-none">VulnHunt</span>
                        <span class="clay-badge bg-indigo-50 text-brand-primary border-indigo-200/70 text-[10px] py-0.5 px-2">UBBG</span>
                    </div>
                    <span class="text-xs text-slate-600 font-medium block mt-0.5">Platform Lomba Bug Bounty</span>
                </div>
            </a>

            <!-- Navigation Links -->
            <nav class="hidden md:flex items-center gap-6 text-sm font-semibold text-slate-700">
                <a href="#tentang" class="hover:text-brand-primary transition-colors py-1">Tentang</a>
                <a href="#alur" class="hover:text-brand-primary transition-colors py-1">Alur Lomba</a>
                <a href="#jadwal" class="hover:text-brand-primary transition-colors py-1">Jadwal</a>
                <a href="#faq" class="hover:text-brand-primary transition-colors py-1">FAQ</a>
            </nav>

            <!-- Auth CTA -->
            <div class="flex items-center gap-3">
                @auth
                    <a href="{{ route('dashboard') }}" class="btn-clay-primary text-sm py-2 px-5">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z"/>
                        </svg>
                        <span>Dasbor</span>
                    </a>
                @else
                    <a href="{{ route('login') }}" class="btn-clay-secondary text-sm py-2 px-4 hidden sm:inline-flex">
                        Masuk
                    </a>
                    <a href="{{ route('register') }}" class="btn-clay-primary text-sm py-2 px-5">
                        Daftar Tim
                    </a>
                @endauth
            </div>
        </div>
    </header>

    <!-- Main Content -->
    <main class="flex-grow">
        {{ $slot }}
    </main>

    <!-- Footer Claymorphism -->
    <footer class="bg-slate-900 text-slate-300 py-16 border-t border-slate-800 mt-20 relative overflow-hidden">
        <!-- Subtle background glow -->
        <div class="absolute -top-32 left-1/2 -translate-x-1/2 w-[600px] h-[300px] bg-brand-primary/10 rounded-full blur-3xl pointer-events-none"></div>

        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 relative z-10">
            <div class="grid grid-cols-1 md:grid-cols-4 gap-10 pb-12 border-b border-slate-800">
                <!-- Col 1: Brand Info -->
                <div class="md:col-span-2 space-y-4">
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 rounded-clay-sm bg-white/10 p-1 flex items-center justify-center">
                            <img src="{{ asset('brand/logo-nav.webp') }}" alt="VulnHunt Logo" width="40" height="40" class="w-full h-full object-contain" loading="lazy" decoding="async">
                        </div>
                        <span class="font-display font-extrabold text-xl text-white tracking-tight">VulnHunt</span>
                    </div>
                    <p class="text-sm text-slate-300 max-w-md leading-relaxed">
                        Platform resmi kompetisi pengujian celah keamanan siber (Bug Bounty) internal Universitas Bina Bangsa Getsempena. Wadah riset etis, aman, dan bertanggung jawab.
                    </p>
                    <div class="pt-2">
                        <span class="clay-badge bg-emerald-950/70 text-emerald-300 border-emerald-700/80 text-xs">
                            Safe Harbor Dilindungi Kebijakan Kampus
                        </span>
                    </div>
                </div>

                <!-- Col 2: Navigasi Cepat -->
                <div>
                    <p class="text-xs font-bold uppercase tracking-wider text-slate-200 mb-4">Navigasi Program</p>
                    <ul class="space-y-2.5 text-sm">
                        <li><a href="#tentang" class="text-slate-300 hover:text-white transition-colors">Tentang Kompetisi</a></li>
                        <li><a href="#alur" class="text-slate-300 hover:text-white transition-colors">Alur Pelaksanaan</a></li>
                        <li><a href="#jadwal" class="text-slate-300 hover:text-white transition-colors">Jadwal Penting</a></li>
                        <li><a href="#faq" class="text-slate-300 hover:text-white transition-colors">Pertanyaan Umum (FAQ)</a></li>
                        <li><a href="{{ route('login') }}" class="text-slate-300 hover:text-white transition-colors">Masuk Peserta</a></li>
                    </ul>
                </div>

                <!-- Col 3: Kontak & Penyelenggara -->
                <div>
                    <p class="text-xs font-bold uppercase tracking-wider text-slate-200 mb-4">Penyelenggara Resmi</p>
                    <div class="space-y-2 text-sm text-slate-300">
                        <p class="font-semibold text-white">Universitas Bina Bangsa Getsempena</p>
                        <p class="text-xs text-slate-400">Panitia Pelaksana Lomba Bug Bounty Kampus</p>
                        <p class="text-xs text-slate-400 mt-2">Banda Aceh, Aceh - Indonesia</p>
                        <p class="text-xs text-slate-400 font-mono">Email: panitia.vulnhunt@ubbg.ac.id</p>
                    </div>
                </div>
            </div>

            <!-- Bottom Copyright & Safe Harbor Disclaimer -->
            <div class="pt-8 flex flex-col md:flex-row items-center justify-between gap-4 text-xs text-slate-400">
                <p>&copy; {{ date('Y') }} VulnHunt — Universitas Bina Bangsa Getsempena (UBBG). Hak Cipta Dilindungi.</p>
                <p class="text-center md:text-right max-w-xl">
                    Segala aktivitas pengujian keamanan hanya diizinkan dalam ruang lingkup (scope) resmi dan setelah verifikasi NDA disetujui.
                </p>
            </div>
        </div>
    </footer>
</body>
</html>
