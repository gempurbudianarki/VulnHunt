<x-app-layout>
    <div class="space-y-8">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <div>
                <span class="clay-badge bg-indigo-50 text-brand-primary border-indigo-200 text-[10px] uppercase font-bold tracking-wider">
                    Papan Peringkat Lomba
                </span>
                <h1 class="text-2xl sm:text-3xl font-extrabold text-slate-900 tracking-tight mt-1">
                    Klasemen Resmi Ekspedisi Tim
                </h1>
                <p class="text-xs text-slate-600 mt-1">
                    Urutan ditentukan berdasarkan total akumulasi poin, jumlah laporan critical, high, dan kecepatan validasi.
                </p>
            </div>

            @if($isAdmin && ! $isPubliclyVisible)
                <div class="shrink-0">
                    <span class="clay-badge bg-amber-50 text-amber-800 border-amber-200 py-1 px-3 text-xs font-bold">
                        <span class="w-2 h-2 rounded-full bg-amber-500 animate-pulse"></span>
                        Pratinjau Admin: Mode Tersembunyi untuk Peserta
                    </span>
                </div>
            @endif
        </div>

        @if(! $isVisible && ! $isAdmin)
            <div class="clay-card p-10 text-center space-y-4 max-w-xl mx-auto">
                <div class="w-16 h-16 rounded-full bg-amber-50 border border-amber-200 text-amber-700 flex items-center justify-center mx-auto shadow-sm">
                    <svg class="w-8 h-8" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l18 18"/>
                    </svg>
                </div>
                <h2 class="text-xl font-bold text-slate-900">Papan Peringkat Sedang Dibekukan</h2>
                <p class="text-xs text-slate-600 leading-relaxed">
                    Panitia sedang menyembunyikan tampilan papan peringkat secara terkoordinasi. Peringkat akhir akan diumumkan secara resmi setelah masa kompetisi berakhir.
                </p>
            </div>
        @else
            <x-clay.table-wrapper title="Klasemen Perolehan Poin" subtitle="Menampilkan nama tim, jumlah laporan valid, dan total poin resmi">
                <thead>
                    <tr class="border-b border-slate-100 text-slate-600 font-mono text-[11px] uppercase bg-slate-50/50">
                        <th class="py-3 px-4 font-bold text-left w-24">Peringkat</th>
                        <th class="py-3 px-4 font-bold text-left">Nama Tim</th>
                        <th class="py-3 px-4 font-bold text-center w-40">Laporan Valid</th>
                        <th class="py-3 px-4 font-bold text-right w-44">Total Poin</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse($rankings as $idx => $rank)
                        <tr class="hover:bg-slate-50/60 transition-colors">
                            <td class="py-3 px-4 font-mono font-bold">
                                @if($idx === 0)
                                    <span class="inline-flex items-center justify-center w-7 h-7 rounded-full bg-amber-400 text-slate-950 font-black text-xs shadow-sm">1</span>
                                @elseif($idx === 1)
                                    <span class="inline-flex items-center justify-center w-7 h-7 rounded-full bg-slate-300 text-slate-900 font-black text-xs shadow-sm">2</span>
                                @elseif($idx === 2)
                                    <span class="inline-flex items-center justify-center w-7 h-7 rounded-full bg-amber-700 text-white font-black text-xs shadow-sm">3</span>
                                @else
                                    <span class="text-slate-600 font-semibold pl-2">#{{ $idx + 1 }}</span>
                                @endif
                            </td>
                            <td class="py-3 px-4 font-bold text-slate-900">
                                {{ $rank->team_name }}
                            </td>
                            <td class="py-3 px-4 text-center">
                                <span class="clay-badge bg-emerald-50 text-emerald-800 border-emerald-200 text-xs py-0.5 px-2.5 font-bold">
                                    {{ $rank->valid_reports_count }} Laporan
                                </span>
                            </td>
                            <td class="py-3 px-4 text-right font-mono font-black text-brand-primary text-sm">
                                {{ number_format($rank->total_points) }} Poin
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="py-8 text-center text-slate-500 text-xs">
                                Belum ada data peringkat tim yang tersedia.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </x-clay.table-wrapper>
        @endif
    </div>
</x-app-layout>
