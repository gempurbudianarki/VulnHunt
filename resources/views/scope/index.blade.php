<x-app-layout>
    <div class="space-y-8">
        <div>
            <span class="text-xs font-mono font-bold text-ambar uppercase tracking-wider">Aset & Batasan Pengujian</span>
            <h1 class="text-2xl sm:text-3xl font-extrabold text-lumut tracking-tight mt-0.5">
                Target Aset & Scope Pengujian
            </h1>
            <p class="text-xs text-abu-batu mt-1">
                Daftar target yang diperbolehkan (In-Scope) dan target yang dilarang keras diuji (Out-of-Scope).
            </p>
        </div>

        <!-- In Scope -->
        <div class="specimen-card p-6 bg-putih-daun shadow-sm">
            <h2 class="font-display font-bold text-lg text-lumut mb-4 flex items-center gap-2">
                <span class="w-3 h-3 rounded-full bg-severity-low"></span>
                Target Diizinkan (In-Scope)
            </h2>
            <div class="overflow-x-auto">
                <table class="w-full text-left text-xs">
                    <thead>
                        <tr class="border-b border-abu-batu/20 text-abu-batu font-mono text-[11px] uppercase">
                            <th class="py-2.5 px-3">Nama Target</th>
                            <th class="py-2.5 px-3">Domain / URL Target</th>
                            <th class="py-2.5 px-3">Tipe</th>
                            <th class="py-2.5 px-3">Bobot Kritis</th>
                            <th class="py-2.5 px-3">Catatan Khusus</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-abu-batu/10">
                        @forelse($inScopes as $scope)
                            <tr>
                                <td class="py-3 px-3 font-semibold text-lumut">{{ $scope->name }}</td>
                                <td class="py-3 px-3 font-mono text-daun font-medium">{{ $scope->target }}</td>
                                <td class="py-3 px-3 uppercase font-mono">{{ $scope->asset_type }}</td>
                                <td class="py-3 px-3 font-mono font-bold">{{ $scope->criticality_weight }}x</td>
                                <td class="py-3 px-3 text-abu-batu">{{ $scope->notes ?? '-' }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="py-4 text-center text-abu-batu">Belum ada target in-scope aktif.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Out of Scope -->
        <div class="specimen-card p-6 bg-putih-daun shadow-sm">
            <h2 class="font-display font-bold text-lg text-severity-critical mb-4 flex items-center gap-2">
                <span class="w-3 h-3 rounded-full bg-severity-critical"></span>
                Target Dilarang (Out-of-Scope)
            </h2>
            <div class="overflow-x-auto">
                <table class="w-full text-left text-xs">
                    <thead>
                        <tr class="border-b border-abu-batu/20 text-abu-batu font-mono text-[11px] uppercase">
                            <th class="py-2.5 px-3">Nama Target</th>
                            <th class="py-2.5 px-3">Domain / URL Target</th>
                            <th class="py-2.5 px-3">Catatan Batasan</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-abu-batu/10">
                        @forelse($outScopes as $scope)
                            <tr>
                                <td class="py-3 px-3 font-semibold text-lumut">{{ $scope->name }}</td>
                                <td class="py-3 px-3 font-mono text-severity-critical line-through">{{ $scope->target }}</td>
                                <td class="py-3 px-3 text-severity-critical font-medium">{{ $scope->notes ?? 'Dilarang keras diuji.' }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="3" class="py-4 text-center text-abu-batu">Tidak ada daftar out-of-scope khusus.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</x-app-layout>
