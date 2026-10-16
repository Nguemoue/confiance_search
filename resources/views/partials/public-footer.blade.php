<footer class="mt-auto border-t border-slate-200 bg-white/60 text-slate-500 text-xs py-8 transition-colors">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 flex flex-col sm:flex-row items-center justify-between gap-4">
        <div class="flex items-center gap-3">
            <x-saasover.mark size="size-5" />
            <p>&copy; {{ date('Y') }} Entreprise Confiance &bull; NvonchiSearch &bull; Base documentaire interne.</p>
        </div>
        <div class="flex items-center gap-6">
            <a href="{{ route('home') }}" class="hover:text-[#081951] transition-colors">Recherche</a>
            <a href="{{ route('about') }}" class="hover:text-[#081951] transition-colors">À propos</a>
            <a href="{{ route('contact') }}" class="hover:text-[#081951] transition-colors">Support & Contact</a>
            <a href="/admin" class="hover:text-[#081951] transition-colors">Administration</a>
        </div>
    </div>
</footer>
