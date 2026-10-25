<x-guest-layout>
    <div class="flex-grow flex items-center justify-center p-3 sm:p-4 relative overflow-hidden bg-cyber-dots" x-data="{ showPass: false }">
        <!-- Ambient Cyber Orbs -->
        <div class="absolute -top-24 -left-24 w-96 h-96 bg-brand-primary/15 rounded-full blur-3xl pointer-events-none animate-pulse-aura"></div>
        <div class="absolute -bottom-24 -right-24 w-96 h-96 bg-brand-accent/15 rounded-full blur-3xl pointer-events-none animate-pulse-aura" style="animation-delay: 1.5s;"></div>

        <!-- Outer Glowing Ambient Ring Around Card -->
        <div class="relative w-full max-w-[400px] group">
            <div class="absolute -inset-0.5 bg-gradient-to-r from-brand-primary/30 via-indigo-500/25 to-brand-accent/30 rounded-[30px] blur-md opacity-70 group-hover:opacity-100 transition-all duration-700 pointer-events-none"></div>

            <!-- Login Card -->
            <div class="relative w-full bg-white/95 backdrop-blur-xl border-2 border-white rounded-clay-xl p-5 sm:p-6 shadow-[0_20px_50px_-10px_rgba(15,23,42,0.12),inset_0_2px_4px_rgba(255,255,255,1)] transition-all duration-300 hover:shadow-[0_25px_60px_-10px_rgba(37,58,161,0.2),inset_0_2px_4px_rgba(255,255,255,1)]">
                <!-- Header Card -->
                <div class="text-center mb-4">
                    <!-- Emblem with Animated Radar Wave -->
                    <div class="relative w-14 h-14 mx-auto mb-2 flex items-center justify-center">
                        <span class="animate-ping absolute inline-flex h-12 w-12 rounded-2xl bg-indigo-400 opacity-25"></span>
                        <div class="w-12 h-12 rounded-2xl bg-gradient-to-br from-indigo-50 via-white to-slate-100 border border-indigo-100/90 shadow-clay-card-light flex items-center justify-center p-1.5 relative z-10 group-hover:scale-105 transition-transform duration-300">
                            <img src="{{ asset('brand/emblem-cyber.png') }}" alt="Emblem VulnHunt" class="w-full h-full object-contain drop-shadow-xs" decoding="async">
                        </div>
                    </div>

                    <!-- Security Status Pill -->
                    <div class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-clay-pill bg-indigo-50 border border-indigo-200/80 text-[10.5px] font-bold text-brand-primary uppercase tracking-wider shadow-2xs">
                        <span class="relative flex h-1.5 w-1.5">
                            <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-emerald-400 opacity-75"></span>
                            <span class="relative inline-flex rounded-full h-1.5 w-1.5 bg-emerald-500"></span>
                        </span>
                        <span>Portal Autentikasi Mahasiswa</span>
                    </div>

                    <h1 class="font-display text-2xl font-black text-slate-950 mt-1.5 tracking-tight">
                        Masuk ke VulnHunt
                    </h1>
                    <p class="text-xs text-slate-500 mt-0.5">
                        Gunakan email kampus resmi <span class="font-mono font-semibold text-slate-700 bg-slate-100/80 px-1 py-0.5 rounded text-[11px]">@ubbg.ac.id</span>
                    </p>
                </div>

                @if (session('status'))
                    <x-clay.alert type="info" class="mb-3 text-xs">
                        {{ session('status') }}
                    </x-clay.alert>
                @endif

                <form method="POST" action="{{ route('login') }}" class="space-y-3">
                    @csrf

                    <!-- Input Email -->
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

                    <!-- Input Password with Eye Toggle -->
                    <div>
                        <div class="flex items-center justify-between mb-1">
                            <label for="password" class="block text-xs font-bold uppercase tracking-wider text-slate-700">
                                Kata Sandi <span class="text-rose-500">*</span>
                            </label>
                            <a href="{{ route('password.request') }}" class="text-[11px] font-semibold text-brand-primary hover:underline transition-colors">
                                Lupa sandi?
                            </a>
                        </div>
                        <div class="relative">
                            <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-slate-400">
                                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
                                </svg>
                            </div>
                            <input id="password" :type="showPass ? 'text' : 'password'" name="password" required autocomplete="current-password"
                                   class="clay-input pl-10 pr-10 py-2 text-xs font-medium @error('password') border-rose-400 focus:border-rose-500 bg-rose-50/30 @enderror"
                                   placeholder="&bull;&bull;&bull;&bull;&bull;&bull;&bull;&bull;&bull;&bull;">
                            <button type="button" @click="showPass = !showPass" class="absolute inset-y-0 right-0 pr-3.5 flex items-center text-slate-400 hover:text-slate-700 transition-colors focus:outline-none" aria-label="Toggle kata sandi">
                                <svg x-show="!showPass" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                                </svg>
                                <svg x-show="showPass" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" style="display: none;">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l18 18"/>
                                </svg>
                            </button>
                        </div>
                        @error('password')
                            <p class="text-xs font-semibold text-rose-600 mt-1 flex items-center gap-1">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Remember Me & Security Status -->
                    <div class="flex items-center justify-between pt-0.5">
                        <label class="inline-flex items-center gap-2 cursor-pointer select-none">
                            <input id="remember" type="checkbox" name="remember"
                                   class="w-3.5 h-3.5 rounded border-slate-300 text-brand-primary focus:ring-brand-primary/20">
                            <span class="text-xs text-slate-600 font-medium">Ingat sesi</span>
                        </label>
                        <span class="text-[10.5px] text-slate-500 font-mono flex items-center gap-1 font-semibold">
                            <svg class="w-3 h-3 text-emerald-500" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M10 1.944A11.954 11.954 0 012.166 5C2.056 5.649 2 6.319 2 7c0 5.225 3.34 9.67 8 11.317C14.66 16.67 18 12.225 18 7c0-.682-.057-1.35-.166-2.001A11.954 11.954 0 0110 1.944zM11 14a1 1 0 11-2 0 1 1 0 012 0zm0-7a1 1 0 10-2 0v3a1 1 0 102 0V7z" clip-rule="evenodd"/>
                            </svg>
                            TLS 1.3 Terenkripsi
                        </span>
                    </div>

                    <!-- Submit Button with Shimmer Sweep Animation -->
                    <div class="pt-1">
                        <button type="submit" class="btn-clay-primary w-full py-2.5 text-xs sm:text-sm font-bold tracking-wide relative overflow-hidden group shadow-clay-btn-primary hover:shadow-clay-btn-primary-hover active:translate-y-0.5 transition-all">
                            <!-- Shimmer Sweep -->
                            <div class="absolute inset-0 -translate-x-full group-hover:translate-x-full transition-transform duration-700 bg-gradient-to-r from-transparent via-white/25 to-transparent pointer-events-none"></div>
                            
                            <span class="relative z-10">Masuk ke Portal Peserta</span>
                            <svg class="w-4 h-4 ml-1 relative z-10 group-hover:translate-x-1 transition-transform duration-200" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M13 7l5 5m0 0l-5 5m5-5H6"/>
                            </svg>
                        </button>
                    </div>
                </form>

                <!-- Card Footer -->
                <div class="mt-3.5 pt-3 border-t border-slate-100 text-center text-xs text-slate-500">
                    Belum memiliki akun tim?
                    <a href="{{ route('register') }}" class="font-bold text-brand-primary hover:underline hover:text-brand-primary-hover transition-colors ml-1">
                        Daftar Tim Baru &rarr;
                    </a>
                </div>
            </div>
        </div>
    </div>
</x-guest-layout>
