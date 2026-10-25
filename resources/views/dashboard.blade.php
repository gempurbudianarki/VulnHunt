<x-app-layout>
    <div class="space-y-8">
        <!-- Header Welcome -->
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 pb-4 border-b border-slate-200/80">
            <div>
                <span class="clay-badge bg-indigo-50 text-brand-primary border-indigo-200 text-[10px] uppercase font-bold tracking-wider">
                    Ringkasan Aktivitas Peserta
                </span>
                <h1 class="text-2xl sm:text-3xl font-extrabold text-slate-900 tracking-tight mt-1">
                    Selamat Datang, {{ $user->name }}
                </h1>
                <p class="text-xs text-slate-600 mt-1">
                    NIM: <span class="font-mono text-slate-800 font-bold">{{ $user->nim ?? '-' }}</span> &bull; Prodi: <span class="text-slate-800 font-semibold">{{ $user->study_program ?? '-' }}</span> (Angkatan {{ $user->cohort_year ?? '-' }})
                </p>
            </div>

            @if($team)
                <div class="clay-card-light p-3.5 flex items-center gap-3.5 bg-white shrink-0">
                    <div class="w-11 h-11 rounded-clay-md bg-indigo-50 border border-indigo-100 text-brand-primary flex items-center justify-center font-bold text-sm shadow-sm">
                        {{ strtoupper(substr($team->name, 0, 2)) }}
                    </div>
                    <div>
                        <div class="text-[10px] text-slate-600 uppercase font-mono font-bold tracking-wider">Tim Ekspedisi</div>
                        <div class="font-bold text-sm text-slate-900 leading-tight">{{ $team->name }}</div>
                        <div class="text-[11px] text-slate-600 mt-0.5">
                            Peran: <span class="font-bold text-brand-primary">{{ $user->teamMember?->role === 'leader' ? 'Ketua Tim' : 'Anggota' }}</span>
                        </div>
                    </div>
                </div>
            @endif
        </div>

        <!-- Gerbang Verifikasi Status Checklist (Per Anggota) -->
        <div class="clay-card p-6 sm:p-7 relative">
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 mb-5 pb-4 border-b border-slate-100">
                <div>
                    <h2 class="font-bold text-lg text-slate-900 tracking-tight">
                        Status Verifikasi & Gerbang Akses
                    </h2>
                    <p class="text-xs text-slate-600 mt-0.5">
                        Akses target scope dan pengiriman laporan akan otomatis terbuka jika keempat tahapan di bawah terpenuhi.
                    </p>
                </div>
                <div>
                    @if($isEmailVerified && $hasTeam && $isRulesAccepted && $isNdaApproved)
                        <span class="clay-badge bg-emerald-50 text-emerald-800 border-emerald-200 py-1 px-3">
                            <span class="w-2 h-2 rounded-full bg-emerald-500"></span>
                            Akses Penuh Terbuka
                        </span>
                    @else
                        <span class="clay-badge bg-amber-50 text-amber-800 border-amber-200 py-1 px-3">
                            <span class="w-2 h-2 rounded-full bg-amber-500 animate-pulse"></span>
                            Perlu Tindakan
                        </span>
                    @endif
                </div>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                <!-- 1. Email -->
                <div class="p-4 rounded-clay-md border-2 {{ $isEmailVerified ? 'bg-emerald-50/50 border-emerald-200 text-emerald-950' : 'bg-rose-50/50 border-rose-200 text-rose-950' }} transition-all">
                    <div class="flex items-center justify-between mb-2">
                        <span class="text-[11px] font-mono font-bold uppercase tracking-wider text-slate-500">01. EMAIL</span>
                        @if($isEmailVerified)
                            <span class="text-xs text-emerald-700 font-bold flex items-center gap-1">✓ Terverifikasi</span>
                        @else
                            <span class="text-xs text-rose-600 font-bold">Belum</span>
                        @endif
                    </div>
                    <p class="text-xs font-bold text-slate-900 truncate">{{ $user->email }}</p>
                    <p class="text-[11px] text-slate-500 mt-1">Identitas kampus UBBG aktif.</p>
                </div>

                <!-- 2. Tim -->
                <div class="p-4 rounded-clay-md border-2 {{ $hasTeam ? 'bg-emerald-50/50 border-emerald-200 text-emerald-950' : 'bg-amber-50/50 border-amber-200 text-amber-950' }} transition-all">
                    <div class="flex items-center justify-between mb-2">
                        <span class="text-[11px] font-mono font-bold uppercase tracking-wider text-slate-500">02. TIM</span>
                        @if($hasTeam)
                            <span class="text-xs text-emerald-700 font-bold flex items-center gap-1">✓ Terdaftar</span>
                        @else
                            <a href="{{ route('teams.index') }}" class="text-xs text-amber-800 hover:underline font-bold">Buat/Gabung &rarr;</a>
                        @endif
                    </div>
                    <p class="text-xs font-bold text-slate-900 truncate">{{ $team ? $team->name : 'Belum ada tim' }}</p>
                    <p class="text-[11px] text-slate-500 mt-1">Basis kompetisi tim.</p>
                </div>

                <!-- 3. Aturan -->
                <div class="p-4 rounded-clay-md border-2 {{ $isRulesAccepted ? 'bg-emerald-50/50 border-emerald-200 text-emerald-950' : 'bg-amber-50/50 border-amber-200 text-amber-950' }} transition-all">
                    <div class="flex items-center justify-between mb-2">
                        <span class="text-[11px] font-mono font-bold uppercase tracking-wider text-slate-500">03. ATURAN</span>
                        @if($isRulesAccepted)
                            <span class="text-xs text-emerald-700 font-bold flex items-center gap-1">✓ Versi {{ $user->rules_accepted_version }}</span>
                        @else
                            <a href="{{ route('rules.index') }}" class="text-xs text-amber-800 hover:underline font-bold">Setujui &rarr;</a>
                        @endif
                    </div>
                    <p class="text-xs font-bold text-slate-900">Rules of Engagement</p>
                    <p class="text-[11px] text-slate-500 mt-1">Versi aktif: v{{ $activeRulesVersion }}</p>
                </div>

                <!-- 4. NDA -->
                <div class="p-4 rounded-clay-md border-2 {{ $isNdaApproved ? 'bg-emerald-50/50 border-emerald-200 text-emerald-950' : ($latestNda && $latestNda->isPending() ? 'bg-amber-50/50 border-amber-200 text-amber-950' : 'bg-rose-50/50 border-rose-200 text-rose-950') }} transition-all">
                    <div class="flex items-center justify-between mb-2">
                        <span class="text-[11px] font-mono font-bold uppercase tracking-wider text-slate-500">04. PERJANJIAN NDA</span>
                        @if($isNdaApproved)
                            <span class="text-xs text-emerald-700 font-bold flex items-center gap-1">✓ Disetujui</span>
                        @elseif($latestNda && $latestNda->isPending())
                            <span class="text-xs text-amber-800 font-bold">Menunggu Review</span>
                        @else
                            <a href="{{ route('verification.index') }}" class="text-xs text-rose-600 hover:underline font-bold">Unggah &rarr;</a>
                        @endif
                    </div>
                    <p class="text-xs font-bold text-slate-900 truncate">
                        {{ $latestNda ? $latestNda->document_number : 'Belum diunggah' }}
                    </p>
                    <p class="text-[11px] text-slate-500 mt-1">
                        @if($latestNda && $latestNda->isRejected())
                            <span class="text-rose-600 font-bold">Ditolak: {{ $latestNda->reject_reason }}</span>
                        @else
                            Dokumen legal safe harbor.
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
                    <div class="clay-card p-6 relative">
                        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 pb-4 border-b border-slate-100 mb-5">
                            <div>
                                <h3 class="font-bold text-base text-slate-900">
                                    Kuota Laporan Aktif Tim
                                </h3>
                                <p class="text-xs text-slate-500 mt-0.5">
                                    Laporan aktif adalah temuan berstatus <code class="bg-slate-100 text-slate-800 px-1 py-0.5 rounded font-mono text-[11px]">new</code>, <code class="bg-slate-100 text-slate-800 px-1 py-0.5 rounded font-mono text-[11px]">in_review</code>, atau <code class="bg-slate-100 text-slate-800 px-1 py-0.5 rounded font-mono text-[11px]">needs_info</code>.
                                </p>
                            </div>
                            <div class="text-left sm:text-right shrink-0">
                                <span class="font-mono text-3xl font-black text-brand-primary">{{ $team->remainingQuota() }}</span>
                                <span class="text-xs font-medium text-slate-500">/ {{ $team->effectiveQuota() }} slot tersisa</span>
                            </div>
                        </div>

                        <div class="grid grid-cols-2 sm:grid-cols-4 gap-4 text-center">
                            <div class="p-3.5 rounded-clay-md bg-slate-50/80 border border-slate-200/80">
                                <span class="text-[10px] text-slate-500 uppercase font-mono font-bold block mb-0.5">Laporan Aktif</span>
                                <span class="font-bold text-xl text-slate-900">{{ $team->activeReportsCount() }}</span>
                            </div>
                            <div class="p-3.5 rounded-clay-md bg-slate-50/80 border border-slate-200/80">
                                <span class="text-[10px] text-slate-500 uppercase font-mono font-bold block mb-0.5">Batas Kuota</span>
                                <span class="font-bold text-xl text-slate-900">{{ $team->effectiveQuota() }}</span>
                            </div>
                            <div class="p-3.5 rounded-clay-md bg-slate-50/80 border border-slate-200/80">
                                <span class="text-[10px] text-slate-500 uppercase font-mono font-bold block mb-0.5">Total Terkirim</span>
                                <span class="font-bold text-xl text-slate-900">{{ $team->totalReportsCount() }}</span>
                            </div>
                            <div class="p-3.5 rounded-clay-md bg-slate-50/80 border border-slate-200/80">
                                <span class="text-[10px] text-slate-500 uppercase font-mono font-bold block mb-0.5">Status Tim</span>
                                <span class="clay-badge {{ $team->status === 'active' ? 'bg-emerald-50 text-emerald-800 border-emerald-200' : 'bg-rose-50 text-rose-800 border-rose-200' }} text-[11px] uppercase">
                                    {{ $team->status }}
                                </span>
                            </div>
                        </div>

                        <div class="mt-6 pt-4 border-t border-slate-100 flex flex-col sm:flex-row sm:items-center justify-between gap-3 text-xs">
                            @if($isEmailVerified && $hasTeam && $isRulesAccepted && $isNdaApproved)
                                <x-clay.button href="{{ route('reports.create') }}" variant="primary" size="sm">
                                    + Kirim Laporan Baru
                                </x-clay.button>
                            @else
                                <span class="text-slate-500 italic">
                                    Lengkapi seluruh verifikasi untuk mengirim laporan.
                                </span>
                            @endif
                            <a href="{{ route('reports.index') }}" class="text-brand-primary hover:underline font-bold">
                                Lihat Semua Laporan Tim &rarr;
                            </a>
                        </div>
                    </div>
                @endif

                <!-- Pengumuman Tersemat -->
                <div class="clay-card p-6 relative">
                    <div class="flex items-center justify-between pb-3 border-b border-slate-100 mb-4">
                        <h3 class="font-bold text-base text-slate-900 flex items-center gap-2">
                            <svg class="w-4 h-4 text-brand-accent" fill="currentColor" viewBox="0 0 20 20">
                                <path d="M5 4a2 2 0 012-2h6a2 2 0 012 2v14l-5-2.5L5 18V4z"/>
                            </svg>
                            Pengumuman Panitia
                        </h3>
                        <a href="{{ route('announcements.index') }}" class="text-xs font-bold text-brand-primary hover:underline">
                            Lihat Semua
                        </a>
                    </div>

                    @forelse($pinnedAnnouncements as $announcement)
                        <div class="mb-4 last:mb-0 p-4 rounded-clay-md bg-slate-50/80 border border-slate-200/80">
                            <div class="flex items-center justify-between text-[11px] text-slate-500 mb-1.5">
                                <span class="font-mono">{{ $announcement->published_at ? $announcement->published_at->format('d M Y, H:i') : '' }} WIB</span>
                                <span class="clay-badge bg-amber-50 text-amber-900 border-amber-200 text-[10px]">Tersemat</span>
                            </div>
                            <h4 class="font-bold text-sm text-slate-900 mb-1">{{ $announcement->title }}</h4>
                            <p class="text-xs text-slate-600 leading-relaxed whitespace-pre-line">{{ Str::limit($announcement->body, 200) }}</p>
                        </div>
                    @empty
                        <p class="text-xs text-slate-500 text-center py-6">Belum ada pengumuman tersemat saat ini.</p>
                    @endforelse
                </div>
            </div>

            <!-- Right Col: Laporan Terbaru Tim (Kartu Spesimen Mini) -->
            <div class="space-y-4">
                <div class="flex items-center justify-between">
                    <h3 class="font-bold text-base text-slate-900">
                        Laporan Spesimen Terbaru
                    </h3>
                </div>

                @if($recentReports->isEmpty())
                    <x-clay.empty-state
                        title="Belum Ada Laporan"
                        description="Tim Anda belum mengirimkan laporan kerentanan ke panitia."
                    />
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
                            <a href="{{ route('reports.show', $report->code) }}" class="block specimen-card {{ $borderClass }} p-4 bg-white hover:shadow-clay-card-hover transition-all">
                                <div class="flex items-center justify-between text-[11px] mb-1.5">
                                    <span class="font-mono font-bold text-slate-900">{{ $report->code }}</span>
                                    <span class="clay-badge bg-slate-100 text-slate-800 border-slate-200 text-[10px] uppercase font-bold">
                                        {{ $report->status }}
                                    </span>
                                </div>
                                <h4 class="font-bold text-sm text-slate-900 line-clamp-1">{{ $report->title }}</h4>
                                <div class="text-[11px] text-slate-500 mt-2 flex items-center justify-between border-t border-slate-100 pt-1.5">
                                    <span class="truncate mr-2">{{ $report->scope?->name }}</span>
                                    <span class="font-mono font-bold text-brand-primary shrink-0">{{ $report->points_awarded > 0 ? "+{$report->points_awarded} Poin" : '-' }}</span>
                                </div>
                            </a>
                        @endforeach
                    </div>
                @endif
            </div>
        </div>
    </div>
</x-app-layout>
