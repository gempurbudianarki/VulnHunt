<x-guest-layout>
    <div class="py-12 px-4 sm:px-6 lg:px-8 max-w-md mx-auto">
        <div class="specimen-card p-8 bg-putih-daun shadow-clay-card text-center">
            <div class="w-12 h-12 rounded-2xl bg-daun/10 text-daun mx-auto flex items-center justify-center mb-4">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                </svg>
            </div>

            <h2 class="text-2xl font-bold font-display text-lumut">
                Verifikasi Email Kampus
            </h2>

            <p class="text-xs text-abu-batu mt-2 leading-relaxed">
                Terima kasih telah mendaftar! Sebelum melanjutkan ke dasbor tim, silakan klik tautan verifikasi bertanda tangan yang baru saja kami kirimkan ke email Anda (<strong>{{ auth()->user()?->email }}</strong>).
            </p>

            @if (session('status') == 'verification-link-sent')
                <div class="my-4 text-xs font-medium text-daun bg-daun/10 border border-daun/30 p-3 rounded-xl">
                    Tautan verifikasi baru telah dikirimkan ke alamat email Anda.
                </div>
            @endif

            <div class="mt-6 space-y-3">
                <form method="POST" action="{{ route('verification.send') }}">
                    @csrf
                    <button type="submit" class="w-full btn-clay-primary py-2.5 text-xs">
                        Kirim Ulang Email Verifikasi
                    </button>
                </form>

                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button type="submit" class="w-full btn-clay-secondary py-2 text-xs">
                        Keluar dari Akun
                    </button>
                </form>
            </div>
        </div>
    </div>
</x-guest-layout>
