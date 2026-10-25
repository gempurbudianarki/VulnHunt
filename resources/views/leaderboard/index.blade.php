<x-app-layout>
    <div class="space-y-8">
        <div>
            <span class="clay-badge bg-indigo-50 text-brand-primary border-indigo-200 text-[10px] uppercase font-bold tracking-wider">
                Papan Peringkat Lomba
            </span>
            <h1 class="text-2xl sm:text-3xl font-extrabold text-slate-900 tracking-tight mt-1">
                Peringkat Ekspedisi Tim
            </h1>
            <p class="text-xs text-slate-500 mt-1">
                Klasemen perolehan poin berdasarkan laporan kerentanan valid dan terverifikasi panitia.
            </p>
        </div>

        @if(($mode === 'hidden' || $mode === 'hidden_until_end') && ! $isAdmin)
            <x-clay.empty-state
                title="Papan Peringkat Sedang Dibekukan"
                description="Panitia menyembunyikan papan peringkat hingga periode pengumuman akhir tiba untuk menjaga kompetisi tetap dinamis."
            />
        @else
            <x-clay.table-wrapper title="Klasemen Sementara" subtitle="Urutan tim berdasarkan poin tertinggi">
                <thead>
                    <tr class="border-b border-slate-100 text-slate-500 font-mono text-[11px] uppercase bg-slate-50/50">
                        <th class="py-3 px-4 font-bold">Peringkat</th>
                        <th class="py-3 px-4 font-bold">Nama Tim</th>
                        <th class="py-3 px-4 font-bold">Anggota</th>
                        <th class="py-3 px-4 font-bold text-right">Total Poin</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse($teams as $idx => $t)
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
                            <td class="py-3 px-4 font-bold text-slate-900">{{ $t->name }}</td>
                            <td class="py-3 px-4 text-slate-500 text-xs">{{ $t->members->count() }} orang</td>
                            <td class="py-3 px-4 text-right font-mono font-black text-brand-primary text-sm">0 Poin</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="py-6 text-center text-slate-400 text-xs">Belum ada tim terdaftar.</td>
                        </tr>
                    @endforelse
                </tbody>
            </x-clay.table-wrapper>
        @endif
    </div>
</x-app-layout>
