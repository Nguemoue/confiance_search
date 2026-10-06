<header class="sticky top-0 z-40 w-full backdrop-blur-md bg-white/85 dark:bg-zinc-900/85 border-b border-zinc-200/80 dark:border-zinc-800/80 transition-colors">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 h-16 flex items-center justify-between">
        <!-- Brand / Logo -->
        <a href="{{ route('home') }}" class="flex items-center gap-3 group focus:outline-hidden focus-visible:ring-2 focus-visible:ring-indigo-500 rounded-xl p-1">
            <img
                src="{{ asset('logo.jpg') }}"
                alt="Nvonchi Search"
                class="w-10 h-10 rounded-xl object-contain shadow-sm border border-zinc-200/60 dark:border-zinc-700/60 group-hover:scale-105 transition-transform duration-200"
            />
            <div class="flex flex-col">
                <span class="text-lg font-black tracking-tight text-zinc-900 dark:text-white leading-tight">
                    {{config('app.name', 'Confiance Search')}}
                </span>
                <span class="text-[10px] font-bold text-zinc-400 dark:text-zinc-500 uppercase tracking-widest leading-none">
                    Collaboration document
                </span>
            </div>
        </a>

        <!-- Center Nav Links -->
        <nav class="hidden md:flex items-center gap-1 text-sm font-medium">
            <a
                href="{{ route('home') }}"
                class="px-3.5 py-1.5 rounded-lg transition-colors {{ request()->routeIs('home') ? 'bg-indigo-50 dark:bg-indigo-950/60 text-indigo-600 dark:text-indigo-400 font-semibold' : 'text-zinc-600 dark:text-zinc-400 hover:text-zinc-900 dark:hover:text-white hover:bg-zinc-100 dark:hover:bg-zinc-800' }}"
            >
                Procédures & Recherche
            </a>
            <a
                href="{{ route('about') }}"
                class="px-3.5 py-1.5 rounded-lg transition-colors {{ request()->routeIs('about') ? 'bg-indigo-50 dark:bg-indigo-950/60 text-indigo-600 dark:text-indigo-400 font-semibold' : 'text-zinc-600 dark:text-zinc-400 hover:text-zinc-900 dark:hover:text-white hover:bg-zinc-100 dark:hover:bg-zinc-800' }}"
            >
                À propos du portail
            </a>

        </nav>

        <!-- Right Menu: Admin Panel & Auth Links -->
        <div class="flex items-center gap-2.5">
            <a
                href="/admin"
                class="inline-flex items-center gap-2 px-3.5 py-1.5 rounded-xl text-xs font-semibold bg-zinc-100/90 hover:bg-zinc-200/90 dark:bg-zinc-800/90 dark:hover:bg-zinc-700/90 text-zinc-800 dark:text-zinc-200 border border-zinc-200/80 dark:border-zinc-700/80 transition-all duration-150 shadow-2xs hover:shadow-xs focus:outline-hidden focus-visible:ring-2 focus-visible:ring-indigo-500"
                title="Accéder au panneau d'administration Filament"
            >
                <svg class="w-4 h-4 text-indigo-600 dark:text-indigo-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z" />
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                </svg>
                <span>Administration</span>
            </a>

        </div>
    </div>
</header>
