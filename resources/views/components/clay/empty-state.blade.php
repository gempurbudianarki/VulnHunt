@props([
    'title' => 'Belum Ada Data',
    'description' => null,
])

<div class="clay-card-light py-12 px-6 text-center">
    <div class="w-16 h-16 mx-auto mb-4 rounded-full bg-slate-100 border border-slate-200/80 flex items-center justify-center text-slate-400">
        @if(isset($icon))
            {{ $icon }}
        @else
            <svg class="w-8 h-8" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4"/>
            </svg>
        @endif
    </div>
    <h4 class="text-base font-bold text-slate-800">{{ $title }}</h4>
    @if($description)
        <p class="text-xs text-slate-500 max-w-sm mx-auto mt-1 leading-relaxed">{{ $description }}</p>
    @endif
    @if(isset($action))
        <div class="mt-5">
            {{ $action }}
        </div>
    @endif
</div>
