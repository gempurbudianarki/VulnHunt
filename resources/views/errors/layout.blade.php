<!DOCTYPE html>
<html lang="id" class="h-full">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title') - VulnHunt UBBG</title>
    <link rel="icon" type="image/x-icon" href="{{ asset('brand/favicon.ico') }}">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@600;700;800&family=JetBrains+Mono:wght@500;700&display=swap">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-full bg-slate-950 text-slate-100 flex items-center justify-center p-4 selection:bg-brand-primary/20 selection:text-brand-primary antialiased relative overflow-hidden">
    <!-- Subtle Background Ambient Blobs -->
    <div class="absolute -top-32 -left-32 w-96 h-96 bg-brand-primary/10 rounded-full blur-3xl pointer-events-none"></div>
    <div class="absolute -bottom-32 -right-32 w-96 h-96 bg-amber-500/10 rounded-full blur-3xl pointer-events-none"></div>

    <main class="w-full max-w-lg relative z-10 text-center">
        <div class="clay-card p-8 sm:p-10 border border-slate-800 bg-slate-900/90 shadow-2xl backdrop-blur-md">
            <!-- Icon Specimen -->
            <div class="w-16 h-16 rounded-clay-md @yield('icon-bg', 'bg-slate-800 text-slate-300') flex items-center justify-center mx-auto mb-6 shadow-sm">
                @yield('icon')
            </div>

            <!-- Error Code Pill -->
            <span class="clay-badge @yield('badge-class', 'bg-slate-800 text-slate-300 border-slate-700') text-xs uppercase font-mono font-bold tracking-widest px-3 py-1 mb-3 inline-block">
                KODE KESALAHAN @yield('code')
            </span>

            <h1 class="text-2xl sm:text-3xl font-extrabold text-white tracking-tight mt-1 mb-3">
                @yield('heading')
            </h1>

            <p class="text-sm text-slate-400 leading-relaxed mb-8 max-w-sm mx-auto">
                @yield('message')
            </p>

            <div class="flex flex-wrap items-center justify-center gap-3 pt-2 border-t border-slate-800">
                <a href="{{ url('/') }}" class="btn-clay-secondary py-2.5 px-5 text-xs text-slate-300 border-slate-700 hover:bg-slate-800">
                    &larr; Halaman Depan
                </a>
                @auth
                    <a href="{{ route('dashboard') }}" class="btn-clay-primary py-2.5 px-5 text-xs">
                        Buka Dasbor Saya &rarr;
                    </a>
                @else
                    <a href="{{ route('login') }}" class="btn-clay-primary py-2.5 px-5 text-xs">
                        Masuk Akun &rarr;
                    </a>
                @endauth
            </div>
        </div>

        <div class="mt-6 text-xs text-slate-500 font-mono">
            VulnHunt Security Platform &bull; Safe Harbor Protected
        </div>
    </main>
</body>
</html>
