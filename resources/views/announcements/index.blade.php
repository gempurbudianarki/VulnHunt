<x-app-layout>
    <div class="max-w-4xl space-y-8">
        <div>
            <span class="text-xs font-mono font-bold text-ambar uppercase tracking-wider">Pemberitahuan Panitia</span>
            <h1 class="text-2xl sm:text-3xl font-extrabold text-lumut tracking-tight mt-0.5">
                Pengumuman Resmi Lomba
            </h1>
            <p class="text-xs text-abu-batu mt-1">
                Informasi penting terkait jadwal, aturan, pembaruan scope, dan jalannya perlombaan.
            </p>
        </div>

        <div class="space-y-4">
            @forelse($announcements as $announcement)
                <div class="specimen-card p-6 bg-putih-daun shadow-sm">
                    <div class="flex items-center justify-between text-xs text-abu-batu mb-2">
                        <span class="font-mono">{{ $announcement->published_at?->format('d F Y, H:i') }} WIB</span>
                        @if($announcement->is_pinned)
                            <span class="px-2.5 py-0.5 rounded-full bg-ambar/20 text-lumut font-semibold text-[10px]">Tersemat</span>
                        @endif
                    </div>
                    <h2 class="font-display font-bold text-lg text-lumut mb-2">{{ $announcement->title }}</h2>
                    <div class="text-xs text-lumut leading-relaxed whitespace-pre-line">{{ $announcement->body }}</div>
                </div>
            @empty
                <div class="specimen-card p-12 bg-putih-daun text-center border-dashed">
                    <p class="text-sm font-semibold text-lumut">Belum Ada Pengumuman</p>
                    <p class="text-xs text-abu-batu mt-1">Belum ada pengumuman resmi yang diterbitkan panitia.</p>
                </div>
            @endforelse

            <div class="pt-4">
                {{ $announcements->links() }}
            </div>
        </div>
    </div>
</x-app-layout>
