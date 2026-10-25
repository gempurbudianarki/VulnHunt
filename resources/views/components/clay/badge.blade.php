@props([
    'variant' => 'slate',
    'size' => 'md',
    'dot' => false,
])

@php
    $variantClasses = match($variant) {
        'primary' => 'bg-indigo-50 text-brand-primary border-indigo-200/80',
        'accent' => 'bg-amber-50 text-amber-800 border-amber-200/80',
        'success' => 'bg-emerald-50 text-emerald-800 border-emerald-200/80',
        'warning' => 'bg-amber-50 text-amber-800 border-amber-200/80',
        'danger', 'critical' => 'bg-rose-50 text-rose-700 border-rose-200/80',
        'high' => 'bg-orange-50 text-orange-700 border-orange-200/80',
        'medium' => 'bg-amber-50 text-amber-800 border-amber-200/80',
        'low' => 'bg-sky-50 text-sky-800 border-sky-200/80',
        'info' => 'bg-slate-100 text-slate-700 border-slate-200/80',
        default => 'bg-slate-100 text-slate-700 border-slate-200/80',
    };

    $dotColor = match($variant) {
        'primary' => 'bg-brand-primary',
        'accent' => 'bg-brand-accent',
        'success' => 'bg-emerald-500',
        'warning' => 'bg-amber-500',
        'danger', 'critical' => 'bg-rose-500',
        'high' => 'bg-orange-500',
        'medium' => 'bg-amber-500',
        'low' => 'bg-sky-500',
        'info' => 'bg-slate-400',
        default => 'bg-slate-400',
    };

    $sizeClasses = match($size) {
        'sm' => 'px-2 py-0.5 text-[11px]',
        'md' => 'px-3 py-1 text-xs',
        'lg' => 'px-3.5 py-1.5 text-sm',
        default => 'px-3 py-1 text-xs',
    };
@endphp

<span {{ $attributes->merge(['class' => "clay-badge {$variantClasses} {$sizeClasses}"]) }}>
    @if($dot)
        <span class="w-1.5 h-1.5 rounded-full {{ $dotColor }} shrink-0"></span>
    @endif
    {{ $slot }}
</span>
