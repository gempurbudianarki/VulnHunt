<x-guest-layout>
    <div class="py-12 px-4 sm:px-6 lg:px-8 max-w-xl mx-auto">
        <div class="specimen-card p-8 bg-putih-daun shadow-clay-card">
            <div class="text-center mb-6">
                <span class="font-mono text-xs font-bold text-severity-critical bg-severity-critical/10 px-3 py-1 rounded-full uppercase tracking-wider">
                    Inisialisasi Keamanan Staf
                </span>
                <h2 class="text-2xl font-bold font-display text-lumut mt-2">
                    Aktivasi Keamanan Akun Admin
                </h2>
                <p class="text-xs text-abu-batu mt-1">
                    Demi integritas perlombaan, akun panitia wajib mengganti kata sandi awal dan mengaktifkan 2FA TOTP sebelum dapat mengakses panel admin.
                </p>
            </div>

            @if (session('status'))
                <div class="mb-4 text-xs font-medium text-daun bg-daun/10 border border-daun/30 p-3 rounded-xl">
                    {{ session('status') }}
                </div>
            @endif

            <form method="POST" action="{{ route('security.setup.save') }}" class="space-y-6">
                @csrf

                <div class="p-4 rounded-card bg-amber-500/10 border border-amber-500/20 text-xs text-lumut">
                    <p class="font-semibold text-ambar">Langkah 1: Hubungkan Aplikasi Autentikator</p>
                    <p class="text-abu-batu mt-1">Pindai kode QR berikut menggunakan aplikasi Google Authenticator, Aegis, atau Authy di ponsel Anda:</p>
                    
                    <div class="my-4 flex flex-col items-center justify-center p-4 bg-white rounded-xl border border-abu-batu/20">
                        {!! $qrCodeSvg !!}
                        <p class="text-[11px] font-mono text-lumut mt-3 select-all bg-sage/30 px-3 py-1 rounded border border-abu-batu/20">
                            Kunci Manual: <span class="font-bold">{{ $manualSecret }}</span>
                        </p>
                    </div>
                </div>

                <div class="space-y-4">
                    <p class="font-semibold text-xs text-lumut uppercase tracking-wider font-mono">Langkah 2: Ganti Password & Verifikasi Kode</p>

                    <div>
                        <label for="current_password" class="block text-xs font-semibold text-lumut mb-1">
                            Kata Sandi Saat Ini (Awal)
                        </label>
                        <input id="current_password" type="password" name="current_password" required
                               class="w-full px-3.5 py-2.5 rounded-input bg-sage/30 border border-abu-batu/20 focus:border-daun focus:ring focus:ring-daun/20 text-sm text-lumut"
                               placeholder="Masukkan password saat ini">
                        @error('current_password')
                            <p class="text-xs text-severity-critical mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label for="password" class="block text-xs font-semibold text-lumut mb-1">
                            Kata Sandi Baru (Minimal 10 Karakter)
                        </label>
                        <input id="password" type="password" name="password" required
                               class="w-full px-3.5 py-2.5 rounded-input bg-sage/30 border border-abu-batu/20 focus:border-daun focus:ring focus:ring-daun/20 text-sm text-lumut"
                               placeholder="Minimal 10 karakter kuat">
                        @error('password')
                            <p class="text-xs text-severity-critical mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label for="password_confirmation" class="block text-xs font-semibold text-lumut mb-1">
                            Konfirmasi Kata Sandi Baru
                        </label>
                        <input id="password_confirmation" type="password" name="password_confirmation" required
                               class="w-full px-3.5 py-2.5 rounded-input bg-sage/30 border border-abu-batu/20 focus:border-daun focus:ring focus:ring-daun/20 text-sm text-lumut"
                               placeholder="Ulangi kata sandi baru">
                    </div>

                    <div>
                        <label for="code" class="block text-xs font-semibold text-lumut mb-1">
                            Kode TOTP 6 Digit dari Aplikasi Autentikator
                        </label>
                        <input id="code" type="text" name="code" required maxlength="6" inputmode="numeric"
                               class="w-full px-3.5 py-2.5 rounded-input bg-sage/30 border border-abu-batu/20 focus:border-daun focus:ring focus:ring-daun/20 font-mono text-center tracking-widest text-lg text-lumut"
                               placeholder="123456">
                        @error('code')
                            <p class="text-xs text-severity-critical mt-1">{{ $message }}</p>
                        @enderror
                    </div>
                </div>

                <div>
                    <button type="submit"
                            class="w-full py-3 px-4 rounded-btn font-semibold text-white bg-daun hover:bg-daun/90 shadow-clay-btn transition text-center text-sm">
                        Simpan Kata Sandi & Aktifkan 2FA
                    </button>
                </div>
            </form>
        </div>
    </div>
</x-guest-layout>
