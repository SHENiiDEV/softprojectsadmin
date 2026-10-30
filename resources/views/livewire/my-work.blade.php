<div class="space-y-6" x-data="myWorkTimer()">

    {{-- Flash message --}}
    @if($flashMessage)
        <div class="flex items-center justify-between px-4 py-3 rounded-xl text-sm font-medium
                    {{ $flashType === 'success' ? 'bg-green-50 text-green-800 border border-green-200 dark:bg-green-900/30 dark:text-green-300 dark:border-green-800' : 'bg-red-50 text-red-800 border border-red-200 dark:bg-red-900/30 dark:text-red-300 dark:border-red-800' }}">
            <span>{{ $flashMessage }}</span>
            <button wire:click="dismissFlash" class="ml-4 text-current opacity-60 hover:opacity-100 cursor-pointer">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/>
                </svg>
            </button>
        </div>
    @endif

    {{-- ═══════════════════════════════════════════════════════════
         HEADER
    ═══════════════════════════════════════════════════════════ --}}
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
            <div class="flex items-center gap-3">
                <h1 class="text-2xl font-bold text-slate-900 dark:text-white tracking-tight">My Work</h1>
                @if($viewMode === 'done')
                    <span class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full text-xs font-semibold bg-emerald-100 text-emerald-800 dark:bg-emerald-950/60 dark:text-emerald-300 border border-emerald-200 dark:border-emerald-800">
                        <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/>
                        </svg>
                        Done Archive
                    </span>
                @else
                    <span class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full text-xs font-semibold bg-indigo-50 text-indigo-700 dark:bg-indigo-950/60 dark:text-indigo-300 border border-indigo-200/80 dark:border-indigo-800/80">
                        Active Tasks
                    </span>
                @endif
            </div>
            <p class="text-sm text-slate-500 dark:text-slate-400 mt-0.5">
                @if($viewMode === 'done')
                    Completed tasks assigned to me
                @else
                    Tasks assigned to me (To Do, In Progress, Review)
                @endif
            </p>
        </div>
        <div class="flex items-center gap-2">
            @if($viewMode === 'done')
                <button type="button"
                        wire:click="setViewMode('active')"
                        class="inline-flex items-center gap-2 px-3.5 py-2 text-sm font-semibold text-slate-700 dark:text-slate-200 bg-white dark:bg-slate-800 border border-slate-300 dark:border-slate-700 rounded-xl hover:bg-slate-50 dark:hover:bg-slate-700/50 transition-all cursor-pointer shadow-sm">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
                    </svg>
                    Back to Active
                </button>
            @endif
            <a href="{{ route('tasks.kanban') }}"
               class="inline-flex items-center gap-2 px-4 py-2 text-sm font-semibold text-indigo-700 dark:text-indigo-300
                      bg-indigo-50 dark:bg-indigo-900/30 border border-indigo-200 dark:border-indigo-700
                      rounded-xl hover:bg-indigo-100 dark:hover:bg-indigo-900/50 transition-all duration-200 cursor-pointer">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 17V7m0 10a2 2 0 01-2 2H5a2 2 0 01-2-2V7a2 2 0 012-2h2a2 2 0 012 2m0 10a2 2 0 002 2h2a2 2 0 002-2M9 7a2 2 0 012-2h2a2 2 0 012 2m0 10V7m0 10a2 2 0 002 2h2a2 2 0 002-2V7a2 2 0 00-2-2h-2a2 2 0 00-2 2"/>
                </svg>
                Open Kanban
            </a>
        </div>
    </div>

    {{-- ═══════════════════════════════════════════════════════════
         VIEW MODE SWITCHER (Active vs Done)
    ═══════════════════════════════════════════════════════════ --}}
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 bg-slate-100 dark:bg-slate-800/80 p-1.5 rounded-2xl border border-slate-200/80 dark:border-slate-700/80">
        <div class="flex items-center gap-1.5 w-full sm:w-auto">
            <button type="button"
                    wire:click="setViewMode('active')"
                    class="flex-1 sm:flex-initial flex items-center justify-center gap-2 px-4 py-2 text-sm rounded-xl transition-all cursor-pointer {{ $viewMode === 'active' ? 'bg-white dark:bg-slate-700 text-slate-900 dark:text-white shadow-sm font-semibold ring-1 ring-slate-200/50 dark:ring-slate-600/50' : 'text-slate-600 dark:text-slate-400 hover:text-slate-900 dark:hover:text-white font-medium' }}">
                <svg class="w-4 h-4 text-indigo-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"/>
                </svg>
                <span>Active Tasks</span>
                <span class="px-2 py-0.5 text-xs rounded-full font-bold {{ $viewMode === 'active' ? 'bg-indigo-100 text-indigo-700 dark:bg-indigo-950/80 dark:text-indigo-300' : 'bg-slate-200 dark:bg-slate-700 text-slate-600 dark:text-slate-400' }}">
                    {{ $stats['active_total'] }}
                </span>
            </button>

            <button type="button"
                    wire:click="setViewMode('done')"
                    class="flex-1 sm:flex-initial flex items-center justify-center gap-2 px-4 py-2 text-sm rounded-xl transition-all cursor-pointer {{ $viewMode === 'done' ? 'bg-white dark:bg-slate-700 text-slate-900 dark:text-white shadow-sm font-semibold ring-1 ring-slate-200/50 dark:ring-slate-600/50' : 'text-slate-600 dark:text-slate-400 hover:text-slate-900 dark:hover:text-white font-medium' }}">
                <svg class="w-4 h-4 text-emerald-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
                <span>Done</span>
                <span class="px-2 py-0.5 text-xs rounded-full font-bold {{ $viewMode === 'done' ? 'bg-emerald-100 text-emerald-700 dark:bg-emerald-950/80 dark:text-emerald-300' : 'bg-slate-200 dark:bg-slate-700 text-slate-600 dark:text-slate-400' }}">
                    {{ $stats['done_total'] }}
                </span>
            </button>
        </div>

        @if($viewMode === 'active')
            <div class="hidden sm:flex items-center gap-2 text-xs text-slate-500 dark:text-slate-400 pr-2">
                <span>Active stages:</span>
                <span class="font-medium text-slate-700 dark:text-slate-300">To Do</span>
                <span>•</span>
                <span class="font-medium text-blue-600 dark:text-blue-400">In Progress</span>
                <span>•</span>
                <span class="font-medium text-purple-600 dark:text-purple-400">Review</span>
            </div>
        @else
            <div class="hidden sm:flex items-center gap-1.5 text-xs text-emerald-600 dark:text-emerald-400 pr-2 font-medium">
                <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/>
                </svg>
                <span>Completed tasks archive</span>
            </div>
        @endif
    </div>

    {{-- ═══════════════════════════════════════════════════════════
         ACTIVE TIMER BANNER
    ═══════════════════════════════════════════════════════════ --}}
    @if($runningTimer)
        <div class="relative overflow-hidden rounded-2xl bg-gradient-to-r from-indigo-600 to-violet-600 p-4 text-white shadow-lg"
             x-data="{ elapsed: {{ time() - $runningTimer['started_at'] }} }"
             x-init="setInterval(() => elapsed++, 1000)">
            <div class="flex items-center justify-between">
                <div class="flex items-center gap-3">
                    <div class="w-2.5 h-2.5 rounded-full bg-green-400 animate-pulse"></div>
                    <div>
                        <p class="text-xs font-semibold uppercase tracking-widest text-indigo-200">Timer started</p>
                        <p class="font-semibold text-base mt-0.5">{{ $runningTimer['task']->title }}</p>
                        @if($runningTimer['task']->project)
                            <p class="text-xs text-indigo-200 mt-0.5">{{ $runningTimer['task']->project->name }}</p>
                        @endif
                    </div>
                </div>
                <div class="flex items-center gap-4">
                    <div class="text-right">
                        <p class="text-2xl font-bold tabular-nums font-mono"
                           x-text="new Date(elapsed * 1000).toISOString().substr(11, 8)">
                            00:00:00
                        </p>
                        <p class="text-xs text-indigo-200">active time</p>
                    </div>
                    <button wire:click="toggleTimer({{ $runningTimer['task']->id }})"
                            class="flex items-center gap-2 px-4 py-2 bg-white/20 hover:bg-white/30
                                   rounded-xl text-sm font-semibold transition-all duration-200 cursor-pointer border border-white/30">
                        <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 24 24">
                            <rect x="6" y="4" width="4" height="16" rx="1"/><rect x="14" y="4" width="4" height="16" rx="1"/>
                        </svg>
                        Stop
                    </button>
                </div>
            </div>
        </div>
    @endif

    {{-- ═══════════════════════════════════════════════════════════
         STATS
    ═══════════════════════════════════════════════════════════ --}}
    @if($viewMode === 'done')
        <div class="grid grid-cols-2 sm:grid-cols-4 gap-3">
            <div class="rounded-xl p-4 bg-emerald-50 dark:bg-emerald-950/40 border border-emerald-100 dark:border-emerald-900/50 shadow-sm">
                <p class="text-xs font-semibold uppercase tracking-widest text-emerald-600 dark:text-emerald-400 mb-1">Total Done</p>
                <p class="text-3xl font-bold text-emerald-700 dark:text-emerald-400 tabular-nums">{{ $stats['done_total'] }}</p>
            </div>
            <div class="rounded-xl p-4 bg-green-50 dark:bg-green-950/40 border border-green-100 dark:border-green-900/50 shadow-sm">
                <p class="text-xs font-semibold uppercase tracking-widest text-green-600 dark:text-green-400 mb-1">Done Today</p>
                <p class="text-3xl font-bold text-green-700 dark:text-green-400 tabular-nums">{{ $stats['done_today'] }}</p>
            </div>
            <div class="rounded-xl p-4 bg-teal-50 dark:bg-teal-950/40 border border-teal-100 dark:border-teal-900/50 shadow-sm">
                <p class="text-xs font-semibold uppercase tracking-widest text-teal-600 dark:text-teal-400 mb-1">Done This Week</p>
                <p class="text-3xl font-bold text-teal-700 dark:text-teal-400 tabular-nums">{{ $stats['done_this_week'] }}</p>
            </div>
            <div class="rounded-xl p-4 bg-cyan-50 dark:bg-cyan-950/40 border border-cyan-100 dark:border-cyan-900/50 shadow-sm">
                <p class="text-xs font-semibold uppercase tracking-widest text-cyan-600 dark:text-cyan-400 mb-1">Done This Month</p>
                <p class="text-3xl font-bold text-cyan-700 dark:text-cyan-400 tabular-nums">{{ $stats['done_this_month'] }}</p>
            </div>
        </div>
    @else
        <div class="grid grid-cols-2 sm:grid-cols-4 gap-3">
            <button type="button"
                    wire:click="$set('filterStatus', '')"
                    class="text-left rounded-xl p-4 bg-white dark:bg-slate-800 border {{ empty($filterStatus) ? 'ring-2 ring-indigo-500 border-indigo-500 shadow-md' : 'border-slate-200 dark:border-slate-700 shadow-sm' }} hover:border-indigo-300 transition-all cursor-pointer">
                <p class="text-xs font-semibold uppercase tracking-widest text-slate-500 dark:text-slate-400 mb-1">Active Tasks</p>
                <p class="text-3xl font-bold text-slate-800 dark:text-white tabular-nums">{{ $stats['active_total'] }}</p>
            </button>
            <button type="button"
                    wire:click="$set('filterStatus', 'in_progress')"
                    class="text-left rounded-xl p-4 bg-blue-50 dark:bg-blue-950/40 border {{ $filterStatus === 'in_progress' ? 'ring-2 ring-blue-500 border-blue-500 shadow-md' : 'border-blue-100 dark:border-blue-900/50 shadow-sm' }} hover:border-blue-300 transition-all cursor-pointer">
                <p class="text-xs font-semibold uppercase tracking-widest text-blue-600 dark:text-blue-400 mb-1">In Progress</p>
                <p class="text-3xl font-bold text-blue-700 dark:text-blue-400 tabular-nums">{{ $stats['in_progress'] }}</p>
            </button>
            <button type="button"
                    wire:click="$set('filterStatus', 'review')"
                    class="text-left rounded-xl p-4 bg-purple-50 dark:bg-purple-950/40 border {{ $filterStatus === 'review' ? 'ring-2 ring-purple-500 border-purple-500 shadow-md' : 'border-purple-100 dark:border-purple-900/50 shadow-sm' }} hover:border-purple-300 transition-all cursor-pointer">
                <p class="text-xs font-semibold uppercase tracking-widest text-purple-600 dark:text-purple-400 mb-1">In Review</p>
                <p class="text-3xl font-bold text-purple-700 dark:text-purple-400 tabular-nums">{{ $stats['review'] }}</p>
            </button>
            <div class="rounded-xl p-4 bg-red-50 dark:bg-red-950/40 border border-red-100 dark:border-red-900/50 shadow-sm">
                <p class="text-xs font-semibold uppercase tracking-widest text-red-600 dark:text-red-400 mb-1">Overdue</p>
                <p class="text-3xl font-bold text-red-600 dark:text-red-400 tabular-nums">{{ $stats['overdue'] }}</p>
            </div>
        </div>
    @endif

    {{-- ═══════════════════════════════════════════════════════════
         FILTERS
    ═══════════════════════════════════════════════════════════ --}}
    <div class="flex flex-wrap items-center gap-2">
        {{-- Search --}}
        <div class="relative flex-1 min-w-48">
            <svg class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
            </svg>
            <input wire:model.live.debounce.300ms="search"
                   type="text" placeholder="Search tasks..."
                   class="w-full pl-9 pr-3 py-2 text-sm rounded-xl bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700
                          text-slate-700 dark:text-slate-200 placeholder-slate-400
                          focus:outline-none focus:ring-2 focus:ring-indigo-500 transition-all duration-200"/>
        </div>

        {{-- Status --}}
        @if($viewMode === 'active')
            <select wire:model.live="filterStatus"
                    class="px-3 py-2 text-sm rounded-xl bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700
                           text-slate-700 dark:text-slate-200 focus:outline-none focus:ring-2 focus:ring-indigo-500 cursor-pointer">
                <option value="">All active (To Do / In Progress / Review)</option>
                <option value="todo">To Do</option>
                <option value="in_progress">In Progress</option>
                <option value="review">Review</option>
            </select>
        @else
            <div class="inline-flex items-center gap-1.5 px-3 py-2 text-sm rounded-xl bg-emerald-50 dark:bg-emerald-950/40 border border-emerald-200 dark:border-emerald-800 text-emerald-700 dark:text-emerald-300 font-medium">
                <svg class="w-4 h-4 text-emerald-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/>
                </svg>
                <span>Status: Done</span>
            </div>
        @endif

        {{-- Priority --}}
        <select wire:model.live="filterPriority"
                class="px-3 py-2 text-sm rounded-xl bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700
                       text-slate-700 dark:text-slate-200 focus:outline-none focus:ring-2 focus:ring-indigo-500 cursor-pointer">
            <option value="">Any priority</option>
            <option value="critical">Critical</option>
            <option value="high">High</option>
            <option value="medium">Medium</option>
            <option value="low">Low</option>
        </select>

        {{-- Sort --}}
        <select wire:model.live="sortBy"
                class="px-3 py-2 text-sm rounded-xl bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700
                       text-slate-700 dark:text-slate-200 focus:outline-none focus:ring-2 focus:ring-indigo-500 cursor-pointer">
            <option value="default">Default Sort</option>
            <option value="latest_comment">💬 Newest Comment First</option>
            <option value="due_date">📅 Due Date</option>
        </select>
    </div>

    {{-- ═══════════════════════════════════════════════════════════
         TASK LIST
    ═══════════════════════════════════════════════════════════ --}}
    @if($tasks->isEmpty())
        @if($viewMode === 'done')
            <div class="flex flex-col items-center justify-center py-20 text-center">
                <div class="w-16 h-16 rounded-2xl bg-emerald-50 dark:bg-emerald-950/40 border border-emerald-100 dark:border-emerald-900/50 flex items-center justify-center mb-4">
                    <svg class="w-8 h-8 text-emerald-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                </div>
                <p class="text-slate-700 dark:text-slate-200 font-semibold text-lg">No completed tasks</p>
                <p class="text-slate-400 dark:text-slate-500 text-sm mt-1 mb-4">No completed tasks match your selected filters.</p>
                <button type="button"
                        wire:click="setViewMode('active')"
                        class="px-4 py-2 text-sm font-semibold rounded-xl bg-indigo-600 text-white hover:bg-indigo-700 transition cursor-pointer shadow-sm">
                    Back to Active Tasks
                </button>
            </div>
        @else
            <div class="flex flex-col items-center justify-center py-20 text-center">
                <div class="w-16 h-16 rounded-2xl bg-slate-100 dark:bg-slate-800 flex items-center justify-center mb-4">
                    <svg class="w-8 h-8 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                </div>
                <p class="text-slate-600 dark:text-slate-300 font-semibold text-lg">No active tasks</p>
                <p class="text-slate-400 dark:text-slate-500 text-sm mt-1 {{ $stats['done_total'] > 0 ? 'mb-4' : '' }}">
                    No active tasks in To Do, In Progress, or Review match the selected filters.
                </p>
                @if($stats['done_total'] > 0)
                    <button type="button"
                            wire:click="setViewMode('done')"
                            class="px-4 py-2 text-sm font-medium rounded-xl bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 text-slate-700 dark:text-slate-300 hover:bg-slate-50 dark:hover:bg-slate-700 transition cursor-pointer shadow-sm">
                        View completed tasks ({{ $stats['done_total'] }})
                    </button>
                @endif
            </div>
        @endif
    @else
        <div class="space-y-3">
            @foreach($tasks as $task)
                @php
                    $now       = \Carbon\Carbon::now();
                    $dueDate   = $task->due_date ? \Carbon\Carbon::parse($task->due_date) : null;
                    $isOverdue = $dueDate && $dueDate->lt($now->startOfDay()) && $task->status !== 'done';
                    $isToday   = $dueDate && $dueDate->isToday();
                    $hasTimer  = isset($activeTimers[$task->id]);
                    $timerStart= $hasTimer ? $activeTimers[$task->id] : null;

                    $priorityColors = [
                        'critical' => 'bg-red-100 text-red-700 dark:bg-red-900/40 dark:text-red-300',
                        'high'     => 'bg-orange-100 text-orange-700 dark:bg-orange-900/40 dark:text-orange-300',
                        'medium'   => 'bg-blue-100 text-blue-700 dark:bg-blue-900/40 dark:text-blue-300',
                        'low'      => 'bg-slate-100 text-slate-600 dark:bg-slate-700 dark:text-slate-300',
                    ];
                    $statusOptions = [
                        'todo'        => ['label' => 'To Do',        'color' => 'bg-slate-100 text-slate-700 dark:bg-slate-700 dark:text-slate-300'],
                        'in_progress' => ['label' => 'In Progress',  'color' => 'bg-blue-100 text-blue-700 dark:bg-blue-900/40 dark:text-blue-300'],
                        'review'      => ['label' => 'Review',       'color' => 'bg-purple-100 text-purple-700 dark:bg-purple-900/40 dark:text-purple-300'],
                        'done'        => ['label' => 'Done',         'color' => 'bg-green-100 text-green-700 dark:bg-green-900/40 dark:text-green-300'],
                    ];
                    $currentStatus = $statusOptions[$task->status] ?? $statusOptions['todo'];
                @endphp

                <div class="group flex flex-col sm:flex-row sm:items-center gap-4 p-4
                            bg-white dark:bg-slate-800/80 rounded-xl
                            border border-slate-200 dark:border-slate-700
                            {{ $isOverdue ? 'border-l-4 border-l-red-500' : ($isToday ? 'border-l-4 border-l-amber-500' : ($hasTimer ? 'border-l-4 border-l-indigo-500' : '')) }}
                            hover:shadow-md hover:border-slate-300 dark:hover:border-slate-600
                            transition-all duration-200"
                     @if($hasTimer)
                         x-data="{ elapsed: {{ time() - $timerStart }} }"
                         x-init="setInterval(() => elapsed++, 1000)"
                     @endif>

                    {{-- Left: Checkbox & Status indicator --}}
                    <div class="flex items-center gap-2.5 flex-shrink-0">
                        <button type="button" 
                                wire:click="changeStatus({{ $task->id }}, '{{ $task->status === 'done' ? 'todo' : 'done' }}')"
                                title="{{ $task->status === 'done' ? 'Mark as To Do' : 'Mark as Done' }}"
                                class="w-4 h-4 rounded border flex items-center justify-center transition-colors cursor-pointer {{ $task->status === 'done' ? 'bg-emerald-500 border-emerald-500 text-white' : 'border-slate-300 dark:border-slate-600 hover:border-indigo-500 bg-white dark:bg-slate-900' }}">
                            @if($task->status === 'done')
                                <svg class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="3">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/>
                                </svg>
                            @endif
                        </button>
                        <div class="w-2.5 h-2.5 rounded-full
                                    {{ $task->status === 'done' ? 'bg-green-500' :
                                       ($task->status === 'in_progress' ? 'bg-blue-500 animate-pulse' :
                                       ($task->status === 'review' ? 'bg-purple-500' : 'bg-slate-300 dark:bg-slate-600')) }}">
                        </div>
                    </div>

                    {{-- Middle: Task info --}}
                    <div class="flex-1 min-w-0">
                        <div class="flex flex-wrap items-center gap-2 mb-1">
                            @if($task->parent)
                                <span class="text-[10px] font-bold tracking-wide px-1.5 py-0.5 rounded bg-indigo-50 text-indigo-700 dark:bg-indigo-950/60 dark:text-indigo-300 border border-indigo-200/60 dark:border-indigo-800/60" title="Parent Task: {{ $task->parent->title }}">
                                    📌 Subtask of: {{ $task->parent->title }}
                                </span>
                            @endif
                            <h3 class="text-sm font-semibold text-slate-800 dark:text-slate-100 truncate {{ $task->status === 'done' ? 'line-through text-slate-400 dark:text-slate-500' : '' }}">
                                {{ $task->title }}
                            </h3>
                            {{-- Priority --}}
                            <span class="text-[10px] font-bold uppercase tracking-wide px-1.5 py-0.5 rounded
                                         {{ $priorityColors[$task->priority] ?? '' }}">
                                {{ $task->priority }}
                            </span>
                        </div>

                        <div class="flex flex-wrap items-center gap-3 text-xs text-slate-500 dark:text-slate-400">
                            {{-- Project --}}
                            @if($task->project)
                                <span class="flex items-center gap-1">
                                    <svg class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/>
                                    </svg>
                                    {{ $task->project->name }}
                                </span>
                            @endif

                            {{-- Due date --}}
                            @if($dueDate)
                                <span class="flex items-center gap-1 {{ $isOverdue ? 'text-red-600 dark:text-red-400 font-semibold' : ($isToday ? 'text-amber-600 dark:text-amber-400 font-semibold' : '') }}">
                                    <svg class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2v-7a2 2 0 00-2-2H5a2 2 0 00-2 2v7a2 2 0 002 2z"/>
                                    </svg>
                                    {{ $isOverdue ? 'Overdue: ' : '' }}{{ $dueDate->format('d M Y') }}
                                </span>
                            @endif

                            {{-- Assignees --}}
                            @php
                                $rowAssignees = $task->assignees->isNotEmpty() ? $task->assignees : ($task->assignee ? collect([$task->assignee]) : collect());
                            @endphp
                            @if($rowAssignees->isNotEmpty())
                                <span class="flex items-center gap-1 text-slate-500 dark:text-slate-400">
                                    <div class="flex -space-x-1">
                                        @foreach($rowAssignees as $u)
                                            <div class="h-4 w-4 rounded-full flex items-center justify-center font-bold text-white text-[7px] uppercase shadow-sm ring-1 ring-white dark:ring-slate-900" style="background-color: {{ $u->color }};" title="{{ $u->name }}">
                                                {{ substr($u->name, 0, 2) }}
                                            </div>
                                        @endforeach
                                    </div>
                                    <span class="truncate max-w-[120px]">{{ $rowAssignees->map(fn($u) => explode(' ', $u->name)[0])->implode(', ') }}</span>
                                </span>
                            @endif

                            {{-- Timer display --}}
                            @if($hasTimer)
                                <span class="flex items-center gap-1 text-indigo-600 dark:text-indigo-400 font-semibold tabular-nums font-mono">
                                    <svg class="w-3 h-3 animate-pulse" fill="currentColor" viewBox="0 0 24 24">
                                        <circle cx="12" cy="12" r="10" fill="none" stroke="currentColor" stroke-width="2"/>
                                        <path d="M12 6v6l4 2" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
                                    </svg>
                                    <span x-text="new Date(elapsed * 1000).toISOString().substr(11, 8)">00:00:00</span>
                                </span>
                            @endif
                        </div>

                        @if($task->excerpt)
                            <p class="text-xs text-slate-500 dark:text-slate-400 mt-1.5 line-clamp-1">
                                {{ $task->excerpt }}
                            </p>
                        @endif

                        {{-- Latest Comment --}}
                        @if($task->latestComment)
                            @php
                                $cmt = $task->latestComment;
                                $authorName = $cmt->user?->name ?? $cmt->client?->name ?? 'System';
                                $isClient = (bool) $cmt->client_id;
                                $cleanComment = Str::limit(strip_tags($cmt->content), 130);
                            @endphp
                            <div class="mt-2.5 p-2.5 rounded-xl bg-slate-50 dark:bg-slate-900/60 border border-slate-200/60 dark:border-slate-800/80 text-xs">
                                <div class="flex items-center justify-between gap-2 mb-1">
                                    <div class="flex items-center gap-1.5 font-semibold text-slate-700 dark:text-slate-200">
                                        <i class="fa-regular fa-comment-dots {{ $isClient ? 'text-purple-500' : 'text-sky-500' }}"></i>
                                        <span>{{ $authorName }}</span>
                                        @if($isClient)
                                            <span class="px-1.5 py-0.2 text-[9px] font-bold rounded bg-purple-100 text-purple-700 dark:bg-purple-950/60 dark:text-purple-300">Client</span>
                                        @endif
                                    </div>
                                    <span class="text-[10px] text-slate-400 font-medium" title="{{ $cmt->created_at->format('d.m.Y H:i:s') }}">
                                        {{ $cmt->created_at->format('d.m.Y H:i:s') }} ({{ $cmt->created_at->diffForHumans() }})
                                    </span>
                                </div>
                                <p class="text-slate-600 dark:text-slate-400 line-clamp-2 leading-relaxed">
                                    {{ $cleanComment }}
                                </p>
                            </div>
                        @endif
                    </div>

                    {{-- Right: Actions --}}
                    <div class="flex items-center gap-2 flex-shrink-0">

                        {{-- Status change dropdown --}}
                        <div x-data="{ open: false }" class="relative">
                            <button @click="open = !open"
                                    class="flex items-center gap-1.5 px-2.5 py-1.5 text-xs font-semibold rounded-lg
                                           {{ $currentStatus['color'] }}
                                           hover:opacity-80 transition-all duration-200 cursor-pointer">
                                {{ $currentStatus['label'] }}
                                <svg class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7"/>
                                </svg>
                            </button>
                            <div x-show="open" @click.away="open = false"
                                 x-transition:enter="transition ease-out duration-150"
                                 x-transition:enter-start="opacity-0 scale-95"
                                 x-transition:enter-end="opacity-100 scale-100"
                                 class="absolute right-0 mt-1 w-36 bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700
                                        rounded-xl shadow-lg z-10 overflow-hidden py-1">
                                @foreach(['todo' => 'To Do', 'in_progress' => 'In Progress', 'review' => 'Review', 'done' => 'Done'] as $statusKey => $statusLabel)
                                    <button wire:click="changeStatus({{ $task->id }}, '{{ $statusKey }}')"
                                            @click="open = false"
                                            class="w-full text-left px-3 py-2 text-xs font-medium
                                                   {{ $task->status === $statusKey ? 'bg-indigo-50 text-indigo-700 dark:bg-indigo-900/30 dark:text-indigo-300' : 'text-slate-700 dark:text-slate-300 hover:bg-slate-50 dark:hover:bg-slate-700' }}
                                                   transition-colors duration-150 cursor-pointer">
                                        {{ $statusLabel }}
                                    </button>
                                @endforeach
                            </div>
                        </div>

                        {{-- Timer toggle --}}
                        <button wire:click="toggleTimer({{ $task->id }})"
                                title="{{ $hasTimer ? 'Stop timer' : 'Start timer' }}"
                                class="p-2 rounded-lg transition-all duration-200 cursor-pointer
                                       {{ $hasTimer
                                           ? 'bg-indigo-100 text-indigo-700 hover:bg-indigo-200 dark:bg-indigo-900/40 dark:text-indigo-300'
                                           : 'bg-slate-100 text-slate-600 hover:bg-slate-200 dark:bg-slate-700 dark:text-slate-300 dark:hover:bg-slate-600' }}">
                            @if($hasTimer)
                                <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 24 24">
                                    <rect x="6" y="4" width="4" height="16" rx="1"/><rect x="14" y="4" width="4" height="16" rx="1"/>
                                </svg>
                            @else
                                <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 24 24">
                                    <path d="M8 5v14l11-7z"/>
                                </svg>
                            @endif
                        </button>

                        {{-- Open in Kanban --}}
                        <a href="{{ route('tasks.kanban') }}?task_id={{ $task->id }}"
                           title="Open in Kanban"
                           class="p-2 rounded-lg bg-slate-100 text-slate-600 hover:bg-slate-200
                                  dark:bg-slate-700 dark:text-slate-300 dark:hover:bg-slate-600
                                  transition-all duration-200 cursor-pointer">
                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/>
                            </svg>
                        </a>
                    </div>
                </div>
            @endforeach
        </div>
    @endif
</div>

<script>
function myWorkTimer() {
    return {};
}
</script>
