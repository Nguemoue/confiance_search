<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="h-full bg-[#f4f5f7]">
    <head>
        @include('partials.head', ['title' => 'Contact Support Documentaire - Entreprise Confiance'])
    </head>
    <body class="min-h-screen flex flex-col bg-[#f4f5f7] text-slate-800 antialiased selection:bg-[#081951] selection:text-white font-sans">
        <!-- Top Confiance Accent Bar -->
        <div class="confiance-accent-bar fixed top-0 left-0 right-0 z-50"></div>

        @include('partials.public-header')

        <main class="flex-1 py-12 md:py-20">
            <div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8">
                <!-- Hero Header -->
                <div class="text-center max-w-2xl mx-auto mb-12">
                    <div class="inline-flex items-center gap-2 px-3.5 py-1 rounded-full bg-[#f1f4fa] border border-[#e2e8f4] text-[#081951] text-xs font-semibold mb-4">
                        <x-saasover.mark size="size-4" />
                        <span>Support Documentaire &bull; Entreprise Confiance</span>
                    </div>
                    <h1 class="text-3xl sm:text-4xl md:text-5xl font-black text-slate-900 tracking-tight leading-tight font-display">
                        Une question ou une suggestion ? <br class="hidden sm:inline" />
                        <span class="text-[#081951]">Contactez notre équipe</span>
                    </h1>
                    <p class="mt-4 text-sm sm:text-base text-slate-600 font-sans">
                        Vous souhaitez proposer l'ajout d'une procédure, signaler un document obsolète ou poser une question sur une démarche interne ? Écrivez directement aux référents documentaires de l'entreprise Confiance via le formulaire ci-dessous.
                    </p>
                </div>

                <div class="grid grid-cols-1 lg:grid-cols-12 gap-8">
                    <!-- Left: Contact Details & Info Cards -->
                    <div class="lg:col-span-5 space-y-5">
                        <div class="bg-white rounded-2xl border border-slate-200 p-6 shadow-xs">
                            <div class="w-10 h-10 rounded-xl bg-[#f1f4fa] text-[#081951] flex items-center justify-center mb-4 border border-[#e2e8f4]">
                                <i class="fas fa-envelope text-sm"></i>
                            </div>
                            <h3 class="text-base font-bold text-slate-900 font-display">Support Documentaire</h3>
                            <p class="text-xs sm:text-sm text-slate-600 mt-1 font-sans">Écrivez aux référents documentaires pour toute question :</p>
                            <a href="mailto:support@confiance.internal" class="inline-block mt-2 text-xs sm:text-sm font-semibold text-[#081951] hover:underline">
                                support@confiance.internal
                            </a>
                        </div>

                        <div class="bg-white rounded-2xl border border-slate-200 p-6 shadow-xs">
                            <div class="w-10 h-10 rounded-xl bg-[#f1f4fa] text-[#081951] flex items-center justify-center mb-4 border border-[#e2e8f4]">
                                <i class="fas fa-phone text-sm"></i>
                            </div>
                            <h3 class="text-base font-bold text-slate-900 font-display">Permanence & Assistance</h3>
                            <p class="text-xs sm:text-sm text-slate-600 mt-1 font-sans">Poste interne disponible du lundi au vendredi :</p>
                            <a href="tel:+237222000000" class="inline-block mt-2 text-xs sm:text-sm font-semibold text-[#081951] hover:underline">
                                Poste 2200 (+237 222 00 00 00)
                            </a>
                        </div>

                        <div class="bg-white rounded-2xl border border-slate-200 p-6 shadow-xs">
                            <div class="w-10 h-10 rounded-xl bg-[#f1f4fa] text-[#081951] flex items-center justify-center mb-4 border border-[#e2e8f4]">
                                <i class="fas fa-folder-gear text-sm"></i>
                            </div>
                            <h3 class="text-base font-bold text-slate-900 font-display">Administration Documentaire</h3>
                            <p class="text-xs sm:text-sm text-slate-600 mt-1 font-sans">Accès réservé aux gestionnaires de pôle et référents habilités :</p>
                            <a href="/admin" class="inline-block mt-2 text-xs sm:text-sm font-semibold text-[#081951] hover:underline">
                                Panneau d'administration Filament &rarr;
                            </a>
                        </div>
                    </div>

                    <!-- Right: Contact Form -->
                    <div class="lg:col-span-7">
                        <div class="bg-white rounded-2xl border border-slate-200 p-6 sm:p-8 shadow-xs">
                            <h2 class="text-xl sm:text-2xl font-bold text-slate-900 mb-6 font-display">
                                Transmettre une demande ou suggestion
                            </h2>

                            <!-- Success Banner -->
                            @if(session('status'))
                                <div class="mb-6 p-4 rounded-xl bg-emerald-50 border border-emerald-200 text-emerald-800 text-xs sm:text-sm flex items-start gap-3">
                                    <svg class="w-5 h-5 text-emerald-600 shrink-0 mt-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                                    </svg>
                                    <span>{{ session('status') }}</span>
                                </div>
                            @endif

                            <form action="{{ route('contact.store') }}" method="POST" class="space-y-4">
                                @csrf

                                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                    <!-- Name -->
                                    <div>
                                        <label for="name" class="block text-xs font-semibold text-slate-700 mb-1.5 font-display">
                                            Votre nom complet <span class="text-rose-500">*</span>
                                        </label>
                                        <input
                                            type="text"
                                            id="name"
                                            name="name"
                                            value="{{ old('name') }}"
                                            required
                                            placeholder="Ex: Jean Dupont"
                                            class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 bg-white text-slate-900 text-sm focus:border-[#081951] focus:ring-1 focus:ring-[#081951] focus:outline-hidden transition-colors"
                                        />
                                        @error('name')
                                            <p class="mt-1 text-xs text-rose-600">{{ $message }}</p>
                                        @enderror
                                    </div>

                                    <!-- Email -->
                                    <div>
                                        <label for="email" class="block text-xs font-semibold text-slate-700 mb-1.5 font-display">
                                            Adresse Email professionnelle <span class="text-rose-500">*</span>
                                        </label>
                                        <input
                                            type="email"
                                            id="email"
                                            name="email"
                                            value="{{ old('email') }}"
                                            required
                                            placeholder="Ex: jean.dupont@confiance.internal"
                                            class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 bg-white text-slate-900 text-sm focus:border-[#081951] focus:ring-1 focus:ring-[#081951] focus:outline-hidden transition-colors"
                                        />
                                        @error('email')
                                            <p class="mt-1 text-xs text-rose-600">{{ $message }}</p>
                                        @enderror
                                    </div>
                                </div>

                                <!-- Subject -->
                                <div>
                                    <label for="subject" class="block text-xs font-semibold text-slate-700 mb-1.5 font-display">
                                        Objet de la demande
                                    </label>
                                    <input
                                        type="text"
                                        id="subject"
                                        name="subject"
                                        value="{{ old('subject') }}"
                                        placeholder="Ex: Actualisation de la note de service Télétravail"
                                        class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 bg-white text-slate-900 text-sm focus:border-[#081951] focus:ring-1 focus:ring-[#081951] focus:outline-hidden transition-colors"
                                    />
                                    @error('subject')
                                        <p class="mt-1 text-xs text-rose-600">{{ $message }}</p>
                                    @enderror
                                </div>

                                <!-- Message -->
                                <div>
                                    <label for="message" class="block text-xs font-semibold text-slate-700 mb-1.5 font-display">
                                        Détails de la demande ou procédure concernée <span class="text-rose-500">*</span>
                                    </label>
                                    <textarea
                                        id="message"
                                        name="message"
                                        rows="4"
                                        required
                                        placeholder="Précisez le document concerné, les éléments à corriger ou la procédure à ajouter..."
                                        class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 bg-white text-slate-900 text-sm focus:border-[#081951] focus:ring-1 focus:ring-[#081951] focus:outline-hidden transition-colors"
                                    >{{ old('message') }}</textarea>
                                    @error('message')
                                        <p class="mt-1 text-xs text-rose-600">{{ $message }}</p>
                                    @enderror
                                </div>

                                <!-- Submit Button -->
                                <button
                                    type="submit"
                                    class="w-full py-2.5 px-5 rounded-xl bg-[#081951] hover:bg-[#0e2454] text-white font-semibold text-sm transition-all shadow-xs cursor-pointer flex items-center justify-center gap-2"
                                >
                                    <i class="fas fa-paper-plane text-xs"></i>
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
