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
     * Helper to highlight matching keywords safely with neon dark accent.
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
            '<mark class="bg-indigo-500/30 text-indigo-200 ring-1 ring-indigo-500/50 px-1 py-0.5 rounded font-semibold">$1</mark>',
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
            // Auto-expand topic when search is running or default first
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
    @keydown.escape="if (mobileSidebarOpen) { mobileSidebarOpen = false; } else if ($wire.search || $wire.selectedTag) { $wire.clearFilters(); }"
    class="min-h-screen bg-zinc-950 text-zinc-100 flex flex-col md:flex-row antialiased relative selection:bg-indigo-500 selection:text-white"
>
    <!-- ============================================================== -->
    <!-- MOBILE SIDEBAR BACKDROP & DRAWER                               -->
    <!-- ============================================================== -->
    <div
        x-show="mobileSidebarOpen"
        x-transition:enter="transition-opacity ease-linear duration-300"
        x-transition:enter-start="opacity-0"
        x-transition:enter-end="opacity-100"
        x-transition:leave="transition-opacity ease-linear duration-200"
        x-transition:leave-start="opacity-100"
        x-transition:leave-end="opacity-0"
        @click="mobileSidebarOpen = false"
        class="fixed inset-0 bg-black/80 backdrop-blur-sm z-40 md:hidden"
        style="display: none;"
    ></div>

    <aside
        x-show="mobileSidebarOpen"
        x-transition:enter="transition ease-out duration-300 transform"
        x-transition:enter-start="-translate-x-full"
        x-transition:enter-end="translate-x-0"
        x-transition:leave="transition ease-in duration-200 transform"
        x-transition:leave-start="translate-x-0"
        x-transition:leave-end="-translate-x-full"
        class="fixed inset-y-0 left-0 w-80 max-w-[85vw] bg-zinc-950/95 border-r border-zinc-800/90 z-50 flex flex-col md:hidden backdrop-blur-2xl shadow-2xl"
        style="display: none;"
    >
        <!-- Mobile Sidebar Header -->
        <div class="h-16 px-5 border-b border-zinc-800/80 flex items-center justify-between">
            <a href="{{ route('home') }}" class="flex items-center gap-3">
                <img
                    src="{{ asset('logo.jpg') }}"
                    alt="Nvonchi Search"
                    class="w-9 h-9 rounded-xl object-contain ring-1 ring-white/10 shadow-sm"
                />
                <div class="flex flex-col">
                    <span class="text-base font-extrabold tracking-tight text-white leading-tight">NvonchiSearch</span>
                    <span class="text-[10px] font-bold text-zinc-400 uppercase tracking-widest leading-none">Hub Opérationnel</span>
                </div>
            </a>
            <button
                type="button"
                @click="mobileSidebarOpen = false"
                class="p-2 rounded-xl text-zinc-400 hover:text-white hover:bg-zinc-800 transition-colors"
            >
                <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                </svg>
            </button>
        </div>

        <!-- Mobile Sidebar Scrollable Content -->
        <div class="flex-1 overflow-y-auto px-4 py-5 space-y-6">
            <!-- Portal Status Badge -->
            <div class="flex items-center gap-2 px-3 py-2 rounded-xl bg-zinc-900/80 border border-zinc-800/80 text-xs">
                <span class="relative flex h-2 w-2">
                    <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-emerald-400 opacity-75"></span>
                    <span class="relative inline-flex rounded-full h-2 w-2 bg-emerald-500"></span>
                </span>
                <span class="text-zinc-300 font-medium">Base opérationnelle active</span>
                <span class="ml-auto text-[10px] text-zinc-500 font-mono">v2.0</span>
            </div>

            <!-- Quick Metrics -->
            <div class="grid grid-cols-2 gap-2">
                <div class="p-3 rounded-xl bg-zinc-900/60 border border-zinc-800/70">
                    <div class="text-lg font-bold text-white">{{ $totalTopicsCount }}</div>
                    <div class="text-[11px] text-zinc-400">Thématiques</div>
                </div>
                <div class="p-3 rounded-xl bg-zinc-900/60 border border-zinc-800/70">
                    <div class="text-lg font-bold text-indigo-400">{{ $totalOptionsCount }}</div>
                    <div class="text-[11px] text-zinc-400">Procédures & Fiches</div>
                </div>
            </div>

            <!-- Navigation Links -->
            <div class="space-y-1">
                <div class="px-2 pb-1.5 text-[10px] font-bold uppercase tracking-wider text-zinc-500">Navigation</div>
                <button
                    type="button"
                    wire:click="clearFilters"
                    @click="mobileSidebarOpen = false"
                    class="w-full flex items-center justify-between px-3 py-2 rounded-xl text-sm font-medium transition-all {{ empty($selectedTag) && empty($search) ? 'bg-indigo-600/20 text-indigo-300 border border-indigo-500/40 shadow-xs' : 'text-zinc-300 hover:text-white hover:bg-zinc-900' }}"
                >
                    <span class="flex items-center gap-2.5">
                        <svg class="w-4 h-4 text-indigo-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z" />
                        </svg>
                        <span>Toutes les rubriques</span>
                    </span>
                    <span class="text-xs px-2 py-0.5 rounded-full bg-zinc-800 text-zinc-400">{{ $totalTopicsCount }}</span>
                </button>

                <a
                    href="{{ route('about') }}"
                    class="flex items-center gap-2.5 px-3 py-2 rounded-xl text-sm font-medium text-zinc-400 hover:text-white hover:bg-zinc-900 transition-colors"
                >
                    <svg class="w-4 h-4 text-sky-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                    <span>À propos du portail</span>
                </a>

                <a
                    href="{{ route('contact') }}"
                    class="flex items-center gap-2.5 px-3 py-2 rounded-xl text-sm font-medium text-zinc-400 hover:text-white hover:bg-zinc-900 transition-colors"
                >
                    <svg class="w-4 h-4 text-emerald-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                    </svg>
                    <span>Support & Questions</span>
                </a>

                <a
                    href="/admin"
                    class="flex items-center gap-2.5 px-3 py-2 rounded-xl text-sm font-medium text-zinc-400 hover:text-white hover:bg-zinc-900 transition-colors"
                >
                    <svg class="w-4 h-4 text-amber-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z" />
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                    </svg>
                    <span>Administration Filament</span>
                </a>
            </div>

            <!-- Departments & Tags Filters -->
            <div class="space-y-1">
                <div class="flex items-center justify-between px-2 pb-1.5 text-[10px] font-bold uppercase tracking-wider text-zinc-500">
                    <span>Pôles & Départements</span>
                    @if(!empty($selectedTag))
                        <button
                            type="button"
                            wire:click="selectTag(null)"
                            @click="mobileSidebarOpen = false"
                            class="text-indigo-400 hover:text-indigo-300 normal-case"
                        >
                            Tout effacer
                        </button>
                    @endif
                </div>

                @foreach($popularTags as $tag)
                    @php
                        $isSelected = $selectedTag === $tag->slug;
                    @endphp
                    <button
                        type="button"
                        wire:click="selectTag('{{ $tag->slug }}')"
                        @click="mobileSidebarOpen = false"
                        class="w-full flex items-center justify-between px-3 py-2 rounded-xl text-xs font-medium transition-all {{ $isSelected ? 'bg-indigo-600/25 text-indigo-200 border border-indigo-500/40 shadow-xs' : 'text-zinc-400 hover:text-zinc-200 hover:bg-zinc-900/80' }}"
                    >
                        <span class="flex items-center gap-2 truncate">
                            <span class="w-1.5 h-1.5 rounded-full {{ $isSelected ? 'bg-indigo-400 ring-2 ring-indigo-400/30' : 'bg-zinc-600' }}"></span>
                            <span class="truncate">#{{ $tag->name }}</span>
                        </span>
                        <span class="text-[10px] px-2 py-0.5 rounded-full {{ $isSelected ? 'bg-indigo-500/30 text-indigo-200' : 'bg-zinc-800/80 text-zinc-500' }}">
                            {{ $tag->topics_count }}
                        </span>
                    </button>
                @endforeach
            </div>
        </div>
    </aside>

    <!-- ============================================================== -->
    <!-- DESKTOP LEFT SIDEBAR                                           -->
    <!-- ============================================================== -->
    <aside class="w-72 lg:w-80 shrink-0 hidden md:flex flex-col h-screen sticky top-0 bg-zinc-950/95 border-r border-zinc-800/80 z-30 backdrop-blur-2xl">
        <!-- Brand Header -->
        <div class="h-16 px-6 border-b border-zinc-800/80 flex items-center justify-between">
            <a href="{{ route('home') }}" class="flex items-center gap-3 group">
                <img
                    src="{{ asset('logo.jpg') }}"
                    alt="Nvonchi Search"
                    class="w-10 h-10 rounded-xl object-contain ring-1 ring-white/10 shadow-md group-hover:scale-105 transition-transform duration-200"
                />
                <div class="flex flex-col">
                    <span class="text-base font-extrabold tracking-tight text-white leading-tight">NvonchiSearch</span>
                    <span class="text-[10px] font-bold text-zinc-400 uppercase tracking-widest leading-none">Hub Opérationnel</span>
                </div>
            </a>
            <span class="relative flex h-2 w-2" title="Système en ligne">
                <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-emerald-400 opacity-75"></span>
                <span class="relative inline-flex rounded-full h-2 w-2 bg-emerald-500"></span>
            </span>
        </div>

        <!-- Sidebar Body (Scrollable) -->
        <div class="flex-1 overflow-y-auto px-4 py-5 space-y-6">
            <!-- Metrics Mini-Widget -->
            <div class="p-3.5 rounded-2xl bg-zinc-900/60 border border-zinc-800/80 shadow-xs space-y-3">
                <div class="flex items-center justify-between text-xs text-zinc-400 font-medium">
                    <span>Statut base documentaire</span>
                    <span class="text-emerald-400 font-semibold text-[11px]">En direct</span>
                </div>
                <div class="grid grid-cols-2 gap-2 text-center">
                    <div class="p-2 rounded-xl bg-zinc-950/70 border border-zinc-800/60">
                        <div class="text-base font-black text-white">{{ $totalTopicsCount }}</div>
                        <div class="text-[10px] text-zinc-400 uppercase font-semibold">Thématiques</div>
                    </div>
                    <div class="p-2 rounded-xl bg-zinc-950/70 border border-zinc-800/60">
                        <div class="text-base font-black text-indigo-400">{{ $totalOptionsCount }}</div>
                        <div class="text-[10px] text-zinc-400 uppercase font-semibold">Procédures</div>
                    </div>
                </div>
            </div>

            <!-- Navigation Links -->
            <div class="space-y-1">
                <div class="px-2 pb-1.5 text-[10px] font-bold uppercase tracking-wider text-zinc-500">Navigation Hub</div>

                <button
                    type="button"
                    wire:click="clearFilters"
                    class="w-full flex items-center justify-between px-3 py-2 rounded-xl text-sm font-medium transition-all cursor-pointer {{ empty($selectedTag) && empty($search) ? 'bg-indigo-600/20 text-indigo-300 border border-indigo-500/40 shadow-xs' : 'text-zinc-400 hover:text-white hover:bg-zinc-900/70' }}"
                >
                    <span class="flex items-center gap-2.5">
                        <svg class="w-4 h-4 text-indigo-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z" />
                        </svg>
                        <span>Toutes les fiches</span>
                    </span>
                    <span class="text-xs px-2 py-0.5 rounded-full bg-zinc-900 text-zinc-400 border border-zinc-800">{{ $totalTopicsCount }}</span>
                </button>

                <a
                    href="{{ route('about') }}"
                    class="flex items-center gap-2.5 px-3 py-2 rounded-xl text-sm font-medium text-zinc-400 hover:text-white hover:bg-zinc-900/70 transition-colors"
                >
                    <svg class="w-4 h-4 text-sky-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                    <span>À propos du portail</span>
                </a>

                <a
                    href="{{ route('contact') }}"
                    class="flex items-center gap-2.5 px-3 py-2 rounded-xl text-sm font-medium text-zinc-400 hover:text-white hover:bg-zinc-900/70 transition-colors"
                >
                    <svg class="w-4 h-4 text-emerald-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                    </svg>
                    <span>Support & Questions</span>
                </a>

                <a
                    href="/admin"
                    class="flex items-center gap-2.5 px-3 py-2 rounded-xl text-sm font-medium text-zinc-400 hover:text-white hover:bg-zinc-900/70 transition-colors"
                >
                    <svg class="w-4 h-4 text-amber-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z" />
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                    </svg>
                    <span>Administration Filament</span>
                </a>
            </div>

            <!-- Departments & Tags Filters -->
            <div class="space-y-1">
                <div class="flex items-center justify-between px-2 pb-1.5 text-[10px] font-bold uppercase tracking-wider text-zinc-500">
                    <span>Pôles & Catégories</span>
                    @if(!empty($selectedTag))
                        <button
                            type="button"
                            wire:click="selectTag(null)"
                            class="text-indigo-400 hover:text-indigo-300 normal-case cursor-pointer text-[11px]"
                        >
                            Réinitialiser
                        </button>
                    @endif
                </div>

                @foreach($popularTags as $tag)
                    @php
                        $isSelected = $selectedTag === $tag->slug;
                    @endphp
                    <button
                        type="button"
                        wire:click="selectTag('{{ $tag->slug }}')"
                        class="w-full flex items-center justify-between px-3 py-2 rounded-xl text-xs font-medium transition-all cursor-pointer {{ $isSelected ? 'bg-indigo-600/25 text-indigo-200 border border-indigo-500/40 shadow-xs' : 'text-zinc-400 hover:text-zinc-200 hover:bg-zinc-900/80' }}"
                    >
                        <span class="flex items-center gap-2 truncate">
                            <span class="w-1.5 h-1.5 rounded-full {{ $isSelected ? 'bg-indigo-400 ring-2 ring-indigo-400/30' : 'bg-zinc-600' }}"></span>
                            <span class="truncate">#{{ $tag->name }}</span>
                        </span>
                        <span class="text-[10px] px-2 py-0.5 rounded-full {{ $isSelected ? 'bg-indigo-500/30 text-indigo-200 font-bold' : 'bg-zinc-800/80 text-zinc-500' }}">
                            {{ $tag->topics_count }}
                        </span>
                    </button>
                @endforeach
            </div>
        </div>

        <!-- Sidebar Footer -->
        <div class="p-4 border-t border-zinc-800/80 bg-zinc-950/60">
            <div class="flex items-center justify-between text-[11px] text-zinc-500">
                <span>Raccourci recherche</span>
                <kbd class="px-2 py-0.5 bg-zinc-900 border border-zinc-800 rounded font-mono text-zinc-400 text-[10px]">/</kbd>
            </div>
            <div class="mt-2 text-[10px] text-zinc-600 text-center">
                &copy; {{ date('Y') }} NvonchiSearch &bull; Tous droits réservés
            </div>
        </div>
    </aside>

    <!-- ============================================================== -->
    <!-- MAIN CONTENT AREA                                              -->
    <!-- ============================================================== -->
    <main class="flex-1 min-w-0 flex flex-col min-h-screen bg-zinc-950">
        <!-- Top Sticky Header Bar -->
        <header class="sticky top-0 z-20 backdrop-blur-xl bg-zinc-950/80 border-b border-zinc-800/80 px-4 sm:px-6 lg:px-8 py-3.5 flex items-center justify-between gap-3 sm:gap-6">
            <!-- Mobile Toggle -->
            <button
                type="button"
                @click="mobileSidebarOpen = true"
                class="md:hidden p-2 rounded-xl bg-zinc-900 border border-zinc-800 text-zinc-300 hover:text-white hover:bg-zinc-800 transition-colors"
                title="Ouvrir le menu latéral"
            >
                <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                </svg>
            </button>

            <!-- Search Input Box -->
            <div class="relative flex-1 max-w-2xl flex items-center bg-zinc-900/90 border border-zinc-800 rounded-2xl px-4 py-2 focus-within:border-indigo-500 focus-within:ring-2 focus-within:ring-indigo-500/20 shadow-lg shadow-black/20 transition-all">
                <!-- Search Icon -->
                <div class="pr-2 flex items-center pointer-events-none text-zinc-400">
                    <svg class="w-5 h-5 text-indigo-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                    </svg>
                </div>

                <!-- Input -->
                <input
                    x-ref="searchInput"
                    type="text"
                    wire:model.live.debounce.250ms="search"
                    placeholder="Rechercher une démarche (ex: congés, notes de frais, VPN, ticket IT...)"
                    class="w-full bg-transparent text-sm sm:text-base text-zinc-100 placeholder-zinc-500 focus:outline-hidden"
                    autofocus
                />

                <!-- Controls inside input: Loading, Clear, Shortcut -->
                <div class="flex items-center gap-2 pl-2">
                    <!-- Loading Indicator -->
                    <div wire:loading wire:target="search, selectTag" class="text-indigo-400">
                        <svg class="animate-spin h-4 w-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                        </svg>
                    </div>

                    @if(!empty($search) || !empty($selectedTag))
                        <button
                            type="button"
                            wire:click="clearFilters"
                            class="p-1 rounded-lg text-zinc-400 hover:text-white hover:bg-zinc-800 transition-colors"
                            title="Effacer les filtres (Echap)"
                        >
                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                            </svg>
                        </button>
                    @else
                        <kbd class="hidden sm:inline-block px-1.5 py-0.5 text-[10px] font-mono text-zinc-500 bg-zinc-800 rounded border border-zinc-700">
                            /
                        </kbd>
                    @endif
                </div>
            </div>

            <!-- Top Right Action Controls -->
            <div class="flex items-center gap-2 sm:gap-3">
                <a
                    href="/admin"
                    class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl text-xs font-semibold bg-zinc-900 hover:bg-zinc-800 text-zinc-200 border border-zinc-800 hover:border-zinc-700 transition-colors shadow-2xs"
                    title="Administration"
                >
                    <svg class="w-4 h-4 text-indigo-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z" />
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                    </svg>
                    <span class="hidden sm:inline">Admin</span>
                </a>
            </div>
        </header>

        <!-- Main Body Flow -->
        <div class="flex-1 px-4 sm:px-6 lg:px-8 py-6 max-w-6xl w-full mx-auto space-y-6">
            <!-- Compact Dark Hero Banner -->
            <div class="relative overflow-hidden rounded-3xl bg-gradient-to-r from-zinc-900 via-zinc-900/90 to-indigo-950/40 border border-zinc-800/80 p-6 sm:p-8 shadow-xl">
                <div class="relative z-10 max-w-2xl">
                    <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-indigo-500/10 border border-indigo-500/30 text-indigo-300 text-xs font-semibold mb-3">
                        <span class="flex h-1.5 w-1.5 rounded-full bg-indigo-400"></span>
                        <span>Portail Interne &bull; Démarches & Fiches</span>
                    </div>
                    <h1 class="text-2xl sm:text-3xl lg:text-4xl font-black text-white tracking-tight leading-tight">
                        La documentation opérationnelle, <br />
                        <span class="text-transparent bg-clip-text bg-gradient-to-r from-indigo-400 via-sky-400 to-emerald-400">instantanée et centralisée</span>
                    </h1>
                    <p class="mt-2.5 text-sm sm:text-base text-zinc-400 leading-relaxed">
                        Trouvez en quelques secondes vos modes opératoires d’équipe, guides RH, outils informatiques et formulaires officiels.
                    </p>
                </div>

                <!-- Ambient Glow Background Decor -->
                <div class="absolute -right-20 -top-20 w-80 h-80 bg-indigo-600/10 rounded-full blur-3xl pointer-events-none"></div>
                <div class="absolute -right-10 -bottom-10 w-60 h-60 bg-sky-600/10 rounded-full blur-2xl pointer-events-none"></div>
            </div>

            <!-- Horizontal Quick Tags Bar -->
            @if($popularTags->isNotEmpty())
                <div class="flex items-center gap-2 overflow-x-auto pb-2 scrollbar-none">
                    <span class="text-xs font-semibold text-zinc-500 shrink-0 mr-1">Filtres rapides :</span>

                    <button
                        type="button"
                        wire:click="clearFilters"
                        class="shrink-0 px-3 py-1 rounded-xl text-xs font-medium transition-all cursor-pointer {{ empty($selectedTag) ? 'bg-indigo-600 text-white shadow-xs' : 'bg-zinc-900 border border-zinc-800 text-zinc-400 hover:text-white hover:bg-zinc-800' }}"
                    >
                        Tous les pôles ({{ $totalTopicsCount }})
                    </button>

                    @foreach($popularTags as $tag)
                        @php
                            $isSelected = $selectedTag === $tag->slug;
                        @endphp
                        <button
                            type="button"
                            wire:click="selectTag('{{ $tag->slug }}')"
                            class="shrink-0 flex items-center gap-1.5 px-3 py-1 rounded-xl text-xs font-medium transition-all cursor-pointer {{ $isSelected ? 'bg-indigo-600 text-white shadow-xs ring-2 ring-indigo-500/20' : 'bg-zinc-900 border border-zinc-800 text-zinc-400 hover:text-white hover:border-zinc-700 hover:bg-zinc-800/80' }}"
                        >
                            <span>#{{ $tag->name }}</span>
                            <span class="px-1.5 py-0.2 rounded-full text-[10px] {{ $isSelected ? 'bg-indigo-700 text-white' : 'bg-zinc-800 text-zinc-500' }}">
                                {{ $tag->topics_count }}
                            </span>
                        </button>
                    @endforeach
                </div>
            @endif

            <!-- Active Results Bar & Client-side Accordion Actions -->
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 text-xs sm:text-sm text-zinc-400 pt-1 border-t border-zinc-850">
                <div class="flex items-center gap-2">
                    @if(!empty($search) || !empty($selectedTag))
                        <span>
                            <strong class="text-white">{{ $topics->count() }}</strong> rubrique(s) trouvée(s)
                            @if(!empty($search))
                                pour « <strong class="text-indigo-400">{{ $search }}</strong> »
                            @endif
                            @if(!empty($selectedTag))
                                dans le pôle <strong class="text-indigo-400">#{{ $selectedTag }}</strong>
                            @endif
                        </span>
                    @else
                        <span>
                            Affichage des <strong class="text-white">{{ $topics->count() }}</strong> rubriques opérationnelles
                        </span>
                    @endif
                </div>

                <div class="flex items-center gap-3 self-end sm:self-auto">
                    @if($topics->isNotEmpty())
                        <button
                            type="button"
                            @click="expandAll([{{ $topics->pluck('id')->implode(',') }}])"
                            class="text-xs font-semibold text-zinc-400 hover:text-indigo-300 transition-colors cursor-pointer"
                        >
                            Tout déplier
                        </button>
                        <span class="text-zinc-700">|</span>
                        <button
                            type="button"
                            @click="collapseAll()"
                            class="text-xs font-semibold text-zinc-400 hover:text-indigo-300 transition-colors cursor-pointer"
                        >
                            Tout replier
                        </button>
                    @endif

                    @if(!empty($search) || !empty($selectedTag))
                        <span class="text-zinc-700">|</span>
                        <button
                            type="button"
                            wire:click="clearFilters"
                            class="text-xs font-semibold text-rose-400 hover:text-rose-300 hover:underline cursor-pointer"
                        >
                            Effacer les filtres
                        </button>
                    @endif
                </div>
            </div>

            <!-- Topics & Procedures Cards List -->
            <div class="space-y-4">
                @forelse($topics as $topic)
                    <div
                        data-topic-id="{{ $topic->id }}"
                        id="procedure-{{ $topic->id }}"
                        class="bg-zinc-900/60 hover:bg-zinc-900/90 border border-zinc-800/80 hover:border-zinc-700/80 rounded-2xl transition-all duration-200 overflow-hidden shadow-lg shadow-black/10 backdrop-blur-sm"
                    >
                        <!-- Topic Header (Accordion Trigger) -->
                        <div
                            @click="toggleTopic({{ $topic->id }})"
                            class="p-5 sm:p-6 cursor-pointer flex items-start justify-between gap-4 select-none hover:bg-zinc-800/30 transition-colors"
                        >
                            <div class="flex items-start gap-4">
                                <!-- Topic Icon Container with Subtle Neon Glow -->
                                <div class="shrink-0 w-11 h-11 sm:w-12 sm:h-12 rounded-2xl bg-indigo-500/10 text-indigo-400 border border-indigo-500/30 flex items-center justify-center shadow-xs">
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
                                    <div class="flex flex-wrap items-center gap-2 mb-1">
                                        <h2 class="text-base sm:text-lg font-bold text-white">
                                            {!! $this->highlightText($topic->title) !!}
                                        </h2>
                                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[11px] font-medium bg-zinc-800 text-zinc-300 border border-zinc-700/60">
                                            {{ $topic->options->count() }} démarche(s)
                                        </span>
                                    </div>

                                    @if($topic->description)
                                        <p class="text-xs sm:text-sm text-zinc-400 leading-relaxed">
                                            {!! $this->highlightText($topic->description) !!}
                                        </p>
                                    @endif

                                    <!-- Tags Pill Row -->
                                    @if($topic->tags->isNotEmpty())
                                        <div class="mt-2.5 flex flex-wrap gap-1.5">
                                            @foreach($topic->tags as $t)
                                                <button
                                                    type="button"
                                                    wire:click.stop="selectTag('{{ $t->slug }}')"
                                                    class="inline-flex items-center px-2 py-0.5 rounded-md text-[11px] font-medium transition-colors cursor-pointer {{ $selectedTag === $t->slug ? 'bg-indigo-600 text-white' : 'bg-zinc-800/80 text-zinc-300 hover:bg-indigo-950/60 hover:text-indigo-300 border border-zinc-700/60' }}"
                                                >
                                                    #{!! $this->highlightText($t->name) !!}
                                                </button>
                                            @endforeach
                                        </div>
                                    @endif
                                </div>
                            </div>

                            <!-- Right Controls: Share link & Chevron -->
                            <div class="shrink-0 flex items-center gap-2 pt-1">
                                <button
                                    type="button"
                                    @click.stop="copyTopicLink({{ $topic->id }})"
                                    class="p-1.5 rounded-lg text-zinc-400 hover:text-white hover:bg-zinc-800 transition-colors cursor-pointer"
                                    title="Copier le lien direct vers cette fiche"
                                >
                                    <template x-if="copiedTopicId === {{ $topic->id }}">
                                        <span class="text-[11px] font-semibold text-emerald-400">Copié !</span>
                                    </template>
                                    <template x-if="copiedTopicId !== {{ $topic->id }}">
                                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1" />
                                        </svg>
                                    </template>
                                </button>

                                <div class="text-zinc-500">
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

                        <!-- Accordion Expansion: Detailed Procedures & Options -->
                        <div
                            x-show="isTopicOpen({{ $topic->id }})"
                            x-transition:enter="transition ease-out duration-200"
                            x-transition:enter-start="opacity-0 -translate-y-1"
                            x-transition:enter-end="opacity-100 translate-y-0"
                            x-transition:leave="transition ease-in duration-150"
                            x-transition:leave-start="opacity-100 translate-y-0"
                            x-transition:leave-end="opacity-0 -translate-y-1"
                            class="border-t border-zinc-800/80 bg-zinc-950/60 p-4 sm:p-6 space-y-3.5"
                        >
                            @forelse($topic->options as $option)
                                <div class="bg-zinc-900/90 rounded-xl p-4 sm:p-5 border border-zinc-800/80 hover:border-zinc-700/80 transition-all shadow-xs">
                                    <div class="flex items-start justify-between gap-3 mb-2">
                                        <h3 class="text-sm sm:text-base font-semibold text-zinc-100 flex items-center gap-2">
                                            @if($option->type === 'faq')
                                                <span class="shrink-0 w-2 h-2 rounded-full bg-indigo-400"></span>
                                            @elseif($option->type === 'link')
                                                <span class="shrink-0 w-2 h-2 rounded-full bg-sky-400"></span>
                                            @elseif($option->type === 'download')
                                                <span class="shrink-0 w-2 h-2 rounded-full bg-emerald-400"></span>
                                            @elseif($option->type === 'contact')
                                                <span class="shrink-0 w-2 h-2 rounded-full bg-amber-400"></span>
                                            @endif
                                            <span>{!! $this->highlightText($option->title) !!}</span>
                                        </h3>

                                        <span class="shrink-0 inline-flex items-center px-2 py-0.5 rounded-md text-[10px] font-bold uppercase tracking-wider {{ match($option->type) {
                                            'faq' => 'bg-indigo-500/10 text-indigo-300 border border-indigo-500/30',
                                            'link' => 'bg-sky-500/10 text-sky-300 border border-sky-500/30',
                                            'download' => 'bg-emerald-500/10 text-emerald-300 border border-emerald-500/30',
                                            'contact' => 'bg-amber-500/10 text-amber-300 border border-amber-500/30',
                                            default => 'bg-zinc-800 text-zinc-300',
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
                                        <div class="text-xs sm:text-sm text-zinc-400 leading-relaxed space-y-1.5">
                                            {!! nl2br($this->highlightText($option->content)) !!}
                                        </div>
                                    @endif

                                    @if($option->action_url)
                                        <div class="mt-3.5 pt-3 border-t border-zinc-800 flex items-center justify-end">
                                            <a
                                                href="{{ $option->action_url }}"
                                                target="_blank"
                                                rel="noopener noreferrer"
                                                class="inline-flex items-center gap-2 px-3 py-1.5 rounded-lg text-xs font-semibold bg-indigo-500/10 hover:bg-indigo-500/20 text-indigo-300 border border-indigo-500/30 transition-colors shadow-2xs"
                                            >
                                                @if($option->type === 'download')
                                                    <svg class="w-3.5 h-3.5 text-emerald-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" />
                                                    </svg>
                                                    <span>Télécharger le document modèle</span>
                                                @elseif($option->type === 'contact')
                                                    <svg class="w-3.5 h-3.5 text-amber-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
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
                                <p class="text-xs text-zinc-500 italic py-2">
                                    Aucune démarche détaillée n’est encore renseignée pour cette thématique.
                                </p>
                            @endforelse
                        </div>
                    </div>
                @empty
                    <!-- Dark Empty State -->
                    <div class="bg-zinc-900/60 rounded-3xl border border-zinc-800 p-8 sm:p-12 text-center max-w-lg mx-auto shadow-xl">
                        <div class="w-16 h-16 mx-auto mb-4 rounded-2xl bg-indigo-500/10 text-indigo-400 border border-indigo-500/20 flex items-center justify-center">
                            <svg class="w-8 h-8" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9.172 16.172a4 4 0 015.656 0M9 10h.01M15 10h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                        </div>
                        <h3 class="text-lg font-bold text-white">Aucune procédure trouvée</h3>
                        <p class="mt-2 text-xs sm:text-sm text-zinc-400 leading-relaxed">
                            Aucun document ne correspond à vos critères « <span class="font-semibold text-indigo-300">{{ $search ?: $selectedTag }}</span> ».
                        </p>
                        <div class="mt-6 flex flex-wrap justify-center gap-3">
                            <button
                                type="button"
                                wire:click="clearFilters"
                                class="px-4 py-2 rounded-xl text-xs sm:text-sm font-semibold bg-indigo-600 text-white hover:bg-indigo-500 transition-colors shadow-xs cursor-pointer"
                            >
                                Réinitialiser les filtres
                            </button>
                        </div>
                    </div>
                @endforelse
            </div>

            <!-- Knowledge Base Contribution Card -->
            <div class="mt-10 rounded-2xl bg-gradient-to-r from-zinc-900 to-indigo-950/40 border border-zinc-800/80 p-5 sm:p-6 flex flex-col sm:flex-row items-center justify-between gap-4">
                <div>
                    <h4 class="text-sm font-bold text-white">Une procédure manque ou est obsolète ?</h4>
                    <p class="text-xs text-zinc-400 mt-0.5">Les référents et équipes habilitées peuvent publier ou mettre à jour des fiches directement depuis l'espace d'administration.</p>
                </div>
                <a
                    href="/admin/topics"
                    class="shrink-0 inline-flex items-center gap-1.5 px-4 py-2 rounded-xl text-xs font-semibold bg-indigo-600 hover:bg-indigo-500 text-white transition-colors shadow-xs"
                >
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                    </svg>
                    <span>Ajouter une procédure</span>
                </a>
            </div>

            <!-- Content Area Minimal Footer -->
            <footer class="pt-8 pb-10 border-t border-zinc-900 text-xs text-zinc-500 flex flex-col sm:flex-row items-center justify-between gap-4">
                <div>
                    <span>NvonchiSearch &bull; Base Opérationnelle Collaborateurs</span>
                </div>
                <div class="flex items-center gap-4">
                    <a href="{{ route('home') }}" class="hover:text-indigo-400 transition-colors">Accueil</a>
                    <a href="{{ route('about') }}" class="hover:text-indigo-400 transition-colors">À propos</a>
                    <a href="{{ route('contact') }}" class="hover:text-indigo-400 transition-colors">Contact</a>
                    <a href="/admin" class="hover:text-indigo-400 transition-colors">Administration</a>
                </div>
            </footer>
        </div>
    </main>
</div>
