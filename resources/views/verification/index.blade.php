<x-app-layout>
    <div class="max-w-4xl space-y-8">
        <div>
            <span class="text-xs font-mono font-bold text-ambar uppercase tracking-wider">Perjanjian Kerahasiaan</span>
            <h1 class="text-2xl sm:text-3xl font-extrabold text-lumut tracking-tight mt-0.5">
                Verifikasi Surat Perjanjian (NDA)
            </h1>
            <p class="text-xs text-abu-batu mt-1">
                Sesuai aturan lomba, setiap anggota tim wajib mengunggah lembar NDA bertanda tangan untuk memperoleh perlindungan safe harbor dan membuka akses target scope.
            </p>
        </div>

        <!-- Status Kartu Terkini -->
        @if($latestNda && $latestNda->isApproved())
            <div class="specimen-card p-6 bg-putih-daun border-daun/40 shadow-sm">
                <div class="flex items-start justify-between">
                    <div class="flex items-start gap-3">
                        <div class="w-10 h-10 rounded-xl bg-daun/10 text-daun flex items-center justify-center shrink-0">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                        </div>
                        <div>
                            <span class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full text-[10px] font-bold uppercase bg-daun/15 text-daun">
                                Status: Disetujui (Approved)
                            </span>
                            <h3 class="font-display font-bold text-lg text-lumut mt-1">
                                Verifikasi NDA Anda Telah Diterima Panitia
                            </h3>
                            <p class="text-xs text-abu-batu mt-0.5">
                                Nomor Dokumen: <span class="font-mono font-bold text-lumut">{{ $latestNda->document_number }}</span> | Disetujui pada: <span class="font-mono">{{ $latestNda->reviewed_at?->format('d M Y, H:i') }} WIB</span>
                            </p>
                            <p class="text-xs text-daun font-medium mt-2">
                                ✓ Seluruh gerbang akses target scope dan pengiriman laporan telah terbuka untuk Anda.
                            </p>
                        </div>
                    </div>

                    <a href="{{ route('verification.download', $latestNda->id) }}" class="btn-clay-secondary text-xs py-2 px-3 shrink-0">
                        Unduh Berkas Saya
                    </a>
                </div>
            </div>
        @elseif($latestNda && $latestNda->isPending())
            <div class="specimen-card p-6 bg-putih-daun border-ambar/40 shadow-sm">
                <div class="flex items-start gap-3">
                    <div class="w-10 h-10 rounded-xl bg-ambar/10 text-ambar flex items-center justify-center shrink-0">
                        <svg class="w-6 h-6 animate-pulse" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                    </div>
                    <div>
                        <span class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full text-[10px] font-bold uppercase bg-ambar/20 text-ambar-dark">
                            Status: Menunggu Review Panitia
                        </span>
                        <h3 class="font-display font-bold text-base text-lumut mt-1">
                            Berkas NDA Anda Sedang Diperiksa Panitia
                        </h3>
                        <p class="text-xs text-abu-batu mt-0.5">
                            Nomor Dokumen: <span class="font-mono font-bold text-lumut">{{ $latestNda->document_number }}</span> | Diunggah pada: <span class="font-mono">{{ $latestNda->submitted_at?->format('d M Y, H:i') }} WIB</span>
                        </p>
                        <p class="text-xs text-abu-batu mt-2">
                            Panitia akan memeriksa kecocokan identitas, nomor dokumen, dan keabsahan tanda tangan Anda. Mohon tunggu notifikasi email.
                        </p>
                    </div>
                </div>
            </div>
        @elseif($latestNda && $latestNda->isRejected())
            <div class="specimen-card p-6 bg-putih-daun border-severity-critical/40 shadow-sm">
                <div class="flex items-start gap-3">
                    <div class="w-10 h-10 rounded-xl bg-severity-critical/10 text-severity-critical flex items-center justify-center shrink-0">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                        </svg>
                    </div>
                    <div>
                        <span class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full text-[10px] font-bold uppercase bg-severity-critical/20 text-severity-critical">
                            Status: Pengajuan Ditolak
                        </span>
                        <h3 class="font-display font-bold text-base text-lumut mt-1">
                            Berkas NDA Sebelumnya Ditolak Panitia
                        </h3>
                        <div class="mt-2 p-3 rounded-xl bg-severity-critical/5 border border-severity-critical/20 text-xs text-severity-critical">
                            <strong>Alasan Penolakan:</strong> {{ $latestNda->reject_reason ?? 'Tanda tangan tidak jelas atau nomor dokumen tidak sesuai.' }}
                        </div>
                        <p class="text-xs text-abu-batu mt-2">
                            Silakan unduh template baru di bawah, perbaiki tanda tangan Anda, lalu unggah kembali.
                        </p>
                    </div>
                </div>
            </div>
        @endif

        <!-- Formulir Unduh Template & Unggah Hasil (Hanya jika belum approved) -->
        @if(! $latestNda || ! $latestNda->isApproved())
            <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                <!-- Tahap 1: Unduh Template -->
                <div class="specimen-card p-6 bg-putih-daun shadow-sm">
                    <span class="font-mono text-xs font-bold text-daun">LANGKAH 01</span>
                    <h3 class="font-display font-bold text-lg text-lumut mt-1 mb-2">
                        Unduh Template Resmi
                    </h3>
                    <p class="text-xs text-abu-batu leading-relaxed mb-6">
                        Sistem akan mengisikan nama, NIM, nama tim, dan nomor dokumen unik ke dalam template PDF secara otomatis.
                    </p>

                    <a href="{{ route('verification.template') }}" class="w-full btn-clay-secondary py-3 text-xs flex items-center justify-center gap-2">
                        <svg class="w-4 h-4 text-daun" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/>
                        </svg>
                        <span>Unduh Template NDA (PDF)</span>
                    </a>
                </div>

                <!-- Tahap 2: Unggah Hasil Tanda Tangan -->
                <div class="specimen-card p-6 bg-putih-daun shadow-sm">
                    <span class="font-mono text-xs font-bold text-daun">LANGKAH 02</span>
                    <h3 class="font-display font-bold text-lg text-lumut mt-1 mb-2">
                        Unggah Hasil Pindai / Foto
                    </h3>
                    <p class="text-xs text-abu-batu leading-relaxed mb-4">
                        Cetak dan bubuhkan tanda tangan fisik, atau bubuhkan tanda tangan digital pada PDF template di atas.
                    </p>

                    <form method="POST" action="{{ route('verification.upload') }}" enctype="multipart/form-data" class="space-y-4">
                        @csrf
                        <div>
                            <label for="signed_file" class="block text-xs font-semibold text-lumut mb-1">
                                Pilih Berkas (PDF, JPG, PNG - Maks. 5 MB)
                            </label>
                            <input id="signed_file" type="file" name="signed_file" required accept=".pdf,.jpg,.jpeg,.png"
                                   class="w-full text-xs text-lumut file:mr-3 file:py-2 file:px-3 file:rounded-xl file:border-0 file:text-xs file:font-semibold file:bg-daun/10 file:text-daun hover:file:bg-daun/20 border border-abu-batu/20 rounded-xl p-2 bg-sage/30">
                            @error('signed_file')
                                <p class="text-xs text-severity-critical mt-1">{{ $message }}</p>
                            @enderror
                        </div>

                        <button type="submit" class="w-full btn-clay-primary py-2.5 text-xs">
                            Unggah Berkas NDA
                        </button>
                    </form>
                </div>
            </div>
        @endif

        <!-- Riwayat Pengajuan NDA Sebelumnya -->
        @if($history->isNotEmpty())
            <div class="specimen-card p-6 bg-putih-daun shadow-sm">
                <h3 class="font-display font-bold text-base text-lumut mb-3">
                    Riwayat Pengajuan Dokumen NDA
                </h3>
                <div class="overflow-x-auto">
                    <table class="w-full text-left text-xs">
                        <thead>
                            <tr class="border-b border-abu-batu/20 text-abu-batu font-mono text-[11px] uppercase">
                                <th class="py-2 px-3">Nomor Dokumen</th>
                                <th class="py-2 px-3">Waktu Kirim</th>
                                <th class="py-2 px-3">Status</th>
                                <th class="py-2 px-3">Catatan / Alasan</th>
                                <th class="py-2 px-3 text-right">Berkas</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-abu-batu/10">
                            @foreach($history as $item)
                                <tr>
                                    <td class="py-2.5 px-3 font-mono font-semibold text-lumut">{{ $item->document_number }}</td>
                                    <td class="py-2.5 px-3 font-mono text-abu-batu">{{ $item->submitted_at?->format('d/m/Y H:i') }}</td>
                                    <td class="py-2.5 px-3">
                                        <span class="px-2 py-0.5 rounded text-[10px] font-bold uppercase {{ $item->status === 'approved' ? 'bg-daun/15 text-daun' : ($item->status === 'pending' ? 'bg-ambar/15 text-ambar-dark' : 'bg-severity-critical/15 text-severity-critical') }}">
                                            {{ $item->status }}
                                        </span>
                                    </td>
                                    <td class="py-2.5 px-3 text-abu-batu">
                                        {{ $item->reject_reason ?? '-' }}
                                    </td>
                                    <td class="py-2.5 px-3 text-right">
                                        <a href="{{ route('verification.download', $item->id) }}" class="text-daun hover:underline font-medium">
                                            Unduh
                                        </a>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        @endif
    </div>
</x-app-layout>
