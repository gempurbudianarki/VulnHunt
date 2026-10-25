<x-guest-layout>
    <div class="py-12 sm:py-16 px-4 sm:px-6 lg:px-8 max-w-xl mx-auto">
        <div class="clay-card p-7 sm:p-9 relative">
            <div class="text-center mb-6">
                <div class="w-12 h-12 mx-auto mb-3 rounded-clay-md bg-indigo-50 border border-indigo-100 p-1.5 shadow-sm flex items-center justify-center">
                    <img src="{{ asset('brand/logo.png') }}" alt="VulnHunt" class="w-full h-full object-contain">
                </div>
                <span class="clay-badge bg-indigo-50 text-brand-primary border-indigo-200 text-[10px] uppercase font-bold tracking-wider">
                    Registrasi Mahasiswa
                </span>
                <h2 class="text-2xl font-extrabold text-slate-900 mt-2 tracking-tight">
                    Daftar Akun VulnHunt
                </h2>
                <p class="text-xs text-slate-500 mt-1 max-w-sm mx-auto">
                    Gunakan identitas dan email resmi kampus UBBG untuk keperluan sertifikasi dan verifikasi NDA.
                </p>
            </div>

            <form method="POST" action="{{ route('register') }}" class="space-y-4">
                @csrf

                <div>
                    <label for="name" class="block text-xs font-bold uppercase tracking-wider text-slate-700 mb-1.5">
                        Nama Lengkap (Sesuai KTM) <span class="text-rose-500">*</span>
                    </label>
                    <input id="name" type="text" name="name" value="{{ old('name') }}" required autofocus
                           class="clay-input @error('name') border-rose-400 focus:border-rose-500 focus:ring-rose-400/20 bg-rose-50/30 @enderror"
                           placeholder="Contoh: Muhammad Fajar">
                    @error('name')
                        <p class="text-xs font-semibold text-rose-600 mt-1.5 flex items-center gap-1">{{ $message }}</p>
                    @enderror
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label for="nim" class="block text-xs font-bold uppercase tracking-wider text-slate-700 mb-1.5">
                            NIM <span class="text-rose-500">*</span>
                        </label>
                        <input id="nim" type="text" name="nim" value="{{ old('nim') }}" required
                               class="clay-input font-mono @error('nim') border-rose-400 focus:border-rose-500 focus:ring-rose-400/20 bg-rose-50/30 @enderror"
                               placeholder="Contoh: 210102001">
                        @error('nim')
                            <p class="text-xs font-semibold text-rose-600 mt-1.5 flex items-center gap-1">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label for="phone" class="block text-xs font-bold uppercase tracking-wider text-slate-700 mb-1.5">
                            WhatsApp / No HP <span class="text-rose-500">*</span>
                        </label>
                        <input id="phone" type="text" name="phone" value="{{ old('phone') }}" required
                               class="clay-input @error('phone') border-rose-400 focus:border-rose-500 focus:ring-rose-400/20 bg-rose-50/30 @enderror"
                               placeholder="Contoh: 081234567890">
                        @error('phone')
                            <p class="text-xs font-semibold text-rose-600 mt-1.5 flex items-center gap-1">{{ $message }}</p>
                        @enderror
                    </div>
                </div>

                <div>
                    <label for="email" class="block text-xs font-bold uppercase tracking-wider text-slate-700 mb-1.5">
                        Email Resmi Kampus <span class="text-rose-500">*</span>
                    </label>
                    <input id="email" type="email" name="email" value="{{ old('email') }}" required
                           class="clay-input font-mono @error('email') border-rose-400 focus:border-rose-500 focus:ring-rose-400/20 bg-rose-50/30 @enderror"
                           placeholder="nim@ubbg.ac.id">
                    <p class="text-[11px] text-slate-500 mt-1">Wajib menggunakan domain @ubbg.ac.id untuk validasi kemahasiswaan.</p>
                    @error('email')
                        <p class="text-xs font-semibold text-rose-600 mt-1.5 flex items-center gap-1">{{ $message }}</p>
                    @enderror
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label for="study_program" class="block text-xs font-bold uppercase tracking-wider text-slate-700 mb-1.5">
                            Program Studi <span class="text-rose-500">*</span>
                        </label>
                        <input id="study_program" type="text" name="study_program" value="{{ old('study_program') }}" required
                               class="clay-input @error('study_program') border-rose-400 focus:border-rose-500 focus:ring-rose-400/20 bg-rose-50/30 @enderror"
                               placeholder="Contoh: Ilmu Komputer">
                        @error('study_program')
                            <p class="text-xs font-semibold text-rose-600 mt-1.5 flex items-center gap-1">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label for="cohort_year" class="block text-xs font-bold uppercase tracking-wider text-slate-700 mb-1.5">
                            Tahun Angkatan <span class="text-rose-500">*</span>
                        </label>
                        <input id="cohort_year" type="number" name="cohort_year" value="{{ old('cohort_year', date('Y')) }}" required min="2015" max="{{ date('Y') + 1 }}"
                               class="clay-input font-mono @error('cohort_year') border-rose-400 focus:border-rose-500 focus:ring-rose-400/20 bg-rose-50/30 @enderror">
                        @error('cohort_year')
                            <p class="text-xs font-semibold text-rose-600 mt-1.5 flex items-center gap-1">{{ $message }}</p>
                        @enderror
                    </div>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 pt-2">
                    <div>
                        <label for="password" class="block text-xs font-bold uppercase tracking-wider text-slate-700 mb-1.5">
                            Kata Sandi (Min. 10) <span class="text-rose-500">*</span>
                        </label>
                        <input id="password" type="password" name="password" required autocomplete="new-password"
                               class="clay-input @error('password') border-rose-400 focus:border-rose-500 focus:ring-rose-400/20 bg-rose-50/30 @enderror">
                        @error('password')
                            <p class="text-xs font-semibold text-rose-600 mt-1.5 flex items-center gap-1">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label for="password_confirmation" class="block text-xs font-bold uppercase tracking-wider text-slate-700 mb-1.5">
                            Konfirmasi Sandi <span class="text-rose-500">*</span>
                        </label>
                        <input id="password_confirmation" type="password" name="password_confirmation" required autocomplete="new-password"
                               class="clay-input">
                    </div>
                </div>

                <div class="pt-3">
                    <button type="submit" class="btn-clay-primary w-full py-3 text-sm">
                        Daftar Akun Tim &rarr;
                    </button>
                </div>
            </form>

            <div class="mt-6 pt-5 border-t border-slate-100 text-center text-xs text-slate-500">
                Sudah memiliki akun?
                <a href="{{ route('login') }}" class="font-bold text-brand-primary hover:underline ml-1">
                    Masuk di sini
                </a>
            </div>
        </div>
    </div>
</x-guest-layout>
