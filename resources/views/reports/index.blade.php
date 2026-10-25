<x-app-layout>
    <div class="space-y-8">
        <!-- Header & Kuota Aktif -->
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 pb-4 border-b border-slate-200/80">
            <div>
                <span class="clay-badge bg-indigo-50 text-brand-primary border-indigo-200 text-[10px] uppercase font-bold tracking-wider">
                    Daftar Temuan Keamanan
                </span>
                <h1 class="text-2xl sm:text-3xl font-extrabold text-slate-900 tracking-tight mt-1">
                    Laporan Kerentanan Tim
                </h1>
                <p class="text-xs text-slate-500 mt-1">
                    Daftar seluruh temuan celah keamanan yang telah dikirimkan oleh anggota tim Anda.
                </p>
            </div>

            <div class="flex flex-wrap items-center gap-3">
                <!-- Indikator Kuota Aktif -->
                <div class="px-4 py-2 rounded-clay-md bg-white border border-slate-200/80 shadow-clay-card-light flex items-center gap-3 text-xs">
                    <div>
                        <span class="text-[10px] uppercase font-mono font-bold text-slate-400 block">Kuota Laporan Aktif</span>
                        <span class="font-mono font-bold text-slate-900">
                            {{ $activeReportsCount }} / {{ $effectiveQuota }} Terpakai
                        </span>
                    </div>
                    <span class="clay-badge {{ $remainingQuota > 0 ? 'bg-emerald-50 text-emerald-800 border-emerald-200' : 'bg-rose-50 text-rose-800 border-rose-200' }} text-[11px] font-mono font-bold">
                        {{ $remainingQuota }} Tersisa
                    </span>
                </div>

                @if ($remainingQuota > 0)
                    <x-clay.button href="{{ route('reports.create') }}" variant="primary" size="md">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
                        </svg>
                        Kirim Laporan Baru
                    </x-clay.button>
                @else
                    <button disabled title="Kuota laporan aktif tim Anda telah penuh."
                            class="inline-flex items-center gap-2 px-5 py-2.5 font-semibold text-white bg-slate-400 rounded-clay-pill shadow-sm cursor-not-allowed text-sm opacity-60">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
                        </svg>
                        Kuota Laporan Penuh
                    </button>
                @endif
            </div>
        </div>

        @if (session('success'))
            <x-clay.alert type="success">
                {{ session('success') }}
            </x-clay.alert>
        @endif

        @if (session('error'))
            <x-clay.alert type="danger">
                {{ session('error') }}
            </x-clay.alert>
        @endif

        <!-- Daftar Kartu Spesimen Laporan -->
        <div class="space-y-4">
            @forelse($reports as $report)
                @php
                    $borderClass = match($report->severity_final ?? $report->severity_claimed) {
                        'critical' => 'specimen-border-critical',
                        'high' => 'specimen-border-high',
                        'medium' => 'specimen-border-medium',
                        'low' => 'specimen-border-low',
                        default => 'specimen-border-info',
                    };
                @endphp
                <div class="specimen-card {{ $borderClass }} p-5 sm:p-6 bg-white shadow-clay-card-light hover:shadow-clay-card-hover transition-all">
                    <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
                        <div class="space-y-2 flex-1">
                            <div class="flex flex-wrap items-center gap-2">
                                <span class="font-mono text-xs font-bold text-slate-800 bg-slate-100 px-2.5 py-0.5 rounded-clay-pill border border-slate-200">
                                    {{ $report->code }}
                                </span>

                                <span class="clay-badge text-[11px] font-bold uppercase
                                    @if($report->status === 'draft') bg-slate-100 text-slate-700 border-slate-200
                                    @elseif($report->status === 'submitted') bg-amber-50 text-amber-800 border-amber-200
                                    @elseif($report->status === 'in_review') bg-indigo-50 text-brand-primary border-indigo-200
                                    @elseif($report->status === 'needs_info') bg-orange-50 text-orange-800 border-orange-200
                                    @elseif($report->status === 'accepted') bg-emerald-50 text-emerald-800 border-emerald-200
                                    @elseif(in_array($report->status, ['rejected', 'spam', 'out_of_scope'])) bg-rose-50 text-rose-800 border-rose-200
                                    @elseif($report->status === 'duplicate') bg-purple-50 text-purple-800 border-purple-200
                                    @elseif($report->status === 'resolved') bg-emerald-50 text-emerald-800 border-emerald-200
                                    @endif">
                                    {{ strtoupper(str_replace('_', ' ', $report->status)) }}
                                </span>

                                <span class="clay-badge text-[11px] font-bold uppercase
                                    @if($report->severity_claimed === 'critical') bg-rose-50 text-rose-700 border-rose-200
                                    @elseif($report->severity_claimed === 'high') bg-orange-50 text-orange-700 border-orange-200
                                    @elseif($report->severity_claimed === 'medium') bg-amber-50 text-amber-800 border-amber-200
                                    @elseif($report->severity_claimed === 'low') bg-sky-50 text-sky-800 border-sky-200
                                    @else bg-slate-100 text-slate-700 border-slate-200
                                    @endif">
                                    CVSS: {{ $report->cvss_score_claimed }} ({{ strtoupper($report->severity_claimed) }})
                                </span>

                                @if ($report->status === 'needs_info')
                                    <span class="px-2 py-0.5 bg-orange-600 text-white text-[10px] font-bold rounded-clay-pill animate-pulse">
                                        Perlu Tanggapan Tim
                                    </span>
                                @endif
                            </div>

                            <h2 class="text-base font-bold text-slate-900">
                                <a href="{{ route('reports.show', $report->code) }}" class="hover:text-brand-primary transition-colors">
                                    {{ $report->title }}
                                </a>
                            </h2>

                            <div class="flex flex-wrap items-center gap-4 text-xs text-slate-500 font-mono">
                                <span>Habitat: <strong class="text-slate-800">{{ $report->scope->name }}</strong></span>
                                <span>&bull;</span>
                                <span>Taksonomi: <strong class="text-slate-800">{{ $report->vulnerabilityType->cwe_id }}</strong></span>
                                <span>&bull;</span>
                                <span>Endpoint: <code class="text-slate-800">{{ $report->endpoint }}</code></span>
                                <span>&bull;</span>
                                <span>Waktu: {{ $report->submitted_at ? $report->submitted_at->format('d M Y H:i') : '-' }}</span>
                            </div>
                        </div>

                        <div class="flex items-center gap-2 self-end md:self-center">
                            <x-clay.button href="{{ route('reports.show', $report->code) }}" variant="secondary" size="sm">
                                Buka Spesimen &rarr;
                            </x-clay.button>
                        </div>
                    </div>
                </div>
            @empty
                <x-clay.empty-state
                    title="Belum Ada Koleksi Spesimen Celah"
                    description="Tim Anda belum pernah mengirimkan laporan temuan kerentanan. Mulai eksplorasi target scope dan laporkan temuan pertama Anda."
                >
                    @if ($remainingQuota > 0)
                        <x-slot:action>
                            <x-clay.button href="{{ route('reports.create') }}" variant="primary" size="md">
                                Buat Laporan Temuan Sekarang
                            </x-clay.button>
                        </x-slot:action>
                    @endif
                </x-clay.empty-state>
            @endforelse
        </div>
    </div>
</x-app-layout>
