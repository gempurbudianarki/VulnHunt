<x-app-layout>
    <div class="space-y-8">
        <div>
            <span class="clay-badge bg-indigo-50 text-brand-primary border-indigo-200 text-[10px] uppercase font-bold tracking-wider">
                Manajemen Tim Ekspedisi
            </span>
            <h1 class="text-2xl sm:text-3xl font-extrabold text-slate-900 tracking-tight mt-1">
                Kelola Tim & Anggota
            </h1>
            <p class="text-xs text-slate-500 mt-1">
                Lomba bug bounty ini berbasis tim (minimal {{ $minTeamSize }} orang, maksimal {{ $maxTeamSize }} orang).
            </p>
        </div>

        @if(! $team)
            <!-- Keadaan Belum Memiliki Tim -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                <!-- 1. Buat Tim Baru -->
                <div class="clay-card p-6 sm:p-7 flex flex-col justify-between">
                    <div>
                        <div class="w-12 h-12 rounded-clay-md bg-indigo-50 border border-indigo-100 text-brand-primary flex items-center justify-center mb-4 shadow-sm">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/>
                            </svg>
                        </div>
                        <h2 class="font-bold text-lg text-slate-900">
                            Bentuk Tim Baru
                        </h2>
                        <p class="text-xs text-slate-600 mt-1 mb-5 leading-relaxed">
                            Anda akan otomatis menjadi Ketua Tim dan mendapatkan kode undangan unik untuk dibagikan ke rekan satu tim.
                        </p>

                        <form method="POST" action="{{ route('teams.store') }}" class="space-y-4">
                            @csrf
                            <div>
                                <label for="name" class="block text-xs font-bold uppercase tracking-wider text-slate-700 mb-1.5">
                                    Nama Tim <span class="text-rose-500">*</span>
                                </label>
                                <input id="name" type="text" name="name" value="{{ old('name') }}" required
                                       class="clay-input @error('name') border-rose-400 bg-rose-50/30 @enderror"
                                       placeholder="Contoh: ZeroDay Seekers">
                                @error('name')
                                    <p class="text-xs font-semibold text-rose-600 mt-1.5 flex items-center gap-1">{{ $message }}</p>
                                @enderror
                            </div>

                            <button type="submit" class="btn-clay-primary w-full py-2.5 text-xs">
                                Buat Tim Sekarang &rarr;
                            </button>
                        </form>
                    </div>
                </div>

                <!-- 2. Gabung Tim -->
                <div class="clay-card p-6 sm:p-7 flex flex-col justify-between">
                    <div>
                        <div class="w-12 h-12 rounded-clay-md bg-amber-50 border border-amber-100 text-brand-accent flex items-center justify-center mb-4 shadow-sm">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 16l-4-4m0 0l4-4m-4 4h14m-5 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h7a3 3 0 013 3v1"/>
                            </svg>
                        </div>
                        <h2 class="font-bold text-lg text-slate-900">
                            Gabung ke Tim yang Ada
                        </h2>
                        <p class="text-xs text-slate-600 mt-1 mb-5 leading-relaxed">
                            Masukkan 8 karakter kode undangan unik yang diberikan oleh ketua tim Anda.
                        </p>

                        <form method="POST" action="{{ route('teams.join') }}" class="space-y-4">
                            @csrf
                            <div>
                                <label for="invite_code" class="block text-xs font-bold uppercase tracking-wider text-slate-700 mb-1.5">
                                    Kode Undangan Tim (8 Karakter) <span class="text-rose-500">*</span>
                                </label>
                                <input id="invite_code" type="text" name="invite_code" value="{{ old('invite_code') }}" required maxlength="8"
                                       class="clay-input font-mono text-center text-lg uppercase tracking-widest font-bold @error('invite_code') border-rose-400 bg-rose-50/30 @enderror"
                                       placeholder="A1B2C3D4">
                                @error('invite_code')
                                    <p class="text-xs font-semibold text-rose-600 mt-1.5 flex items-center gap-1">{{ $message }}</p>
                                @enderror
                            </div>

                            <button type="submit" class="btn-clay-secondary w-full py-2.5 text-xs">
                                Gabung ke Tim &rarr;
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        @else
            <!-- Keadaan Sudah Memiliki Tim -->
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                <!-- Kartu Info Tim -->
                <div class="lg:col-span-1 space-y-6">
                    <div class="clay-card p-6">
                        <div class="text-[10px] text-slate-400 uppercase font-mono font-bold tracking-wider">Profil Ekspedisi</div>
                        <h2 class="font-extrabold text-xl text-slate-900 mt-1 tracking-tight">
                            {{ $team->name }}
                        </h2>

                        <div class="mt-4 pt-4 border-t border-slate-100 space-y-3 text-xs">
                            <div class="flex justify-between items-center">
                                <span class="text-slate-500">Status Tim:</span>
                                <span class="clay-badge uppercase text-[10px] {{ $team->status === 'active' ? 'bg-emerald-50 text-emerald-800 border-emerald-200' : 'bg-rose-50 text-rose-800 border-rose-200' }}">
                                    {{ $team->status }}
                                </span>
                            </div>
                            <div class="flex justify-between items-center">
                                <span class="text-slate-500">Jumlah Anggota:</span>
                                <span class="font-mono font-bold text-slate-900">{{ $team->members->count() }} / {{ $maxTeamSize }} orang</span>
                            </div>
                            <div class="flex justify-between items-center">
                                <span class="text-slate-500">Kuota Laporan:</span>
                                <span class="font-mono font-bold text-slate-900">{{ $team->remainingQuota() }} tersisa (Max {{ $team->effectiveQuota() }})</span>
                            </div>
                            <div class="flex justify-between items-center">
                                <span class="text-slate-500">Total Laporan:</span>
                                <span class="font-mono font-bold text-slate-900">{{ $team->totalReportsCount() }} laporan</span>
                            </div>
                        </div>

                        <!-- Box Kode Undangan -->
                        <div class="mt-6 p-4 rounded-clay-md bg-slate-50 border border-slate-200/80" x-data="{ copied: false }">
                            <span class="text-[10px] uppercase font-mono font-bold text-slate-400 block mb-1">Kode Undangan Tim</span>
                            <div class="flex items-center justify-between">
                                <span class="font-mono text-lg font-black tracking-widest text-brand-primary">{{ $team->invite_code }}</span>
                                <button type="button" 
                                        @click="navigator.clipboard.writeText('{{ $team->invite_code }}'); copied = true; setTimeout(() => copied = false, 2000)"
                                        class="btn-clay-secondary py-1 px-3 text-[11px]">
                                    <span x-show="!copied">Salin</span>
                                    <span x-show="copied" style="display: none;" class="text-emerald-700 font-bold">Tersalin!</span>
                                </button>
                            </div>
                            <p class="text-[10px] text-slate-500 mt-2">Bagikan kode di atas ke rekan Anda untuk bergabung ke tim ini.</p>

                            @if($user->teamMember?->isLeader())
                                <form method="POST" action="{{ route('teams.regenerate-code') }}" class="mt-3 pt-2 border-t border-slate-200">
                                    @csrf
                                    <button type="submit" class="text-[11px] text-brand-primary hover:underline font-bold" onclick="return confirm('Apakah Anda yakin ingin membuat ulang kode undangan? Kode lama tidak akan berlaku lagi.')">
                                        ↻ Buat Ulang Kode Undangan
                                    </button>
                                </form>
                            @endif
                        </div>

                        <!-- Keluar Tim -->
                        <div class="mt-6 pt-4 border-t border-slate-100">
                            @if($team->reports()->count() === 0)
                                <form method="POST" action="{{ route('teams.leave') }}">
                                    @csrf
                                    <button type="submit" class="w-full text-xs text-rose-600 hover:underline font-bold text-center" onclick="return confirm('Apakah Anda yakin ingin keluar dari tim ini?')">
                                        Keluar dari Tim
                                    </button>
                                </form>
                            @else
                                <p class="text-[11px] text-slate-400 text-center italic">
                                    Anggota tidak dapat keluar karena tim telah memiliki riwayat laporan.
                                </p>
                            @endif
                        </div>
                    </div>
                </div>

                <!-- Tabel Anggota Tim & Status Verifikasi -->
                <div class="lg:col-span-2">
                    <x-clay.table-wrapper
                        title="Daftar Anggota Tim ({{ $team->members->count() }})"
                        subtitle="Status verifikasi individual untuk setiap anggota tim."
                    >
                        <x-slot:actions>
                            @if($team->members->count() < $minTeamSize)
                                <span class="clay-badge bg-amber-50 text-amber-800 border-amber-200 text-xs">
                                    Kurang {{ $minTeamSize - $team->members->count() }} anggota (Min. {{ $minTeamSize }})
                                </span>
                            @endif
                        </x-slot:actions>

                        <thead>
                            <tr class="border-b border-slate-100 text-slate-500 font-mono text-[11px] uppercase bg-slate-50/50">
                                <th class="py-3 px-4 font-bold">Anggota</th>
                                <th class="py-3 px-3 font-bold">Peran</th>
                                <th class="py-3 px-3 font-bold">Email</th>
                                <th class="py-3 px-3 font-bold">Aturan</th>
                                <th class="py-3 px-3 font-bold">NDA</th>
                                @if($user->teamMember?->isLeader() && $team->reports()->count() === 0)
                                    <th class="py-3 px-3 font-bold text-right">Aksi</th>
                                @endif
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            @foreach($team->members as $member)
                                @php
                                    $mUser = $member->user;
                                    $mNda = $mUser->latestNdaSubmission;
                                    $mRulesOk = $mUser->rules_accepted_version === $activeRulesVersion;
                                @endphp
                                <tr class="hover:bg-slate-50/60 transition-colors">
                                    <td class="py-3 px-4">
                                        <div class="font-bold text-slate-900">{{ $mUser->name }}</div>
                                        <div class="text-[11px] text-slate-400 font-mono">{{ $mUser->nim ?? '-' }}</div>
                                    </td>
                                    <td class="py-3 px-3">
                                        <span class="clay-badge text-[10px] uppercase font-bold {{ $member->isLeader() ? 'bg-amber-50 text-amber-900 border-amber-200' : 'bg-slate-100 text-slate-700 border-slate-200' }}">
                                            {{ $member->isLeader() ? 'Ketua' : 'Anggota' }}
                                        </span>
                                    </td>
                                    <td class="py-3 px-3 text-xs">
                                        @if($mUser->hasVerifiedEmail())
                                            <span class="text-emerald-700 font-bold">✓ Terverifikasi</span>
                                        @else
                                            <span class="text-rose-600 font-bold">Belum</span>
                                        @endif
                                    </td>
                                    <td class="py-3 px-3 text-xs">
                                        @if($mRulesOk)
                                            <span class="text-emerald-700 font-bold">✓ v{{ $mUser->rules_accepted_version }}</span>
                                        @else
                                            <span class="text-amber-800 font-bold">Belum</span>
                                        @endif
                                    </td>
                                    <td class="py-3 px-3 text-xs">
                                        @if($mNda && $mNda->isApproved())
                                            <span class="text-emerald-700 font-bold">✓ Disetujui</span>
                                        @elseif($mNda && $mNda->isPending())
                                            <span class="text-amber-800 font-bold">Review</span>
                                        @elseif($mNda && $mNda->isRejected())
                                            <span class="text-rose-600 font-bold">Ditolak</span>
                                        @else
                                            <span class="text-slate-400">Belum</span>
                                        @endif
                                    </td>
                                    @if($user->teamMember?->isLeader() && $team->reports()->count() === 0)
                                        <td class="py-3 px-3 text-right">
                                            @if(! $member->isLeader())
                                                <form method="POST" action="{{ route('teams.remove-member', $mUser->id) }}">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" class="text-rose-600 hover:underline text-[11px] font-bold" onclick="return confirm('Keluarkan anggota ini dari tim?')">
                                                        Keluarkan
                                                    </button>
                                                </form>
                                            @endif
                                        </td>
                                    @endif
                                </tr>
                            @endforeach
                        </tbody>
                    </x-clay.table-wrapper>
                </div>
            </div>
        @endif
    </div>
</x-app-layout>
