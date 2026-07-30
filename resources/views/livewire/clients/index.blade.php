<div class="space-y-6">
    <x-slot name="header">
        Clients Registry
    </x-slot>

    <!-- Top Action Bar -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <h1 class="font-outfit font-extrabold text-2xl text-slate-800 dark:text-white tracking-tight">Clients Registry</h1>
            <p class="text-sm text-slate-500 dark:text-slate-400 mt-1">Manage client accounts, view linked companies, and access Jira support portals.</p>
        </div>
        @if(auth()->user()->hasAnyRole(['admin', 'manager']))
            <div>
                <button wire:click="openCreateModal" class="inline-flex items-center justify-center px-4 py-2.5 text-sm font-semibold text-white bg-sky-600 hover:bg-sky-500 dark:bg-sky-500 dark:hover:bg-sky-400 rounded-xl shadow-sm hover:shadow-sky-500/25 hover:-translate-y-0.5 transition-all duration-200 cursor-pointer">
                    <svg class="w-4 h-4 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4" />
                    </svg>
                    Add Client
                </button>
            </div>
        @endif
    </div>

    <!-- Alert Messages -->
    @if (session()->has('message'))
        <div x-data="{ show: true }" x-show="show" x-init="setTimeout(() => show = false, 4000)" x-transition class="p-4 rounded-xl bg-emerald-50 dark:bg-emerald-950/20 border border-emerald-100 dark:border-emerald-800/40 text-emerald-800 dark:text-emerald-400 flex items-center justify-between shadow-sm">
            <div class="flex items-center space-x-3">
                <i class="fa-solid fa-circle-check text-emerald-600 dark:text-emerald-400 text-lg"></i>
                <span class="text-sm font-medium">{{ session('message') }}</span>
            </div>
            <button @click="show = false" class="text-emerald-500 hover:text-emerald-700 dark:hover:text-emerald-300">
                <i class="fa-solid fa-xmark"></i>
            </button>
        </div>
    @endif

    @if (session()->has('error'))
        <div x-data="{ show: true }" x-show="show" x-init="setTimeout(() => show = false, 4000)" x-transition class="p-4 rounded-xl bg-rose-50 dark:bg-rose-950/20 border border-rose-100 dark:border-rose-800/40 text-rose-800 dark:text-rose-400 flex items-center justify-between shadow-sm">
            <div class="flex items-center space-x-3">
                <i class="fa-solid fa-triangle-exclamation text-rose-600 dark:text-rose-400 text-lg"></i>
                <span class="text-sm font-medium">{{ session('error') }}</span>
            </div>
            <button @click="show = false" class="text-rose-500 hover:text-rose-700 dark:hover:text-rose-300">
                <i class="fa-solid fa-xmark"></i>
            </button>
        </div>
    @endif

    <!-- KPI Stats Bar -->
    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
        <div class="bg-white dark:bg-slate-900 border border-slate-100 dark:border-slate-800/80 p-5 rounded-2xl shadow-sm flex items-center justify-between">
            <div>
                <span class="text-xs font-semibold uppercase tracking-wider text-slate-400 dark:text-slate-500">Total Clients</span>
                <h3 class="font-outfit font-bold text-2xl text-slate-800 dark:text-white mt-1">{{ number_format($totalClients) }}</h3>
            </div>
            <div class="w-12 h-12 rounded-xl bg-sky-50 dark:bg-sky-950/40 border border-sky-100 dark:border-sky-800/40 flex items-center justify-center text-sky-600 dark:text-sky-400">
                <i class="fa-solid fa-users text-xl"></i>
            </div>
        </div>

        <div class="bg-white dark:bg-slate-900 border border-slate-100 dark:border-slate-800/80 p-5 rounded-2xl shadow-sm flex items-center justify-between">
            <div>
                <span class="text-xs font-semibold uppercase tracking-wider text-slate-400 dark:text-slate-500">Total Linked Companies</span>
                <h3 class="font-outfit font-bold text-2xl text-slate-800 dark:text-white mt-1">{{ number_format($totalCompanies) }}</h3>
            </div>
            <div class="w-12 h-12 rounded-xl bg-indigo-50 dark:bg-indigo-950/40 border border-indigo-100 dark:border-indigo-800/40 flex items-center justify-center text-indigo-600 dark:text-indigo-400">
                <i class="fa-solid fa-building text-xl"></i>
            </div>
        </div>

        <div class="bg-white dark:bg-slate-900 border border-slate-100 dark:border-slate-800/80 p-5 rounded-2xl shadow-sm flex items-center justify-between">
            <div>
                <span class="text-xs font-semibold uppercase tracking-wider text-slate-400 dark:text-slate-500">Avg Companies / Client</span>
                <h3 class="font-outfit font-bold text-2xl text-slate-800 dark:text-white mt-1">
                    {{ $totalClients > 0 ? round($totalCompanies / $totalClients, 1) : 0 }}
                </h3>
            </div>
            <div class="w-12 h-12 rounded-xl bg-emerald-50 dark:bg-emerald-950/40 border border-emerald-100 dark:border-emerald-800/40 flex items-center justify-center text-emerald-600 dark:text-emerald-400">
                <i class="fa-solid fa-chart-pie text-xl"></i>
            </div>
        </div>
    </div>

    <!-- Controls Toolbar (Search, Filter, Sort, View Toggle) -->
    <div class="bg-white dark:bg-slate-900 border border-slate-100 dark:border-slate-800/80 rounded-2xl p-4 shadow-sm flex flex-col md:flex-row md:items-center justify-between gap-4">
        <!-- Search -->
        <div class="relative flex-1 max-w-md">
            <span class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-slate-400">
                <i class="fa-solid fa-magnifying-glass"></i>
            </span>
            <input type="text" 
                   wire:model.live.debounce.300ms="search" 
                   placeholder="Search by client name..." 
                   class="w-full pl-10 pr-4 py-2.5 bg-slate-50 dark:bg-slate-950 border border-slate-200 dark:border-slate-800/80 rounded-xl text-sm text-slate-800 dark:text-slate-100 placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-sky-500/20 focus:border-sky-500 transition-all duration-200">
            @if($search)
                <button wire:click="$set('search', '')" class="absolute inset-y-0 right-0 pr-3 flex items-center text-slate-400 hover:text-slate-600 dark:hover:text-slate-200">
                    <i class="fa-solid fa-xmark text-sm"></i>
                </button>
            @endif
        </div>

        <!-- Filter & Sort & View Switcher -->
        <div class="flex flex-wrap items-center gap-3">
            <!-- Filter -->
            <select wire:model.live="filterCompanies" class="px-3.5 py-2.5 bg-slate-50 dark:bg-slate-950 border border-slate-200 dark:border-slate-800/80 rounded-xl text-xs font-semibold text-slate-700 dark:text-slate-200 focus:outline-none focus:ring-2 focus:ring-sky-500/20 focus:border-sky-500 transition-all">
                <option value="all">All Clients</option>
                <option value="with_companies">With Companies</option>
                <option value="empty">Without Companies</option>
            </select>

            <!-- Sort -->
            <select wire:model.live="sortBy" class="px-3.5 py-2.5 bg-slate-50 dark:bg-slate-950 border border-slate-200 dark:border-slate-800/80 rounded-xl text-xs font-semibold text-slate-700 dark:text-slate-200 focus:outline-none focus:ring-2 focus:ring-sky-500/20 focus:border-sky-500 transition-all">
                <option value="id_desc">Newest First</option>
                <option value="name_asc">Name (A-Z)</option>
                <option value="companies_desc">Most Companies</option>
            </select>

            <!-- View Switcher -->
            <div class="inline-flex p-1 bg-slate-100 dark:bg-slate-950 border border-slate-200/60 dark:border-slate-800/60 rounded-xl">
                <button wire:click="$set('viewMode', 'table')" class="px-3 py-1.5 rounded-lg text-xs font-semibold transition-all duration-150 cursor-pointer {{ $viewMode === 'table' ? 'bg-white dark:bg-slate-900 text-sky-600 dark:text-sky-400 shadow-sm' : 'text-slate-500 dark:text-slate-400 hover:text-slate-800 dark:hover:text-slate-200' }}">
                    <i class="fa-solid fa-list mr-1"></i> Table
                </button>
                <button wire:click="$set('viewMode', 'grid')" class="px-3 py-1.5 rounded-lg text-xs font-semibold transition-all duration-150 cursor-pointer {{ $viewMode === 'grid' ? 'bg-white dark:bg-slate-900 text-sky-600 dark:text-sky-400 shadow-sm' : 'text-slate-500 dark:text-slate-400 hover:text-slate-800 dark:hover:text-slate-200' }}">
                    <i class="fa-solid fa-border-all mr-1"></i> Cards
                </button>
            </div>
        </div>
    </div>

    <!-- MAIN CONTENT VIEW -->
    @if($viewMode === 'table')
        <!-- TABLE VIEW WITH EXPANDABLE ROWS -->
        <div class="bg-white dark:bg-slate-900 border border-slate-100 dark:border-slate-800/80 rounded-2xl shadow-sm overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse">
                    <thead>
                        <tr class="text-slate-400 dark:text-slate-500 text-xs font-semibold uppercase border-b border-slate-100 dark:border-slate-800/60 bg-slate-50/50 dark:bg-slate-950/20">
                            <th class="py-4 px-6">Client</th>
                            <th class="py-4 px-6">Companies Linked</th>
                            <th class="py-4 px-6">Portal Link (Jira Support)</th>
                            @if(auth()->user()->hasAnyRole(['admin', 'manager']))
                                <th class="py-4 px-6 text-right">Actions</th>
                            @endif
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 dark:divide-slate-800/40 text-sm">
                        @forelse($clients as $client)
                            @php
                                $portalUrl = url('/portal/' . $client->hash);
                                $btnId = 'copy-btn-table-' . $client->id;
                                $isExpanded = in_array($client->id, $expandedClientIds);
                                $initials = strtoupper(substr($client->name, 0, 2));
                            @endphp
                            <!-- Main Row -->
                            <tr class="hover:bg-slate-50/70 dark:hover:bg-slate-800/20 transition-colors duration-150 {{ $isExpanded ? 'bg-sky-50/30 dark:bg-sky-950/10' : '' }}">
                                <!-- Client Avatar & Name -->
                                <td class="py-4 px-6">
                                    <div class="flex items-center space-x-3">
                                        <div class="w-9 h-9 rounded-xl bg-gradient-to-tr from-sky-500 to-indigo-600 text-white font-bold text-xs flex items-center justify-center shadow-sm">
                                            {{ $initials }}
                                        </div>
                                        <div>
                                            <span class="font-bold text-slate-800 dark:text-slate-100 block">{{ $client->name }}</span>
                                            <span class="text-xs text-slate-400">ID: #{{ $client->id }}</span>
                                        </div>
                                    </div>
                                </td>

                                <!-- Companies Count & Expand Button -->
                                <td class="py-4 px-6">
                                    <button wire:click="toggleExpand({{ $client->id }})" 
                                            class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl text-xs font-semibold transition-all duration-150 cursor-pointer {{ $client->companies_count > 0 ? 'bg-sky-50 text-sky-700 dark:bg-sky-950/40 dark:text-sky-300 border border-sky-200/50 dark:border-sky-800/50 hover:bg-sky-100' : 'bg-slate-100 text-slate-500 dark:bg-slate-800 dark:text-slate-400' }}">
                                        <i class="fa-solid fa-building text-sky-500"></i>
                                        <span>{{ $client->companies_count }} {{ Str::plural('company', $client->companies_count) }}</span>
                                        <i class="fa-solid fa-chevron-down transition-transform duration-200 text-[10px] ml-1 {{ $isExpanded ? 'rotate-180' : '' }}"></i>
                                    </button>
                                </td>

                                <!-- Portal Link -->
                                <td class="py-4 px-6">
                                    <div class="flex items-center space-x-2">
                                        <span class="font-mono text-xs text-slate-500 dark:text-slate-400 bg-slate-50 dark:bg-slate-950 px-2.5 py-1.5 rounded-lg border border-slate-200/60 dark:border-slate-800 select-all max-w-xs truncate" title="{{ $portalUrl }}">
                                            {{ $portalUrl }}
                                        </span>
                                        <button type="button" 
                                                id="{{ $btnId }}"
                                                onclick="window.copyPortalLink('{{ $portalUrl }}', '{{ $btnId }}')" 
                                                class="inline-flex items-center px-2.5 py-1.5 text-xs font-semibold text-indigo-700 bg-indigo-50 hover:bg-indigo-100 dark:bg-indigo-950/40 dark:text-indigo-400 border border-indigo-200/40 dark:border-indigo-900/40 rounded-lg shadow-sm transition-all duration-150 cursor-pointer">
                                            <i class="fa-solid fa-copy mr-1"></i> Copy Link
                                        </button>
                                    </div>
                                </td>

                                <!-- Actions -->
                                @if(auth()->user()->hasAnyRole(['admin', 'manager']))
                                    <td class="py-4 px-6 text-right space-x-1.5 whitespace-nowrap">
                                        <button type="button" 
                                                wire:click="openEditModal({{ $client->id }})" 
                                                class="inline-flex items-center justify-center p-2 rounded-lg text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-800 hover:text-sky-600 dark:hover:text-sky-400 transition-colors duration-150 cursor-pointer" 
                                                title="Edit Client">
                                            <i class="fa-solid fa-pen"></i>
                                        </button>

                                        <button type="button" 
                                                wire:click="deleteClient({{ $client->id }})" 
                                                wire:confirm="Are you sure you want to delete this client? All associated companies will be unlinked."
                                                class="inline-flex items-center justify-center p-2 rounded-lg text-slate-400 hover:bg-rose-50 dark:hover:bg-rose-950/20 hover:text-rose-600 dark:hover:text-rose-400 transition-colors duration-150 cursor-pointer" 
                                                title="Delete Client">
                                            <i class="fa-solid fa-trash"></i>
                                        </button>
                                    </td>
                                @endif
                            </tr>

                            <!-- Expandable Drawer for Companies -->
                            @if($isExpanded)
                                <tr class="bg-slate-50/80 dark:bg-slate-950/40">
                                    <td colspan="4" class="p-4 sm:px-8">
                                        <div class="bg-white dark:bg-slate-900 rounded-xl border border-slate-200/70 dark:border-slate-800 p-4 shadow-inner space-y-3">
                                            <div class="flex items-center justify-between border-b border-slate-100 dark:border-slate-800/80 pb-2">
                                                <h4 class="text-xs font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400 flex items-center gap-2">
                                                    <i class="fa-solid fa-layer-group text-sky-500"></i>
                                                    Linked Companies for {{ $client->name }} ({{ $client->companies->count() }})
                                                </h4>
                                            </div>

                                            @if($client->companies->isEmpty())
                                                <p class="text-xs text-slate-400 italic py-2">No companies currently assigned to this client.</p>
                                            @else
                                                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-3 pt-1">
                                                    @foreach($client->companies as $company)
                                                        @php
                                                            $mainWebsite = $company->websites->first();
                                                        @endphp
                                                        <div class="bg-slate-50 dark:bg-slate-950 border border-slate-200/60 dark:border-slate-800/80 rounded-xl p-3.5 space-y-2 hover:border-sky-300 dark:hover:border-sky-700 transition-all">
                                                            <div class="flex items-start justify-between gap-2">
                                                                <a href="{{ route('projects.show', $company->id) }}" class="font-bold text-xs text-slate-800 dark:text-white hover:text-sky-600 dark:hover:text-sky-400 line-clamp-1">
                                                                    {{ $company->name }}
                                                                </a>
                                                                <span class="inline-flex px-2 py-0.5 rounded text-[10px] font-semibold uppercase {{ $company->archived_at ? 'bg-amber-100 text-amber-800 dark:bg-amber-950/40 dark:text-amber-300' : 'bg-emerald-100 text-emerald-800 dark:bg-emerald-950/40 dark:text-emerald-300' }}">
                                                                    {{ $company->archived_at ? 'Archived' : 'Active' }}
                                                                </span>
                                                            </div>

                                                            @if($company->ubo)
                                                                <p class="text-[11px] text-slate-500 dark:text-slate-400 flex items-center gap-1.5">
                                                                    <i class="fa-solid fa-user text-slate-400"></i>
                                                                    <span>UBO: {{ $company->ubo }}</span>
                                                                </p>
                                                            @endif

                                                            @if($mainWebsite)
                                                                <p class="text-[11px] font-mono text-sky-600 dark:text-sky-400 truncate flex items-center gap-1.5">
                                                                    <i class="fa-solid fa-globe"></i>
                                                                    <a href="{{ $mainWebsite->url }}" target="_blank" class="hover:underline">{{ $mainWebsite->url }}</a>
                                                                </p>
                                                            @endif
                                                        </div>
                                                    @endforeach
                                                </div>
                                            @endif
                                        </div>
                                    </td>
                                </tr>
                            @endif
                        @empty
                            <tr>
                                <td colspan="4" class="py-12 text-center text-slate-400 dark:text-slate-500">
                                    <div class="flex flex-col items-center justify-center space-y-2">
                                        <i class="fa-solid fa-users-slash text-3xl text-slate-300 dark:text-slate-700"></i>
                                        <span class="text-sm font-medium">No clients found matching the search criteria.</span>
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <!-- Pagination -->
            @if($clients->hasPages())
                <div class="px-6 py-4 border-t border-slate-100 dark:border-slate-800">
                    {{ $clients->links() }}
                </div>
            @endif
        </div>

    @else
        <!-- GRID VIEW (CARDS) -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-5">
            @forelse($clients as $client)
                @php
                    $portalUrl = url('/portal/' . $client->hash);
                    $btnId = 'copy-btn-grid-' . $client->id;
                    $initials = strtoupper(substr($client->name, 0, 2));
                @endphp
                <div class="bg-white dark:bg-slate-900 border border-slate-100 dark:border-slate-800/80 rounded-2xl p-5 shadow-sm hover:shadow-md transition-all duration-200 space-y-4 flex flex-col justify-between">
                    <div class="space-y-3">
                        <!-- Top Header -->
                        <div class="flex items-center justify-between">
                            <div class="flex items-center space-x-3">
                                <div class="w-10 h-10 rounded-xl bg-gradient-to-tr from-sky-500 to-indigo-600 text-white font-bold text-sm flex items-center justify-center shadow-sm">
                                    {{ $initials }}
                                </div>
                                <div>
                                    <h3 class="font-bold text-base text-slate-800 dark:text-white line-clamp-1">{{ $client->name }}</h3>
                                    <span class="text-xs text-slate-400">ID: #{{ $client->id }}</span>
                                </div>
                            </div>

                            @if(auth()->user()->hasAnyRole(['admin', 'manager']))
                                <div class="flex items-center space-x-1">
                                    <button type="button" 
                                            wire:click="openEditModal({{ $client->id }})" 
                                            class="p-1.5 rounded-lg text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-800 hover:text-sky-600 dark:hover:text-sky-400 transition-colors" 
                                            title="Edit">
                                        <i class="fa-solid fa-pen text-xs"></i>
                                    </button>
                                    <button type="button" 
                                            wire:click="deleteClient({{ $client->id }})" 
                                            wire:confirm="Are you sure you want to delete this client?"
                                            class="p-1.5 rounded-lg text-slate-400 hover:bg-rose-50 dark:hover:bg-rose-950/20 hover:text-rose-600 dark:hover:text-rose-400 transition-colors" 
                                            title="Delete">
                                        <i class="fa-solid fa-trash text-xs"></i>
                                    </button>
                                </div>
                            @endif
                        </div>

                        <!-- Stats Badge -->
                        <div class="flex items-center space-x-2">
                            <span class="inline-flex items-center px-2.5 py-1 rounded-lg text-xs font-semibold bg-sky-50 text-sky-700 dark:bg-sky-950/40 dark:text-sky-300 border border-sky-200/50 dark:border-sky-800/50">
                                <i class="fa-solid fa-building mr-1.5 text-sky-500"></i>
                                {{ $client->companies_count }} Companies
                            </span>
                        </div>

                        <!-- Companies Preview -->
                        <div class="bg-slate-50 dark:bg-slate-950 rounded-xl p-3 border border-slate-100 dark:border-slate-800/80 space-y-1.5">
                            <span class="text-[10px] font-bold uppercase tracking-wider text-slate-400">Linked Companies</span>
                            @if($client->companies->isEmpty())
                                <p class="text-xs text-slate-400 italic">No companies linked.</p>
                            @else
                                <div class="space-y-1">
                                    @foreach($client->companies->take(3) as $comp)
                                        <div class="flex items-center justify-between text-xs">
                                            <a href="{{ route('projects.show', $comp->id) }}" class="font-semibold text-slate-700 dark:text-slate-300 hover:text-sky-600 dark:hover:text-sky-400 truncate max-w-[180px]">
                                                • {{ $comp->name }}
                                            </a>
                                            <span class="text-[10px] text-slate-400">#{{ $comp->id }}</span>
                                        </div>
                                    @endforeach
                                    @if($client->companies->count() > 3)
                                        <p class="text-[11px] text-sky-600 dark:text-sky-400 font-medium pt-1">+ {{ $client->companies->count() - 3 }} more companies</p>
                                    @endif
                                </div>
                            @endif
                        </div>
                    </div>

                    <!-- Card Footer (Jira Link Copy) -->
                    <div class="pt-3 border-t border-slate-100 dark:border-slate-800/80 flex items-center justify-between">
                        <span class="text-xs font-semibold text-slate-400">Jira Portal</span>
                        <button type="button" 
                                id="{{ $btnId }}"
                                onclick="window.copyPortalLink('{{ $portalUrl }}', '{{ $btnId }}')" 
                                class="inline-flex items-center px-3 py-1.5 text-xs font-semibold text-indigo-700 bg-indigo-50 hover:bg-indigo-100 dark:bg-indigo-950/40 dark:text-indigo-400 border border-indigo-200/40 dark:border-indigo-900/40 rounded-xl transition-all duration-150 cursor-pointer">
                            <i class="fa-solid fa-copy mr-1"></i> Copy Link
                        </button>
                    </div>
                </div>
            @empty
                <div class="col-span-full py-12 text-center text-slate-400 dark:text-slate-500 bg-white dark:bg-slate-900 rounded-2xl border border-slate-100 dark:border-slate-800">
                    <i class="fa-solid fa-users-slash text-3xl text-slate-300 dark:text-slate-700 mb-2"></i>
                    <p class="text-sm font-medium">No clients found matching criteria.</p>
                </div>
            @endforelse
        </div>

        <!-- Pagination for Grid -->
        @if($clients->hasPages())
            <div class="bg-white dark:bg-slate-900 border border-slate-100 dark:border-slate-800/80 rounded-2xl p-4 shadow-sm">
                {{ $clients->links() }}
            </div>
        @endif
    @endif

    <!-- Client Modal (Glassmorphic) -->
    @if($showModal)
        <div class="fixed inset-0 z-50 overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
            <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
                <!-- Overlay background -->
                <div class="fixed inset-0 bg-slate-950/60 backdrop-blur-sm transition-opacity" aria-hidden="true" wire:click="$set('showModal', false)"></div>

                <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>

                <!-- Modal Panel -->
                <div class="inline-block align-bottom bg-white dark:bg-slate-900 rounded-2xl text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-md sm:w-full border border-slate-100 dark:border-slate-800/80">
                    <div class="px-6 py-5 border-b border-slate-100 dark:border-slate-800/80 flex items-center justify-between bg-slate-50/50 dark:bg-slate-950/20">
                        <h3 class="text-base font-bold text-slate-800 dark:text-white font-outfit" id="modal-title">
                            {{ $clientId ? 'Edit Client' : 'Add Client' }}
                        </h3>
                        <button type="button" wire:click="$set('showModal', false)" class="text-slate-400 hover:text-slate-600 dark:hover:text-slate-200 transition-colors focus:outline-none cursor-pointer">
                            <i class="fa-solid fa-xmark text-lg"></i>
                        </button>
                    </div>

                    <form wire:submit.prevent="saveClient">
                        <div class="px-6 py-6 space-y-4">
                            <!-- Name -->
                            <div>
                                <label class="block text-[10px] font-bold uppercase tracking-wider text-slate-400 dark:text-slate-500 mb-1.5">Client Name <span class="text-rose-500">*</span></label>
                                <input type="text" wire:model="name" placeholder="e.g., APS, Chilly, Marvli" class="w-full px-3.5 py-2.5 bg-slate-50 dark:bg-slate-950 border border-slate-200 dark:border-slate-800 rounded-xl text-sm text-slate-800 dark:text-slate-100 focus:outline-none focus:ring-2 focus:ring-sky-500/20 focus:border-sky-500 transition-all duration-150">
                                @error('name') <span class="text-[10px] text-rose-500 mt-1 block">{{ $message }}</span> @enderror
                            </div>
                        </div>

                        <!-- Footer -->
                        <div class="px-6 py-4 border-t border-slate-100 dark:border-slate-800/80 bg-slate-50/50 dark:bg-slate-950/20 flex items-center justify-end space-x-2">
                            <button type="button" wire:click="$set('showModal', false)" class="px-4 py-2 text-xs font-semibold text-slate-700 dark:text-slate-200 bg-white dark:bg-slate-900 hover:bg-slate-100 dark:hover:bg-slate-800 border border-slate-200 dark:border-slate-800 rounded-xl transition-all duration-150 cursor-pointer">
                                Cancel
                            </button>
                            <button type="submit" class="px-4 py-2 text-xs font-semibold text-white bg-sky-600 hover:bg-sky-500 dark:bg-sky-500 dark:hover:bg-sky-400 rounded-xl transition-all duration-150 cursor-pointer shadow-sm">
                                Save
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    @endif

    <script>
        if (typeof window.copyPortalLink === 'undefined') {
            window.copyPortalLink = function(url, buttonId) {
                navigator.clipboard.writeText(url).then(() => {
                    const btn = document.getElementById(buttonId);
                    if (!btn) return;
                    const originalHTML = btn.innerHTML;
                    btn.innerHTML = '<i class="fa-solid fa-check mr-1"></i> Copied!';
                    btn.classList.remove('bg-indigo-50', 'text-indigo-700', 'dark:bg-indigo-950/40', 'dark:text-indigo-400');
                    btn.classList.add('bg-emerald-100', 'text-emerald-700', 'dark:bg-emerald-950/40', 'dark:text-emerald-400');
                    setTimeout(() => {
                        btn.innerHTML = originalHTML;
                        btn.classList.remove('bg-emerald-100', 'text-emerald-700', 'dark:bg-emerald-950/40', 'dark:text-emerald-400');
                        btn.classList.add('bg-indigo-50', 'text-indigo-700', 'dark:bg-indigo-950/40', 'dark:text-indigo-400');
                    }, 2000);
                }).catch(err => {
                    console.error('Failed to copy link: ', err);
                });
            };
        }
    </script>
</div>
