<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="h-full bg-[#f4f5f7]">
    <head>
        @include('partials.head', ['title' => 'À propos du Portail Documentaire - Entreprise Confiance'])
    </head>
    <body class="min-h-screen flex flex-col bg-[#f4f5f7] text-slate-800 antialiased selection:bg-[#081951] selection:text-white font-sans">
        <!-- Top Confiance Accent Bar -->
        <div class="confiance-accent-bar fixed top-0 left-0 right-0 z-50"></div>

        @include('partials.public-header')

        <main class="flex-1 py-12 md:py-20">
            <div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8">
                <!-- Hero Header -->
                <div class="text-center max-w-3xl mx-auto mb-16">
                    <div class="inline-flex items-center gap-2 px-3.5 py-1 rounded-full bg-[#f1f4fa] border border-[#e2e8f4] text-[#081951] text-xs font-semibold mb-4">
                        <x-saasover.mark size="size-4" />
                        <span>Portail Documentaire &bull; Entreprise Confiance</span>
                    </div>
                    <h1 class="text-3xl sm:text-4xl md:text-5xl font-black text-slate-900 tracking-tight leading-tight font-display">
                        La documentation interne de l'entreprise, <br class="hidden sm:inline" />
                        <span class="text-[#081951]">claire, instantanée et à portée de main</span>
                    </h1>
                    <p class="mt-5 text-base sm:text-lg text-slate-600 leading-relaxed font-sans">
                        Confiance (NvonchiSearch) est le portail documentaire et le moteur de recherche interne dédié aux collaborateurs de l'entreprise Confiance. Il centralise l'ensemble des procédures opérationnelles, démarches RH, guides de sécurité IT, notes de service et formulaires d'entreprise en un point d'accès unique et épuré.
                    </p>
                </div>

                <!-- Brand Card -->
                <div class="bg-white rounded-2xl border border-slate-200 p-8 sm:p-12 shadow-xs mb-12">
                    <div class="grid grid-cols-1 md:grid-cols-12 gap-8 items-center">
                        <div class="md:col-span-4 flex justify-center">
                            <x-saasover.mark size="size-36" />
                        </div>
                        <div class="md:col-span-8 space-y-4">
                            <div class="inline-flex items-center gap-2">
                                <x-saasover.verified-badge text="Référentiel documentaire validé" />
                            </div>
                            <h2 class="text-2xl font-bold text-slate-900 font-display">
                                Une réponse immédiate pour chaque démarche métier
                            </h2>
                            <p class="text-slate-600 leading-relaxed font-sans">
                                Finies les pertes de temps à rechercher des documents disséminés dans des boîtes mails ou sur des disques partagés. Ce portail indexe en continu les modes opératoires et documents validés par chaque direction de l'entreprise Confiance (RH, DSI, Finance, Opérations).
                            </p>
                            <p class="text-slate-600 leading-relaxed font-sans">
                                Chaque fiche pratique intègre la démarche pas-à-pas, le lien direct vers l'application métier concernée (SIRH, Helpdesk, Achats), le document modèle téléchargeable ainsi que le contact direct du référent interne.
                            </p>
                        </div>
                    </div>
                </div>

                <!-- 3 Pillars Grid -->
                <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-12">
                    <div class="bg-white rounded-2xl border border-slate-200 p-6 shadow-xs hover:border-slate-300 transition-colors">
                        <div class="w-11 h-11 rounded-xl bg-[#f1f4fa] text-[#081951] flex items-center justify-center mb-4 border border-[#e2e8f4]">
                            <i class="fas fa-magnifying-glass text-base"></i>
                        </div>
                        <h3 class="text-base font-bold text-slate-900 mb-2 font-display">Recherche Instantanée</h3>
                        <p class="text-xs sm:text-sm text-slate-600 leading-relaxed font-sans">
                            Interrogez le texte des documents en quelques frappes de touches avec raccourcis clavier (<kbd class="px-1.5 py-0.5 text-xs bg-slate-100 text-slate-700 border border-slate-200 rounded font-mono">/</kbd>) et surlignage dynamique des termes.
                        </p>
                    </div>

                    <div class="bg-white rounded-2xl border border-slate-200 p-6 shadow-xs hover:border-slate-300 transition-colors">
                        <div class="w-11 h-11 rounded-xl bg-[#f1f4fa] text-[#081951] flex items-center justify-center mb-4 border border-[#e2e8f4]">
                            <i class="fas fa-folder-tree text-base"></i>
                        </div>
                        <h3 class="text-base font-bold text-slate-900 mb-2 font-display">Organisation Métier</h3>
                        <p class="text-xs sm:text-sm text-slate-600 leading-relaxed font-sans">
                            Accédez aux procédures structurées par direction de l'entreprise Confiance (Ressources Humaines, Informatique & IT, Finance, Sécurité, Onboarding).
                        </p>
                    </div>

                    <div class="bg-white rounded-2xl border border-slate-200 p-6 shadow-xs hover:border-slate-300 transition-colors">
                        <div class="w-11 h-11 rounded-xl bg-[#f1f4fa] text-[#081951] flex items-center justify-center mb-4 border border-[#e2e8f4]">
                            <i class="fas fa-shield-halved text-base"></i>
                        </div>
                        <h3 class="text-base font-bold text-slate-900 mb-2 font-display">Gouvernance & Conformité</h3>
                        <p class="text-xs sm:text-sm text-slate-600 leading-relaxed font-sans">
                            Maintien à jour assuré par les référents habilités via le panneau d'administration sécurisé de l'entreprise Confiance.
                        </p>
                    </div>
                </div>

                <!-- Call To Action -->
                <div class="bg-[#081951] rounded-2xl p-8 sm:p-10 text-white text-center shadow-sm">
                    <h2 class="text-xl sm:text-2xl font-bold mb-2 font-display">Besoin d'une procédure ou d'un renseignement ?</h2>
                    <p class="text-slate-300 max-w-xl mx-auto mb-6 text-xs sm:text-sm font-sans">
                        Recherchez parmi l'ensemble des fiches actives ou adressez votre demande directement au support interne de Confiance.
                    </p>
                    <div class="flex flex-wrap justify-center gap-3">
                        <a
                            href="{{ route('home') }}"
                            class="px-5 py-2.5 rounded-xl bg-white text-[#081951] hover:bg-slate-100 font-bold text-xs sm:text-sm transition-all shadow-xs"
                        >
                            Accéder aux documents
                        </a>
                        <a
                            href="{{ route('contact') }}"
                            class="px-5 py-2.5 rounded-xl bg-[#0e2454] hover:bg-[#143673] text-white border border-slate-600 font-semibold text-xs sm:text-sm transition-colors"
                        >
                            Contacter le support interne
                        </a>
                    </div>
                </div>
            </div>
        </main>

        @include('partials.public-footer')

        @fluxScripts
    </body>
</html>
