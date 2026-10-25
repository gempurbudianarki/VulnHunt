<x-app-layout>
    <div class="max-w-4xl space-y-8">
        <div>
            <span class="clay-badge bg-indigo-50 text-brand-primary border-indigo-200 text-[10px] uppercase font-bold tracking-wider">
                Pemberitahuan Panitia
            </span>
            <h1 class="text-2xl sm:text-3xl font-extrabold text-slate-900 tracking-tight mt-1">
                Pengumuman Resmi Lomba
            </h1>
            <p class="text-xs text-slate-500 mt-1">
                Informasi penting terkait jadwal, aturan, pembaruan scope, dan jalannya perlombaan.
            </p>
        </div>

        <div class="space-y-4">
            @forelse($announcements as $announcement)
                <div class="clay-card p-6">
                    <div class="flex items-center justify-between text-xs text-slate-500 mb-2">
                        <span class="font-mono">{{ $announcement->published_at?->format('d F Y, H:i') }} WIB</span>
                        @if($announcement->is_pinned)
                            <span class="clay-badge bg-amber-50 text-amber-900 border-amber-200 text-[10px] font-bold">Tersemat</span>
                        @endif
                    </div>
                    <h2 class="font-bold text-lg text-slate-900 mb-2 tracking-tight">{{ $announcement->title }}</h2>
                    <div class="text-xs text-slate-700 leading-relaxed whitespace-pre-line">{{ $announcement->body }}</div>
                </div>
            @empty
                <x-clay.empty-state
                    title="Belum Ada Pengumuman"
                    description="Belum ada pengumuman resmi yang diterbitkan panitia saat ini."
                />
            @endforelse

            <div class="pt-4">
                {{ $announcements->links() }}
            </div>
        </div>
    </div>
</x-app-layout>
