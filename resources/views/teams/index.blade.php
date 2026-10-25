<x-app-layout>
    <div class="space-y-8">
        <div>
            <span class="text-xs font-mono font-bold text-ambar uppercase tracking-wider">Manajemen Tim Ekspedisi</span>
            <h1 class="text-2xl sm:text-3xl font-extrabold text-lumut tracking-tight mt-0.5">
                Kelola Tim & Anggota
            </h1>
            <p class="text-xs text-abu-batu mt-1">
                Lomba bug bounty ini berbasis tim (minimal {{ $minTeamSize }} orang, maksimal {{ $maxTeamSize }} orang).
            </p>
        </div>

        @if(! $team)
            <!-- Keadaan Belum Memiliki Tim -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                <!-- 1. Buat Tim Baru -->
                <div class="specimen-card p-6 bg-putih-daun shadow-sm">
                    <div class="w-10 h-10 rounded-xl bg-daun/10 text-daun flex items-center justify-center mb-4">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/>
                        </svg>
                    </div>
                    <h2 class="font-display font-bold text-lg text-lumut">
                        Bentuk Tim Baru
                    </h2>
                    <p class="text-xs text-abu-batu mt-1 mb-5">
                        Anda akan otomatis menjadi Ketua Tim dan mendapatkan kode undangan unik untuk dibagikan ke anggota rekan Anda.
                    </p>

                    <form method="POST" action="{{ route('teams.store') }}" class="space-y-4">
                        @csrf
                        <div>
                            <label for="name" class="block text-xs font-semibold text-lumut mb-1">
                                Nama Tim
                            </label>
                            <input id="name" type="text" name="name" value="{{ old('name') }}" required
                                   class="w-full px-3.5 py-2.5 rounded-input bg-sage/30 border border-abu-batu/20 focus:border-daun focus:ring focus:ring-daun/20 text-sm text-lumut"
                                   placeholder="Contoh: ZeroDay Seekers">
                            @error('name')
                                <p class="text-xs text-severity-critical mt-1">{{ $message }}</p>
                            @enderror
                        </div>

                        <button type="submit" class="w-full btn-clay-primary py-2.5 text-xs">
                            Buat Tim Sekarang
                        </button>
                    </form>
                </div>

                <!-- 2. Gabung Tim -->
                <div class="specimen-card p-6 bg-putih-daun shadow-sm">
                    <div class="w-10 h-10 rounded-xl bg-ambar/10 text-ambar flex items-center justify-center mb-4">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 16l-4-4m0 0l4-4m-4 4h14m-5 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h7a3 3 0 013 3v1"/>
                        </svg>
                    </div>
                    <h2 class="font-display font-bold text-lg text-lumut">
                        Gabung ke Tim yang Ada
                    </h2>
                    <p class="text-xs text-abu-batu mt-1 mb-5">
                        Masukkan 8 karakter kode undangan yang diberikan oleh ketua tim Anda.
                    </p>

                    <form method="POST" action="{{ route('teams.join') }}" class="space-y-4">
                        @csrf
                        <div>
                            <label for="invite_code" class="block text-xs font-semibold text-lumut mb-1">
                                Kode Undangan Tim (8 Karakter)
                            </label>
                            <input id="invite_code" type="text" name="invite_code" value="{{ old('invite_code') }}" required maxlength="8"
                                   class="w-full px-3.5 py-2.5 rounded-input bg-sage/30 border border-abu-batu/20 focus:border-daun focus:ring focus:ring-daun/20 font-mono text-center text-lg uppercase tracking-widest text-lumut"
                                   placeholder="A1B2C3D4">
                            @error('invite_code')
                                <p class="text-xs text-severity-critical mt-1">{{ $message }}</p>
                            @enderror
                        </div>

                        <button type="submit" class="w-full btn-clay-secondary py-2.5 text-xs">
                            Gabung ke Tim
                        </button>
                    </form>
                </div>
            </div>
        @else
            <!-- Keadaan Sudah Memiliki Tim -->
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                <!-- Kartu Info Tim -->
                <div class="lg:col-span-1 space-y-6">
                    <div class="specimen-card p-6 bg-putih-daun shadow-sm">
                        <div class="text-[10px] text-abu-batu uppercase font-mono tracking-wider">Profil Ekspedisi</div>
                        <h2 class="font-display font-extrabold text-xl text-lumut mt-1">
                            {{ $team->name }}
                        </h2>

                        <div class="mt-4 pt-4 border-t border-abu-batu/15 space-y-3 text-xs">
                            <div class="flex justify-between items-center">
                                <span class="text-abu-batu">Status Tim:</span>
                                <span class="font-semibold uppercase px-2 py-0.5 rounded text-[11px] {{ $team->status === 'active' ? 'bg-daun/10 text-daun' : 'bg-severity-critical/10 text-severity-critical' }}">
                                    {{ $team->status }}
                                </span>
                            </div>
                            <div class="flex justify-between items-center">
                                <span class="text-abu-batu">Jumlah Anggota:</span>
                                <span class="font-mono font-bold text-lumut">{{ $team->members->count() }} / {{ $maxTeamSize }} orang</span>
                            </div>
                            <div class="flex justify-between items-center">
                                <span class="text-abu-batu">Kuota Laporan:</span>
                                <span class="font-mono font-bold text-lumut">{{ $team->remainingQuota() }} tersisa (Max {{ $team->effectiveQuota() }})</span>
                            </div>
                            <div class="flex justify-between items-center">
                                <span class="text-abu-batu">Total Laporan:</span>
                                <span class="font-mono font-bold text-lumut">{{ $team->totalReportsCount() }} laporan</span>
                            </div>
                        </div>

                        <!-- Box Kode Undangan -->
                        <div class="mt-6 p-4 rounded-xl bg-sage/50 border border-abu-batu/20" x-data="{ copied: false }">
                            <span class="text-[10px] uppercase font-mono text-abu-batu block mb-1">Kode Undangan Tim</span>
                            <div class="flex items-center justify-between">
                                <span class="font-mono text-lg font-extrabold tracking-wider text-lumut">{{ $team->invite_code }}</span>
                                <button type="button" 
                                        @click="navigator.clipboard.writeText('{{ $team->invite_code }}'); copied = true; setTimeout(() => copied = false, 2000)"
                                        class="btn-clay-secondary py-1 px-2.5 text-[11px]">
                                    <span x-show="!copied">Salin</span>
                                    <span x-show="copied" style="display: none;" class="text-daun font-bold">Tersalin!</span>
                                </button>
                            </div>
                            <p class="text-[10px] text-abu-batu mt-2">Bagikan kode di atas ke rekan Anda untuk bergabung ke tim ini.</p>

                            @if($user->teamMember?->isLeader())
                                <form method="POST" action="{{ route('teams.regenerate-code') }}" class="mt-3 pt-2 border-t border-abu-batu/15">
                                    @csrf
                                    <button type="submit" class="text-[11px] text-daun hover:underline font-medium" onclick="return confirm('Apakah Anda yakin ingin membuat ulang kode undangan? Kode lama tidak akan berlaku lagi.')">
                                        ↻ Buat Ulang Kode Undangan
                                    </button>
                                </form>
                            @endif
                        </div>

                        <!-- Keluar Tim -->
                        <div class="mt-6 pt-4 border-t border-abu-batu/15">
                            @if($team->reports()->count() === 0)
                                <form method="POST" action="{{ route('teams.leave') }}">
                                    @csrf
                                    <button type="submit" class="w-full text-xs text-severity-critical hover:underline font-medium text-center" onclick="return confirm('Apakah Anda yakin ingin keluar dari tim ini?')">
                                        Keluar dari Tim
                                    </button>
                                </form>
                            @else
                                <p class="text-[11px] text-abu-batu text-center italic">
                                    Anggota tidak dapat keluar karena tim telah memiliki riwayat laporan.
                                </p>
                            @endif
                        </div>
                    </div>
                </div>

                <!-- Tabel Anggota Tim & Status Verifikasi -->
                <div class="lg:col-span-2">
                    <div class="specimen-card p-6 bg-putih-daun shadow-sm">
                        <div class="flex items-center justify-between mb-4">
                            <h3 class="font-display font-bold text-base text-lumut">
                                Daftar Anggota Tim ({{ $team->members->count() }})
                            </h3>
                            @if($team->members->count() < $minTeamSize)
                                <span class="text-xs text-ambar font-semibold flex items-center gap-1">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                                    </svg>
                                    Kurang {{ $minTeamSize - $team->members->count() }} anggota lagi (Min. {{ $minTeamSize }})
                                </span>
                            @endif
                        </div>

                        <div class="overflow-x-auto">
                            <table class="w-full text-left text-xs">
                                <thead>
                                    <tr class="border-b border-abu-batu/20 text-abu-batu font-mono text-[11px] uppercase">
                                        <th class="py-2.5 px-3">Anggota</th>
                                        <th class="py-2.5 px-2">Peran</th>
                                        <th class="py-2.5 px-2">Email</th>
                                        <th class="py-2.5 px-2">Aturan</th>
                                        <th class="py-2.5 px-2">NDA</th>
                                        @if($user->teamMember?->isLeader() && $team->reports()->count() === 0)
                                            <th class="py-2.5 px-2 text-right">Aksi</th>
                                        @endif
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-abu-batu/10">
                                    @foreach($team->members as $member)
                                        @php
                                            $mUser = $member->user;
                                            $mNda = $mUser->latestNdaSubmission;
                                            $mRulesOk = $mUser->rules_accepted_version === $activeRulesVersion;
                                        @endphp
                                        <tr>
                                            <td class="py-3 px-3">
                                                <div class="font-semibold text-lumut">{{ $mUser->name }}</div>
                                                <div class="text-[11px] text-abu-batu font-mono">{{ $mUser->nim ?? '-' }}</div>
                                            </td>
                                            <td class="py-3 px-2">
                                                <span class="px-2 py-0.5 rounded text-[10px] font-semibold uppercase {{ $member->isLeader() ? 'bg-ambar/15 text-ambar-dark' : 'bg-sage text-lumut' }}">
                                                    {{ $member->isLeader() ? 'Ketua' : 'Anggota' }}
                                                </span>
                                            </td>
                                            <td class="py-3 px-2">
                                                @if($mUser->hasVerifiedEmail())
                                                    <span class="text-daun font-semibold">✓ Terverifikasi</span>
                                                @else
                                                    <span class="text-severity-critical">Belum</span>
                                                @endif
                                            </td>
                                            <td class="py-3 px-2">
                                                @if($mRulesOk)
                                                    <span class="text-daun font-semibold">✓ v{{ $mUser->rules_accepted_version }}</span>
                                                @else
                                                    <span class="text-ambar">Belum</span>
                                                @endif
                                            </td>
                                            <td class="py-3 px-2">
                                                @if($mNda && $mNda->isApproved())
                                                    <span class="text-daun font-semibold">✓ Disetujui</span>
                                                @elseif($mNda && $mNda->isPending())
                                                    <span class="text-ambar font-medium">Review</span>
                                                @elseif($mNda && $mNda->isRejected())
                                                    <span class="text-severity-critical font-medium">Ditolak</span>
                                                @else
                                                    <span class="text-abu-batu">Belum</span>
                                                @endif
                                            </td>
                                            @if($user->teamMember?->isLeader() && $team->reports()->count() === 0)
                                                <td class="py-3 px-2 text-right">
                                                    @if(! $member->isLeader())
                                                        <form method="POST" action="{{ route('teams.remove-member', $mUser->id) }}">
                                                            @csrf
                                                            @method('DELETE')
                                                            <button type="submit" class="text-severity-critical hover:underline text-[11px]" onclick="return confirm('Keluarkan anggota ini dari tim?')">
                                                                Keluarkan
                                                            </button>
                                                        </form>
                                                    @endif
                                                </td>
                                            @endif
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        @endif
    </div>
</x-app-layout>
