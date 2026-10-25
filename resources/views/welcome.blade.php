<x-guest-layout>
    <!-- 1. Hero Section: Symmetrical High-Impact Cyber Arena -->
    <section class="relative overflow-hidden py-16 sm:py-24 md:py-28 bg-gradient-to-b from-white via-brand-canvas/70 to-brand-canvas border-b border-slate-200/80">
        <!-- Background Subtle Glowing Blobs -->
        <div class="absolute -top-32 -left-32 w-[500px] h-[500px] bg-indigo-200/40 rounded-full blur-3xl pointer-events-none"></div>
        <div class="absolute top-1/3 -right-32 w-[500px] h-[500px] bg-amber-200/35 rounded-full blur-3xl pointer-events-none"></div>
        <div class="absolute bottom-0 left-1/2 -translate-x-1/2 w-3/4 h-24 bg-brand-primary/5 rounded-full blur-2xl pointer-events-none"></div>

        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 relative z-10">
            <div class="grid grid-cols-1 lg:grid-cols-12 gap-12 lg:gap-10 items-center">
                <!-- Left Column: Copywriting & High-Converting Actions (7 cols) -->
                <div class="lg:col-span-7 space-y-7 text-center lg:text-left">
                    <!-- Live Event Badge -->
                    <div class="inline-flex items-center gap-2.5 px-4 py-1.5 rounded-clay-pill bg-white border border-slate-200/90 shadow-sm text-xs font-bold text-slate-800">
                        <span class="flex h-2.5 w-2.5 relative">
                            <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-brand-accent opacity-75"></span>
                            <span class="relative inline-flex rounded-full h-2.5 w-2.5 bg-brand-accent"></span>
                        </span>
                        <span class="tracking-wide uppercase text-[11px] text-slate-700">Kompetisi Resmi Bug Bounty • UBBG Cyber Security 2026</span>
                    </div>

                    <!-- Main Dynamic Headline -->
                    <h1 class="text-4xl sm:text-5xl lg:text-6xl font-black text-slate-900 tracking-tight leading-[1.12]">
                        Tantang Skilmu, Singkap Celahnya, <br class="hidden sm:inline"/>
                        <span class="bg-gradient-to-r from-brand-primary via-indigo-600 to-brand-accent bg-clip-text text-transparent">
                            Rebut Gelar Peneliti Keamanan Terbaik!
                        </span>
                    </h1>

                    <!-- Authoritative & Inviting Subtitle -->
                    <p class="text-base sm:text-lg text-slate-600 max-w-2xl mx-auto lg:mx-0 leading-relaxed font-normal">
                        <strong>VulnHunt</strong> adalah arena resmi <em>ethical hacking</em> dan crowdsourced bug bounty mahasiswa <strong>Universitas Bina Bangsa Getsempena</strong>. Uji ketahanan sistem informasi kampus secara legal, asah portofolio keamanan siber standar industri, dan raih total hadiah pembinaan <strong>Rp 7.000.000,-</strong> terlindungi penuh di bawah payung hukum <em>Safe Harbor</em>.
                    </p>

                    <!-- CTA Action Buttons -->
                    <div class="flex flex-wrap items-center justify-center lg:justify-start gap-4 pt-2">
                        @auth
                            <x-clay.button href="{{ route('dashboard') }}" variant="primary" size="lg" class="shadow-clay-btn-primary hover:shadow-clay-btn-primary-hover">
                                <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/>
                                </svg>
                                <span>Buka Dasbor Tim</span>
                            </x-clay.button>
                        @else
                            <x-clay.button href="{{ route('register') }}" variant="accent" size="lg" class="shadow-clay-btn-accent hover:shadow-clay-btn-accent-hover text-slate-950 font-black">
                                <svg class="w-5 h-5 text-slate-950" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z"/>
                                </svg>
                                <span>Daftar Tim Sekarang</span>
                            </x-clay.button>
                            <x-clay.button href="{{ route('login') }}" variant="secondary" size="lg">
                                <span>Masuk Peserta</span>
                            </x-clay.button>
                        @endauth
                        <x-clay.button href="#alur" variant="ghost" size="lg" class="text-slate-600 hover:text-brand-primary">
                            <span>Panduan Lomba &darr;</span>
                        </x-clay.button>
                    </div>

                    <!-- 3 Symmetrical Trust Badges -->
                    <div class="pt-3 flex flex-wrap items-center justify-center lg:justify-start gap-6 text-xs text-slate-700 font-semibold border-t border-slate-200/80">
                        <div class="flex items-center gap-2">
                            <span class="w-5 h-5 rounded-full bg-emerald-100 text-emerald-700 flex items-center justify-center shrink-0">
                                <svg class="w-3.5 h-3.5" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                                </svg>
                            </span>
                            <span>Safe Harbor 100% Legal</span>
                        </div>
                        <div class="flex items-center gap-2">
                            <span class="w-5 h-5 rounded-full bg-indigo-100 text-brand-primary flex items-center justify-center shrink-0">
                                <svg class="w-3.5 h-3.5" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                                </svg>
                            </span>
                            <span>Kalkulasi CVSS v3.1 Standar Global</span>
                        </div>
                        <div class="flex items-center gap-2">
                            <span class="w-5 h-5 rounded-full bg-amber-100 text-amber-800 flex items-center justify-center shrink-0">
                                <svg class="w-3.5 h-3.5" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                                </svg>
                            </span>
                            <span>Sertifikat & Hadiah Rektorat</span>
                        </div>
                    </div>
                </div>

                <!-- Right Column: Precision Cyber Specimen Telemetry Card (5 cols) -->
                <div class="lg:col-span-5 flex justify-center">
                    <div class="w-full max-w-md relative">
                        <!-- Top Indicator Pill -->
                        <div class="absolute -top-4 -right-2 z-20 hidden sm:flex items-center gap-2 px-3.5 py-1.5 rounded-clay-pill bg-white border border-slate-200/90 shadow-clay-card-light text-xs font-bold text-slate-800">
                            <span class="w-2 h-2 rounded-full bg-emerald-500 animate-pulse"></span>
                            <span>Contoh Simulasi Telemetri</span>
                        </div>

                        <!-- Main Specimen Clay Showcase -->
                        <div class="clay-card p-6 sm:p-7 relative overflow-hidden border-2 border-white bg-white/95 shadow-clay-card">
                            <!-- Card Header -->
                            <div class="flex items-center justify-between pb-4 border-b border-slate-100 mb-5">
                                <div class="flex items-center gap-2">
                                    <span class="font-mono text-xs font-extrabold text-brand-primary bg-indigo-50 border border-indigo-200/70 px-3 py-1 rounded-clay-pill">
                                        CONTOH LAPORAN #VH-2026-0042
                                    </span>
                                </div>
                                <span class="clay-badge bg-rose-50 text-rose-700 border-rose-200 text-xs py-1 px-3">
                                    <span class="w-2 h-2 rounded-full bg-rose-600"></span>
                                    CRITICAL (CVSS 9.8)
                                </span>
                            </div>

                            <!-- Specimen Body -->
                            <div class="space-y-4">
                                <div>
                                    <p class="text-lg font-bold text-slate-900 leading-snug">
                                        Remote Code Execution via Insecure Deserialization
                                    </p>
                                    <p class="text-xs text-slate-600 font-mono mt-1.5 flex items-center gap-1.5">
                                        <svg class="w-3.5 h-3.5 text-slate-400 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1"/>
                                        </svg>
                                        <span class="truncate">portal-demo.ubbg.ac.id/api/v1/auth (Simulasi)</span>
                                    </p>
                                </div>

                                <!-- Metadata Grid -->
                                <div class="grid grid-cols-2 gap-3 text-xs p-3.5 bg-slate-50/90 rounded-clay-md border border-slate-200/80">
                                    <div>
                                        <span class="text-[10px] uppercase font-bold tracking-wider text-slate-500 block">Kategori CWE</span>
                                        <span class="font-semibold text-slate-800">CWE-502 Deserialization</span>
                                    </div>
                                    <div>
                                        <span class="text-[10px] uppercase font-bold tracking-wider text-slate-500 block">Penemu Laporan</span>
                                        <span class="font-semibold text-slate-800">Tim CyberSquad UBBG (Contoh)</span>
                                    </div>
                                </div>

                                <!-- Mini Triage Flow Pipeline -->
                                <div class="p-3 bg-indigo-50/50 rounded-clay-md border border-indigo-100/80 text-[11px] space-y-1.5">
                                    <div class="flex items-center justify-between text-slate-600 font-medium">
                                        <span>Status Triage Dewan Juri:</span>
                                        <span class="text-emerald-700 font-bold">100% Terverifikasi</span>
                                    </div>
                                    <div class="flex items-center gap-1.5">
                                        <div class="flex-1 h-1.5 rounded-full bg-emerald-500"></div>
                                        <div class="flex-1 h-1.5 rounded-full bg-emerald-500"></div>
                                        <div class="flex-1 h-1.5 rounded-full bg-brand-primary"></div>
                                    </div>
                                </div>

                                <!-- Status & Points Rewarded -->
                                <div class="pt-2 flex items-center justify-between text-xs">
                                    <div class="flex items-center gap-2 text-emerald-700 font-bold">
                                        <div class="w-5 h-5 rounded-full bg-emerald-100 flex items-center justify-center text-emerald-600 shrink-0">
                                            <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/>
                                            </svg>
                                        </div>
                                        <span>Status: RESOLVED</span>
                                    </div>
                                    <span class="font-mono font-extrabold text-sm text-brand-primary bg-indigo-50 px-3 py-1 rounded-clay-pill border border-indigo-200/80">
                                        +150 Poin Tim (Contoh)
                                    </span>
                                </div>
                            </div>
                        </div>

                        <!-- Floating Stat Pill Bottom Left -->
                        <div class="absolute -bottom-4 -left-3 z-20 hidden sm:flex items-center gap-2.5 px-4 py-2 rounded-clay-pill bg-white border border-slate-200/90 shadow-clay-card-light text-xs font-semibold text-slate-800">
                            <span class="w-2.5 h-2.5 rounded-full bg-brand-primary"></span>
                            <span>Target Scope (Ilustrasi): Web & API Kampus UBBG</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- 2. Symmetrical 4-Grid Key Metrics Strip -->
    <section class="py-10 bg-white border-b border-slate-200/80">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="grid grid-cols-2 md:grid-cols-4 gap-6 text-center">
                <div class="p-5 rounded-clay-md bg-slate-50/70 border border-slate-200/70">
                    <p class="text-2xl sm:text-3xl font-black text-brand-primary font-mono tracking-tight">Rp 7.000.000</p>
                    <p class="text-xs font-bold text-slate-700 mt-1 uppercase tracking-wider">Total Dana Pembinaan</p>
                    <p class="text-[11px] text-slate-500 mt-0.5">Apresiasi Finansial Juara</p>
                </div>
                <div class="p-5 rounded-clay-md bg-slate-50/70 border border-slate-200/70">
                    <p class="text-2xl sm:text-3xl font-black text-emerald-600 font-mono tracking-tight">100% Legal</p>
                    <p class="text-xs font-bold text-slate-700 mt-1 uppercase tracking-wider">Payung Safe Harbor</p>
                    <p class="text-[11px] text-slate-500 mt-0.5">Dilindungi Kebijakan Kampus</p>
                </div>
                <div class="p-5 rounded-clay-md bg-slate-50/70 border border-slate-200/70">
                    <p class="text-2xl sm:text-3xl font-black text-amber-900 font-mono tracking-tight">CVSS v3.1</p>
                    <p class="text-xs font-bold text-slate-700 mt-1 uppercase tracking-wider">Standar Global FIRST</p>
                    <p class="text-[11px] text-slate-500 mt-0.5">Triage Transparan & Adil</p>
                </div>
                <div class="p-5 rounded-clay-md bg-slate-50/70 border border-slate-200/70">
                    <p class="text-2xl sm:text-3xl font-black text-slate-900 font-mono tracking-tight">E-Sertifikat</p>
                    <p class="text-xs font-bold text-slate-700 mt-1 uppercase tracking-wider">Kredensial Rektorat</p>
                    <p class="text-[11px] text-slate-500 mt-0.5">Portofolio Karir Industri</p>
                </div>
            </div>
        </div>
    </section>

    <!-- 3. Profil Kompetisi: Visi, Misi & Pilar Integritas (Balanced 3-Column Symmetrical Grid) -->
    <section id="tentang" class="py-20 bg-brand-canvas border-b border-slate-200/80">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-14">
            <div class="max-w-3xl mx-auto text-center">
                <span class="clay-badge bg-indigo-50 text-brand-primary border-indigo-200 mb-3">Profil & Landasan Filosofis</span>
                <h2 class="text-3xl sm:text-4xl font-extrabold text-slate-900 tracking-tight">
                    Membangun Kampus yang Tangguh Melalui Riset Etis
                </h2>
                <p class="text-base text-slate-600 mt-4 leading-relaxed">
                    <strong>VulnHunt UBBG</strong> adalah jembatan sinergi strategis antara talenta mahasiswa peminat keamanan informasi dengan pengelola infrastruktur sistem kampus. Bersama-sama, kita menciptakan ekosistem teknologi pendidikan yang aman, berdaya saing tinggi, dan berintegritas.
                </p>
            </div>

            <!-- Symmetrical 3-Card Balanced Grid -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
                <!-- Card 1: Visi -->
                <div class="clay-card p-7 flex flex-col justify-between border-t-4 border-t-brand-primary">
                    <div>
                        <div class="w-12 h-12 rounded-clay-md bg-indigo-100 text-brand-primary flex items-center justify-center mb-5 shadow-sm">
                            <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                            </svg>
                        </div>
                        <h3 class="text-xl font-bold text-slate-900 mb-3">Visi Ketahanan Siber</h3>
                        <p class="text-sm text-slate-600 leading-relaxed">
                            Mewujudkan ketahanan ekosistem digital institusi pendidikan tinggi UBBG yang mandiri, adaptif, dan terpercaya melalui pemberdayaan talenta muda siber dalam koridor pengujian yang legal dan bertanggung jawab.
                        </p>
                    </div>
                    <div class="pt-6 mt-6 border-t border-slate-100 text-xs font-semibold text-brand-primary">
                        Pilar 01 • Institutional Resilience
                    </div>
                </div>

                <!-- Card 2: Misi -->
                <div class="clay-card p-7 flex flex-col justify-between border-t-4 border-t-brand-accent">
                    <div>
                        <div class="w-12 h-12 rounded-clay-md bg-amber-100 text-brand-accent flex items-center justify-center mb-5 shadow-sm">
                            <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4M7.835 4.697a3.42 3.42 0 001.946-.806 3.42 3.42 0 014.438 0 3.42 3.42 0 001.946.806 3.42 3.42 0 013.138 3.138 3.42 3.42 0 00.806 1.946 3.42 3.42 0 010 4.438 3.42 3.42 0 00-.806 1.946 3.42 3.42 0 01-3.138 3.138 3.42 3.42 0 00-1.946.806 3.42 3.42 0 01-4.438 0 3.42 3.42 0 00-1.946-.806 3.42 3.42 0 01-3.138-3.138 3.42 3.42 0 00-.806-1.946 3.42 3.42 0 010-4.438 3.42 3.42 0 00.806-1.946 3.42 3.42 0 013.138-3.138z"/>
                            </svg>
                        </div>
                        <h3 class="text-xl font-bold text-slate-900 mb-3">Misi Riset White-Hat</h3>
                        <p class="text-sm text-slate-600 leading-relaxed">
                            Mendeteksi kelemahan sistem sebelum disusupi ancaman eksternal, mengedukasi standar pelaporan kerentanan terkoordinasi (<em>Coordinated Disclosure</em>), serta menanamkan etika peretasan putih di lingkungan perguruan tinggi.
                        </p>
                    </div>
                    <div class="pt-6 mt-6 border-t border-slate-100 text-xs font-semibold text-amber-900">
                        Pilar 02 • Ethical Standards
                    </div>
                </div>

                <!-- Card 3: Tujuan & Portofolio -->
                <div class="clay-card p-7 flex flex-col justify-between border-t-4 border-t-emerald-600">
                    <div>
                        <div class="w-12 h-12 rounded-clay-md bg-emerald-100 text-emerald-700 flex items-center justify-center mb-5 shadow-sm">
                            <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/>
                            </svg>
                        </div>
                        <h3 class="text-xl font-bold text-slate-900 mb-3">Tujuan & Nilai Karir</h3>
                        <p class="text-sm text-slate-600 leading-relaxed">
                            Menyediakan laboratorium nyata pengujian keamanan sistem bagi mahasiswa untuk mempraktikkan keterampilan investigasi celah, membuktikan kompetensi teknis, dan meraih pengakuan portofolio resmi.
                        </p>
                    </div>
                    <div class="pt-6 mt-6 border-t border-slate-100 text-xs font-semibold text-emerald-700">
                        Pilar 03 • Real-World Impact
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- 4. Podium Hadiah & Apresiasi Juara (The Grand Champions Podium) -->
    <section class="py-20 bg-white border-b border-slate-200/80">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-14">
            <div class="text-center max-w-3xl mx-auto">
                <span class="clay-badge bg-amber-50 text-amber-900 border-amber-200 mb-3">Penghargaan Bergengsi</span>
                <h2 class="text-3xl sm:text-4xl font-extrabold text-slate-900 tracking-tight">
                    Podium Kehormatan Peneliti Terbaik UBBG 2026
                </h2>
                <p class="text-base text-slate-600 mt-3 leading-relaxed">
                    Total dana pembinaan <strong>Rp 7.000.000,-</strong> beserta trofi dan piagam rektorat dipersembahkan kepada tim dengan akumulasi poin laporan valid tertinggi:
                </p>
            </div>

            <!-- Symmetrical 3-Tier Champions Podium -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-8 items-end max-w-5xl mx-auto pt-6">
                <!-- Juara 2: Silver Stage (Kiri) -->
                <div class="order-2 md:order-1">
                    <div class="clay-card p-6 sm:p-7 bg-gradient-to-b from-white to-slate-50 border-2 border-slate-200 shadow-clay-card hover:-translate-y-1 transition-transform">
                        <div class="flex items-center justify-between pb-3 border-b border-slate-200/80 mb-4">
                            <span class="clay-badge bg-slate-100 text-slate-700 border-slate-300 font-bold text-xs">
                                RUNNER UP
                            </span>
                            <span class="w-8 h-8 rounded-full bg-slate-200 text-slate-800 font-black text-sm flex items-center justify-center shadow-inner">
                                2
                            </span>
                        </div>
                        <h3 class="text-2xl font-black text-slate-900">Juara II</h3>
                        <p class="text-xs text-slate-600 mt-1">Plakat Perak + Piagam Rektorat UBBG</p>
                        
                        <div class="my-5 p-4 rounded-clay-md bg-white border border-slate-200 shadow-inner text-center">
                            <p class="text-xs font-bold uppercase tracking-wider text-slate-500">Dana Pembinaan</p>
                            <p class="text-2xl font-black text-slate-900 font-mono mt-1">Rp 2.250.000,-</p>
                        </div>

                        <ul class="text-xs text-slate-600 space-y-2">
                            <li class="flex items-center gap-2">
                                <svg class="w-4 h-4 text-emerald-600 shrink-0" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/></svg>
                                <span>E-Sertifikat Juara II Rektorat</span>
                            </li>
                            <li class="flex items-center gap-2">
                                <svg class="w-4 h-4 text-emerald-600 shrink-0" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/></svg>
                                <span>Publikasi Hall of Fame Peneliti</span>
                            </li>
                        </ul>
                    </div>
                </div>

                <!-- Juara 1: Grand Champion (Tengah - Tertinggi & Paling Megah) -->
                <div class="order-1 md:order-2">
                    <div class="clay-card p-7 sm:p-8 bg-gradient-to-b from-amber-50/80 via-white to-amber-50/40 border-2 border-amber-300 shadow-clay-card-hover -translate-y-2 hover:-translate-y-3 transition-transform relative overflow-hidden">
                        <!-- Crown Banner -->
                        <div class="absolute top-0 right-0 bg-brand-accent text-slate-950 text-[10px] font-black px-4 py-1 rounded-bl-clay-md tracking-widest uppercase">
                            GRAND CHAMPION
                        </div>

                        <div class="flex items-center justify-between pb-3 border-b border-amber-200/80 mb-5">
                            <span class="clay-badge bg-amber-100 text-amber-900 border-amber-300 font-extrabold text-xs">
                                JUARA UTAMA
                            </span>
                            <span class="w-10 h-10 rounded-full bg-amber-400 text-slate-950 font-black text-lg flex items-center justify-center shadow-md">
                                1
                            </span>
                        </div>
                        <h3 class="text-3xl font-black text-slate-900">Juara I</h3>
                        <p class="text-xs text-amber-900 font-semibold mt-1">Trophy Eksklusif Rektorat + Piagam Emas Kehormatan</p>
                        
                        <div class="my-6 p-5 rounded-clay-md bg-white border-2 border-amber-200 shadow-clay-card-light text-center">
                            <p class="text-xs font-bold uppercase tracking-wider text-amber-800">Dana Pembinaan Utama</p>
                            <p class="text-3xl sm:text-4xl font-black text-brand-primary font-mono mt-1">Rp 3.500.000,-</p>
                        </div>

                        <ul class="text-xs text-slate-700 font-medium space-y-2.5">
                            <li class="flex items-center gap-2">
                                <svg class="w-4 h-4 text-amber-800 shrink-0" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/></svg>
                                <span>Trophy Bergilir Juara Umum Rektorat</span>
                            </li>
                            <li class="flex items-center gap-2">
                                <svg class="w-4 h-4 text-amber-800 shrink-0" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/></svg>
                                <span>Surat Rekomendasi Tim Riset Khusus</span>
                            </li>
                            <li class="flex items-center gap-2">
                                <svg class="w-4 h-4 text-amber-800 shrink-0" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/></svg>
                                <span>Sorotan Utama di Portal Resmi Kampus</span>
                            </li>
                        </ul>
                    </div>
                </div>

                <!-- Juara 3: Bronze Stage (Kanan) -->
                <div class="order-3">
                    <div class="clay-card p-6 sm:p-7 bg-gradient-to-b from-white to-amber-50/20 border-2 border-amber-900/20 shadow-clay-card hover:-translate-y-1 transition-transform">
                        <div class="flex items-center justify-between pb-3 border-b border-slate-200/80 mb-4">
                            <span class="clay-badge bg-amber-900/10 text-amber-900 border-amber-900/30 font-bold text-xs">
                                3RD PLACE
                            </span>
                            <span class="w-8 h-8 rounded-full bg-amber-700 text-white font-black text-sm flex items-center justify-center shadow-inner">
                                3
                            </span>
                        </div>
                        <h3 class="text-2xl font-black text-slate-900">Juara III</h3>
                        <p class="text-xs text-slate-600 mt-1">Plakat Perunggu + Piagam Rektorat UBBG</p>
                        
                        <div class="my-5 p-4 rounded-clay-md bg-white border border-slate-200 shadow-inner text-center">
                            <p class="text-xs font-bold uppercase tracking-wider text-slate-500">Dana Pembinaan</p>
                            <p class="text-2xl font-black text-slate-900 font-mono mt-1">Rp 1.250.000,-</p>
                        </div>

                        <ul class="text-xs text-slate-600 space-y-2">
                            <li class="flex items-center gap-2">
                                <svg class="w-4 h-4 text-emerald-600 shrink-0" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/></svg>
                                <span>E-Sertifikat Juara III Rektorat</span>
                            </li>
                            <li class="flex items-center gap-2">
                                <svg class="w-4 h-4 text-emerald-600 shrink-0" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/></svg>
                                <span>Publikasi Hall of Fame Peneliti</span>
                            </li>
                        </ul>
                    </div>
                </div>
            </div>

            <!-- Bottom Facility Strip -->
            <div class="max-w-4xl mx-auto p-5 rounded-clay-md bg-slate-50/80 border border-slate-200 text-center text-xs text-slate-600">
                <span class="font-bold text-slate-800">Fasilitas Seluruh Peserta:</span> Seluruh tim yang berhasil mengirimkan minimal satu laporan tervalidasi sah akan menerima <strong>E-Sertifikat Resmi Peneliti Keamanan Siber</strong> dari Rektorat UBBG dan dicantumkan pada Hall of Fame kampus.
            </div>
        </div>
    </section>

    <!-- 5. Benefit & Nilai Tambah Peserta (Symmetrical 4-Column Balanced Grid) -->
    <section class="py-20 bg-brand-canvas border-b border-slate-200/80">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-12">
            <div class="text-center max-w-2xl mx-auto">
                <span class="clay-badge bg-emerald-50 text-emerald-800 border-emerald-200 mb-3">Nilai Tambah & Rekognisi</span>
                <h2 class="text-3xl sm:text-4xl font-extrabold text-slate-900 tracking-tight">
                    Mengapa Anda Wajib Bergabung di VulnHunt?
                </h2>
                <p class="text-sm text-slate-600 mt-2">
                    Keikutsertaan Anda bukan sekadar kompetisi biasa, melainkan batu loncatan karir di industri keamanan informasi.
                </p>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
                <!-- Benefit 1 -->
                <div class="clay-card p-6 flex flex-col justify-between">
                    <div>
                        <div class="w-12 h-12 rounded-clay-md bg-emerald-100 text-emerald-700 flex items-center justify-center mb-4 shadow-sm">
                            <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/>
                            </svg>
                        </div>
                        <h3 class="text-base font-bold text-slate-900 mb-2">Payung Safe Harbor</h3>
                        <p class="text-xs text-slate-600 leading-relaxed">
                            Bebas dari risiko pelanggaran hukum. Seluruh aktivitas riset yang mematuhi Rules of Engagement dilindungi penuh oleh kebijakan safe harbor institusi kampus.
                        </p>
                    </div>
                    <div class="pt-4 mt-4 border-t border-slate-100 text-[11px] font-semibold text-emerald-700">
                        100% Terlindungi Hukum
                    </div>
                </div>

                <!-- Benefit 2 -->
                <div class="clay-card p-6 flex flex-col justify-between">
                    <div>
                        <div class="w-12 h-12 rounded-clay-md bg-indigo-100 text-brand-primary flex items-center justify-center mb-4 shadow-sm">
                            <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/>
                            </svg>
                        </div>
                        <h3 class="text-base font-bold text-slate-900 mb-2">Kredensial Resmi Rektorat</h3>
                        <p class="text-xs text-slate-600 leading-relaxed">
                            Menerima e-sertifikat bernomor registrasi resmi dari Rektorat UBBG yang menjadi bukti otentik keahlian teknis Anda saat melamar pekerjaan di industri siber.
                        </p>
                    </div>
                    <div class="pt-4 mt-4 border-t border-slate-100 text-[11px] font-semibold text-brand-primary">
                        Sertifikasi Portofolio
                    </div>
                </div>

                <!-- Benefit 3 -->
                <div class="clay-card p-6 flex flex-col justify-between">
                    <div>
                        <div class="w-12 h-12 rounded-clay-md bg-amber-100 text-amber-800 flex items-center justify-center mb-4 shadow-sm">
                            <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"/>
                            </svg>
                        </div>
                        <h3 class="text-base font-bold text-slate-900 mb-2">Pengujian Sistem Nyata</h3>
                        <p class="text-xs text-slate-600 leading-relaxed">
                            Bukan sekadar simulasi CTF buatan! Anda menguji sistem web, API, dan infrastruktur nyata yang melayani ribuan civitas akademika UBBG setiap hari.
                        </p>
                    </div>
                    <div class="pt-4 mt-4 border-t border-slate-100 text-[11px] font-semibold text-amber-800">
                        Real-World Penetration
                    </div>
                </div>

                <!-- Benefit 4 -->
                <div class="clay-card p-6 flex flex-col justify-between">
                    <div>
                        <div class="w-12 h-12 rounded-clay-md bg-purple-100 text-purple-700 flex items-center justify-center mb-4 shadow-sm">
                            <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 8h2a2 2 0 012 2v6a2 2 0 01-2 2h-2v4l-4-4H9a1.994 1.994 0 01-1.414-.586m0 0L11 14h4a2 2 0 002-2V6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2v4l.586-.586z"/>
                            </svg>
                        </div>
                        <h3 class="text-base font-bold text-slate-900 mb-2">Feedback & Mentoring Juri</h3>
                        <p class="text-xs text-slate-600 leading-relaxed">
                            Setiap laporan temuan yang Anda kirimkan akan ditinjau langsung oleh dewan juri ahli dengan catatan konstruktif untuk meningkatkan analisis keamanan Anda.
                        </p>
                    </div>
                    <div class="pt-4 mt-4 border-t border-slate-100 text-[11px] font-semibold text-purple-700">
                        Triage Transparan & Adil
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- 6. Alur Lomba: 5 Tahapan Terstruktur (Connected Stepped Flow) -->
    <section id="alur" class="py-20 bg-white border-b border-slate-200/80">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="text-center max-w-2xl mx-auto mb-16">
                <span class="clay-badge bg-indigo-50 text-brand-primary border-indigo-200 mb-3">Tahapan Partisipasi</span>
                <h2 class="text-3xl sm:text-4xl font-extrabold text-slate-900 tracking-tight">
                    Alur Mengikuti Kompetisi
                </h2>
                <p class="text-sm text-slate-600 mt-2">
                    5 langkah terstruktur dan terkoordinasi dari registrasi hingga penganugerahan di podium juara.
                </p>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-3 lg:grid-cols-5 gap-6">
                <!-- Step 01 -->
                <div class="clay-card p-6 flex flex-col justify-between group hover:shadow-clay-card-hover transition-all">
                    <div>
                        <div class="w-12 h-12 rounded-clay-md bg-indigo-50 border border-indigo-200/80 text-brand-primary font-mono font-black text-lg flex items-center justify-center mb-4 group-hover:bg-brand-primary group-hover:text-white transition-colors">
                            01
                        </div>
                        <h3 class="text-base font-bold text-slate-900 mb-2">Daftar Akun</h3>
                        <p class="text-xs text-slate-600 leading-relaxed">
                            Registrasi menggunakan NIM aktif dan email resmi kampus UBBG. Lakukan verifikasi email untuk mengaktifkan akun peserta.
                        </p>
                    </div>
                    <div class="pt-4 mt-4 border-t border-slate-100 text-[11px] font-semibold text-brand-primary">
                        Email @ubbg.ac.id
                    </div>
                </div>

                <!-- Step 02 -->
                <div class="clay-card p-6 flex flex-col justify-between group hover:shadow-clay-card-hover transition-all">
                    <div>
                        <div class="w-12 h-12 rounded-clay-md bg-indigo-50 border border-indigo-200/80 text-brand-primary font-mono font-black text-lg flex items-center justify-center mb-4 group-hover:bg-brand-primary group-hover:text-white transition-colors">
                            02
                        </div>
                        <h3 class="text-base font-bold text-slate-900 mb-2">Bentuk Tim</h3>
                        <p class="text-xs text-slate-600 leading-relaxed">
                            Buat tim baru sebagai ketua atau bergabung menggunakan kode undangan 8-karakter unik dari rekan Anda (beranggotakan 2–4 mahasiswa).
                        </p>
                    </div>
                    <div class="pt-4 mt-4 border-t border-slate-100 text-[11px] font-semibold text-brand-primary">
                        Skuad 2 - 4 Mahasiswa
                    </div>
                </div>

                <!-- Step 03 -->
                <div class="clay-card p-6 flex flex-col justify-between group hover:shadow-clay-card-hover transition-all">
                    <div>
                        <div class="w-12 h-12 rounded-clay-md bg-indigo-50 border border-indigo-200/80 text-brand-primary font-mono font-black text-lg flex items-center justify-center mb-4 group-hover:bg-brand-primary group-hover:text-white transition-colors">
                            03
                        </div>
                        <h3 class="text-base font-bold text-slate-900 mb-2">Setujui Rules</h3>
                        <p class="text-xs text-slate-600 leading-relaxed">
                            Pahami batasan etika mutlak: larangan DoS/DDoS, perusakan integritas data, pengunduhan PII, maupun serangan rekayasa sosial.
                        </p>
                    </div>
                    <div class="pt-4 mt-4 border-t border-slate-100 text-[11px] font-semibold text-brand-primary">
                        Rules of Engagement
                    </div>
                </div>

                <!-- Step 04 -->
                <div class="clay-card p-6 flex flex-col justify-between group hover:shadow-clay-card-hover transition-all">
                    <div>
                        <div class="w-12 h-12 rounded-clay-md bg-indigo-50 border border-indigo-200/80 text-brand-primary font-mono font-black text-lg flex items-center justify-center mb-4 group-hover:bg-brand-primary group-hover:text-white transition-colors">
                            04
                        </div>
                        <h3 class="text-base font-bold text-slate-900 mb-2">Unggah NDA</h3>
                        <p class="text-xs text-slate-600 leading-relaxed">
                            Unduh template PDF ber-ID unik yang terisi otomatis, bubuhkan tanda tangan, dan unggah kembali untuk disetujui panitia admin.
                        </p>
                    </div>
                    <div class="pt-4 mt-4 border-t border-slate-100 text-[11px] font-semibold text-brand-primary">
                        Safe Harbor Legalitas
                    </div>
                </div>

                <!-- Step 05 -->
                <div class="clay-card p-6 flex flex-col justify-between group hover:shadow-clay-card-hover transition-all">
                    <div>
                        <div class="w-12 h-12 rounded-clay-md bg-indigo-50 border border-indigo-200/80 text-brand-primary font-mono font-black text-lg flex items-center justify-center mb-4 group-hover:bg-brand-primary group-hover:text-white transition-colors">
                            05
                        </div>
                        <h3 class="text-base font-bold text-slate-900 mb-2">Uji & Lapor</h3>
                        <p class="text-xs text-slate-600 leading-relaxed">
                            Akses scope target resmi. Kirim bukti konsep kerentanan (PoC) lengkap dengan vector CVSS v3.1 untuk dinilai oleh dewan juri.
                        </p>
                    </div>
                    <div class="pt-4 mt-4 border-t border-slate-100 text-[11px] font-semibold text-brand-primary">
                        Triage & Papan Skor
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- 7. Jadwal Pelaksanaan: 3 Milestone Simetris -->
    <section id="jadwal" class="py-20 bg-brand-canvas border-b border-slate-200/80">
        <div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8 space-y-12">
            <div class="text-center max-w-2xl mx-auto">
                <span class="clay-badge bg-indigo-50 text-brand-primary border-indigo-200 mb-3">Timeline & Milestone</span>
                <h2 class="text-3xl sm:text-4xl font-extrabold text-slate-900 tracking-tight">
                    Jadwal Resmi Pelaksanaan Lomba
                </h2>
                <p class="text-sm text-slate-600 mt-2">
                    Catat tanggal-tanggal penting agar tim Anda dapat mempersiapkan strategi pengujian secara optimal.
                </p>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                <!-- Fase 1 -->
                <div class="clay-card p-6 text-center border-t-4 border-t-brand-primary flex flex-col justify-between">
                    <div>
                        <span class="font-mono text-xs font-bold text-brand-primary bg-indigo-50 px-3 py-1 rounded-clay-pill border border-indigo-200/80 inline-block mb-3">
                            Fase 01 • Pembukaan
                        </span>
                        <h3 class="text-lg font-bold text-slate-900 mb-1">Registrasi & Verifikasi NDA</h3>
                        <p class="text-xs text-slate-600 mb-4">Pembentukan tim dan pengesahan dokumen integritas</p>
                    </div>
                    <div class="p-3.5 rounded-clay-md bg-white border border-slate-200 font-mono font-extrabold text-sm text-brand-primary shadow-inner">
                        10 – 18 November 2026
                    </div>
                </div>

                <!-- Fase 2 -->
                <div class="clay-card p-6 text-center border-t-4 border-t-brand-accent flex flex-col justify-between">
                    <div>
                        <span class="font-mono text-xs font-bold text-amber-900 bg-amber-100 px-3 py-1 rounded-clay-pill border border-amber-300 inline-block mb-3">
                            Fase 02 • Invasion Window
                        </span>
                        <h3 class="text-lg font-bold text-slate-900 mb-1">Periode Pengujian Celah</h3>
                        <p class="text-xs text-slate-600 mb-4">Eksploitasi target scope resmi & pengiriman laporan temuan</p>
                    </div>
                    <div class="p-3.5 rounded-clay-md bg-amber-50/70 border border-amber-300 font-mono font-extrabold text-sm text-amber-900 shadow-inner">
                        19 – 30 November 2026
                    </div>
                </div>

                <!-- Fase 3 -->
                <div class="clay-card p-6 text-center border-t-4 border-t-emerald-600 flex flex-col justify-between">
                    <div>
                        <span class="font-mono text-xs font-bold text-emerald-800 bg-emerald-100 px-3 py-1 rounded-clay-pill border border-emerald-300 inline-block mb-3">
                            Fase 03 • Awarding
                        </span>
                        <h3 class="text-lg font-bold text-slate-900 mb-1">Final Triage & Pengumuman</h3>
                        <p class="text-xs text-slate-600 mb-4">Validasi skor akhir, penetapan juara, dan penganugerahan</p>
                    </div>
                    <div class="p-3.5 rounded-clay-md bg-white border border-slate-200 font-mono font-extrabold text-sm text-emerald-800 shadow-inner">
                        1 – 5 Desember 2026
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- 8. FAQ Accordion: Symmetrical & Accessible -->
    <section id="faq" class="py-20 bg-white border-b border-slate-200/80" x-data="{ openFaq: null }">
        <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 space-y-12">
            <div class="text-center max-w-2xl mx-auto">
                <span class="clay-badge bg-indigo-50 text-brand-primary border-indigo-200 mb-3">Tanya Jawab & Aturan Teknis</span>
                <h2 class="text-3xl sm:text-4xl font-extrabold text-slate-900 tracking-tight">
                    Pertanyaan yang Sering Diajukan
                </h2>
                <p class="text-sm text-slate-600 mt-2">
                    Informasi penting seputar teknis pengujian, legalitas safe harbor, dan penilaian kompetisi.
                </p>
            </div>

            <div class="space-y-4">
                <!-- FAQ Item 1 -->
                <div class="clay-card-light overflow-hidden border border-slate-200/90 transition-all">
                    <button
                        type="button"
                        class="w-full px-6 py-4.5 text-left flex items-center justify-between gap-4 font-bold text-slate-900 hover:text-brand-primary focus:outline-none focus:bg-slate-50"
                        @click="openFaq = (openFaq === 1 ? null : 1)"
                        :aria-expanded="openFaq === 1"
                        aria-controls="faq-ans-1"
                    >
                        <span class="text-sm sm:text-base">1. Siapa saja yang berhak mengikuti kompetisi VulnHunt?</span>
                        <svg class="w-5 h-5 shrink-0 transition-transform duration-200" :class="openFaq === 1 ? 'rotate-180 text-brand-primary' : 'text-slate-400'" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                        </svg>
                    </button>
                    <div id="faq-ans-1" x-show="openFaq === 1" x-collapse class="px-6 pb-5 pt-1 text-xs sm:text-sm text-slate-600 leading-relaxed border-t border-slate-100">
                        Seluruh mahasiswa aktif Universitas Bina Bangsa Getsempena (UBBG) yang memiliki NIM terdaftar dan alamat email resmi kampus (@ubbg.ac.id). Peserta wajib membentuk tim beranggotakan 2 hingga 4 orang. Mahasiswa dari semua program studi diperbolehkan berpartisipasi.
                    </div>
                </div>

                <!-- FAQ Item 2 -->
                <div class="clay-card-light overflow-hidden border border-slate-200/90 transition-all">
                    <button
                        type="button"
                        class="w-full px-6 py-4.5 text-left flex items-center justify-between gap-4 font-bold text-slate-900 hover:text-brand-primary focus:outline-none focus:bg-slate-50"
                        @click="openFaq = (openFaq === 2 ? null : 2)"
                        :aria-expanded="openFaq === 2"
                        aria-controls="faq-ans-2"
                    >
                        <span class="text-sm sm:text-base">2. Apa itu Safe Harbor dan mengapa peserta wajib menandatangani NDA?</span>
                        <svg class="w-5 h-5 shrink-0 transition-transform duration-200" :class="openFaq === 2 ? 'rotate-180 text-brand-primary' : 'text-slate-400'" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                        </svg>
                    </button>
                    <div id="faq-ans-2" x-show="openFaq === 2" x-collapse class="px-6 pb-5 pt-1 text-xs sm:text-sm text-slate-600 leading-relaxed border-t border-slate-100">
                        Safe Harbor adalah jaminan perlindungan hukum resmi dari institusi UBBG bahwa seluruh aktivitas pengujian keamanan Anda diakui legal dan tidak akan dituntut secara pidana maupun akademik, selama Anda mematuhi Rules of Engagement. Dokumen NDA (Non-Disclosure Agreement) mengikat peserta untuk menjaga kerahasiaan celah agar tidak dibocorkan ke publik sebelum diperbaiki oleh pengelola sistem.
                    </div>
                </div>

                <!-- FAQ Item 3 -->
                <div class="clay-card-light overflow-hidden border border-slate-200/90 transition-all">
                    <button
                        type="button"
                        class="w-full px-6 py-4.5 text-left flex items-center justify-between gap-4 font-bold text-slate-900 hover:text-brand-primary focus:outline-none focus:bg-slate-50"
                        @click="openFaq = (openFaq === 3 ? null : 3)"
                        :aria-expanded="openFaq === 3"
                        aria-controls="faq-ans-3"
                    >
                        <span class="text-sm sm:text-base">3. Apakah diperbolehkan menggunakan alat pemindai otomatis (scanner)?</span>
                        <svg class="w-5 h-5 shrink-0 transition-transform duration-200" :class="openFaq === 3 ? 'rotate-180 text-brand-primary' : 'text-slate-400'" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                        </svg>
                    </button>
                    <div id="faq-ans-3" x-show="openFaq === 3" x-collapse class="px-6 pb-5 pt-1 text-xs sm:text-sm text-slate-600 leading-relaxed border-t border-slate-100">
                        Pemindaian otomatis berskala masif (seperti vulnerability network scanner berkecepatan tinggi) yang berpotensi menyebabkan Denial of Service (DoS) atau kelumpuhan server dilarang keras. Pengujian difokuskan pada analisis kerentanan manual, logika bisnis, otentikasi, injeksi SQL/XSS, dan miskonfigurasi dengan lalu lintas terkontrol (rate-limited).
                    </div>
                </div>

                <!-- FAQ Item 4 -->
                <div class="clay-card-light overflow-hidden border border-slate-200/90 transition-all">
                    <button
                        type="button"
                        class="w-full px-6 py-4.5 text-left flex items-center justify-between gap-4 font-bold text-slate-900 hover:text-brand-primary focus:outline-none focus:bg-slate-50"
                        @click="openFaq = (openFaq === 4 ? null : 4)"
                        :aria-expanded="openFaq === 4"
                        aria-controls="faq-ans-4"
                    >
                        <span class="text-sm sm:text-base">4. Bagaimana jika dua tim melaporkan celah yang sama (duplikat)?</span>
                        <svg class="w-5 h-5 shrink-0 transition-transform duration-200" :class="openFaq === 4 ? 'rotate-180 text-brand-primary' : 'text-slate-400'" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                        </svg>
                    </button>
                    <div id="faq-ans-4" x-show="openFaq === 4" x-collapse class="px-6 pb-5 pt-1 text-xs sm:text-sm text-slate-600 leading-relaxed border-t border-slate-100">
                        Prinsip <em>first-come, first-served</em> diterapkan secara ketat berdasarkan stempel waktu server (submitted_at) yang tidak dapat dimanipulasi. Laporan pertama yang masuk dan diverifikasi valid oleh dewan juri akan mendapatkan poin penuh, sedangkan laporan sejenis berikutnya akan diklasifikasikan sebagai duplikat.
                    </div>
                </div>

                <!-- FAQ Item 5 -->
                <div class="clay-card-light overflow-hidden border border-slate-200/90 transition-all">
                    <button
                        type="button"
                        class="w-full px-6 py-4.5 text-left flex items-center justify-between gap-4 font-bold text-slate-900 hover:text-brand-primary focus:outline-none focus:bg-slate-50"
                        @click="openFaq = (openFaq === 5 ? null : 5)"
                        :aria-expanded="openFaq === 5"
                        aria-controls="faq-ans-5"
                    >
                        <span class="text-sm sm:text-base">5. Berapa batas kuota laporan aktif yang dimiliki setiap tim?</span>
                        <svg class="w-5 h-5 shrink-0 transition-transform duration-200" :class="openFaq === 5 ? 'rotate-180 text-brand-primary' : 'text-slate-400'" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                        </svg>
                    </button>
                    <div id="faq-ans-5" x-show="openFaq === 5" x-collapse class="px-6 pb-5 pt-1 text-xs sm:text-sm text-slate-600 leading-relaxed border-t border-slate-100">
                        Setiap tim memiliki kuota laporan aktif bersamaan (default 5 laporan aktif berstatus submitted atau in_review). Kebijakan ini mendorong peserta untuk menyusun laporan temuan berkualitas tinggi dan mencegah spam laporan berulang. Begitu laporan diputuskan valid, invalid, atau duplikat oleh juri, kuota tim akan langsung terbuka kembali.
                    </div>
                </div>

                <!-- FAQ Item 6 -->
                <div class="clay-card-light overflow-hidden border border-slate-200/90 transition-all">
                    <button
                        type="button"
                        class="w-full px-6 py-4.5 text-left flex items-center justify-between gap-4 font-bold text-slate-900 hover:text-brand-primary focus:outline-none focus:bg-slate-50"
                        @click="openFaq = (openFaq === 6 ? null : 6)"
                        :aria-expanded="openFaq === 6"
                        aria-controls="faq-ans-6"
                    >
                        <span class="text-sm sm:text-base">6. Apakah mahasiswa pemula di bidang keamanan siber boleh ikut mendaftar?</span>
                        <svg class="w-5 h-5 shrink-0 transition-transform duration-200" :class="openFaq === 6 ? 'rotate-180 text-brand-primary' : 'text-slate-400'" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                        </svg>
                    </button>
                    <div id="faq-ans-6" x-show="openFaq === 6" x-collapse class="px-6 pb-5 pt-1 text-xs sm:text-sm text-slate-600 leading-relaxed border-t border-slate-100">
                        Sangat dianjurkan! VulnHunt dirancang sebagai ruang edukatif yang suportif. Anda dapat membentuk tim kolaboratif bersama rekan yang memiliki keahlian saling melengkapi (misalnya pemrograman web, jaringan, dan analisis sistem). Kategori kerentanan mencakup tingkat Low dan Medium yang ramah bagi peneliti pemula.
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- 9. Symmetrical High-Impact Final Call to Action -->
    <section class="py-20 bg-gradient-to-br from-slate-950 via-brand-primary to-indigo-950 text-white relative overflow-hidden">
        <!-- Background Lighting -->
        <div class="absolute -top-24 left-1/2 -translate-x-1/2 w-[700px] h-[350px] bg-brand-accent/15 rounded-full blur-3xl pointer-events-none"></div>

        <div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8 text-center relative z-10 space-y-7">
            <div class="inline-flex items-center gap-2 px-4 py-1.5 rounded-clay-pill bg-white/10 border border-white/20 text-xs font-bold text-amber-300">
                <span class="w-2 h-2 rounded-full bg-amber-400 animate-ping"></span>
                <span>Pendaftaran Gelombang I Resmi Dibuka</span>
            </div>

            <h2 class="text-3xl sm:text-4xl md:text-5xl font-black tracking-tight leading-tight">
                Siap Menjadi Garda Terdepan <br class="hidden sm:inline"/>
                <span class="bg-gradient-to-r from-amber-300 via-amber-200 to-white bg-clip-text text-transparent">
                    Ketahanan Siber Kampus UBBG?
                </span>
            </h2>

            <p class="text-base sm:text-lg text-slate-200 max-w-2xl mx-auto leading-relaxed">
                Bentuk skuad tim Anda sekarang, tandatangani perjanjian kerahasiaan safe harbor, dan raih kehormatan tertinggi sebagai Peneliti Keamanan Siber Terbaik UBBG 2026.
            </p>

            <div class="pt-4 flex flex-wrap justify-center gap-4">
                @auth
                    <x-clay.button href="{{ route('dashboard') }}" variant="accent" size="lg" class="shadow-clay-btn-accent text-slate-950 font-black px-8 py-4 text-base">
                        <span>Buka Dasbor Laporan &rarr;</span>
                    </x-clay.button>
                @else
                    <x-clay.button href="{{ route('register') }}" variant="accent" size="lg" class="shadow-clay-btn-accent text-slate-950 font-black px-8 py-4 text-base">
                        <span>Daftar Tim Sekarang &rarr;</span>
                    </x-clay.button>
                    <x-clay.button href="{{ route('login') }}" variant="secondary" size="lg" class="bg-white/10 text-white border-white/30 hover:bg-white/20 px-8 py-4 text-base">
                        <span>Masuk ke Akun Peserta</span>
                    </x-clay.button>
                @endauth
            </div>
        </div>
    </section>
</x-guest-layout>
