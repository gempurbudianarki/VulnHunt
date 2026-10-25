<x-guest-layout>
    <div class="py-12 px-4 sm:px-6 lg:px-8 max-w-md mx-auto">
        <div class="specimen-card p-8 bg-putih-daun shadow-clay-card">
            <div class="text-center mb-6">
                <span class="font-mono text-xs font-bold text-ambar bg-lumut/5 px-3 py-1 rounded-full uppercase tracking-wider">
                    Pemulihan Akses
                </span>
                <h2 class="text-2xl font-bold font-display text-lumut mt-2">
                    Atur Ulang Kata Sandi
                </h2>
                <p class="text-xs text-abu-batu mt-1">
                    Silakan masukkan kata sandi baru minimal 10 karakter.
                </p>
            </div>

            <form method="POST" action="{{ route('password.update') }}" class="space-y-4">
                @csrf
                <input type="hidden" name="token" value="{{ $request->route('token') }}">

                <div>
                    <label for="email" class="block text-xs font-semibold text-lumut mb-1">
                        Email Kampus
                    </label>
                    <input id="email" type="email" name="email" value="{{ old('email', $request->email) }}" required autofocus
                           class="w-full px-3.5 py-2.5 rounded-input bg-sage/30 border border-abu-batu/20 focus:border-daun focus:ring focus:ring-daun/20 text-sm text-lumut">
                    @error('email')
                        <p class="text-xs text-severity-critical mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="password" class="block text-xs font-semibold text-lumut mb-1">
                        Kata Sandi Baru (Min. 10 Karakter)
                    </label>
                    <input id="password" type="password" name="password" required autocomplete="new-password"
                           class="w-full px-3.5 py-2.5 rounded-input bg-sage/30 border border-abu-batu/20 focus:border-daun focus:ring focus:ring-daun/20 text-sm text-lumut">
                    @error('password')
                        <p class="text-xs text-severity-critical mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="password_confirmation" class="block text-xs font-semibold text-lumut mb-1">
                        Konfirmasi Kata Sandi Baru
                    </label>
                    <input id="password_confirmation" type="password" name="password_confirmation" required autocomplete="new-password"
                           class="w-full px-3.5 py-2.5 rounded-input bg-sage/30 border border-abu-batu/20 focus:border-daun focus:ring focus:ring-daun/20 text-sm text-lumut">
                </div>

                <div class="pt-2">
                    <button type="submit" class="w-full btn-clay-primary py-3">
                        Perbarui Kata Sandi
                    </button>
                </div>
            </form>
        </div>
    </div>
</x-guest-layout>
