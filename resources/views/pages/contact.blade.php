<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="h-full">
    <head>
        @include('partials.head', ['title' => 'Contactez-nous - Nvonchi Search'])
    </head>
    <body class="min-h-screen flex flex-col bg-zinc-50 dark:bg-zinc-950 text-zinc-900 dark:text-zinc-100 antialiased selection:bg-indigo-500 selection:text-white">
        @include('partials.public-header')

        <main class="flex-1 py-12 md:py-20">
            <div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8">
                <!-- Hero Header -->
                <div class="text-center max-w-2xl mx-auto mb-14">
                    <div class="inline-flex items-center gap-2 px-3.5 py-1.5 rounded-full bg-indigo-50 dark:bg-indigo-950/60 border border-indigo-200/80 dark:border-indigo-800 text-indigo-700 dark:text-indigo-300 text-xs font-semibold mb-4">
                        <span>Assistance & Questions</span>
                    </div>
                    <h1 class="text-3xl sm:text-4xl md:text-5xl font-black text-zinc-900 dark:text-white tracking-tight leading-tight">
                        Une question ou une suggestion ? <br class="hidden sm:inline" />
                        <span class="text-transparent bg-clip-text bg-gradient-to-r from-indigo-600 via-indigo-500 to-sky-500">Contactez notre équipe</span>
                    </h1>
                    <p class="mt-4 text-base text-zinc-600 dark:text-zinc-400">
                        Vous souhaitez proposer l'ajout d'une thématique ou signaler une information à actualiser ? Écrivez-nous directement via le formulaire ci-dessous.
                    </p>
                </div>

                <div class="grid grid-cols-1 lg:grid-cols-12 gap-10">
                    <!-- Left: Contact Details & Info Cards -->
                    <div class="lg:col-span-5 space-y-6">
                        <div class="bg-white dark:bg-zinc-900 rounded-2xl border border-zinc-200/80 dark:border-zinc-800 p-6 shadow-2xs">
                            <div class="w-10 h-10 rounded-xl bg-indigo-50 dark:bg-indigo-950/60 text-indigo-600 dark:text-indigo-400 flex items-center justify-center mb-4">
                                <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                                </svg>
                            </div>
                            <h3 class="text-base font-bold text-zinc-900 dark:text-white">Email Direct</h3>
                            <p class="text-sm text-zinc-600 dark:text-zinc-400 mt-1">Écrivez à notre équipe support pour toute demande :</p>
                            <a href="mailto:contact@nvonchi.com" class="inline-block mt-2 text-sm font-semibold text-indigo-600 dark:text-indigo-400 hover:underline">
                                contact@nvonchi.com
                            </a>
                        </div>

                        <div class="bg-white dark:bg-zinc-900 rounded-2xl border border-zinc-200/80 dark:border-zinc-800 p-6 shadow-2xs">
                            <div class="w-10 h-10 rounded-xl bg-sky-50 dark:bg-sky-950/60 text-sky-600 dark:text-sky-400 flex items-center justify-center mb-4">
                                <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z" />
                                </svg>
                            </div>
                            <h3 class="text-base font-bold text-zinc-900 dark:text-white">Téléphone & Assistance</h3>
                            <p class="text-sm text-zinc-600 dark:text-zinc-400 mt-1">Disponible du lundi au vendredi de 8h à 17h :</p>
                            <a href="tel:+237222000000" class="inline-block mt-2 text-sm font-semibold text-indigo-600 dark:text-indigo-400 hover:underline">
                                +237 222 00 00 00
                            </a>
                        </div>

                        <div class="bg-white dark:bg-zinc-900 rounded-2xl border border-zinc-200/80 dark:border-zinc-800 p-6 shadow-2xs">
                            <div class="w-10 h-10 rounded-xl bg-emerald-50 dark:bg-emerald-950/60 text-emerald-600 dark:text-emerald-400 flex items-center justify-center mb-4">
                                <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" />
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" />
                                </svg>
                            </div>
                            <h3 class="text-base font-bold text-zinc-900 dark:text-white">Administration</h3>
                            <p class="text-sm text-zinc-600 dark:text-zinc-400 mt-1">Accédez à l'interface d'administration pour gérer les contenus :</p>
                            <a href="/admin" class="inline-block mt-2 text-sm font-semibold text-indigo-600 dark:text-indigo-400 hover:underline">
                                Connexion au panel Filament &rarr;
                            </a>
                        </div>
                    </div>

                    <!-- Right: Contact Form -->
                    <div class="lg:col-span-7">
                        <div class="bg-white dark:bg-zinc-900 rounded-3xl border border-zinc-200/80 dark:border-zinc-800 p-6 sm:p-10 shadow-sm">
                            <h2 class="text-xl sm:text-2xl font-bold text-zinc-900 dark:text-white mb-6">
                                Envoyez-nous un message
                            </h2>

                            <!-- Success Banner -->
                            @if(session('status'))
                                <div class="mb-6 p-4 rounded-xl bg-emerald-50 dark:bg-emerald-950/60 border border-emerald-200 dark:border-emerald-800 text-emerald-800 dark:text-emerald-200 text-sm flex items-start gap-3">
                                    <svg class="w-5 h-5 text-emerald-600 shrink-0 mt-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                                    </svg>
                                    <span>{{ session('status') }}</span>
                                </div>
                            @endif

                            <form action="{{ route('contact.store') }}" method="POST" class="space-y-5">
                                @csrf

                                <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
                                    <!-- Name -->
                                    <div>
                                        <label for="name" class="block text-xs font-semibold text-zinc-700 dark:text-zinc-300 mb-1.5">
                                            Votre nom complet <span class="text-rose-500">*</span>
                                        </label>
                                        <input
                                            type="text"
                                            id="name"
                                            name="name"
                                            value="{{ old('name') }}"
                                            required
                                            placeholder="Ex: Jean Dupont"
                                            class="w-full px-4 py-2.5 rounded-xl border border-zinc-300 dark:border-zinc-700 bg-white dark:bg-zinc-800 text-zinc-900 dark:text-zinc-100 text-sm focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/20 focus:outline-hidden transition-colors"
                                        />
                                        @error('name')
                                            <p class="mt-1 text-xs text-rose-600 dark:text-rose-400">{{ $message }}</p>
                                        @enderror
                                    </div>

                                    <!-- Email -->
                                    <div>
                                        <label for="email" class="block text-xs font-semibold text-zinc-700 dark:text-zinc-300 mb-1.5">
                                            Adresse Email <span class="text-rose-500">*</span>
                                        </label>
                                        <input
                                            type="email"
                                            id="email"
                                            name="email"
                                            value="{{ old('email') }}"
                                            required
                                            placeholder="Ex: jean.dupont@example.com"
                                            class="w-full px-4 py-2.5 rounded-xl border border-zinc-300 dark:border-zinc-700 bg-white dark:bg-zinc-800 text-zinc-900 dark:text-zinc-100 text-sm focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/20 focus:outline-hidden transition-colors"
                                        />
                                        @error('email')
                                            <p class="mt-1 text-xs text-rose-600 dark:text-rose-400">{{ $message }}</p>
                                        @enderror
                                    </div>
                                </div>

                                <!-- Subject -->
                                <div>
                                    <label for="subject" class="block text-xs font-semibold text-zinc-700 dark:text-zinc-300 mb-1.5">
                                        Objet du message
                                    </label>
                                    <input
                                        type="text"
                                        id="subject"
                                        name="subject"
                                        value="{{ old('subject') }}"
                                        placeholder="Ex: Suggestion d'ajout sur les Bourses universitaires"
                                        class="w-full px-4 py-2.5 rounded-xl border border-zinc-300 dark:border-zinc-700 bg-white dark:bg-zinc-800 text-zinc-900 dark:text-zinc-100 text-sm focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/20 focus:outline-hidden transition-colors"
                                    />
                                    @error('subject')
                                        <p class="mt-1 text-xs text-rose-600 dark:text-rose-400">{{ $message }}</p>
                                    @enderror
                                </div>

                                <!-- Message -->
                                <div>
                                    <label for="message" class="block text-xs font-semibold text-zinc-700 dark:text-zinc-300 mb-1.5">
                                        Votre message <span class="text-rose-500">*</span>
                                    </label>
                                    <textarea
                                        id="message"
                                        name="message"
                                        rows="5"
                                        required
                                        placeholder="Expliquez en détails votre demande ou suggestion..."
                                        class="w-full px-4 py-2.5 rounded-xl border border-zinc-300 dark:border-zinc-700 bg-white dark:bg-zinc-800 text-zinc-900 dark:text-zinc-100 text-sm focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/20 focus:outline-hidden transition-colors"
                                    >{{ old('message') }}</textarea>
                                    @error('message')
                                        <p class="mt-1 text-xs text-rose-600 dark:text-rose-400">{{ $message }}</p>
                                    @enderror
                                </div>

                                <!-- Submit Button -->
                                <button
                                    type="submit"
                                    class="w-full py-3 px-6 rounded-xl bg-indigo-600 hover:bg-indigo-700 text-white font-bold text-sm transition-colors shadow-sm cursor-pointer flex items-center justify-center gap-2"
                                >
                                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8" />
                                    </svg>
                                    <span>Envoyer le message</span>
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </main>

        @include('partials.public-footer')

        @fluxScripts
    </body>
</html>
