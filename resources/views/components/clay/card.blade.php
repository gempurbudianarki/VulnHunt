@props([
    'title' => null,
    'subtitle' => null,
    'interactive' => false,
    'light' => false,
])

@php
    $baseCard = $light ? 'clay-card-light p-5' : ($interactive ? 'clay-card-interactive p-6' : 'clay-card p-6');
@endphp

<div {{ $attributes->merge(['class' => $baseCard]) }}>
    @if($title || isset($header))
        <div class="mb-4 pb-3 border-b border-slate-100 flex items-center justify-between gap-4">
            <div>
                @if($title)
                    <h3 class="text-base font-bold text-slate-900 tracking-tight">{{ $title }}</h3>
                @endif
                @if($subtitle)
                    <p class="text-xs text-slate-500 mt-0.5">{{ $subtitle }}</p>
                @endif
            </div>
            @if(isset($header))
                <div class="shrink-0">
                    {{ $header }}
                </div>
            @endif
        </div>
    @endif

    {{ $slot }}

    @if(isset($footer))
        <div class="mt-4 pt-3 border-t border-slate-100 flex items-center justify-between text-xs text-slate-500">
            {{ $footer }}
        </div>
    @endif
</div>
