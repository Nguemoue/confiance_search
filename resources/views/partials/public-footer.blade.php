<footer class="border-t border-zinc-200/80 dark:border-zinc-800/80 py-10 bg-white/60 dark:bg-zinc-900/60 backdrop-blur-xs">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex flex-col md:flex-row items-center justify-between gap-6">
            <!-- Brand Info -->
            <div class="flex items-center gap-3">
                <img
                    src="{{ asset('logo.jpg') }}"
                    alt="Nvonchi Search Logo"
                    class="w-9 h-9 rounded-xl object-contain shadow-2xs border border-zinc-200/60 dark:border-zinc-700/60"
                />
                <div>
                    <span class="font-bold text-zinc-900 dark:text-white text-sm">NvonchiSearch</span>
                    <p class="text-xs text-zinc-500 dark:text-zinc-400">Intranet d'entreprise &bull; Base de connaissances opérationnelles</p>
                </div>
            </div>

            <!-- Nav links in footer -->
            <div class="flex flex-wrap items-center gap-6 text-xs font-medium text-zinc-600 dark:text-zinc-400">
                <a href="{{ route('home') }}" class="hover:text-indigo-600 dark:hover:text-indigo-400 transition-colors">Accueil</a>
                <a href="{{ route('about') }}" class="hover:text-indigo-600 dark:hover:text-indigo-400 transition-colors">À propos</a>
                <a href="{{ route('contact') }}" class="hover:text-indigo-600 dark:hover:text-indigo-400 transition-colors">Contact</a>
                <a href="/admin" class="hover:text-indigo-600 dark:hover:text-indigo-400 transition-colors">Administration Filament</a>
            </div>

            <!-- Copyright -->
            <div class="text-xs text-zinc-500 dark:text-zinc-400 text-center md:text-right">
                <p>&copy; {{ date('Y') }} Nvonchi Search. Tous droits réservés.</p>
            </div>
        </div>
    </div>
</footer>
