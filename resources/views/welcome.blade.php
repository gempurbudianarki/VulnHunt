<x-guest-layout>
    <!-- 1. Hero Section -->
    <section class="relative overflow-hidden py-16 md:py-24 border-b border-abu-batu/20 bg-gradient-to-b from-sage-light via-sage to-sage">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="grid grid-cols-1 lg:grid-cols-12 gap-12 items-center">
                <div class="lg:col-span-7 space-y-6">
                    <div class="inline-flex items-center gap-2 px-3.5 py-1.5 rounded-full bg-lumut/5 border border-lumut/15 text-xs font-semibold text-lumut">
                        <span class="w-2 h-2 rounded-full bg-daun animate-pulse"></span>
                        Program Resmi Internal Kampus UBBG 2026
                    </div>

                    <h1 class="text-4xl sm:text-5xl lg:text-6xl font-extrabold text-lumut tracking-tight leading-[1.1]">
                        Temukan celah sebelum orang lain menemukannya.
                    </h1>

                    <p class="text-lg text-abu-batu max-w-2xl leading-relaxed">
                        Ajang kompetisi pengujian keamanan siber kolaboratif internal mahasiswa Universitas Bina Bangsa Getsempena. Cari, laporkan, dan bantu amankan ekosistem web kampus secara etis.
                    </p>

                    <div class="flex flex-wrap items-center gap-4 pt-2">
                        @auth
                            <a href="{{ route('dashboard') }}" class="btn-clay-primary text-base px-8 py-3.5">
                                Buka Dasbor Ekspedisi
                            </a>
                        @else
                            <a href="{{ route('register') }}" class="btn-clay-primary text-base px-8 py-3.5">
                                Daftar Sekarang
                            </a>
                            <a href="{{ route('login') }}" class="btn-clay-secondary text-base px-7 py-3.5">
                                Masuk ke Akun
                            </a>
                        @endauth
                        <span class="text-xs text-abu-batu block sm:inline font-mono">
                            Periode: [ISI: November 2026]
                        </span>
                    </div>
                </div>

                <!-- Komposisi Kartu Spesimen Besar (CSS murni tanpa foto stok) -->
                <div class="lg:col-span-5 flex justify-center">
                    <div class="w-full max-w-md specimen-card specimen-border-critical shadow-clay-card p-6 bg-putih-daun">
                        <div class="flex items-center justify-between border-b border-abu-batu/15 pb-4 mb-4">
                            <span class="font-mono text-xs font-bold text-lumut bg-sage px-2.5 py-1 rounded-md">
                                SPESIMEN #BB-2026-0001
                            </span>
                            <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-semibold bg-severity-critical/15 text-severity-critical">
                                <span class="w-2 h-2 rounded-full bg-severity-critical"></span>
                                CRITICAL (CVSS 9.8)
                            </span>
                        </div>

                        <div class="space-y-4">
                            <div>
                                <h3 class="font-display font-bold text-xl text-lumut leading-snug">
                                    Pre-Auth Remote Code Execution pada Gateway Mahasiswa
                                </h3>
                                <p class="text-xs text-abu-batu mt-1 font-mono">Habitat: portal-demo.ubbg.ac.id/api/v1/auth</p>
                            </div>

                            <div class="grid grid-cols-2 gap-3 text-xs py-2 px-3 bg-sage/50 rounded-xl border border-abu-batu/10">
                                <div>
                                    <span class="text-abu-batu block text-[10px] uppercase font-mono">Taksonomi CWE</span>
                                    <span class="font-medium text-lumut">CWE-94 Code Injection</span>
                                </div>
                                <div>
                                    <span class="text-abu-batu block text-[10px] uppercase font-mono">Penemu</span>
                                    <span class="font-medium text-lumut">Tim CyberHunter UBBG</span>
                                </div>
                            </div>

                            <div class="pt-2 flex items-center justify-between text-xs text-abu-batu">
                                <div class="flex items-center gap-1.5 text-daun font-semibold">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                    </svg>
                                    Tervalidasi & Diberi Poin
                                </div>
                                <span class="font-mono font-bold text-lumut">+150 Poin</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- 2 - 7. Tentang, Visi, Misi, Tujuan, Benefit, Hadiah -->
    <section class="py-16 bg-putih-daun border-b border-abu-batu/20">
        <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 space-y-12">
            <div>
                <h2 class="text-2xl sm:text-3xl font-extrabold text-lumut tracking-tight mb-4">
                    Tentang Program
                </h2>
                <p class="text-base text-lumut leading-relaxed">
                    UBBG VulnHunt adalah wadah resmi pengujian keamanan siber yang diselenggarakan oleh civitas akademika UBBG. Melalui pendekatan crowdsourced security, mahasiswa ditantang untuk menemukan dan melaporkan celah keamanan secara terkoordinasi sebelum disalahgunakan pihak luar.
                </p>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                <div class="p-6 rounded-panel bg-sage/60 border border-abu-batu/20">
                    <h3 class="font-display font-bold text-xl text-lumut mb-2">Visi</h3>
                    <p class="text-sm text-lumut leading-relaxed">
                        Mewujudkan ekosistem digital kampus UBBG yang tangguh, aman, dan berdaya saing melalui partisipasi aktif talenta muda keamanan siber.
                    </p>
                </div>
                <div class="p-6 rounded-panel bg-sage/60 border border-abu-batu/20">
                    <h3 class="font-display font-bold text-xl text-lumut mb-2">Misi</h3>
                    <ul class="text-sm text-lumut space-y-1.5 list-disc list-inside">
                        <li>Mengidentifikasi kerentanan aset digital kampus lebih dini.</li>
                        <li>Melatih etika pelaporan kerentanan (coordinated disclosure).</li>
                        <li>Membudayakan praktik riset keamanan siber yang bertanggung jawab.</li>
                    </ul>
                </div>
            </div>

            <div>
                <h3 class="text-xl font-bold text-lumut mb-3">Tujuan Program</h3>
                <p class="text-sm text-lumut leading-relaxed">
                    Memberikan ruang edukatif yang legal bagi mahasiswa peminat ethical hacking untuk mempraktikkan keterampilan investigasi keamanan, sekaligus memberikan apresiasi nyata atas kontribusi pengamanan aset digital kampus.
                </p>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-8 pt-4">
                <div>
                    <h3 class="text-xl font-bold text-lumut mb-3">Benefit untuk Peserta</h3>
                    <ul class="text-sm text-lumut space-y-2">
                        <li class="flex items-start gap-2">
                            <span class="text-daun font-bold">✓</span>
                            <span>Sertifikat apresiasi resmi sebagai Peneliti Keamanan UBBG.</span>
                        </li>
                        <li class="flex items-start gap-2">
                            <span class="text-daun font-bold">✓</span>
                            <span>Portofolio nyata pengujian penetrasi terkoordinasi.</span>
                        </li>
                        <li class="flex items-start gap-2">
                            <span class="text-daun font-bold">✓</span>
                            <span>Perlindungan safe harbor kampus selama mematuhi aturan main.</span>
                        </li>
                    </ul>
                </div>

                <div class="p-6 rounded-panel bg-ambar/10 border border-ambar/30">
                    <h3 class="text-xl font-bold text-lumut mb-2">Hadiah & Apresiasi</h3>
                    <p class="text-sm text-lumut mb-3">
                        Total hadiah jutaan rupiah dan plakat penghargaan untuk tim dengan skor kontribusi tertinggi:
                    </p>
                    <div class="space-y-1.5 text-xs font-mono">
                        <div class="flex justify-between border-b border-ambar/20 pb-1">
                            <span>Juara 1:</span>
                            <span class="font-bold text-lumut">[ISI: Rp 3.000.000 + Sertifikat]</span>
                        </div>
                        <div class="flex justify-between border-b border-ambar/20 pb-1">
                            <span>Juara 2:</span>
                            <span class="font-bold text-lumut">[ISI: Rp 2.000.000 + Sertifikat]</span>
                        </div>
                        <div class="flex justify-between">
                            <span>Juara 3:</span>
                            <span class="font-bold text-lumut">[ISI: Rp 1.000.000 + Sertifikat]</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- 8. Alur Ikut Lomba (01 - 06) -->
    <section class="py-16 bg-sage border-b border-abu-batu/20">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="text-center max-w-2xl mx-auto mb-12">
                <h2 class="text-3xl font-extrabold text-lumut tracking-tight">
                    Alur Mengikuti Lomba
                </h2>
                <p class="text-sm text-abu-batu mt-2">
                    Enam tahapan terstruktur mulai dari pendaftaran akun hingga validasi laporan spesimen.
                </p>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                <!-- 01 -->
                <div class="bg-putih-daun p-6 rounded-panel border border-abu-batu/20">
                    <span class="font-mono text-2xl font-bold text-daun block mb-2">01</span>
                    <h3 class="font-display font-bold text-lg text-lumut mb-1">Daftar Akun</h3>
                    <p class="text-xs text-abu-batu leading-relaxed">
                        Daftar dengan NIM dan email resmi kampus. Verifikasi tautan bertanda tangan yang dikirim ke email Anda.
                    </p>
                </div>

                <!-- 02 -->
                <div class="bg-putih-daun p-6 rounded-panel border border-abu-batu/20">
                    <span class="font-mono text-2xl font-bold text-daun block mb-2">02</span>
                    <h3 class="font-display font-bold text-lg text-lumut mb-1">Bentuk Tim</h3>
                    <p class="text-xs text-abu-batu leading-relaxed">
                        Buat tim baru sebagai ketua atau gabung ke tim rekan Anda menggunakan 8 karakter kode undangan unik.
                    </p>
                </div>

                <!-- 03 -->
                <div class="bg-putih-daun p-6 rounded-panel border border-abu-batu/20">
                    <span class="font-mono text-2xl font-bold text-daun block mb-2">03</span>
                    <h3 class="font-display font-bold text-lg text-lumut mb-1">Setujui Aturan</h3>
                    <p class="text-xs text-abu-batu leading-relaxed">
                        Pelajari dan setujui Rules of Engagement (larangan DoS, social engineering, pengunduhan data pribadi).
                    </p>
                </div>

                <!-- 04 -->
                <div class="bg-putih-daun p-6 rounded-panel border border-abu-batu/20">
                    <span class="font-mono text-2xl font-bold text-daun block mb-2">04</span>
                    <h3 class="font-display font-bold text-lg text-lumut mb-1">Unggah NDA</h3>
                    <p class="text-xs text-abu-batu leading-relaxed">
                        Unduh template surat perjanjian kerahasiaan ber-ID unik, tanda tangani, lalu unggah untuk diverifikasi panitia.
                    </p>
                </div>

                <!-- 05 -->
                <div class="bg-putih-daun p-6 rounded-panel border border-abu-batu/20">
                    <span class="font-mono text-2xl font-bold text-daun block mb-2">05</span>
                    <h3 class="font-display font-bold text-lg text-lumut mb-1">Uji Target Scope</h3>
                    <p class="text-xs text-abu-batu leading-relaxed">
                        Setelah NDA disetujui, telusuri daftar target resmi kampus dan cari kerentanan tanpa merusak integritas layanan.
                    </p>
                </div>

                <!-- 06 -->
                <div class="bg-putih-daun p-6 rounded-panel border border-abu-batu/20">
                    <span class="font-mono text-2xl font-bold text-daun block mb-2">06</span>
                    <h3 class="font-display font-bold text-lg text-lumut mb-1">Kirim & Triage</h3>
                    <p class="text-xs text-abu-batu leading-relaxed">
                        Kirim formulir PoC lengkap dengan kalkulator CVSS. Panitia men-triage temuan dan memberikan poin di papan peringkat.
                    </p>
                </div>
            </div>
        </div>
    </section>

    <!-- 9. Jadwal Singkat & 10. FAQ -->
    <section class="py-16 bg-putih-daun">
        <div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8 space-y-16">
            <div>
                <h2 class="text-2xl sm:text-3xl font-extrabold text-lumut tracking-tight text-center mb-8">
                    Jadwal Pelaksanaan Lomba
                </h2>
                <div class="grid grid-cols-1 sm:grid-cols-3 gap-6 text-center">
                    <div class="p-5 rounded-panel bg-sage/50 border border-abu-batu/20">
                        <span class="text-xs font-mono text-daun font-bold block mb-1">Fase 1</span>
                        <h4 class="font-bold text-lumut">Pendaftaran & NDA</h4>
                        <p class="text-xs text-abu-batu mt-1 font-mono">[ISI: 1 - 10 Nov 2026]</p>
                    </div>
                    <div class="p-5 rounded-panel bg-sage/50 border border-abu-batu/20">
                        <span class="text-xs font-mono text-daun font-bold block mb-1">Fase 2</span>
                        <h4 class="font-bold text-lumut">Periode Pengujian</h4>
                        <p class="text-xs text-abu-batu mt-1 font-mono">[ISI: 11 - 25 Nov 2026]</p>
                    </div>
                    <div class="p-5 rounded-panel bg-sage/50 border border-abu-batu/20">
                        <span class="text-xs font-mono text-daun font-bold block mb-1">Fase 3</span>
                        <h4 class="font-bold text-lumut">Triage & Pengumuman</h4>
                        <p class="text-xs text-abu-batu mt-1 font-mono">[ISI: 26 - 30 Nov 2026]</p>
                    </div>
                </div>
            </div>

            <div>
                <h2 class="text-2xl sm:text-3xl font-extrabold text-lumut tracking-tight text-center mb-8">
                    Pertanyaan Umum (FAQ)
                </h2>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6 text-sm">
                    <div class="p-5 rounded-panel bg-sage/30 border border-abu-batu/15">
                        <h4 class="font-bold text-lumut mb-1">Siapa saja yang boleh berpartisipasi?</h4>
                        <p class="text-xs text-abu-batu leading-relaxed">
                            Seluruh mahasiswa aktif Universitas Bina Bangsa Getsempena (UBBG) yang memiliki NIM dan email resmi kampus.
                        </p>
                    </div>
                    <div class="p-5 rounded-panel bg-sage/30 border border-abu-batu/15">
                        <h4 class="font-bold text-lumut mb-1">Berapa jumlah anggota dalam satu tim?</h4>
                        <p class="text-xs text-abu-batu leading-relaxed">
                            Minimal 2 orang dan maksimal 4 orang per tim. Setiap mahasiswa hanya boleh terdaftar di 1 tim.
                        </p>
                    </div>
                    <div class="p-5 rounded-panel bg-sage/30 border border-abu-batu/15">
                        <h4 class="font-bold text-lumut mb-1">Mengapa NDA wajib diunggah per anggota?</h4>
                        <p class="text-xs text-abu-batu leading-relaxed">
                            Perjanjian kerahasiaan mengikat individu secara hukum dan etis untuk tidak mempublikasikan celah sebelum diperbaiki kampus.
                        </p>
                    </div>
                    <div class="p-5 rounded-panel bg-sage/30 border border-abu-batu/15">
                        <h4 class="font-bold text-lumut mb-1">Apakah pemindai otomatis diperbolehkan?</h4>
                        <p class="text-xs text-abu-batu leading-relaxed">
                            Pemindaian otomatis berskala tinggi (DDoS/aggressive brute force) dilarang keras karena dapat mengganggu operasional akademik.
                        </p>
                    </div>
                    <div class="p-5 rounded-panel bg-sage/30 border border-abu-batu/15">
                        <h4 class="font-bold text-lumut mb-1">Bagaimana jika ada laporan duplikat?</h4>
                        <p class="text-xs text-abu-batu leading-relaxed">
                            Laporan pertama yang masuk berdasarkan waktu server (`submitted_at`) dan berstatus valid yang akan menerima poin penuh.
                        </p>
                    </div>
                    <div class="p-5 rounded-panel bg-sage/30 border border-abu-batu/15">
                        <h4 class="font-bold text-lumut mb-1">Kapan saya bisa mengakses daftar scope?</h4>
                        <p class="text-xs text-abu-batu leading-relaxed">
                            Halaman scope dan formulir laporan akan otomatis terbuka segera setelah pengajuan NDA Anda disetujui admin.
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </section>
</x-guest-layout>
