<?php

use App\Models\Topic;
use Illuminate\Database\Eloquent\Collection;
use Livewire\Attributes\Url;
use Livewire\Component;

new class extends Component
{
    /**
     * @var array{total_topics?: int, total_options?: int}
     */
    public array $stats = [];

    #[Url(as: 'q', history: true)]
    public string $search = '';

    /**
     * @var list<string>
     */
    public array $searchHistory = [];

    public function mount(array $stats = []): void
    {
        $this->stats = $stats;
        $this->searchHistory = session('search_history', []);
    }

    public function clearFilters(): void
    {
        $this->search = '';
    }

    /**
     * Save non-empty search terms into user search history.
     */
    public function recordSearch(): void
    {
        $term = trim($this->search);
        if (mb_strlen($term) >= 2) {
            $history = session('search_history', []);
            // Remove previous occurrence to put most recent at the top
            $history = array_values(array_filter($history, fn ($item) => mb_strtolower($item) !== mb_strtolower($term)));
            array_unshift($history, $term);
            $history = array_slice($history, 0, 10);
            session(['search_history' => $history]);
            $this->searchHistory = $history;
        }
    }

    public function updatedSearch(): void
    {
        $this->recordSearch();
    }

    public function applyHistory(string $term): void
    {
        $this->search = $term;
        $this->recordSearch();
    }

    public function removeFromHistory(string $term): void
    {
        $history = session('search_history', []);
        $history = array_values(array_filter($history, fn ($item) => $item !== $term));
        session(['search_history' => $history]);
        $this->searchHistory = $history;
    }

    public function clearHistory(): void
    {
        session(['search_history' => []]);
        $this->searchHistory = [];
    }

    /**
     * Helper to highlight matching keywords with clean Confiance navy accent.
     */
    public function highlightText(?string $text): string
    {
        if ($text === null || $text === '') {
            return '';
        }

        $escaped = e($text);
        $term = trim($this->search);

        if ($term === '') {
            return $escaped;
        }

        $pattern = '/('.preg_quote(e($term), '/').')/iu';

        return preg_replace(
            $pattern,
            '<mark class="bg-[#e2e8f4] text-[#081951] px-1 py-0.5 rounded font-semibold">$1</mark>',
            $escaped
        ) ?? $escaped;
    }

    /**
     * @return array{
     *     topics: Collection<int, Topic>|\Illuminate\Support\Collection<int, Topic>,
     *     totalTopicsCount: int,
     *     totalOptionsCount: int,
     *     searchHistory: list<string>
     * }
     */
    public function with(): array
    {
        $trimmedSearch = trim($this->search);

        if ($trimmedSearch !== '') {
            try {
                // Use Laravel Scout search engine
                $topics = Topic::search($trimmedSearch)
                    ->query(function ($query): void {
                        $query->published()
                            ->with([
                                'options' => fn ($q) => $q->published()->orderBy('order'),
                            ]);
                    })
                    ->get();
            } catch (\Throwable $e) {
                // Graceful fallback to Eloquent multi-column search
                $topics = Topic::query()
                    ->published()
                    ->with([
                        'options' => fn ($q) => $q->published()->orderBy('order'),
                    ])
                    ->search($trimmedSearch)
                    ->orderBy('order')
                    ->get();
            }
        } else {
            // Standard Eloquent retrieval
            $topics = Topic::query()
                ->published()
                ->with([
                    'options' => fn ($q) => $q->published()->orderBy('order'),
                ])
                ->orderBy('order')
                ->get();
        }

        $totalTopicsCount = $this->stats['total_topics'] ?? Topic::query()->published()->count();
        $totalOptionsCount = $this->stats['total_options'] ?? \App\Models\TopicOption::query()->published()->count();

        return [
            'topics' => $topics,
            'totalTopicsCount' => $totalTopicsCount,
            'totalOptionsCount' => $totalOptionsCount,
            'searchHistory' => $this->searchHistory,
        ];
    }
};
?>

<div
    x-data="{
        openTopics: {},
        copiedTopicId: null,
        mobileSidebarOpen: false,

        isTopicOpen(id) {
            return !!this.openTopics[id];
        },

        toggleTopic(id) {
            this.openTopics[id] = !this.isTopicOpen(id);
        },

        expandAll(ids) {
            ids.forEach(id => {
                this.openTopics[id] = true;
            });
        },

        collapseAll() {
            this.openTopics = {};
        },

        copyTopicLink(id) {
            const url = window.location.origin + window.location.pathname + '#procedure-' + id;
            navigator.clipboard.writeText(url);
            this.copiedTopicId = id;
            setTimeout(() => { this.copiedTopicId = null; }, 2000);
        },

        init() {
            const currentSearch = ($wire.search || '').trim();
            if (currentSearch !== '') {
                @foreach($topics as $t)
                    this.openTopics[{{ $t->id }}] = true;
                @endforeach
            } else {
                @if($topics->first())
                    this.openTopics[{{ $topics->first()->id }}] = true;
                @endif
            }

            $wire.$watch('search', (newVal) => {
                if ((newVal || '').trim() !== '') {
                    this.$nextTick(() => {
                        document.querySelectorAll('[data-topic-id]').forEach(el => {
                            const id = parseInt(el.getAttribute('data-topic-id'), 10);
                            if (id) this.openTopics[id] = true;
                        });
                    });
                }
            });
        }
    }"
    @keydown.window.prevent.slash="$refs.searchInput.focus()"
    @keydown.window.cmd.k.prevent="$refs.searchInput.focus()"
    @keydown.window.ctrl.k.prevent="$refs.searchInput.focus()"
    @keydown.escape="if (mobileSidebarOpen) { mobileSidebarOpen = false; } else if ($wire.search) { $wire.clearFilters(); }"
    class="min-h-screen bg-[#f4f5f7] text-slate-800 flex flex-col md:flex-row antialiased relative selection:bg-[#081951] selection:text-white"
