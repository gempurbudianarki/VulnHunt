@props([
    'title',
    'value',
    'subtitle' => null,
    'variant' => 'primary',
])

@php
    $accentBg = match($variant) {
        'primary' => 'bg-indigo-50 text-brand-primary border-indigo-100',
        'accent' => 'bg-amber-50 text-amber-700 border-amber-100',
        'success' => 'bg-emerald-50 text-emerald-700 border-emerald-100',
        'danger' => 'bg-rose-50 text-rose-700 border-rose-100',
        'slate' => 'bg-slate-50 text-slate-700 border-slate-100',
        default => 'bg-indigo-50 text-brand-primary border-indigo-100',
    };
@endphp

<div class="clay-card p-5 relative overflow-hidden group hover:shadow-clay-card-hover transition-all duration-200">
    <div class="flex items-start justify-between gap-3">
        <div>
            <p class="text-xs font-bold uppercase tracking-wider text-slate-500 mb-1">{{ $title }}</p>
            <h4 class="text-2xl sm:text-3xl font-extrabold text-slate-900 tracking-tight">{{ $value }}</h4>
            @if($subtitle)
                <p class="text-xs text-slate-500 mt-1 flex items-center gap-1">{{ $subtitle }}</p>
            @endif
        </div>
        @if(isset($icon))
            <div class="w-12 h-12 rounded-clay-md border flex items-center justify-center shrink-0 shadow-sm {{ $accentBg }}">
                {{ $icon }}
            </div>
        @endif
    </div>
</div>
