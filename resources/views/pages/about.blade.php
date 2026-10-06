<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="h-full">
    <head>
        @include('partials.head', ['title' => 'À propos du Portail Interne - Nvonchi Search'])
    </head>
    <body class="min-h-screen flex flex-col bg-zinc-50 dark:bg-zinc-950 text-zinc-900 dark:text-zinc-100 antialiased selection:bg-indigo-500 selection:text-white">
        @include('partials.public-header')

        <main class="flex-1 py-12 md:py-20">
            <div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8">
                <!-- Hero Header -->
                <div class="text-center max-w-3xl mx-auto mb-16">
                    <div class="inline-flex items-center gap-2 px-3.5 py-1.5 rounded-full bg-indigo-50 dark:bg-indigo-950/60 border border-indigo-200/80 dark:border-indigo-800 text-indigo-700 dark:text-indigo-300 text-xs font-semibold mb-4">
                        <span>Base Opérationnelle Collaborateurs</span>
                    </div>
                    <h1 class="text-3xl sm:text-4xl md:text-5xl font-black text-zinc-900 dark:text-white tracking-tight leading-tight">
                        La documentation interne de l'entreprise, <br class="hidden sm:inline" />
                        <span class="text-transparent bg-clip-text bg-gradient-to-r from-indigo-600 via-indigo-500 to-sky-500">claire, instantanée et à portée de main</span>
                    </h1>
                    <p class="mt-5 text-base sm:text-lg text-zinc-600 dark:text-zinc-400 leading-relaxed">
                        Nvonchi Search est le hub de connaissances interne dédié aux équipes et collaborateurs. Il centralise l'ensemble des procédures opérationnelles, démarches RH, guides de sécurité IT et formulaires d'entreprise en un point d'accès unique.
                    </p>
                </div>

                <!-- Brand Card -->
                <div class="bg-white dark:bg-zinc-900 rounded-3xl border border-zinc-200/80 dark:border-zinc-800 p-8 sm:p-12 shadow-sm mb-16">
                    <div class="grid grid-cols-1 md:grid-cols-12 gap-8 items-center">
                        <div class="md:col-span-4 flex justify-center">
                            <img
                                src="{{ asset('logo.jpg') }}"
                                alt="Local Search Logo"
                                class="w-48 h-48 rounded-2xl object-contain shadow-md border border-zinc-200/80 dark:border-zinc-700"
                            />
                        </div>
                        <div class="md:col-span-8 space-y-4">
                            <h2 class="text-2xl font-bold text-zinc-900 dark:text-white">
                                Une réponse immédiate pour chaque démarche métier
                            </h2>
                            <p class="text-zinc-600 dark:text-zinc-300 leading-relaxed">
                                Finies les pertes de temps à rechercher des documents disséminés dans des boîtes mails ou sur des disques partagés. Ce portail indexe en continu les modes opératoires validés par chaque direction (RH, DSI, Finance, Opérations).
                            </p>
                            <p class="text-zinc-600 dark:text-zinc-300 leading-relaxed">
                                Chaque fiche pratique intègre la démarche pas-à-pas, le lien direct vers l'application métier concernée (SIRH, Helpdesk, Achats), le document modèle téléchargeable ainsi que le contact direct du référent interne.
                            </p>
                        </div>
                    </div>
                </div>

                <!-- 3 Pillars Grid -->
                <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-16">
                    <div class="bg-white dark:bg-zinc-900 rounded-2xl border border-zinc-200/80 dark:border-zinc-800 p-6 shadow-2xs">
                        <div class="w-12 h-12 rounded-xl bg-indigo-50 dark:bg-indigo-950/60 text-indigo-600 dark:text-indigo-400 flex items-center justify-center mb-4">
                            <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z" />
                            </svg>
                        </div>
                        <h3 class="text-lg font-bold text-zinc-900 dark:text-white mb-2">Recherche Instantanée</h3>
                        <p class="text-sm text-zinc-600 dark:text-zinc-400 leading-relaxed">
                            Interrogez la base en quelques frappes de touches avec raccourcis clavier (<kbd class="px-1 py-0.5 text-xs bg-zinc-100 dark:bg-zinc-800 rounded font-mono">/</kbd>) et surlignage dynamique des termes.
                        </p>
                    </div>

                    <div class="bg-white dark:bg-zinc-900 rounded-2xl border border-zinc-200/80 dark:border-zinc-800 p-6 shadow-2xs">
                        <div class="w-12 h-12 rounded-xl bg-sky-50 dark:bg-sky-950/60 text-sky-600 dark:text-sky-400 flex items-center justify-center mb-4">
                            <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z" />
                            </svg>
                        </div>
                        <h3 class="text-lg font-bold text-zinc-900 dark:text-white mb-2">Classement par Pôles</h3>
                        <p class="text-sm text-zinc-600 dark:text-zinc-400 leading-relaxed">
                            Filtrez les procédures par département (#Ressources Humaines, #Informatique & IT, #Finance, #Sécurité, #Onboarding).
                        </p>
                    </div>

                    <div class="bg-white dark:bg-zinc-900 rounded-2xl border border-zinc-200/80 dark:border-zinc-800 p-6 shadow-2xs">
                        <div class="w-12 h-12 rounded-xl bg-emerald-50 dark:bg-emerald-950/60 text-emerald-600 dark:text-emerald-400 flex items-center justify-center mb-4">
                            <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" />
                            </svg>
                        </div>
                        <h3 class="text-lg font-bold text-zinc-900 dark:text-white mb-2">Gouvernance & Conformité</h3>
                        <p class="text-sm text-zinc-600 dark:text-zinc-400 leading-relaxed">
                            Maintien à jour assuré par les référents habilités via le panneau d'administration sécurisé Filament.
                        </p>
                    </div>
                </div>

                <!-- Call To Action -->
                <div class="bg-gradient-to-r from-indigo-600 to-sky-600 rounded-3xl p-8 sm:p-12 text-white text-center shadow-lg">
                    <h2 class="text-2xl sm:text-3xl font-bold mb-3">Besoin d'une procédure ou d'un renseignement ?</h2>
                    <p class="text-indigo-100 max-w-xl mx-auto mb-8 text-sm sm:text-base">
                        Recherchez parmi l'ensemble des fiches actives ou adressez votre demande directement au support interne.
                    </p>
                    <div class="flex flex-wrap justify-center gap-4">
                        <a
                            href="{{ route('home') }}"
                            class="px-6 py-3 rounded-xl bg-white text-indigo-700 font-bold text-sm hover:bg-indigo-50 transition-colors shadow-sm"
                        >
                            Accéder aux procédures
                        </a>
                        <a
                            href="{{ route('contact') }}"
                            class="px-6 py-3 rounded-xl bg-indigo-700/80 hover:bg-indigo-700 text-white font-bold text-sm border border-indigo-400/40 transition-colors"
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
