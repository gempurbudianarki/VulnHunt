<x-app-layout>
    <div class="space-y-8">
        <!-- Header & Kuota Aktif -->
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <div>
                <span class="text-xs font-mono font-bold text-ambar uppercase tracking-wider">Buku Lapangan Koleksi Bug</span>
                <h1 class="text-2xl sm:text-3xl font-extrabold text-lumut tracking-tight mt-0.5">
                    Laporan Kerentanan Tim
                </h1>
                <p class="text-xs text-abu-batu mt-1">
                    Daftar seluruh temuan celah keamanan yang telah dikirimkan oleh anggota tim Anda.
                </p>
            </div>

            <div class="flex items-center gap-3">
                <!-- Indikator Kuota Aktif -->
                <div class="px-4 py-2 rounded-card bg-putih-daun border border-abu-batu/20 shadow-sm flex items-center gap-3 text-xs">
                    <div>
                        <span class="text-[10px] uppercase font-mono text-abu-batu block">Kuota Laporan Aktif</span>
                        <span class="font-mono font-bold text-lumut">
                            {{ $activeReportsCount }} / {{ $effectiveQuota }} Terpakai
                        </span>
                    </div>
                    <span class="px-2 py-0.5 rounded text-[11px] font-mono font-bold"
                          @class([
                              'bg-daun/10 text-daun' => $remainingQuota > 0,
                              'bg-severity-critical/10 text-severity-critical' => $remainingQuota <= 0,
                          ])>
                        {{ $remainingQuota }} Tersisa
                    </span>
                </div>

                @if ($remainingQuota > 0)
                    <a href="{{ route('reports.create') }}"
                       class="py-2.5 px-4 rounded-btn font-semibold text-white bg-daun hover:bg-daun/90 shadow-clay-btn transition text-xs flex items-center gap-1.5">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
                        </svg>
                        Kirim Laporan Baru
                    </a>
                @else
                    <button disabled title="Kuota laporan aktif tim Anda telah penuh."
                            class="py-2.5 px-4 rounded-btn font-semibold text-white bg-abu-batu/50 cursor-not-allowed text-xs flex items-center gap-1.5 opacity-60">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
                        </svg>
                        Kuota Laporan Penuh
                    </button>
                @endif
            </div>
        </div>

        @if (session('success'))
            <div class="p-4 rounded-card bg-daun/10 border border-daun/30 text-daun text-xs font-semibold">
                {{ session('success') }}
            </div>
        @endif

        @if (session('error'))
            <div class="p-4 rounded-card bg-severity-critical/10 border border-severity-critical/20 text-severity-critical text-xs">
                {{ session('error') }}
            </div>
        @endif

        <!-- Daftar Kartu Spesimen Laporan -->
        <div class="space-y-4">
            @forelse($reports as $report)
                <div class="specimen-card p-6 bg-putih-daun shadow-sm hover:shadow-md transition">
                    <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
                        <div class="space-y-2 flex-1">
                            <div class="flex flex-wrap items-center gap-2">
                                <span class="font-mono text-xs font-extrabold text-lumut bg-sage/40 px-2.5 py-0.5 rounded border border-abu-batu/20">
                                    {{ $report->code }}
                                </span>

                                <span class="font-mono text-[11px] font-bold px-2 py-0.5 rounded uppercase"
                                      @class([
                                          'bg-severity-info/10 text-severity-info border border-severity-info/20' => $report->status === 'draft',
                                          'bg-amber-500/10 text-amber-600 border border-amber-500/20' => $report->status === 'submitted',
                                          'bg-severity-medium/10 text-severity-medium border border-severity-medium/20' => $report->status === 'in_review',
                                          'bg-orange-500/10 text-orange-600 border border-orange-500/20' => $report->status === 'needs_info',
                                          'bg-severity-low/10 text-severity-low border border-severity-low/20' => $report->status === 'accepted',
                                          'bg-severity-critical/10 text-severity-critical border border-severity-critical/20' => in_array($report->status, ['rejected', 'spam', 'out_of_scope']),
                                          'bg-purple-500/10 text-purple-600 border border-purple-500/20' => $report->status === 'duplicate',
                                          'bg-emerald-500/10 text-emerald-600 border border-emerald-500/20' => $report->status === 'resolved',
                                      ])>
                                    {{ strtoupper(str_replace('_', ' ', $report->status)) }}
                                </span>

                                <span class="font-mono text-[11px] font-bold px-2 py-0.5 rounded uppercase"
                                      @class([
                                          'bg-severity-info/10 text-severity-info' => $report->severity_claimed === 'info',
                                          'bg-severity-low/10 text-severity-low' => $report->severity_claimed === 'low',
                                          'bg-severity-medium/10 text-severity-medium' => $report->severity_claimed === 'medium',
                                          'bg-severity-high/10 text-severity-high' => $report->severity_claimed === 'high',
                                          'bg-severity-critical/10 text-severity-critical' => $report->severity_claimed === 'critical',
                                      ])>
                                    CVSS: {{ $report->cvss_score_claimed }} ({{ strtoupper($report->severity_claimed) }})
                                </span>

                                @if ($report->status === 'needs_info')
                                    <span class="px-2 py-0.5 bg-orange-600 text-white text-[10px] font-bold rounded-full animate-pulse">
                                        Perlu Tanggapan Tim
                                    </span>
                                @endif
                            </div>

                            <h2 class="text-base font-bold text-lumut">
                                <a href="{{ route('reports.show', $report->code) }}" class="hover:text-daun hover:underline">
                                    {{ $report->title }}
                                </a>
                            </h2>

                            <div class="flex flex-wrap items-center gap-4 text-xs text-abu-batu font-mono">
                                <span>Habitat: <strong class="text-lumut">{{ $report->scope->name }}</strong></span>
                                <span>&bull;</span>
                                <span>Taksonomi: <strong class="text-lumut">{{ $report->vulnerabilityType->cwe_id }}</strong></span>
                                <span>&bull;</span>
                                <span>Endpoint: <code class="text-lumut">{{ $report->endpoint }}</code></span>
                                <span>&bull;</span>
                                <span>Waktu: {{ $report->submitted_at ? $report->submitted_at->format('d M Y H:i') : '-' }}</span>
                            </div>
                        </div>

                        <div class="flex items-center gap-2 self-end md:self-center">
                            <a href="{{ route('reports.show', $report->code) }}"
                               class="py-2 px-4 rounded-btn font-semibold text-xs text-daun bg-daun/10 hover:bg-daun hover:text-white transition">
                                Buka Spesimen &rarr;
                            </a>
                        </div>
                    </div>
                </div>
            @empty
                <div class="specimen-card p-12 bg-putih-daun text-center space-y-3">
                    <div class="w-12 h-12 mx-auto rounded-full bg-sage/30 flex items-center justify-center text-abu-batu">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                        </svg>
                    </div>
                    <h3 class="font-display font-bold text-base text-lumut">Belum Ada Koleksi Spesimen Celah</h3>
                    <p class="text-xs text-abu-batu max-w-sm mx-auto">
                        Tim Anda belum pernah mengirimkan laporan temuan kerentanan. Klik tombol di bawah untuk membuat laporan pertama Anda.
                    </p>
                    @if ($remainingQuota > 0)
                        <div class="pt-2">
                            <a href="{{ route('reports.create') }}"
                               class="inline-flex py-2.5 px-5 rounded-btn font-semibold text-white bg-daun hover:bg-daun/90 shadow-clay-btn text-xs">
                                Buat Laporan Temuan Sekarang
                            </a>
                        </div>
                    @endif
                </div>
            @endforelse
        </div>
    </div>
</x-app-layout>
