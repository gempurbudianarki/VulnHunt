<x-app-layout>
    <div class="space-y-8">
        <div class="flex items-center justify-between">
            <div>
                <span class="text-xs font-mono font-bold text-ambar uppercase tracking-wider">Koleksi Spesimen Temuan</span>
                <h1 class="text-2xl sm:text-3xl font-extrabold text-lumut tracking-tight mt-0.5">
                    Laporan Kerentanan Tim
                </h1>
                <p class="text-xs text-abu-batu mt-1">
                    Daftar seluruh laporan celah keamanan yang telah dikirimkan oleh tim Anda.
                </p>
            </div>
            <a href="{{ route('reports.create') }}" class="btn-clay-primary py-2.5 px-4 text-xs">
                + Kirim Laporan Baru
            </a>
        </div>

        @if($reports->isEmpty())
            <div class="specimen-card p-12 bg-putih-daun text-center border-dashed">
                <p class="text-sm font-semibold text-lumut">Belum Ada Spesimen Laporan</p>
                <p class="text-xs text-abu-batu mt-1">Gunakan tombol di atas untuk mengirim laporan temuan baru.</p>
            </div>
        @else
            <div class="space-y-4">
                @foreach($reports as $report)
                    <div class="specimen-card p-6 bg-putih-daun">
                        <div class="flex items-center justify-between">
                            <span class="font-mono font-bold text-lumut text-sm">{{ $report->code }}</span>
                            <span class="px-2.5 py-0.5 rounded-full text-xs font-bold uppercase bg-sage text-lumut">{{ $report->status }}</span>
                        </div>
                        <h3 class="font-display font-bold text-lg text-lumut mt-2">{{ $report->title }}</h3>
                    </div>
                @endforeach
            </div>
        @endif
    </div>
</x-app-layout>
