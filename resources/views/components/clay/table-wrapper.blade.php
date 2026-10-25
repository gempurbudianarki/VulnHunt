@props([
    'title' => null,
    'subtitle' => null,
])

<div class="clay-card-light overflow-hidden">
    @if($title || isset($actions))
        <div class="px-5 py-4 border-b border-slate-100 flex flex-col sm:flex-row sm:items-center justify-between gap-3 bg-slate-50/50">
            <div>
                @if($title)
                    <h2 class="text-sm font-bold text-slate-900 tracking-tight">{{ $title }}</h2>
                @endif
                @if($subtitle)
                    <p class="text-xs text-slate-600 mt-0.5">{{ $subtitle }}</p>
                @endif
            </div>
            @if(isset($actions))
                <div class="flex items-center gap-2">
                    {{ $actions }}
                </div>
            @endif
        </div>
    @endif

    <div class="overflow-x-auto">
        <table class="w-full text-left text-sm text-slate-700">
            {{ $slot }}
        </table>
    </div>

    @if(isset($pagination))
        <div class="px-5 py-3 border-t border-slate-100 bg-slate-50/50">
            {{ $pagination }}
        </div>
    @endif
</div>
