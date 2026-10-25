<x-guest-layout>
    <div class="py-12 sm:py-16 px-4 sm:px-6 lg:px-8 max-w-md mx-auto">
        <div class="clay-card p-7 sm:p-8 relative">
            <div class="text-center mb-6">
                <div class="w-12 h-12 mx-auto mb-3 rounded-clay-md bg-indigo-50 border border-indigo-100 p-1.5 shadow-sm flex items-center justify-center">
                    <img src="{{ asset('brand/logo.png') }}" alt="VulnHunt" class="w-full h-full object-contain">
                </div>
                <span class="clay-badge bg-indigo-50 text-brand-primary border-indigo-200 text-[10px] uppercase font-bold tracking-wider">
                    Portal Autentikasi
                </span>
                <h2 class="text-2xl font-extrabold text-slate-900 mt-2 tracking-tight">
                    Masuk ke VulnHunt
                </h2>
                <p class="text-xs text-slate-500 mt-1">
                    Gunakan email resmi kampus dan kata sandi Anda.
                </p>
            </div>

            @if (session('status'))
                <x-clay.alert type="info" class="mb-4">
                    {{ session('status') }}
                </x-clay.alert>
            @endif

            <form method="POST" action="{{ route('login') }}" class="space-y-4">
                @csrf

                <div>
                    <label for="email" class="block text-xs font-bold uppercase tracking-wider text-slate-700 mb-1.5">
                        Email Kampus <span class="text-rose-500">*</span>
                    </label>
                    <input id="email" type="email" name="email" value="{{ old('email') }}" required autofocus
                           class="clay-input @error('email') border-rose-400 focus:border-rose-500 focus:ring-rose-400/20 bg-rose-50/30 @enderror"
                           placeholder="nama@ubbg.ac.id">
                    @error('email')
                        <p class="text-xs font-semibold text-rose-600 mt-1.5 flex items-center gap-1">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <div class="flex items-center justify-between mb-1.5">
                        <label for="password" class="block text-xs font-bold uppercase tracking-wider text-slate-700">
                            Kata Sandi <span class="text-rose-500">*</span>
                        </label>
                        <a href="{{ route('password.request') }}" class="text-xs font-semibold text-brand-primary hover:underline">
                            Lupa kata sandi?
                        </a>
                    </div>
                    <input id="password" type="password" name="password" required autocomplete="current-password"
                           class="clay-input @error('password') border-rose-400 focus:border-rose-500 focus:ring-rose-400/20 bg-rose-50/30 @enderror">
                    @error('password')
                        <p class="text-xs font-semibold text-rose-600 mt-1.5 flex items-center gap-1">{{ $message }}</p>
                    @enderror
                </div>

                <div class="flex items-center justify-between pt-1">
                    <label class="inline-flex items-center gap-2 cursor-pointer">
                        <input id="remember" type="checkbox" name="remember"
                               class="rounded border-slate-300 text-brand-primary focus:ring-brand-primary/20">
                        <span class="text-xs text-slate-600 font-medium">Ingat saya</span>
                    </label>
                </div>

                <div class="pt-2">
                    <button type="submit" class="btn-clay-primary w-full py-3 text-sm">
                        Masuk ke Akun &rarr;
                    </button>
                </div>
            </form>

            <div class="mt-6 pt-5 border-t border-slate-100 text-center text-xs text-slate-500">
                Belum memiliki akun tim?
                <a href="{{ route('register') }}" class="font-bold text-brand-primary hover:underline ml-1">
                    Daftar di sini
                </a>
            </div>
        </div>
    </div>
</x-guest-layout>
