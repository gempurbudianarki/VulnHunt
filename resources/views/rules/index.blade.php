<x-app-layout>
    <div class="max-w-4xl space-y-8">
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 pb-4 border-b border-slate-200/80">
            <div>
                <span class="clay-badge bg-indigo-50 text-brand-primary border-indigo-200 text-[10px] uppercase font-bold tracking-wider">
                    Ketentuan & Safe Harbor
                </span>
                <h1 class="text-2xl sm:text-3xl font-extrabold text-slate-900 tracking-tight mt-1">
                    Aturan Main (Rules of Engagement)
                </h1>
                <p class="text-xs text-slate-500 mt-1">
                    Pedoman etis dan batasan hukum pelaksanaan pengujian keamanan di lingkungan kampus UBBG.
                </p>
            </div>

            <div class="text-right">
                <span class="font-mono text-xs font-bold text-slate-700 bg-white px-3.5 py-1.5 rounded-clay-pill border border-slate-200/90 shadow-sm">
                    Versi Dokumen: v{{ $activeRulesVersion }}
                </span>
            </div>
        </div>

        <!-- Status Persetujuan Box -->
        @if($isAccepted)
            <x-clay.alert type="success" title="Anda telah menyetujui Aturan Main Versi {{ $user->rules_accepted_version }}">
                Disetujui pada: <span class="font-mono font-bold">{{ $user->rules_accepted_at?->format('d M Y, H:i') }} WIB</span> (Alamat IP: <span class="font-mono">{{ $user->rules_accepted_ip }}</span>).
            </x-clay.alert>
        @else
            <x-clay.alert type="warning" title="Persetujuan Diperlukan untuk Melanjutkan">
                Anda belum menyetujui Aturan Main versi terbaru (v{{ $activeRulesVersion }}). Silakan baca seluruh poin di bawah ini dan centang pernyataan persetujuan di bagian akhir.
            </x-clay.alert>
        @endif

        <!-- Isi Aturan Main (Rules of Engagement) -->
        <div class="clay-card p-6 sm:p-8 space-y-6 text-sm text-slate-800 leading-relaxed">
            <div>
                <h3 class="font-bold text-base text-slate-900 mb-2">1. Prinsip Umum & Safe Harbor Kampus</h3>
                <p class="text-xs text-slate-600 leading-relaxed">
                    Pihak kampus Universitas Bina Bangsa Getsempena memberikan perlindungan safe harbor (tidak akan menuntut secara pidana maupun perdata) kepada peserta lomba, <strong>dengan syarat mutlak</strong> bahwa peserta melakukan pengujian dengan itikad baik (good faith), hanya menguji target yang dinyatakan <em>in-scope</em>, tidak merusak sistem, dan mematuhi seluruh aturan yang tertera di dokumen ini.
                </p>
            </div>

            <div class="border-t border-slate-100 pt-4">
                <h3 class="font-bold text-base text-slate-900 mb-2">2. Larangan Mutlak (Out of Bounds)</h3>
                <ul class="text-xs text-slate-600 space-y-2 list-disc list-inside">
                    <li><strong class="text-slate-900">Penolakan Layanan (DoS / DDoS):</strong> Dilarang keras melakukan pengujian stres volumetrik, flooding paket, atau tindakan apa pun yang bertujuan membuat layanan tidak dapat diakses oleh civitas akademika.</li>
                    <li><strong class="text-slate-900">Rekayasa Sosial (Social Engineering):</strong> Dilarang melakukan phishing, vishing, impersonasi staf atau dosen, atau manipulasi psikologis dalam bentuk apa pun.</li>
                    <li><strong class="text-slate-900">Akses atau Pengunduhan Data Pribadi:</strong> Jika Anda menemukan celah akses database (SQLi, IDOR, dsb.), Anda hanya diperbolehkan membuktikan keberadaan celah dengan data dummy atau informasi minimal (misal: versi database, username non-sensitif). <em>Dilarang keras mengekstraksi atau mengunduh data pribadi mahasiswa, dosen, atau pegawai kampus.</em></li>
                    <li><strong class="text-slate-900">Target di Luar Scope:</strong> Dilarang keras menyentuh sistem pembayaran pihak ketiga (bank gateway), akun email penyedia eksternal, atau infrastruktur fisik server.</li>
                    <li><strong class="text-slate-900">Pengrusakan Data (Defacement / Data Deletion):</strong> Dilarang mengubah tampilan situs atau menghapus record data produksi yang ada.</li>
                </ul>
            </div>

            <div class="border-t border-slate-100 pt-4">
                <h3 class="font-bold text-base text-slate-900 mb-2">3. Kerahasiaan & Coordinated Disclosure</h3>
                <p class="text-xs text-slate-600 leading-relaxed">
                    Seluruh informasi kerentanan yang Anda temukan bersifat <strong>RAHASIA TINGKAT TINGGI</strong>. Peserta dilarang keras mempublikasikan temuan di media sosial, blog pribadi, repositori publik, atau membagikannya kepada pihak ketiga mana pun sebelum panitia menyatakan bahwa kerentanan telah diperbaiki dan memberikan izin tertulis untuk pengungkapan publik.
                </p>
            </div>

            <div class="border-t border-slate-100 pt-4">
                <h3 class="font-bold text-base text-slate-900 mb-2">4. Konsekuensi Pelanggaran</h3>
                <p class="text-xs text-slate-600 leading-relaxed">
                    Pelanggaran terhadap poin-poin di atas akan mengakibatkan diskualifikasi tim secara permanen, pencabutan seluruh poin dan hadiah, gugurnya perlindungan safe harbor, serta sanksi akademik sesuai peraturan disiplin mahasiswa UBBG.
                </p>
            </div>

            <!-- Form Persetujuan -->
            <div class="border-t border-slate-100 pt-6">
                <form method="POST" action="{{ route('rules.accept') }}" class="space-y-4">
                    @csrf

                    <div class="flex items-start gap-3 p-4 rounded-clay-md bg-slate-50 border border-slate-200">
                        <input id="agree" type="checkbox" name="agree" value="1" required {{ $isAccepted ? 'checked' : '' }}
                               class="mt-1 rounded border-slate-300 text-brand-primary focus:ring-brand-primary/20">
                        <label for="agree" class="text-xs text-slate-800 font-medium cursor-pointer">
                            Saya telah membaca, memahami, dan berjanji akan mematuhi seluruh ketentuan Rules of Engagement Lomba Bug Bounty UBBG Versi {{ $activeRulesVersion }}. Saya memahami bahwa segala bentuk pelanggaran membatalkan status safe harbor saya.
                        </label>
                    </div>
                    @error('agree')
                        <p class="text-xs font-semibold text-rose-600">{{ $message }}</p>
                    @enderror

                    <button type="submit" class="btn-clay-primary py-3 px-6 text-xs">
                        {{ $isAccepted ? 'Perbarui Konfirmasi Persetujuan' : 'Setujui Aturan Main & Lanjutkan' }}
                    </button>
                </form>
            </div>
        </div>
    </div>
</x-app-layout>
