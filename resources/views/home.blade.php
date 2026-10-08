<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="dark h-full bg-zinc-950">
    <head>
        @include('partials.head', ['title' => 'Portail Procédures & Démarches - Nvonchi Search'])
    </head>
    <body class="h-full bg-zinc-950 text-zinc-100 antialiased selection:bg-indigo-500 selection:text-white overflow-x-hidden font-sans">
        <!-- Main Livewire Search with Left Sidebar Layout -->
        <livewire:dynamic-search :stats="$stats" />

        @fluxScripts
    </body>
</html>
