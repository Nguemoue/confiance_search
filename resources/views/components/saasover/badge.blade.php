@props([
    'variant' => 'brand',
    'size' => 'sm',
])

@php
    $variants = [
        'brand' => 'bg-[#f1f4fa] text-[#081951] border border-[#e2e8f4]',
        'ink' => 'bg-white text-slate-700 border border-slate-200',
        'dark' => 'bg-[#081951] text-white',
        'white' => 'bg-white text-slate-900 shadow-2xs border border-slate-200',
    ];

    $sizes = [
        'xs' => 'px-2 py-0.5 text-[11px]',
        'sm' => 'px-2.5 py-0.5 text-xs',
        'md' => 'px-3 py-1 text-sm',
    ];
@endphp

<span {{ $attributes->merge(['class' => 'inline-flex items-center gap-1.5 font-medium rounded-full transition-colors ' . ($variants[$variant] ?? $variants['brand']) . ' ' . ($sizes[$size] ?? $sizes['sm'])]) }}>
    {{ $slot }}
</span>
