<x-app-layout>
    <div class="space-y-8">
        <!-- Header Welcome -->
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 pb-4 border-b border-abu-batu/15">
            <div>
                <span class="text-xs font-mono font-bold text-ambar uppercase tracking-wider">Buku Lapangan Ekspedisi</span>
                <h1 class="text-2xl sm:text-3xl font-extrabold text-lumut tracking-tight mt-0.5">
                    Selamat Datang, {{ $user->name }}
                </h1>
                <p class="text-xs text-abu-batu mt-1">
                    NIM: <span class="font-mono text-lumut font-semibold">{{ $user->nim ?? '-' }}</span> | Prodi: <span class="text-lumut font-medium">{{ $user->study_program ?? '-' }}</span> (Angkatan {{ $user->cohort_year ?? '-' }})
                </p>
            </div>

            @if($team)
                <div class="flex items-center gap-3 bg-putih-daun px-4 py-3 rounded-2xl border border-abu-batu/20 shadow-sm">
                    <div class="w-10 h-10 rounded-xl bg-daun/10 text-daun flex items-center justify-center font-display font-bold text-sm">
                        {{ strtoupper(substr($team->name, 0, 2)) }}
                    </div>
                    <div>
                        <div class="text-[10px] text-abu-batu uppercase font-mono tracking-wider">Ekspedisi Tim</div>
                        <div class="font-display font-bold text-sm text-lumut leading-tight">{{ $team->name }}</div>
                        <div class="text-[11px] text-abu-batu">
                            Peran: <span class="font-semibold text-daun">{{ $user->teamMember?->role === 'leader' ? 'Ketua Tim' : 'Anggota' }}</span>
                        </div>
                    </div>
                </div>
            @endif
        </div>

        <!-- Gerbang Verifikasi Status Checklist (Per Anggota) -->
        <div class="specimen-card p-6 bg-putih-daun shadow-sm">
            <div class="flex items-center justify-between mb-4">
                <div>
                    <h2 class="font-display font-bold text-lg text-lumut">
                        Status Verifikasi & Gerbang Akses
                    </h2>
                    <p class="text-xs text-abu-batu mt-0.5">
                        Akses target scope dan pengiriman laporan akan otomatis terbuka jika keempat tahapan di bawah terpenuhi.
                    </p>
                </div>
                @if($isEmailVerified && $hasTeam && $isRulesAccepted && $isNdaApproved)
                    <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-semibold bg-severity-low/15 text-severity-low">
                        <span class="w-2 h-2 rounded-full bg-severity-low"></span>
                        Akses Penuh Terbuka
                    </span>
                @else
                    <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-semibold bg-ambar/15 text-ambar">
                        <span class="w-2 h-2 rounded-full bg-ambar animate-pulse"></span>
                        Perlu Tindakan
                    </span>
                @endif
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 pt-2">
                <!-- 1. Email -->
                <div class="p-4 rounded-xl border {{ $isEmailVerified ? 'bg-daun/5 border-daun/30' : 'bg-severity-critical/5 border-severity-critical/30' }}">
                    <div class="flex items-center justify-between mb-2">
                        <span class="text-xs font-mono font-bold text-abu-batu">01. EMAIL</span>
                        @if($isEmailVerified)
                            <span class="text-xs text-daun font-bold">✓ Terverifikasi</span>
                        @else
                            <span class="text-xs text-severity-critical font-bold">Belum</span>
                        @endif
                    </div>
                    <p class="text-xs font-semibold text-lumut">{{ $user->email }}</p>
                    <p class="text-[11px] text-abu-batu mt-1">Identitas kemahasiswaan aktif.</p>
                </div>

                <!-- 2. Tim -->
                <div class="p-4 rounded-xl border {{ $hasTeam ? 'bg-daun/5 border-daun/30' : 'bg-ambar/5 border-ambar/30' }}">
                    <div class="flex items-center justify-between mb-2">
                        <span class="text-xs font-mono font-bold text-abu-batu">02. TIM</span>
                        @if($hasTeam)
                            <span class="text-xs text-daun font-bold">✓ Terdaftar</span>
                        @else
                            <a href="{{ route('teams.index') }}" class="text-xs text-ambar hover:underline font-bold">Buat / Gabung &rarr;</a>
                        @endif
                    </div>
                    <p class="text-xs font-semibold text-lumut">{{ $team ? $team->name : 'Belum memiliki tim' }}</p>
                    <p class="text-[11px] text-abu-batu mt-1">Basis partisipasi lomba.</p>
                </div>

                <!-- 3. Aturan -->
                <div class="p-4 rounded-xl border {{ $isRulesAccepted ? 'bg-daun/5 border-daun/30' : 'bg-ambar/5 border-ambar/30' }}">
                    <div class="flex items-center justify-between mb-2">
                        <span class="text-xs font-mono font-bold text-abu-batu">03. ATURAN</span>
                        @if($isRulesAccepted)
                            <span class="text-xs text-daun font-bold">✓ Versi {{ $user->rules_accepted_version }}</span>
                        @else
                            <a href="{{ route('rules.index') }}" class="text-xs text-ambar hover:underline font-bold">Setujui &rarr;</a>
                        @endif
                    </div>
                    <p class="text-xs font-semibold text-lumut">Rules of Engagement</p>
                    <p class="text-[11px] text-abu-batu mt-1">Versi aktif: v{{ $activeRulesVersion }}</p>
                </div>

                <!-- 4. NDA -->
                <div class="p-4 rounded-xl border {{ $isNdaApproved ? 'bg-daun/5 border-daun/30' : ($latestNda && $latestNda->isPending() ? 'bg-ambar/5 border-ambar/30' : 'bg-severity-critical/5 border-severity-critical/30') }}">
                    <div class="flex items-center justify-between mb-2">
                        <span class="text-xs font-mono font-bold text-abu-batu">04. PERJANJIAN NDA</span>
                        @if($isNdaApproved)
                            <span class="text-xs text-daun font-bold">✓ Disetujui</span>
                        @elseif($latestNda && $latestNda->isPending())
                            <span class="text-xs text-ambar font-bold">Menunggu Review</span>
                        @else
                            <a href="{{ route('verification.index') }}" class="text-xs text-severity-critical hover:underline font-bold">Unggah &rarr;</a>
                        @endif
                    </div>
                    <p class="text-xs font-semibold text-lumut">
                        {{ $latestNda ? $latestNda->document_number : 'Belum diunggah' }}
                    </p>
                    <p class="text-[11px] text-abu-batu mt-1">
                        @if($latestNda && $latestNda->isRejected())
                            <span class="text-severity-critical font-medium">Ditolak: {{ $latestNda->reject_reason }}</span>
                        @else
                            Dokumen sah safe harbor.
                        @endif
                    </p>
                </div>
            </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
            <!-- Left 2 Cols: Kuota & Pengumuman -->
            <div class="lg:col-span-2 space-y-8">
                <!-- Kuota Tim Card -->
                @if($team)
                    <div class="specimen-card p-6 bg-putih-daun shadow-sm">
                        <div class="flex items-center justify-between mb-4 pb-3 border-b border-abu-batu/15">
                            <div>
                                <h3 class="font-display font-bold text-base text-lumut">
                                    Kuota Laporan Aktif Tim
                                </h3>
                                <p class="text-xs text-abu-batu mt-0.5">
                                    Laporan aktif adalah temuan berstatus <code class="bg-sage px-1 py-0.5 rounded text-[11px]">submitted</code> atau <code class="bg-sage px-1 py-0.5 rounded text-[11px]">in_review</code>.
                                </p>
                            </div>
                            <div class="text-right">
                                <span class="font-mono text-2xl font-extrabold text-daun">{{ $team->remainingQuota() }}</span>
                                <span class="text-xs text-abu-batu">/ {{ $team->effectiveQuota() }} slot tersisa</span>
                            </div>
                        </div>

                        <div class="grid grid-cols-2 sm:grid-cols-4 gap-4 text-center">
                            <div class="p-3 rounded-xl bg-sage/40">
                                <span class="text-[10px] text-abu-batu uppercase font-mono block">Aktif</span>
                                <span class="font-display font-bold text-lg text-lumut">{{ $team->activeReportsCount() }}</span>
                            </div>
                            <div class="p-3 rounded-xl bg-sage/40">
                                <span class="text-[10px] text-abu-batu uppercase font-mono block">Batas Kuota</span>
                                <span class="font-display font-bold text-lg text-lumut">{{ $team->effectiveQuota() }}</span>
                            </div>
                            <div class="p-3 rounded-xl bg-sage/40">
                                <span class="text-[10px] text-abu-batu uppercase font-mono block">Total Terkirim</span>
                                <span class="font-display font-bold text-lg text-lumut">{{ $team->totalReportsCount() }}</span>
                            </div>
                            <div class="p-3 rounded-xl bg-sage/40">
                                <span class="text-[10px] text-abu-batu uppercase font-mono block">Status Tim</span>
                                <span class="font-display font-bold text-xs uppercase px-2 py-1 rounded {{ $team->status === 'active' ? 'text-daun bg-daun/10' : 'text-severity-critical bg-severity-critical/10' }}">
                                    {{ $team->status }}
                                </span>
                            </div>
                        </div>

                        <div class="mt-4 pt-3 flex items-center justify-between text-xs">
                            @if($isEmailVerified && $hasTeam && $isRulesAccepted && $isNdaApproved)
                                <a href="{{ route('reports.create') }}" class="btn-clay-primary py-2 px-4 text-xs">
                                    + Kirim Laporan Baru
                                </a>
                            @else
                                <span class="text-abu-batu italic">
                                    Lengkapi seluruh verifikasi untuk mengirim laporan.
                                </span>
                            @endif
                            <a href="{{ route('reports.index') }}" class="text-daun hover:underline font-medium">
                                Lihat Semua Laporan Tim &rarr;
                            </a>
                        </div>
                    </div>
                @endif

                <!-- Pengumuman Tersemat -->
                <div class="specimen-card p-6 bg-putih-daun shadow-sm">
                    <div class="flex items-center justify-between mb-4 pb-2 border-b border-abu-batu/15">
                        <h3 class="font-display font-bold text-base text-lumut flex items-center gap-2">
                            <svg class="w-4 h-4 text-ambar" fill="currentColor" viewBox="0 0 20 20">
                                <path d="M5 4a2 2 0 012-2h6a2 2 0 012 2v14l-5-2.5L5 18V4z"/>
                            </svg>
                            Pengumuman Panitia
                        </h3>
                        <a href="{{ route('announcements.index') }}" class="text-xs text-daun hover:underline">
                            Lihat Semua
                        </a>
                    </div>

                    @forelse($pinnedAnnouncements as $announcement)
                        <div class="mb-4 last:mb-0 p-4 rounded-xl bg-sage/30 border border-abu-batu/15">
                            <div class="flex items-center justify-between text-[11px] text-abu-batu mb-1">
                                <span class="font-mono">{{ $announcement->published_at ? $announcement->published_at->format('d M Y, H:i') : '' }} WIB</span>
                                <span class="px-2 py-0.5 rounded-full bg-ambar/20 text-lumut font-semibold text-[10px]">Tersemat</span>
                            </div>
                            <h4 class="font-display font-bold text-sm text-lumut mb-1">{{ $announcement->title }}</h4>
                            <p class="text-xs text-abu-batu leading-relaxed whitespace-pre-line">{{ Str::limit($announcement->body, 200) }}</p>
                        </div>
                    @empty
                        <p class="text-xs text-abu-batu text-center py-4">Belum ada pengumuman tersemat saat ini.</p>
                    @endforelse
                </div>
            </div>

            <!-- Right Col: Laporan Terbaru Tim (Kartu Spesimen Mini) -->
            <div class="space-y-4">
                <div class="flex items-center justify-between">
                    <h3 class="font-display font-bold text-base text-lumut">
                        Laporan Spesimen Terbaru
                    </h3>
                </div>

                @if($recentReports->isEmpty())
                    <div class="specimen-card p-8 bg-putih-daun text-center border-dashed">
                        <div class="w-10 h-10 rounded-xl bg-sage mx-auto flex items-center justify-center text-abu-batu mb-3">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                            </svg>
                        </div>
                        <p class="text-xs text-lumut font-semibold">Belum Ada Laporan</p>
                        <p class="text-[11px] text-abu-batu mt-1">Tim Anda belum mengirimkan laporan kerentanan.</p>
                    </div>
                @else
                    <div class="space-y-3">
                        @foreach($recentReports as $report)
                            @php
                                $borderClass = match($report->severity_final ?? $report->severity_claimed) {
                                    'critical' => 'specimen-border-critical',
                                    'high' => 'specimen-border-high',
                                    'medium' => 'specimen-border-medium',
                                    'low' => 'specimen-border-low',
                                    default => 'specimen-border-info',
                                };
                            @endphp
                            <a href="{{ route('reports.show', $report->code) }}" class="block specimen-card {{ $borderClass }} p-4 bg-putih-daun hover:shadow-md transition-shadow">
                                <div class="flex items-center justify-between text-[11px] mb-1">
                                    <span class="font-mono font-bold text-lumut">{{ $report->code }}</span>
                                    <span class="px-2 py-0.5 rounded-full text-[10px] font-semibold uppercase bg-sage text-lumut">
                                        {{ $report->status }}
                                    </span>
                                </div>
                                <h4 class="font-display font-bold text-sm text-lumut line-clamp-1">{{ $report->title }}</h4>
                                <div class="text-[11px] text-abu-batu mt-1 flex justify-between">
                                    <span class="truncate mr-2">{{ $report->scope?->name }}</span>
                                    <span class="font-mono font-bold text-daun shrink-0">{{ $report->points_awarded > 0 ? "+{$report->points_awarded} Poin" : '-' }}</span>
                                </div>
                            </a>
                        @endforeach
                    </div>
                @endif
            </div>
        </div>
    </div>
</x-app-layout>
