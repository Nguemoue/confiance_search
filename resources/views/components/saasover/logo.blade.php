@props([
    'size' => 'md',
    'showSubname' => true,
    'href' => route('home'),
])

@php
    $markSizes = [
        'sm' => 'size-7',
        'md' => 'size-9',
        'lg' => 'size-11',
    ];
    $titleSizes = [
        'sm' => 'text-base',
        'md' => 'text-lg',
        'lg' => 'text-xl',
    ];
@endphp

<a href="{{ $href }}" {{ $attributes->merge(['class' => 'inline-flex items-center gap-3 group focus:outline-hidden']) }}>
    <x-saasover.mark :size="$markSizes[$size] ?? 'size-9'" class="group-hover:scale-105 transition-transform duration-200" />
    <div class="flex flex-col">
        <div class="flex items-center gap-2">
            <span class="font-black tracking-tight text-[#081951] font-display {{ $titleSizes[$size] ?? 'text-lg' }} leading-none">
                Confiance
            </span>
            <span class="inline-flex items-center px-1.5 py-0.5 rounded text-[10px] font-semibold tracking-wide uppercase bg-slate-100 text-slate-700 border border-slate-200">
                Portail Interne
            </span>
        </div>
        @if($showSubname)
            <span class="text-[11px] font-medium text-slate-500 group-hover:text-slate-700 transition-colors tracking-tight leading-tight mt-0.5">
                NvonchiSearch &bull; Recherche documentaire
            </span>
        @endif
    </div>
</a>
