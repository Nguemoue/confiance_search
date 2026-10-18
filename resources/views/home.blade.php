<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="h-full bg-[#f4f5f7]">
    <head>
        @include('partials.head', ['title' => 'CS Knowledge - Portail Documentaire & Base de Connaissances | Entreprise Confiance'])
    </head>
    <body class="h-full bg-[#f4f5f7] text-slate-800 antialiased selection:bg-[#081951] selection:text-white overflow-x-hidden font-sans">
        <!-- Top Confiance Accent Bar -->
        <div class="confiance-accent-bar fixed top-0 left-0 right-0 z-50"></div>

        <!-- Main Livewire Search with Left Sidebar Layout -->
        <livewire:dynamic-search :stats="$stats" />

        @fluxScripts
    </body>
</html>
