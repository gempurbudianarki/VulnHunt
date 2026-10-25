@props([
    'label' => null,
    'error' => null,
    'hint' => null,
    'id' => null,
    'rows' => 4,
])

@php
    $id = $id ?? $attributes->get('name') ?? 'textarea-' . md5(uniqid(mt_rand(), true));
    $errorClass = $error ? 'border-rose-400 focus:border-rose-500 focus:ring-rose-400/20 bg-rose-50/30' : 'border-slate-200/90 focus:border-brand-primary focus:ring-brand-primary/15 bg-slate-50/80';
@endphp

<div class="w-full">
    @if($label)
        <label for="{{ $id }}" class="block text-xs font-bold uppercase tracking-wider text-slate-700 mb-1.5">
            {{ $label }}
            @if($attributes->has('required'))
                <span class="text-rose-500">*</span>
            @endif
        </label>
    @endif

    <div class="relative">
        <textarea
            id="{{ $id }}"
            rows="{{ $rows }}"
            {{ $attributes->merge([
                'class' => "w-full text-slate-900 placeholder:text-slate-400 font-medium rounded-input border-2 px-4 py-3 shadow-clay-input transition-all duration-150 focus:bg-white focus:outline-none focus:ring-4 {$errorClass}"
            ]) }}
        >{{ $slot }}</textarea>
    </div>

    @if($hint && !$error)
        <p class="mt-1 text-xs text-slate-500">{{ $hint }}</p>
    @endif

    @if($error)
        <p class="mt-1.5 text-xs font-semibold text-rose-600 flex items-center gap-1">
            <svg class="w-3.5 h-3.5 inline-block shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
            </svg>
            {{ $error }}
        </p>
    @endif
</div>
