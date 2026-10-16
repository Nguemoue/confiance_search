@props([
    'text' => config('saasover.brand.verified_label', 'Document certifié Confiance'),
    'compact' => false,
])

<span {{ $attributes->merge(['class' => 'inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full text-xs font-medium bg-[#f1f4fa] text-[#081951] border border-[#e2e8f4]']) }} title="{{ $text }}">
    <svg viewBox="0 0 20 20" fill="currentColor" class="size-3.5 text-[#081951] shrink-0">
        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.857-9.809a.75.75 0 00-1.214-.882l-3.483 4.79-1.88-1.88a.75.75 0 10-1.06 1.061l2.5 2.5a.75.75 0 001.137-.089l4-5.5z" clip-rule="evenodd" />
    </svg>
    @if(!$compact)
        <span class="truncate">{{ $text }}</span>
    @endif
</span>
