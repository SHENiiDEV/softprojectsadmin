<div class="space-y-6">
    <x-slot name="header">
        Companies (Projects)
    </x-slot>

    <!-- Top Action Bar -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <h1 class="font-outfit font-extrabold text-2xl text-slate-800 dark:text-white tracking-tight">Companies Registry</h1>
            <p class="text-sm text-slate-500 dark:text-slate-400 mt-1">Manage all projects, their onboarding, and credentials.</p>
        </div>
        <div>
            <a href="{{ route('projects.create') }}" class="inline-flex items-center justify-center px-4 py-2.5 text-sm font-bold text-sky-700 bg-sky-100 hover:bg-sky-200 dark:bg-sky-950/40 dark:text-sky-400 border border-sky-200/40 dark:border-sky-900/40 rounded-xl shadow-sm hover:-translate-y-0.5 transition-all duration-200" wire:navigate>
                <i class="fa-solid fa-plus mr-2 text-xs"></i> Add Company
            </a>
        </div>
    </div>

    <!-- Alert Message -->
    @if (session()->has('message'))
        <div x-data="{ show: true }" x-show="show" x-init="setTimeout(() => show = false, 4000)" x-transition:leave="transition ease-in duration-300 transform" x-transition:leave-start="opacity-100 scale-100" x-transition:leave-end="opacity-0 scale-95" class="p-4 rounded-xl bg-emerald-50 dark:bg-emerald-950/20 border border-emerald-100 dark:border-emerald-800/40 text-emerald-800 dark:text-emerald-400 flex items-center justify-between shadow-sm">
            <div class="flex items-center space-x-3">
                <i class="fa-solid fa-circle-check text-emerald-600 dark:text-emerald-400 text-lg"></i>
                <span class="text-sm font-medium">{{ session('message') }}</span>
            </div>
            <button @click="show = false" class="text-emerald-500 hover:text-emerald-700 dark:hover:text-emerald-300">
                <i class="fa-solid fa-xmark"></i>
            </button>
        </div>
    @endif

    <!-- Stats Cards Grid -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-5">
        <!-- Total -->
        <div class="bg-white dark:bg-slate-900 border border-slate-100 dark:border-slate-800/80 p-5 rounded-2xl shadow-sm flex items-center justify-between hover:shadow-md transition-shadow duration-150">
            <div>
                <span class="text-xs font-semibold text-slate-400 uppercase tracking-wider block">Total Registry</span>
                <span class="font-outfit font-extrabold text-2xl text-slate-850 dark:text-white mt-1.5 block">{{ $stats['total'] }}</span>
            </div>
            <div class="p-3 rounded-xl bg-indigo-50 dark:bg-indigo-950/30 text-indigo-600 dark:text-indigo-400">
                <i class="fa-solid fa-city text-lg block"></i>
            </div>
        </div>
        <!-- Active -->
        <div class="bg-white dark:bg-slate-900 border border-slate-100 dark:border-slate-800/80 p-5 rounded-2xl shadow-sm flex items-center justify-between hover:shadow-md transition-shadow duration-150">
            <div>
                <span class="text-xs font-semibold text-slate-400 uppercase tracking-wider block">Active Companies</span>
                <span class="font-outfit font-extrabold text-2xl text-emerald-600 dark:text-emerald-400 mt-1.5 block">{{ $stats['active'] }}</span>
            </div>
            <div class="p-3 rounded-xl bg-emerald-50 dark:bg-emerald-950/30 text-emerald-600 dark:text-emerald-400">
                <i class="fa-solid fa-circle-check text-lg block"></i>
            </div>
        </div>
        <!-- Onboarding -->
        <div class="bg-white dark:bg-slate-900 border border-slate-100 dark:border-slate-800/80 p-5 rounded-2xl shadow-sm flex items-center justify-between hover:shadow-md transition-shadow duration-150">
            <div>
                <span class="text-xs font-semibold text-slate-400 uppercase tracking-wider block">Onboarding</span>
                <span class="font-outfit font-extrabold text-2xl text-amber-600 dark:text-amber-400 mt-1.5 block">{{ $stats['onboarding'] }}</span>
            </div>
            <div class="p-3 rounded-xl bg-amber-50 dark:bg-amber-950/30 text-amber-600 dark:text-amber-400">
                <i class="fa-solid fa-spinner text-lg block"></i>
            </div>
        </div>
        <!-- Suspended -->
        <div class="bg-white dark:bg-slate-900 border border-slate-100 dark:border-slate-800/80 p-5 rounded-2xl shadow-sm flex items-center justify-between hover:shadow-md transition-shadow duration-150">
            <div>
                <span class="text-xs font-semibold text-slate-400 uppercase tracking-wider block">Suspended</span>
                <span class="font-outfit font-extrabold text-2xl text-rose-600 dark:text-rose-400 mt-1.5 block">{{ $stats['suspended'] }}</span>
            </div>
            <div class="p-3 rounded-xl bg-rose-50 dark:bg-rose-950/30 text-rose-600 dark:text-rose-400">
                <i class="fa-solid fa-circle-xmark text-lg block"></i>
            </div>
        </div>
    </div>

    <!-- Controls Panel (Glassmorphism Styled) -->
    <div class="bg-white dark:bg-slate-900 border border-slate-100 dark:border-slate-800/80 rounded-2xl p-5 shadow-sm space-y-4">
        <div class="flex flex-col lg:flex-row gap-4 items-stretch lg:items-center justify-between">
            <!-- Search & Basic filters -->
            <div class="flex-1 grid grid-cols-1 md:grid-cols-12 gap-3">
                <!-- Search Input -->
                <div class="relative md:col-span-4">
                    <span class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-slate-400">
                        <i class="fa-solid fa-magnifying-glass text-xs"></i>
                    </span>
                    <input type="text" 
                           wire:model.live.debounce.300ms="search" 
                           placeholder="Search by name, UBO, MCC..." 
                           class="w-full pl-10 pr-4 py-2.5 bg-slate-50 dark:bg-slate-950 border border-slate-200 dark:border-slate-800/80 rounded-xl text-sm text-slate-850 dark:text-slate-100 placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-sky-500/20 focus:border-sky-500 transition-all duration-200">
                </div>

                <!-- Client Filter -->
                <div class="md:col-span-3">
                    <select wire:model.live="filterClient" 
                            class="w-full px-3 py-2.5 bg-slate-50 dark:bg-slate-950 border border-slate-200 dark:border-slate-800/80 rounded-xl text-sm text-slate-800 dark:text-slate-100 focus:outline-none focus:ring-2 focus:ring-sky-500/20 focus:border-sky-500 transition-all duration-200">
                        <option value="">All Clients</option>
                        <option value="none">No Client</option>
                        @foreach($clients as $c)
                            <option value="{{ $c->id }}">{{ $c->name }}</option>
                        @endforeach
                    </select>
                </div>

                <!-- Status Filter -->
                <div class="md:col-span-2">
                    <select wire:model.live="status" 
                            class="w-full px-3 py-2.5 bg-slate-50 dark:bg-slate-950 border border-slate-200 dark:border-slate-800/80 rounded-xl text-sm text-slate-800 dark:text-slate-100 focus:outline-none focus:ring-2 focus:ring-sky-500/20 focus:border-sky-500 transition-all duration-200">
                        <option value="">All Statuses</option>
                        <option value="active">Active</option>
                        <option value="onboarding">Onboarding</option>
                        <option value="suspended">Suspended</option>
                    </select>
                </div>

                <!-- Integration Status Filter -->
                <div class="md:col-span-3">
                    <select wire:model.live="integrationStatus" 
                            class="w-full px-3 py-2.5 bg-slate-50 dark:bg-slate-950 border border-slate-200 dark:border-slate-800/80 rounded-xl text-sm text-slate-800 dark:text-slate-100 focus:outline-none focus:ring-2 focus:ring-sky-500/20 focus:border-sky-500 transition-all duration-200">
                        <option value="">All Integration Statuses</option>
                        <option value="completed">Completed</option>
                        <option value="in_progress">In Progress</option>
                        <option value="pending">Pending</option>
                    </select>
                </div>
            </div>

            <!-- Toggles (Archive + Layout View) -->
            <div class="flex items-center space-x-3 self-end lg:self-center">
                <!-- Clear Filters Button -->
                @if($search || $status || $integrationStatus || $filterClient)
                    <button type="button" wire:click="$set('search', ''); $set('status', ''); $set('integrationStatus', ''); $set('filterClient', '')" class="px-4 py-2.5 text-xs font-semibold text-rose-600 hover:text-rose-700 bg-rose-50 hover:bg-rose-100 dark:bg-rose-950/20 dark:text-rose-400 border border-rose-200/30 dark:border-rose-900/30 rounded-xl transition-all">
                        <i class="fa-solid fa-filter-circle-xmark mr-1.5"></i> Clear
                    </button>
                @endif

                <!-- Sleek sliding active/archived selector -->
                <div class="flex items-center bg-slate-100 dark:bg-slate-950 p-1 rounded-xl border border-slate-200/40 dark:border-slate-800/50">
                    <button type="button" wire:click="$set('showArchived', '0')" class="px-3 py-1.5 rounded-lg text-xs font-bold transition-all duration-150 focus:outline-none {{ $showArchived === '0' ? 'bg-white dark:bg-slate-800 text-sky-600 dark:text-sky-400 shadow-sm' : 'text-slate-400 hover:text-slate-700 dark:hover:text-slate-300' }}">
                        Active
                    </button>
                    <button type="button" wire:click="$set('showArchived', '1')" class="px-3 py-1.5 rounded-lg text-xs font-bold transition-all duration-150 focus:outline-none {{ $showArchived === '1' ? 'bg-white dark:bg-slate-800 text-amber-600 dark:text-amber-400 shadow-sm' : 'text-slate-400 hover:text-slate-700 dark:hover:text-slate-300' }}">
                        Archived
                    </button>
                </div>

                <!-- Grid/Table switcher -->
                <div class="flex items-center bg-slate-100 dark:bg-slate-950 p-1 rounded-xl border border-slate-200/40 dark:border-slate-800/50">
                    <button type="button" wire:click="$set('layout', 'table')" class="p-1.5 rounded-lg transition-all duration-150 focus:outline-none {{ $layout === 'table' ? 'bg-white dark:bg-slate-800 text-sky-600 dark:text-sky-400 shadow-sm' : 'text-slate-400 hover:text-slate-700 dark:hover:text-slate-300' }}" title="Table View">
                        <i class="fa-solid fa-table-list text-xs block px-0.5"></i>
                    </button>
                    <button type="button" wire:click="$set('layout', 'grid')" class="p-1.5 rounded-lg transition-all duration-150 focus:outline-none {{ $layout === 'grid' ? 'bg-white dark:bg-slate-800 text-sky-600 dark:text-sky-400 shadow-sm' : 'text-slate-400 hover:text-slate-700 dark:hover:text-slate-300' }}" title="Grid Cards View">
                        <i class="fa-solid fa-grip-both text-xs block px-0.5"></i>
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Main List Container -->
    <div>
        @if($layout === 'table')
            <!-- Upgraded Table View -->
            <div class="bg-white dark:bg-slate-900 border border-slate-100 dark:border-slate-800/80 rounded-2xl shadow-sm overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="w-full text-left border-collapse">
                        <thead>
                            <tr class="text-slate-400 dark:text-slate-500 text-xs font-bold uppercase border-b border-slate-100 dark:border-slate-800/60 bg-slate-50/50 dark:bg-slate-950/20">
                                <th class="py-4 px-6">Company</th>
                                <th class="py-4 px-6">Client</th>
                                <th class="py-4 px-6">UBO</th>
                                <th class="py-4 px-6">MCC Code</th>
                                <th class="py-4 px-6">Integration</th>
                                <th class="py-4 px-6">Director</th>
                                <th class="py-4 px-6 text-right">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 dark:divide-slate-800/40 text-sm">
                            @forelse($projects as $project)
                                @php
                                    $gradient = match($project->status) {
                                        'active' => 'from-emerald-500 to-teal-600 shadow-emerald-500/10',
                                        'onboarding' => 'from-amber-400 to-orange-500 shadow-amber-500/10',
                                        default => 'from-rose-500 to-red-600 shadow-rose-500/10',
                                    };
                                    $dotColor = match($project->status) {
                                        'active' => 'bg-emerald-500',
                                        'onboarding' => 'bg-amber-500',
                                        default => 'bg-rose-500',
                                    };
                                    $pingColor = match($project->status) {
                                        'active' => 'bg-emerald-400',
                                        'onboarding' => 'bg-amber-400',
                                        default => 'bg-rose-400',
                                    };
                                @endphp
                                <tr class="hover:bg-slate-50/50 dark:hover:bg-slate-800/10 transition-colors duration-150">
                                    <!-- Company Name -->
                                    <td class="py-4 px-6">
                                        <div class="flex items-center space-x-3">
                                            <div class="relative h-9 w-9 rounded-xl bg-gradient-to-tr {{ $gradient }} text-white font-extrabold flex items-center justify-center text-xs uppercase shadow-sm flex-shrink-0">
                                                {{ substr($project->name, 0, 2) }}
                                                <span class="absolute -top-1 -right-1 flex h-2.5 w-2.5">
                                                    <span class="animate-ping absolute inline-flex h-full w-full rounded-full opacity-75 {{ $pingColor }}"></span>
                                                    <span class="relative inline-flex rounded-full h-2.5 w-2.5 {{ $dotColor }} ring-1 ring-white dark:ring-slate-900"></span>
                                                </span>
                                            </div>
                                            <div>
                                                <a href="{{ route('projects.show', $project->id) }}" class="font-semibold text-slate-800 dark:text-slate-200 hover:text-sky-600 dark:hover:text-sky-400 transition-colors duration-150" wire:navigate>
                                                    {{ $project->name }}
                                                </a>
                                                <span class="text-[10px] text-slate-400 block mt-0.5">Manager: {{ $project->manager?->name ?? 'System' }}</span>
                                            </div>
                                        </div>
                                    </td>

                                    <!-- Client Name -->
                                    <td class="py-4 px-6 whitespace-nowrap">
                                        @if($project->client)
                                            <div class="relative inline-block text-left" x-data="{ open: false }" @mouseenter="open = true" @mouseleave="open = false">
                                                <span class="cursor-pointer inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold bg-indigo-50 text-indigo-750 dark:bg-indigo-950/30 dark:text-indigo-400 transition-all hover:bg-indigo-100 hover:text-indigo-850 whitespace-nowrap">
                                                    {{ $project->client->name }}
                                                    @if($project->websites->isNotEmpty())
                                                        <i class="fa-solid fa-globe ml-1.5 text-[10px] opacity-70"></i>
                                                    @endif
                                                </span>

                                                <!-- Hover Popover for Websites -->
                                                @if($project->websites->isNotEmpty())
                                                    <div x-show="open" 
                                                         x-transition:enter="transition ease-out duration-100"
                                                         x-transition:enter-start="transform opacity-0 scale-95"
                                                         x-transition:enter-end="transform opacity-100 scale-100"
                                                         x-transition:leave="transition ease-in duration-75"
                                                         x-transition:leave-start="transform opacity-100 scale-100"
                                                         x-transition:leave-end="transform opacity-0 scale-95"
                                                         class="absolute z-50 left-0 mt-2 w-64 bg-white dark:bg-slate-900 border border-slate-200/80 dark:border-slate-800 rounded-xl shadow-xl p-3 space-y-2 pointer-events-auto"
                                                         style="display: none;">
                                                        <div class="text-[10px] font-bold text-slate-400 dark:text-slate-500 uppercase tracking-wider mb-1">Websites</div>
                                                        <div class="space-y-1 max-h-40 overflow-y-auto">
                                                            @foreach($project->websites as $web)
                                                                <a href="{{ $web->url }}" target="_blank" class="flex items-center justify-between p-1.5 rounded-lg hover:bg-slate-50 dark:hover:bg-slate-850/80 text-xs font-mono font-medium text-slate-700 dark:text-slate-355 hover:text-sky-600 dark:hover:text-sky-400 transition-colors">
                                                                    <span class="truncate pr-2">{{ parse_url($web->url, PHP_URL_HOST) ?: $web->url }}</span>
                                                                    <i class="fa-solid fa-arrow-up-right-from-square text-[9px] opacity-60"></i>
                                                                </a>
                                                            @endforeach
                                                        </div>
                                                    </div>
                                                @endif
                                            </div>
                                        @else
                                            <span class="text-slate-400 dark:text-slate-600 font-medium text-xs">
                                                No Client
                                            </span>
                                        @endif
                                    </td>

                                    <!-- UBO -->
                                    <td class="py-4 px-6 text-slate-650 dark:text-slate-400 truncate max-w-[150px]">
                                        {{ $project->ubo ?: '-' }}
                                    </td>

                                    <!-- MCC Code -->
                                    <td class="py-4 px-6">
                                        @if($project->mcc)
                                            <span class="font-mono text-xs bg-slate-50 dark:bg-slate-950 px-2 py-0.5 rounded border border-slate-100 dark:border-slate-800 text-slate-600 dark:text-slate-400">
                                                {{ $project->mcc }}
                                            </span>
                                        @else
                                            <span class="text-slate-400">-</span>
                                        @endif
                                    </td>

                                    <!-- Integration Status -->
                                    <td class="py-4 px-6">
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-lg text-xs font-semibold uppercase tracking-wider
                                            @if($project->integration_status === 'completed') bg-emerald-50 text-emerald-700 dark:bg-emerald-950/30 dark:text-emerald-400
                                            @elseif($project->integration_status === 'in_progress') bg-blue-50 text-blue-700 dark:bg-blue-950/30 dark:text-blue-400
                                            @else bg-slate-100 text-slate-655 dark:bg-slate-800 dark:text-slate-400 @endif">
                                            {{ $project->integration_status ?: 'Pending' }}
                                        </span>
                                    </td>

                                    <!-- Director -->
                                    <td class="py-4 px-6 text-slate-655 dark:text-slate-355">
                                        @if($project->director)
                                            <div class="flex items-center space-x-2">
                                                <div class="h-6 w-6 rounded-full bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-400 flex items-center justify-center text-[9px] font-bold uppercase border border-slate-200/50 dark:border-slate-700">
                                                    {{ substr($project->director->name, 0, 2) }}
                                                </div>
                                                <span class="font-medium">{{ $project->director->name }}</span>
                                            </div>
                                        @else
                                            <span class="text-slate-400 dark:text-slate-600 font-normal">-</span>
                                        @endif
                                    </td>

                                    <!-- Actions -->
                                    <td class="py-4 px-6 text-right whitespace-nowrap">
                                        <div class="inline-flex items-center space-x-1">
                                            <!-- View Details -->
                                            <a href="{{ route('projects.show', $project->id) }}" 
                                               class="p-2 rounded-xl text-slate-400 hover:bg-slate-50 dark:hover:bg-slate-800/80 hover:text-sky-600 dark:hover:text-sky-400 transition-all" 
                                               title="View"
                                               wire:navigate>
                                                <i class="fa-solid fa-eye text-xs"></i>
                                            </a>

                                            <!-- Edit -->
                                            <a href="{{ route('projects.edit', $project->id) }}" 
                                               class="p-2 rounded-xl text-slate-400 hover:bg-slate-50 dark:hover:bg-slate-800/80 hover:text-amber-400 dark:hover:text-amber-400 transition-all" 
                                               title="Edit"
                                               wire:navigate>
                                                <i class="fa-solid fa-pencil text-xs"></i>
                                            </a>

                                            <!-- Add Note -->
                                            @if(config('features.notes_tab', true))
                                                <button type="button"
                                                        wire:click="openNoteModal({{ $project->id }})"
                                                        class="p-2 rounded-xl text-slate-400 hover:bg-amber-50 dark:hover:bg-amber-950/20 hover:text-amber-600 dark:hover:text-amber-400 transition-all"
                                                        title="Add Note">
                                                    <i class="fa-solid fa-note-sticky text-xs"></i>
                                                </button>
                                            @endif

                                            <!-- Archive / Restore -->
                                            @if($project->archived_at)
                                                <button type="button"
                                                        wire:click="unarchiveProject({{ $project->id }})"
                                                        wire:confirm="Restore this company to active view?"
                                                        class="p-2 rounded-xl text-slate-400 hover:bg-emerald-50 dark:hover:bg-emerald-950/20 hover:text-emerald-600 dark:hover:text-emerald-400 transition-all"
                                                        title="Restore">
                                                    <i class="fa-solid fa-box-open text-xs"></i>
                                                </button>
                                            @else
                                                <button type="button"
                                                        wire:click="archiveProject({{ $project->id }})"
                                                        wire:confirm="Archive this company? It will be hidden from the main view."
                                                        class="p-2 rounded-xl text-slate-400 hover:bg-amber-50 dark:hover:bg-amber-950/20 hover:text-amber-400 dark:hover:text-amber-400 transition-all"
                                                        title="Archive">
                                                    <i class="fa-solid fa-box-archive text-xs"></i>
                                                </button>
                                            @endif

                                            <!-- Delete -->
                                            <button type="button" 
                                                    wire:click="deleteProject({{ $project->id }})" 
                                                    wire:confirm="Are you sure you want to delete this company and all associated director data?"
                                                    class="p-2 rounded-xl text-slate-400 hover:bg-rose-50 dark:hover:bg-rose-950/20 hover:text-rose-600 dark:hover:text-rose-400 transition-all" 
                                                    title="Delete">
                                                <i class="fa-solid fa-trash-can text-xs"></i>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="7" class="py-16 text-center text-slate-400 dark:text-slate-500 bg-slate-50/50 dark:bg-slate-950/20">
                                        <div class="flex flex-col items-center justify-center space-y-3">
                                            <i class="fa-solid fa-folder-open text-3xl text-slate-300 dark:text-slate-700"></i>
                                            <span class="text-sm font-medium">No companies found matching the search criteria.</span>
                                        </div>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        @else
            <!-- Premium Grid view -->
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-6">
                @forelse($projects as $project)
                    @php
                        $gradient = match($project->status) {
                            'active' => 'from-emerald-500 to-teal-600 shadow-emerald-500/10',
                            'onboarding' => 'from-amber-400 to-orange-500 shadow-amber-500/10',
                            default => 'from-rose-500 to-red-600 shadow-rose-500/10',
                        };
                        $dotColor = match($project->status) {
                            'active' => 'bg-emerald-500',
                            'onboarding' => 'bg-amber-500',
                            default => 'bg-rose-500',
                        };
                        $pingColor = match($project->status) {
                            'active' => 'bg-emerald-400',
                            'onboarding' => 'bg-amber-400',
                            default => 'bg-rose-400',
                        };
                    @endphp
                    <div class="relative bg-white dark:bg-slate-900 border border-slate-100 dark:border-slate-800/80 rounded-3xl p-5 shadow-sm hover:shadow-xl hover:-translate-y-1 transition-all duration-300 flex flex-col justify-between group">
                        
                        <!-- Card Header -->
                        <div>
                            <div class="flex items-start justify-between gap-3">
                                <div class="flex items-center space-x-3 overflow-hidden">
                                    <!-- Initials logo -->
                                    <div class="relative h-11 w-11 rounded-2xl bg-gradient-to-tr {{ $gradient }} text-white font-black flex items-center justify-center text-xs uppercase shadow-md flex-shrink-0">
                                        {{ substr($project->name, 0, 2) }}
                                        <span class="absolute -top-1 -right-1 flex h-3 w-3">
                                            <span class="animate-ping absolute inline-flex h-full w-full rounded-full opacity-75 {{ $pingColor }}"></span>
                                            <span class="relative inline-flex rounded-full h-3 w-3 {{ $dotColor }} ring-2 ring-white dark:ring-slate-900"></span>
                                        </span>
                                    </div>
                                    <div class="overflow-hidden">
                                        <a href="{{ route('projects.show', $project->id) }}" class="font-outfit font-extrabold text-slate-800 dark:text-white hover:text-sky-600 dark:hover:text-sky-400 transition-colors leading-tight line-clamp-1 block" title="{{ $project->name }}" wire:navigate>
                                            {{ $project->name }}
                                        </a>
                                        @if($project->client)
                                            <span class="inline-block text-[10px] font-bold text-indigo-650 dark:text-indigo-405 bg-indigo-50 dark:bg-indigo-950/20 border border-indigo-100/50 dark:border-indigo-900/30 px-2 py-0.5 rounded-md mt-1 truncate max-w-[150px]">
                                                {{ $project->client->name }}
                                            </span>
                                        @else
                                            <span class="inline-block text-[10px] font-medium text-slate-400 dark:text-slate-500 mt-1">
                                                No Client
                                            </span>
                                        @endif
                                    </div>
                                </div>
                            </div>

                            <!-- Badges Row -->
                            <div class="flex items-center space-x-2 mt-4">
                                <span class="inline-flex items-center px-2 py-0.5 rounded-lg text-[10px] font-bold uppercase tracking-wider
                                    @if($project->status === 'active') bg-emerald-50 text-emerald-700 dark:bg-emerald-950/30 dark:text-emerald-400 border border-emerald-200/20 dark:border-emerald-900/30
                                    @elseif($project->status === 'onboarding') bg-amber-50 text-amber-700 dark:bg-amber-950/30 dark:text-amber-400 border border-amber-200/20 dark:border-amber-900/30
                                    @else bg-rose-50 text-rose-700 dark:bg-rose-950/30 dark:text-rose-400 border border-rose-200/20 dark:border-rose-900/30 @endif">
                                    <span class="w-1.5 h-1.5 rounded-full mr-1.5 @if($project->status === 'active') bg-emerald-500 @elseif($project->status === 'onboarding') bg-amber-500 @else bg-rose-500 @endif"></span>
                                    {{ $project->status }}
                                </span>

                                <span class="inline-flex items-center px-2 py-0.5 rounded-lg text-[10px] font-bold uppercase tracking-wider
                                    @if($project->integration_status === 'completed') bg-emerald-50 text-emerald-700 dark:bg-emerald-950/30 dark:text-emerald-400
                                    @elseif($project->integration_status === 'in_progress') bg-blue-50 text-blue-700 dark:bg-blue-950/30 dark:text-blue-400
                                    @else bg-slate-100 text-slate-600 dark:bg-slate-800 dark:text-slate-400 @endif">
                                    {{ $project->integration_status ?: 'Pending' }}
                                </span>
                            </div>

                            <!-- Card Middle Meta -->
                            <div class="my-5 py-4 border-t border-b border-slate-100 dark:border-slate-800/60 space-y-2.5 text-xs">
                                <div class="flex items-center justify-between text-slate-500 dark:text-slate-400">
                                    <span class="font-medium">Director:</span>
                                    <div class="flex items-center space-x-1.5 font-semibold text-slate-755 dark:text-slate-350">
                                        @if($project->director)
                                            <span>{{ $project->director->name }}</span>
                                            @if($project->director->managed_by)
                                                <div class="h-4.5 w-4.5 rounded-full bg-slate-100 dark:bg-slate-800 text-slate-500 dark:text-slate-400 flex items-center justify-center text-[7px] font-bold uppercase border border-slate-200/50 dark:border-slate-700" title="Director Managed By">
                                                    {{ substr($project->director->manager?->name ?? 'M', 0, 2) }}
                                                </div>
                                            @endif
                                        @else
                                            <span class="text-slate-400 font-normal">-</span>
                                        @endif
                                    </div>
                                </div>
                                <div class="flex items-center justify-between text-slate-500 dark:text-slate-400">
                                    <span class="font-medium">UBO:</span>
                                    <span class="font-semibold text-slate-700 dark:text-slate-355 truncate max-w-[150px]" title="{{ $project->ubo }}">{{ $project->ubo ?: '-' }}</span>
                                </div>
                                <div class="flex items-center justify-between text-slate-500 dark:text-slate-400">
                                    <span class="font-medium">MCC Code:</span>
                                    <span class="font-mono bg-slate-50 dark:bg-slate-950 px-2 py-0.5 rounded border border-slate-150/40 dark:border-slate-800 text-slate-600 dark:text-slate-400 font-semibold">{{ $project->mcc ?: '-' }}</span>
                                </div>
                                <div class="flex items-center justify-between text-slate-500 dark:text-slate-400">
                                    <span class="font-medium">Manager:</span>
                                    <div class="flex items-center space-x-1.5 font-semibold text-slate-700 dark:text-slate-300">
                                        <div class="h-4.5 w-4.5 rounded-full bg-gradient-to-tr from-sky-400 to-indigo-500 text-white font-bold flex items-center justify-center text-[7px] uppercase shadow-sm">
                                            {{ substr($project->manager?->name ?? 'S', 0, 2) }}
                                        </div>
                                        <span>{{ $project->manager?->name ?? 'System' }}</span>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Card Bottom/Websites + Actions -->
                        <div>
                            <!-- Websites list -->
                            <div class="flex flex-wrap gap-1.5 mb-4 min-h-[26px]">
                                @forelse($project->websites->take(2) as $web)
                                    <a href="{{ $web->url }}" target="_blank" class="inline-flex items-center space-x-1 px-2.5 py-1 rounded-lg bg-sky-50 dark:bg-sky-950/20 text-[10px] font-semibold text-sky-700 dark:text-sky-400 hover:bg-sky-100 dark:hover:bg-sky-950/40 border border-sky-100/40 dark:border-sky-900/30 transition-all font-mono">
                                        <span class="truncate max-w-[100px]">{{ parse_url($web->url, PHP_URL_HOST) ?: $web->url }}</span>
                                        <i class="fa-solid fa-arrow-up-right-from-square text-[8px] opacity-70"></i>
                                    </a>
                                @empty
                                    <span class="text-[10px] text-slate-400 dark:text-slate-500 italic self-center">No websites registered</span>
                                @endforelse
                                @if($project->websites->count() > 2)
                                    <span class="text-[9px] text-slate-400 dark:text-slate-500 font-semibold self-center">+ {{ $project->websites->count() - 2 }} more</span>
                                @endif
                            </div>

                            <!-- Footer Actions -->
                            <div class="flex items-center justify-between pt-3 border-t border-slate-100 dark:border-slate-800/60">
                                <!-- Details Button -->
                                <a href="{{ route('projects.show', $project->id) }}" class="inline-flex items-center justify-center px-3.5 py-1.5 text-xs font-bold text-slate-700 hover:text-sky-700 bg-slate-50 hover:bg-sky-50 dark:bg-slate-950 dark:text-slate-350 dark:hover:text-sky-400 border border-slate-200/50 dark:border-slate-800 rounded-xl transition-all" wire:navigate>
                                    <i class="fa-solid fa-eye mr-1.5 text-[10px]"></i> View Details
                                </a>

                                <!-- Small hover action icons -->
                                <div class="flex items-center space-x-0.5 opacity-60 group-hover:opacity-100 transition-opacity">
                                    <!-- Edit -->
                                    <a href="{{ route('projects.edit', $project->id) }}" class="p-2 text-slate-400 hover:text-amber-500 hover:bg-amber-50 dark:hover:bg-amber-950/20 rounded-xl transition-all" title="Edit" wire:navigate>
                                        <i class="fa-solid fa-pencil text-xs"></i>
                                    </a>

                                    <!-- Add Note -->
                                    @if(config('features.notes_tab', true))
                                        <button type="button" wire:click="openNoteModal({{ $project->id }})" class="p-2 text-slate-400 hover:text-amber-500 hover:bg-amber-50 dark:hover:bg-amber-950/20 rounded-xl transition-all" title="Add Note">
                                            <i class="fa-solid fa-note-sticky text-xs"></i>
                                        </button>
                                    @endif

                                    <!-- Archive / Restore -->
                                    @if($project->archived_at)
                                        <button type="button" wire:click="unarchiveProject({{ $project->id }})" wire:confirm="Restore this company to active view?" class="p-2 text-slate-400 hover:text-emerald-500 hover:bg-emerald-50 dark:hover:bg-emerald-950/20 rounded-xl transition-all" title="Restore">
                                            <i class="fa-solid fa-box-open text-xs"></i>
                                        </button>
                                    @else
                                        <button type="button" wire:click="archiveProject({{ $project->id }})" wire:confirm="Archive this company? It will be hidden from the main view." class="p-2 text-slate-400 hover:text-amber-500 hover:bg-amber-50 dark:hover:bg-amber-950/20 rounded-xl transition-all" title="Archive">
                                            <i class="fa-solid fa-box-archive text-xs"></i>
                                        </button>
                                    @endif

                                    <!-- Delete -->
                                    <button type="button" wire:click="deleteProject({{ $project->id }})" wire:confirm="Are you sure you want to delete this company and all associated director data?" class="p-2 text-slate-400 hover:text-rose-600 hover:bg-rose-50 dark:hover:bg-rose-950/20 rounded-xl transition-all" title="Delete">
                                        <i class="fa-solid fa-trash-can text-xs"></i>
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                @empty
                    <div class="col-span-full bg-white dark:bg-slate-900 border border-slate-100 dark:border-slate-800/80 rounded-2xl p-16 text-center text-slate-400 dark:text-slate-500">
                        <div class="flex flex-col items-center justify-center space-y-3">
                            <i class="fa-solid fa-folder-open text-4xl text-slate-355 dark:text-slate-700"></i>
                            <span class="text-sm font-medium">No companies found matching the search criteria.</span>
                        </div>
                    </div>
                @endforelse
            </div>
        @endif
    </div>

    <!-- Pagination Links -->
    @if($projects->hasPages())
        <div class="px-6 py-4 border-t border-slate-100 dark:border-slate-800/60 bg-slate-50/50 dark:bg-slate-950/20 rounded-2xl">
            {{ $projects->links() }}
        </div>
    @endif

    <!-- Add Note Modal (Glassmorphic) -->
    @if($showNoteModal)
        <div class="fixed inset-0 z-50 overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
            <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
                <!-- Overlay background -->
                <div class="fixed inset-0 bg-slate-950/60 backdrop-blur-sm transition-opacity" aria-hidden="true" wire:click="$set('showNoteModal', false)"></div>

                <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>

                <!-- Modal Panel -->
                <div class="inline-block align-bottom bg-white dark:bg-slate-900 rounded-2xl text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-md sm:w-full border border-slate-100 dark:border-slate-800/80">
                    <div class="px-6 py-5 border-b border-slate-100 dark:border-slate-800/80 flex items-center justify-between bg-slate-50/50 dark:bg-slate-950/20">
                        <h3 class="text-base font-bold text-slate-850 dark:text-white font-outfit" id="modal-title">
                            Add Note to {{ $noteProjectName }}
                        </h3>
                        <button type="button" wire:click="$set('showNoteModal', false)" class="text-slate-400 hover:text-slate-600 dark:hover:text-slate-200 transition-colors focus:outline-none cursor-pointer">
                            <i class="fa-solid fa-xmark text-lg"></i>
                        </button>
                    </div>

                    <form wire:submit.prevent="saveNote">
                        <div class="px-6 py-6 space-y-4">
                            <!-- Note Content -->
                            <div>
                                <label class="block text-[10px] font-bold uppercase tracking-wider text-slate-400 dark:text-slate-500 mb-1.5">Note Content <span class="text-rose-500">*</span></label>
                                <textarea wire:model="noteContent" rows="4" placeholder="Write a note visible only to the team..." class="w-full px-3.5 py-2.5 bg-slate-50 dark:bg-slate-950 border border-slate-200 dark:border-slate-800 rounded-xl text-sm text-slate-800 dark:text-slate-100 placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-sky-500/20 focus:border-sky-500 transition-all resize-none"></textarea>
                                @error('noteContent') <span class="text-[10px] text-rose-500 mt-1 block">{{ $message }}</span> @enderror
                            </div>
                        </div>

                        <!-- Footer -->
                        <div class="px-6 py-4 border-t border-slate-100 dark:border-slate-800/80 bg-slate-50/50 dark:bg-slate-950/20 flex items-center justify-end space-x-2">
                            <button type="button" wire:click="$set('showNoteModal', false)" class="px-4 py-2 text-xs font-semibold text-slate-700 dark:text-slate-200 bg-white dark:bg-slate-900 hover:bg-slate-100 dark:hover:bg-slate-800 border border-slate-200 dark:border-slate-800 rounded-xl transition-all duration-150 cursor-pointer">
                                Cancel
                            </button>
                            <button type="submit" class="px-4 py-2 bg-sky-600 hover:bg-sky-700 text-white text-xs font-semibold rounded-xl transition-colors shadow-sm cursor-pointer">
                                Add Note
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    @endif
</div>
