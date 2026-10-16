@props([
    'size' => 'size-8',
])

<div {{ $attributes->merge(['class' => "relative inline-flex items-center justify-center shrink-0 {$size}"]) }}>
    <img
        src="{{ asset('images/logo-confiance.png') }}"
        alt="Logo Confiance"
        class="w-full h-full object-contain"
    />
</div>
