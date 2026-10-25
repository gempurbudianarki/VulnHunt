<x-app-layout>
    <div class="max-w-4xl mx-auto space-y-8">
        <!-- Header Laporan (Kartu Spesimen) -->
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 pb-4 border-b border-slate-200/80">
            <div>
                <div class="flex flex-wrap items-center gap-2">
                    <span class="clay-badge bg-indigo-50 text-brand-primary border-indigo-200 text-[10px] uppercase font-bold tracking-wider">
                        Kode Spesimen
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
                        Status: {{ strtoupper(str_replace('_', ' ', $report->status)) }}
                    </span>
                </div>
                <h1 class="text-2xl sm:text-3xl font-extrabold font-mono text-slate-900 tracking-tight mt-1">
                    {{ $report->code }}
                </h1>
                <p class="text-sm font-bold text-slate-700 mt-1">
                    {{ $report->title }}
                </p>
            </div>
            <a href="{{ route('reports.index') }}"
               class="inline-flex items-center text-xs font-bold text-brand-primary hover:underline">
                &larr; Kembali ke Daftar Laporan
            </a>
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

        <!-- Kotak Intervensi jika status needs_info -->
        @if ($report->status === 'needs_info')
            <div class="clay-card p-6 border-2 border-orange-300 bg-orange-50/30 space-y-4">
                <div class="flex items-center gap-2.5">
                    <span class="w-3 h-3 rounded-full bg-orange-500 animate-ping"></span>
                    <h2 class="font-extrabold text-sm text-orange-900 uppercase font-mono tracking-wider">
                        Tindakan Diperlukan: Panitia Membutuhkan Informasi Tambahan
                    </h2>
                </div>
                <p class="text-xs text-slate-700 leading-relaxed">
                    Panitia penilai telah menandai laporan ini berstatus <strong>Needs Info</strong>. Silakan baca catatan panitia di bawah dan kirimkan tanggapan atau revisi langkah reproduksi temuan Anda.
                </p>

                <form method="POST" action="{{ route('reports.reply', $report->code) }}" enctype="multipart/form-data" class="space-y-4 pt-2">
                    @csrf
                    <div>
                        <label for="reply_message" class="block text-xs font-bold uppercase tracking-wider text-slate-700 mb-1.5">
                            Pesan Tanggapan Tim <span class="text-rose-500">*</span>
                        </label>
                        <textarea id="reply_message" name="reply_message" rows="3" required
                                  placeholder="Tuliskan klarifikasi atau informasi tambahan yang diminta panitia..."
                                  class="clay-input @error('reply_message') border-rose-400 bg-rose-50/30 @enderror">{{ old('reply_message') }}</textarea>
                        @error('reply_message')
                            <p class="text-xs font-semibold text-rose-600 mt-1.5 flex items-center gap-1">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label for="steps_to_reproduce" class="block text-xs font-bold uppercase tracking-wider text-slate-700 mb-1.5">
                            Revisi Langkah Reproduksi (Opsional, minimal 100 karakter jika diperbarui)
                        </label>
                        <textarea id="steps_to_reproduce" name="steps_to_reproduce" rows="4"
                                  placeholder="Perbarui langkah reproduksi jika diminta oleh panitia..."
                                  class="clay-input font-mono text-xs @error('steps_to_reproduce') border-rose-400 bg-rose-50/30 @enderror">{{ old('steps_to_reproduce') }}</textarea>
                        @error('steps_to_reproduce')
                            <p class="text-xs font-semibold text-rose-600 mt-1.5 flex items-center gap-1">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label for="additional_attachments" class="block text-xs font-bold uppercase tracking-wider text-slate-700 mb-1.5">
                            Unggah Bukti Tambahan (Maksimal 4 Berkas, 25 MB per Berkas)
                        </label>
                        <input id="additional_attachments" type="file" name="attachments[]" multiple
                               accept=".png,.jpg,.jpeg,.webp,.mp4,.webm,.pdf,.txt,.md"
                               class="clay-input text-xs file:mr-3 file:py-1.5 file:px-3 file:rounded-clay-pill file:border-0 file:text-xs file:font-semibold file:bg-orange-600 file:text-white hover:file:bg-orange-700">
                        @error('attachments')
                            <p class="text-xs font-semibold text-rose-600 mt-1.5 flex items-center gap-1">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="flex justify-end pt-2">
                        <button type="submit"
                                class="btn-clay-accent py-2.5 px-6 text-xs">
                            Kirim Tanggapan & Kembalikan ke Dalam Review
                        </button>
                    </div>
                </form>
            </div>
        @endif

        <!-- Rincian Taksonomi & CVSS -->
        <div class="clay-card p-6 sm:p-7 space-y-6">
            <h2 class="text-sm font-bold font-mono uppercase tracking-wider text-slate-800 border-b border-slate-100 pb-3 flex items-center gap-2">
                <span class="w-2.5 h-2.5 rounded-full bg-brand-primary"></span>
                Identitas Spesimen Temuan
            </h2>

            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 text-xs">
                <div class="p-3.5 bg-slate-50 rounded-clay-md border border-slate-200/80">
                    <span class="text-slate-400 text-[10px] uppercase font-mono font-bold block">Habitat Target</span>
                    <span class="font-bold text-slate-900 mt-1 block">{{ $report->scope->name }}</span>
                    <span class="font-mono text-[11px] text-brand-primary block truncate mt-0.5">{{ $report->scope->target }}</span>
                </div>

                <div class="p-3.5 bg-slate-50 rounded-clay-md border border-slate-200/80">
                    <span class="text-slate-400 text-[10px] uppercase font-mono font-bold block">Taksonomi CWE</span>
                    <span class="font-bold text-slate-900 mt-1 block font-mono">{{ $report->vulnerabilityType->cwe_id }}</span>
                    <span class="text-[11px] text-slate-500 block truncate mt-0.5">{{ $report->vulnerabilityType->name }}</span>
                </div>

                <div class="p-3.5 bg-slate-50 rounded-clay-md border border-slate-200/80">
                    <span class="text-slate-400 text-[10px] uppercase font-mono font-bold block">Klaim Severity</span>
                    <span class="font-bold uppercase mt-1 block
                        @if($report->severity_claimed === 'critical') text-rose-600
                        @elseif($report->severity_claimed === 'high') text-orange-600
                        @elseif($report->severity_claimed === 'medium') text-amber-600
                        @else text-sky-600
                        @endif">
                        {{ $report->severity_claimed }} ({{ $report->cvss_score_claimed }})
                    </span>
                    <span class="font-mono text-[10px] text-slate-400 block truncate mt-0.5">{{ $report->cvss_vector_claimed }}</span>
                </div>

                <div class="p-3.5 bg-slate-50 rounded-clay-md border border-slate-200/80">
                    <span class="text-slate-400 text-[10px] uppercase font-mono font-bold block">Keputusan Panitia</span>
                    @if ($report->severity_final)
                        <span class="font-bold uppercase text-emerald-700 mt-1 block">
                            {{ $report->severity_final }} ({{ $report->cvss_score_final }})
                        </span>
                        <span class="font-mono font-bold text-[11px] text-brand-primary block mt-0.5">+{{ $report->points_awarded ?? 0 }} Poin</span>
                    @else
                        <span class="text-slate-400 italic mt-1 block">Menunggu Triage</span>
                    @endif
                </div>
            </div>

            <!-- Path Endpoint & Parameter -->
            <div class="p-4 bg-slate-50 rounded-clay-md border border-slate-200/80 space-y-2 text-xs">
                <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-1">
                    <span class="font-mono font-bold text-slate-500 text-[11px]">Endpoint Temuan:</span>
                    <span class="font-mono font-bold text-slate-900 select-all">{{ $report->endpoint }}</span>
                </div>
                @if ($report->parameter)
                    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-1 border-t border-slate-200 pt-2">
                        <span class="font-mono font-bold text-slate-500 text-[11px]">Parameter Rentan:</span>
                        <span class="font-mono font-bold text-amber-700 select-all">{{ $report->parameter }}</span>
                    </div>
                @endif
            </div>
        </div>

        <!-- Narasi Teknis Terenkripsi -->
        <div class="clay-card p-6 sm:p-7 space-y-6">
            <h2 class="text-sm font-bold font-mono uppercase tracking-wider text-slate-800 border-b border-slate-100 pb-3 flex items-center gap-2">
                <span class="w-2.5 h-2.5 rounded-full bg-brand-primary"></span>
                Uraian Teknis (Dekripsi Sisi Server)
            </h2>

            <div class="space-y-4 text-xs">
                <div>
                    <h3 class="font-bold text-slate-800 mb-1.5 uppercase text-[11px] tracking-wider">Deskripsi Temuan:</h3>
                    <div class="p-4 bg-slate-50 rounded-clay-md border border-slate-200/80 text-slate-800 whitespace-pre-wrap leading-relaxed">
                        {{ $report->description }}
                    </div>
                </div>

                <div>
                    <h3 class="font-bold text-slate-800 mb-1.5 uppercase text-[11px] tracking-wider">Langkah Reproduksi (Steps to Reproduce):</h3>
                    <div class="p-4 bg-slate-50 rounded-clay-md border border-slate-200/80 text-slate-800 font-mono text-[11px] whitespace-pre-wrap leading-relaxed">
                        {{ $report->steps_to_reproduce }}
                    </div>
                </div>

                <div>
                    <h3 class="font-bold text-slate-800 mb-1.5 uppercase text-[11px] tracking-wider">Dampak Potensial (Impact):</h3>
                    <div class="p-4 bg-slate-50 rounded-clay-md border border-slate-200/80 text-slate-800 whitespace-pre-wrap leading-relaxed">
                        {{ $report->impact }}
                    </div>
                </div>

                @if ($report->remediation)
                    <div>
                        <h3 class="font-bold text-slate-800 mb-1.5 uppercase text-[11px] tracking-wider">Saran Mitigasi / Perbaikan:</h3>
                        <div class="p-4 bg-slate-50 rounded-clay-md border border-slate-200/80 text-slate-800 whitespace-pre-wrap leading-relaxed">
                            {{ $report->remediation }}
                        </div>
                    </div>
                @endif
            </div>
        </div>

        <!-- Berkas Lampiran Bukti -->
        <div class="clay-card p-6 sm:p-7 space-y-4">
            <h2 class="text-sm font-bold font-mono uppercase tracking-wider text-slate-800 border-b border-slate-100 pb-3 flex items-center gap-2">
                <span class="w-2.5 h-2.5 rounded-full bg-brand-primary"></span>
                Berkas Lampiran Bukti ({{ $report->attachments->count() }} Berkas)
            </h2>

            @if ($report->attachments->isEmpty())
                <p class="text-xs text-slate-400 italic">Tidak ada berkas lampiran yang diunggah.</p>
            @else
                <div class="divide-y divide-slate-100">
                    @foreach($report->attachments as $att)
                        <div class="py-3 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2 text-xs">
                            <div>
                                <span class="font-bold text-slate-900 block">{{ $att->original_name }}</span>
                                <div class="flex items-center gap-2 mt-0.5 text-[11px] text-slate-500 font-mono">
                                    <span>{{ round($att->size / 1024, 1) }} KB</span>
                                    <span>&bull;</span>
                                    <span>{{ $att->mime }}</span>
                                    <span>&bull;</span>
                                    <span class="truncate max-w-[200px]" title="{{ $att->sha256 }}">SHA: {{ substr($att->sha256, 0, 16) }}...</span>
                                </div>
                            </div>
                            <div>
                                <x-clay.button href="{{ route('reports.attachment.download', [$report->code, $att->id]) }}" variant="secondary" size="sm">
                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"></path>
                                    </svg>
                                    Unduh Lampiran
                                </x-clay.button>
                            </div>
                        </div>
                    @endforeach
                </div>
            @endif
        </div>

        <!-- Diskusi / Komunikasi Laporan -->
        @if ($report->comments->isNotEmpty())
            <div class="clay-card p-6 sm:p-7 space-y-4">
                <h2 class="text-sm font-bold font-mono uppercase tracking-wider text-slate-800 border-b border-slate-100 pb-3 flex items-center gap-2">
                    <span class="w-2.5 h-2.5 rounded-full bg-brand-primary"></span>
                    Catatan & Komunikasi Laporan ({{ $report->comments->count() }})
                </h2>

                <div class="space-y-3 text-xs">
                    @foreach($report->comments as $comment)
                        <div class="p-4 rounded-clay-md {{ $comment->user_id === $report->reporter_id ? 'bg-indigo-50/50 border border-indigo-200' : 'bg-amber-50/50 border border-amber-200' }}">
                            <div class="flex items-center justify-between mb-2">
                                <span class="font-bold text-slate-900">{{ $comment->user->name }}</span>
                                <span class="text-[11px] text-slate-500 font-mono">{{ $comment->created_at->format('d M Y H:i') }}</span>
                            </div>
                            <p class="text-slate-800 whitespace-pre-wrap leading-relaxed">{{ $comment->body }}</p>
                        </div>
                    @endforeach
                </div>
            </div>
        @endif

        <!-- Riwayat Perubahan Status (Timeline) -->
        <div class="clay-card p-6 sm:p-7 space-y-4">
            <h2 class="text-sm font-bold font-mono uppercase tracking-wider text-slate-800 border-b border-slate-100 pb-3 flex items-center gap-2">
                <span class="w-2.5 h-2.5 rounded-full bg-brand-primary"></span>
                Linimasa Riwayat Status
            </h2>

            <div class="space-y-4 text-xs">
                @foreach($report->statusHistories as $history)
                    <div class="flex items-start gap-3">
                        <span class="w-2 h-2 rounded-full bg-brand-primary mt-1.5 shrink-0"></span>
                        <div class="flex-1">
                            <div class="flex items-center justify-between">
                                <span class="font-bold text-slate-900 uppercase font-mono">{{ $history->to_status }}</span>
                                <span class="text-[11px] text-slate-500 font-mono">{{ $history->created_at->format('d M Y H:i') }}</span>
                            </div>
                            <p class="text-slate-600 mt-0.5">{{ $history->reason ?? 'Perubahan status laporan.' }}</p>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    </div>
</x-app-layout>
