<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="h-full">
    <head>
        @include('partials.head')
    </head>
    <body class="min-h-screen flex flex-col bg-zinc-50 dark:bg-zinc-950 text-zinc-900 dark:text-zinc-100 antialiased selection:bg-indigo-500 selection:text-white">
        @include('partials.public-header')

        <!-- Main Livewire Search & Dynamic FAQ Section -->
        <main class="flex-1">
            <livewire:dynamic-search :stats="$stats" />
        </main>

        @include('partials.public-footer')

        @fluxScripts
    </body>
</html>
