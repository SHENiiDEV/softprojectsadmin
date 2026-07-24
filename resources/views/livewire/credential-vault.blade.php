<div class="space-y-6">

    {{-- ═══════════════════════════════════════════════════════════
         HEADER
    ═══════════════════════════════════════════════════════════ --}}
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold text-slate-900 dark:text-white tracking-tight">Credential Vault</h1>
            <p class="text-sm text-slate-500 dark:text-slate-400 mt-0.5">
                Credentials across all companies — {{ $credentials->count() }} records
            </p>
        </div>

        {{-- Group by toggle --}}
        <div class="flex items-center gap-1.5 bg-slate-100 dark:bg-slate-800 rounded-xl p-1">
            <button wire:click="$set('groupBy', 'project')"
                    class="px-3 py-1.5 text-xs font-semibold rounded-lg transition-all duration-200 cursor-pointer
                           {{ $groupBy === 'project'
                               ? 'bg-white dark:bg-slate-700 text-slate-800 dark:text-white shadow-sm'
                               : 'text-slate-500 dark:text-slate-400 hover:text-slate-700 dark:hover:text-slate-200' }}">
                By Company
            </button>
            <button wire:click="$set('groupBy', 'type')"
                    class="px-3 py-1.5 text-xs font-semibold rounded-lg transition-all duration-200 cursor-pointer
                           {{ $groupBy === 'type'
                               ? 'bg-white dark:bg-slate-700 text-slate-800 dark:text-white shadow-sm'
                               : 'text-slate-500 dark:text-slate-400 hover:text-slate-700 dark:hover:text-slate-200' }}">
                By Type
            </button>
        </div>
    </div>

    {{-- ═══════════════════════════════════════════════════════════
         FILTERS
    ═══════════════════════════════════════════════════════════ --}}
    <div class="flex flex-wrap items-center gap-2">

        {{-- Search --}}
        <div class="relative flex-1 min-w-64">
            <svg class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-slate-400 pointer-events-none"
                 fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
            </svg>
            <input wire:model.live.debounce.300ms="search"
                   type="text" placeholder="Search by name, login, company, website..."
                   class="w-full pl-9 pr-3 py-2 text-sm rounded-xl bg-white dark:bg-slate-800
                          border border-slate-200 dark:border-slate-700
                          text-slate-700 dark:text-slate-200 placeholder-slate-400
                          focus:outline-none focus:ring-2 focus:ring-indigo-500 transition-all duration-200"/>
        </div>

        {{-- Type filter --}}
        <select wire:model.live="filterType"
                class="px-3 py-2 text-sm rounded-xl bg-white dark:bg-slate-800
                       border border-slate-200 dark:border-slate-700
                       text-slate-700 dark:text-slate-200
                       focus:outline-none focus:ring-2 focus:ring-indigo-500 cursor-pointer">
            <option value="">All Types</option>
            @foreach($types as $type)
                <option value="{{ $type }}">{{ $type }}</option>
            @endforeach
        </select>

        {{-- Project filter --}}
        <select wire:model.live="filterProject"
                class="px-3 py-2 text-sm rounded-xl bg-white dark:bg-slate-805
                       border border-slate-200 dark:border-slate-700
                       text-slate-700 dark:text-slate-200
                       focus:outline-none focus:ring-2 focus:ring-indigo-500 cursor-pointer max-w-56">
            <option value="">All Companies</option>
            @foreach($projects as $p)
                <option value="{{ $p->id }}">{{ $p->name }}</option>
            @endforeach
        </select>

        {{-- Clear filters --}}
        @if($search || $filterType || $filterProject)
            <button wire:click="$set('search', ''); $set('filterType', ''); $set('filterProject', '')"
                    class="px-3 py-2 text-xs font-semibold text-slate-600 dark:text-slate-300
                           bg-slate-100 dark:bg-slate-700 hover:bg-slate-200 dark:hover:bg-slate-600
                           rounded-xl transition-all duration-200 cursor-pointer flex items-center gap-1.5">
                <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/>
                </svg>
                Reset
            </button>
        @endif
    </div>

    {{-- ═══════════════════════════════════════════════════════════
         GROUPED CREDENTIAL SECTIONS
    ═══════════════════════════════════════════════════════════ --}}
    @if($grouped->isEmpty())
        <div class="flex flex-col items-center justify-center py-20 text-center">
            <div class="w-16 h-16 rounded-2xl bg-slate-100 dark:bg-slate-800 flex items-center justify-center mb-4">
                <svg class="w-8 h-8 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z"/>
                </svg>
            </div>
            <p class="text-slate-600 dark:text-slate-300 font-semibold text-lg">No credentials found</p>
            <p class="text-slate-400 dark:text-slate-500 text-sm mt-1">Try changing the filters.</p>
        </div>
    @else
        <div class="space-y-8">
            @foreach($grouped as $section)
                <div>
                    {{-- Section header --}}
                    <div class="flex items-center gap-3 mb-3">
                        <div class="w-8 h-8 rounded-lg bg-indigo-100 dark:bg-indigo-900/40
                                    flex items-center justify-center flex-shrink-0">
                            @if($groupBy === 'type')
                                <svg class="w-4 h-4 text-indigo-600 dark:text-indigo-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z"/>
                                </svg>
                            @else
                                <svg class="w-4 h-4 text-indigo-600 dark:text-indigo-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/>
                                </svg>
                            @endif
                        </div>
                        <h2 class="text-sm font-bold text-slate-800 dark:text-slate-100 truncate">
                            {{ $section['label'] }}
                        </h2>
                        <span class="text-xs font-semibold text-slate-400 dark:text-slate-500
                                     bg-slate-100 dark:bg-slate-800 px-2 py-0.5 rounded-full flex-shrink-0">
                            {{ $section['items']->count() }}
                        </span>
                        <div class="flex-1 h-px bg-slate-200 dark:bg-slate-700"></div>
                    </div>

                    {{-- Cards grid --}}
                    <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4">
                        @foreach($section['items'] as $cred)
                            @php
                                $typeColors = [
                                    'Portal' => 'bg-indigo-100 text-indigo-700 dark:bg-indigo-900/40 dark:text-indigo-300',
                                    'Email'  => 'bg-sky-100 text-sky-700 dark:bg-sky-900/40 dark:text-sky-300',
                                    'FTP'    => 'bg-amber-100 text-amber-700 dark:bg-amber-900/40 dark:text-amber-300',
                                    'SSH'    => 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/40 dark:text-emerald-300',
                                    'Bank'   => 'bg-green-100 text-green-700 dark:bg-green-900/40 dark:text-green-300',
                                ];
                                $typeColor = $typeColors[$cred->type] ?? 'bg-slate-100 text-slate-600 dark:bg-slate-700 dark:text-slate-300';
                            @endphp

                            <button wire:click="openCredential({{ $cred->id }})"
                                    class="group text-left w-full flex flex-col bg-white dark:bg-slate-800/80 rounded-xl
                                           border border-slate-200 dark:border-slate-700
                                           hover:border-indigo-300 dark:hover:border-indigo-700
                                           hover:shadow-md transition-all duration-200 cursor-pointer overflow-hidden p-4">

                                {{-- Type + lock icon --}}
                                <div class="flex items-center justify-between mb-3">
                                    <span class="inline-flex items-center gap-1.5 text-xs font-bold px-2 py-0.5 rounded-md {{ $typeColor }}">
                                        <svg class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z"/>
                                        </svg>
                                        {{ $cred->type }}
                                    </span>
                                    <svg class="w-4 h-4 text-slate-300 dark:text-slate-600 group-hover:text-indigo-400 transition-colors duration-200"
                                         fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/>
                                    </svg>
                                </div>

                                {{-- Name / login --}}
                                <h3 class="text-sm font-semibold text-slate-800 dark:text-slate-100 truncate mb-1">
                                    {{ $cred->name ?: $cred->login }}
                                </h3>

                                {{-- Login --}}
                                <p class="text-xs text-slate-500 dark:text-slate-400 truncate mb-2">
                                    {{ $cred->login }}
                                </p>

                                {{-- Project (when grouping by type) --}}
                                @if($groupBy === 'type' && $cred->project)
                                    <div class="flex items-center gap-1 mb-1">
                                        <svg class="w-3 h-3 text-slate-400 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/>
                                        </svg>
                                        <span class="text-xs text-slate-400 dark:text-slate-500 truncate">{{ $cred->project->name }}</span>
                                    </div>
                                @endif

                                {{-- Website --}}
                                @if($cred->website)
                                    <div class="flex items-center gap-1">
                                        <svg class="w-3 h-3 text-slate-400 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1"/>
                                        </svg>
                                        <span class="text-xs text-indigo-500 dark:text-indigo-400 truncate">
                                            {{ parse_url($cred->website->url, PHP_URL_HOST) ?: $cred->website->url }}
                                        </span>
                                    </div>
                                @endif

                                {{-- Password dots --}}
                                <div class="mt-3 pt-2.5 border-t border-slate-100 dark:border-slate-700 flex items-center gap-1.5">
                                    <svg class="w-3.5 h-3.5 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
                                    </svg>
                                    <span class="text-xs text-slate-400 dark:text-slate-500 tracking-widest">••••••••</span>
                                </div>
                            </button>
                        @endforeach
                    </div>
                </div>
            @endforeach
        </div>
    @endif

    {{-- ═══════════════════════════════════════════════════════════
         MODAL
    ═══════════════════════════════════════════════════════════ --}}
    @if($showModal && $selectedCredential)
        <div class="fixed inset-0 z-50 flex items-center justify-center p-4"
             wire:click.self="closeModal">

            {{-- Backdrop --}}
            <div class="absolute inset-0 bg-black/50 backdrop-blur-sm"></div>

            {{-- Modal --}}
            <div class="relative bg-white dark:bg-slate-900 rounded-2xl shadow-2xl w-full max-w-lg
                        border border-slate-200 dark:border-slate-700"
                 x-trap="$el">

                {{-- Header --}}
                <div class="flex items-center justify-between p-5 border-b border-slate-100 dark:border-slate-800">
                    <div class="flex items-center gap-3">
                        @php
                            $modalTypeColors = [
                                'Portal' => 'bg-indigo-100 text-indigo-700 dark:bg-indigo-900/40 dark:text-indigo-300',
                                'Email'  => 'bg-sky-100 text-sky-700 dark:bg-sky-900/40 dark:text-sky-300',
                                'FTP'    => 'bg-amber-100 text-amber-700 dark:bg-amber-900/40 dark:text-amber-300',
                                'SSH'    => 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/40 dark:text-emerald-300',
                                'Bank'   => 'bg-green-100 text-green-700 dark:bg-green-900/40 dark:text-green-300',
                            ];
                            $mc = $modalTypeColors[$selectedCredential->type] ?? 'bg-slate-100 text-slate-600 dark:bg-slate-700 dark:text-slate-300';
                        @endphp
                        <span class="px-2.5 py-1 text-xs font-bold rounded-lg {{ $mc }}">
                            {{ $selectedCredential->type }}
                        </span>
                        <h3 class="text-base font-bold text-slate-800 dark:text-slate-100">
                            {{ $selectedCredential->name ?: $selectedCredential->type }}
                        </h3>
                    </div>
                    <button wire:click="closeModal"
                            class="p-2 text-slate-400 hover:text-slate-600 dark:hover:text-slate-200
                                   hover:bg-slate-100 dark:hover:bg-slate-800 rounded-lg transition-all duration-200 cursor-pointer">
                        <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/>
                        </svg>
                    </button>
                </div>

                {{-- Body --}}
                <div class="p-5 space-y-4">

                    {{-- Company --}}
                    @if($selectedCredential->project)
                        <div class="flex items-start gap-3 p-3 bg-slate-50 dark:bg-slate-800 rounded-xl">
                            <svg class="w-4 h-4 text-slate-500 mt-0.5 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/>
                            </svg>
                            <div>
                                <p class="text-xs font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wide">Company</p>
                                <p class="text-sm font-medium text-slate-800 dark:text-slate-100 mt-0.5">{{ $selectedCredential->project->name }}</p>
                            </div>
                        </div>
                    @endif

                    {{-- Website --}}
                    @if($selectedCredential->website)
                        <div class="flex items-start gap-3">
                            <svg class="w-4 h-4 text-slate-500 mt-0.5 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M21 12a9 9 0 01-9 9m9-9a9 9 0 00-9-9m9 9H3m9 9a9 9 0 01-9-9m9 9c1.657 0 3-4.03 3-9s-1.343-9-3-9m0 18c-1.657 0-3-4.03-3-9s1.343-9 3-9m-9 9a9 9 0 019-9"/>
                            </svg>
                            <div class="flex-1 min-w-0">
                                <p class="text-xs font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wide">Website</p>
                                <a href="{{ $selectedCredential->website->url }}" target="_blank"
                                   class="text-sm font-medium text-indigo-600 dark:text-indigo-400 hover:underline truncate block mt-0.5">
                                    {{ $selectedCredential->website->url }}
                                </a>
                            </div>
                        </div>
                    @endif

                    {{-- Login --}}
                    <div class="rounded-xl border border-slate-200 dark:border-slate-700 overflow-hidden">
                        <div class="px-4 py-2 bg-slate-50 dark:bg-slate-800 border-b border-slate-200 dark:border-slate-700">
                            <p class="text-xs font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wide">Login</p>
                        </div>
                        <div class="flex items-center justify-between px-4 py-3">
                            <span class="text-sm font-mono text-slate-800 dark:text-slate-100 select-all">
                                {{ $selectedCredential->login }}
                            </span>
                            <button wire:click="copyToClipboard('login')"
                                    class="flex items-center gap-1.5 px-2.5 py-1.5 text-xs font-semibold
                                           text-indigo-600 dark:text-indigo-400
                                           bg-indigo-50 dark:bg-indigo-900/30 hover:bg-indigo-100 dark:hover:bg-indigo-900/50
                                           rounded-lg transition-all duration-200 cursor-pointer"
                                    x-data x-on:click="
                                        $el.innerHTML = '<svg class=\'w-3 h-3\' fill=\'none\' viewBox=\'0 0 24 24\' stroke=\'currentColor\' stroke-width=\'2.5\'><path stroke-linecap=\'round\' stroke-linejoin=\'round\' d=\'M5 13l4 4L19 7\'/></svg> Copied';
                                        setTimeout(() => $el.innerHTML = '<svg class=\'w-3 h-3\' fill=\'none\' viewBox=\'0 0 24 24\' stroke=\'currentColor\' stroke-width=\'2\'><path stroke-linecap=\'round\' stroke-linejoin=\'round\' d=\'M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z\'/></svg> Copy', 2000);
                                    ">
                                <svg class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"/>
                                </svg>
                                Copy
                            </button>
                        </div>
                    </div>

                    {{-- Password --}}
                    <div class="rounded-xl border border-slate-200 dark:border-slate-700 overflow-hidden">
                        <div class="px-4 py-2 bg-slate-50 dark:bg-slate-800 border-b border-slate-200 dark:border-slate-700">
                            <p class="text-xs font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wide">Password</p>
                        </div>
                        <div class="flex items-center justify-between px-4 py-3 gap-3">
                            <span class="text-sm font-mono text-slate-800 dark:text-slate-100 flex-1 truncate select-all">
                                @if($showPassword)
                                    {{ $selectedCredential->password }}
                                @else
                                    ••••••••••••••••
                                @endif
                            </span>
                            <div class="flex items-center gap-1.5 flex-shrink-0">
                                <button wire:click="togglePassword"
                                        class="p-1.5 text-slate-500 hover:text-slate-700 dark:hover:text-slate-200
                                               hover:bg-slate-100 dark:hover:bg-slate-700 rounded-lg transition-all duration-200 cursor-pointer">
                                    @if($showPassword)
                                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.543 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21"/>
                                        </svg>
                                    @else
                                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                                        </svg>
                                    @endif
                                </button>
                                <button wire:click="copyToClipboard('password')"
                                        class="flex items-center gap-1.5 px-2.5 py-1.5 text-xs font-semibold
                                               text-indigo-600 dark:text-indigo-400
                                               bg-indigo-50 dark:bg-indigo-900/30 hover:bg-indigo-100 dark:hover:bg-indigo-900/50
                                               rounded-lg transition-all duration-200 cursor-pointer"
                                        x-data x-on:click="
                                            $el.innerHTML = '<svg class=\'w-3 h-3\' fill=\'none\' viewBox=\'0 0 24 24\' stroke=\'currentColor\' stroke-width=\'2.5\'><path stroke-linecap=\'round\' stroke-linejoin=\'round\' d=\'M5 13l4 4L19 7\'/></svg> Copied';
                                            setTimeout(() => $el.innerHTML = '<svg class=\'w-3 h-3\' fill=\'none\' viewBox=\'0 0 24 24\' stroke=\'currentColor\' stroke-width=\'2\'><path stroke-linecap=\'round\' stroke-linejoin=\'round\' d=\'M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z\'/></svg> Copy', 2000);
                                        ">
                                    <svg class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"/>
                                    </svg>
                                    Copy
                                </button>
                            </div>
                        </div>
                    </div>

                    {{-- Comments --}}
                    @if($selectedCredential->comments)
                        <div class="rounded-xl border border-slate-200 dark:border-slate-700 overflow-hidden">
                            <div class="px-4 py-2 bg-slate-50 dark:bg-slate-800 border-b border-slate-200 dark:border-slate-700">
                                <p class="text-xs font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wide">Comment</p>
                            </div>
                            <p class="px-4 py-3 text-sm text-slate-700 dark:text-slate-300">
                                {{ $selectedCredential->comments }}
                            </p>
                        </div>
                    @endif
                </div>

                {{-- Footer --}}
                <div class="px-5 py-4 border-t border-slate-100 dark:border-slate-800">
                    <button wire:click="closeModal"
                            class="w-full px-4 py-2.5 bg-slate-100 dark:bg-slate-800 text-slate-700 dark:text-slate-200
                                   hover:bg-slate-200 dark:hover:bg-slate-700 rounded-xl text-sm font-semibold
                                   transition-all duration-200 cursor-pointer">
                        Close
                    </button>
                </div>
            </div>
        </div>
    @endif
</div>

{{-- Copy to clipboard JS --}}
<script>
    document.addEventListener('livewire:init', () => {
        Livewire.on('copy-to-clipboard', (event) => {
            if (navigator.clipboard && event.value) {
                navigator.clipboard.writeText(event.value).catch(() => {});
            }
        });
    });
</script>
