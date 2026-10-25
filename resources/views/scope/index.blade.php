<x-app-layout>
    <div class="space-y-8">
        <div>
            <span class="clay-badge bg-indigo-50 text-brand-primary border-indigo-200 text-[10px] uppercase font-bold tracking-wider">
                Aset & Batasan Pengujian
            </span>
            <h1 class="text-2xl sm:text-3xl font-extrabold text-slate-900 tracking-tight mt-1">
                Target Aset & Scope Pengujian
            </h1>
            <p class="text-xs text-slate-600 mt-1">
                Daftar target yang diperbolehkan (In-Scope) dan target yang dilarang keras diuji (Out-of-Scope).
            </p>
        </div>

        <!-- In Scope -->
        <x-clay.table-wrapper title="Target Diizinkan (In-Scope)" subtitle="Aset resmi yang sah untuk diteliti keamanannya.">
            <thead>
                <tr class="border-b border-slate-100 text-slate-600 font-mono text-[11px] uppercase bg-slate-50/50">
                    <th class="py-3 px-4 font-bold">Nama Target</th>
                    <th class="py-3 px-4 font-bold">Domain / URL Target</th>
                    <th class="py-3 px-4 font-bold">Tipe Aset</th>
                    <th class="py-3 px-4 font-bold">Bobot Kritis</th>
                    <th class="py-3 px-4 font-bold">Catatan Khusus</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @forelse($inScopes as $scope)
                    <tr class="hover:bg-slate-50/70 transition-colors">
                        <td class="py-3 px-4 font-bold text-slate-900">{{ $scope->name }}</td>
                        <td class="py-3 px-4 font-mono text-brand-primary font-semibold text-xs">{{ $scope->target }}</td>
                        <td class="py-3 px-4 uppercase font-mono text-xs">
                            <span class="clay-badge bg-slate-100 text-slate-700 border-slate-200 text-[10px]">
                                {{ $scope->asset_type }}
                            </span>
                        </td>
                        <td class="py-3 px-4 font-mono font-bold text-slate-900">{{ $scope->criticality_weight }}x</td>
                        <td class="py-3 px-4 text-slate-500 text-xs">{{ $scope->notes ?? '-' }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="py-6 text-center text-slate-400 text-xs">Belum ada target in-scope aktif.</td>
                    </tr>
                @endforelse
            </tbody>
        </x-clay.table-wrapper>

        <!-- Out of Scope -->
        <x-clay.table-wrapper title="Target Dilarang (Out-of-Scope)" subtitle="DILARANG KERAS diuji. Pelanggaran membatalkan status safe harbor.">
            <thead>
                <tr class="border-b border-slate-100 text-slate-500 font-mono text-[11px] uppercase bg-rose-50/30">
                    <th class="py-3 px-4 font-bold">Nama Target</th>
                    <th class="py-3 px-4 font-bold">Domain / URL Target</th>
                    <th class="py-3 px-4 font-bold">Catatan Batasan</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @forelse($outScopes as $scope)
                    <tr class="hover:bg-rose-50/20 transition-colors">
                        <td class="py-3 px-4 font-bold text-slate-900">{{ $scope->name }}</td>
                        <td class="py-3 px-4 font-mono text-rose-600 line-through text-xs">{{ $scope->target }}</td>
                        <td class="py-3 px-4 text-rose-600 font-semibold text-xs">{{ $scope->notes ?? 'Dilarang keras diuji.' }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="3" class="py-6 text-center text-slate-400 text-xs">Tidak ada daftar out-of-scope khusus.</td>
                    </tr>
                @endforelse
            </tbody>
        </x-clay.table-wrapper>
    </div>
</x-app-layout>
