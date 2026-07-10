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
            <h1 class="text-2xl font-bold text-slate-900 dark:text-white tracking-tight">My Work</h1>
            <p class="text-sm text-slate-500 dark:text-slate-400 mt-0.5">Tasks assigned to me</p>
        </div>
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
    <div class="grid grid-cols-2 sm:grid-cols-4 gap-3">
        <div class="rounded-xl p-4 bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 shadow-sm">
            <p class="text-xs font-semibold uppercase tracking-widest text-slate-500 dark:text-slate-400 mb-1">Total</p>
            <p class="text-3xl font-bold text-slate-800 dark:text-white tabular-nums">{{ $stats['total'] }}</p>
        </div>
        <div class="rounded-xl p-4 bg-blue-50 dark:bg-blue-950/40 border border-blue-100 dark:border-blue-900/50 shadow-sm">
            <p class="text-xs font-semibold uppercase tracking-widest text-blue-600 dark:text-blue-400 mb-1">In Progress</p>
            <p class="text-3xl font-bold text-blue-700 dark:text-blue-400 tabular-nums">{{ $stats['in_progress'] }}</p>
        </div>
        <div class="rounded-xl p-4 bg-red-50 dark:bg-red-950/40 border border-red-100 dark:border-red-900/50 shadow-sm">
            <p class="text-xs font-semibold uppercase tracking-widest text-red-600 dark:text-red-400 mb-1">Overdue</p>
            <p class="text-3xl font-bold text-red-600 dark:text-red-400 tabular-nums">{{ $stats['overdue'] }}</p>
        </div>
        <div class="rounded-xl p-4 bg-green-50 dark:bg-green-950/40 border border-green-100 dark:border-green-900/50 shadow-sm">
            <p class="text-xs font-semibold uppercase tracking-widest text-green-600 dark:text-green-400 mb-1">Done Today</p>
            <p class="text-3xl font-bold text-green-700 dark:text-green-400 tabular-nums">{{ $stats['done_today'] }}</p>
        </div>
    </div>

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
        <select wire:model.live="filterStatus"
                class="px-3 py-2 text-sm rounded-xl bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700
                       text-slate-700 dark:text-slate-200 focus:outline-none focus:ring-2 focus:ring-indigo-500 cursor-pointer">
            <option value="">All (except done)</option>
            <option value="todo">To Do</option>
            <option value="in_progress">In Progress</option>
            <option value="review">Review</option>
            <option value="done">Done</option>
        </select>

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
    </div>

    {{-- ═══════════════════════════════════════════════════════════
         TASK LIST
    ═══════════════════════════════════════════════════════════ --}}
    @if($tasks->isEmpty())
        <div class="flex flex-col items-center justify-center py-20 text-center">
            <div class="w-16 h-16 rounded-2xl bg-slate-100 dark:bg-slate-800 flex items-center justify-center mb-4">
                <svg class="w-8 h-8 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
            </div>
            <p class="text-slate-600 dark:text-slate-300 font-semibold text-lg">No tasks</p>
            <p class="text-slate-400 dark:text-slate-500 text-sm mt-1">No tasks match the selected filters.</p>
        </div>
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

                    {{-- Left: Status indicator --}}
                    <div class="flex-shrink-0">
                        <div class="w-3 h-3 rounded-full mt-1
                                    {{ $task->status === 'done' ? 'bg-green-500' :
                                       ($task->status === 'in_progress' ? 'bg-blue-500 animate-pulse' :
                                       ($task->status === 'review' ? 'bg-purple-500' : 'bg-slate-300 dark:bg-slate-600')) }}">
                        </div>
                    </div>

                    {{-- Middle: Task info --}}
                    <div class="flex-1 min-w-0">
                        <div class="flex flex-wrap items-center gap-2 mb-1">
                            <h3 class="text-sm font-semibold text-slate-800 dark:text-slate-100 truncate">
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

                        @if($task->description)
                            <p class="text-xs text-slate-500 dark:text-slate-400 mt-1.5 line-clamp-1">
                                {{ \Illuminate\Support\Str::limit($task->description, 100) }}
                            </p>
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
