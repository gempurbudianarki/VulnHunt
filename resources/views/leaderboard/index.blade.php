<x-app-layout>
    <div class="space-y-8">
        <div>
            <span class="text-xs font-mono font-bold text-ambar uppercase tracking-wider">Papan Peringkat Lomba</span>
            <h1 class="text-2xl sm:text-3xl font-extrabold text-lumut tracking-tight mt-0.5">
                Peringkat Ekspedisi Tim
            </h1>
            <p class="text-xs text-abu-batu mt-1">
                Klasemen perolehan poin berdasarkan laporan kerentanan valid dan terverifikasi.
            </p>
        </div>

        @if(($mode === 'hidden' || $mode === 'hidden_until_end') && ! $isAdmin)
            <div class="specimen-card p-12 bg-putih-daun text-center border-dashed">
                <p class="text-sm font-semibold text-lumut">Papan Peringkat Sedang Dibekukan</p>
                <p class="text-xs text-abu-batu mt-1">Panitia menyembunyikan papan peringkat hingga periode pengumuman akhir tiba.</p>
            </div>
        @else
            <div class="specimen-card p-6 bg-putih-daun shadow-sm">
                <div class="overflow-x-auto">
                    <table class="w-full text-left text-xs">
                        <thead>
                            <tr class="border-b border-abu-batu/20 text-abu-batu font-mono text-[11px] uppercase">
                                <th class="py-2.5 px-3">Peringkat</th>
                                <th class="py-2.5 px-3">Nama Tim</th>
                                <th class="py-2.5 px-3">Anggota</th>
                                <th class="py-2.5 px-3 text-right">Total Poin</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-abu-batu/10">
                            @forelse($teams as $idx => $t)
                                <tr>
                                    <td class="py-3 px-3 font-mono font-bold text-lumut">#{{ $idx + 1 }}</td>
                                    <td class="py-3 px-3 font-semibold text-lumut">{{ $t->name }}</td>
                                    <td class="py-3 px-3 text-abu-batu">{{ $t->members->count() }} orang</td>
                                    <td class="py-3 px-3 text-right font-mono font-bold text-daun">0 Poin</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="4" class="py-4 text-center text-abu-batu">Belum ada tim terdaftar.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        @endif
    </div>
</x-app-layout>
