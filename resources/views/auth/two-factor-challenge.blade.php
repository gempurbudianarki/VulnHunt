<x-guest-layout>
    <div class="py-12 sm:py-16 px-4 sm:px-6 lg:px-8 max-w-md mx-auto" x-data="{ recovery: false }">
        <div class="clay-card p-7 sm:p-8 relative">
            <div class="text-center mb-6">
                <div class="w-12 h-12 mx-auto mb-3 rounded-clay-md bg-indigo-50 border border-indigo-100 p-1.5 shadow-sm flex items-center justify-center">
                    <img src="{{ asset('brand/logo.png') }}" alt="VulnHunt" class="w-full h-full object-contain">
                </div>
                <span class="clay-badge bg-indigo-50 text-brand-primary border-indigo-200 text-[10px] uppercase font-bold tracking-wider">
                    Verifikasi Keamanan
                </span>
                <h2 class="text-2xl font-extrabold text-slate-900 mt-2 tracking-tight">
                    Autentikasi Dua Faktor
                </h2>
                <p class="text-xs text-slate-500 mt-1" x-show="!recovery">
                    Masukkan kode 6 digit dari aplikasi autentikator (Google Authenticator / TOTP).
                </p>
                <p class="text-xs text-slate-500 mt-1" x-show="recovery" style="display: none;">
                    Masukkan salah satu kode pemulihan darurat Anda.
                </p>
            </div>

            <form method="POST" action="{{ url('/two-factor-challenge') }}" class="space-y-4">
                @csrf

                <div x-show="!recovery">
                    <label for="code" class="block text-xs font-bold uppercase tracking-wider text-slate-700 mb-1.5">
                        Kode Otentikasi (TOTP)
                    </label>
                    <input id="code" type="text" inputmode="numeric" name="code" autofocus x-ref="code"
                           autocomplete="one-time-code"
                           class="clay-input text-center font-mono text-xl tracking-[0.3em] font-black @error('code') border-rose-400 bg-rose-50/30 @enderror"
                           placeholder="000000">
                    @error('code')
                        <p class="text-xs font-semibold text-rose-600 mt-1.5 flex items-center gap-1">{{ $message }}</p>
                    @enderror
                </div>

                <div x-show="recovery" style="display: none;">
                    <label for="recovery_code" class="block text-xs font-bold uppercase tracking-wider text-slate-700 mb-1.5">
                        Kode Pemulihan Darurat
                    </label>
                    <input id="recovery_code" type="text" name="recovery_code" x-ref="recovery_code"
                           autocomplete="one-time-code"
                           class="clay-input font-mono text-sm @error('recovery_code') border-rose-400 bg-rose-50/30 @enderror"
                           placeholder="xxxx-xxxx-xxxx">
                    @error('recovery_code')
                        <p class="text-xs font-semibold text-rose-600 mt-1.5 flex items-center gap-1">{{ $message }}</p>
                    @enderror
                </div>

                <div class="pt-3">
                    <button type="submit" class="btn-clay-primary w-full py-3 text-sm">
                        Verifikasi & Lanjutkan Masuk
                    </button>
                </div>

                <div class="text-center pt-2">
                    <button type="button" class="text-xs font-semibold text-brand-primary hover:underline"
                            x-show="!recovery"
                            @click="recovery = true; $nextTick(() => { $refs.recovery_code.focus() })">
                        Gunakan kode pemulihan darurat
                    </button>

                    <button type="button" class="text-xs font-semibold text-brand-primary hover:underline"
                            x-show="recovery"
                            style="display: none;"
                            @click="recovery = false; $nextTick(() => { $refs.code.focus() })">
                        Gunakan kode autentikator (TOTP)
                    </button>
                </div>
            </form>
        </div>
    </div>
</x-guest-layout>
