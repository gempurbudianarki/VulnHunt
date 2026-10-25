<x-guest-layout>
    <div class="py-12 px-4 sm:px-6 lg:px-8 max-w-md mx-auto" x-data="{ recovery: false }">
        <div class="specimen-card p-8 bg-putih-daun shadow-clay-card">
            <div class="text-center mb-6">
                <span class="font-mono text-xs font-bold text-ambar bg-lumut/5 px-3 py-1 rounded-full uppercase tracking-wider">
                    Keamanan Tingkat Lanjut
                </span>
                <h2 class="text-2xl font-bold font-display text-lumut mt-2">
                    Autentikasi Dua Faktor (2FA)
                </h2>
                <p class="text-xs text-abu-batu mt-1" x-show="!recovery">
                    Masukkan kode 6 digit dari aplikasi autentikator Anda.
                </p>
                <p class="text-xs text-abu-batu mt-1" x-show="recovery" style="display: none;">
                    Masukkan salah satu kode pemulihan darurat Anda.
                </p>
            </div>

            <form method="POST" action="{{ url('/two-factor-challenge') }}" class="space-y-4">
                @csrf

                <div x-show="!recovery">
                    <label for="code" class="block text-xs font-semibold text-lumut mb-1">
                        Kode Otentikasi (TOTP)
                    </label>
                    <input id="code" type="text" inputmode="numeric" name="code" autofocus x-ref="code"
                           autocomplete="one-time-code"
                           class="w-full px-3.5 py-2.5 rounded-input bg-sage/30 border border-abu-batu/20 focus:border-daun focus:ring focus:ring-daun/20 text-center font-mono text-lg tracking-widest text-lumut"
                           placeholder="000000">
                    @error('code')
                        <p class="text-xs text-severity-critical mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <div x-show="recovery" style="display: none;">
                    <label for="recovery_code" class="block text-xs font-semibold text-lumut mb-1">
                        Kode Pemulihan Darurat
                    </label>
                    <input id="recovery_code" type="text" name="recovery_code" x-ref="recovery_code"
                           autocomplete="one-time-code"
                           class="w-full px-3.5 py-2.5 rounded-input bg-sage/30 border border-abu-batu/20 focus:border-daun focus:ring focus:ring-daun/20 font-mono text-sm text-lumut">
                    @error('recovery_code')
                        <p class="text-xs text-severity-critical mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <div class="pt-2">
                    <button type="submit" class="w-full btn-clay-primary py-3">
                        Verifikasi Masuk
                    </button>
                </div>

                <div class="text-center pt-2">
                    <button type="button" class="text-xs text-daun hover:underline"
                            x-show="!recovery"
                            @click="recovery = true; $nextTick(() => { $refs.recovery_code.focus() })">
                        Gunakan kode pemulihan darurat
                    </button>

                    <button type="button" class="text-xs text-daun hover:underline"
                            x-show="recovery"
                            style="display: none;"
                            @click="recovery = false; $nextTick(() => { $refs.code.focus() })">
                        Gunakan aplikasi autentikator
                    </button>
                </div>
            </form>
        </div>
    </div>
</x-guest-layout>
