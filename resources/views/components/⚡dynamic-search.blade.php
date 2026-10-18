<?php

use App\Models\Document;
use App\Models\DocumentSection;
use App\Models\Topic;
use App\Services\PdfParserService;
use Illuminate\Database\Eloquent\Collection;
use Livewire\Attributes\Url;
use Livewire\Component;

new class extends Component
{
    /**
     * @var array{total_topics?: int, total_options?: int, total_documents?: int, total_sections?: int}
     */
    public array $stats = [];

    #[Url(as: 'q', history: true)]
    public string $search = '';

    /**
     * Filter tab: 'all', 'pdf', 'guides'
     */
    #[Url(as: 'type')]
    public string $activeTab = 'all';

    /**
     * Language filter: 'all', 'fr', 'en'
     */
    #[Url(as: 'lang')]
    public string $docLanguage = 'all';

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

    public function setTab(string $tab): void
    {
        $this->activeTab = $tab;
    }

    public function setLanguage(string $lang): void
    {
        $this->docLanguage = $lang;
    }

    public function switchAppLocale(string $locale): void
    {
        if (in_array($locale, ['fr', 'en'])) {
            session(['locale' => $locale]);
            app()->setLocale($locale);
        }
    }

    /**
     * Save non-empty search terms into user search history.
     */
    public function recordSearch(): void
    {
        $term = trim($this->search);
        if (mb_strlen($term) >= 2) {
            $history = session('search_history', []);
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

        $pattern = '/(' . preg_quote(e($term), '/') . ')/iu';

        return preg_replace(
            $pattern,
            '<mark class="bg-[#e2e8f4] text-[#081951] px-1 py-0.5 rounded font-semibold">$1</mark>',
            $escaped
        ) ?? $escaped;
    }

    /**
     * Extract context snippet around keyword.
     */
    public function getSnippet(string $content): string
    {
        $result = PdfParserService::extractSnippet($content, $this->search, 150);

        return $this->highlightText($result['snippet']);
    }

    /**
     * @return array{
     *     topics: Collection<int, Topic>|\Illuminate\Support\Collection<int, Topic>,
     *     documents: Collection<int, Document>|\Illuminate\Support\Collection<int, Document>,
     *     totalTopicsCount: int,
     *     totalOptionsCount: int,
     *     totalDocumentsCount: int,
     *     totalSectionsCount: int,
     *     searchHistory: list<string>
     * }
     */
    public function with(): array
    {
        $trimmedSearch = trim($this->search);

        // Fetch documents matching search query and language filter
        $documentsQuery = Document::query()
            ->published()
            ->with(['sections']);

        if ($this->docLanguage !== 'all') {
            $documentsQuery->where('language', $this->docLanguage);
        }

        if ($trimmedSearch !== '') {
            // Priority: direct matches in title, reference, or section content
            $documentsQuery->search($trimmedSearch);
        }

        $documents = $documentsQuery->latest()->get();

        // Fetch topics matching search query (only when no strict EN filter active)
        if ($this->docLanguage === 'en') {
            $topics = new Collection();
        } else {
            if ($trimmedSearch !== '') {
                try {
                    $topics = Topic::search($trimmedSearch)
                        ->query(function ($query): void {
                            $query->published()
                                ->with([
                                    'options' => fn ($q) => $q->published()->orderBy('order'),
                                ]);
                        })
                        ->get();
                } catch (\Throwable $e) {
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
                $topics = Topic::query()
                    ->published()
                    ->with([
                        'options' => fn ($q) => $q->published()->orderBy('order'),
                    ])
                    ->orderBy('order')
                    ->get();
            }
        }

        $totalTopicsCount = $this->stats['total_topics'] ?? Topic::query()->published()->count();
        $totalOptionsCount = $this->stats['total_options'] ?? \App\Models\TopicOption::query()->published()->count();
        $totalDocumentsCount = $this->stats['total_documents'] ?? Document::query()->published()->count();
        $totalSectionsCount = $this->stats['total_sections'] ?? DocumentSection::query()->count();

        return [
            'topics' => $topics,
            'documents' => $documents,
            'totalTopicsCount' => $totalTopicsCount,
            'totalOptionsCount' => $totalOptionsCount,
            'totalDocumentsCount' => $totalDocumentsCount,
            'totalSectionsCount' => $totalSectionsCount,
            'searchHistory' => $this->searchHistory,
        ];
    }
};
?>

<div
    x-data="{
        openTopics: {},
        openDocuments: {},
        copiedTopicId: null,
        copiedDocId: null,
        mobileSidebarOpen: false,

        isTopicOpen(id) {
            return !!this.openTopics[id];
        },

        toggleTopic(id) {
            this.openTopics[id] = !this.isTopicOpen(id);
        },

        isDocOpen(id) {
            return !!this.openDocuments[id];
        },

        toggleDoc(id) {
            this.openDocuments[id] = !this.isDocOpen(id);
        },

        expandAll(topicIds, docIds) {
            (topicIds || []).forEach(id => { this.openTopics[id] = true; });
            (docIds || []).forEach(id => { this.openDocuments[id] = true; });
        },

        collapseAll() {
            this.openTopics = {};
            this.openDocuments = {};
        },

        copyTopicLink(id) {
            const url = window.location.origin + window.location.pathname + '#procedure-' + id;
            navigator.clipboard.writeText(url);
            this.copiedTopicId = id;
            setTimeout(() => { this.copiedTopicId = null; }, 2000);
        },

        copyDocLink(id) {
            const url = window.location.origin + window.location.pathname + '#doc-' + id;
            navigator.clipboard.writeText(url);
            this.copiedDocId = id;
            setTimeout(() => { this.copiedDocId = null; }, 2000);
        },

        init() {
            const currentSearch = ($wire.search || '').trim();
            if (currentSearch !== '') {
                @foreach($topics as $t)
                    this.openTopics[{{ $t->id }}] = true;
                @endforeach
                @foreach($documents as $d)
                    this.openDocuments[{{ $d->id }}] = true;
                @endforeach
            } else {
                @if($documents->first())
                    this.openDocuments[{{ $documents->first()->id }}] = true;
                @endif
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
                        document.querySelectorAll('[data-doc-id]').forEach(el => {
                            const id = parseInt(el.getAttribute('data-doc-id'), 10);
                            if (id) this.openDocuments[id] = true;
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

        <div class="flex-1 overflow-y-auto px-4 py-5 space-y-6">
            <!-- Language Switcher Mobile -->
            <div class="flex items-center justify-between p-3 rounded-xl bg-slate-50 border border-slate-200">
                <span class="text-xs font-semibold text-slate-700">Langue / Language :</span>
                <div class="flex items-center gap-1">
                    <button
                        type="button"
                        wire:click="switchAppLocale('fr')"
                        class="px-2.5 py-1 rounded text-xs font-bold {{ app()->getLocale() === 'fr' ? 'bg-[#081951] text-white' : 'bg-white text-slate-600 border border-slate-200' }}"
                    >
                        FR
                    </button>
                    <button
                        type="button"
                        wire:click="switchAppLocale('en')"
                        class="px-2.5 py-1 rounded text-xs font-bold {{ app()->getLocale() === 'en' ? 'bg-[#081951] text-white' : 'bg-white text-slate-600 border border-slate-200' }}"
                    >
                        EN
                    </button>
                </div>
            </div>

            <!-- Stats Mini Card -->
            <div class="grid grid-cols-2 gap-2">
                <div class="p-3 rounded-xl bg-slate-50 border border-slate-200">
                    <div class="text-lg font-bold text-[#081951] font-display">{{ $totalDocumentsCount }}</div>
                    <div class="text-[11px] text-slate-500">{{ __('portal.pdf_tab') }}</div>
                </div>
                <div class="p-3 rounded-xl bg-slate-50 border border-slate-200">
                    <div class="text-lg font-bold text-slate-900 font-display">{{ $totalSectionsCount }}</div>
                    <div class="text-[11px] text-slate-500">{{ __('portal.section') }}s</div>
                </div>
            </div>

            <!-- Navigation Links -->
            <div class="space-y-1">
                <div class="text-[10px] font-mono font-bold uppercase tracking-wider text-slate-400 px-2 pb-1">
                    {{ __('portal.navigation') }}
                </div>

                <button
                    type="button"
                    wire:click="clearFilters"
                    @click="mobileSidebarOpen = false"
                    class="w-full flex items-center justify-between px-3.5 py-2 rounded-xl text-sm font-semibold transition-all {{ empty($search) ? 'bg-[#081951] text-white shadow-xs' : 'text-slate-600 hover:text-slate-900 hover:bg-slate-50' }}"
                >
                    <span class="flex items-center gap-2.5">
                        <i class="fas fa-layer-group text-xs"></i>
                        <span>{{ __('portal.all_documents') }}</span>
                    </span>
                    <span class="text-xs px-2 py-0.5 rounded-full {{ empty($search) ? 'bg-white/20 text-white' : 'bg-slate-100 text-slate-700' }}">{{ $totalDocumentsCount }}</span>
                </button>

                <a
                    href="{{ route('about') }}"
                    class="flex items-center gap-2.5 px-3.5 py-2 rounded-xl text-sm font-medium text-slate-600 hover:text-[#081951] hover:bg-slate-50 transition-colors"
                >
                    <i class="fas fa-circle-info text-xs text-slate-400"></i>
                    <span>{{ __('portal.about') }}</span>
                </a>

                <a
                    href="{{ route('contact') }}"
                    class="flex items-center gap-2.5 px-3.5 py-2 rounded-xl text-sm font-medium text-slate-600 hover:text-[#081951] hover:bg-slate-50 transition-colors"
                >
                    <i class="fas fa-headset text-xs text-slate-400"></i>
                    <span>{{ __('portal.support') }}</span>
                </a>

                <a
                    href="/admin/documents"
                    class="flex items-center gap-2.5 px-3.5 py-2 rounded-xl text-sm font-medium text-slate-600 hover:text-[#081951] hover:bg-slate-50 transition-colors"
                >
                    <i class="fas fa-file-pdf text-xs text-slate-400"></i>
                    <span>{{ __('portal.admin') }}</span>
                </a>
            </div>

            <!-- Historique des Recherches (Mobile) -->
            <div class="space-y-2 pt-2 border-t border-slate-200">
                <div class="flex items-center justify-between px-2 pb-1">
                    <div class="flex items-center gap-1.5 text-xs text-slate-500 font-medium">
                        <i class="fas fa-clock-rotate-left text-slate-400 text-xs"></i>
                        <span class="text-[10px] font-mono font-bold uppercase tracking-wider">{{ __('portal.history') }}</span>
                    </div>
                    @if(!empty($searchHistory))
                        <button
                            type="button"
                            wire:click="clearHistory"
                            class="text-slate-400 hover:text-slate-700 text-xs font-medium"
                        >
                            {{ __('portal.clear') }}
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
                        <p class="text-xs text-slate-500">{{ __('portal.no_history') }}</p>
                    </div>
                @endif
            </div>
        </div>
    </aside>

    <!-- ============================================================== -->
    <!-- DESKTOP LEFT SIDEBAR                                          -->
    <!-- ============================================================== -->
    <aside class="w-72 lg:w-80 shrink-0 hidden md:flex flex-col h-screen sticky top-0 bg-white border-r border-slate-200 z-30 shadow-xs">
        <div class="h-16 px-6 border-b border-slate-200 flex items-center justify-between">
            <x-saasover.logo size="md" />
            <span class="size-2 rounded-full bg-[#081951]" title="{{ __('portal.portal_active') }}"></span>
        </div>

        <div class="flex-1 overflow-y-auto px-4 py-5 space-y-6">
            <!-- Language Switcher in Desktop Sidebar -->
            <div class="p-3 rounded-xl bg-slate-50 border border-slate-200 flex items-center justify-between">
                <span class="text-xs font-semibold text-slate-700 flex items-center gap-1.5">
                    <i class="fas fa-globe text-[#081951] text-xs"></i>
                    <span>Langue / Language</span>
                </span>
                <div class="flex items-center gap-1">
                    <button
                        type="button"
                        wire:click="switchAppLocale('fr')"
                        class="px-2.5 py-0.5 rounded text-xs font-bold transition-colors cursor-pointer {{ app()->getLocale() === 'fr' ? 'bg-[#081951] text-white shadow-2xs' : 'bg-white text-slate-600 hover:text-[#081951] border border-slate-200' }}"
                        title="Afficher en Français"
                    >
                        FR
                    </button>
                    <button
                        type="button"
                        wire:click="switchAppLocale('en')"
                        class="px-2.5 py-0.5 rounded text-xs font-bold transition-colors cursor-pointer {{ app()->getLocale() === 'en' ? 'bg-[#081951] text-white shadow-2xs' : 'bg-white text-slate-600 hover:text-[#081951] border border-slate-200' }}"
                        title="Display in English"
                    >
                        EN
                    </button>
                </div>
            </div>

            <!-- Portal Info Box -->
            <div class="p-3.5 rounded-xl bg-slate-50 border border-slate-200 space-y-2.5">
                <div class="flex items-center justify-between text-xs text-slate-600 font-medium">
                    <span class="flex items-center gap-1.5 text-slate-900 font-semibold font-display">
                        <i class="fas fa-file-pdf text-[#081951]"></i>
                        <span>CS Knowledge Base</span>
                    </span>
                    <span class="text-[10px] font-bold text-[#081951] uppercase tracking-wider bg-[#f1f4fa] border border-[#e2e8f4] px-2 py-0.5 rounded-full">
                        {{ __('portal.certified') }}
                    </span>
                </div>
                <div class="grid grid-cols-2 gap-2 text-center">
                    <div class="p-2 rounded-lg bg-white border border-slate-200">
                        <div class="text-base font-bold text-[#081951] font-display">{{ $totalDocumentsCount }}</div>
                        <div class="text-[10px] text-slate-500 uppercase font-semibold">{{ __('portal.pdf_tab') }}</div>
                    </div>
                    <div class="p-2 rounded-lg bg-white border border-slate-200">
                        <div class="text-base font-bold text-slate-900 font-display">{{ $totalSectionsCount }}</div>
                        <div class="text-[10px] text-slate-500 uppercase font-semibold">{{ __('portal.section') }}s</div>
                    </div>
                </div>
            </div>

            <!-- Navigation Links -->
            <div class="space-y-1">
                <div class="text-[10px] font-mono font-bold uppercase tracking-wider text-slate-400 px-2 pb-1">
                    {{ __('portal.navigation') }}
                </div>

                <button
                    type="button"
                    wire:click="clearFilters"
                    class="w-full flex items-center justify-between px-3.5 py-2 rounded-xl text-sm font-semibold transition-all cursor-pointer {{ empty($search) ? 'bg-[#081951] text-white shadow-xs' : 'text-slate-600 hover:text-slate-900 hover:bg-slate-50' }}"
                >
                    <span class="flex items-center gap-2.5">
                        <i class="fas fa-layer-group text-xs"></i>
                        <span>{{ __('portal.all_documents') }}</span>
                    </span>
                    <span class="text-xs px-2 py-0.5 rounded-full {{ empty($search) ? 'bg-white/20 text-white' : 'bg-slate-100 text-slate-700' }}">{{ $totalDocumentsCount }}</span>
                </button>

                <a
                    href="{{ route('about') }}"
                    class="flex items-center gap-2.5 px-3.5 py-2 rounded-xl text-sm font-medium text-slate-600 hover:text-[#081951] hover:bg-slate-50 transition-colors"
                >
                    <i class="fas fa-circle-info text-xs text-slate-400"></i>
                    <span>{{ __('portal.about') }}</span>
                </a>

                <a
                    href="{{ route('contact') }}"
                    class="flex items-center gap-2.5 px-3.5 py-2 rounded-xl text-sm font-medium text-slate-600 hover:text-[#081951] hover:bg-slate-50 transition-colors"
                >
                    <i class="fas fa-headset text-xs text-slate-400"></i>
                    <span>{{ __('portal.support') }}</span>
                </a>

                <a
                    href="/admin/documents"
                    class="flex items-center gap-2.5 px-3.5 py-2 rounded-xl text-sm font-medium text-slate-600 hover:text-[#081951] hover:bg-slate-50 transition-colors"
                >
                    <i class="fas fa-file-arrow-up text-xs text-[#081951]"></i>
                    <span>{{ __('portal.add_pdf') }}</span>
                </a>
            </div>

            <!-- Historique des Recherches (Desktop Sidebar) -->
            <div class="space-y-2 pt-2 border-t border-slate-200">
                <div class="flex items-center justify-between px-2 pb-1">
                    <div class="flex items-center gap-1.5 text-xs text-slate-500 font-medium">
                        <i class="fas fa-clock-rotate-left text-slate-400 text-xs"></i>
                        <span class="text-[10px] font-mono font-bold uppercase tracking-wider">{{ __('portal.history') }}</span>
                    </div>
                    @if(!empty($searchHistory))
                        <button
                            type="button"
                            wire:click="clearHistory"
                            class="text-slate-400 hover:text-slate-700 text-xs font-medium cursor-pointer"
                        >
                            {{ __('portal.clear') }}
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
                        <p class="text-xs text-slate-600 font-medium">{{ __('portal.no_history') }}</p>
                    </div>
                @endif
            </div>
        </div>

        <div class="p-3.5 border-t border-slate-200 bg-slate-50 flex items-center justify-between text-xs text-slate-500">
            <span class="flex items-center gap-1.5">
                <span class="size-2 rounded-full bg-[#081951]"></span>
                <span>CS Knowledge</span>
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
                <div class="pr-2.5 flex items-center pointer-events-none text-[#081951]">
                    <svg viewBox="0 0 20 20" fill="currentColor" class="size-4.5">
                        <path fill-rule="evenodd" d="M9 3.5a5.5 5.5 0 100 11 5.5 5.5 0 000-11zM2 9a7 7 0 1112.452 4.391l3.328 3.329a.75.75 0 11-1.06 1.06l-3.329-3.328A7 7 0 012 9z" clip-rule="evenodd" />
                    </svg>
                </div>

                <input
                    x-ref="searchInput"
                    type="text"
                    wire:model.live.debounce.250ms="search"
                    @keydown.enter="recordSearch"
                    placeholder="{{ __('portal.search_placeholder') }}"
                    class="w-full bg-transparent text-sm sm:text-base text-slate-900 placeholder-slate-400 focus:outline-hidden font-sans"
                    autofocus
                />

                <div class="flex items-center gap-2 pl-2">
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
                <!-- Direct Language Switcher in Header -->
                <div class="hidden sm:flex items-center rounded-lg border border-slate-200 p-0.5 bg-slate-50 text-xs font-semibold">
                    <button
                        type="button"
                        wire:click="switchAppLocale('fr')"
                        class="px-2 py-0.5 rounded transition-colors cursor-pointer {{ app()->getLocale() === 'fr' ? 'bg-[#081951] text-white' : 'text-slate-600 hover:text-[#081951]' }}"
                    >
                        FR
                    </button>
                    <button
                        type="button"
                        wire:click="switchAppLocale('en')"
                        class="px-2 py-0.5 rounded transition-colors cursor-pointer {{ app()->getLocale() === 'en' ? 'bg-[#081951] text-white' : 'text-slate-600 hover:text-[#081951]' }}"
                    >
                        EN
                    </button>
                </div>

                <a
                    href="/admin/documents"
                    class="inline-flex items-center gap-2 px-3.5 py-2 rounded-xl text-xs font-semibold bg-[#081951] hover:bg-[#0e2454] text-white transition-all shadow-xs"
                    title="{{ __('portal.add_pdf') }}"
                >
                    <i class="fas fa-upload text-xs"></i>
                    <span class="hidden sm:inline">{{ __('portal.add_pdf') }}</span>
                </a>
            </div>
        </header>

        <!-- Main Body Flow -->
        <div class="flex-1 px-4 sm:px-6 lg:px-8 py-6 max-w-6xl w-full mx-auto space-y-6">
            <!-- Hero Section Dédiée à CS Knowledge -->
            <div class="rounded-2xl bg-white border border-slate-200 p-6 sm:p-8 shadow-xs">
                <div class="max-w-3xl">
                    <div class="flex flex-wrap items-center gap-2 mb-3">
                        <x-saasover.verified-badge text="CS Knowledge &bull; Référentiel Documentaire" />
                        <span class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full text-xs font-medium bg-slate-100 text-slate-700 border border-slate-200">
                            Entreprise Confiance
                        </span>
                        <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-xs font-semibold bg-[#f1f4fa] text-[#081951] border border-[#e2e8f4]">
                            <i class="fas fa-language text-xs"></i>
                            Bilingue FR / EN
                        </span>
                    </div>

                    <h1 class="text-2xl sm:text-3xl md:text-4xl font-extrabold text-slate-900 tracking-tight leading-tight font-display">
                        CS Knowledge <br class="hidden sm:inline" />
                        <span class="text-[#081951]">Moteur de Recherche & Références Documentaires</span>
                    </h1>

                    <p class="mt-3 text-xs sm:text-sm text-slate-600 leading-relaxed font-sans">
                        Retrouvez immédiatement les documents spécifiques directement liés à vos recherches. Chaque paragraphe extrait est accompagné de la <strong>référence exacte du document source</strong> et de sa section parente.
                    </p>
                </div>
            </div>

            <!-- Filter Tabs (Tous / Documents PDF / Fiches Métiers) & Language Filter -->
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 border-b border-slate-200 pb-3">
                <div class="flex items-center gap-2 overflow-x-auto pb-1 sm:pb-0">
                    <button
                        type="button"
                        wire:click="setTab('all')"
                        class="px-3 py-1.5 rounded-lg text-xs font-semibold transition-colors cursor-pointer {{ $activeTab === 'all' ? 'bg-[#081951] text-white shadow-2xs' : 'bg-white hover:bg-slate-50 text-slate-600 border border-slate-200' }}"
                    >
                        <i class="fas fa-layer-group text-[10px] mr-1"></i>
                        <span>{{ __('portal.all_tab') }}</span>
                        <span class="ml-1.5 text-[10px] px-1.5 py-0.2 rounded-full {{ $activeTab === 'all' ? 'bg-white/20 text-white' : 'bg-slate-100 text-slate-600' }}">{{ $documents->count() + $topics->count() }}</span>
                    </button>

                    <button
                        type="button"
                        wire:click="setTab('pdf')"
                        class="px-3 py-1.5 rounded-lg text-xs font-semibold transition-colors cursor-pointer {{ $activeTab === 'pdf' ? 'bg-[#081951] text-white shadow-2xs' : 'bg-white hover:bg-slate-50 text-slate-600 border border-slate-200' }}"
                    >
                        <i class="fas fa-file-pdf text-[10px] mr-1 text-rose-500"></i>
                        <span>{{ __('portal.pdf_tab') }}</span>
                        <span class="ml-1.5 text-[10px] px-1.5 py-0.2 rounded-full {{ $activeTab === 'pdf' ? 'bg-white/20 text-white' : 'bg-slate-100 text-slate-600' }}">{{ $documents->count() }}</span>
                    </button>

                    <button
                        type="button"
                        wire:click="setTab('guides')"
                        class="px-3 py-1.5 rounded-lg text-xs font-semibold transition-colors cursor-pointer {{ $activeTab === 'guides' ? 'bg-[#081951] text-white shadow-2xs' : 'bg-white hover:bg-slate-50 text-slate-600 border border-slate-200' }}"
                    >
                        <i class="fas fa-folder-open text-[10px] mr-1 text-blue-500"></i>
                        <span>{{ __('portal.guides_tab') }}</span>
                        <span class="ml-1.5 text-[10px] px-1.5 py-0.2 rounded-full {{ $activeTab === 'guides' ? 'bg-white/20 text-white' : 'bg-slate-100 text-slate-600' }}">{{ $topics->count() }}</span>
                    </button>
                </div>

                <!-- Document Language Filter -->
                <div class="flex items-center gap-1.5 self-start sm:self-auto text-xs">
                    <span class="text-slate-500 font-medium mr-1">{{ __('portal.filter_lang') }} :</span>
                    <button
                        type="button"
                        wire:click="setLanguage('all')"
                        class="px-2.5 py-1 rounded-md font-semibold transition-colors cursor-pointer {{ $docLanguage === 'all' ? 'bg-slate-800 text-white' : 'bg-white text-slate-600 hover:bg-slate-100 border border-slate-200' }}"
                    >
                        {{ __('portal.all_languages') }}
                    </button>
                    <button
                        type="button"
                        wire:click="setLanguage('fr')"
                        class="px-2.5 py-1 rounded-md font-semibold transition-colors cursor-pointer {{ $docLanguage === 'fr' ? 'bg-[#081951] text-white' : 'bg-white text-slate-600 hover:bg-slate-100 border border-slate-200' }}"
                    >
                        🇫🇷 Français
                    </button>
                    <button
                        type="button"
                        wire:click="setLanguage('en')"
                        class="px-2.5 py-1 rounded-md font-semibold transition-colors cursor-pointer {{ $docLanguage === 'en' ? 'bg-[#081951] text-white' : 'bg-white text-slate-600 hover:bg-slate-100 border border-slate-200' }}"
                    >
                        🇬🇧 English
                    </button>
                </div>
            </div>

            <!-- Historique des Recherches -->
            <div class="space-y-2">
                <div class="flex items-center justify-between text-xs text-slate-500">
                    <div class="flex items-center gap-1.5">
                        <i class="fas fa-clock-rotate-left text-slate-400 text-xs"></i>
                        <span class="font-mono text-[10px] uppercase tracking-wider text-slate-500 font-bold">
                            {{ !empty($searchHistory) ? 'VOS RECHERCHES RÉCENTES' : 'RECHERCHES RECOMMANDÉES' }}
                        </span>
                    </div>

                    @if(!empty($searchHistory))
                        <button
                            type="button"
                            wire:click="clearHistory"
                            class="text-slate-400 hover:text-slate-700 text-xs font-medium cursor-pointer flex items-center gap-1"
                        >
                            <i class="fas fa-trash-can text-[10px]"></i>
                            <span>{{ __('portal.clear') }}</span>
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
                                'Article 4',
                                'CS-RH-2026-001',
                                'Télétravail',
                                'VPN',
                                'Indemnité',
                                'Remote Work',
                                'Cybersecurity',
                                'Budget'
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

            <!-- Active Results Bar -->
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 text-xs text-slate-500 pt-2 border-t border-slate-200">
                <div class="flex items-center gap-2">
                    @if(!empty($search))
                        <span>
                            {{ __('portal.results_for') }} « <strong class="text-[#081951]">{{ $search }}</strong> » :
                            <strong class="text-slate-900">{{ $documents->count() }}</strong> PDF et <strong class="text-slate-900">{{ $topics->count() }}</strong> fiches
                        </span>
                    @else
                        <span>
                            {{ __('portal.displaying') }} <strong class="text-slate-900">{{ $documents->count() }}</strong> documents PDF et <strong class="text-slate-900">{{ $topics->count() }}</strong> fiches
                        </span>
                    @endif
                </div>

                <div class="flex items-center gap-3 self-end sm:self-auto">
                    <button
                        type="button"
                        @click="expandAll([{{ $topics->pluck('id')->implode(',') }}], [{{ $documents->pluck('id')->implode(',') }}])"
                        class="text-xs font-medium text-slate-600 hover:text-[#081951] transition-colors cursor-pointer"
                    >
                        {{ __('portal.expand_all') }}
                    </button>
                    <span class="text-slate-300">|</span>
                    <button
                        type="button"
                        @click="collapseAll()"
                        class="text-xs font-medium text-slate-600 hover:text-[#081951] transition-colors cursor-pointer"
                    >
                        {{ __('portal.collapse_all') }}
                    </button>

                    @if(!empty($search))
                        <span class="text-slate-300">|</span>
                        <button
                            type="button"
                            wire:click="clearFilters"
                            class="text-xs font-medium text-rose-600 hover:text-rose-700 hover:underline cursor-pointer"
                        >
                            {{ __('portal.clear_search') }}
                        </button>
                    @endif
                </div>
            </div>

            <!-- ============================================================== -->
            <!-- 1. SECTION DOCUMENTS PDF (Avec Référence Source & Périphérie)  -->
            <!-- ============================================================== -->
            @if($activeTab === 'all' || $activeTab === 'pdf')
                <div class="space-y-4">
                    <div class="flex items-center justify-between">
                        <div class="flex items-center gap-2 text-xs font-bold uppercase tracking-wider text-slate-700">
                            <i class="fas fa-file-pdf text-rose-600"></i>
                            <span>{{ __('portal.pdf_tab') }} (Documents Spécifiques & Références Sources)</span>
                        </div>
                    </div>

                    @forelse($documents as $doc)
                        @php
                            $pdfUrl = Storage::url($doc->file_path);
                            $matchingSections = !empty($search) 
                                ? $doc->sections->filter(fn ($s) => mb_stripos($s->content, $search) !== false || mb_stripos($s->section_title, $search) !== false || mb_stripos($s->reference_label, $search) !== false)
                                : $doc->sections;
                        @endphp
                        <div
                            data-doc-id="{{ $doc->id }}"
                            id="doc-{{ $doc->id }}"
                            class="bg-white rounded-xl border border-slate-200 shadow-xs hover:border-slate-300 transition-all duration-150 overflow-hidden"
                        >
                            <!-- Document Header Trigger -->
                            <div
                                @click="toggleDoc({{ $doc->id }})"
                                class="p-4 sm:p-5 cursor-pointer flex items-start justify-between gap-4 select-none hover:bg-slate-50/60 transition-colors"
                            >
                                <div class="flex items-start gap-3.5">
                                    <div class="shrink-0 size-11 rounded-xl bg-rose-50 text-rose-600 flex items-center justify-center border border-rose-200 shadow-2xs">
                                        <i class="fas fa-file-pdf text-lg"></i>
                                    </div>

                                    <div>
                                        <div class="flex flex-wrap items-center gap-2 mb-1">
                                            <!-- Official Document Source Reference Code -->
                                            <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-md text-xs font-mono font-bold bg-[#081951] text-white tracking-wide shadow-2xs" title="Référence Source Officielle">
                                                <i class="fas fa-barcode text-[10px]"></i>
                                                {{ $doc->reference_code }}
                                            </span>

                                            <h2 class="text-base font-bold text-slate-900 font-display">
                                                {!! $this->highlightText($doc->title) !!}
                                            </h2>

                                            <span class="inline-flex items-center px-2 py-0.5 rounded text-[11px] font-bold uppercase {{ $doc->language === 'en' ? 'bg-sky-50 text-sky-700 border border-sky-200' : 'bg-slate-100 text-slate-700 border border-slate-200' }}">
                                                {{ strtoupper($doc->language) }}
                                            </span>

                                            @if($doc->department)
                                                <span class="inline-flex items-center px-2 py-0.5 rounded text-[11px] font-semibold bg-[#f1f4fa] text-[#081951] border border-[#e2e8f4]">
                                                    {{ $doc->department }}
                                                </span>
                                            @endif

                                            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[11px] font-medium bg-slate-100 text-slate-600 border border-slate-200">
                                                {{ $doc->page_count }} {{ __('portal.page') }}(s) &bull; {{ $doc->formatted_file_size }}
                                            </span>

                                            @if(!empty($search) && $matchingSections->isNotEmpty())
                                                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[11px] font-bold bg-amber-50 text-amber-800 border border-amber-200">
                                                    {{ $matchingSections->count() }} passage(s) directement lié(s)
                                                </span>
                                            @endif
                                        </div>

                                        @if($doc->description)
                                            <p class="text-xs sm:text-sm text-slate-600 leading-relaxed font-sans">
                                                {!! $this->highlightText($doc->description) !!}
                                            </p>
                                        @endif
                                    </div>
                                </div>

                                <!-- Right Controls -->
                                <div class="shrink-0 flex items-center gap-2 pt-1">
                                    <a
                                        href="{{ $pdfUrl }}"
                                        target="_blank"
                                        @click.stop
                                        class="p-1.5 rounded-lg text-slate-400 hover:text-[#081951] hover:bg-slate-100 transition-colors cursor-pointer"
                                        title="Ouvrir le PDF complet dans un nouvel onglet"
                                    >
                                        <i class="fas fa-arrow-up-right-from-square text-xs"></i>
                                    </a>

                                    <button
                                        type="button"
                                        @click.stop="copyDocLink({{ $doc->id }})"
                                        class="p-1.5 rounded-lg text-slate-400 hover:text-[#081951] hover:bg-slate-100 transition-colors cursor-pointer"
                                        title="Copier le lien direct vers ce document"
                                    >
                                        <template x-if="copiedDocId === {{ $doc->id }}">
                                            <span class="text-[11px] font-bold text-[#081951]">Lien copié !</span>
                                        </template>
                                        <template x-if="copiedDocId !== {{ $doc->id }}">
                                            <i class="fas fa-link text-xs"></i>
                                        </template>
                                    </button>

                                    <div class="text-slate-400">
                                        <i
                                            class="fas fa-chevron-down text-xs transform transition-transform duration-200"
                                            :class="isDocOpen({{ $doc->id }}) ? 'rotate-180 text-[#081951]' : 'text-slate-400'"
                                        ></i>
                                    </div>
                                </div>
                            </div>

                            <!-- Document Sections & Snippets Accordion with Source Reference per Paragraph -->
                            <div
                                x-show="isDocOpen({{ $doc->id }})"
                                x-transition:enter="transition ease-out duration-150"
                                x-transition:enter-start="opacity-0 -translate-y-1"
                                x-transition:enter-end="opacity-100 translate-y-0"
                                x-transition:leave="transition ease-in duration-100"
                                x-transition:leave-start="opacity-100 translate-y-0"
                                x-transition:leave-end="opacity-0 -translate-y-1"
                                class="border-t border-slate-100 bg-[#f8fafc] p-4 sm:p-5 space-y-3"
                            >
                                <div class="text-xs text-slate-500 font-medium flex items-center justify-between pb-1">
                                    <span>
                                        @if(!empty($search))
                                            Passages spécifiques liés à la recherche « <strong class="text-[#081951]">{{ $search }}</strong> » :
                                        @else
                                            Sections et paragraphes indexés du document :
                                        @endif
                                    </span>
                                    <a
                                        href="{{ $pdfUrl }}"
                                        download="{{ $doc->original_filename }}"
                                        class="inline-flex items-center gap-1.5 text-xs text-[#081951] hover:underline font-semibold"
                                    >
                                        <i class="fas fa-download text-[10px]"></i>
                                        <span>{{ __('portal.download_pdf') }}</span>
                                    </a>
                                </div>

                                @forelse($matchingSections as $section)
                                    <div class="bg-white rounded-lg p-4 border border-slate-200 shadow-2xs space-y-2.5">
                                        <!-- Header with Document Source Reference and Parent Section -->
                                        <div class="flex flex-wrap items-center justify-between gap-2 border-b border-slate-100 pb-2">
                                            <div class="flex flex-wrap items-center gap-2 text-xs">
                                                <!-- Explicit Source Document Reference Badge -->
                                                <span class="inline-flex items-center gap-1.5 px-2 py-0.5 rounded bg-slate-100 border border-slate-200 text-slate-700 font-mono text-[11px] font-bold" title="Document Source">
                                                    <i class="fas fa-file-lines text-slate-400"></i>
                                                    <span>{{ $doc->reference_code }}</span>
                                                </span>

                                                <span class="text-slate-300">&bull;</span>

                                                <!-- Parent Section Title -->
                                                <span class="inline-flex items-center gap-1.5 font-bold text-slate-900 font-display">
                                                    <i class="fas fa-folder-open text-[#081951] text-xs"></i>
                                                    <span>{{ $section->section_title ?: 'Section' }}</span>
                                                </span>
                                            </div>

                                            <div class="flex items-center gap-2">
                                                <span class="px-2 py-0.5 rounded text-[10px] font-bold uppercase bg-[#f1f4fa] text-[#081951] border border-[#e2e8f4]">
                                                    {{ __('portal.page') }} {{ $section->page_number }}
                                                </span>
                                                <a
                                                    href="{{ $pdfUrl }}#page={{ $section->page_number }}"
                                                    target="_blank"
                                                    class="inline-flex items-center gap-1 px-2.5 py-1 rounded text-xs font-semibold bg-[#081951] hover:bg-[#0e2454] text-white transition-colors"
                                                    title="Consulter le document source à la page {{ $section->page_number }}"
                                                >
                                                    <i class="fas fa-book-open-reader text-[10px]"></i>
                                                    <span>{{ __('portal.open_page') }} {{ $section->page_number }} &rarr;</span>
                                                </a>
                                            </div>
                                        </div>

                                        <!-- Contextual Periphery Snippet -->
                                        <div class="text-xs sm:text-sm text-slate-700 leading-relaxed font-sans">
                                            @if(!empty($search))
                                                <p class="italic bg-slate-50 p-3 rounded-md border border-slate-200/70">
                                                    « {!! $this->getSnippet($section->content) !!} »
                                                </p>
                                            @else
                                                <p>{!! nl2br(e($section->content)) !!}</p>
                                            @endif
                                        </div>

                                        <!-- Bottom Reference Stamp -->
                                        <div class="pt-1.5 flex items-center justify-between text-[10px] text-slate-400 font-mono">
                                            <span>Réf. complète : {{ $section->reference_label }}</span>
                                            <span>Fichier : {{ $doc->original_filename }}</span>
                                        </div>
                                    </div>
                                @empty
                                    <p class="text-xs text-slate-400 italic py-2">
                                        {{ __('portal.no_results') }}
                                    </p>
                                @endforelse
                            </div>
                        </div>
                    @empty
                        <div class="bg-white rounded-xl border border-slate-200 p-6 text-center text-slate-500 text-xs">
                            {{ __('portal.no_results') }}
                        </div>
                    @endforelse
                </div>
            @endif

            <!-- ============================================================== -->
            <!-- 2. SECTION FICHES MÉTIERS & PROCÉDURES                         -->
            <!-- ============================================================== -->
            @if(($activeTab === 'all' || $activeTab === 'guides') && $topics->isNotEmpty())
                <div class="space-y-4 pt-2">
                    <div class="flex items-center gap-2 text-xs font-bold uppercase tracking-wider text-slate-700">
                        <i class="fas fa-folder-tree text-blue-600"></i>
                        <span>{{ __('portal.guides_tab') }}</span>
                    </div>

                    <div class="space-y-3.5">
                        @foreach($topics as $topic)
                            <div
                                data-topic-id="{{ $topic->id }}"
                                id="procedure-{{ $topic->id }}"
                                class="bg-white rounded-xl border border-slate-200 shadow-xs hover:border-slate-300 transition-all duration-150 overflow-hidden"
                            >
                                <div
                                    @click="toggleTopic({{ $topic->id }})"
                                    class="p-4 sm:p-5 cursor-pointer flex items-start justify-between gap-4 select-none hover:bg-slate-50/60 transition-colors"
                                >
                                    <div class="flex items-start gap-3.5">
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
                                                <x-saasover.verified-badge compact="true" text="Fiche validée CS Knowledge" />
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

                                    <div class="shrink-0 flex items-center gap-2 pt-1">
                                        <button
                                            type="button"
                                            @click.stop="copyTopicLink({{ $topic->id }})"
                                            class="p-1.5 rounded-lg text-slate-400 hover:text-[#081951] hover:bg-slate-100 transition-colors cursor-pointer"
                                            title="Copier le lien direct vers cette fiche"
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
                        @endforeach
                    </div>
                </div>
            @endif

            <!-- Call To Action Banner -->
            <div class="rounded-2xl bg-[#081951] p-6 sm:p-8 text-white text-center shadow-xs">
                <h2 class="text-xl sm:text-2xl font-bold mb-2 font-display">
                    {{ __('portal.missing_doc_title') }}
                </h2>
                <p class="text-slate-300 text-xs sm:text-sm max-w-xl mx-auto mb-6 leading-relaxed font-sans">
                    {{ __('portal.missing_doc_desc') }}
                </p>
                <div class="flex flex-wrap justify-center gap-3">
                    <a
                        href="/admin/documents/create"
                        class="bg-white text-[#081951] hover:bg-slate-100 font-bold px-5 py-2 rounded-xl transition-colors shadow-xs inline-flex items-center gap-2 text-xs sm:text-sm"
                    >
                        <i class="fas fa-upload text-xs"></i>
                        <span>{{ __('portal.add_pdf') }}</span>
                    </a>
                    <a
                        href="{{ route('contact') }}"
                        class="bg-[#0e2454] hover:bg-[#143673] border border-slate-600 text-white font-semibold px-5 py-2 rounded-xl transition-colors inline-flex items-center gap-2 text-xs sm:text-sm"
                    >
                        <i class="fas fa-paper-plane text-xs"></i>
                        <span>{{ __('portal.suggest_update') }}</span>
                    </a>
                </div>
            </div>

            <!-- Footer -->
            <footer class="pt-6 pb-6 border-t border-slate-200 text-xs text-slate-500 flex flex-col sm:flex-row items-center justify-between gap-4">
                <div class="flex items-center gap-3">
                    <x-saasover.mark size="size-5" />
                    <span>&copy; {{ date('Y') }} {{ __('portal.footer_copyright') }}</span>
                </div>
                <div class="flex items-center gap-5 font-medium text-slate-600">
                    <a href="{{ route('home') }}" class="hover:text-[#081951] transition-colors">{{ __('portal.all_documents') }}</a>
                    <a href="{{ route('about') }}" class="hover:text-[#081951] transition-colors">{{ __('portal.about') }}</a>
                    <a href="{{ route('contact') }}" class="hover:text-[#081951] transition-colors">{{ __('portal.support') }}</a>
                    <a href="/admin" class="hover:text-[#081951] transition-colors">{{ __('portal.admin') }}</a>
                </div>
            </footer>
        </div>
    </main>
</div>
