<x-guest-layout>
    <div class="flex-grow flex items-center justify-center p-3 sm:p-4 relative overflow-hidden bg-cyber-dots">
        <!-- Ambient Cyber Orbs -->
        <div class="absolute -top-24 -left-24 w-96 h-96 bg-brand-primary/15 rounded-full blur-3xl pointer-events-none animate-pulse-aura"></div>
        <div class="absolute -bottom-24 -right-24 w-96 h-96 bg-brand-accent/15 rounded-full blur-3xl pointer-events-none animate-pulse-aura" style="animation-delay: 1.5s;"></div>

        <!-- Outer Glowing Ambient Ring Around Card -->
        <div class="relative w-full max-w-[400px] group">
            <div class="absolute -inset-0.5 bg-gradient-to-r from-brand-primary/30 via-indigo-500/25 to-brand-accent/30 rounded-[30px] blur-md opacity-70 group-hover:opacity-100 transition-all duration-700 pointer-events-none"></div>

            <!-- Card -->
            <div class="relative w-full bg-white/95 backdrop-blur-xl border-2 border-white rounded-clay-xl p-5 sm:p-6 shadow-[0_20px_50px_-10px_rgba(15,23,42,0.12),inset_0_2px_4px_rgba(255,255,255,1)] transition-all duration-300 hover:shadow-[0_25px_60px_-10px_rgba(37,58,161,0.2),inset_0_2px_4px_rgba(255,255,255,1)]">
                <div class="text-center mb-4">
                    <div class="relative w-12 h-12 mx-auto mb-2 flex items-center justify-center">
                        <span class="animate-ping absolute inline-flex h-10 w-10 rounded-2xl bg-indigo-400 opacity-25"></span>
                        <div class="w-11 h-11 rounded-2xl bg-gradient-to-br from-indigo-50 via-white to-slate-100 border border-indigo-100/90 shadow-clay-card-light flex items-center justify-center p-1.5 relative z-10 group-hover:scale-105 transition-transform duration-300">
                            <img src="{{ asset('brand/emblem-cyber.png') }}" alt="Emblem VulnHunt" class="w-full h-full object-contain drop-shadow-xs" decoding="async">
                        </div>
                    </div>

                    <div class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-clay-pill bg-indigo-50 border border-indigo-200/80 text-[10.5px] font-bold text-brand-primary uppercase tracking-wider shadow-2xs">
                        <span>Pemulihan Akun Riset</span>
                    </div>

                    <h1 class="font-display text-2xl font-black text-slate-950 mt-1.5 tracking-tight">
                        Lupa Kata Sandi
                    </h1>
                    <p class="text-xs text-slate-500 mt-0.5">
                        Masukkan email kampus Anda. Kami akan mengirimkan tautan reset kata sandi resmi.
                    </p>
                </div>

                @if (session('status'))
                    <x-clay.alert type="success" class="mb-3 text-xs">
                        {{ session('status') }}
                    </x-clay.alert>
                @endif

                <form method="POST" action="{{ route('password.email') }}" class="space-y-3">
                    @csrf

                    <div>
                        <label for="email" class="block text-xs font-bold uppercase tracking-wider text-slate-700 mb-1">
                            Email Kampus <span class="text-rose-500">*</span>
                        </label>
                        <div class="relative">
                            <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-slate-400">
                                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                                </svg>
                            </div>
                            <input id="email" type="email" name="email" value="{{ old('email') }}" required autofocus
                                   class="clay-input pl-10 py-2 text-xs font-medium @error('email') border-rose-400 focus:border-rose-500 bg-rose-50/30 @enderror"
                                   placeholder="nama@ubbg.ac.id">
                        </div>
                        @error('email')
                            <p class="text-xs font-semibold text-rose-600 mt-1 flex items-center gap-1">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="pt-1">
                        <button type="submit" class="btn-clay-primary w-full py-2.5 text-xs sm:text-sm font-bold tracking-wide relative overflow-hidden group shadow-clay-btn-primary hover:shadow-clay-btn-primary-hover active:translate-y-0.5 transition-all">
                            <div class="absolute inset-0 -translate-x-full group-hover:translate-x-full transition-transform duration-700 bg-gradient-to-r from-transparent via-white/25 to-transparent pointer-events-none"></div>
                            <span class="relative z-10">Kirim Tautan Pemulihan &rarr;</span>
                        </button>
                    </div>
                </form>

                <div class="mt-4 pt-3 border-t border-slate-100 text-center text-xs text-slate-500">
                    <a href="{{ route('login') }}" class="font-bold text-brand-primary hover:underline hover:text-brand-primary-hover transition-colors">
                        &larr; Kembali ke halaman Masuk
                    </a>
                </div>
            </div>
        </div>
    </div>
</x-guest-layout>
