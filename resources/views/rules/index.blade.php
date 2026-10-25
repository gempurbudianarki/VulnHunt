<x-app-layout>
    <div class="max-w-4xl space-y-8">
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 pb-4 border-b border-abu-batu/15">
            <div>
                <span class="text-xs font-mono font-bold text-ambar uppercase tracking-wider">Ketentuan & Safe Harbor</span>
                <h1 class="text-2xl sm:text-3xl font-extrabold text-lumut tracking-tight mt-0.5">
                    Aturan Main (Rules of Engagement)
                </h1>
                <p class="text-xs text-abu-batu mt-1">
                    Pedoman etis dan batasan hukum pelaksanaan pengujian keamanan di lingkungan kampus UBBG.
                </p>
            </div>

            <div class="text-right">
                <span class="font-mono text-xs font-bold text-lumut bg-sage px-3 py-1.5 rounded-lg border border-abu-batu/20">
                    Versi Dokumen: v{{ $activeRulesVersion }}
                </span>
            </div>
        </div>

        <!-- Status Persetujuan Box -->
        <div class="p-5 rounded-2xl border {{ $isAccepted ? 'bg-daun/10 border-daun/30 text-daun' : 'bg-ambar/10 border-ambar/30 text-ambar-dark' }} flex items-start gap-4">
            <div class="mt-0.5 shrink-0">
                @if($isAccepted)
                    <svg class="w-6 h-6 text-daun" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                @else
                    <svg class="w-6 h-6 text-ambar" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                    </svg>
                @endif
            </div>
            <div>
                @if($isAccepted)
                    <h3 class="font-display font-bold text-sm text-lumut">
                        Anda telah menyetujui Aturan Main Versi {{ $user->rules_accepted_version }}
                    </h3>
                    <p class="text-xs text-abu-batu mt-0.5">
                        Disetujui pada: <span class="font-mono text-lumut font-semibold">{{ $user->rules_accepted_at?->format('d M Y, H:i') }} WIB</span> (Alamat IP: <span class="font-mono">{{ $user->rules_accepted_ip }}</span>).
                    </p>
                @else
                    <h3 class="font-display font-bold text-sm text-lumut">
                        Persetujuan Diperlukan untuk Melanjutkan
                    </h3>
                    <p class="text-xs text-abu-batu mt-0.5">
                        Anda belum menyetujui Aturan Main versi terbaru (v{{ $activeRulesVersion }}). Silakan baca seluruh poin di bawah ini dan centang pernyataan persetujuan di bagian akhir.
                    </p>
                @endif
            </div>
        </div>

        <!-- Isi Aturan Main (Rules of Engagement) -->
        <div class="specimen-card p-8 bg-putih-daun shadow-sm space-y-6 text-sm text-lumut leading-relaxed">
            <div>
                <h3 class="font-display font-bold text-base text-lumut mb-2">1. Prinsip Umum & Safe Harbor Kampus</h3>
                <p class="text-xs text-abu-batu">
                    Pihak kampus Universitas Bina Bangsa Getsempena memberikan perlindungan safe harbor (tidak akan menuntut secara pidana maupun perdata) kepada peserta lomba, <strong>dengan syarat mutlak</strong> bahwa peserta melakukan pengujian dengan itikad baik (good faith), hanya menguji target yang dinyatakan <em>in-scope</em>, tidak merusak sistem, dan mematuhi seluruh aturan yang tertera di dokumen ini.
                </p>
            </div>

            <div class="border-t border-abu-batu/15 pt-4">
                <h3 class="font-display font-bold text-base text-lumut mb-2">2. Larangan Mutlak (Out of Bounds)</h3>
                <ul class="text-xs text-abu-batu space-y-2 list-disc list-inside">
                    <li><strong class="text-lumut">Penolakan Layanan (DoS / DDoS):</strong> Dilarang keras melakukan pengujian stres volumetrik, flooding paket, atau tindakan apa pun yang bertujuan membuat layanan tidak dapat diakses oleh civitas akademika.</li>
                    <li><strong class="text-lumut">Rekayasa Sosial (Social Engineering):</strong> Dilarang melakukan phishing, vishing, impersonasi staf atau dosen, atau manipulasi psikologis dalam bentuk apa pun.</li>
                    <li><strong class="text-lumut">Akses atau Pengunduhan Data Pribadi:</strong> Jika Anda menemukan celah akses database (SQLi, IDOR, dsb.), Anda hanya diperbolehkan membuktikan keberadaan celah dengan data dummy atau informasi minimal (misal: versi database, username non-sensitif). <em>Dilarang keras mengekstraksi atau mengunduh data pribadi mahasiswa, dosen, atau pegawai kampus.</em></li>
                    <li><strong class="text-lumut">Target di Luar Scope:</strong> Dilarang keras menyentuh sistem pembayaran pihak ketiga (bank gateway), akun email penyedia eksternal, atau infrastruktur fisik server.</li>
                    <li><strong class="text-lumut">Pengrusakan Data (Defacement / Data Deletion):</strong> Dilarang mengubah tampilan situs atau menghapus record data produksi yang ada.</li>
                </ul>
            </div>

            <div class="border-t border-abu-batu/15 pt-4">
                <h3 class="font-display font-bold text-base text-lumut mb-2">3. Kerahasiaan & Coordinated Disclosure</h3>
                <p class="text-xs text-abu-batu">
                    Seluruh informasi kerentanan yang Anda temukan bersifat <strong>RAHASIA TINGKAT TINGGI</strong>. Peserta dilarang keras mempublikasikan temuan di media sosial, blog pribadi, repositori publik, atau membagikannya kepada pihak ketiga mana pun sebelum panitia menyatakan bahwa kerentanan telah diperbaiki dan memberikan izin tertulis untuk pengungkapan publik.
                </p>
            </div>

            <div class="border-t border-abu-batu/15 pt-4">
                <h3 class="font-display font-bold text-base text-lumut mb-2">4. Konsekuensi Pelanggaran</h3>
                <p class="text-xs text-abu-batu">
                    Pelanggaran terhadap poin-poin di atas akan mengakibatkan diskualifikasi tim secara permanen, pencabutan seluruh poin dan hadiah, gugurnya perlindungan safe harbor, serta sanksi akademik sesuai peraturan disiplin mahasiswa UBBG.
                </p>
            </div>

            <!-- Form Persetujuan -->
            <div class="border-t border-abu-batu/15 pt-6">
                <form method="POST" action="{{ route('rules.accept') }}" class="space-y-4">
                    @csrf

                    <div class="flex items-start gap-3 p-4 rounded-xl bg-sage/50 border border-abu-batu/20">
                        <input id="agree" type="checkbox" name="agree" value="1" required {{ $isAccepted ? 'checked' : '' }}
                               class="mt-1 rounded text-daun focus:ring-daun/30 border-abu-batu/30">
                        <label for="agree" class="text-xs text-lumut font-medium cursor-pointer">
                            Saya telah membaca, memahami, dan berjanji akan mematuhi seluruh ketentuan Rules of Engagement Lomba Bug Bounty UBBG Versi {{ $activeRulesVersion }}. Saya memahami bahwa segala bentuk pelanggaran membatalkan status safe harbor saya.
                        </label>
                    </div>
                    @error('agree')
                        <p class="text-xs text-severity-critical">{{ $message }}</p>
                    @enderror

                    <button type="submit" class="btn-clay-primary py-3 px-6 text-xs">
                        {{ $isAccepted ? 'Perbarui Konfirmasi Persetujuan' : 'Setujui Aturan Main & Lanjutkan' }}
                    </button>
                </form>
            </div>
        </div>
    </div>
</x-app-layout>
