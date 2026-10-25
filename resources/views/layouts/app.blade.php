<!DOCTYPE html>
<html lang="id" class="h-full">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ $title ?? 'Portal Peserta - VulnHunt' }}</title>
    <meta name="description" content="Portal peserta kompetisi bug bounty kampus VulnHunt UBBG.">

    <!-- Favicon & Brand Icons -->
    <link rel="icon" type="image/x-icon" href="{{ asset('brand/favicon.ico') }}">
    <link rel="icon" type="image/png" sizes="32x32" href="{{ asset('brand/favicon-32x32.png') }}">
    <link rel="apple-touch-icon" sizes="180x180" href="{{ asset('brand/apple-touch-icon.png') }}">

    <!-- Styles & Scripts -->
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-full bg-brand-canvas text-brand-dark font-sans antialiased selection:bg-brand-primary/20 selection:text-brand-primary" x-data="{ sidebarOpen: false }">
    <div class="min-h-screen flex flex-col md:flex-row">
        <!-- Mobile Sidebar Backdrop -->
        <div x-show="sidebarOpen" 
             x-transition:enter="transition-opacity ease-linear duration-300"
             x-transition:enter-start="opacity-0"
             x-transition:enter-end="opacity-100"
             x-transition:leave="transition-opacity ease-linear duration-300"
             x-transition:leave-start="opacity-100"
             x-transition:leave-end="opacity-0"
             class="fixed inset-0 bg-slate-900/60 backdrop-blur-sm z-40 md:hidden"
             @click="sidebarOpen = false"
             style="display: none;"></div>

        <!-- Clay Sidebar -->
        <aside class="fixed md:sticky top-0 inset-y-0 left-0 w-64 bg-slate-900 text-slate-200 flex flex-col justify-between z-50 transform md:transform-none transition-transform duration-300 ease-in-out md:h-screen border-r border-slate-800 shadow-xl"
               :class="sidebarOpen ? 'translate-x-0' : '-translate-x-full md:translate-x-0'">
            <div>
                <!-- Brand Header -->
                <div class="h-20 px-5 border-b border-slate-800 flex items-center justify-between">
                    <a href="{{ route('dashboard') }}" class="flex items-center gap-3 group focus:outline-none focus:ring-2 focus:ring-brand-primary rounded-clay-md">
                        <div class="w-10 h-10 rounded-clay-sm bg-white/10 border border-white/20 p-1 flex items-center justify-center shrink-0 group-hover:scale-105 transition-transform">
                            <img src="{{ asset('brand/logo.png') }}" alt="VulnHunt" class="w-full h-full object-contain">
                        </div>
                        <div>
                            <div class="flex items-center gap-1.5">
                                <span class="font-display font-extrabold text-base text-white tracking-tight block leading-tight">VulnHunt</span>
                                <span class="clay-badge bg-indigo-500/20 text-indigo-300 border-indigo-400/30 text-[9px] py-0 px-1.5">UBBG</span>
                            </div>
                            <span class="text-[11px] text-slate-400 font-medium block mt-0.5">Portal Peserta</span>
                        </div>
                    </a>
                    <button class="md:hidden text-slate-400 hover:text-white p-1" @click="sidebarOpen = false" aria-label="Tutup Menu Navigasi">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                        </svg>
                    </button>
                </div>

                <!-- Tim Badge / Ekspedisi Card -->
                @php
                    $user = auth()->user();
                    $team = $user?->currentTeam();
                    $isApproved = $user?->isApprovedParticipant();
                @endphp
                <div class="p-4 mx-3 my-3 bg-slate-800/80 rounded-clay-md border border-slate-700/80 shadow-inner">
                    <div class="text-[10px] text-amber-400 font-mono font-bold uppercase tracking-wider">Tim Ekspedisi</div>
                    <div class="font-display font-bold text-sm text-white truncate mt-0.5">
                        {{ $team ? $team->name : 'Belum Bergabung Tim' }}
                    </div>
                    @if($team)
                        <div class="text-[11px] text-slate-300 mt-2 flex items-center justify-between border-t border-slate-700 pt-2">
                            <span>Sisa Kuota Aktif:</span>
                            <span class="font-mono font-extrabold text-amber-400">{{ $team->remainingQuota() }} / {{ $team->effectiveQuota() }}</span>
                        </div>
                    @else
                        <div class="mt-2 pt-2 border-t border-slate-700">
                            <a href="{{ route('teams.index') }}" class="text-[11px] text-indigo-400 hover:text-indigo-300 font-semibold block">
                                + Buat atau Gabung Tim
                            </a>
                        </div>
                    @endif
                </div>

                <!-- Navigation Menu -->
                <nav class="px-3 space-y-1.5 mt-2 text-sm font-semibold">
                    <a href="{{ route('dashboard') }}" 
                       class="flex items-center gap-3 px-3.5 py-2.5 rounded-clay-pill transition-all {{ request()->routeIs('dashboard') ? 'bg-brand-primary text-white shadow-clay-pill' : 'text-slate-300 hover:bg-slate-800 hover:text-white' }}">
                        <svg class="w-5 h-5 shrink-0 opacity-80" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z"/>
                        </svg>
                        <span>Ringkasan Dasbor</span>
                    </a>

                    <a href="{{ route('teams.index') }}" 
                       class="flex items-center gap-3 px-3.5 py-2.5 rounded-clay-pill transition-all {{ request()->routeIs('teams.*') ? 'bg-brand-primary text-white shadow-clay-pill' : 'text-slate-300 hover:bg-slate-800 hover:text-white' }}">
                        <svg class="w-5 h-5 shrink-0 opacity-80" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/>
                        </svg>
                        <span>Kelola Tim</span>
                    </a>

                    <a href="{{ route('verification.index') }}" 
                       class="flex items-center gap-3 px-3.5 py-2.5 rounded-clay-pill transition-all {{ request()->routeIs('verification.*') ? 'bg-brand-primary text-white shadow-clay-pill' : 'text-slate-300 hover:bg-slate-800 hover:text-white' }}">
                        <svg class="w-5 h-5 shrink-0 opacity-80" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/>
                        </svg>
                        <span>Verifikasi NDA</span>
                        @if($isApproved)
                            <span class="ml-auto w-2 h-2 rounded-full bg-emerald-400" title="NDA Disetujui"></span>
                        @else
                            <span class="ml-auto w-2 h-2 rounded-full bg-amber-400 animate-pulse" title="Perlu Verifikasi"></span>
                        @endif
                    </a>

                    <a href="{{ route('rules.index') }}" 
                       class="flex items-center gap-3 px-3.5 py-2.5 rounded-clay-pill transition-all {{ request()->routeIs('rules.*') ? 'bg-brand-primary text-white shadow-clay-pill' : 'text-slate-300 hover:bg-slate-800 hover:text-white' }}">
                        <svg class="w-5 h-5 shrink-0 opacity-80" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"/>
                        </svg>
                        <span>Aturan Main (RoE)</span>
                    </a>

                    <!-- Scope (Terkunci jika belum approved) -->
                    <a href="{{ route('scope.index') }}" 
                       class="flex items-center justify-between px-3.5 py-2.5 rounded-clay-pill transition-all {{ request()->routeIs('scope.*') ? 'bg-brand-primary text-white shadow-clay-pill' : 'text-slate-300 hover:bg-slate-800 hover:text-white' }} {{ ! $isApproved ? 'opacity-50' : '' }}">
                        <div class="flex items-center gap-3">
                            <svg class="w-5 h-5 shrink-0 opacity-80" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                            </svg>
                            <span>Daftar Scope</span>
                        </div>
                        @if(! $isApproved)
                            <svg class="w-4 h-4 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
                            </svg>
                        @endif
                    </a>

                    <!-- Laporan (Terkunci jika belum approved) -->
                    <a href="{{ route('reports.index') }}" 
                       class="flex items-center justify-between px-3.5 py-2.5 rounded-clay-pill transition-all {{ request()->routeIs('reports.*') ? 'bg-brand-primary text-white shadow-clay-pill' : 'text-slate-300 hover:bg-slate-800 hover:text-white' }} {{ ! $isApproved ? 'opacity-50' : '' }}">
                        <div class="flex items-center gap-3">
                            <svg class="w-5 h-5 shrink-0 opacity-80" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                            </svg>
                            <span>Laporan Spesimen</span>
                        </div>
                        @if(! $isApproved)
                            <svg class="w-4 h-4 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
                            </svg>
                        @endif
                    </a>

                    <a href="{{ route('leaderboard.index') }}" 
                       class="flex items-center gap-3 px-3.5 py-2.5 rounded-clay-pill transition-all {{ request()->routeIs('leaderboard.*') ? 'bg-brand-primary text-white shadow-clay-pill' : 'text-slate-300 hover:bg-slate-800 hover:text-white' }}">
                        <svg class="w-5 h-5 shrink-0 opacity-80" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"/>
                        </svg>
                        <span>Papan Peringkat</span>
                    </a>

                    <a href="{{ route('announcements.index') }}" 
                       class="flex items-center gap-3 px-3.5 py-2.5 rounded-clay-pill transition-all {{ request()->routeIs('announcements.*') ? 'bg-brand-primary text-white shadow-clay-pill' : 'text-slate-300 hover:bg-slate-800 hover:text-white' }}">
                        <svg class="w-5 h-5 shrink-0 opacity-80" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5.882V19.24a1.76 1.76 0 01-3.417.592l-2.147-6.15M18 13a3 3 0 100-6M5.436 13.683A4.001 4.001 0 017 6h1.832c4.1 0 7.625-1.234 9.168-3v14c-1.543-1.766-5.067-3-9.168-3H7a3.988 3.988 0 01-1.564-.317z"/>
                        </svg>
                        <span>Pengumuman</span>
                    </a>
                </nav>
            </div>

            <!-- User Footer in Sidebar -->
            <div class="p-4 border-t border-slate-800 bg-slate-950/40">
                <div class="flex items-center justify-between">
                    <div class="truncate mr-2">
                        <p class="text-xs font-bold text-white truncate">{{ $user->name }}</p>
                        <p class="text-[11px] text-slate-400 font-mono truncate">{{ $user->nim ?? $user->email }}</p>
                    </div>
                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button type="submit" class="p-2 rounded-clay-pill text-slate-400 hover:text-white hover:bg-slate-800 transition-colors" title="Keluar dari Akun" aria-label="Keluar dari Akun">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/>
                            </svg>
                        </button>
                    </form>
                </div>
            </div>
        </aside>

        <!-- Main Content Area -->
        <div class="flex-1 flex flex-col min-w-0">
            <!-- Top Mobile Bar -->
            <div class="md:hidden bg-white/90 backdrop-blur border-b border-slate-200/80 px-4 h-16 flex items-center justify-between">
                <button @click="sidebarOpen = true" class="p-2 rounded-clay-pill text-slate-700 hover:bg-slate-100 focus:outline-none" aria-label="Buka Menu Navigasi">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/>
                    </svg>
                </button>
                <div class="flex items-center gap-2">
                    <img src="{{ asset('brand/logo.png') }}" alt="VulnHunt" class="w-7 h-7 object-contain">
                    <span class="font-display font-extrabold text-sm text-slate-900">VulnHunt</span>
                </div>
                <div class="w-6"></div>
            </div>

            <!-- Page Content -->
            <main class="flex-1 p-4 sm:p-6 lg:p-8 max-w-7xl w-full mx-auto space-y-6">
                <!-- Flash Alerts -->
                @if (session('success'))
                    <x-clay.alert type="success">
                        {{ session('success') }}
                    </x-clay.alert>
                @endif

                @if (session('error'))
                    <x-clay.alert type="danger">
                        {{ session('error') }}
                    </x-clay.alert>
                @endif

                {{ $slot }}
            </main>
        </div>
    </div>
</body>
</html>
