<x-app-layout>
    <div class="max-w-4xl mx-auto space-y-8">
        <!-- Header Laporan (Kartu Spesimen) -->
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <div>
                <div class="flex items-center gap-2">
                    <span class="font-mono text-xs font-bold text-ambar bg-lumut/5 px-3 py-1 rounded-full uppercase tracking-wider">
                        Nomor Koleksi Spesimen
                    </span>
                    <span class="font-mono text-xs font-bold px-2.5 py-0.5 rounded-full uppercase"
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
                        Status: {{ strtoupper(str_replace('_', ' ', $report->status)) }}
                    </span>
                </div>
                <h1 class="text-2xl sm:text-3xl font-extrabold font-mono text-lumut tracking-tight mt-1">
                    {{ $report->code }}
                </h1>
                <p class="text-sm font-semibold text-lumut/80 mt-1">
                    {{ $report->title }}
                </p>
            </div>
            <a href="{{ route('reports.index') }}"
               class="inline-flex items-center text-xs font-semibold text-abu-batu hover:text-lumut">
                &larr; Kembali ke Daftar Laporan
            </a>
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

        <!-- Kotak Intervensi jika status needs_info -->
        @if ($report->status === 'needs_info')
            <div class="specimen-card p-6 bg-orange-500/5 border-2 border-orange-500/30 rounded-card space-y-4">
                <div class="flex items-center gap-2">
                    <span class="w-3 h-3 rounded-full bg-orange-500 animate-ping"></span>
                    <h2 class="font-bold text-sm text-orange-700 uppercase font-mono tracking-wider">
                        Tindakan Diperlukan: Panitia Membutuhkan Informasi Tambahan
                    </h2>
                </div>
                <p class="text-xs text-lumut">
                    Panitia penilai telah menandai laporan ini berstatus <strong>Needs Info</strong>. Silakan baca catatan panitia di bawah dan kirimkan tanggapan atau revisi langkah reproduksi temuan Anda.
                </p>

                <form method="POST" action="{{ route('reports.reply', $report->code) }}" enctype="multipart/form-data" class="space-y-4 pt-2">
                    @csrf
                    <div>
                        <label for="reply_message" class="block text-xs font-semibold text-lumut mb-1">
                            Pesan Tanggapan Tim <span class="text-severity-critical">*</span>
                        </label>
                        <textarea id="reply_message" name="reply_message" rows="3" required
                                  placeholder="Tuliskan klarifikasi atau informasi tambahan yang diminta panitia..."
                                  class="w-full px-3.5 py-2.5 rounded-input bg-white border border-orange-500/30 focus:border-orange-500 focus:ring focus:ring-orange-500/20 text-xs text-lumut">{{ old('reply_message') }}</textarea>
                        @error('reply_message')
                            <p class="text-xs text-severity-critical mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label for="steps_to_reproduce" class="block text-xs font-semibold text-lumut mb-1">
                            Revisi Langkah Reproduksi (Opsional, minimal 100 karakter jika diperbarui)
                        </label>
                        <textarea id="steps_to_reproduce" name="steps_to_reproduce" rows="4"
                                  placeholder="Perbarui langkah reproduksi jika diminta oleh panitia..."
                                  class="w-full px-3.5 py-2.5 rounded-input bg-white border border-orange-500/30 focus:border-orange-500 focus:ring focus:ring-orange-500/20 text-xs font-mono text-lumut">{{ old('steps_to_reproduce') }}</textarea>
                        @error('steps_to_reproduce')
                            <p class="text-xs text-severity-critical mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-lumut mb-1">
                            Unggah Bukti Tambahan (Maksimal 4 Berkas, 25 MB per Berkas)
                        </label>
                        <input type="file" name="attachments[]" multiple
                               accept=".png,.jpg,.jpeg,.webp,.mp4,.webm,.pdf,.txt,.md"
                               class="w-full text-xs text-abu-batu file:mr-4 file:py-2 file:px-3 file:rounded-btn file:border-0 file:text-xs file:font-semibold file:bg-orange-600 file:text-white hover:file:bg-orange-700 file:cursor-pointer border border-orange-500/30 rounded-input p-2 bg-white">
                        @error('attachments')
                            <p class="text-xs text-severity-critical mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="flex justify-end">
                        <button type="submit"
                                class="py-2.5 px-6 rounded-btn font-semibold text-white bg-orange-600 hover:bg-orange-700 shadow-sm transition text-xs">
                            Kirim Tanggapan & Kembalikan ke Dalam Review
                        </button>
                    </div>
                </form>
            </div>
        @endif

        <!-- Rincian Taksonomi & CVSS -->
        <div class="specimen-card p-6 bg-putih-daun shadow-sm space-y-6">
            <h2 class="text-sm font-bold font-mono uppercase tracking-wider text-lumut border-b border-abu-batu/20 pb-3 flex items-center gap-2">
                <span class="w-2.5 h-2.5 rounded-full bg-ambar"></span>
                Identitas Spesimen Temuan
            </h2>

            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 text-xs">
                <div class="p-3 bg-sage/20 rounded-card">
                    <span class="text-abu-batu text-[10px] uppercase font-mono block">Habitat Target</span>
                    <span class="font-bold text-lumut mt-0.5 block">{{ $report->scope->name }}</span>
                    <span class="font-mono text-[11px] text-daun block truncate">{{ $report->scope->target }}</span>
                </div>

                <div class="p-3 bg-sage/20 rounded-card">
                    <span class="text-abu-batu text-[10px] uppercase font-mono block">Taksonomi CWE</span>
                    <span class="font-bold text-lumut mt-0.5 block font-mono">{{ $report->vulnerabilityType->cwe_id }}</span>
                    <span class="text-[11px] text-abu-batu block truncate">{{ $report->vulnerabilityType->name }}</span>
                </div>

                <div class="p-3 bg-sage/20 rounded-card">
                    <span class="text-abu-batu text-[10px] uppercase font-mono block">Klaim Severity (Peserta)</span>
                    <span class="font-bold uppercase mt-0.5 block"
                          @class([
                              'text-severity-info' => $report->severity_claimed === 'info',
                              'text-severity-low' => $report->severity_claimed === 'low',
                              'text-severity-medium' => $report->severity_claimed === 'medium',
                              'text-severity-high' => $report->severity_claimed === 'high',
                              'text-severity-critical' => $report->severity_claimed === 'critical',
                          ])>
                        {{ $report->severity_claimed }} ({{ $report->cvss_score_claimed }})
                    </span>
                    <span class="font-mono text-[10px] text-abu-batu block truncate">{{ $report->cvss_vector_claimed }}</span>
                </div>

                <div class="p-3 bg-sage/20 rounded-card">
                    <span class="text-abu-batu text-[10px] uppercase font-mono block">Keputusan Akhir (Panitia)</span>
                    @if ($report->severity_final)
                        <span class="font-bold uppercase text-daun mt-0.5 block">
                            {{ $report->severity_final }} ({{ $report->cvss_score_final }})
                        </span>
                        <span class="font-mono text-[10px] text-daun block">Poin: {{ $report->points_awarded ?? 0 }}</span>
                    @else
                        <span class="text-abu-batu italic mt-0.5 block">Menunggu Triage</span>
                    @endif
                </div>
            </div>

            <!-- Path Endpoint & Parameter -->
            <div class="p-4 bg-sage/10 rounded-card space-y-2 text-xs">
                <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-1">
                    <span class="font-mono text-abu-batu text-[11px]">Endpoint Temuan:</span>
                    <span class="font-mono font-bold text-lumut select-all">{{ $report->endpoint }}</span>
                </div>
                @if ($report->parameter)
                    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-1 border-t border-abu-batu/10 pt-2">
                        <span class="font-mono text-abu-batu text-[11px]">Parameter Rentan:</span>
                        <span class="font-mono font-bold text-ambar select-all">{{ $report->parameter }}</span>
                    </div>
                @endif
            </div>
        </div>

        <!-- Narasi Teknis Terenkripsi -->
        <div class="specimen-card p-6 bg-putih-daun shadow-sm space-y-6">
            <h2 class="text-sm font-bold font-mono uppercase tracking-wider text-lumut border-b border-abu-batu/20 pb-3 flex items-center gap-2">
                <span class="w-2.5 h-2.5 rounded-full bg-daun"></span>
                Uraian Teknis (Dekripsi Sisi Server)
            </h2>

            <div class="space-y-4 text-xs">
                <div>
                    <h3 class="font-semibold text-lumut mb-1">Deskripsi Temuan:</h3>
                    <div class="p-3.5 bg-sage/20 rounded-card text-lumut whitespace-pre-wrap leading-relaxed">
                        {{ $report->description }}
                    </div>
                </div>

                <div>
                    <h3 class="font-semibold text-lumut mb-1">Langkah Reproduksi (Steps to Reproduce):</h3>
                    <div class="p-3.5 bg-sage/20 rounded-card text-lumut font-mono text-[11px] whitespace-pre-wrap leading-relaxed">
                        {{ $report->steps_to_reproduce }}
                    </div>
                </div>

                <div>
                    <h3 class="font-semibold text-lumut mb-1">Dampak Potensial (Impact):</h3>
                    <div class="p-3.5 bg-sage/20 rounded-card text-lumut whitespace-pre-wrap leading-relaxed">
                        {{ $report->impact }}
                    </div>
                </div>

                @if ($report->remediation)
                    <div>
                        <h3 class="font-semibold text-lumut mb-1">Saran Mitigasi / Perbaikan:</h3>
                        <div class="p-3.5 bg-sage/20 rounded-card text-lumut whitespace-pre-wrap leading-relaxed">
                            {{ $report->remediation }}
                        </div>
                    </div>
                @endif
            </div>
        </div>

        <!-- Berkas Lampiran Bukti -->
        <div class="specimen-card p-6 bg-putih-daun shadow-sm space-y-4">
            <h2 class="text-sm font-bold font-mono uppercase tracking-wider text-lumut border-b border-abu-batu/20 pb-3 flex items-center gap-2">
                <span class="w-2.5 h-2.5 rounded-full bg-severity-low"></span>
                Berkas Lampiran Bukti ({{ $report->attachments->count() }} Berkas)
            </h2>

            @if ($report->attachments->isEmpty())
                <p class="text-xs text-abu-batu italic">Tidak ada berkas lampiran yang diunggah.</p>
            @else
                <div class="divide-y divide-abu-batu/10">
                    @foreach($report->attachments as $att)
                        <div class="py-3 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2 text-xs">
                            <div>
                                <span class="font-semibold text-lumut block">{{ $att->original_name }}</span>
                                <div class="flex items-center gap-2 mt-0.5 text-[11px] text-abu-batu font-mono">
                                    <span>{{ round($att->size / 1024, 1) }} KB</span>
                                    <span>&bull;</span>
                                    <span>{{ $att->mime }}</span>
                                    <span>&bull;</span>
                                    <span class="truncate max-w-[200px]" title="{{ $att->sha256 }}">SHA: {{ substr($att->sha256, 0, 16) }}...</span>
                                </div>
                            </div>
                            <div>
                                <a href="{{ route('reports.attachment.download', [$report->code, $att->id]) }}"
                                   class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-btn bg-daun/10 text-daun hover:bg-daun hover:text-white font-semibold transition text-xs">
                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"></path>
                                    </svg>
                                    Unduh Lampiran
                                </a>
                            </div>
                        </div>
                    @endforeach
                </div>
            @endif
        </div>

        <!-- Diskusi / Komunikasi Laporan -->
        @if ($report->comments->isNotEmpty())
            <div class="specimen-card p-6 bg-putih-daun shadow-sm space-y-4">
                <h2 class="text-sm font-bold font-mono uppercase tracking-wider text-lumut border-b border-abu-batu/20 pb-3 flex items-center gap-2">
                    <span class="w-2.5 h-2.5 rounded-full bg-daun"></span>
                    Catatan & Komunikasi Laporan ({{ $report->comments->count() }})
                </h2>

                <div class="space-y-3 text-xs">
                    @foreach($report->comments as $comment)
                        <div class="p-4 rounded-card {{ $comment->user_id === $report->reporter_id ? 'bg-sage/20 border border-daun/20' : 'bg-orange-500/10 border border-orange-500/20' }}">
                            <div class="flex items-center justify-between mb-2">
                                <span class="font-bold text-lumut">{{ $comment->user->name }}</span>
                                <span class="text-[11px] text-abu-batu font-mono">{{ $comment->created_at->format('d M Y H:i') }}</span>
                            </div>
                            <p class="text-lumut whitespace-pre-wrap leading-relaxed">{{ $comment->body }}</p>
                        </div>
                    @endforeach
                </div>
            </div>
        @endif

        <!-- Riwayat Perubahan Status (Timeline) -->
        <div class="specimen-card p-6 bg-putih-daun shadow-sm space-y-4">
            <h2 class="text-sm font-bold font-mono uppercase tracking-wider text-lumut border-b border-abu-batu/20 pb-3 flex items-center gap-2">
                <span class="w-2.5 h-2.5 rounded-full bg-lumut"></span>
                Linimasa Riwayat Status
            </h2>

            <div class="space-y-4 text-xs">
                @foreach($report->statusHistories as $history)
                    <div class="flex items-start gap-3">
                        <span class="w-2 h-2 rounded-full bg-daun mt-1.5"></span>
                        <div class="flex-1">
                            <div class="flex items-center justify-between">
                                <span class="font-bold text-lumut uppercase font-mono">{{ $history->to_status }}</span>
                                <span class="text-[11px] text-abu-batu font-mono">{{ $history->created_at->format('d M Y H:i') }}</span>
                            </div>
                            <p class="text-abu-batu mt-0.5">{{ $history->reason ?? 'Perubahan status laporan.' }}</p>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    </div>
</x-app-layout>
