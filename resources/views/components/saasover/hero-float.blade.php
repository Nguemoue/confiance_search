@props([
    'icon' => null,
    'title' => '',
    'subtitle' => null,
])

<div {{ $attributes->merge(['class' => 'inline-flex items-center gap-3 px-3.5 py-2 rounded-xl bg-white border border-slate-200 shadow-xs text-left']) }}>
    @if($icon)
        <div class="size-8 rounded-lg bg-[#f1f4fa] text-[#081951] flex items-center justify-center shrink-0 border border-[#e2e8f4]">
            {!! $icon !!}
        </div>
    @else
        <div class="size-2 rounded-full bg-[#081951] shrink-0"></div>
    @endif
    <div>
        <div class="text-xs font-bold text-slate-900 leading-tight font-display">{{ $title }}</div>
        @if($subtitle)
            <div class="text-[11px] text-slate-500 leading-tight mt-0.5">{{ $subtitle }}</div>
        @endif
    </div>
</div>
