<?php

use App\Models\Tag;
use App\Models\Topic;
use Illuminate\Database\Eloquent\Collection;
use Livewire\Attributes\Url;
use Livewire\Component;

new class extends Component
{
    /**
     * @var array{total_topics?: int, total_options?: int, total_tags?: int}
     */
    public array $stats = [];

    #[Url(as: 'q', history: true)]
    public string $search = '';

    #[Url(as: 'tag', history: true)]
    public ?string $selectedTag = null;

    public function mount(array $stats = []): void
    {
        $this->stats = $stats;
    }

    public function selectTag(?string $slug): void
    {
        if ($this->selectedTag === $slug) {
            $this->selectedTag = null;
        } else {
            $this->selectedTag = $slug;
        }
    }

    public function clearFilters(): void
    {
        $this->search = '';
        $this->selectedTag = null;
    }

    /**
     * Helper to highlight matching keywords safely.
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
            '<mark class="bg-indigo-100 dark:bg-indigo-950/90 text-indigo-900 dark:text-indigo-200 px-1 py-0.5 rounded font-semibold">$1</mark>',
            $escaped
        ) ?? $escaped;
    }

    /**
     * @return array{
     *     topics: Collection<int, Topic>|\Illuminate\Support\Collection<int, Topic>,
     *     popularTags: Collection<int, Tag>,
     *     totalTopicsCount: int,
     *     totalOptionsCount: int
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
                                'tags',
                                'options' => fn ($q) => $q->published()->orderBy('order'),
                            ]);

                        if (! empty($this->selectedTag)) {
                            $query->whereHas('tags', fn ($q) => $q->where('slug', $this->selectedTag));
                        }
                    })
                    ->get();
            } catch (\Throwable $e) {
                // Graceful fallback to Eloquent multi-column search
                $query = Topic::query()
                    ->published()
                    ->with([
                        'tags',
                        'options' => fn ($q) => $q->published()->orderBy('order'),
                    ])
                    ->search($trimmedSearch)
                    ->orderBy('order');

                if (! empty($this->selectedTag)) {
                    $query->whereHas('tags', fn ($q) => $q->where('slug', $this->selectedTag));
                }

                $topics = $query->get();
            }
        } else {
            // Standard Eloquent retrieval
            $query = Topic::query()
                ->published()
                ->with([
                    'tags',
                    'options' => fn ($q) => $q->published()->orderBy('order'),
                ])
                ->orderBy('order');

            if (! empty($this->selectedTag)) {
                $query->whereHas('tags', fn ($q) => $q->where('slug', $this->selectedTag));
            }

            $topics = $query->get();
        }

        $popularTags = Tag::query()
            ->withCount(['topics' => fn ($q) => $q->published()])
            ->where('topics_count', '>', 0)
            ->orderByDesc('topics_count')
            ->get();

        $totalTopicsCount = $this->stats['total_topics'] ?? Topic::query()->published()->count();
        $totalOptionsCount = $this->stats['total_options'] ?? \App\Models\TopicOption::query()->published()->count();

        return [
            'topics' => $topics,
            'popularTags' => $popularTags,
            'totalTopicsCount' => $totalTopicsCount,
            'totalOptionsCount' => $totalOptionsCount,
        ];
    }
};
?>

<div
    x-data="{
        openTopics: {},
        copiedTopicId: null,

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
            // Auto open first topic or all topics when search is active
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

            // Watch Livewire search changes via $wire to automatically expand matching topics
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
    class="w-full max-w-5xl mx-auto px-4 sm:px-6 lg:px-8 py-8 md:py-14"
>
    <!-- Professional Enterprise Hero Section -->
    <div class="text-center max-w-3xl mx-auto mb-10 md:mb-14">
        <!-- Internal Portal Badge -->
        <div class="inline-flex items-center gap-2 px-3.5 py-1.5 rounded-full bg-indigo-50 dark:bg-indigo-950/60 border border-indigo-200/80 dark:border-indigo-800 text-indigo-700 dark:text-indigo-300 text-xs font-semibold mb-5 shadow-xs">
            <span class="flex h-2 w-2 relative">
                <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-indigo-400 opacity-75"></span>
                <span class="relative inline-flex rounded-full h-2 w-2 bg-indigo-600"></span>
            </span>
            <span class="tracking-wide uppercase text-[11px] font-bold"> Base de Connaissances Interne</span>
        </div>

        <h1 class="text-3xl sm:text-4xl md:text-5xl lg:text-6xl font-black text-zinc-900 dark:text-white tracking-tight leading-[1.15]">
            Portail des Procédures, Politiques <br class="hidden sm:inline" />
            <span class="text-transparent bg-clip-text bg-gradient-to-r from-indigo-600 via-indigo-500 to-sky-500">& Ressources Internes</span>
        </h1>

        <p class="mt-4 sm:mt-5 text-base sm:text-lg text-zinc-600 dark:text-zinc-400 max-w-2xl mx-auto leading-relaxed">
            Recherchez instantanément les modes opératoires, politiques RH, guides techniques, outils métiers et contacts référents de l’entreprise.
        </p>

        <!-- Enterprise Stats Strip -->
        <div class="mt-6 flex flex-wrap items-center justify-center gap-4 sm:gap-6 text-xs text-zinc-500 dark:text-zinc-400 font-medium">
            <span class="inline-flex items-center gap-1.5 bg-zinc-100 dark:bg-zinc-800/80 px-3 py-1 rounded-lg border border-zinc-200/60 dark:border-zinc-700/60">
                <svg class="w-4 h-4 text-indigo-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10" />
                </svg>
                <strong class="text-zinc-900 dark:text-white font-bold">{{ $totalTopicsCount }}</strong> thématiques documentées
            </span>
            <span class="inline-flex items-center gap-1.5 bg-zinc-100 dark:bg-zinc-800/80 px-3 py-1 rounded-lg border border-zinc-200/60 dark:border-zinc-700/60">
                <svg class="w-4 h-4 text-sky-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                </svg>
                <strong class="text-zinc-900 dark:text-white font-bold">{{ $totalOptionsCount }}</strong> fiches & procédures actives
            </span>
            <span class="inline-flex items-center gap-1.5 bg-zinc-100 dark:bg-zinc-800/80 px-3 py-1 rounded-lg border border-zinc-200/60 dark:border-zinc-700/60">
                <svg class="w-4 h-4 text-emerald-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z" />
                </svg>
                <strong class="text-zinc-900 dark:text-white font-bold">{{ $popularTags->count() }}</strong> pôles couverts
            </span>
        </div>
    </div>

    <!-- Search Input Box -->
    <div class="max-w-3xl mx-auto mb-8 relative">
        <div class="relative flex items-center shadow-xl shadow-zinc-900/5 rounded-2xl bg-white dark:bg-zinc-900 border border-zinc-200/90 dark:border-zinc-800/90 focus-within:border-indigo-500 focus-within:ring-4 focus-within:ring-indigo-500/10 transition-all duration-200">
            <!-- Search Icon -->
            <div class="pl-5 pr-2 flex items-center pointer-events-none text-zinc-400 dark:text-zinc-500">
                <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                </svg>
            </div>

            <!-- Input Element -->
            <input
                x-ref="searchInput"
                type="text"
                wire:model.live.debounce.250ms="search"
                placeholder="Rechercher une procédure (ex: notes de frais, congés, VPN, onboarding, bon de commande...)"
                class="w-full py-4.5 px-2 text-base sm:text-lg bg-transparent text-zinc-900 dark:text-zinc-100 placeholder-zinc-400 dark:placeholder-zinc-500 focus:outline-hidden"
                autofocus
            />

            <!-- Controls: Loading, Clear, Shortcut -->
            <div class="pr-4 flex items-center gap-2">
                <!-- Loading Indicator -->
                <div wire:loading wire:target="search, selectTag" class="text-indigo-600 dark:text-indigo-400">
                    <svg class="animate-spin h-5 w-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                    </svg>
                </div>

                <!-- Clear Search Button -->
                @if(!empty($search) || !empty($selectedTag))
                    <button
                        type="button"
                        wire:click="clearFilters"
                        class="p-1.5 rounded-lg text-zinc-400 hover:text-zinc-700 dark:hover:text-zinc-200 hover:bg-zinc-100 dark:hover:bg-zinc-800 transition-colors"
                        title="Effacer la recherche (Echap)"
                    >
                        <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                @else
                    <!-- Keyboard Shortcut Badge -->
                    <div class="hidden sm:flex items-center gap-1">
                        <kbd class="px-2 py-0.5 text-[11px] font-semibold text-zinc-400 dark:text-zinc-500 bg-zinc-100 dark:bg-zinc-800 rounded border border-zinc-200 dark:border-zinc-700 shadow-2xs">
                            /
                        </kbd>
                    </div>
                @endif
            </div>
        </div>

        <!-- Corporate Department / Tags Filter -->
        @if($popularTags->isNotEmpty())
            <div class="mt-4 flex flex-wrap items-center gap-2 justify-center">
                <span class="text-xs font-semibold text-zinc-400 dark:text-zinc-500 mr-1">Pôles & Thématiques :</span>
                @foreach($popularTags as $tag)
                    @php
                        $isSelected = $selectedTag === $tag->slug;
                    @endphp
                    <button
                        type="button"
                        wire:click="selectTag('{{ $tag->slug }}')"
                        class="group inline-flex items-center gap-1.5 px-3 py-1 rounded-xl text-xs font-medium transition-all duration-150 cursor-pointer {{ $isSelected ? 'bg-indigo-600 text-white shadow-sm ring-2 ring-indigo-500/20' : 'bg-white dark:bg-zinc-900 border border-zinc-200/80 dark:border-zinc-800 text-zinc-700 dark:text-zinc-300 hover:border-indigo-400 dark:hover:border-indigo-600 hover:bg-indigo-50/30 dark:hover:bg-indigo-950/20' }}"
                    >
                        <span>#{{ $tag->name }}</span>
                        <span class="px-1.5 py-0.2 rounded-full text-[10px] {{ $isSelected ? 'bg-indigo-700 text-white' : 'bg-zinc-100 dark:bg-zinc-800 text-zinc-500 dark:text-zinc-400 group-hover:bg-indigo-100 dark:group-hover:bg-indigo-900/50 group-hover:text-indigo-700 dark:group-hover:text-indigo-300' }}">
                            {{ $tag->topics_count }}
                        </span>
                    </button>
                @endforeach

                @if(!empty($selectedTag))
                    <button
                        type="button"
                        wire:click="selectTag(null)"
                        class="text-xs font-semibold text-rose-600 dark:text-rose-400 hover:underline ml-1"
                    >
                        ✕ Réinitialiser le filtre
                    </button>
                @endif
            </div>
        @endif
    </div>

    <!-- Active Filter Bar & Client-side Instant Actions (Powered by Alpine) -->
    <div class="max-w-4xl mx-auto flex flex-col sm:flex-row sm:items-center justify-between gap-3 text-xs sm:text-sm text-zinc-500 dark:text-zinc-400 mb-6 px-1">
        <div class="flex items-center gap-2">
            @if(!empty($search) || !empty($selectedTag))
                <span>
                    <strong class="text-zinc-900 dark:text-white">{{ $topics->count() }}</strong> résultat(s) interne(s)
                    @if(!empty($search))
                        pour « <strong class="text-indigo-600 dark:text-indigo-400">{{ $search }}</strong> »
                    @endif
                    @if(!empty($selectedTag))
                        dans le pôle <strong class="text-indigo-600 dark:text-indigo-400">#{{ $selectedTag }}</strong>
                    @endif
                </span>
            @else
                <span>
                    Affichage des <strong>{{ $topics->count() }}</strong> rubriques opérationnelles
                </span>
            @endif
        </div>

        <div class="flex items-center gap-3 self-end sm:self-auto">
            @if($topics->isNotEmpty())
                <!-- Instant Client-side Expand/Collapse using Alpine.js (0ms Latency) -->
                <button
                    type="button"
                    @click="expandAll([{{ $topics->pluck('id')->implode(',') }}])"
                    class="text-xs font-semibold text-zinc-600 dark:text-zinc-300 hover:text-indigo-600 dark:hover:text-indigo-400 transition-colors cursor-pointer"
                >
                    Tout déplier
                </button>
                <span class="text-zinc-300 dark:text-zinc-700">|</span>
                <button
                    type="button"
                    @click="collapseAll()"
                    class="text-xs font-semibold text-zinc-600 dark:text-zinc-300 hover:text-indigo-600 dark:hover:text-indigo-400 transition-colors cursor-pointer"
                >
                    Tout replier
                </button>
            @endif

            @if(!empty($search) || !empty($selectedTag))
                <span class="text-zinc-300 dark:text-zinc-700">|</span>
                <button
                    type="button"
                    wire:click="clearFilters"
                    class="text-xs font-semibold text-rose-600 dark:text-rose-400 hover:underline cursor-pointer"
                >
                    Effacer tout
                </button>
            @endif
        </div>
    </div>

    <!-- Procedures & Operating Topics List -->
    <div class="max-w-4xl mx-auto space-y-6">
        @forelse($topics as $topic)
            <div
                data-topic-id="{{ $topic->id }}"
                id="procedure-{{ $topic->id }}"
                class="bg-white dark:bg-zinc-900 rounded-2xl border border-zinc-200/80 dark:border-zinc-800 shadow-xs hover:shadow-md transition-all duration-200 overflow-hidden"
            >
                <!-- Topic Header (Pure Client-side Instant Toggle via Alpine) -->
                <div
                    @click="toggleTopic({{ $topic->id }})"
                    class="p-5 sm:p-6 cursor-pointer flex items-start justify-between gap-4 select-none hover:bg-zinc-50/60 dark:hover:bg-zinc-800/40 transition-colors"
                >
                    <div class="flex items-start gap-4">
                        <!-- Corporate Topic Icon -->
                        <div class="shrink-0 w-12 h-12 rounded-2xl bg-indigo-50 dark:bg-indigo-950/60 text-indigo-600 dark:text-indigo-400 border border-indigo-100 dark:border-indigo-900/50 flex items-center justify-center shadow-2xs">
                            @if($topic->icon === 'academic-cap')
                                <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
                                </svg>
                            @elseif($topic->icon === 'banknotes')
                                <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                            @elseif($topic->icon === 'calendar')
                                <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                </svg>
                            @elseif($topic->icon === 'compass')
                                <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.75 17L9 20l-1 1h8l-1-1-.75-3M3 13h18M5 17h14a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                                </svg>
                            @else
                                <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10" />
                                </svg>
                            @endif
                        </div>

                        <div>
                            <div class="flex flex-wrap items-center gap-2 mb-1.5">
                                <h2 class="text-lg sm:text-xl font-bold text-zinc-900 dark:text-white">
                                    {!! $this->highlightText($topic->title) !!}
                                </h2>
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-zinc-100 dark:bg-zinc-800 text-zinc-600 dark:text-zinc-400">
                                    {{ $topic->options->count() }} démarche(s)
                                </span>
                            </div>

                            @if($topic->description)
                                <p class="text-sm text-zinc-600 dark:text-zinc-400 leading-relaxed">
                                    {!! $this->highlightText($topic->description) !!}
                                </p>
                            @endif

                            <!-- Department Tags -->
                            @if($topic->tags->isNotEmpty())
                                <div class="mt-3 flex flex-wrap gap-1.5">
                                    @foreach($topic->tags as $t)
                                        <button
                                            type="button"
                                            wire:click.stop="selectTag('{{ $t->slug }}')"
                                            class="inline-flex items-center px-2 py-0.5 rounded-md text-xs font-medium transition-colors cursor-pointer {{ $selectedTag === $t->slug ? 'bg-indigo-600 text-white' : 'bg-zinc-100 dark:bg-zinc-800/80 text-zinc-600 dark:text-zinc-300 hover:bg-indigo-50 dark:hover:bg-indigo-950/40 hover:text-indigo-600 dark:hover:text-indigo-300' }} border border-zinc-200/60 dark:border-zinc-700/60"
                                        >
                                            #{!! $this->highlightText($t->name) !!}
                                        </button>
                                    @endforeach
                                </div>
                            @endif
                        </div>
                    </div>

                    <!-- Right Controls (Share & Accordion Chevron) -->
                    <div class="shrink-0 flex items-center gap-2 pt-1">
                        <button
                            type="button"
                            @click.stop="copyTopicLink({{ $topic->id }})"
                            class="p-1.5 rounded-lg text-zinc-400 hover:text-zinc-600 dark:hover:text-zinc-200 hover:bg-zinc-100 dark:hover:bg-zinc-800 transition-colors cursor-pointer"
                            title="Copier le lien direct vers cette procédure"
                        >
                            <template x-if="copiedTopicId === {{ $topic->id }}">
                                <span class="text-[11px] font-semibold text-emerald-600 dark:text-emerald-400">Lien copié !</span>
                            </template>
                            <template x-if="copiedTopicId !== {{ $topic->id }}">
                                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1" />
                                </svg>
                            </template>
                        </button>

                        <div class="text-zinc-400 dark:text-zinc-500">
                            <svg
                                class="w-5 h-5 transform transition-transform duration-200"
                                :class="isTopicOpen({{ $topic->id }}) ? 'rotate-180' : ''"
                                fill="none"
                                viewBox="0 0 24 24"
                                stroke="currentColor"
                            >
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                            </svg>
                        </div>
                    </div>
                </div>

                <!-- Instant Accordion Expansion (Powered by Alpine.js with 0ms Delay) -->
                <div
                    x-show="isTopicOpen({{ $topic->id }})"
                    x-transition:enter="transition ease-out duration-200"
                    x-transition:enter-start="opacity-0 -translate-y-1"
                    x-transition:enter-end="opacity-100 translate-y-0"
                    x-transition:leave="transition ease-in duration-150"
                    x-transition:leave-start="opacity-100 translate-y-0"
                    x-transition:leave-end="opacity-0 -translate-y-1"
                    class="border-t border-zinc-100 dark:border-zinc-800/80 bg-zinc-50/50 dark:bg-zinc-900/50 p-5 sm:p-6 space-y-4"
                >
                    @forelse($topic->options as $option)
                        <div class="bg-white dark:bg-zinc-800/90 rounded-xl p-4 sm:p-5 border border-zinc-200/70 dark:border-zinc-700/60 shadow-xs hover:border-zinc-300 dark:hover:border-zinc-600 transition-all">
                            <div class="flex items-start justify-between gap-3 mb-2.5">
                                <h3 class="text-base font-semibold text-zinc-900 dark:text-zinc-100 flex items-center gap-2">
                                    @if($option->type === 'faq')
                                        <span class="shrink-0 w-2.5 h-2.5 rounded-full bg-indigo-500"></span>
                                    @elseif($option->type === 'link')
                                        <span class="shrink-0 w-2.5 h-2.5 rounded-full bg-sky-500"></span>
                                    @elseif($option->type === 'download')
                                        <span class="shrink-0 w-2.5 h-2.5 rounded-full bg-emerald-500"></span>
                                    @elseif($option->type === 'contact')
                                        <span class="shrink-0 w-2.5 h-2.5 rounded-full bg-amber-500"></span>
                                    @endif
                                    <span>{!! $this->highlightText($option->title) !!}</span>
                                </h3>

                                <span class="shrink-0 inline-flex items-center px-2.5 py-0.5 rounded-md text-[11px] font-bold uppercase tracking-wider {{ match($option->type) {
                                    'faq' => 'bg-indigo-50 dark:bg-indigo-950/60 text-indigo-700 dark:text-indigo-300 border border-indigo-200/60 dark:border-indigo-800/60',
                                    'link' => 'bg-sky-50 dark:bg-sky-950/60 text-sky-700 dark:text-sky-300 border border-sky-200/60 dark:border-sky-800/60',
                                    'download' => 'bg-emerald-50 dark:bg-emerald-950/60 text-emerald-700 dark:text-emerald-300 border border-emerald-200/60 dark:border-emerald-800/60',
                                    'contact' => 'bg-amber-50 dark:bg-amber-950/60 text-amber-700 dark:text-amber-300 border border-amber-200/60 dark:border-amber-800/60',
                                    default => 'bg-zinc-100 dark:bg-zinc-800 text-zinc-700 dark:text-zinc-300',
                                } }}">
                                    {{ match($option->type) {
                                    'faq' => 'Procédure Interne',
                                    'link' => 'Outil / Plateforme',
                                    'download' => 'Modèle / Document',
                                    'contact' => 'Assistance Dédiée',
                                    default => $option->type,
                                } }}
                                </span>
                            </div>

                            @if($option->content)
                                <div class="text-sm text-zinc-600 dark:text-zinc-300 leading-relaxed space-y-2">
                                    {!! nl2br($this->highlightText($option->content)) !!}
                                </div>
                            @endif

                            @if($option->action_url)
                                <div class="mt-4 pt-3 border-t border-zinc-100 dark:border-zinc-700/60 flex items-center justify-end">
                                    <a
                                        href="{{ $option->action_url }}"
                                        target="_blank"
                                        rel="noopener noreferrer"
                                        class="inline-flex items-center gap-2 px-3 py-1.5 rounded-lg text-xs font-semibold bg-indigo-50 hover:bg-indigo-100 dark:bg-indigo-950/60 dark:hover:bg-indigo-900/60 text-indigo-700 dark:text-indigo-300 transition-colors shadow-2xs"
                                    >
                                        @if($option->type === 'download')
                                            <svg class="w-4 h-4 text-emerald-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" />
                                            </svg>
                                            <span>Télécharger le document modèle</span>
                                        @elseif($option->type === 'contact')
                                            <svg class="w-4 h-4 text-amber-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z" />
                                            </svg>
                                            <span>Appeler le poste interne</span>
                                        @else
                                            <span>Accéder à l’outil interne</span>
                                            <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14" />
                                            </svg>
                                        @endif
                                    </a>
                                </div>
                            @endif
                        </div>
                    @empty
                        <p class="text-sm text-zinc-500 dark:text-zinc-400 italic py-2">
                            Aucune procédure détaillée n’est encore renseignée pour cette thématique.
                        </p>
                    @endforelse
                </div>
            </div>
        @empty
            <!-- Corporate Empty State -->
            <div class="bg-white dark:bg-zinc-900 rounded-3xl border border-zinc-200/80 dark:border-zinc-800 p-8 sm:p-12 text-center max-w-lg mx-auto shadow-xs">
                <div class="w-16 h-16 mx-auto mb-4 rounded-2xl bg-indigo-50 dark:bg-indigo-950/60 text-indigo-600 dark:text-indigo-400 flex items-center justify-center">
                    <svg class="w-8 h-8" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9.172 16.172a4 4 0 015.656 0M9 10h.01M15 10h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                </div>
                <h3 class="text-lg font-bold text-zinc-900 dark:text-white">Aucune procédure interne trouvée</h3>
                <p class="mt-2 text-sm text-zinc-500 dark:text-zinc-400 leading-relaxed">
                    Aucun document ou mode opératoire ne correspond à votre recherche « <span class="font-medium text-zinc-800 dark:text-zinc-200">{{ $search ?: $selectedTag }}</span> ».
                </p>
                <div class="mt-6 flex flex-wrap justify-center gap-3">
                    <button
                        type="button"
                        wire:click="clearFilters"
                        class="px-4 py-2 rounded-xl text-sm font-semibold bg-indigo-600 text-white hover:bg-indigo-700 transition-colors shadow-xs cursor-pointer"
                    >
                        Réinitialiser la recherche
                    </button>
                </div>
            </div>
        @endforelse
    </div>

    <!-- Corporate Enterprise Knowledge Contribution Banner -->
    <div class="max-w-4xl mx-auto mt-12 bg-gradient-to-r from-indigo-500/10 via-sky-500/10 to-transparent border border-indigo-200/50 dark:border-indigo-900/40 rounded-2xl p-6 flex flex-col sm:flex-row items-center justify-between gap-4">
        <div>
            <h4 class="text-sm font-bold text-zinc-900 dark:text-white">Une procédure métier manque ou nécessite une révision ?</h4>
            <p class="text-xs text-zinc-600 dark:text-zinc-400 mt-0.5">Les référents et managers de département peuvent actualiser ou publier des fiches directement depuis l'espace d'administration.</p>
        </div>
        <a
            href="/admin/topics"
            class="shrink-0 inline-flex items-center gap-1.5 px-4 py-2 rounded-xl text-xs font-semibold bg-indigo-600 hover:bg-indigo-700 text-white transition-colors shadow-xs"
        >
            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
            </svg>
            <span>Gérer les procédures</span>
        </a>
    </div>
</div>