>
    <!-- ============================================================== -->
    <!-- MOBILE SIDEBAR BACKDROP & DRAWER                              -->
    <!-- ============================================================== -->
    <div
        x-show="mobileSidebarOpen"
        x-transition:enter="transition-opacity ease-linear duration-200"
        x-transition:enter-start="opacity-0"
        x-transition:enter-end="opacity-100"
        x-transition:leave="transition-opacity ease-linear duration-150"
        x-transition:leave-start="opacity-100"
        x-transition:leave-end="opacity-0"
        @click="mobileSidebarOpen = false"
        class="fixed inset-0 bg-slate-900/40 backdrop-blur-xs z-40 md:hidden"
        style="display: none;"
    ></div>

    <aside
        x-show="mobileSidebarOpen"
        x-transition:enter="transition ease-out duration-250 transform"
        x-transition:enter-start="-translate-x-full"
        x-transition:enter-end="translate-x-0"
        x-transition:leave="transition ease-in duration-200 transform"
        x-transition:leave-start="translate-x-0"
        x-transition:leave-end="-translate-x-full"
        class="fixed inset-y-0 left-0 w-80 max-w-[85vw] bg-white border-r border-slate-200 z-50 flex flex-col md:hidden shadow-xl"
        style="display: none;"
    >
        <!-- Mobile Sidebar Header -->
        <div class="h-16 px-5 border-b border-slate-200 flex items-center justify-between">
            <x-saasover.logo size="md" />
            <button
                type="button"
                @click="mobileSidebarOpen = false"
                class="p-2 rounded-lg text-slate-400 hover:text-slate-800 hover:bg-slate-100 transition-colors"
                title="Fermer le menu"
            >
                <i class="fas fa-times text-sm"></i>
            </button>
        </div>

        <!-- Mobile Sidebar Scrollable Content -->
        <div class="flex-1 overflow-y-auto px-4 py-5 space-y-6">
            <!-- Statut Portail Confiance -->
            <div class="flex items-center gap-2 px-3.5 py-2 rounded-xl bg-[#f1f4fa] border border-[#e2e8f4] text-xs">
                <span class="size-2 rounded-full bg-[#081951]"></span>
                <span class="text-[#081951] font-semibold">Portail Interne Confiance</span>
                <span class="ml-auto text-[10px] text-slate-600 font-bold bg-white px-2 py-0.5 rounded border border-slate-200">Actif</span>
            </div>

            <!-- Stats Mini Card -->
            <div class="grid grid-cols-2 gap-2">
                <div class="p-3 rounded-xl bg-slate-50 border border-slate-200">
                    <div class="text-lg font-bold text-slate-900 font-display">{{ $totalTopicsCount }}</div>
                    <div class="text-[11px] text-slate-500">Dossiers actifs</div>
                </div>
                <div class="p-3 rounded-xl bg-slate-50 border border-slate-200">
                    <div class="text-lg font-bold text-[#081951] font-display">{{ $totalOptionsCount }}</div>
                    <div class="text-[11px] text-slate-500">Procédures</div>
                </div>
            </div>

            <!-- Navigation Links -->
            <div class="space-y-1">
                <div class="text-[10px] font-mono font-bold uppercase tracking-wider text-slate-400 px-2 pb-1">
                    Navigation
                </div>

                <button
                    type="button"
                    wire:click="clearFilters"
                    @click="mobileSidebarOpen = false"
                    class="w-full flex items-center justify-between px-3.5 py-2 rounded-xl text-sm font-semibold transition-all {{ empty($search) ? 'bg-[#081951] text-white shadow-xs' : 'text-slate-600 hover:text-slate-900 hover:bg-slate-50' }}"
                >
                    <span class="flex items-center gap-2.5">
                        <i class="fas fa-layer-group text-xs"></i>
                        <span>Tous les documents</span>
                    </span>
                    <span class="text-xs px-2 py-0.5 rounded-full {{ empty($search) ? 'bg-white/20 text-white' : 'bg-slate-100 text-slate-700' }}">{{ $totalTopicsCount }}</span>
                </button>

                <a
                    href="{{ route('about') }}"
                    class="flex items-center gap-2.5 px-3.5 py-2 rounded-xl text-sm font-medium text-slate-600 hover:text-[#081951] hover:bg-slate-50 transition-colors"
                >
                    <i class="fas fa-circle-info text-xs text-slate-400"></i>
                    <span>À propos du portail</span>
                </a>

                <a
                    href="{{ route('contact') }}"
                    class="flex items-center gap-2.5 px-3.5 py-2 rounded-xl text-sm font-medium text-slate-600 hover:text-[#081951] hover:bg-slate-50 transition-colors"
                >
                    <i class="fas fa-headset text-xs text-slate-400"></i>
                    <span>Support & Référents</span>
                </a>

                <a
                    href="/admin"
                    class="flex items-center gap-2.5 px-3.5 py-2 rounded-xl text-sm font-medium text-slate-600 hover:text-[#081951] hover:bg-slate-50 transition-colors"
                >
                    <i class="fas fa-cog text-xs text-slate-400"></i>
                    <span>Administration des documents</span>
                </a>
            </div>

            <!-- Historique des Recherches (Mobile) -->
            <div class="space-y-2 pt-2 border-t border-slate-200">
                <div class="flex items-center justify-between px-2 pb-1">
                    <div class="flex items-center gap-1.5 text-xs text-slate-500 font-medium">
                        <i class="fas fa-clock-rotate-left text-slate-400 text-xs"></i>
                        <span class="text-[10px] font-mono font-bold uppercase tracking-wider">Historique</span>
                    </div>
                    @if(!empty($searchHistory))
                        <button
                            type="button"
                            wire:click="clearHistory"
                            class="text-slate-400 hover:text-slate-700 text-xs font-medium"
                        >
                            Effacer
                        </button>
                    @endif
                </div>

                @if(!empty($searchHistory))
                    <div class="space-y-1">
                        @foreach($searchHistory as $term)
                            <div class="flex items-center justify-between px-3 py-1.5 rounded-lg text-xs font-medium bg-slate-50 hover:bg-slate-100 border border-slate-200 text-slate-700 transition-colors">
                                <button
                                    type="button"
                                    wire:click="applyHistory('{{ $term }}')"
                                    @click="mobileSidebarOpen = false"
                                    class="flex items-center gap-2 truncate text-left flex-1"
                                >
                                    <i class="fas fa-magnifying-glass text-slate-400 text-[10px]"></i>
                                    <span class="truncate">{{ $term }}</span>
                                </button>
                                <button
                                    type="button"
                                    wire:click.stop="removeFromHistory('{{ $term }}')"
                                    class="p-1 text-slate-300 hover:text-rose-500 transition-colors"
                                    title="Supprimer"
                                >
                                    <i class="fas fa-times text-[10px]"></i>
                                </button>
                            </div>
                        @endforeach
                    </div>
                @else
                    <div class="px-3 py-3 rounded-lg bg-slate-50 border border-slate-200 text-center">
                        <p class="text-xs text-slate-500">Aucune recherche récente</p>
                    </div>
                @endif
            </div>
        </div>
    </aside>

    <!-- ============================================================== -->
    <!-- DESKTOP LEFT SIDEBAR (Épurée & Fluide)                         -->
    <!-- ============================================================== -->
    <aside class="w-72 lg:w-80 shrink-0 hidden md:flex flex-col h-screen sticky top-0 bg-white border-r border-slate-200 z-30 shadow-xs">
        <!-- Brand Header -->
        <div class="h-16 px-6 border-b border-slate-200 flex items-center justify-between">
            <x-saasover.logo size="md" />
            <span class="size-2 rounded-full bg-[#081951]" title="Portail interne opérationnel"></span>
        </div>

        <!-- Sidebar Body (Scrollable) -->
        <div class="flex-1 overflow-y-auto px-4 py-5 space-y-6">
            <!-- Confiance Portal Info -->
            <div class="p-3.5 rounded-xl bg-slate-50 border border-slate-200 space-y-2.5">
                <div class="flex items-center justify-between text-xs text-slate-600 font-medium">
                    <span class="flex items-center gap-1.5 text-slate-900 font-semibold font-display">
                        <i class="fas fa-folder-closed text-[#081951]"></i>
                        <span>Portail Confiance</span>
                    </span>
                    <span class="text-[10px] font-bold text-[#081951] uppercase tracking-wider bg-[#f1f4fa] border border-[#e2e8f4] px-2 py-0.5 rounded-full">
                        Certifié
                    </span>
                </div>
                <div class="grid grid-cols-2 gap-2 text-center">
                    <div class="p-2 rounded-lg bg-white border border-slate-200">
                        <div class="text-base font-bold text-slate-900 font-display">{{ $totalTopicsCount }}</div>
                        <div class="text-[10px] text-slate-500 uppercase font-semibold">Dossiers</div>
                    </div>
                    <div class="p-2 rounded-lg bg-white border border-slate-200">
                        <div class="text-base font-bold text-[#081951] font-display">{{ $totalOptionsCount }}</div>
                        <div class="text-[10px] text-slate-500 uppercase font-semibold">Procédures</div>
                    </div>
                </div>
            </div>

            <!-- Navigation Links -->
            <div class="space-y-1">
                <div class="text-[10px] font-mono font-bold uppercase tracking-wider text-slate-400 px-2 pb-1">
                    Navigation
                </div>

                <button
                    type="button"
                    wire:click="clearFilters"
                    class="w-full flex items-center justify-between px-3.5 py-2 rounded-xl text-sm font-semibold transition-all cursor-pointer {{ empty($search) ? 'bg-[#081951] text-white shadow-xs' : 'text-slate-600 hover:text-slate-900 hover:bg-slate-50' }}"
                >
                    <span class="flex items-center gap-2.5">
                        <i class="fas fa-layer-group text-xs"></i>
                        <span>Tous les documents</span>
                    </span>
                    <span class="text-xs px-2 py-0.5 rounded-full {{ empty($search) ? 'bg-white/20 text-white' : 'bg-slate-100 text-slate-700' }}">{{ $totalTopicsCount }}</span>
                </button>

                <a
                    href="{{ route('about') }}"
                    class="flex items-center gap-2.5 px-3.5 py-2 rounded-xl text-sm font-medium text-slate-600 hover:text-[#081951] hover:bg-slate-50 transition-colors"
                >
                    <i class="fas fa-circle-info text-xs text-slate-400"></i>
                    <span>À propos du portail</span>
                </a>

                <a
                    href="{{ route('contact') }}"
                    class="flex items-center gap-2.5 px-3.5 py-2 rounded-xl text-sm font-medium text-slate-600 hover:text-[#081951] hover:bg-slate-50 transition-colors"
                >
                    <i class="fas fa-headset text-xs text-slate-400"></i>
                    <span>Support & Référents</span>
                </a>

                <a
                    href="/admin"
                    class="flex items-center gap-2.5 px-3.5 py-2 rounded-xl text-sm font-medium text-slate-600 hover:text-[#081951] hover:bg-slate-50 transition-colors"
                >
                    <i class="fas fa-cog text-xs text-slate-400"></i>
                    <span>Administration des documents</span>
                </a>
            </div>

            <!-- Historique des Recherches (Desktop Sidebar) -->
            <div class="space-y-2 pt-2 border-t border-slate-200">
                <div class="flex items-center justify-between px-2 pb-1">
                    <div class="flex items-center gap-1.5 text-xs text-slate-500 font-medium">
                        <i class="fas fa-clock-rotate-left text-slate-400 text-xs"></i>
                        <span class="text-[10px] font-mono font-bold uppercase tracking-wider">Historique</span>
                    </div>
                    @if(!empty($searchHistory))
                        <button
                            type="button"
                            wire:click="clearHistory"
                            class="text-slate-400 hover:text-slate-700 text-xs font-medium cursor-pointer"
                        >
                            Effacer
                        </button>
                    @endif
                </div>

                @if(!empty($searchHistory))
                    <div class="space-y-1">
                        @foreach($searchHistory as $term)
                            <div class="flex items-center justify-between px-3 py-1.5 rounded-lg text-xs font-medium bg-slate-50 hover:bg-slate-100 border border-slate-200 text-slate-700 transition-colors group">
                                <button
                                    type="button"
                                    wire:click="applyHistory('{{ $term }}')"
                                    class="flex items-center gap-2 truncate text-left flex-1 cursor-pointer"
                                    title="Relancer cette recherche"
                                >
                                    <i class="fas fa-magnifying-glass text-slate-400 text-[10px] shrink-0"></i>
                                    <span class="truncate">{{ $term }}</span>
                                </button>
                                <button
                                    type="button"
                                    wire:click.stop="removeFromHistory('{{ $term }}')"
                                    class="p-0.5 text-slate-300 hover:text-rose-500 transition-colors cursor-pointer"
                                    title="Supprimer de l'historique"
                                >
                                    <i class="fas fa-times text-[10px]"></i>
                                </button>
                            </div>
                        @endforeach
                    </div>
                @else
                    <div class="px-3.5 py-4 rounded-xl bg-slate-50 border border-slate-200 text-center">
                        <i class="fas fa-clock text-slate-300 text-sm mb-1 block"></i>
                        <p class="text-xs text-slate-600 font-medium">Aucune recherche récente</p>
                        <p class="text-[10px] text-slate-400 mt-0.5">Vos recherches apparaîtront ici.</p>
                    </div>
                @endif
            </div>
        </div>

        <!-- Sidebar Footer -->
        <div class="p-3.5 border-t border-slate-200 bg-slate-50 flex items-center justify-between text-xs text-slate-500">
            <span class="flex items-center gap-1.5">
                <span class="size-2 rounded-full bg-[#081951]"></span>
                <span>Raccourci</span>
            </span>
            <kbd class="px-2 py-0.5 bg-white border border-slate-200 rounded font-mono text-slate-600 text-[10px] shadow-2xs">/</kbd>
        </div>
    </aside>

    <!-- ============================================================== -->
    <!-- MAIN CONTENT AREA                                              -->
    <!-- ============================================================== -->
    <main class="flex-1 min-w-0 flex flex-col min-h-screen bg-transparent relative z-10">
        <!-- Top Sticky Header Bar -->
        <header class="sticky top-0 z-20 backdrop-blur-md bg-white/95 border-b border-slate-200 px-4 sm:px-6 lg:px-8 py-3 flex items-center justify-between gap-3 sm:gap-6">
            <!-- Mobile Toggle -->
            <button
                type="button"
                @click="mobileSidebarOpen = true"
                class="md:hidden p-2 rounded-xl bg-white border border-slate-200 text-slate-600 hover:text-slate-900 hover:bg-slate-50 transition-colors shadow-2xs"
                title="Ouvrir le menu latéral"
            >
                <i class="fas fa-bars text-sm"></i>
            </button>

            <!-- Search Input Box -->
            <div class="relative flex-1 max-w-2xl flex items-center bg-white border border-slate-200 rounded-xl px-3.5 py-2 focus-within:border-[#081951] focus-within:ring-2 focus-within:ring-[#081951]/10 shadow-xs transition-all">
                <!-- Search Icon -->
                <div class="pr-2.5 flex items-center pointer-events-none text-[#081951]">
                    <svg viewBox="0 0 20 20" fill="currentColor" class="size-4.5">
                        <path fill-rule="evenodd" d="M9 3.5a5.5 5.5 0 100 11 5.5 5.5 0 000-11zM2 9a7 7 0 1112.452 4.391l3.328 3.329a.75.75 0 11-1.06 1.06l-3.329-3.328A7 7 0 012 9z" clip-rule="evenodd" />
                    </svg>
                </div>

                <!-- Input -->
                <input
                    x-ref="searchInput"
                    type="text"
                    wire:model.live.debounce.250ms="search"
                    @keydown.enter="recordSearch"
                    placeholder="Rechercher une démarche, un mot-clé ou un document (ex: télétravail, mutuelle, VPN...)"
                    class="w-full bg-transparent text-sm sm:text-base text-slate-900 placeholder-slate-400 focus:outline-hidden font-sans"
                    autofocus
                />

                <!-- Controls inside input: Loading, Clear, Shortcut -->
                <div class="flex items-center gap-2 pl-2">
                    <!-- Loading Indicator -->
                    <div wire:loading wire:target="search" class="text-[#081951]">
                        <i class="fas fa-circle-notch fa-spin text-sm"></i>
                    </div>

                    @if(!empty($search))
                        <button
                            type="button"
                            wire:click="clearFilters"
                            class="p-1 rounded-md text-slate-400 hover:text-slate-700 hover:bg-slate-100 transition-colors"
                            title="Effacer la recherche (Echap)"
                        >
                            <i class="fas fa-times text-xs"></i>
                        </button>
                    @else
                        <kbd class="hidden sm:inline-block px-1.5 py-0.5 text-[10px] font-mono text-slate-400 bg-slate-50 rounded border border-slate-200">
                            /
                        </kbd>
                    @endif
                </div>
            </div>

            <!-- Top Right Action Controls -->
            <div class="flex items-center gap-2">
                <a
                    href="/admin"
                    class="inline-flex items-center gap-2 px-3.5 py-2 rounded-xl text-xs font-semibold bg-[#081951] hover:bg-[#0e2454] text-white transition-all shadow-xs"
                    title="Administration documentaire"
                >
                    <i class="fas fa-folder-gear text-xs"></i>
                    <span class="hidden sm:inline">Administration</span>
                </a>
            </div>
        </header>

        <!-- Main Body Flow -->
        <div class="flex-1 px-4 sm:px-6 lg:px-8 py-6 max-w-6xl w-full mx-auto space-y-6">
            <!-- Hero Section Dédiée à l'Entreprise Confiance -->
            <div class="rounded-2xl bg-white border border-slate-200 p-6 sm:p-8 shadow-xs">
                <div class="max-w-3xl">
                    <!-- Eyebrow Badges -->
                    <div class="flex flex-wrap items-center gap-2 mb-3">
                        <x-saasover.verified-badge text="Base Documentaire & Procédures Officielles" />
                        <span class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full text-xs font-medium bg-slate-100 text-slate-700 border border-slate-200">
                            Entreprise Confiance
                        </span>
                    </div>

                    <!-- Hero Title -->
                    <h1 class="text-2xl sm:text-3xl md:text-4xl font-extrabold text-slate-900 tracking-tight leading-tight font-display">
                        Documentation & procédures internes <br class="hidden sm:inline" />
                        <span class="text-[#081951]">Entreprise Confiance</span>
                    </h1>

                    <p class="mt-3 text-xs sm:text-sm text-slate-600 leading-relaxed font-sans">
                        Recherchez instantanément parmi l'ensemble des directives, modes opératoires validés, guides RH, protocoles IT et démarches de l'entreprise. <strong class="text-slate-900 font-semibold">{{ $totalOptionsCount }} procédures vérifiées</strong> actuellement indexées.
                    </p>

                    <!-- Trust Chips -->
                    <div class="mt-5 flex flex-wrap items-center gap-2 text-xs">
                        <x-saasover.hero-float
                            title="Recherche Instantanée"
                            subtitle="Plein texte dans les documents"
                        />
                        <x-saasover.hero-float
                            title="Documents Certifiés"
                            subtitle="Validés par les directions métiers"
                        />
                        <x-saasover.hero-float
                            title="Accès Direct"
                            subtitle="Liens rapides vers les applications"
                        />
                    </div>
                </div>
            </div>

            <!-- Metrics Strip -->
            <div class="grid grid-cols-2 md:grid-cols-4 gap-3">
                <div class="p-3.5 rounded-xl bg-white border border-slate-200 shadow-2xs">
                    <div class="flex items-center justify-between text-slate-500 text-xs mb-1">
                        <span>Dossiers</span>
                        <i class="fas fa-folder-closed text-[#081951] text-xs"></i>
                    </div>
                    <div class="text-xl font-bold text-slate-900 font-display">{{ $totalTopicsCount }}</div>
                    <div class="text-[10px] text-slate-500 mt-0.5 font-medium">Indexés dans la base</div>
                </div>

                <div class="p-3.5 rounded-xl bg-white border border-slate-200 shadow-2xs">
                    <div class="flex items-center justify-between text-slate-500 text-xs mb-1">
                        <span>Procédures</span>
                        <i class="fas fa-file-lines text-[#081951] text-xs"></i>
                    </div>
                    <div class="text-xl font-bold text-[#081951] font-display">{{ $totalOptionsCount }}</div>
                    <div class="text-[10px] text-slate-500 mt-0.5">Modes opératoires</div>
                </div>

                <div class="p-3.5 rounded-xl bg-white border border-slate-200 shadow-2xs">
                    <div class="flex items-center justify-between text-slate-500 text-xs mb-1">
                        <span>Vélocité</span>
                        <i class="fas fa-bolt text-slate-400 text-xs"></i>
                    </div>
                    <div class="text-xl font-bold text-slate-900 font-display">&lt; 80ms</div>
                    <div class="text-[10px] text-slate-500 mt-0.5 font-medium">Recherche plein texte</div>
                </div>

                <div class="p-3.5 rounded-xl bg-white border border-slate-200 shadow-2xs">
                    <div class="flex items-center justify-between text-slate-500 text-xs mb-1">
                        <span>Directions</span>
                        <i class="fas fa-sitemap text-[#081951] text-xs"></i>
                    </div>
                    <div class="text-xl font-bold text-slate-900 font-display">100%</div>
                    <div class="text-[10px] text-slate-500 mt-0.5">RH, IT, Finance & Opérations</div>
                </div>
            </div>

            <!-- ============================================================== -->
            <!-- SECTION HISTORIQUE DE RECHERCHE                                -->
            <!-- ============================================================== -->
            <div class="space-y-2">
                <div class="flex items-center justify-between text-xs text-slate-500">
                    <div class="flex items-center gap-1.5">
                        <i class="fas fa-clock-rotate-left text-slate-400 text-xs"></i>
                        <span class="font-mono text-[10px] uppercase tracking-wider text-slate-500 font-bold">
                            @if(!empty($searchHistory))
                                VOS RECHERCHES RÉCENTES
                            @else
                                RECHERCHES SUGGÉRÉES
                            @endif
                        </span>
                    </div>

                    @if(!empty($searchHistory))
                        <button
                            type="button"
                            wire:click="clearHistory"
                            class="text-slate-400 hover:text-slate-700 text-xs font-medium cursor-pointer flex items-center gap-1"
                        >
                            <i class="fas fa-trash-can text-[10px]"></i>
                            <span>Vider l'historique</span>
                        </button>
                    @endif
                </div>

                <div class="flex items-center gap-2 overflow-x-auto pb-1 scrollbar-none">
                    @if(!empty($searchHistory))
                        @foreach($searchHistory as $term)
                            <div class="shrink-0 group flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-xs font-medium bg-white hover:bg-slate-50 border border-slate-200 text-slate-700 shadow-2xs transition-colors">
                                <button
                                    type="button"
                                    wire:click="applyHistory('{{ $term }}')"
                                    class="flex items-center gap-1.5 cursor-pointer"
                                    title="Relancer la recherche « {{ $term }} »"
                                >
                                    <i class="fas fa-magnifying-glass text-slate-400 text-[10px]"></i>
                                    <span>{{ $term }}</span>
                                </button>
                                <button
                                    type="button"
                                    wire:click.stop="removeFromHistory('{{ $term }}')"
                                    class="p-0.5 text-slate-300 hover:text-rose-500 transition-colors cursor-pointer"
                                    title="Supprimer"
                                >
                                    <i class="fas fa-times text-[10px]"></i>
                                </button>
                            </div>
                        @endforeach
                    @else
                        @php
                            $suggestions = [
                                'Télétravail',
                                'Notes de frais',
                                'Congés payés',
                                'VPN & Réseau',
                                'Mutuelle santé',
                                'Procédure achat',
                                'Onboarding'
                            ];
                        @endphp
                        @foreach($suggestions as $suggested)
                            <button
                                type="button"
                                wire:click="applyHistory('{{ $suggested }}')"
                                class="shrink-0 flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-xs font-medium bg-white hover:bg-slate-50 border border-slate-200 text-slate-600 hover:text-[#081951] shadow-2xs transition-colors cursor-pointer"
                            >
                                <i class="fas fa-magnifying-glass text-slate-400 text-[10px]"></i>
                                <span>{{ $suggested }}</span>
                            </button>
                        @endforeach
                    @endif
                </div>
            </div>

            <!-- Active Results Bar & Client-side Accordion Actions -->
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 text-xs text-slate-500 pt-2 border-t border-slate-200">
                <div class="flex items-center gap-2">
                    @if(!empty($search))
                        <span>
                            <strong class="text-slate-900">{{ $topics->count() }}</strong> dossier(s) trouvé(s) pour « <strong class="text-[#081951]">{{ $search }}</strong> »
                        </span>
                    @else
                        <span>
                            Affichage des <strong class="text-slate-900">{{ $topics->count() }}</strong> dossiers documentaires
                        </span>
                    @endif
                </div>

                <div class="flex items-center gap-3 self-end sm:self-auto">
                    @if($topics->isNotEmpty())
                        <button
                            type="button"
                            @click="expandAll([{{ $topics->pluck('id')->implode(',') }}])"
                            class="text-xs font-medium text-slate-600 hover:text-[#081951] transition-colors cursor-pointer"
                        >
                            Tout déplier
                        </button>
                        <span class="text-slate-300">|</span>
                        <button
                            type="button"
                            @click="collapseAll()"
                            class="text-xs font-medium text-slate-600 hover:text-[#081951] transition-colors cursor-pointer"
                        >
                            Tout replier
                        </button>
                    @endif

                    @if(!empty($search))
                        <span class="text-slate-300">|</span>
                        <button
                            type="button"
                            wire:click="clearFilters"
                            class="text-xs font-medium text-rose-600 hover:text-rose-700 hover:underline cursor-pointer"
                        >
                            Effacer la recherche
                        </button>
                    @endif
                </div>
            </div>

            <!-- Topics & Procedures Cards List -->
            <div class="space-y-3.5">
                @forelse($topics as $topic)
                    <div
                        data-topic-id="{{ $topic->id }}"
                        id="procedure-{{ $topic->id }}"
                        class="bg-white rounded-xl border border-slate-200 shadow-xs hover:border-slate-300 transition-all duration-150 overflow-hidden"
                    >
                        <!-- Topic Header (Accordion Trigger) -->
                        <div
                            @click="toggleTopic({{ $topic->id }})"
                            class="p-4 sm:p-5 cursor-pointer flex items-start justify-between gap-4 select-none hover:bg-slate-50/60 transition-colors"
                        >
                            <div class="flex items-start gap-3.5">
                                <!-- Confiance Mark Icon -->
                                <div class="shrink-0 flex flex-col items-center gap-1">
                                    <x-saasover.mark size="size-9" />
                                    <span class="text-[10px] font-mono text-slate-400">
                                        #{{ str_pad($topic->id, 2, '0', STR_PAD_LEFT) }}
                                    </span>
                                </div>

                                <div>
                                    <div class="flex flex-wrap items-center gap-2 mb-1">
                                        <h2 class="text-base font-bold text-slate-900 font-display">
                                            {!! $this->highlightText($topic->title) !!}
                                        </h2>
                                        <x-saasover.verified-badge compact="true" text="Document validé Confiance" />
                                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[11px] font-medium bg-slate-100 text-slate-600 border border-slate-200">
                                            {{ $topic->options->count() }} démarche(s)
                                        </span>
                                    </div>

                                    @if($topic->description)
                                        <p class="text-xs sm:text-sm text-slate-600 leading-relaxed font-sans">
                                            {!! $this->highlightText($topic->description) !!}
                                        </p>
                                    @endif
                                </div>
                            </div>

                            <!-- Right Controls: Share link & Chevron -->
                            <div class="shrink-0 flex items-center gap-2 pt-1">
                                <button
                                    type="button"
                                    @click.stop="copyTopicLink({{ $topic->id }})"
                                    class="p-1.5 rounded-lg text-slate-400 hover:text-[#081951] hover:bg-slate-100 transition-colors cursor-pointer"
                                    title="Copier le lien direct vers ce document"
                                >
                                    <template x-if="copiedTopicId === {{ $topic->id }}">
                                        <span class="text-[11px] font-bold text-[#081951]">Lien copié !</span>
                                    </template>
                                    <template x-if="copiedTopicId !== {{ $topic->id }}">
                                        <i class="fas fa-link text-xs"></i>
                                    </template>
                                </button>

                                <div class="text-slate-400">
                                    <i
                                        class="fas fa-chevron-down text-xs transform transition-transform duration-200"
                                        :class="isTopicOpen({{ $topic->id }}) ? 'rotate-180 text-[#081951]' : 'text-slate-400'"
                                    ></i>
                                </div>
                            </div>
                        </div>

                        <!-- Accordion Expansion: Detailed Procedures & Documents -->
                        <div
                            x-show="isTopicOpen({{ $topic->id }})"
                            x-transition:enter="transition ease-out duration-150"
                            x-transition:enter-start="opacity-0 -translate-y-1"
                            x-transition:enter-end="opacity-100 translate-y-0"
                            x-transition:leave="transition ease-in duration-100"
                            x-transition:leave-start="opacity-100 translate-y-0"
                            x-transition:leave-end="opacity-0 -translate-y-1"
                            class="border-t border-slate-100 bg-[#f8fafc] p-4 sm:p-5 space-y-3"
                        >
                            @forelse($topic->options as $option)
                                <div class="bg-white rounded-lg p-4 border border-slate-200 shadow-2xs">
                                    <div class="flex items-start justify-between gap-3 mb-2">
                                        <h3 class="text-sm font-bold text-slate-900 flex items-center gap-2 font-display">
                                            <span class="shrink-0 size-2 rounded-full bg-[#081951]"></span>
                                            <span>{!! $this->highlightText($option->title) !!}</span>
                                        </h3>

                                        <span class="shrink-0 inline-flex items-center px-2 py-0.5 rounded text-[10px] font-semibold uppercase tracking-wider bg-slate-100 text-slate-700 border border-slate-200">
                                            {{ match($option->type) {
                                                'faq' => 'Procédure Officielle',
                                                'link' => 'Outil / Service Métier',
                                                'download' => 'Document à télécharger',
                                                'contact' => 'Référent Interne',
                                                default => $option->type,
                                            } }}
                                        </span>
                                    </div>

                                    @if($option->content)
                                        <div class="text-xs sm:text-sm text-slate-600 leading-relaxed space-y-1.5 font-sans">
                                            {!! nl2br($this->highlightText($option->content)) !!}
                                        </div>
                                    @endif

                                    @if($option->action_url)
                                        <div class="mt-3 pt-2.5 border-t border-slate-100 flex items-center justify-end">
                                            <a
                                                href="{{ $option->action_url }}"
                                                target="_blank"
                                                rel="noopener noreferrer"
                                                class="group inline-flex items-center gap-1.5 bg-[#081951] hover:bg-[#0e2454] text-white font-medium px-3.5 py-1.5 rounded-lg text-xs transition-colors shadow-2xs"
                                            >
                                                @if($option->type === 'download')
                                                    <i class="fas fa-download text-xs"></i>
                                                    <span>Télécharger le document</span>
                                                @elseif($option->type === 'contact')
                                                    <i class="fas fa-phone text-xs"></i>
                                                    <span>Joindre le référent</span>
                                                @else
                                                    <span>Accéder à l'application</span>
                                                    <i class="fas fa-arrow-right text-xs group-hover:translate-x-0.5 transition-transform"></i>
                                                @endif
                                            </a>
                                        </div>
                                    @endif
                                </div>
                            @empty
                                <p class="text-xs text-slate-400 italic py-2">
                                    Aucun texte ou document détaillé n’est encore renseigné pour cette thématique.
                                </p>
                            @endforelse
                        </div>
                    </div>
                @empty
                    <!-- Empty State -->
                    <div class="bg-white rounded-2xl border border-slate-200 p-8 sm:p-12 text-center max-w-lg mx-auto shadow-xs">
                        <div class="size-12 mx-auto mb-3 rounded-xl bg-slate-100 text-slate-500 flex items-center justify-center text-xl">
                            <i class="fas fa-magnifying-glass"></i>
                        </div>
                        <h3 class="text-base font-bold text-slate-900 font-display">Aucun document correspondant</h3>
                        <p class="mt-1.5 text-xs text-slate-500 leading-relaxed font-sans">
                            Aucun document ou texte ne contient le terme « <span class="font-semibold text-[#081951]">{{ $search }}</span> ». Essayez avec un mot-clé plus général.
                        </p>
                        <div class="mt-5 flex justify-center">
                            <button
                                type="button"
                                wire:click="clearFilters"
                                class="px-4 py-2 rounded-xl text-xs font-semibold bg-[#081951] text-white hover:bg-[#0e2454] transition-colors shadow-xs cursor-pointer"
                            >
                                Réinitialiser la recherche
                            </button>
                        </div>
                    </div>
                @endforelse
            </div>

            <!-- Call To Action Banner -->
            <div class="rounded-2xl bg-[#081951] p-6 sm:p-8 text-white text-center shadow-xs">
                <h2 class="text-xl sm:text-2xl font-bold mb-2 font-display">
                    Un document manque ou doit être actualisé ?
                </h2>
                <p class="text-slate-300 text-xs sm:text-sm max-w-xl mx-auto mb-6 leading-relaxed font-sans">
                    Les référents de pôle et gestionnaires documentaires de l'entreprise Confiance peuvent actualiser les modes opératoires et ajouter des documents directement depuis le panneau d'administration.
                </p>
                <div class="flex flex-wrap justify-center gap-3">
                    <a
                        href="/admin/topics"
                        class="bg-white text-[#081951] hover:bg-slate-100 font-bold px-5 py-2 rounded-xl transition-colors shadow-xs inline-flex items-center gap-2 text-xs sm:text-sm"
                    >
                        <i class="fas fa-plus text-xs"></i>
                        <span>Gérer les documents</span>
                    </a>
                    <a
                        href="{{ route('contact') }}"
                        class="bg-[#0e2454] hover:bg-[#143673] border border-slate-600 text-white font-semibold px-5 py-2 rounded-xl transition-colors inline-flex items-center gap-2 text-xs sm:text-sm"
                    >
                        <i class="fas fa-paper-plane text-xs"></i>
                        <span>Signaler une mise à jour</span>
                    </a>
                </div>
            </div>

            <!-- Footer -->
            <footer class="pt-6 pb-6 border-t border-slate-200 text-xs text-slate-500 flex flex-col sm:flex-row items-center justify-between gap-4">
                <div class="flex items-center gap-3">
                    <x-saasover.mark size="size-5" />
                    <span>&copy; {{ date('Y') }} Entreprise Confiance &bull; NvonchiSearch &bull; Base documentaire et procédures internes</span>
                </div>
                <div class="flex items-center gap-5 font-medium text-slate-600">
                    <a href="{{ route('home') }}" class="hover:text-[#081951] transition-colors">Accueil</a>
                    <a href="{{ route('about') }}" class="hover:text-[#081951] transition-colors">À propos</a>
                    <a href="{{ route('contact') }}" class="hover:text-[#081951] transition-colors">Contact Support</a>
                    <a href="/admin" class="hover:text-[#081951] transition-colors">Administration</a>
                </div>
            </footer>
        </div>
    </main>
</div>
