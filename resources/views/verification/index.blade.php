<x-app-layout>
    <div class="max-w-4xl space-y-8">
        <div>
            <span class="clay-badge bg-indigo-50 text-brand-primary border-indigo-200 text-[10px] uppercase font-bold tracking-wider">
                Perjanjian Kerahasiaan
            </span>
            <h1 class="text-2xl sm:text-3xl font-extrabold text-slate-900 tracking-tight mt-1">
                Verifikasi Surat Perjanjian (NDA)
            </h1>
            <p class="text-xs text-slate-600 mt-1">
                Sesuai aturan lomba, setiap anggota tim wajib mengunggah lembar NDA bertanda tangan untuk memperoleh perlindungan safe harbor dan membuka akses target scope.
            </p>
        </div>

        <!-- Status Kartu Terkini -->
        @if($latestNda && $latestNda->isApproved())
            <div class="clay-card p-6 border-2 border-emerald-200/80 bg-white">
                <div class="flex flex-col sm:flex-row sm:items-start justify-between gap-4">
                    <div class="flex items-start gap-4">
                        <div class="w-12 h-12 rounded-clay-md bg-emerald-100 text-emerald-700 flex items-center justify-center shrink-0 shadow-sm">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/>
                            </svg>
                        </div>
                        <div>
                            <span class="clay-badge bg-emerald-50 text-emerald-800 border-emerald-200 text-xs">
                                Status: Disetujui (Approved)
                            </span>
                            <h2 class="text-lg font-bold text-slate-900 mt-2">
                                Verifikasi NDA Anda Telah Diterima Panitia
                            </h2>
                            <p class="text-xs text-slate-600 mt-1">
                                Nomor Dokumen: <span class="font-mono font-bold text-slate-800">{{ $latestNda->document_number }}</span> &bull; Disetujui pada: <span class="font-mono">{{ $latestNda->reviewed_at?->format('d M Y, H:i') }} WIB</span>
                            </p>
                            <p class="text-xs text-emerald-700 font-semibold mt-3 flex items-center gap-1.5">
                                <svg class="w-4 h-4 shrink-0" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                                </svg>
                                Seluruh gerbang akses target scope dan pengiriman laporan telah terbuka untuk Anda.
                            </p>
                        </div>
                    </div>

                    <x-clay.button href="{{ route('verification.download', $latestNda->id) }}" variant="secondary" size="sm" class="shrink-0">
                        Unduh Berkas Saya
                    </x-clay.button>
                </div>
            </div>
        @elseif($latestNda && $latestNda->isPending())
            <div class="clay-card p-6 border-2 border-amber-200/80 bg-white">
                <div class="flex items-start gap-4">
                    <div class="w-12 h-12 rounded-clay-md bg-amber-100 text-amber-700 flex items-center justify-center shrink-0 shadow-sm">
                        <svg class="w-6 h-6 animate-pulse" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                    </div>
                    <div>
                        <span class="clay-badge bg-amber-50 text-amber-800 border-amber-200 text-xs">
                            Status: Menunggu Review Panitia
                        </span>
                        <h2 class="text-lg font-bold text-slate-900 mt-2">
                            Berkas NDA Anda Sedang Diperiksa Panitia
                        </h2>
                        <p class="text-xs text-slate-600 mt-1">
                            Nomor Dokumen: <span class="font-mono font-bold text-slate-800">{{ $latestNda->document_number }}</span> &bull; Diunggah pada: <span class="font-mono">{{ $latestNda->submitted_at?->format('d M Y, H:i') }} WIB</span>
                        </p>
                        <p class="text-xs text-slate-600 mt-2">
                            Panitia akan memeriksa kecocokan identitas, nomor dokumen, dan keabsahan tanda tangan Anda. Mohon tunggu notifikasi email.
                        </p>
                    </div>
                </div>
            </div>
        @elseif($latestNda && $latestNda->isRejected())
            <div class="clay-card p-6 border-2 border-rose-200/80 bg-white">
                <div class="flex items-start gap-4">
                    <div class="w-12 h-12 rounded-clay-md bg-rose-100 text-rose-700 flex items-center justify-center shrink-0 shadow-sm">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                        </svg>
                    </div>
                    <div>
                        <span class="clay-badge bg-rose-50 text-rose-800 border-rose-200 text-xs">
                            Status: Pengajuan Ditolak
                        </span>
                        <h2 class="text-lg font-bold text-slate-900 mt-2">
                            Berkas NDA Sebelumnya Ditolak Panitia
                        </h2>
                        <div class="mt-2.5 p-3.5 rounded-clay-md bg-rose-50 border border-rose-200 text-xs text-rose-900">
                            <strong>Alasan Penolakan:</strong> {{ $latestNda->reject_reason ?? 'Tanda tangan tidak jelas atau nomor dokumen tidak sesuai.' }}
                        </div>
                        <p class="text-xs text-slate-600 mt-3">
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
                <div class="clay-card p-6 sm:p-7 flex flex-col justify-between">
                    <div>
                        <span class="font-mono text-xs font-bold text-brand-primary bg-indigo-50 px-2.5 py-1 rounded-clay-pill border border-indigo-100 inline-block mb-3">LANGKAH 01</span>
                        <h3 class="text-lg font-bold text-slate-900 mb-2">
                            Unduh Template Resmi
                        </h3>
                        <p class="text-xs text-slate-600 leading-relaxed mb-6">
                            Sistem akan mengisikan nama, NIM, nama tim, dan nomor dokumen unik ke dalam template PDF secara otomatis dengan header resmi VulnHunt UBBG.
                        </p>
                    </div>

                    <x-clay.button href="{{ route('verification.template') }}" variant="secondary" size="md" class="w-full">
                        <svg class="w-4 h-4 text-brand-primary" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/>
                        </svg>
                        <span>Unduh Template NDA (PDF)</span>
                    </x-clay.button>
                </div>

                <!-- Tahap 2: Unggah Hasil Tanda Tangan -->
                <div class="clay-card p-6 sm:p-7 flex flex-col justify-between">
                    <div>
                        <span class="font-mono text-xs font-bold text-brand-accent bg-amber-50 px-2.5 py-1 rounded-clay-pill border border-amber-100 inline-block mb-3">LANGKAH 02</span>
                        <h3 class="text-lg font-bold text-slate-900 mb-2">
                            Unggah Hasil Tanda Tangan
                        </h3>
                        <p class="text-xs text-slate-600 leading-relaxed mb-4">
                            Cetak dan bubuhkan tanda tangan fisik basah, atau bubuhkan tanda tangan digital pada PDF template resmi di samping.
                        </p>

                        <form method="POST" action="{{ route('verification.upload') }}" enctype="multipart/form-data" class="space-y-4">
                            @csrf
                            <div>
                                <label for="signed_file" class="block text-xs font-bold uppercase tracking-wider text-slate-700 mb-1.5">
                                    Pilih Berkas (PDF, JPG, PNG - Maks. 5 MB) <span class="text-rose-500">*</span>
                                </label>
                                <input id="signed_file" type="file" name="signed_file" required accept=".pdf,.jpg,.jpeg,.png"
                                       class="clay-input text-xs file:mr-3 file:py-1.5 file:px-3 file:rounded-clay-pill file:border-0 file:text-xs file:font-semibold file:bg-brand-primary file:text-white hover:file:bg-brand-primary-hover">
                                @error('signed_file')
                                    <p class="text-xs font-semibold text-rose-600 mt-1.5 flex items-center gap-1">{{ $message }}</p>
                                @enderror
                            </div>

                            <button type="submit" class="btn-clay-primary w-full py-2.5 text-xs">
                                Unggah Berkas NDA
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        @endif

        <!-- Riwayat Pengajuan NDA Sebelumnya -->
        @if($history->isNotEmpty())
            <x-clay.table-wrapper title="Riwayat Pengajuan Dokumen NDA">
                <thead>
                    <tr class="border-b border-slate-100 text-slate-500 font-mono text-[11px] uppercase bg-slate-50/50">
                        <th class="py-3 px-4 font-bold">Nomor Dokumen</th>
                        <th class="py-3 px-4 font-bold">Waktu Kirim</th>
                        <th class="py-3 px-4 font-bold">Status</th>
                        <th class="py-3 px-4 font-bold">Catatan / Alasan</th>
                        <th class="py-3 px-4 font-bold text-right">Berkas</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @foreach($history as $item)
                        <tr class="hover:bg-slate-50/70 transition-colors">
                            <td class="py-3 px-4 font-mono font-bold text-slate-900">{{ $item->document_number }}</td>
                            <td class="py-3 px-4 font-mono text-slate-500">{{ $item->submitted_at?->format('d/m/Y H:i') }}</td>
                            <td class="py-3 px-4">
                                <span class="clay-badge text-[10px] uppercase font-bold {{ $item->status === 'approved' ? 'bg-emerald-50 text-emerald-800 border-emerald-200' : ($item->status === 'pending' ? 'bg-amber-50 text-amber-800 border-amber-200' : 'bg-rose-50 text-rose-800 border-rose-200') }}">
                                    {{ $item->status }}
                                </span>
                            </td>
                            <td class="py-3 px-4 text-slate-600 text-xs">
                                {{ $item->reject_reason ?? '-' }}
                            </td>
                            <td class="py-3 px-4 text-right">
                                <a href="{{ route('verification.download', $item->id) }}" class="text-brand-primary hover:underline font-bold text-xs">
                                    Unduh
                                </a>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </x-clay.table-wrapper>
        @endif
    </div>
</x-app-layout>
