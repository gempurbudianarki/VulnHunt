@props([
    'variant' => 'primary',
    'size' => 'md',
    'type' => 'button',
    'href' => null,
])

@php
    $baseClasses = 'inline-flex items-center justify-center gap-2 font-semibold transition-all duration-150 focus:outline-none focus:ring-4 disabled:opacity-50 disabled:cursor-not-allowed disabled:pointer-events-none cursor-pointer';

    $variantClasses = match($variant) {
        'primary' => 'bg-brand-primary hover:bg-brand-primary-hover text-white rounded-clay-pill shadow-clay-btn-primary hover:shadow-clay-btn-primary-hover active:shadow-clay-btn-primary-active active:translate-y-0.5 focus:ring-brand-primary/25 focus:ring-offset-2',
        'accent' => 'bg-brand-accent hover:bg-brand-accent-hover text-slate-950 font-black rounded-clay-pill shadow-clay-btn-accent hover:shadow-clay-btn-accent-hover active:shadow-clay-btn-accent-active active:translate-y-0.5 focus:ring-brand-accent/35 focus:ring-offset-2',
        'secondary' => 'bg-white hover:bg-slate-50 text-slate-800 rounded-clay-pill border border-slate-200/90 shadow-clay-btn-secondary hover:shadow-clay-btn-secondary-hover active:shadow-clay-btn-secondary-active active:translate-y-0.5 focus:ring-slate-400/25 focus:ring-offset-2',
        'danger' => 'bg-rose-600 hover:bg-rose-700 text-white rounded-clay-pill shadow-sm hover:shadow active:translate-y-0.5 focus:ring-rose-500/25 focus:ring-offset-2',
        'ghost' => 'bg-transparent hover:bg-slate-200/50 text-slate-700 rounded-clay-pill focus:ring-slate-300/30',
        default => 'bg-brand-primary text-white rounded-clay-pill shadow-clay-btn-primary',
    };

    $sizeClasses = match($size) {
        'sm' => 'px-3.5 py-1.5 text-xs',
        'md' => 'px-5 py-2.5 text-sm',
        'lg' => 'px-7 py-3.5 text-base',
        default => 'px-5 py-2.5 text-sm',
    };

    $classes = "{$baseClasses} {$variantClasses} {$sizeClasses}";
@endphp

@if($href)
    <a href="{{ $href }}" {{ $attributes->merge(['class' => $classes]) }}>
        {{ $slot }}
    </a>
@else
    <button type="{{ $type }}" {{ $attributes->merge(['class' => $classes]) }}>
        {{ $slot }}
    </button>
@endif
