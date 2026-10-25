<x-guest-layout>
    <div class="flex-grow flex items-center justify-center p-3 sm:p-6 relative overflow-hidden bg-cyber-dots" x-data="{ showPass: false }">
        <!-- Ambient Cyber Orbs -->
        <div class="absolute -top-24 -left-24 w-96 h-96 bg-brand-primary/15 rounded-full blur-3xl pointer-events-none animate-pulse-aura"></div>
        <div class="absolute -bottom-24 -right-24 w-96 h-96 bg-brand-accent/15 rounded-full blur-3xl pointer-events-none animate-pulse-aura" style="animation-delay: 1.5s;"></div>

        <!-- Outer Glowing Ambient Ring Around Card -->
        <div class="relative w-full max-w-xl group my-2 sm:my-4">
            <div class="absolute -inset-0.5 bg-gradient-to-r from-brand-primary/30 via-indigo-500/25 to-brand-accent/30 rounded-[30px] blur-md opacity-70 group-hover:opacity-100 transition-all duration-700 pointer-events-none"></div>

            <!-- Register Card -->
            <div class="relative w-full bg-white/95 backdrop-blur-xl border-2 border-white rounded-clay-xl p-5 sm:p-7 shadow-[0_20px_50px_-10px_rgba(15,23,42,0.12),inset_0_2px_4px_rgba(255,255,255,1)] transition-all duration-300 hover:shadow-[0_25px_60px_-10px_rgba(37,58,161,0.2),inset_0_2px_4px_rgba(255,255,255,1)]">
                <!-- Header Card -->
                <div class="text-center mb-4">
                    <div class="relative w-12 h-12 mx-auto mb-2 flex items-center justify-center">
                        <span class="animate-ping absolute inline-flex h-10 w-10 rounded-2xl bg-indigo-400 opacity-25"></span>
                        <div class="w-11 h-11 rounded-2xl bg-gradient-to-br from-indigo-50 via-white to-slate-100 border border-indigo-100/90 shadow-clay-card-light flex items-center justify-center p-1.5 relative z-10 group-hover:scale-105 transition-transform duration-300">
                            <img src="{{ asset('brand/emblem-cyber.png') }}" alt="Emblem VulnHunt" class="w-full h-full object-contain drop-shadow-xs" decoding="async">
                        </div>
                    </div>

                    <div class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-clay-pill bg-indigo-50 border border-indigo-200/80 text-[10px] font-bold text-brand-primary uppercase tracking-wider shadow-2xs">
                        <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span>
                        <span>Registrasi Peneliti Mahasiswa</span>
                    </div>

                    <h1 class="font-display text-2xl font-black text-slate-950 mt-1.5 tracking-tight">
                        Daftar Akun Tim VulnHunt
                    </h1>
                    <p class="text-xs text-slate-500 mt-0.5 max-w-md mx-auto">
                        Gunakan data identitas valid & email resmi kampus UBBG untuk verifikasi NDA dan penerbitan sertifikat.
                    </p>
                </div>

                <form method="POST" action="{{ route('register') }}" class="space-y-3">
                    @csrf

                    <!-- Nama Lengkap -->
                    <div>
                        <label for="name" class="block text-xs font-bold uppercase tracking-wider text-slate-700 mb-1">
                            Nama Lengkap (Sesuai KTM) <span class="text-rose-500">*</span>
                        </label>
                        <input id="name" type="text" name="name" value="{{ old('name') }}" required autofocus
                               class="clay-input py-2 text-xs font-medium @error('name') border-rose-400 focus:border-rose-500 bg-rose-50/30 @enderror"
                               placeholder="Contoh: Muhammad Fajar">
                        @error('name')
                            <p class="text-xs font-semibold text-rose-600 mt-1 flex items-center gap-1">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- NIM & No HP (2 Columns) -->
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                        <div>
                            <label for="nim" class="block text-xs font-bold uppercase tracking-wider text-slate-700 mb-1">
                                NIM Mahasiswa <span class="text-rose-500">*</span>
                            </label>
                            <input id="nim" type="text" name="nim" value="{{ old('nim') }}" required
                                   class="clay-input font-mono py-2 text-xs font-medium @error('nim') border-rose-400 focus:border-rose-500 bg-rose-50/30 @enderror"
                                   placeholder="Contoh: 210102001">
                            @error('nim')
                                <p class="text-xs font-semibold text-rose-600 mt-1 flex items-center gap-1">{{ $message }}</p>
                            @enderror
                        </div>

                        <div>
                            <label for="phone" class="block text-xs font-bold uppercase tracking-wider text-slate-700 mb-1">
                                WhatsApp / No. HP <span class="text-rose-500">*</span>
                            </label>
                            <input id="phone" type="text" name="phone" value="{{ old('phone') }}" required
                                   class="clay-input py-2 text-xs font-medium @error('phone') border-rose-400 focus:border-rose-500 bg-rose-50/30 @enderror"
                                   placeholder="Contoh: 081234567890">
                            @error('phone')
                                <p class="text-xs font-semibold text-rose-600 mt-1 flex items-center gap-1">{{ $message }}</p>
                            @enderror
                        </div>
                    </div>

                    <!-- Email Kampus -->
                    <div>
                        <label for="email" class="block text-xs font-bold uppercase tracking-wider text-slate-700 mb-1">
                            Email Kampus Resmi <span class="text-rose-500">*</span>
                        </label>
                        <input id="email" type="email" name="email" value="{{ old('email') }}" required
                               class="clay-input font-mono py-2 text-xs font-medium @error('email') border-rose-400 focus:border-rose-500 bg-rose-50/30 @enderror"
                               placeholder="nim@ubbg.ac.id">
                        <p class="text-[10.5px] text-slate-500 mt-1">Wajib menggunakan domain resmi <span class="font-mono font-semibold text-slate-700">@ubbg.ac.id</span></p>
                        @error('email')
                            <p class="text-xs font-semibold text-rose-600 mt-1 flex items-center gap-1">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Program Studi & Angkatan (2 Columns) -->
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                        <div>
                            <label for="study_program" class="block text-xs font-bold uppercase tracking-wider text-slate-700 mb-1">
                                Program Studi <span class="text-rose-500">*</span>
                            </label>
                            <input id="study_program" type="text" name="study_program" value="{{ old('study_program') }}" required
                                   class="clay-input py-2 text-xs font-medium @error('study_program') border-rose-400 focus:border-rose-500 bg-rose-50/30 @enderror"
                                   placeholder="Contoh: Ilmu Komputer">
                            @error('study_program')
                                <p class="text-xs font-semibold text-rose-600 mt-1 flex items-center gap-1">{{ $message }}</p>
                            @enderror
                        </div>

                        <div>
                            <label for="cohort_year" class="block text-xs font-bold uppercase tracking-wider text-slate-700 mb-1">
                                Tahun Angkatan <span class="text-rose-500">*</span>
                            </label>
                            <input id="cohort_year" type="number" name="cohort_year" value="{{ old('cohort_year', date('Y')) }}" required min="2015" max="{{ date('Y') + 1 }}"
                                   class="clay-input font-mono py-2 text-xs font-medium @error('cohort_year') border-rose-400 focus:border-rose-500 bg-rose-50/30 @enderror">
                            @error('cohort_year')
                                <p class="text-xs font-semibold text-rose-600 mt-1 flex items-center gap-1">{{ $message }}</p>
                            @enderror
                        </div>
                    </div>

                    <!-- Kata Sandi & Konfirmasi (2 Columns) -->
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 pt-1">
                        <div>
                            <label for="password" class="block text-xs font-bold uppercase tracking-wider text-slate-700 mb-1">
                                Kata Sandi (Min. 10) <span class="text-rose-500">*</span>
                            </label>
                            <input id="password" :type="showPass ? 'text' : 'password'" name="password" required autocomplete="new-password"
                                   class="clay-input py-2 text-xs font-medium @error('password') border-rose-400 focus:border-rose-500 bg-rose-50/30 @enderror"
                                   placeholder="Minimal 10 karakter">
                            @error('password')
                                <p class="text-xs font-semibold text-rose-600 mt-1 flex items-center gap-1">{{ $message }}</p>
                            @enderror
                        </div>

                        <div>
                            <label for="password_confirmation" class="block text-xs font-bold uppercase tracking-wider text-slate-700 mb-1">
                                Konfirmasi Sandi <span class="text-rose-500">*</span>
                            </label>
                            <input id="password_confirmation" :type="showPass ? 'text' : 'password'" name="password_confirmation" required autocomplete="new-password"
                                   class="clay-input py-2 text-xs font-medium"
                                   placeholder="Ulangi kata sandi">
                        </div>
                    </div>

                    <!-- Show Password Checkbox -->
                    <div class="pt-0.5">
                        <label class="inline-flex items-center gap-2 cursor-pointer select-none">
                            <input type="checkbox" @change="showPass = !showPass"
                                   class="w-3.5 h-3.5 rounded border-slate-300 text-brand-primary focus:ring-brand-primary/20">
                            <span class="text-xs text-slate-600 font-medium">Tampilkan kata sandi</span>
                        </label>
                    </div>

                    <!-- Submit Button with Shimmer Sweep -->
                    <div class="pt-1.5">
                        <button type="submit" class="btn-clay-primary w-full py-2.5 text-xs sm:text-sm font-bold tracking-wide relative overflow-hidden group shadow-clay-btn-primary hover:shadow-clay-btn-primary-hover active:translate-y-0.5 transition-all">
                            <div class="absolute inset-0 -translate-x-full group-hover:translate-x-full transition-transform duration-700 bg-gradient-to-r from-transparent via-white/25 to-transparent pointer-events-none"></div>
                            <span class="relative z-10">Daftar Akun Tim Riset &rarr;</span>
                        </button>
                    </div>
                </form>

                <!-- Card Footer -->
                <div class="mt-4 pt-3.5 border-t border-slate-100 text-center text-xs text-slate-500">
                    Sudah memiliki akun tim?
                    <a href="{{ route('login') }}" class="font-bold text-brand-primary hover:underline hover:text-brand-primary-hover transition-colors ml-1">
                        Masuk di sini &rarr;
                    </a>
                </div>
            </div>
        </div>
    </div>
</x-guest-layout>
