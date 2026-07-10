<div class="space-y-6">

    {{-- ═══════════════════════════════════════════════════════════
         HEADER
    ═══════════════════════════════════════════════════════════ --}}
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold text-slate-900 dark:text-white tracking-tight">Deadline Center</h1>
            <p class="text-sm text-slate-500 dark:text-slate-400 mt-0.5">All task and Companies House report deadlines</p>
        </div>

        {{-- Type filter --}}
        <div class="flex items-center gap-2">
            <button wire:click="$set('typeFilter', '')"
                    class="px-3 py-1.5 text-xs font-semibold rounded-lg transition-all duration-200 cursor-pointer
                           {{ $typeFilter === '' ? 'bg-slate-900 text-white dark:bg-white dark:text-slate-900' : 'bg-slate-100 text-slate-600 hover:bg-slate-200 dark:bg-slate-800 dark:text-slate-300 dark:hover:bg-slate-700' }}">
                All
            </button>
            <button wire:click="$set('typeFilter', 'task')"
                    class="px-3 py-1.5 text-xs font-semibold rounded-lg transition-all duration-200 cursor-pointer
                           {{ $typeFilter === 'task' ? 'bg-indigo-600 text-white' : 'bg-slate-100 text-slate-600 hover:bg-slate-200 dark:bg-slate-800 dark:text-slate-300 dark:hover:bg-slate-700' }}">
                Tasks
            </button>
            <button wire:click="$set('typeFilter', 'report')"
                    class="px-3 py-1.5 text-xs font-semibold rounded-lg transition-all duration-200 cursor-pointer
                           {{ $typeFilter === 'report' ? 'bg-amber-500 text-white' : 'bg-slate-100 text-slate-600 hover:bg-slate-200 dark:bg-slate-800 dark:text-slate-300 dark:hover:bg-slate-700' }}">
                CH Reports
            </button>
        </div>
    </div>

    {{-- ═══════════════════════════════════════════════════════════
         STATS BAR
    ═══════════════════════════════════════════════════════════ --}}
    <div class="grid grid-cols-2 sm:grid-cols-4 gap-3">
        <button wire:click="$set('filter', 'overdue')"
                class="group relative overflow-hidden rounded-xl p-4 text-left transition-all duration-200 cursor-pointer
                       {{ $filter === 'overdue' ? 'ring-2 ring-red-500' : '' }}
                       bg-red-50 hover:bg-red-100 dark:bg-red-950/40 dark:hover:bg-red-950/60 border border-red-100 dark:border-red-900/50">
            <div class="flex items-center justify-between mb-2">
                <span class="text-xs font-semibold uppercase tracking-widest text-red-500 dark:text-red-400">Overdue</span>
                <svg class="w-4 h-4 text-red-500 dark:text-red-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                </svg>
            </div>
            <p class="text-3xl font-bold text-red-600 dark:text-red-400 tabular-nums">{{ $stats['overdue'] ?? 0 }}</p>
        </button>

        <button wire:click="$set('filter', 'today')"
                class="group relative overflow-hidden rounded-xl p-4 text-left transition-all duration-200 cursor-pointer
                       {{ $filter === 'today' ? 'ring-2 ring-amber-500' : '' }}
                       bg-amber-50 hover:bg-amber-100 dark:bg-amber-950/40 dark:hover:bg-amber-950/60 border border-amber-100 dark:border-amber-900/50">
            <div class="flex items-center justify-between mb-2">
                <span class="text-xs font-semibold uppercase tracking-widest text-amber-600 dark:text-amber-400">Today</span>
                <svg class="w-4 h-4 text-amber-500 dark:text-amber-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364-6.364l-.707.707M6.343 17.657l-.707.707m0-12.728l.707.707m12.728 12.728l.707-.707M12 8a4 4 0 100 8 4 4 0 000-8z"/>
                </svg>
            </div>
            <p class="text-3xl font-bold text-amber-600 dark:text-amber-400 tabular-nums">{{ $stats['today'] ?? 0 }}</p>
        </button>

        <button wire:click="$set('filter', 'week')"
                class="group relative overflow-hidden rounded-xl p-4 text-left transition-all duration-200 cursor-pointer
                       {{ $filter === 'week' ? 'ring-2 ring-blue-500' : '' }}
                       bg-blue-50 hover:bg-blue-100 dark:bg-blue-950/40 dark:hover:bg-blue-950/60 border border-blue-100 dark:border-blue-900/50">
            <div class="flex items-center justify-between mb-2">
                <span class="text-xs font-semibold uppercase tracking-widest text-blue-600 dark:text-blue-400">This Week</span>
                <svg class="w-4 h-4 text-blue-500 dark:text-blue-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2v-7a2 2 0 00-2-2H5a2 2 0 00-2 2v7a2 2 0 002 2z"/>
                </svg>
            </div>
            <p class="text-3xl font-bold text-blue-600 dark:text-blue-400 tabular-nums">{{ $stats['week'] ?? 0 }}</p>
        </button>

        <button wire:click="$set('filter', 'all')"
                class="group relative overflow-hidden rounded-xl p-4 text-left transition-all duration-200 cursor-pointer
                       {{ $filter === 'all' ? 'ring-2 ring-slate-400' : '' }}
                       bg-slate-50 hover:bg-slate-100 dark:bg-slate-800/60 dark:hover:bg-slate-800 border border-slate-200 dark:border-slate-700">
            <div class="flex items-center justify-between mb-2">
                <span class="text-xs font-semibold uppercase tracking-widest text-slate-500 dark:text-slate-400">Total</span>
                <svg class="w-4 h-4 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M4 6h16M4 10h16M4 14h16M4 18h16"/>
                </svg>
            </div>
            <p class="text-3xl font-bold text-slate-700 dark:text-slate-200 tabular-nums">{{ $stats['total'] ?? 0 }}</p>
        </button>
    </div>

    {{-- ═══════════════════════════════════════════════════════════
         DATE FILTER TABS
    ═══════════════════════════════════════════════════════════ --}}
    <div class="flex items-center gap-1 border-b border-slate-200 dark:border-slate-700 pb-0">
        @foreach([
            ['all',     'All Deadlines'],
            ['today',   'Today'],
            ['week',    'Week'],
            ['month',   'Month'],
            ['overdue', 'Overdue'],
        ] as [$key, $label])
            <button wire:click="$set('filter', '{{ $key }}')"
                    class="px-4 py-2.5 text-sm font-medium border-b-2 transition-all duration-200 cursor-pointer -mb-px
                           {{ $filter === $key
                               ? ($key === 'overdue'
                                   ? 'border-red-500 text-red-600 dark:text-red-400'
                                   : 'border-indigo-600 text-indigo-700 dark:text-indigo-400')
                               : 'border-transparent text-slate-500 dark:text-slate-400 hover:text-slate-700 dark:hover:text-slate-200 hover:border-slate-300 dark:hover:border-slate-600' }}">
                {{ $label }}
                @if($key === 'overdue' && ($stats['overdue'] ?? 0) > 0)
                    <span class="ml-1.5 inline-flex items-center justify-center w-4 h-4 text-[10px] font-bold rounded-full bg-red-500 text-white">
                        {{ min($stats['overdue'], 9) }}{{ $stats['overdue'] > 9 ? '+' : '' }}
                    </span>
                @endif
            </button>
        @endforeach
    </div>

    {{-- ═══════════════════════════════════════════════════════════
         GROUPED DEADLINE SECTIONS
    ═══════════════════════════════════════════════════════════ --}}
    @if($grouped->isEmpty())
        <div class="flex flex-col items-center justify-center py-20 text-center">
            <div class="w-16 h-16 rounded-2xl bg-slate-100 dark:bg-slate-800 flex items-center justify-center mb-4">
                <svg class="w-8 h-8 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
            </div>
            <p class="text-slate-600 dark:text-slate-300 font-semibold text-lg">No deadlines</p>
            <p class="text-slate-400 dark:text-slate-500 text-sm mt-1">For the selected period and filter.</p>
        </div>
    @else
        <div class="space-y-8">
            @foreach($grouped as $section)
                @php
                    $sectionColors = [
                        'overdue'  => ['label' => 'text-red-600 dark:text-red-400',   'dot' => 'bg-red-500',    'line' => 'bg-red-500'],
                        'today'    => ['label' => 'text-amber-600 dark:text-amber-400','dot' => 'bg-amber-500',  'line' => 'bg-amber-500'],
                        'tomorrow' => ['label' => 'text-blue-600 dark:text-blue-400',  'dot' => 'bg-blue-500',   'line' => 'bg-blue-500'],
                        'week'     => ['label' => 'text-indigo-600 dark:text-indigo-400','dot'=> 'bg-indigo-500','line' => 'bg-indigo-500'],
                        'later'    => ['label' => 'text-slate-500 dark:text-slate-400','dot' => 'bg-slate-400',  'line' => 'bg-slate-300 dark:bg-slate-600'],
                    ];
                    $sc = $sectionColors[$section['key']] ?? $sectionColors['later'];
                @endphp

                <div>
                    {{-- Section Header --}}
                    <div class="flex items-center gap-3 mb-3">
                        <span class="w-2.5 h-2.5 rounded-full flex-shrink-0 {{ $sc['dot'] }}"></span>
                        <h2 class="text-sm font-bold uppercase tracking-widest {{ $sc['label'] }}">
                            {{ $section['label'] }}
                        </h2>
                        <span class="text-xs font-semibold text-slate-400 dark:text-slate-500 bg-slate-100 dark:bg-slate-800 px-2 py-0.5 rounded-full">
                            {{ $section['items']->count() }}
                        </span>
                        <div class="flex-1 h-px {{ $sc['line'] }} opacity-30"></div>
                    </div>

                    {{-- Cards Grid --}}
                    <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4">
                        @foreach($section['items'] as $d)
                            @php
                                $now       = \Carbon\Carbon::now();
                                $isOverdue = $d->due_at->lt($now->copy()->startOfDay());
                                $isToday   = $d->due_at->isToday();
                                $daysLeft  = (int) $now->copy()->startOfDay()->diffInDays($d->due_at->copy()->startOfDay(), false);

                                // Priority colors
                                $priorityMeta = match($d->priority ?? '') {
                                    'critical' => ['bg' => 'bg-red-100 dark:bg-red-900/40',    'text' => 'text-red-700 dark:text-red-300',    'label' => 'Critical'],
                                    'high'     => ['bg' => 'bg-orange-100 dark:bg-orange-900/40','text'=> 'text-orange-700 dark:text-orange-300','label' => 'High'],
                                    'medium'   => ['bg' => 'bg-blue-100 dark:bg-blue-900/40',  'text' => 'text-blue-700 dark:text-blue-300',  'label' => 'Medium'],
                                    'low'      => ['bg' => 'bg-slate-100 dark:bg-slate-700',   'text' => 'text-slate-600 dark:text-slate-300','label' => 'Low'],
                                    default    => ['bg' => '', 'text' => '', 'label' => ''],
                                };

                                // Status colors
                                $statusMeta = match($d->status ?? '') {
                                    'todo'        => ['bg' => 'bg-slate-100 dark:bg-slate-700', 'text' => 'text-slate-600 dark:text-slate-300', 'label' => 'To Do'],
                                    'in_progress' => ['bg' => 'bg-indigo-100 dark:bg-indigo-900/40', 'text' => 'text-indigo-700 dark:text-indigo-300', 'label' => 'In Progress'],
                                    'review'      => ['bg' => 'bg-purple-100 dark:bg-purple-900/40', 'text' => 'text-purple-700 dark:text-purple-300', 'label' => 'Review'],
                                    'done'        => ['bg' => 'bg-green-100 dark:bg-green-900/40', 'text' => 'text-green-700 dark:text-green-300', 'label' => 'Done'],
                                    default       => ['bg' => '', 'text' => '', 'label' => ''],
                                };

                                // Card border accent
                                $cardBorder = $isOverdue
                                    ? 'border-l-4 border-l-red-500'
                                    : ($isToday ? 'border-l-4 border-l-amber-500' : 'border-l-4 border-l-transparent');
                            @endphp

                            <div class="group relative flex flex-col bg-white dark:bg-slate-800/80 rounded-xl shadow-sm
                                        border border-slate-200 dark:border-slate-700/80
                                        hover:shadow-md hover:border-slate-300 dark:hover:border-slate-600
                                        transition-all duration-200 overflow-hidden {{ $cardBorder }}">

                                {{-- Card Body --}}
                                <div class="p-4 flex-1">

                                    {{-- Top row: type + date --}}
                                    <div class="flex items-center justify-between mb-3">
                                        @if($d->type === 'task')
                                            <span class="inline-flex items-center gap-1.5 text-xs font-semibold px-2 py-0.5 rounded-md bg-indigo-50 text-indigo-700 dark:bg-indigo-900/40 dark:text-indigo-300">
                                                <svg class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                                                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"/>
                                                </svg>
                                                Task
                                            </span>
                                        @else
                                            <span class="inline-flex items-center gap-1.5 text-xs font-semibold px-2 py-0.5 rounded-md bg-amber-50 text-amber-700 dark:bg-amber-900/40 dark:text-amber-300">
                                                <svg class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                                                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                                                </svg>
                                                CH Report
                                            </span>
                                        @endif

                                        {{-- Due date badge --}}
                                        @if($isOverdue)
                                            <span class="text-xs font-bold text-red-600 dark:text-red-400 tabular-nums">
                                                {{ abs($daysLeft) }}d overdue
                                            </span>
                                        @elseif($isToday)
                                            <span class="text-xs font-bold text-amber-600 dark:text-amber-400">Today</span>
                                        @elseif($daysLeft === 1)
                                            <span class="text-xs font-semibold text-blue-600 dark:text-blue-400">Tomorrow</span>
                                        @else
                                            <span class="text-xs text-slate-500 dark:text-slate-400 tabular-nums">
                                                in {{ $daysLeft }}d
                                            </span>
                                        @endif
                                    </div>

                                    {{-- Title --}}
                                    <h3 class="text-sm font-semibold text-slate-800 dark:text-slate-100 leading-snug mb-1 line-clamp-2">
                                        {{ $d->title }}
                                    </h3>

                                    {{-- Company --}}
                                    @if($d->project)
                                        <div class="flex items-center gap-1.5 mt-1.5">
                                            <svg class="w-3.5 h-3.5 text-slate-400 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/>
                                            </svg>
                                            <span class="text-xs text-slate-500 dark:text-slate-400 truncate font-medium">
                                                {{ $d->project->name }}
                                            </span>
                                        </div>
                                    @endif

                                    {{-- Description --}}
                                    @if($d->description)
                                        <p class="text-xs text-slate-500 dark:text-slate-400 mt-2 line-clamp-2 leading-relaxed">
                                            {{ \Illuminate\Support\Str::limit($d->description, 80) }}
                                        </p>
                                    @endif
                                </div>

                                {{-- Card Footer --}}
                                <div class="px-4 py-3 border-t border-slate-100 dark:border-slate-700/60 flex items-center justify-between gap-2">
                                    <div class="flex items-center gap-1.5 flex-wrap">
                                        {{-- Priority --}}
                                        @if($priorityMeta['label'])
                                            <span class="text-[10px] font-bold uppercase tracking-wide px-1.5 py-0.5 rounded
                                                         {{ $priorityMeta['bg'] }} {{ $priorityMeta['text'] }}">
                                                {{ $priorityMeta['label'] }}
                                            </span>
                                        @endif

                                        {{-- Status --}}
                                        @if($statusMeta['label'])
                                            <span class="text-[10px] font-semibold px-1.5 py-0.5 rounded
                                                         {{ $statusMeta['bg'] }} {{ $statusMeta['text'] }}">
                                                {{ $statusMeta['label'] }}
                                            </span>
                                        @endif
                                    </div>

                                    {{-- Due date + Assignee --}}
                                    <div class="flex items-center gap-2 flex-shrink-0">
                                        <span class="text-xs text-slate-500 dark:text-slate-400 tabular-nums font-medium">
                                            {{ $d->due_at->format('d M') }}
                                        </span>
                                        @if($d->assignee)
                                            <div class="w-6 h-6 rounded-full bg-gradient-to-tr from-sky-400 to-indigo-500
                                                        flex items-center justify-center text-[10px] font-bold text-white
                                                        flex-shrink-0 ring-2 ring-white dark:ring-slate-800"
                                                 title="{{ $d->assignee->name }}">
                                                {{ strtoupper(substr($d->assignee->name, 0, 2)) }}
                                            </div>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            @endforeach
        </div>
    @endif
</div>
