<!DOCTYPE html>
<html lang="id" class="h-full">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ $title ?? 'Portal Peserta - UBBG VulnHunt' }}</title>
    <meta name="description" content="Dasbor pemburu celah keamanan kampus UBBG.">

    <!-- Styles & Scripts -->
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-full bg-sage text-lumut font-sans antialiased" x-data="{ sidebarOpen: false }">
    <div class="min-h-screen flex flex-col md:flex-row">
        <!-- Mobile Sidebar Backdrop -->
        <div x-show="sidebarOpen" 
             x-transition:enter="transition-opacity ease-linear duration-300"
             x-transition:enter-start="opacity-0"
             x-transition:enter-end="opacity-100"
             x-transition:leave="transition-opacity ease-linear duration-300"
             x-transition:leave-start="opacity-100"
             x-transition:leave-end="opacity-0"
             class="fixed inset-0 bg-lumut/60 backdrop-blur-sm z-40 md:hidden"
             @click="sidebarOpen = false"
             style="display: none;"></div>

        <!-- Sidebar -->
        <aside class="fixed md:sticky top-0 inset-y-0 left-0 w-64 bg-lumut text-sage-light flex flex-col justify-between z-50 transform md:transform-none transition-transform duration-300 ease-in-out md:h-screen"
               :class="sidebarOpen ? 'translate-x-0' : '-translate-x-full md:translate-x-0'">
            <div>
                <!-- Brand Header -->
                <div class="h-16 px-5 border-b border-lumut-light flex items-center justify-between">
                    <a href="{{ route('dashboard') }}" class="flex items-center space-x-3">
                        <div class="w-8 h-8 rounded-lg bg-daun flex items-center justify-center text-white font-display font-bold">
                            VH
                        </div>
                        <div>
                            <span class="font-display font-bold text-sm text-white tracking-wide block leading-tight">UBBG VulnHunt</span>
                            <span class="text-[10px] text-abu-batu-light block">Buku Lapangan Peserta</span>
                        </div>
                    </a>
                    <button class="md:hidden text-abu-batu-light hover:text-white p-1" @click="sidebarOpen = false">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                        </svg>
                    </button>
                </div>

                <!-- Tim Badge / Greeting -->
                @php
                    $user = auth()->user();
                    $team = $user?->currentTeam();
                    $isApproved = $user?->isApprovedParticipant();
                @endphp
                <div class="p-4 mx-3 my-3 bg-lumut-light/60 rounded-xl border border-lumut-light">
                    <div class="text-[10px] text-ambar font-mono uppercase tracking-wider">Ekspedisi Tim</div>
                    <div class="font-display font-semibold text-sm text-white truncate mt-0.5">
                        {{ $team ? $team->name : 'Belum Ada Tim' }}
                    </div>
                    @if($team)
                        <div class="text-[11px] text-abu-batu-light mt-1 flex items-center justify-between">
                            <span>Sisa Kuota Aktif:</span>
                            <span class="font-mono font-bold text-white">{{ $team->remainingQuota() }} / {{ $team->effectiveQuota() }}</span>
                        </div>
                    @endif
                </div>

                <!-- Navigation Menu -->
                <nav class="px-3 space-y-1 mt-2 text-sm">
                    <a href="{{ route('dashboard') }}" 
                       class="flex items-center gap-3 px-3 py-2.5 rounded-xl font-medium transition-colors {{ request()->routeIs('dashboard') ? 'bg-daun text-white' : 'text-sage-light hover:bg-lumut-light/40' }}">
                        <svg class="w-5 h-5 opacity-80" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z"/>
                        </svg>
                        <span>Ringkasan</span>
                    </a>

                    <a href="{{ route('teams.index') }}" 
                       class="flex items-center gap-3 px-3 py-2.5 rounded-xl font-medium transition-colors {{ request()->routeIs('teams.*') ? 'bg-daun text-white' : 'text-sage-light hover:bg-lumut-light/40' }}">
                        <svg class="w-5 h-5 opacity-80" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/>
                        </svg>
                        <span>Kelola Tim</span>
                    </a>

                    <a href="{{ route('verification.index') }}" 
                       class="flex items-center gap-3 px-3 py-2.5 rounded-xl font-medium transition-colors {{ request()->routeIs('verification.*') ? 'bg-daun text-white' : 'text-sage-light hover:bg-lumut-light/40' }}">
                        <svg class="w-5 h-5 opacity-80" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/>
                        </svg>
                        <span>Verifikasi NDA</span>
                        @if($isApproved)
                            <span class="ml-auto w-2 h-2 rounded-full bg-severity-low" title="NDA Disetujui"></span>
                        @else
                            <span class="ml-auto w-2 h-2 rounded-full bg-ambar animate-pulse" title="Perlu Verifikasi"></span>
                        @endif
                    </a>

                    <a href="{{ route('rules.index') }}" 
                       class="flex items-center gap-3 px-3 py-2.5 rounded-xl font-medium transition-colors {{ request()->routeIs('rules.*') ? 'bg-daun text-white' : 'text-sage-light hover:bg-lumut-light/40' }}">
                        <svg class="w-5 h-5 opacity-80" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"/>
                        </svg>
                        <span>Aturan Main</span>
                    </a>

                    <!-- Scope (Terkunci jika belum approved) -->
                    <a href="{{ route('scope.index') }}" 
                       class="flex items-center justify-between px-3 py-2.5 rounded-xl font-medium transition-colors {{ request()->routeIs('scope.*') ? 'bg-daun text-white' : 'text-sage-light hover:bg-lumut-light/40' }} {{ ! $isApproved ? 'opacity-50' : '' }}">
                        <div class="flex items-center gap-3">
                            <svg class="w-5 h-5 opacity-80" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                            </svg>
                            <span>Daftar Scope</span>
                        </div>
                        @if(! $isApproved)
                            <svg class="w-4 h-4 text-abu-batu-light" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
                            </svg>
                        @endif
                    </a>

                    <!-- Laporan (Terkunci jika belum approved) -->
                    <a href="{{ route('reports.index') }}" 
                       class="flex items-center justify-between px-3 py-2.5 rounded-xl font-medium transition-colors {{ request()->routeIs('reports.*') ? 'bg-daun text-white' : 'text-sage-light hover:bg-lumut-light/40' }} {{ ! $isApproved ? 'opacity-50' : '' }}">
                        <div class="flex items-center gap-3">
                            <svg class="w-5 h-5 opacity-80" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                            </svg>
                            <span>Laporan Spesimen</span>
                        </div>
                        @if(! $isApproved)
                            <svg class="w-4 h-4 text-abu-batu-light" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
                            </svg>
                        @endif
                    </a>

                    <a href="{{ route('leaderboard.index') }}" 
                       class="flex items-center gap-3 px-3 py-2.5 rounded-xl font-medium transition-colors {{ request()->routeIs('leaderboard.*') ? 'bg-daun text-white' : 'text-sage-light hover:bg-lumut-light/40' }}">
                        <svg class="w-5 h-5 opacity-80" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"/>
                        </svg>
                        <span>Papan Peringkat</span>
                    </a>

                    <a href="{{ route('announcements.index') }}" 
                       class="flex items-center gap-3 px-3 py-2.5 rounded-xl font-medium transition-colors {{ request()->routeIs('announcements.*') ? 'bg-daun text-white' : 'text-sage-light hover:bg-lumut-light/40' }}">
                        <svg class="w-5 h-5 opacity-80" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5.882V19.24a1.76 1.76 0 01-3.417.592l-2.147-6.15M18 13a3 3 0 100-6M5.436 13.683A4.001 4.001 0 017 6h1.832c4.1 0 7.625-1.234 9.168-3v14c-1.543-1.766-5.067-3-9.168-3H7a3.988 3.988 0 01-1.564-.317z"/>
                        </svg>
                        <span>Pengumuman</span>
                    </a>
                </nav>
            </div>

            <!-- User Footer in Sidebar -->
            <div class="p-4 border-t border-lumut-light">
                <div class="flex items-center justify-between">
                    <div class="truncate mr-2">
                        <p class="text-xs font-semibold text-white truncate">{{ $user->name }}</p>
                        <p class="text-[11px] text-abu-batu-light font-mono truncate">{{ $user->nim ?? $user->email }}</p>
                    </div>
                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button type="submit" class="p-1.5 rounded-lg text-abu-batu-light hover:text-white hover:bg-lumut-light transition-colors" title="Keluar">
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
            <div class="md:hidden bg-putih-daun border-b border-abu-batu/20 px-4 h-14 flex items-center justify-between">
                <button @click="sidebarOpen = true" class="p-2 rounded-lg text-lumut hover:bg-sage">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/>
                    </svg>
                </button>
                <span class="font-display font-bold text-sm text-lumut">UBBG VulnHunt</span>
                <div class="w-6"></div>
            </div>

            <!-- Page Content -->
            <main class="flex-1 p-4 sm:p-6 lg:p-8 max-w-7xl w-full mx-auto">
                <!-- Flash Alerts -->
                @if (session('success'))
                    <div class="mb-6 p-4 rounded-xl bg-daun/10 border border-daun/30 text-daun flex items-center gap-3">
                        <svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                        </svg>
                        <p class="text-sm font-medium">{{ session('success') }}</p>
                    </div>
                @endif

                @if (session('error'))
                    <div class="mb-6 p-4 rounded-xl bg-severity-critical/10 border border-severity-critical/30 text-severity-critical flex items-center gap-3">
                        <svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                        <p class="text-sm font-medium">{{ session('error') }}</p>
                    </div>
                @endif

                {{ $slot }}
            </main>
        </div>
    </div>
</body>
</html>
