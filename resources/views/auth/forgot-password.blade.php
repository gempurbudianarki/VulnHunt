<x-guest-layout>
    <div class="py-12 px-4 sm:px-6 lg:px-8 max-w-md mx-auto">
        <div class="specimen-card p-8 bg-putih-daun shadow-clay-card">
            <div class="text-center mb-6">
                <span class="font-mono text-xs font-bold text-ambar bg-lumut/5 px-3 py-1 rounded-full uppercase tracking-wider">
                    Pemulihan Akses
                </span>
                <h2 class="text-2xl font-bold font-display text-lumut mt-2">
                    Lupa Kata Sandi
                </h2>
                <p class="text-xs text-abu-batu mt-1">
                    Masukkan email kampus Anda. Kami akan mengirimkan tautan untuk mengatur ulang kata sandi.
                </p>
            </div>

            @if (session('status'))
                <div class="mb-4 text-xs font-medium text-daun bg-daun/10 border border-daun/30 p-3 rounded-xl">
                    {{ session('status') }}
                </div>
            @endif

            <form method="POST" action="{{ route('password.email') }}" class="space-y-4">
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

                <div class="pt-2">
                    <button type="submit" class="w-full btn-clay-primary py-3">
                        Kirim Tautan Reset Kata Sandi
                    </button>
                </div>
            </form>

            <div class="mt-6 pt-4 border-t border-abu-batu/15 text-center text-xs text-abu-batu">
                <a href="{{ route('login') }}" class="text-daun font-semibold hover:underline">
                    &larr; Kembali ke halaman Masuk
                </a>
            </div>
        </div>
    </div>
</x-guest-layout>
