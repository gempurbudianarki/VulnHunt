@props([
    'type' => 'info',
    'title' => null,
])

@php
    $typeClasses = match($type) {
        'success' => 'bg-emerald-50/90 border-emerald-200 text-emerald-900',
        'warning' => 'bg-amber-50/90 border-amber-200 text-amber-900',
        'danger', 'error' => 'bg-rose-50/90 border-rose-200 text-rose-900',
        'info' => 'bg-sky-50/90 border-sky-200 text-sky-900',
        default => 'bg-slate-50/90 border-slate-200 text-slate-900',
    };

    $iconClasses = match($type) {
        'success' => 'text-emerald-600',
        'warning' => 'text-amber-600',
        'danger', 'error' => 'text-rose-600',
        'info' => 'text-sky-600',
        default => 'text-slate-600',
    };
@endphp

<div {{ $attributes->merge(['class' => "p-4 rounded-clay-md border-2 shadow-sm flex items-start gap-3 {$typeClasses}"]) }} role="alert">
    <div class="shrink-0 mt-0.5 {{ $iconClasses }}">
        @if($type === 'success')
            <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
            </svg>
        @elseif($type === 'warning')
            <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
            </svg>
        @elseif($type === 'danger' || $type === 'error')
            <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z"/>
            </svg>
        @else
            <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
            </svg>
        @endif
    </div>
    <div class="flex-1 text-sm">
        @if($title)
            <h4 class="font-bold mb-1">{{ $title }}</h4>
        @endif
        <div class="leading-relaxed">
            {{ $slot }}
        </div>
    </div>
</div>
