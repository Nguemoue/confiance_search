<header class="sticky top-0 z-40 w-full backdrop-blur-md bg-white/95 border-b border-slate-200/90 transition-colors">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 h-16 flex items-center justify-between">
        <!-- Brand / Logo CS Knowledge -->
        <x-saasover.logo size="md" />

        <!-- Center Nav Links -->
        <nav class="hidden md:flex items-center gap-1.5 text-sm font-medium">
            <a
                href="{{ route('home') }}"
                class="px-3 py-1.5 rounded-lg transition-colors {{ request()->routeIs('home') ? 'bg-[#f1f4fa] text-[#081951] font-semibold border border-[#e2e8f4]' : 'text-slate-600 hover:text-[#081951] hover:bg-slate-50' }}"
            >
                {{ __('portal.all_documents') }}
            </a>
            <a
                href="{{ route('about') }}"
                class="px-3 py-1.5 rounded-lg transition-colors {{ request()->routeIs('about') ? 'bg-[#f1f4fa] text-[#081951] font-semibold border border-[#e2e8f4]' : 'text-slate-600 hover:text-[#081951] hover:bg-slate-50' }}"
            >
                {{ __('portal.about') }}
            </a>
            <a
                href="{{ route('contact') }}"
                class="px-3 py-1.5 rounded-lg transition-colors {{ request()->routeIs('contact') ? 'bg-[#f1f4fa] text-[#081951] font-semibold border border-[#e2e8f4]' : 'text-slate-600 hover:text-[#081951] hover:bg-slate-50' }}"
            >
                {{ __('portal.support') }}
            </a>
        </nav>

        <!-- Right Menu: Language Switcher & Admin Panel -->
        <div class="flex items-center gap-3">
            <!-- Language Switcher FR / EN -->
            <div class="flex items-center rounded-lg border border-slate-200 p-0.5 bg-slate-50 text-xs font-semibold">
                <a
                    href="{{ route('locale.switch', 'fr') }}"
                    class="px-2 py-1 rounded-md transition-colors {{ app()->getLocale() === 'fr' ? 'bg-[#081951] text-white shadow-2xs' : 'text-slate-600 hover:text-[#081951]' }}"
                    title="Français"
                >
                    FR
                </a>
                <a
                    href="{{ route('locale.switch', 'en') }}"
                    class="px-2 py-1 rounded-md transition-colors {{ app()->getLocale() === 'en' ? 'bg-[#081951] text-white shadow-2xs' : 'text-slate-600 hover:text-[#081951]' }}"
                    title="English"
                >
                    EN
                </a>
            </div>

            <a
                href="/admin"
                class="inline-flex items-center gap-2 px-3.5 py-1.5 rounded-lg text-xs font-semibold bg-[#081951] hover:bg-[#0e2454] text-white transition-all duration-150 shadow-xs focus:outline-hidden"
                title="Accéder au panneau d'administration des documents"
            >
                <i class="fas fa-folder-gear text-slate-300"></i>
                <span class="hidden sm:inline">{{ __('portal.admin') }}</span>
            </a>
        </div>
    </div>
</header>
