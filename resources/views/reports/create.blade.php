<x-app-layout>
    <div class="max-w-4xl mx-auto space-y-8" x-data="reportForm()">
        <!-- Header Halaman -->
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <div>
                <div class="flex items-center gap-2">
                    <span class="font-mono text-xs font-bold text-amber-900 bg-amber-50 border border-amber-200/80 px-3 py-1 rounded-clay-pill uppercase tracking-wider">
                        Koleksi Baru Spesimen Celah
                    </span>
                    <span class="font-mono text-xs font-semibold px-2.5 py-0.5 rounded-clay-pill bg-brand-primary/10 text-brand-primary border border-brand-primary/20">
                        Sisa Kuota: {{ $team->remainingQuota() }} Slot
                    </span>
                </div>
                <h1 class="text-2xl sm:text-3xl font-extrabold text-slate-900 tracking-tight mt-1">
                    Formulir Pengajuan Laporan Temuan
                </h1>
                <p class="text-xs text-slate-600 mt-1">
                    Lengkapi seluruh rincian langkah reproduksi dan kalkulasi skor CVSS v3.1 sebelum mengirim laporan.
                </p>
            </div>
            <a href="{{ route('reports.index') }}"
               class="inline-flex items-center text-xs font-semibold text-slate-600 hover:text-slate-900">
                &larr; Kembali ke Daftar Laporan
            </a>
        </div>

        @if (session('error'))
            <div class="p-4 rounded-card bg-severity-critical/10 border border-severity-critical/20 text-severity-critical text-xs">
                {{ session('error') }}
            </div>
        @endif

        <form method="POST" action="{{ route('reports.store') }}" enctype="multipart/form-data" class="space-y-8">
            @csrf

            <!-- 1. Metadata Target & Taksonomi -->
            <div class="specimen-card p-6 bg-putih-daun shadow-sm space-y-6">
                <h2 class="text-sm font-bold font-mono uppercase tracking-wider text-lumut flex items-center gap-2 border-b border-abu-batu/20 pb-3">
                    <span class="w-2.5 h-2.5 rounded-full bg-ambar"></span>
                    01. Habitat Target & Taksonomi Celah
                </h2>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <label for="scope_id" class="block text-xs font-semibold text-lumut mb-1">
                            Target Aset (In-Scope) <span class="text-severity-critical">*</span>
                        </label>
                        <select id="scope_id" name="scope_id" required
                                class="w-full px-3.5 py-2.5 rounded-input bg-sage/30 border border-abu-batu/20 focus:border-daun focus:ring focus:ring-daun/20 text-xs text-lumut font-medium">
                            <option value="">-- Pilih Target Dalam Scope --</option>
                            @foreach($scopes as $scope)
                                <option value="{{ $scope->id }}" {{ old('scope_id') == $scope->id ? 'selected' : '' }}>
                                    [{{ strtoupper($scope->asset_type) }}] {{ $scope->name }} ({{ $scope->target }}) - Bobot: {{ $scope->criticality_weight }}x
                                </option>
                            @endforeach
                        </select>
                        @error('scope_id')
                            <p class="text-xs text-severity-critical mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label for="vulnerability_type_id" class="block text-xs font-semibold text-lumut mb-1">
                            Jenis Kerentanan (CWE) <span class="text-severity-critical">*</span>
                        </label>
                        <select id="vulnerability_type_id" name="vulnerability_type_id" required
                                class="w-full px-3.5 py-2.5 rounded-input bg-sage/30 border border-abu-batu/20 focus:border-daun focus:ring focus:ring-daun/20 text-xs text-lumut font-medium">
                            <option value="">-- Pilih Taksonomi CWE --</option>
                            @foreach($vulnerabilityTypes as $vuln)
                                <option value="{{ $vuln->id }}" {{ old('vulnerability_type_id') == $vuln->id ? 'selected' : '' }}>
                                    {{ $vuln->cwe_id }}: {{ $vuln->name }}
                                </option>
                            @endforeach
                        </select>
                        @error('vulnerability_type_id')
                            <p class="text-xs text-severity-critical mt-1">{{ $message }}</p>
                        @enderror
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                    <div class="md:col-span-2">
                        <label for="endpoint" class="block text-xs font-semibold text-lumut mb-1">
                            Endpoint / Path URL Temuan <span class="text-severity-critical">*</span>
                        </label>
                        <input id="endpoint" type="text" name="endpoint" value="{{ old('endpoint') }}" required
                               placeholder="/api/v1/users atau /login.php"
                               class="w-full px-3.5 py-2.5 rounded-input bg-sage/30 border border-abu-batu/20 focus:border-daun focus:ring focus:ring-daun/20 text-xs font-mono text-lumut">
                        @error('endpoint')
                            <p class="text-xs text-severity-critical mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label for="parameter" class="block text-xs font-semibold text-lumut mb-1">
                            Parameter Rentan (Opsional)
                        </label>
                        <input id="parameter" type="text" name="parameter" value="{{ old('parameter') }}"
                               placeholder="id, redirect, token"
                               class="w-full px-3.5 py-2.5 rounded-input bg-sage/30 border border-abu-batu/20 focus:border-daun focus:ring focus:ring-daun/20 text-xs font-mono text-lumut">
                        @error('parameter')
                            <p class="text-xs text-severity-critical mt-1">{{ $message }}</p>
                        @enderror
                    </div>
                </div>
            </div>

            <!-- 2. Kalkulator Interaktif CVSS v3.1 -->
            <div class="specimen-card p-6 bg-putih-daun shadow-sm space-y-6" x-data="cvssCalculator()" x-init="calculate()">
                <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between border-b border-abu-batu/20 pb-3 gap-2">
                    <h2 class="text-sm font-bold font-mono uppercase tracking-wider text-lumut flex items-center gap-2">
                        <span class="w-2.5 h-2.5 rounded-full bg-severity-medium"></span>
                        02. Kalkulator CVSS v3.1 Interaktif
                    </h2>
                    <div class="flex items-center gap-2">
                        <span class="font-mono text-xs px-3 py-1.5 rounded-clay-sm font-black tracking-wide"
                              :class="{
                                  'bg-sky-100 text-sky-950 border border-sky-300': severity === 'info',
                                  'bg-emerald-100 text-emerald-950 border border-emerald-300': severity === 'low',
                                  'bg-amber-100 text-amber-950 border border-amber-300': severity === 'medium',
                                  'bg-orange-100 text-orange-950 border border-orange-300': severity === 'high',
                                  'bg-rose-100 text-rose-950 border border-rose-300': severity === 'critical'
                              }"
                              x-text="severity.toUpperCase() + ' (' + score + ')'">
                        </span>
                    </div>
                </div>

                <!-- Hidden Input untuk dikirim ke backend -->
                <input type="hidden" name="cvss_vector_claimed" :value="vector">

                <!-- Vector Badge -->
                <div class="p-3 bg-sage/20 border border-abu-batu/20 rounded-card font-mono text-xs text-lumut break-all flex items-center justify-between">
                    <div>
                        <span class="text-abu-batu text-[10px] block">Klaim Vector:</span>
                        <span x-text="vector" class="font-semibold text-daun"></span>
                    </div>
                </div>

                <!-- Grid Metrik CVSS -->
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 text-xs">
                    <!-- Attack Vector (AV) -->
                    <div class="p-3 rounded-card bg-sage/10 border border-abu-batu/10 space-y-1.5">
                        <span class="font-mono text-[11px] font-bold text-lumut block">Attack Vector (AV)</span>
                        <div class="space-y-1">
                            <label class="flex items-center gap-2.5 py-2 px-2.5 rounded-clay-sm hover:bg-slate-200/60 cursor-pointer min-h-[44px]">
                                <input type="radio" name="calc_av" value="N" x-model="av" @change="calculate()" class="w-4 h-4 text-daun focus:ring-daun shrink-0">
                                <span>Network (N)</span>
                            </label>
                            <label class="flex items-center gap-2.5 py-2 px-2.5 rounded-clay-sm hover:bg-slate-200/60 cursor-pointer min-h-[44px]">
                                <input type="radio" name="calc_av" value="A" x-model="av" @change="calculate()" class="w-4 h-4 text-daun focus:ring-daun shrink-0">
                                <span>Adjacent (A)</span>
                            </label>
                            <label class="flex items-center gap-2.5 py-2 px-2.5 rounded-clay-sm hover:bg-slate-200/60 cursor-pointer min-h-[44px]">
                                <input type="radio" name="calc_av" value="L" x-model="av" @change="calculate()" class="w-4 h-4 text-daun focus:ring-daun shrink-0">
                                <span>Local (L)</span>
                            </label>
                            <label class="flex items-center gap-2.5 py-2 px-2.5 rounded-clay-sm hover:bg-slate-200/60 cursor-pointer min-h-[44px]">
                                <input type="radio" name="calc_av" value="P" x-model="av" @change="calculate()" class="w-4 h-4 text-daun focus:ring-daun shrink-0">
                                <span>Physical (P)</span>
                            </label>
                        </div>
                    </div>

                    <!-- Attack Complexity (AC) -->
                    <div class="p-3 rounded-card bg-sage/10 border border-abu-batu/10 space-y-1.5">
                        <span class="font-mono text-[11px] font-bold text-lumut block">Complexity (AC)</span>
                        <div class="space-y-1">
                            <label class="flex items-center gap-2.5 py-2 px-2.5 rounded-clay-sm hover:bg-slate-200/60 cursor-pointer min-h-[44px]">
                                <input type="radio" name="calc_ac" value="L" x-model="ac" @change="calculate()" class="w-4 h-4 text-daun focus:ring-daun shrink-0">
                                <span>Low (L)</span>
                            </label>
                            <label class="flex items-center gap-2.5 py-2 px-2.5 rounded-clay-sm hover:bg-slate-200/60 cursor-pointer min-h-[44px]">
                                <input type="radio" name="calc_ac" value="H" x-model="ac" @change="calculate()" class="w-4 h-4 text-daun focus:ring-daun shrink-0">
                                <span>High (H)</span>
                            </label>
                        </div>
                    </div>

                    <!-- Privileges Required (PR) -->
                    <div class="p-3 rounded-card bg-sage/10 border border-abu-batu/10 space-y-1.5">
                        <span class="font-mono text-[11px] font-bold text-lumut block">Privileges (PR)</span>
                        <div class="space-y-1">
                            <label class="flex items-center gap-2.5 py-2 px-2.5 rounded-clay-sm hover:bg-slate-200/60 cursor-pointer min-h-[44px]">
                                <input type="radio" name="calc_pr" value="N" x-model="pr" @change="calculate()" class="w-4 h-4 text-daun focus:ring-daun shrink-0">
                                <span>None (N)</span>
                            </label>
                            <label class="flex items-center gap-2.5 py-2 px-2.5 rounded-clay-sm hover:bg-slate-200/60 cursor-pointer min-h-[44px]">
                                <input type="radio" name="calc_pr" value="L" x-model="pr" @change="calculate()" class="w-4 h-4 text-daun focus:ring-daun shrink-0">
                                <span>Low (L)</span>
                            </label>
                            <label class="flex items-center gap-2.5 py-2 px-2.5 rounded-clay-sm hover:bg-slate-200/60 cursor-pointer min-h-[44px]">
                                <input type="radio" name="calc_pr" value="H" x-model="pr" @change="calculate()" class="w-4 h-4 text-daun focus:ring-daun shrink-0">
                                <span>High (H)</span>
                            </label>
                        </div>
                    </div>

                    <!-- User Interaction (UI) -->
                    <div class="p-3 rounded-card bg-sage/10 border border-abu-batu/10 space-y-1.5">
                        <span class="font-mono text-[11px] font-bold text-lumut block">User Interaction (UI)</span>
                        <div class="space-y-1">
                            <label class="flex items-center gap-2.5 py-2 px-2.5 rounded-clay-sm hover:bg-slate-200/60 cursor-pointer min-h-[44px]">
                                <input type="radio" name="calc_ui" value="N" x-model="ui" @change="calculate()" class="w-4 h-4 text-daun focus:ring-daun shrink-0">
                                <span>None (N)</span>
                            </label>
                            <label class="flex items-center gap-2.5 py-2 px-2.5 rounded-clay-sm hover:bg-slate-200/60 cursor-pointer min-h-[44px]">
                                <input type="radio" name="calc_ui" value="R" x-model="ui" @change="calculate()" class="w-4 h-4 text-daun focus:ring-daun shrink-0">
                                <span>Required (R)</span>
                            </label>
                        </div>
                    </div>

                    <!-- Scope (S) -->
                    <div class="p-3 rounded-card bg-sage/10 border border-abu-batu/10 space-y-1.5">
                        <span class="font-mono text-[11px] font-bold text-lumut block">Scope (S)</span>
                        <div class="space-y-1">
                            <label class="flex items-center gap-2.5 py-2 px-2.5 rounded-clay-sm hover:bg-slate-200/60 cursor-pointer min-h-[44px]">
                                <input type="radio" name="calc_s" value="U" x-model="s" @change="calculate()" class="w-4 h-4 text-daun focus:ring-daun shrink-0">
                                <span>Unchanged (U)</span>
                            </label>
                            <label class="flex items-center gap-2.5 py-2 px-2.5 rounded-clay-sm hover:bg-slate-200/60 cursor-pointer min-h-[44px]">
                                <input type="radio" name="calc_s" value="C" x-model="s" @change="calculate()" class="w-4 h-4 text-daun focus:ring-daun shrink-0">
                                <span>Changed (C)</span>
                            </label>
                        </div>
                    </div>

                    <!-- Confidentiality (C) -->
                    <div class="p-3 rounded-card bg-sage/10 border border-abu-batu/10 space-y-1.5">
                        <span class="font-mono text-[11px] font-bold text-lumut block">Confidentiality (C)</span>
                        <div class="space-y-1">
                            <label class="flex items-center gap-2.5 py-2 px-2.5 rounded-clay-sm hover:bg-slate-200/60 cursor-pointer min-h-[44px]">
                                <input type="radio" name="calc_c" value="N" x-model="c" @change="calculate()" class="w-4 h-4 text-daun focus:ring-daun shrink-0">
                                <span>None (N)</span>
                            </label>
                            <label class="flex items-center gap-2.5 py-2 px-2.5 rounded-clay-sm hover:bg-slate-200/60 cursor-pointer min-h-[44px]">
                                <input type="radio" name="calc_c" value="L" x-model="c" @change="calculate()" class="w-4 h-4 text-daun focus:ring-daun shrink-0">
                                <span>Low (L)</span>
                            </label>
                            <label class="flex items-center gap-2.5 py-2 px-2.5 rounded-clay-sm hover:bg-slate-200/60 cursor-pointer min-h-[44px]">
                                <input type="radio" name="calc_c" value="H" x-model="c" @change="calculate()" class="w-4 h-4 text-daun focus:ring-daun shrink-0">
                                <span>High (H)</span>
                            </label>
                        </div>
                    </div>

                    <!-- Integrity (I) -->
                    <div class="p-3 rounded-card bg-sage/10 border border-abu-batu/10 space-y-1.5">
                        <span class="font-mono text-[11px] font-bold text-lumut block">Integrity (I)</span>
                        <div class="space-y-1">
                            <label class="flex items-center gap-2.5 py-2 px-2.5 rounded-clay-sm hover:bg-slate-200/60 cursor-pointer min-h-[44px]">
                                <input type="radio" name="calc_i" value="N" x-model="i" @change="calculate()" class="w-4 h-4 text-daun focus:ring-daun shrink-0">
                                <span>None (N)</span>
                            </label>
                            <label class="flex items-center gap-2.5 py-2 px-2.5 rounded-clay-sm hover:bg-slate-200/60 cursor-pointer min-h-[44px]">
                                <input type="radio" name="calc_i" value="L" x-model="i" @change="calculate()" class="w-4 h-4 text-daun focus:ring-daun shrink-0">
                                <span>Low (L)</span>
                            </label>
                            <label class="flex items-center gap-2.5 py-2 px-2.5 rounded-clay-sm hover:bg-slate-200/60 cursor-pointer min-h-[44px]">
                                <input type="radio" name="calc_i" value="H" x-model="i" @change="calculate()" class="w-4 h-4 text-daun focus:ring-daun shrink-0">
                                <span>High (H)</span>
                            </label>
                        </div>
                    </div>

                    <!-- Availability (A) -->
                    <div class="p-3 rounded-card bg-sage/10 border border-abu-batu/10 space-y-1.5">
                        <span class="font-mono text-[11px] font-bold text-lumut block">Availability (A)</span>
                        <div class="space-y-1">
                            <label class="flex items-center gap-2.5 py-2 px-2.5 rounded-clay-sm hover:bg-slate-200/60 cursor-pointer min-h-[44px]">
                                <input type="radio" name="calc_a" value="N" x-model="a" @change="calculate()" class="w-4 h-4 text-daun focus:ring-daun shrink-0">
                                <span>None (N)</span>
                            </label>
                            <label class="flex items-center gap-2.5 py-2 px-2.5 rounded-clay-sm hover:bg-slate-200/60 cursor-pointer min-h-[44px]">
                                <input type="radio" name="calc_a" value="L" x-model="a" @change="calculate()" class="w-4 h-4 text-daun focus:ring-daun shrink-0">
                                <span>Low (L)</span>
                            </label>
                            <label class="flex items-center gap-2.5 py-2 px-2.5 rounded-clay-sm hover:bg-slate-200/60 cursor-pointer min-h-[44px]">
                                <input type="radio" name="calc_a" value="H" x-model="a" @change="calculate()" class="w-4 h-4 text-daun focus:ring-daun shrink-0">
                                <span>High (H)</span>
                            </label>
                        </div>
                    </div>
                </div>
            </div>

            <!-- 3. Narasi Temuan & Langkah Reproduksi -->
            <div class="specimen-card p-6 bg-putih-daun shadow-sm space-y-6">
                <h2 class="text-sm font-bold font-mono uppercase tracking-wider text-lumut flex items-center gap-2 border-b border-abu-batu/20 pb-3">
                    <span class="w-2.5 h-2.5 rounded-full bg-daun"></span>
                    03. Narasi Teknis & Bukti Eksploitasi
                </h2>

                <div>
                    <label for="title" class="block text-xs font-semibold text-lumut mb-1">
                        Judul Laporan Singkat & Jelas <span class="text-severity-critical">*</span>
                    </label>
                    <input id="title" type="text" name="title" value="{{ old('title') }}" required
                           placeholder="Contoh: Reflected XSS pada Parameter 'query' di Halaman Pencarian Katalog"
                           class="w-full px-3.5 py-2.5 rounded-input bg-sage/30 border border-abu-batu/20 focus:border-daun focus:ring focus:ring-daun/20 text-xs font-semibold text-lumut">
                    @error('title')
                        <p class="text-xs text-severity-critical mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="description" class="block text-xs font-semibold text-lumut mb-1">
                        Deskripsi Singkat Temuan <span class="text-severity-critical">*</span>
                    </label>
                    <textarea id="description" name="description" rows="3" required
                              placeholder="Jelaskan secara garis besar kerentanan apa yang ditemukan..."
                              class="w-full px-3.5 py-2.5 rounded-input bg-sage/30 border border-abu-batu/20 focus:border-daun focus:ring focus:ring-daun/20 text-xs text-lumut">{{ old('description') }}</textarea>
                    @error('description')
                        <p class="text-xs text-severity-critical mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <div class="flex items-center justify-between mb-1">
                        <label for="steps_to_reproduce" class="block text-xs font-semibold text-lumut">
                            Langkah Reproduksi (Steps to Reproduce) <span class="text-severity-critical">* (Minimal 100 Karakter)</span>
                        </label>
                        <span class="text-[11px] font-mono"
                              :class="stepsCount >= 100 ? 'text-daun font-bold' : 'text-severity-critical'"
                              x-text="stepsCount + '/100 karakter'">
                        </span>
                    </div>
                    <textarea id="steps_to_reproduce" name="steps_to_reproduce" rows="6" required
                              x-model="stepsText"
                              @input="stepsCount = stepsText.length"
                              placeholder="1. Buka URL target di peramban&#10;2. Masukkan payload berikut pada parameter 'id'&#10;3. Amati respons HTTP dan eksekusi payload..."
                              class="w-full px-3.5 py-2.5 rounded-input bg-sage/30 border border-abu-batu/20 focus:border-daun focus:ring focus:ring-daun/20 text-xs font-mono text-lumut">{{ old('steps_to_reproduce') }}</textarea>
                    @error('steps_to_reproduce')
                        <p class="text-xs text-severity-critical mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <label for="impact" class="block text-xs font-semibold text-lumut mb-1">
                            Dampak Potensial (Impact) <span class="text-severity-critical">*</span>
                        </label>
                        <textarea id="impact" name="impact" rows="3" required
                                  placeholder="Apa kerugian yang dapat ditimbulkan jika celah ini dieksploitasi oleh penyerang..."
                                  class="w-full px-3.5 py-2.5 rounded-input bg-sage/30 border border-abu-batu/20 focus:border-daun focus:ring focus:ring-daun/20 text-xs text-lumut">{{ old('impact') }}</textarea>
                        @error('impact')
                            <p class="text-xs text-severity-critical mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label for="remediation" class="block text-xs font-semibold text-lumut mb-1">
                            Saran Perbaikan / Mitigasi (Opsional)
                        </label>
                        <textarea id="remediation" name="remediation" rows="3"
                                  placeholder="Rekomendasi patch kode atau konfigurasi keamanan..."
                                  class="w-full px-3.5 py-2.5 rounded-input bg-sage/30 border border-abu-batu/20 focus:border-daun focus:ring focus:ring-daun/20 text-xs text-lumut">{{ old('remediation') }}</textarea>
                        @error('remediation')
                            <p class="text-xs text-severity-critical mt-1">{{ $message }}</p>
                        @enderror
                    </div>
                </div>

                <!-- Unggah Bukti (Lampiran) -->
                <div>
                    <label for="attachments" class="block text-xs font-semibold text-slate-800 mb-1">
                        Berkas Lampiran Bukti Eksploitasi (Maksimal 8 Berkas, 25 MB per Berkas)
                    </label>
                    <p class="text-[11px] text-slate-600 mb-2">
                        Format aman yang didukung: PNG, JPG, WEBP, MP4, WEBM, PDF, TXT, MD. Format SVG ditolak mutlak.
                    </p>
                    <input id="attachments" type="file" name="attachments[]" multiple
                           accept=".png,.jpg,.jpeg,.webp,.mp4,.webm,.pdf,.txt,.md"
                           class="w-full text-xs text-slate-700 file:mr-4 file:py-2.5 file:px-4 file:rounded-clay-pill file:border-0 file:text-xs file:font-semibold file:bg-brand-primary file:text-white hover:file:bg-brand-primary-hover file:cursor-pointer border border-slate-200 rounded-clay-md p-2 bg-slate-50">
                    @error('attachments')
                        <p class="text-xs text-severity-critical mt-1">{{ $message }}</p>
                    @enderror
                    @error('attachments.*')
                        <p class="text-xs text-severity-critical mt-1">{{ $message }}</p>
                    @enderror
                </div>
            </div>

            <!-- 4. Pernyataan Etika & Safe Conduct -->
            <div class="specimen-card p-6 bg-putih-daun shadow-sm space-y-4">
                <h2 class="text-sm font-bold font-mono uppercase tracking-wider text-lumut border-b border-abu-batu/20 pb-3">
                    04. Pernyataan Integritas Pengujian (Safe Conduct)
                </h2>

                <div class="space-y-3 text-xs text-lumut">
                    <label class="flex items-start gap-2.5 cursor-pointer">
                        <input type="checkbox" name="agree_no_privacy_violation" value="1" required
                               {{ old('agree_no_privacy_violation') ? 'checked' : '' }}
                               class="rounded text-daun focus:ring-daun mt-0.5">
                        <span>Saya menyatakan bahwa selama pengujian celah ini, saya <strong>tidak mengakses, mengubah, menyalin, atau mengunduh data pribadi</strong> milik civitas akademika atau pihak mana pun.</span>
                    </label>
                    @error('agree_no_privacy_violation')
                        <p class="text-xs text-severity-critical ml-6">{{ $message }}</p>
                    @enderror

                    <label class="flex items-start gap-2.5 cursor-pointer">
                        <input type="checkbox" name="agree_no_destruction" value="1" required
                               {{ old('agree_no_destruction') ? 'checked' : '' }}
                               class="rounded text-daun focus:ring-daun mt-0.5">
                        <span>Saya menyatakan bahwa pengujian ini <strong>tidak merusak, mendegradasi kinerja, atau mengganggu ketersediaan layanan</strong> target kampus (tidak ada serangan DoS/DDoS).</span>
                    </label>
                    @error('agree_no_destruction')
                        <p class="text-xs text-severity-critical ml-6">{{ $message }}</p>
                    @enderror

                    <label class="flex items-start gap-2.5 cursor-pointer">
                        <input type="checkbox" name="agree_in_scope_only" value="1" required
                               {{ old('agree_in_scope_only') ? 'checked' : '' }}
                               class="rounded text-daun focus:ring-daun mt-0.5">
                        <span>Saya menyatakan bahwa pengujian ini <strong>hanya dilakukan pada target yang terdaftar secara resmi di In-Scope</strong> perlombaan.</span>
                    </label>
                    @error('agree_in_scope_only')
                        <p class="text-xs text-severity-critical ml-6">{{ $message }}</p>
                    @enderror
                </div>
            </div>

            <!-- Tombol Kirim -->
            <div class="flex items-center justify-end gap-4">
                <a href="{{ route('reports.index') }}"
                   class="py-3 px-6 rounded-clay-pill font-semibold text-slate-600 hover:text-slate-900 transition text-xs">
                    Batalkan
                </a>
                <button type="submit"
                        :disabled="stepsCount < 100"
                        class="py-3.5 px-8 rounded-btn font-semibold text-white bg-daun hover:bg-daun/90 shadow-clay-btn transition text-xs flex items-center gap-2 disabled:opacity-50 disabled:cursor-not-allowed">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8"></path>
                    </svg>
                    Kirim Laporan Kerentanan Resmi
                </button>
            </div>
        </form>
    </div>

    <!-- Script Alpine.js Form & CVSS Calculator -->
    <script>
        function reportForm() {
            return {
                stepsText: @json(old('steps_to_reproduce', '')),
                stepsCount: {{ strlen(old('steps_to_reproduce', '')) }}
            }
        }

        function cvssCalculator() {
            return {
                av: 'N',
                ac: 'L',
                pr: 'N',
                ui: 'N',
                s: 'U',
                c: 'H',
                i: 'H',
                a: 'H',
                vector: 'CVSS:3.1/AV:N/AC:L/PR:N/UI:N/S:U/C:H/I:H/A:H',
                score: '9.8',
                severity: 'critical',

                weights: {
                    av: { N: 0.85, A: 0.62, L: 0.55, P: 0.20 },
                    ac: { L: 0.77, H: 0.44 },
                    pr: {
                        U: { N: 0.85, L: 0.62, H: 0.27 },
                        C: { N: 0.85, L: 0.68, H: 0.50 }
                    },
                    ui: { N: 0.85, R: 0.62 },
                    c: { H: 0.56, L: 0.22, N: 0.00 },
                    i: { H: 0.56, L: 0.22, N: 0.00 },
                    a: { H: 0.56, L: 0.22, N: 0.00 }
                },

                roundup(x) {
                    let i = Math.round(x * 100000);
                    if (i % 10000 === 0) return i / 100000;
                    return (Math.floor(i / 10000) + 1) / 10;
                },

                calculate() {
                    this.vector = `CVSS:3.1/AV:${this.av}/AC:${this.ac}/PR:${this.pr}/UI:${this.ui}/S:${this.s}/C:${this.c}/I:${this.i}/A:${this.a}`;

                    let av = this.weights.av[this.av];
                    let ac = this.weights.ac[this.ac];
                    let pr = this.weights.pr[this.s][this.pr];
                    let ui = this.weights.ui[this.ui];
                    let c = this.weights.c[this.c];
                    let i = this.weights.i[this.i];
                    let a = this.weights.a[this.a];

                    let iss = 1.0 - ((1.0 - c) * (1.0 - i) * (1.0 - a));
                    let impact = 0;
                    if (this.s === 'U') {
                        impact = 6.42 * iss;
                    } else {
                        impact = 7.52 * (iss - 0.029) - 3.25 * Math.pow((iss - 0.02), 15);
                    }

                    let exploitability = 8.22 * av * ac * pr * ui;

                    let base = 0;
                    if (impact <= 0) {
                        base = 0.0;
                    } else if (this.s === 'U') {
                        base = this.roundup(Math.min(impact + exploitability, 10.0));
                    } else {
                        base = this.roundup(Math.min(1.08 * (impact + exploitability), 10.0));
                    }

                    this.score = base.toFixed(1);

                    if (base <= 0.0) this.severity = 'info';
                    else if (base < 4.0) this.severity = 'low';
                    else if (base < 7.0) this.severity = 'medium';
                    else if (base < 9.0) this.severity = 'high';
                    else this.severity = 'critical';
                }
            }
        }
    </script>
</x-app-layout>
