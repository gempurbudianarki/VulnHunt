<!DOCTYPE html>
<html lang="id" class="h-full">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ $title ?? 'UBBG VulnHunt - Platform Lomba Bug Bounty Kampus' }}</title>
    <meta name="description" content="Platform resmi kompetisi bug bounty internal Universitas Bina Bangsa Getsempena (UBBG). Temukan celah keamanan secara etis dan aman.">

    <!-- Styles & Scripts -->
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-full bg-sage text-lumut font-sans antialiased flex flex-col justify-between">
    <!-- Navbar Publik -->
    <header class="bg-putih-daun/80 backdrop-blur border-b border-abu-batu/20 sticky top-0 z-40">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 h-16 flex items-center justify-between">
            <a href="{{ url('/') }}" class="flex items-center space-x-3 group">
                <div class="w-9 h-9 rounded-xl bg-lumut flex items-center justify-center text-ambar shadow-sm group-hover:scale-105 transition-transform">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
                    </svg>
                </div>
                <div>
                    <span class="font-display font-bold text-lg text-lumut tracking-tight block leading-tight">UBBG VulnHunt</span>
                    <span class="text-xs text-abu-batu block tracking-wide">Buku Lapangan Riset Keamanan</span>
                </div>
            </a>

            <nav class="flex items-center space-x-3">
                @auth
                    <a href="{{ route('dashboard') }}" class="btn-clay-primary text-sm py-2 px-4">
                        Buka Dasbor
                    </a>
                @else
                    <a href="{{ route('login') }}" class="btn-clay-secondary text-sm py-2 px-4">
                        Masuk
                    </a>
                    <a href="{{ route('register') }}" class="btn-clay-primary text-sm py-2 px-4">
                        Daftar Tim
                    </a>
                @endauth
            </nav>
        </div>
    </header>

    <!-- Main Content -->
    <main class="flex-grow">
        {{ $slot }}
    </main>

    <!-- Footer -->
    <footer class="bg-lumut text-sage-light py-10 border-t border-lumut-light mt-16">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 flex flex-col md:flex-row justify-between items-center gap-6">
            <div>
                <p class="font-display font-bold text-base text-white tracking-wide">UBBG VulnHunt 2026</p>
                <p class="text-xs text-abu-batu-light mt-1">Program Pengungkapan Kerentanan Terkoordinasi Universitas Bina Bangsa Getsempena.</p>
            </div>
            <div class="text-xs text-abu-batu-light text-center md:text-right">
                <p>Pengujian hanya diperkenankan sesuai daftar target yang sah dan safe harbor terlampir.</p>
                <p class="mt-1">&copy; {{ date('Y') }} Panitia Lomba Bug Bounty UBBG. Seluruh hak cipta dilindungi.</p>
            </div>
        </div>
    </footer>
</body>
</html>
