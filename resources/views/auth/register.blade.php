<x-guest-layout>
    <div class="py-12 px-4 sm:px-6 lg:px-8 max-w-xl mx-auto">
        <div class="specimen-card p-8 bg-putih-daun shadow-clay-card">
            <div class="text-center mb-6">
                <span class="font-mono text-xs font-bold text-ambar bg-lumut/5 px-3 py-1 rounded-full uppercase tracking-wider">
                    Registrasi Peneliti Mahasiswa
                </span>
                <h2 class="text-2xl font-bold font-display text-lumut mt-2">
                    Daftar Akun Peserta
                </h2>
                <p class="text-xs text-abu-batu mt-1">
                    Gunakan identitas dan email kampus resmi UBBG untuk keperluan sertifikasi dan verifikasi NDA.
                </p>
            </div>

            <form method="POST" action="{{ route('register') }}" class="space-y-4">
                @csrf

                <div>
                    <label for="name" class="block text-xs font-semibold text-lumut mb-1">
                        Nama Lengkap (Sesuai KTM)
                    </label>
                    <input id="name" type="text" name="name" value="{{ old('name') }}" required autofocus
                           class="w-full px-3.5 py-2.5 rounded-input bg-sage/30 border border-abu-batu/20 focus:border-daun focus:ring focus:ring-daun/20 text-sm text-lumut"
                           placeholder="Contoh: Muhammad Fajar">
                    @error('name')
                        <p class="text-xs text-severity-critical mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label for="nim" class="block text-xs font-semibold text-lumut mb-1">
                            Nomor Induk Mahasiswa (NIM)
                        </label>
                        <input id="nim" type="text" name="nim" value="{{ old('nim') }}" required
                               class="w-full px-3.5 py-2.5 rounded-input bg-sage/30 border border-abu-batu/20 focus:border-daun focus:ring focus:ring-daun/20 text-sm text-lumut font-mono"
                               placeholder="Contoh: 210102001">
                        @error('nim')
                            <p class="text-xs text-severity-critical mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label for="phone" class="block text-xs font-semibold text-lumut mb-1">
                            Nomor WhatsApp / HP Aktif
                        </label>
                        <input id="phone" type="text" name="phone" value="{{ old('phone') }}" required
                               class="w-full px-3.5 py-2.5 rounded-input bg-sage/30 border border-abu-batu/20 focus:border-daun focus:ring focus:ring-daun/20 text-sm text-lumut"
                               placeholder="Contoh: 081234567890">
                        @error('phone')
                            <p class="text-xs text-severity-critical mt-1">{{ $message }}</p>
                        @enderror
                    </div>
                </div>

                <div>
                    <label for="email" class="block text-xs font-semibold text-lumut mb-1">
                        Email Resmi Kampus
                    </label>
                    <input id="email" type="email" name="email" value="{{ old('email') }}" required
                           class="w-full px-3.5 py-2.5 rounded-input bg-sage/30 border border-abu-batu/20 focus:border-daun focus:ring focus:ring-daun/20 text-sm text-lumut font-mono"
                           placeholder="nim@ubbg.ac.id">
                    <p class="text-[11px] text-abu-batu mt-1">Wajib menggunakan domain @ubbg.ac.id untuk validasi status kemahasiswaan.</p>
                    @error('email')
                        <p class="text-xs text-severity-critical mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label for="study_program" class="block text-xs font-semibold text-lumut mb-1">
                            Program Studi
                        </label>
                        <input id="study_program" type="text" name="study_program" value="{{ old('study_program') }}" required
                               class="w-full px-3.5 py-2.5 rounded-input bg-sage/30 border border-abu-batu/20 focus:border-daun focus:ring focus:ring-daun/20 text-sm text-lumut"
                               placeholder="Contoh: Ilmu Komputer">
                        @error('study_program')
                            <p class="text-xs text-severity-critical mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label for="cohort_year" class="block text-xs font-semibold text-lumut mb-1">
                            Tahun Angkatan
                        </label>
                        <input id="cohort_year" type="number" name="cohort_year" value="{{ old('cohort_year', date('Y')) }}" required min="2015" max="{{ date('Y') + 1 }}"
                               class="w-full px-3.5 py-2.5 rounded-input bg-sage/30 border border-abu-batu/20 focus:border-daun focus:ring focus:ring-daun/20 text-sm text-lumut font-mono">
                        @error('cohort_year')
                            <p class="text-xs text-severity-critical mt-1">{{ $message }}</p>
                        @enderror
                    </div>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 pt-2">
                    <div>
                        <label for="password" class="block text-xs font-semibold text-lumut mb-1">
                            Kata Sandi (Min. 10 Karakter)
                        </label>
                        <input id="password" type="password" name="password" required autocomplete="new-password"
                               class="w-full px-3.5 py-2.5 rounded-input bg-sage/30 border border-abu-batu/20 focus:border-daun focus:ring focus:ring-daun/20 text-sm text-lumut">
                        @error('password')
                            <p class="text-xs text-severity-critical mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label for="password_confirmation" class="block text-xs font-semibold text-lumut mb-1">
                            Konfirmasi Kata Sandi
                        </label>
                        <input id="password_confirmation" type="password" name="password_confirmation" required autocomplete="new-password"
                               class="w-full px-3.5 py-2.5 rounded-input bg-sage/30 border border-abu-batu/20 focus:border-daun focus:ring focus:ring-daun/20 text-sm text-lumut">
                    </div>
                </div>

                <div class="pt-4">
                    <button type="submit" class="w-full btn-clay-primary py-3">
                        Daftar Akun & Lanjutkan
                    </button>
                </div>
            </form>

            <div class="mt-6 pt-4 border-t border-abu-batu/15 text-center text-xs text-abu-batu">
                Sudah memiliki akun?
                <a href="{{ route('login') }}" class="text-daun font-semibold hover:underline">
                    Masuk di sini
                </a>
            </div>
        </div>
    </div>
</x-guest-layout>
