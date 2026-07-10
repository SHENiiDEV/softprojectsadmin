<div class="space-y-6">
    <!-- Header -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 pb-5 border-b border-slate-100 dark:border-slate-800/60">
        <div>
            <h1 class="font-outfit font-extrabold text-2xl text-slate-800 dark:text-white tracking-tight">Company Health Score</h1>
            <p class="text-sm text-slate-500 dark:text-slate-400 mt-1">Completeness rating of integrated data and documentation across active companies.</p>
        </div>
        <div class="flex-shrink-0">
            <span class="inline-flex items-center px-3 py-1.5 rounded-xl bg-slate-100 dark:bg-slate-900 text-xs font-semibold text-slate-600 dark:text-slate-400 border border-slate-200/40 dark:border-slate-800/40 shadow-sm">
                <i class="fa-solid fa-building mr-1.5 opacity-60"></i> {{ $companies->count() }} Companies
            </span>
        </div>
    </div>

    <!-- Filters & Actions -->
    <div class="bg-white dark:bg-slate-900 border border-slate-100 dark:border-slate-800/80 rounded-2xl p-5 shadow-sm">
        <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
            <!-- Search, Client Filter & Sort -->
            <div class="flex-1 grid grid-cols-1 sm:grid-cols-3 gap-4">
                <!-- Search input -->
                <div class="relative">
                    <span class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-slate-400">
                        <i class="fa-solid fa-magnifying-glass text-xs"></i>
                    </span>
                    <input type="text" 
                           wire:model.live.debounce.300ms="search" 
                           placeholder="Search companies by name..." 
                           class="w-full pl-10 pr-4 py-2.5 bg-slate-50 dark:bg-slate-950 border border-slate-200 dark:border-slate-850 rounded-xl text-xs text-slate-850 dark:text-slate-100 placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-sky-500/20 focus:border-sky-500 transition-all">
                </div>

                <!-- Client filter -->
                <div class="relative">
                    <select wire:model.live="filterClient" 
                            class="w-full px-3.5 py-2.5 bg-slate-50 dark:bg-slate-950 border border-slate-200 dark:border-slate-850 rounded-xl text-xs text-slate-855 dark:text-slate-100 focus:outline-none focus:ring-2 focus:ring-sky-500/20 focus:border-sky-500 transition-all">
                        <option value="">All Clients</option>
                        @foreach($clients as $client)
                            <option value="{{ $client->id }}">{{ $client->name }}</option>
                        @endforeach
                    </select>
                </div>

                <!-- Sort select -->
                <div class="relative">
                    <select wire:model.live="sortOrder" 
                            class="w-full px-3.5 py-2.5 bg-slate-50 dark:bg-slate-950 border border-slate-200 dark:border-slate-850 rounded-xl text-xs text-slate-855 dark:text-slate-100 focus:outline-none focus:ring-2 focus:ring-sky-500/20 focus:border-sky-500 transition-all">
                        <option value="desc">Highest Health Score first</option>
                        <option value="asc">Lowest Health Score first</option>
                    </select>
                </div>
            </div>

            <!-- View Modes (Grid / List Toggles) -->
            <div class="flex items-center bg-slate-100 dark:bg-slate-950 p-1 rounded-xl border border-slate-200/40 dark:border-slate-800/60 shadow-inner flex-shrink-0">
                <button type="button" 
                        wire:click="$set('viewMode', 'grid')" 
                        class="px-3.5 py-2 rounded-lg text-xs font-bold transition-all duration-150 focus:outline-none flex items-center gap-1.5 {{ $viewMode === 'grid' ? 'bg-white dark:bg-slate-800 text-sky-600 dark:text-sky-400 shadow-sm border border-slate-200/20 dark:border-slate-700/20' : 'text-slate-500 hover:text-slate-700 dark:hover:text-slate-350 border border-transparent' }}">
                    <i class="fa-solid fa-table-cells"></i> Grid
                </button>
                <button type="button" 
                        wire:click="$set('viewMode', 'list')" 
                        class="px-3.5 py-2 rounded-lg text-xs font-bold transition-all duration-150 focus:outline-none flex items-center gap-1.5 {{ $viewMode === 'list' ? 'bg-white dark:bg-slate-800 text-sky-600 dark:text-sky-400 shadow-sm border border-slate-200/20 dark:border-slate-700/20' : 'text-slate-500 hover:text-slate-700 dark:hover:text-slate-350 border border-transparent' }}">
                    <i class="fa-solid fa-list"></i> List
                </button>
            </div>
        </div>
    </div>

    @if($viewMode === 'list')
    <!-- List Mode Table -->
    <div class="bg-white dark:bg-slate-900 border border-slate-100 dark:border-slate-800/80 rounded-2xl shadow-sm overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse">
                <thead>
                    <tr class="text-slate-400 dark:text-slate-500 text-xs font-semibold uppercase border-b border-slate-100 dark:border-slate-800/60">
                        <th class="py-4 px-6">Company</th>
                        <th class="py-4 px-6 text-center">Score</th>
                        <th class="py-4 px-6">Health Rating</th>
                        <th class="py-4 px-6">Checks Checklist</th>
                        <th class="py-4 px-6 text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 dark:divide-slate-800/40 text-sm">
                    @forelse($companies as $company)
                        @php $h = $company->health; @endphp
                        <tr class="hover:bg-slate-50/50 dark:hover:bg-slate-850/10 transition-colors">
                            <!-- Company Name -->
                            <td class="py-4 px-6">
                                <div class="flex items-center gap-2.5 min-w-0">
                                    <span class="flex-shrink-0 w-2.5 h-2.5 rounded-full
                                        @if($company->status === 'active') bg-emerald-500
                                        @elseif($company->status === 'onboarding') bg-amber-500
                                        @else bg-rose-500 @endif">
                                    </span>
                                    <span class="font-semibold text-slate-800 dark:text-slate-200 truncate">
                                        {{ $company->name }}
                                    </span>
                                </div>
                            </td>
                            <!-- Score -->
                            <td class="py-4 px-6 text-center font-outfit font-black text-base {{ $h['color']['text'] }}">
                                {{ $h['score'] }}%
                            </td>
                            <!-- Progress Bar -->
                            <td class="py-4 px-6 min-w-[150px]">
                                <div class="space-y-1">
                                    <div class="w-full bg-slate-100 dark:bg-slate-950 rounded-full h-1.5 overflow-hidden shadow-inner border border-slate-200/10 dark:border-slate-850">
                                        <div class="h-1.5 rounded-full {{ $h['color']['bar'] }}" style="width: {{ $h['score'] }}%"></div>
                                    </div>
                                    <span class="text-[10px] text-slate-400 dark:text-slate-550 font-semibold">{{ $h['passed'] }} / {{ $h['total'] }} Checks passed</span>
                                </div>
                            </td>
                            <!-- Details Indicators -->
                            <td class="py-4 px-6">
                                <div class="flex flex-wrap gap-1.5 max-w-[450px]">
                                    @foreach($h['checks'] as $label => $passed)
                                        @php
                                            $icon = match($label) {
                                                'Website'         => 'fa-globe',
                                                'Director'        => 'fa-user-tie',
                                                'KYB'             => 'fa-building-shield',
                                                'Onboarding'      => 'fa-user-check',
                                                'CFS'             => 'fa-shield-halved',
                                                'Bank'            => 'fa-building-columns',
                                                'Companies House' => 'fa-house-chimney-window',
                                                'Report'          => 'fa-file-invoice',
                                                'Credentials'     => 'fa-key',
                                                default           => 'fa-check'
                                            };
                                        @endphp
                                        <span class="inline-flex items-center gap-1.5 px-2 py-0.5 rounded-lg text-[9px] font-semibold border {{ $passed ? 'bg-emerald-50/50 text-emerald-700 border-emerald-100/50 dark:bg-emerald-950/20 dark:text-emerald-400 dark:border-emerald-900/30' : 'bg-rose-50/50 text-rose-700 border-rose-100/50 dark:bg-rose-950/20 dark:text-rose-400 dark:border-rose-900/30' }}">
                                            <i class="fa-solid {{ $icon }} opacity-60"></i>
                                            <span>{{ $label }}</span>
                                            <i class="fa-solid {{ $passed ? 'fa-check text-[7.5px]' : 'fa-xmark text-[7.5px]' }} opacity-70"></i>
                                        </span>
                                    @endforeach
                                </div>
                            </td>
                            <!-- Actions -->
                            <td class="py-4 px-6 text-right">
                                <a href="{{ route('projects.show', $company->id) }}?tab=boarding" class="inline-flex items-center justify-center p-2 rounded-xl text-slate-400 hover:text-indigo-600 hover:bg-slate-50 dark:hover:bg-slate-800 transition-all shadow-sm border border-transparent hover:border-slate-200/50 dark:hover:border-slate-700" title="Open Boarding Section" wire:navigate>
                                    <i class="fa-solid fa-chevron-right text-xs"></i>
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="py-12 text-center text-slate-400 dark:text-slate-500">
                                <div class="flex flex-col items-center justify-center space-y-2">
                                    <i class="fa-solid fa-building-circle-exclamation text-3xl text-slate-300 dark:text-slate-700"></i>
                                    <span class="text-sm font-semibold mt-2">No companies match your filters.</span>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
    @else
    <!-- Grid Layout -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
        @forelse($companies as $company)
            @php $h = $company->health; @endphp
            <a href="{{ route('projects.show', $company->id) }}?tab=boarding"
               class="group relative bg-white dark:bg-slate-900 border border-slate-100 dark:border-slate-800/80 rounded-2xl p-6 shadow-sm hover:shadow-xl hover:border-sky-500/30 dark:hover:border-sky-500/20 transition-all duration-300 flex flex-col justify-between cursor-pointer"
               wire:navigate>
                
                <div>
                    <!-- Top Line -->
                    <div class="flex items-start justify-between gap-4 mb-4">
                        <div class="min-w-0">
                            <h3 class="font-outfit font-bold text-base text-slate-800 dark:text-white group-hover:text-indigo-600 dark:group-hover:text-sky-400 transition-colors duration-150 truncate">
                                {{ $company->name }}
                            </h3>
                            <span class="inline-flex items-center gap-1.5 mt-2 px-2.5 py-0.5 rounded-full text-[10px] font-semibold uppercase tracking-wider
                                @if($company->status === 'active') bg-emerald-50 text-emerald-700 dark:bg-emerald-950/30 dark:text-emerald-450 border border-emerald-100/50 dark:border-emerald-900/30
                                @elseif($company->status === 'onboarding') bg-amber-50 text-amber-700 dark:bg-amber-950/30 dark:text-amber-450 border border-amber-100/50 dark:border-amber-900/30
                                @else bg-rose-50 text-rose-700 dark:bg-rose-950/30 dark:text-rose-455 border border-rose-100/50 dark:border-rose-900/30 @endif">
                                <span class="w-1.5 h-1.5 rounded-full
                                    @if($company->status === 'active') bg-emerald-500
                                    @elseif($company->status === 'onboarding') bg-amber-500
                                    @else bg-rose-500 @endif"></span>
                                {{ $company->status }}
                            </span>
                        </div>
                        <div class="flex-shrink-0 flex flex-col items-end">
                            <span class="text-2xl font-outfit font-black {{ $h['color']['text'] }}">
                                {{ $h['score'] }}%
                            </span>
                            <span class="text-[9px] font-bold text-slate-400 dark:text-slate-500 uppercase tracking-wider">Health</span>
                        </div>
                    </div>

                    {{-- Progress bar --}}
                    <div class="space-y-1.5 mb-5">
                        <div class="w-full bg-slate-100 dark:bg-slate-950 rounded-full h-2 overflow-hidden shadow-inner border border-slate-200/10 dark:border-slate-850">
                            <div class="h-2 rounded-full transition-all duration-500 ease-out {{ $h['color']['bar'] }}"
                                 style="width: {{ $h['score'] }}%"></div>
                        </div>
                        <div class="flex items-center justify-between text-[10px] text-slate-400 dark:text-slate-550 font-semibold">
                            <span>Completeness</span>
                            <span>{{ $h['passed'] }} of {{ $h['total'] }} Checks</span>
                        </div>
                    </div>
                </div>

                {{-- Checks Grid --}}
                <div class="grid grid-cols-2 gap-2 pt-4 border-t border-slate-100 dark:border-slate-800/60 mt-auto">
                    @foreach($h['checks'] as $label => $passed)
                        @php
                            $icon = match($label) {
                                'Website'         => 'fa-globe',
                                'Director'        => 'fa-user-tie',
                                'KYB'             => 'fa-building-shield',
                                'Onboarding'      => 'fa-user-check',
                                'CFS'             => 'fa-shield-halved',
                                'Bank'            => 'fa-building-columns',
                                'Companies House' => 'fa-house-chimney-window',
                                'Report'          => 'fa-file-invoice',
                                'Credentials'     => 'fa-key',
                                default           => 'fa-check'
                            };
                        @endphp
                        <div class="flex items-center space-x-1.5 p-1.5 rounded-xl text-[11px] font-medium leading-none {{ $passed ? 'bg-emerald-50/40 text-emerald-700 dark:bg-emerald-950/10 dark:text-emerald-400' : 'bg-rose-50/40 text-rose-700 dark:bg-rose-950/10 dark:text-rose-400' }}">
                            <span class="flex-shrink-0 flex items-center justify-center w-4 h-4 rounded-md {{ $passed ? 'bg-emerald-100/50 text-emerald-600 dark:bg-emerald-900/40 dark:text-emerald-400' : 'bg-rose-100/50 text-rose-600 dark:bg-rose-900/40 dark:text-rose-400' }}">
                                <i class="fa-solid {{ $passed ? 'fa-check text-[9px]' : 'fa-xmark text-[9px]' }}"></i>
                            </span>
                            <span class="truncate flex items-center gap-1">
                                <i class="fa-solid {{ $icon }} opacity-40 text-[9px]"></i>
                                {{ $label }}
                            </span>
                        </div>
                    @endforeach
                </div>
            </a>
        @empty
            <div class="col-span-full bg-white dark:bg-slate-900 border border-slate-100 dark:border-slate-800/80 rounded-2xl p-16 text-center shadow-sm">
                <div class="text-5xl mb-4">🏢</div>
                <h3 class="font-outfit font-bold text-lg text-slate-800 dark:text-white">No Companies Found</h3>
                <p class="text-slate-500 dark:text-slate-400 text-sm mt-1">No companies match your search query.</p>
            </div>
        @endforelse
    </div>
    @endif
</div>
