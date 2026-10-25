<x-guest-layout>
    <div class="py-12 px-4 sm:px-6 lg:px-8 max-w-md mx-auto">
        <div class="specimen-card p-8 bg-putih-daun shadow-clay-card">
            <div class="text-center mb-6">
                <span class="font-mono text-xs font-bold text-ambar bg-lumut/5 px-3 py-1 rounded-full uppercase tracking-wider">
                    Autentikasi Pemburu Celah
                </span>
                <h2 class="text-2xl font-bold font-display text-lumut mt-2">
                    Masuk ke Akun
                </h2>
                <p class="text-xs text-abu-batu mt-1">
                    Silakan masukkan email kampus dan kata sandi Anda.
                </p>
            </div>

            @if (session('status'))
                <div class="mb-4 text-xs font-medium text-daun bg-daun/10 border border-daun/30 p-3 rounded-xl">
                    {{ session('status') }}
                </div>
            @endif

            <form method="POST" action="{{ route('login') }}" class="space-y-4">
                @csrf

                <div>
                    <label for="email" class="block text-xs font-semibold text-lumut mb-1">
                        Email Kampus
                    </label>
                    <input id="email" type="email" name="email" value="{{ old('email') }}" required autofocus
                           class="w-full px-3.5 py-2.5 rounded-input bg-sage/30 border border-abu-batu/20 focus:border-daun focus:ring focus:ring-daun/20 text-sm text-lumut"
                           placeholder="nama@ubbg.ac.id">
                    @error('email')
                        <p class="text-xs text-severity-critical mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <div class="flex items-center justify-between mb-1">
                        <label for="password" class="block text-xs font-semibold text-lumut">
                            Kata Sandi
                        </label>
                        <a href="{{ route('password.request') }}" class="text-[11px] text-daun hover:underline">
                            Lupa kata sandi?
                        </a>
                    </div>
                    <input id="password" type="password" name="password" required autocomplete="current-password"
                           class="w-full px-3.5 py-2.5 rounded-input bg-sage/30 border border-abu-batu/20 focus:border-daun focus:ring focus:ring-daun/20 text-sm text-lumut">
                    @error('password')
                        <p class="text-xs text-severity-critical mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <div class="flex items-center">
                    <input id="remember" type="checkbox" name="remember"
                           class="rounded text-daun focus:ring-daun/30 border-abu-batu/30">
                    <label for="remember" class="ml-2 text-xs text-abu-batu">
                        Ingat saya di perangkat ini
                    </label>
                </div>

                <div class="pt-2">
                    <button type="submit" class="w-full btn-clay-primary py-3">
                        Masuk ke Dasbor
                    </button>
                </div>
            </form>

            <div class="mt-6 pt-4 border-t border-abu-batu/15 text-center text-xs text-abu-batu">
                Belum memiliki tim atau akun?
                <a href="{{ route('register') }}" class="text-daun font-semibold hover:underline">
                    Daftar di sini
                </a>
            </div>
        </div>
    </div>
</x-guest-layout>
