<div class="space-y-6" x-data="{
    draggedId: null,
    dragStart(e, id) {
        this.draggedId = id;
        e.dataTransfer.effectAllowed = 'move';
        e.dataTransfer.setData('text/plain', id);
    },
    drop(e, status) {
        if (this.draggedId) {
            $wire.call('updateTaskStatus', this.draggedId, status);
            this.draggedId = null;
        }
    }
}">
    <x-slot name="header">
        Tasks (Kanban)
    </x-slot>

    <!-- Header Actions & Notifications -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <h1 class="font-outfit font-extrabold text-2xl text-slate-800 dark:text-white tracking-tight">Tasks & Projects</h1>
            <p class="text-sm text-slate-500 dark:text-slate-400 mt-1">Track the progress of team tasks.</p>
        </div>
        <div class="flex items-center gap-2">
            <!-- Show Archive Toggle -->
            <button wire:click="$set('showArchived', '{{ $showArchived === '0' ? '1' : '0' }}')" 
                    class="inline-flex items-center justify-center px-4 py-2.5 text-sm font-semibold rounded-xl border transition-all duration-200 cursor-pointer shadow-sm {{ $showArchived === '1' ? 'bg-amber-50 text-amber-800 border-amber-300 dark:bg-amber-950/40 dark:text-amber-300 dark:border-amber-800' : 'bg-white text-slate-700 border-slate-200 hover:bg-slate-50 dark:bg-slate-900 dark:text-slate-200 dark:border-slate-800 dark:hover:bg-slate-800' }}">
                <i class="fa-solid fa-box-archive mr-2 {{ $showArchived === '1' ? 'text-amber-500' : 'text-slate-400' }}"></i>
                <span>{{ $showArchived === '1' ? 'Show Active Tasks' : 'Show Archive' }}</span>
                @if(($archivedCount ?? 0) > 0)
                    <span class="ml-2 px-2 py-0.5 text-xs font-bold rounded-lg {{ $showArchived === '1' ? 'bg-amber-200 text-amber-900 dark:bg-amber-900 dark:text-amber-100' : 'bg-slate-100 text-slate-600 dark:bg-slate-800 dark:text-slate-400' }}">
                        {{ $archivedCount }}
                    </span>
                @endif
            </button>

            @if(!auth()->user()->hasRole('curator'))
                <button wire:click="openTaskModal()" class="inline-flex items-center justify-center px-4 py-2.5 text-sm font-semibold text-white bg-sky-600 hover:bg-sky-500 dark:bg-sky-500 dark:hover:bg-sky-400 rounded-xl shadow-sm hover:-translate-y-0.5 transition-all duration-200 cursor-pointer">
                    <svg class="w-4 h-4 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4" />
                    </svg>
                    Create Task
                </button>
            @endif
        </div>
    </div>

    <!-- Alert / Messages -->
    @if (session()->has('message'))
        <div x-data="{ show: true }" x-show="show" x-init="setTimeout(() => show = false, 4000)" x-transition class="p-4 rounded-xl bg-emerald-50 dark:bg-emerald-950/20 border border-emerald-100 dark:border-emerald-800/40 text-emerald-800 dark:text-emerald-400 flex items-center justify-between shadow-sm">
            <div class="flex items-center space-x-3">
                <svg class="h-5 w-5 text-emerald-600 dark:text-emerald-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
                <span class="text-sm font-medium">{{ session('message') }}</span>
            </div>
            <button @click="show = false" class="text-emerald-500 hover:text-emerald-700 dark:hover:text-emerald-300">
                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                </svg>
            </button>
        </div>
    @endif

    @if (session()->has('error'))
        <div x-data="{ show: true }" x-show="show" x-init="setTimeout(() => show = false, 5000)" 
             x-transition:enter="transition ease-out duration-300"
             x-transition:enter-start="opacity-0 scale-90 translate-y-4"
             x-transition:enter-end="opacity-100 scale-100 translate-y-0"
             x-transition:leave="transition ease-in duration-200"
             x-transition:leave-start="opacity-100 scale-100 translate-y-0"
             x-transition:leave-end="opacity-0 scale-90 translate-y-4"
             class="fixed inset-0 z-[100] flex items-center justify-center p-4 pointer-events-none">
            
            <div class="bg-white dark:bg-slate-900 border-2 border-rose-500/80 rounded-2xl p-5 shadow-2xl max-w-md w-full pointer-events-auto flex items-start gap-4 ring-4 ring-rose-500/20">
                <div class="p-3 bg-rose-100 dark:bg-rose-950/60 rounded-xl text-rose-600 dark:text-rose-400 flex-shrink-0">
                    <i class="fa-solid fa-triangle-exclamation text-xl"></i>
                </div>
                <div class="flex-1 min-w-0">
                    <h4 class="font-outfit font-bold text-sm text-slate-800 dark:text-slate-100">Notice</h4>
                    <p class="text-xs font-semibold text-rose-600 dark:text-rose-400 mt-1 leading-relaxed">{{ session('error') }}</p>
                </div>
                <button @click="show = false" type="button" class="text-slate-400 hover:text-slate-600 dark:hover:text-slate-200 text-sm p-1 cursor-pointer">
                    <i class="fa-solid fa-xmark"></i>
                </button>
            </div>
        </div>
    @endif

    <!-- Filters Bar -->
    <div class="bg-white dark:bg-slate-900 border border-slate-100 dark:border-slate-800/80 rounded-2xl p-5 shadow-sm">
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-12 gap-4">
            <!-- Search -->
            <div class="relative lg:col-span-4">
                <span class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-slate-400">
                    <svg class="h-4.5 w-4.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                    </svg>
                </span>
                <input type="text" wire:model.live.debounce.300ms="search" placeholder="Search by title or description..." class="w-full pl-9 pr-4 py-2 bg-slate-50 dark:bg-slate-950 border border-slate-200 dark:border-slate-800/80 rounded-xl text-xs text-slate-800 dark:text-slate-100 placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-sky-500/20 focus:border-sky-500 transition-all duration-150">
            </div>

            <!-- Project Filter -->
            <div class="lg:col-span-3">
                <select wire:model.live="filterProject" class="w-full px-3.5 py-2 bg-slate-50 dark:bg-slate-950 border border-slate-200 dark:border-slate-800/80 rounded-xl text-xs text-slate-800 dark:text-slate-100 focus:outline-none focus:ring-2 focus:ring-sky-500/20 focus:border-sky-500 transition-all duration-150">
                    <option value="">All Companies (Projects)</option>
                    <option value="global">Global Tasks</option>
                    @foreach($projects as $p)
                        <option value="{{ $p->id }}">{{ $p->name }}</option>
                    @endforeach
                </select>
            </div>

            <!-- Assignee Filter -->
            <div class="lg:col-span-3">
                <select wire:model.live="filterAssignee" class="w-full px-3.5 py-2 bg-slate-50 dark:bg-slate-950 border border-slate-200 dark:border-slate-800/80 rounded-xl text-xs text-slate-800 dark:text-slate-100 focus:outline-none focus:ring-2 focus:ring-sky-500/20 focus:border-sky-500 transition-all duration-150">
                    <option value="">All Assignees</option>
                    @foreach($users as $u)
                        <option value="{{ $u->id }}">{{ $u->name }}</option>
                    @endforeach
                </select>
            </div>

            <!-- Priority Filter -->
            <div class="lg:col-span-2">
                <select wire:model.live="filterPriority" class="w-full px-3.5 py-2 bg-slate-50 dark:bg-slate-950 border border-slate-200 dark:border-slate-800/80 rounded-xl text-xs text-slate-800 dark:text-slate-100 focus:outline-none focus:ring-2 focus:ring-sky-500/20 focus:border-sky-500 transition-all duration-150">
                    <option value="">All Priorities</option>
                    <option value="low">Low</option>
                    <option value="medium">Medium</option>
                    <option value="high">High</option>
                    <option value="critical">Critical</option>
                </select>
            </div>
        </div>
    </div>

    <!-- Kanban Grid -->
    <div class="grid grid-cols-1 md:grid-cols-3 lg:grid-cols-5 gap-5">
        <!-- Loop statuses -->
        @foreach(['email_inbox' => ['Name' => '📨 Email Requests', 'bg' => 'bg-purple-50/60 dark:bg-purple-950/20 border-purple-200/60 dark:border-purple-900/40', 'badge' => 'bg-purple-100 text-purple-700 dark:bg-purple-950/50 dark:text-purple-300'],
                  'todo' => ['Name' => 'To Do', 'bg' => 'bg-slate-100 dark:bg-slate-900/60 border-slate-200/40 dark:border-slate-800', 'badge' => 'bg-slate-200 text-slate-700 dark:bg-slate-800 dark:text-slate-400'],
                  'in_progress' => ['Name' => 'In Progress', 'bg' => 'bg-sky-50/50 dark:bg-sky-950/10 border-sky-100 dark:border-sky-950/20', 'badge' => 'bg-sky-100 text-sky-700 dark:bg-sky-950/50 dark:text-sky-400'],
                  'review' => ['Name' => 'Review', 'bg' => 'bg-amber-50/30 dark:bg-amber-950/5 border-amber-100/40 dark:border-amber-950/10', 'badge' => 'bg-amber-100 text-amber-700 dark:bg-amber-950/50 dark:text-amber-400'],
                  'done' => ['Name' => 'Done', 'bg' => 'bg-emerald-50/30 dark:bg-emerald-950/5 border-emerald-100/40 dark:border-emerald-950/10', 'badge' => 'bg-emerald-100 text-emerald-700 dark:bg-emerald-950/50 dark:text-emerald-400']] as $statusCode => $statusDetails)
            
            <div class="flex flex-col rounded-2xl border p-4 {{ $statusDetails['bg'] }}">
                <!-- Status Column Header -->
                <div class="flex items-center justify-between pb-3.5 mb-4 border-b border-slate-200/50 dark:border-slate-800/50">
                    <span class="font-outfit font-bold text-sm text-slate-700 dark:text-slate-200">{{ $statusDetails['Name'] }}</span>
                    <span class="px-2 py-0.5 rounded-lg text-xs font-bold {{ $statusDetails['badge'] }}">
                        {{ $statusCounts[$statusCode] ?? count($tasks[$statusCode]) }}
                    </span>
                </div>

                <!-- Dropzone zone for drag and drop -->
                <div class="flex-1 space-y-3.5 min-h-[500px]" 
                     data-kanban-column="{{ $statusCode }}"
                     @dragover.prevent
                     @drop="drop($event, '{{ $statusCode }}')">
                    
                    @forelse($tasks[$statusCode] as $task)
                        <!-- Task Card -->
                        <div draggable="true" 
                             data-task-id="{{ $task->id }}"
                             @dragstart="dragStart($event, {{ $task->id }})"
                             class="group relative bg-white dark:bg-slate-900 border border-slate-200/60 dark:border-slate-800 rounded-xl p-4 shadow-sm hover:shadow-md hover:border-slate-300 dark:hover:border-slate-700 transition-all duration-150 cursor-grab active:cursor-grabbing">
                            
                            <!-- Badges -->
                            <div class="flex flex-wrap items-center gap-1.5 mb-2.5">
                                <!-- Priority -->
                                <span class="inline-flex items-center px-1.5 py-0.5 rounded text-[9px] font-bold uppercase tracking-wider
                                    @if($task->priority === 'low') bg-slate-100 text-slate-600 dark:bg-slate-800 dark:text-slate-400
                                    @elseif($task->priority === 'medium') bg-sky-50 text-sky-700 dark:bg-sky-950/30 dark:text-sky-400
                                    @elseif($task->priority === 'high') bg-amber-50 text-amber-700 dark:bg-amber-950/30 dark:text-amber-400
                                    @else bg-rose-50 text-rose-700 dark:bg-rose-950/30 dark:text-rose-400 @endif">
                                    {{ $task->priority }}
                                </span>

                                <!-- Project label -->
                                @if($task->project)
                                    <span class="inline-flex items-center px-1.5 py-0.5 rounded text-[9px] font-bold bg-indigo-50 text-indigo-700 dark:bg-indigo-950/30 dark:text-indigo-400 max-w-[120px] truncate" title="{{ $task->project->name }}">
                                        {{ $task->project->name }}
                                    </span>
                                @else
                                    <span class="inline-flex items-center px-1.5 py-0.5 rounded text-[9px] font-medium bg-slate-100 text-slate-500 dark:bg-slate-800 dark:text-slate-400">
                                        Global
                                    </span>
                                @endif
                            </div>

                            <!-- Title -->
                            <h4 class="font-semibold text-sm text-slate-800 dark:text-slate-200 group-hover:text-sky-600 dark:group-hover:text-sky-400 transition-colors">
                                <button type="button" wire:click="openTaskModal({{ $task->id }})" class="text-left hover:underline focus:outline-none">
                                    {{ $task->title }}
                                </button>
                            </h4>

                            <!-- Description Snippet -->
                            @if($task->excerpt)
                                <p class="text-xs text-slate-400 dark:text-slate-500 mt-1.5 line-clamp-2">
                                    {{ $task->excerpt }}
                                </p>
                            @endif

                            <!-- Footer Info -->
                            <div class="flex items-center justify-between mt-4 pt-3 border-t border-slate-100 dark:border-slate-800/60 text-[10px] text-slate-400">
                                <!-- Due Date & Timer Info -->
                                <div class="flex flex-col space-y-1.5">
                                    <!-- Due Date -->
                                    <div class="flex items-center space-x-1">
                                        @if($task->due_date)
                                            @php
                                                $dueDate = \Carbon\Carbon::parse($task->due_date);
                                                $isPast = $dueDate->isPast() && !$dueDate->isToday();
                                                $isTodayOrTomorrow = $dueDate->isToday() || $dueDate->isTomorrow();
                                                
                                                $colorClass = 'text-slate-400';
                                                $svgClass = 'text-slate-400';
                                                if ($task->status !== 'done') {
                                                    if ($isPast) {
                                                        $colorClass = 'text-rose-500 font-bold';
                                                        $svgClass = 'text-rose-500 animate-pulse';
                                                    } elseif ($isTodayOrTomorrow) {
                                                        $colorClass = 'text-amber-500 font-bold';
                                                        $svgClass = 'text-amber-500';
                                                    } else {
                                                        $colorClass = 'text-emerald-600 dark:text-emerald-400 font-semibold';
                                                        $svgClass = 'text-emerald-500';
                                                    }
                                                }
                                            @endphp
                                            <svg class="h-3.5 w-3.5 {{ $svgClass }}" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                                            </svg>
                                            <span class="{{ $colorClass }}">
                                                {{ $dueDate->format('d.m.Y') }}
                                            </span>
                                        @else
                                            <span>No deadline</span>
                                        @endif
                                    </div>

                                    <!-- Timer Section (if assigned) -->
                                    @if(config('features.task_time_logs', true) && $task->assigned_to)
                                        @php
                                            $activeTimer = $task->activeTimer();
                                        @endphp
                                        <div class="flex items-center space-x-1.5 flex-wrap">
                                            @if($activeTimer)
                                                <button type="button" wire:click="toggleTimer({{ $task->id }})" class="px-1.5 py-0.5 rounded bg-rose-50 dark:bg-rose-950/40 text-rose-600 dark:text-rose-400 border border-rose-100/40 dark:border-rose-900/30 flex items-center space-x-1 text-[8px] font-bold animate-pulse" title="Stop tracking time">
                                                    <span class="w-1.5 h-1.5 rounded-full bg-rose-600 dark:bg-rose-400"></span>
                                                    <span>Stop</span>
                                                </button>
                                                <!-- Live ticking timer via Alpine JS -->
                                                <div x-data="{ 
                                                     elapsed: {{ (int) $activeTimer->started_at->diffInSeconds(now(), true) }},
                                                     init() {
                                                         setInterval(() => {
                                                             this.elapsed++;
                                                         }, 1000);
                                                     },
                                                     formatTime(seconds) {
                                                         const hrs = Math.floor(seconds / 3600);
                                                         const mins = Math.floor((seconds % 3600) / 60);
                                                         const secs = seconds % 60;
                                                         return `${hrs.toString().padStart(2, '0')}:${mins.toString().padStart(2, '0')}:${secs.toString().padStart(2, '0')}`;
                                                     }
                                                }" class="font-mono text-emerald-600 dark:text-emerald-400 font-bold text-[9px] leading-none whitespace-nowrap">
                                                    <span x-text="formatTime(elapsed)"></span>
                                                </div>
                                            @else
                                                @if($task->assigned_to === auth()->id() || auth()->user()->hasAnyRole(['admin', 'manager']))
                                                    <button type="button" wire:click="toggleTimer({{ $task->id }})" class="px-1.5 py-0.5 rounded bg-slate-50 hover:bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-400 border border-slate-200 dark:border-slate-700 flex items-center space-x-1 text-[8px] font-bold transition-all duration-150 whitespace-nowrap" title="Start tracking time">
                                                        <svg class="h-2 w-2 text-slate-500" fill="currentColor" viewBox="0 0 24 24">
                                                            <path d="M8 5v14l11-7z"/>
                                                        </svg>
                                                        <span>Start</span>
                                                    </button>
                                                @endif
                                                @if($task->total_duration > 0)
                                                    <span class="inline-flex items-center px-1.5 py-0.5 rounded font-mono text-[9px] bg-slate-50 dark:bg-slate-900 border border-slate-200/60 dark:border-slate-800/80 text-slate-500 dark:text-slate-400 whitespace-nowrap" title="Total tracked time">
                                                        ⏱️ {{ $task->human_formatted_duration }}
                                                    </span>
                                                @endif
                                            @endif
                                        </div>
                                    @endif
                                </div>

                                <!-- Assignee Info / Avatar -->
                                <div class="flex items-center space-x-1.5 self-end">
                                    @if(config('features.task_attachments', true) && $task->media->count() > 0)
                                        <div class="flex items-center text-slate-400 mr-1" title="Attached files">
                                            <svg class="h-3.5 w-3.5 mr-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M15.172 7l-6.586 6.586a2 2 0 102.828 2.828l6.414-6.586a4 4 0 00-5.656-5.656l-6.415 6.585a6 6 0 108.486 8.486L20.5 13" />
                                            </svg>
                                            <span class="font-bold">{{ $task->media->count() }}</span>
                                        </div>
                                    @endif

                                    @if($task->assignee)
                                        <div class="flex items-center space-x-1.5 bg-slate-50 dark:bg-slate-950/40 py-0.5 pl-2 pr-0.5 rounded-full border border-slate-200/50 dark:border-slate-800/80">
                                            <span class="text-[9px] font-semibold text-slate-500 dark:text-slate-400 truncate max-w-[70px]" title="{{ $task->assignee->name }}">
                                                {{ explode(' ', $task->assignee->name)[0] }}
                                            </span>
                                            <div class="h-5 w-5 rounded-full bg-gradient-to-tr {{ $task->assignee->gradient }} flex items-center justify-center font-bold text-white text-[8px] uppercase" title="Assignee: {{ $task->assignee->name }}">
                                                {{ substr($task->assignee->name, 0, 2) }}
                                            </div>
                                        </div>
                                    @else
                                        <button type="button" wire:click="takeTask({{ $task->id }})" class="px-2 py-1 bg-indigo-50 hover:bg-indigo-100 dark:bg-indigo-950/40 dark:hover:bg-indigo-900/40 text-indigo-600 dark:text-indigo-400 rounded-lg text-[9px] font-bold border border-indigo-100/40 dark:border-indigo-900/30 transition-all duration-150 flex items-center space-x-1" title="Take this task">
                                            <svg class="h-2.5 w-2.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="3">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4" />
                                            </svg>
                                            <span>Take</span>
                                        </button>
                                    @endif

                                    @if($showArchived === '1' || $task->isArchived())
                                        <button type="button" wire:click="restoreTask({{ $task->id }})" class="px-2 py-1 bg-emerald-50 hover:bg-emerald-100 dark:bg-emerald-950/40 dark:hover:bg-emerald-900/40 text-emerald-600 dark:text-emerald-400 rounded-lg text-[9px] font-bold border border-emerald-100/40 dark:border-emerald-900/30 transition-all duration-150 flex items-center space-x-1" title="Restore task to active board">
                                            <i class="fa-solid fa-rotate-left text-[9px]"></i>
                                            <span>Restore</span>
                                        </button>
                                    @endif
                                </div>
                            </div>
                        </div>
                    @empty
                        <div class="py-12 border-2 border-dashed border-slate-200/50 dark:border-slate-800 rounded-xl flex items-center justify-center text-slate-400 dark:text-slate-600">
                            <span class="text-xs">No tasks available</span>
                        </div>
                    @endforelse

                    @if(($statusCounts[$statusCode] ?? 0) > count($tasks[$statusCode]))
                        <div class="pt-2">
                            <button type="button" 
                                    wire:click="loadMoreStatus('{{ $statusCode }}')" 
                                    class="w-full py-2.5 px-3 text-xs font-semibold text-sky-700 bg-sky-50 hover:bg-sky-100 dark:bg-sky-950/40 dark:text-sky-300 border border-sky-200/40 dark:border-sky-800/40 rounded-xl transition-all duration-150 cursor-pointer shadow-sm">
                                <i class="fa-solid fa-angles-down mr-1"></i>
                                Load More (+{{ min(30, ($statusCounts[$statusCode] ?? 0) - count($tasks[$statusCode])) }} of {{ ($statusCounts[$statusCode] ?? 0) - count($tasks[$statusCode]) }} remaining)
                            </button>
                        </div>
                    @endif
                </div>
            </div>
        @endforeach
    </div>

    <!-- Livewire Modal CRUD -->
    @if($showModal)
        <div class="fixed inset-0 z-50 overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
            <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
                <!-- Overlay background -->
                <div class="fixed inset-0 bg-slate-950/60 backdrop-blur-sm transition-opacity" aria-hidden="true" wire:click="closeModal()"></div>

                <!-- Modal panel centering trick -->
                <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>

                <!-- Modal Panel -->
                <div class="inline-block align-bottom bg-white dark:bg-slate-900 rounded-2xl text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-4xl sm:w-full border border-slate-100 dark:border-slate-800">
                    <form wire:submit.prevent="saveTask">
                        <!-- Jira-style Breadcrumb Header -->
                        <div class="px-6 py-4 border-b border-slate-100 dark:border-slate-800/80 flex items-center justify-between bg-slate-50/20 dark:bg-slate-950/20">
                            <div class="flex items-center space-x-4">
                                <div class="flex flex-col space-y-0.5">
                                    <div class="flex items-center space-x-2 text-[10px] font-bold text-slate-400 dark:text-slate-500 uppercase tracking-wider">
                                        <span>Tasks</span>
                                        <span>/</span>
                                        @if($editingTaskId)
                                            @php
                                                $tempTask = \App\Models\Task::with('project.client')->find($editingTaskId);
                                            @endphp
                                            @if($tempTask && $tempTask->project)
                                                @if($tempTask->project->client)
                                                    <span class="text-indigo-650 dark:text-indigo-400 font-extrabold">{{ $tempTask->project->client->name }}</span>
                                                    <span>/</span>
                                                @endif
                                                <span>{{ $tempTask->project->name }}</span>
                                            @else
                                                <span class="text-slate-500">Global Tasks</span>
                                            @endif
                                            <span>/</span>
                                            <span class="text-slate-800 dark:text-slate-200 font-mono">TASK-{{ $editingTaskId }}</span>
                                        @else
                                            <span class="text-slate-500 font-bold">New Task</span>
                                        @endif
                                    </div>
                                    <h3 class="text-sm font-bold text-slate-800 dark:text-white font-outfit mt-0.5" id="modal-title">
                                        {{ $editingTaskId ? 'Task Details' : 'Create New Task' }}
                                    </h3>
                                </div>
                                <button type="submit" class="inline-flex items-center justify-center px-4 py-1.5 text-xs font-extrabold text-white bg-indigo-600 hover:bg-indigo-700 rounded-xl shadow-sm hover:-translate-y-0.5 transition-all duration-150">
                                    Save
                                </button>
                            </div>
                            <button type="button" wire:click="closeModal()" class="text-slate-400 hover:text-slate-600 dark:hover:text-slate-200 transition-colors focus:outline-none">
                                <svg class="h-5.5 w-5.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                                </svg>
                            </button>
                        </div>

                        @php
                            $modalTask = $editingTaskId ? \App\Models\Task::with(['creator', 'assignee', 'timeLogs.user'])->find($editingTaskId) : null;
                        @endphp

                        <div class="p-6">
                            <div class="grid grid-cols-1 lg:grid-cols-12 gap-6">
                                <!-- Left Column (Task details) -->
                                <div class="lg:col-span-8 space-y-6">
                                    <!-- Title -->
                                    <div>
                                        <label class="block text-[10px] font-bold uppercase tracking-wider text-slate-400 dark:text-slate-500 mb-1.5">Task Title <span class="text-rose-500">*</span></label>
                                        <input type="text" wire:model="taskTitle" placeholder="What needs to be done?" class="w-full px-4 py-2.5 bg-slate-50 dark:bg-slate-950 border border-slate-200 dark:border-slate-800 rounded-xl text-sm font-semibold text-slate-800 dark:text-slate-100 focus:outline-none focus:ring-2 focus:ring-sky-500/20 focus:border-sky-500 transition-all duration-150">
                                        @error('taskTitle') <span class="text-[10px] text-rose-500 mt-1 block">{{ $message }}</span> @enderror
                                    </div>
                                    <!-- Description -->
                                    <div>
                                        <div class="flex items-center justify-between mb-1.5">
                                            <label class="block text-[10px] font-bold uppercase tracking-wider text-slate-400 dark:text-slate-500">Description</label>
                                            @if(str_contains($taskDescription ?? '', '<div'))
                                                <span class="text-[10px] font-bold text-sky-600 dark:text-sky-400 bg-sky-50 dark:bg-sky-950/40 border border-sky-200/50 dark:border-sky-800/50 px-2 py-0.5 rounded-md">
                                                    <i class="fa-solid fa-sparkles mr-1"></i> Formatted Campaign Card
                                                </span>
                                            @endif
                                        </div>
                                        @if(str_contains($taskDescription ?? '', '<div'))
                                            <div class="p-4 bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-xl max-h-[420px] overflow-y-auto mb-3 shadow-inner">
                                                {!! $taskDescription !!}
                                            </div>
                                            <details class="text-[11px] text-slate-400">
                                                <summary class="cursor-pointer hover:text-slate-600 dark:hover:text-slate-200 font-semibold mb-1">Edit Raw HTML Code</summary>
                                                <textarea wire:model="taskDescription" rows="5" class="w-full px-4 py-3 bg-slate-50 dark:bg-slate-950 border border-slate-200 dark:border-slate-800 rounded-xl text-xs font-mono text-slate-800 dark:text-slate-100 focus:outline-none"></textarea>
                                            </details>
                                        @else
                                            <textarea wire:model="taskDescription" rows="6" placeholder="Describe the task in detail..." class="w-full px-4 py-3 bg-slate-50 dark:bg-slate-950 border border-slate-200 dark:border-slate-800 rounded-xl text-xs text-slate-800 dark:text-slate-100 focus:outline-none focus:ring-2 focus:ring-sky-500/20 focus:border-sky-500 transition-all duration-150"></textarea>
                                        @endif
                                        @error('taskDescription') <span class="text-[10px] text-rose-500 mt-1 block">{{ $message }}</span> @enderror
                                    </div>

                                    <!-- Document Upload Section -->
                                    @if(config('features.task_attachments', true))
                                    <div class="border-t border-slate-100 dark:border-slate-800/80 pt-5">
                                        <label class="block text-[10px] font-bold uppercase tracking-wider text-slate-400 dark:text-slate-500 mb-2.5">Documents and Attachments</label>
                                        
                                        <!-- Existing attachments -->
                                        @if(!empty($existingMedia))
                                            <div class="space-y-2 mb-4">
                                                @foreach($existingMedia as $mediaItem)
                                                    <div class="flex items-center justify-between p-2.5 bg-slate-50 dark:bg-slate-950/60 border border-slate-100 dark:border-slate-800/60 rounded-xl">
                                                        <div class="flex items-center space-x-2.5 overflow-hidden">
                                                            <svg class="h-4 w-4 text-slate-400 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                                                <path stroke-linecap="round" stroke-linejoin="round" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z" />
                                                            </svg>
                                                            <a href="{{ Storage::url($mediaItem['id'] . '/' . $mediaItem['file_name']) }}" target="_blank" class="text-xs font-medium text-indigo-600 hover:text-indigo-500 dark:text-indigo-400 dark:hover:text-indigo-300 truncate hover:underline" title="{{ $mediaItem['file_name'] }}">
                                                                {{ $mediaItem['file_name'] }}
                                                            </a>
                                                            <span class="text-[10px] text-slate-400">({{ number_format($mediaItem['size'] / 1024, 1) }} KB)</span>
                                                        </div>
                                                        <button type="button" wire:click="deleteAttachment({{ $mediaItem['id'] }})" class="p-1 rounded text-slate-400 hover:bg-rose-50 dark:hover:bg-rose-950/20 hover:text-rose-600 transition-colors" title="Delete file">
                                                            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                                                <path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                                            </svg>
                                                        </button>
                                                    </div>
                                                @endforeach
                                            </div>
                                        @endif

                                        <!-- Upload new files (with Drag & Drop + Paste support) -->
                                        <div x-data="{ isDragging: false }"
                                             @dragover.prevent="isDragging = true"
                                             @dragleave.prevent="isDragging = false"
                                             @drop.prevent="isDragging = false; $wire.uploadMultiple('attachments', $event.dataTransfer.files)"
                                             @paste.window="
                                                 if (document.activeElement.tagName !== 'TEXTAREA' && document.activeElement.tagName !== 'INPUT') {
                                                     let items = ($event.clipboardData || $event.originalEvent.clipboardData).items;
                                                     let files = [];
                                                     for (let index in items) {
                                                         let item = items[index];
                                                         if (item.kind === 'file') {
                                                             files.push(item.getAsFile());
                                                         }
                                                     }
                                                     if (files.length > 0) {
                                                         $wire.uploadMultiple('attachments', files);
                                                     }
                                                 }
                                             "
                                             :class="isDragging ? 'border-sky-500 bg-sky-500/10' : 'border-slate-200 dark:border-slate-800'"
                                             class="relative border-2 border-dashed rounded-xl p-4 text-center hover:bg-slate-50 dark:hover:bg-slate-950/40 transition-colors duration-150">
                                            <input type="file" wire:model="attachments" multiple class="absolute inset-0 w-full h-full opacity-0 cursor-pointer">
                                            <div class="space-y-1">
                                                <svg class="mx-auto h-8 w-8 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                                                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 16.5V9.75m0 0l3 3m-3-3l-3 3M6.75 19.5a4.5 4.5 0 01-1.41-8.775 5.25 5.25 0 0110.233-2.33 3 3 0 013.758 3.848A3.752 3.752 0 0118 19.5H6.75z" />
                                                </svg>
                                                <p class="text-xs font-semibold text-slate-600 dark:text-slate-400">Select files, drag them here, or paste (Ctrl+V)</p>
                                                <p class="text-[10px] text-slate-400">Allowed documents up to 10 MB</p>
                                            </div>
                                        </div>        
                                            <!-- Uploading indicator -->
                                            <div wire:loading wire:target="attachments" class="text-xs text-indigo-600 dark:text-indigo-400 mt-2 font-semibold">
                                                Uploading attachments...
                                            </div>
                                        @error('attachments.*') <span class="text-[10px] text-rose-500 mt-1 block">{{ $message }}</span> @enderror
                                    </div>
                                    @endif

                                    <!-- Activity Section (Jira Style Tabs) -->
                                    @if(config('features.task_comments', true) || config('features.task_time_logs', true) || config('features.task_history', true))
                                    <div class="border-t border-slate-100 dark:border-slate-800/80 pt-6 mt-6" x-data="{ activeTab: '{{ config('features.task_comments', true) ? 'comments' : (config('features.task_time_logs', true) ? 'work-logs' : 'history') }}' }">
                                        <div class="flex items-center justify-between mb-4">
                                            <h4 class="text-xs font-bold uppercase tracking-wider text-slate-400 dark:text-slate-500">Activity</h4>
                                            
                                            <!-- Tab Headers -->
                                            <div class="flex items-center space-x-1 bg-slate-100 dark:bg-slate-950 p-1 rounded-xl border border-slate-200/40 dark:border-slate-800/60 shadow-inner">
                                                @if($modalTask && $modalTask->supportTicket)
                                                <button type="button" @click="activeTab = 'email-client'" :class="activeTab === 'email-client' ? 'bg-white dark:bg-slate-900 text-sky-600 dark:text-sky-400 shadow-sm border border-slate-200/40 dark:border-slate-800/40' : 'text-slate-500 hover:text-slate-700 dark:hover:text-slate-300 border border-transparent'" class="px-3 py-1.5 rounded-lg text-xs font-bold transition-all duration-150 focus:outline-none flex items-center gap-1.5">
                                                    <i class="fa-solid fa-paper-plane text-sky-500"></i> Reply to Client
                                                </button>
                                                @endif
                                                @if(config('features.task_comments', true))
                                                <button type="button" @click="activeTab = 'comments'" :class="activeTab === 'comments' ? 'bg-white dark:bg-slate-900 text-indigo-600 dark:text-indigo-400 shadow-sm border border-slate-200/40 dark:border-slate-800/40' : 'text-slate-500 hover:text-slate-700 dark:hover:text-slate-300 border border-transparent'" class="px-3 py-1.5 rounded-lg text-xs font-bold transition-all duration-150 focus:outline-none">
                                                    Comments
                                                </button>
                                                @endif
                                                @if(config('features.task_time_logs', true))
                                                <button type="button" @click="activeTab = 'work-logs'" :class="activeTab === 'work-logs' ? 'bg-white dark:bg-slate-900 text-indigo-600 dark:text-indigo-400 shadow-sm border border-slate-200/40 dark:border-slate-800/40' : 'text-slate-500 hover:text-slate-700 dark:hover:text-slate-300 border border-transparent'" class="px-3 py-1.5 rounded-lg text-xs font-bold transition-all duration-150 focus:outline-none">
                                                    Work Logs & Time Tracker
                                                </button>
                                                @endif
                                                @if(config('features.task_history', true))
                                                <button type="button" @click="activeTab = 'history'" :class="activeTab === 'history' ? 'bg-white dark:bg-slate-900 text-indigo-600 dark:text-indigo-400 shadow-sm border border-slate-200/40 dark:border-slate-800/40' : 'text-slate-500 hover:text-slate-700 dark:hover:text-slate-300 border border-transparent'" class="px-3 py-1.5 rounded-lg text-xs font-bold transition-all duration-150 focus:outline-none">
                                                    History
                                                </button>
                                                @endif
                                            </div>
                                        </div>

                                        <!-- Tab 4: Email Client -->
                                        @if($modalTask && $modalTask->supportTicket)
                                        <div x-show="activeTab === 'email-client'" x-transition class="space-y-4">
                                            @php
                                                $ticket = $modalTask->supportTicket;
                                                $firstMsg = $ticket->messages()->orderBy('id', 'asc')->first();
                                                $supportToEmail = $firstMsg ? $firstMsg->to : 'info@sivora.co.uk';
                                            @endphp

                                            <!-- Recipient & Sender Info Box -->
                                            <div class="bg-sky-50/60 dark:bg-sky-950/20 border border-sky-200/60 dark:border-sky-800/50 rounded-2xl p-4 space-y-2">
                                                <div class="flex flex-wrap items-center justify-between gap-2 text-xs">
                                                    <div class="flex items-center gap-2">
                                                        <span class="font-bold text-slate-500 dark:text-slate-400">To Client:</span>
                                                        <span class="font-semibold text-sky-700 dark:text-sky-300 bg-white dark:bg-slate-900 px-2.5 py-1 rounded-lg border border-sky-200/60 dark:border-sky-800/40">
                                                            <i class="fa-solid fa-user-circle mr-1"></i> {{ $ticket->customer_name }} <{{ $ticket->customer_email }}>
                                                        </span>
                                                    </div>
                                                    <div class="flex items-center gap-2">
                                                        <span class="font-bold text-slate-500 dark:text-slate-400">Support Mail:</span>
                                                        <span class="font-semibold text-slate-700 dark:text-slate-300 bg-white dark:bg-slate-900 px-2.5 py-1 rounded-lg border border-sky-200/60 dark:border-sky-800">
                                                            <i class="fa-solid fa-paper-plane text-sky-500 mr-1"></i> {{ $supportToEmail }}
                                                        </span>
                                                    </div>
                                                </div>
                                                @if($ticket->subject)
                                                    <div class="text-xs text-slate-600 dark:text-slate-400 pt-1">
                                                        <strong>Subject:</strong> Re: {{ preg_replace('/^Re:\s*/i', '', $ticket->subject) }}
                                                    </div>
                                                @endif
                                            </div>

                                            <!-- Reply Form -->
                                            <div class="space-y-3 bg-white dark:bg-slate-900 p-4 rounded-2xl border border-slate-200/60 dark:border-slate-800 shadow-sm">
                                                <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2">
                                                    <label class="block text-xs font-bold text-slate-700 dark:text-slate-200">Compose Reply Message</label>
                                                    @php
                                                        $templates = \App\Models\EmailTemplate::orderBy('name')->get();
                                                    @endphp
                                                    @if($templates->count() > 0)
                                                        <div class="flex items-center gap-2">
                                                            <span class="text-[11px] text-slate-400 font-medium"><i class="fa-solid fa-file-invoice text-sky-500"></i> Template:</span>
                                                            <select wire:model.live="selectedEmailTemplateId" wire:change="applyEmailTemplate" class="px-2.5 py-1 bg-slate-50 dark:bg-slate-950 border border-slate-200 dark:border-slate-800 rounded-lg text-xs font-semibold text-slate-700 dark:text-slate-200 focus:outline-none focus:ring-2 focus:ring-sky-500/20">
                                                                <option value="">-- Select Canned Template --</option>
                                                                @foreach($templates as $tmpl)
                                                                    <option value="{{ $tmpl->id }}">{{ $tmpl->name }}</option>
                                                                @endforeach
                                                            </select>
                                                        </div>
                                                    @endif
                                                </div>
                                                <textarea wire:model="emailReplyBody" rows="5" placeholder="Type your response to the client here or select a canned template above..." class="w-full px-3.5 py-2.5 bg-slate-50 dark:bg-slate-950 border border-slate-200 dark:border-slate-800 rounded-xl text-xs text-slate-800 dark:text-slate-100 focus:outline-none focus:ring-2 focus:ring-sky-500/20 focus:border-sky-500 transition-all resize-none"></textarea>
                                                @error('emailReplyBody') <span class="text-xs text-rose-500 block">{{ $message }}</span> @enderror

                                                <div class="flex justify-end pt-1">
                                                    <button type="button" wire:click="sendClientEmailReply" wire:loading.attr="disabled" class="inline-flex items-center gap-2 px-5 py-2 bg-sky-600 hover:bg-sky-500 disabled:opacity-50 text-white font-bold rounded-xl text-xs transition-all shadow-sm cursor-pointer">
                                                        <i class="fa-solid fa-paper-plane"></i>
                                                        <span wire:loading.remove wire:target="sendClientEmailReply">Send Reply to Client</span>
                                                        <span wire:loading wire:target="sendClientEmailReply">Sending Email...</span>
                                                    </button>
                                                </div>
                                            </div>

                                            <!-- Email Thread History -->
                                            @if($ticket->messages()->exists())
                                                <div class="space-y-3 pt-2">
                                                    <h5 class="text-xs font-bold uppercase tracking-wider text-slate-400 dark:text-slate-500">Email Conversation History</h5>
                                                    <div class="space-y-3 max-h-[300px] overflow-y-auto pr-1">
                                                        @foreach($ticket->messages()->orderBy('created_at', 'desc')->get() as $msg)
                                                            <div class="p-3.5 rounded-2xl border text-xs {{ $msg->is_outgoing ? 'bg-sky-50/40 dark:bg-sky-950/20 border-sky-200/50 dark:border-sky-800/40 ml-4' : 'bg-slate-50 dark:bg-slate-950/40 border-slate-200/50 dark:border-slate-800/40 mr-4' }}">
                                                                <div class="flex items-center justify-between mb-2">
                                                                    <span class="font-bold {{ $msg->is_outgoing ? 'text-sky-700 dark:text-sky-400' : 'text-slate-800 dark:text-slate-200' }}">
                                                                        {{ $msg->is_outgoing ? '📤 Sent Reply' : '📥 Client Email' }} ({{ $msg->from }})
                                                                    </span>
                                                                    <span class="text-[10px] text-slate-400">{{ $msg->sent_at ? $msg->sent_at->format('d M Y H:i') : $msg->created_at->format('d M Y H:i') }}</span>
                                                                </div>
                                                                <div class="text-slate-600 dark:text-slate-300 whitespace-pre-line text-xs font-mono">
                                                                    {{ Str::limit(strip_tags($msg->body_text), 300) }}
                                                                </div>
                                                            </div>
                                                        @endforeach
                                                    </div>
                                                </div>
                                            @endif
                                        </div>
                                        @endif

                                        <!-- Tab 3: Comments Content -->
                                        @if(config('features.task_comments', true))
                                        <div x-show="activeTab === 'comments'" x-transition class="space-y-4">
                                            @if($modalTask)
                                                <!-- Add comment area -->
                                                <div class="space-y-3 bg-slate-50/50 dark:bg-slate-950/20 p-4 rounded-2xl border border-slate-100 dark:border-slate-800/40">
                                                    <x-comment-input
                                                        wire-model="newCommentContent"
                                                        :users="$users->map(fn($u) => ['name' => $u->name, 'username' => $u->telegram_username ?: preg_replace('/[^a-zA-Z0-9_]/', '', str_replace(' ', '_', $u->name)), 'type' => 'User'])->concat($clients->map(fn($c) => ['name' => $c->name, 'username' => preg_replace('/[^a-zA-Z0-9_]/', '', str_replace(' ', '_', $c->name)) . '_client', 'type' => 'Client']))->values()"
                                                        submit-action="$wire.addComment()"
                                                        class="w-full px-3 py-2 bg-white dark:bg-slate-950 border border-slate-200 dark:border-slate-800 rounded-xl text-xs text-slate-805 dark:text-slate-100 focus:outline-none focus:ring-2 focus:ring-sky-500/20 focus:border-sky-500 transition-all duration-150"
                                                    />
                                                    
                                                    <div class="flex items-center justify-between">
                                                        <!-- Private setting -->
                                                        <label class="inline-flex items-center text-[10px] font-bold uppercase tracking-wider text-slate-400 dark:text-slate-500 cursor-pointer">
                                                            <input type="checkbox" wire:model="newCommentIsPrivate" class="rounded dark:bg-gray-900 border-gray-300 dark:border-gray-700 text-indigo-600 shadow-sm focus:ring-indigo-500 mr-2">
                                                            🔒 Private (team only)
                                                        </label>
                                                        <button type="button" wire:click="addComment" class="px-4 py-1.5 bg-indigo-600 hover:bg-indigo-700 text-white font-bold rounded-xl text-xs transition-all duration-150 shadow-sm">
                                                            Comment
                                                        </button>
                                                    </div>
                                                </div>

                                                <!-- Comments Controls (Sorting & System Filter) -->
                                                <div class="flex items-center justify-between pt-2 text-xs">
                                                    <div class="flex items-center gap-2">
                                                        <button type="button" wire:click="$toggle('hideSystemComments')" class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-lg text-[11px] font-semibold transition-all cursor-pointer {{ $hideSystemComments ? 'bg-amber-100 text-amber-800 dark:bg-amber-950/60 dark:text-amber-300 border border-amber-200 dark:border-amber-900/50' : 'bg-slate-100 text-slate-600 dark:bg-slate-800 dark:text-slate-400 hover:bg-slate-200' }}">
                                                            <i class="fa-solid {{ $hideSystemComments ? 'fa-eye-slash text-amber-500' : 'fa-eye text-slate-400' }}"></i>
                                                            {{ $hideSystemComments ? 'System Messages Hidden' : 'Hide System Messages' }}
                                                        </button>
                                                    </div>

                                                    <button type="button" wire:click="$set('commentSortOrder', '{{ $commentSortOrder === 'asc' ? 'desc' : 'asc' }}')" class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-lg text-[11px] font-semibold bg-slate-100 text-slate-600 dark:bg-slate-800 dark:text-slate-400 hover:bg-slate-200 transition-all cursor-pointer">
                                                        <i class="fa-solid {{ $commentSortOrder === 'asc' ? 'fa-arrow-up-short-wide text-sky-500' : 'fa-arrow-down-wide-short text-sky-500' }}"></i>
                                                        {{ $commentSortOrder === 'asc' ? 'Oldest First' : 'Newest First' }}
                                                    </button>
                                                </div>

                                                <!-- Comments List -->
                                                <div class="space-y-4 max-h-[400px] overflow-y-auto pr-1 mt-3">
                                                    @forelse($this->modalComments as $comment)
                                                        <div class="bg-white dark:bg-slate-900 p-4.5 rounded-2xl border border-slate-200/50 dark:border-slate-800/60 shadow-sm relative group">
                                                            <x-comment-bubble :comment="$comment" avatar-size="6" />

                                                            <!-- Replies list -->
                                                            <div class="mt-3.5 pl-4 border-l-2 border-slate-100 dark:border-slate-800 space-y-3.5">
                                                                @foreach($comment->replies as $reply)
                                                                    <div class="relative group/reply">
                                                                        <x-comment-bubble :comment="$reply" avatar-size="5" avatar-text-size="9px" />
                                                                    </div>
                                                                @endforeach

                                                                <!-- Form to reply -->
                                                                <div x-data="{ showReplyForm: false }" class="mt-2">
                                                                    <button type="button" @click="showReplyForm = !showReplyForm" class="text-[10px] font-bold text-indigo-500 hover:text-indigo-600 dark:text-indigo-400 dark:hover:text-indigo-300 transition-all">
                                                                        <i class="fa-solid fa-reply mr-1"></i> Reply
                                                                    </button>

                                                                    <div x-show="showReplyForm" x-transition class="mt-2 space-y-2">
                                                                        <x-comment-input
                                                                            wire-model="replyCommentContent.{{ $comment->id }}"
                                                                            :users="$users->map(fn($u) => ['name' => $u->name, 'username' => $u->telegram_username ?: preg_replace('/[^a-zA-Z0-9_]/', '', str_replace(' ', '_', $u->name)), 'type' => 'User'])->concat($clients->map(fn($c) => ['name' => $c->name, 'username' => preg_replace('/[^a-zA-Z0-9_]/', '', str_replace(' ', '_', $c->name)) . '_client', 'type' => 'Client']))->values()"
                                                                            submit-action="$wire.addReply({{ $comment->id }}); showReplyForm = false"
                                                                            :rows="2"
                                                                            placeholder="Write a reply..."
                                                                            :show-toolbar="false"
                                                                            class="w-full px-3 py-1.5 bg-slate-50 dark:bg-slate-950 border border-slate-200 dark:border-slate-800 rounded-xl text-xs text-slate-800 dark:text-slate-100 focus:outline-none focus:ring-2 focus:ring-sky-500/20 focus:border-sky-500 transition-all duration-150"
                                                                        />
                                                                        <div class="flex justify-end">
                                                                            <button type="button" wire:click="addReply({{ $comment->id }})" @click="showReplyForm = false" class="px-3 py-1 bg-indigo-600 hover:bg-indigo-700 text-white font-bold rounded-lg text-[10px] transition-all duration-150">
                                                                                Submit Reply
                                                                            </button>
                                                                        </div>
                                                                    </div>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    @empty
                                                        <div class="text-center py-8 border-2 border-dashed border-slate-100 dark:border-slate-800 rounded-xl text-slate-400 dark:text-slate-600">
                                                            <i class="fa-regular fa-comments text-2xl text-slate-300 dark:text-slate-700"></i>
                                                            <p class="text-xs font-semibold mt-2">No comments yet</p>
                                                            <p class="text-[10px] text-slate-400 mt-0.5">Start the conversation by adding a comment above.</p>
                                                        </div>
                                                    @endforelse
                                                </div>
                                            @else
                                                <div class="text-xs text-slate-400 italic text-center py-4">Save the task first to read/write comments.</div>
                                            @endif
                                        </div>
                                        @endif

                                        <!-- Tab 1: Work Logs Content -->
                                        @if(config('features.task_time_logs', true))
                                        <div x-show="activeTab === 'work-logs'" x-transition class="space-y-5">
                                            @if($modalTask)
                                                @php
                                                    $userBreakdown = $modalTask->getDurationByUser();
                                                    $totalSeconds = $modalTask->total_duration;
                                                @endphp

                                                <!-- Breakdown Grouped By User -->
                                                <div class="bg-slate-50/50 dark:bg-slate-950/20 border border-slate-200/60 dark:border-slate-800/60 rounded-xl p-4 space-y-3 shadow-sm">
                                                    <h5 class="text-xs font-bold text-slate-700 dark:text-slate-300 flex items-center justify-between border-b border-slate-200/40 dark:border-slate-800/40 pb-2">
                                                        <span>Time Logged by Member</span>
                                                        <span class="text-[10px] font-mono text-slate-400 dark:text-slate-500 bg-slate-100 dark:bg-slate-900 px-2 py-0.5 rounded border border-slate-200/40 dark:border-slate-800/40">Total: {{ $modalTask->human_formatted_duration }}</span>
                                                    </h5>
                                                    
                                                    <div class="space-y-3">
                                                        @forelse($userBreakdown as $breakdown)
                                                            @php
                                                                $percent = $totalSeconds > 0 ? ($breakdown['duration'] / $totalSeconds) * 100 : 0;
                                                            @endphp
                                                            <div class="space-y-1">
                                                                <div class="flex items-center justify-between text-xs font-semibold">
                                                                    <div class="flex items-center space-x-2">
                                                                        <div class="h-5 w-5 rounded-full bg-indigo-50 dark:bg-indigo-950 border border-indigo-200/40 dark:border-indigo-900/30 flex items-center justify-center font-extrabold text-indigo-600 dark:text-indigo-400 text-[8px] uppercase">
                                                                            {{ substr($breakdown['user']->name, 0, 2) }}
                                                                        </div>
                                                                        <span class="text-slate-700 dark:text-slate-300">{{ $breakdown['user']->name }}</span>
                                                                    </div>
                                                                    <span class="font-mono text-slate-800 dark:text-slate-200 font-bold bg-slate-100 dark:bg-slate-900 px-2 py-0.5 rounded border border-slate-200/20 dark:border-slate-800/20">
                                                                        {{ $breakdown['formatted'] }}
                                                                    </span>
                                                                </div>
                                                                <!-- Visual Progress Bar -->
                                                                <div class="w-full bg-slate-200 dark:bg-slate-800 h-2 rounded-full overflow-hidden">
                                                                    <div class="bg-indigo-500 dark:bg-indigo-600 h-full rounded-full transition-all duration-300" style="width: {{ $percent }}%"></div>
                                                                </div>
                                                            </div>
                                                        @empty
                                                            <div class="text-xs text-slate-400 italic py-2 text-center">No time has been tracked on this task yet.</div>
                                                        @endforelse
                                                    </div>
                                                </div>

                                                <!-- Session History list -->
                                                @if(config('features.session_log_history', true))
                                                <div class="space-y-2" x-data="{ showAllLogs: false }">
                                                    <h5 class="text-xs font-bold text-slate-700 dark:text-slate-300">Work Session Log History</h5>
                                                    <div class="max-h-[300px] overflow-y-auto pr-1 space-y-2">
                                                        @forelse($modalTask->timeLogs()->with('user')->orderBy('started_at', 'desc')->get() as $index => $log)
                                                            <div x-show="showAllLogs || {{ $index }} < 3" class="flex items-center justify-between p-3 bg-slate-50 dark:bg-slate-950/40 border border-slate-200/40 dark:border-slate-800/40 rounded-xl shadow-sm hover:border-slate-300 dark:hover:border-slate-700 transition-colors">
                                                                <div class="flex items-center space-x-3">
                                                                    <div class="h-6.5 w-6.5 rounded-full bg-gradient-to-br from-indigo-500 to-sky-500 flex items-center justify-center font-extrabold text-white text-[9px] uppercase">
                                                                        {{ substr($log->user->name, 0, 2) }}
                                                                    </div>
                                                                    <div class="space-y-0.5">
                                                                        <div class="text-xs font-bold text-slate-800 dark:text-slate-200">{{ $log->user->name }}</div>
                                                                        <div class="text-[10px] text-slate-400 dark:text-slate-500">
                                                                            {{ $log->started_at->format('d.m.Y H:i') }}
                                                                            @if($log->stopped_at) 
                                                                                - {{ $log->stopped_at->format('H:i') }} 
                                                                            @else 
                                                                                (Active Session) 
                                                                            @endif
                                                                        </div>
                                                                    </div>
                                                                </div>
                                                                <div class="flex items-center space-x-2">
                                                                    @if(!$log->stopped_at)
                                                                        <span class="inline-flex items-center px-1.5 py-0.5 rounded text-[8px] font-bold uppercase tracking-wider bg-emerald-50 dark:bg-emerald-950/30 text-emerald-600 dark:text-emerald-400 border border-emerald-100 dark:border-emerald-900/30 animate-pulse">Running</span>
                                                                    @endif
                                                                    <span class="font-mono font-bold text-xs text-indigo-600 dark:text-indigo-400 bg-indigo-50 dark:bg-indigo-950/40 border border-indigo-100/40 dark:border-indigo-900/30 px-2 py-1 rounded-lg">
                                                                        @if($log->duration_seconds !== null)
                                                                            {{ sprintf('%02d:%02d:%02d', floor($log->duration_seconds / 3600), floor(($log->duration_seconds / 60) % 60), $log->duration_seconds % 60) }}
                                                                        @else
                                                                            @php
                                                                                $currentActiveSeconds = $log->started_at->diffInSeconds(now(), true);
                                                                            @endphp
                                                                            {{ sprintf('%02d:%02d:%02d', floor($currentActiveSeconds / 3600), floor(($currentActiveSeconds / 60) % 60), $currentActiveSeconds % 60) }}
                                                                        @endif
                                                                    </span>
                                                                </div>
                                                            </div>
                                                        @empty
                                                            <div class="text-xs text-slate-400 italic py-6 text-center border-2 border-dashed border-slate-100 dark:border-slate-800 rounded-xl">No work sessions logged yet.</div>
                                                        @endforelse
                                                    </div>

                                                    @if($modalTask->timeLogs->count() > 3)
                                                        <div class="flex justify-center mt-2.5">
                                                            <button type="button" @click="showAllLogs = !showAllLogs" class="inline-flex items-center justify-center px-3.5 py-1.5 bg-slate-50 hover:bg-slate-100 dark:bg-slate-950 dark:hover:bg-slate-900 text-indigo-600 hover:text-indigo-800 dark:text-indigo-400 dark:hover:text-indigo-300 border border-slate-200 dark:border-slate-800 rounded-xl text-xs font-bold transition-all shadow-sm">
                                                                <span x-show="!showAllLogs">Show all logs (+{{ $modalTask->timeLogs->count() - 3 }})</span>
                                                                <span x-show="showAllLogs">Show less</span>
                                                            </button>
                                                        </div>
                                                    @endif
                                                </div>
                                                @endif
                                            @else
                                                <div class="text-xs text-slate-400 italic text-center py-4">Save the task first to track time.</div>
                                            @endif
                                        </div>
                                        @endif

                                        <!-- Tab 2: History Content -->
                                        @if(config('features.task_history', true))
                                        <div x-show="activeTab === 'history'" x-transition class="space-y-4">
                                            @if($modalTask && $modalTask->activityLogs()->exists())
                                                <div class="flow-root max-h-[350px] overflow-y-auto pr-1">
                                                    <ul role="list" class="-mb-8">
                                                        @foreach($modalTask->activityLogs()->with(['user', 'client'])->latest()->get() as $log)
                                                            <li>
                                                                <div class="relative pb-8">
                                                                    <!-- Line connecting timelines -->
                                                                    @if(!$loop->last)
                                                                        <span class="absolute top-4 left-4 -ml-px h-full w-0.5 bg-slate-200 dark:bg-slate-800/60" aria-hidden="true"></span>
                                                                    @endif
                                                                    <div class="relative flex space-x-3 text-xs">
                                                                        <div>
                                                                            <span class="h-8 w-8 rounded-full flex items-center justify-center ring-8 ring-white dark:ring-slate-900 bg-slate-50 dark:bg-slate-950 border border-slate-200 dark:border-slate-800 text-slate-500 dark:text-slate-400">
                                                                                @if($log->action === 'task_created')
                                                                                    <i class="fa-solid fa-plus text-sky-500"></i>
                                                                                @elseif($log->action === 'task_status_updated')
                                                                                    <i class="fa-solid fa-arrow-right-arrow-left text-indigo-500"></i>
                                                                                @elseif($log->action === 'task_claimed' || $log->action === 'task_assigned')
                                                                                    <i class="fa-solid fa-user-check text-emerald-500"></i>
                                                                                @elseif($log->action === 'task_unassigned')
                                                                                    <i class="fa-solid fa-user-xmark text-rose-500"></i>
                                                                                @elseif($log->action === 'timer_started')
                                                                                    <i class="fa-solid fa-play text-emerald-500"></i>
                                                                                @elseif($log->action === 'timer_stopped')
                                                                                    <i class="fa-solid fa-stop text-rose-500"></i>
                                                                                @elseif($log->action === 'client_portal_task_created')
                                                                                    <i class="fa-solid fa-globe text-teal-500"></i>
                                                                                @else
                                                                                    <i class="fa-solid fa-circle-info text-slate-400"></i>
                                                                                @endif
                                                                            </span>
                                                                        </div>
                                                                        <div class="flex-1 min-w-0 pt-1.5 flex justify-between space-x-4">
                                                                            <div>
                                                                                <p class="text-xs text-slate-600 dark:text-slate-300">
                                                                                    {{ $log->description }}
                                                                                </p>
                                                                            </div>
                                                                            <div class="text-right whitespace-nowrap text-[10px] text-slate-400 dark:text-slate-500 pt-0.5">
                                                                                <time datetime="{{ $log->created_at->toIso8601String() }}">
                                                                                    {{ $log->created_at->diffForHumans() }}
                                                                                </time>
                                                                            </div>
                                                                        </div>
                                                                    </div>
                                                                </div>
                                                            </li>
                                                        @endforeach
                                                    </ul>
                                                </div>
                                            @else
                                                <div class="text-center py-8 border-2 border-dashed border-slate-100 dark:border-slate-800 rounded-xl text-slate-400 dark:text-slate-600">
                                                    <svg class="mx-auto h-8 w-8 text-slate-300 dark:text-slate-700" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                                                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 11-18 0 9 9 0 0118 0z" />
                                                    </svg>
                                                    <p class="text-xs font-semibold mt-2">Activity history is empty</p>
                                                    <p class="text-[10px] text-slate-400 mt-0.5">Task lifecycle events and modifications will be listed here.</p>
                                                </div>
                                            @endif
                                        </div>
                                        @endif
                                    </div>
                                    @endif
                                </div>

                                <!-- Right Column (Attributes Sidebar) -->
                                <div class="lg:col-span-4 space-y-4">
                                    <div class="bg-slate-50/50 dark:bg-slate-950/20 p-4.5 rounded-2xl border border-slate-100 dark:border-slate-800/40 space-y-4">
                                        <!-- Status -->
                                        <div>
                                            <label class="block text-[10px] font-bold uppercase tracking-wider text-slate-400 dark:text-slate-500 mb-1.5">Status</label>
                                            <select wire:model="taskStatus" class="w-full px-3 py-1.5 bg-slate-50 dark:bg-slate-950 border border-slate-200 dark:border-slate-800 rounded-xl text-xs text-slate-800 dark:text-slate-100 focus:outline-none focus:ring-2 focus:ring-sky-500/20 focus:border-sky-500 transition-all duration-150">
                                                <option value="email_inbox">📨 Email Requests</option>
                                                <option value="todo">To Do</option>
                                                <option value="in_progress">In Progress</option>
                                                <option value="review">Review</option>
                                                <option value="done">Done</option>
                                            </select>
                                            @error('taskStatus') <span class="text-[10px] text-rose-500 mt-1 block">{{ $message }}</span> @enderror
                                        </div>

                                        <!-- Priority -->
                                        <div>
                                            <label class="block text-[10px] font-bold uppercase tracking-wider text-slate-400 dark:text-slate-500 mb-1.5">Priority</label>
                                            <select wire:model="taskPriority" class="w-full px-3 py-1.5 bg-slate-50 dark:bg-slate-950 border border-slate-200 dark:border-slate-800 rounded-xl text-xs text-slate-800 dark:text-slate-100 focus:outline-none focus:ring-2 focus:ring-sky-500/20 focus:border-sky-500 transition-all duration-150">
                                                <option value="low">🟢 Low</option>
                                                <option value="medium">🔵 Medium</option>
                                                <option value="high">🟠 High</option>
                                                <option value="critical">🔴 Critical</option>
                                            </select>
                                            @error('taskPriority') <span class="text-[10px] text-rose-500 mt-1 block">{{ $message }}</span> @enderror
                                        </div>

                                        <!-- Client / Company -->
                                        <div>
                                            <label class="block text-[10px] font-bold uppercase tracking-wider text-slate-400 dark:text-slate-500 mb-1.5">Client & Company</label>

                                            @php
                                                $projectsOptions = $projects->map(function ($p) {
                                                    return [
                                                        'id' => (string) $p->id,
                                                        'name' => $p->name,
                                                        'client_name' => $p->client ? $p->client->name : 'No Client',
                                                    ];
                                                })->values();
                                            @endphp

                                            <div x-data="{
                                                open: false,
                                                search: '',
                                                selectedId: @entangle('taskProject').live,
                                                options: {{ json_encode($projectsOptions) }},
                                                get selectedItem() {
                                                    return this.options.find(o => String(o.id) === String(this.selectedId)) || null;
                                                },
                                                get filteredOptions() {
                                                    if (!this.search.trim()) return this.options;
                                                    const q = this.search.toLowerCase();
                                                    return this.options.filter(o =>
                                                        o.name.toLowerCase().includes(q) ||
                                                        o.client_name.toLowerCase().includes(q)
                                                    );
                                                },
                                                get groupedOptions() {
                                                    const groups = {};
                                                    this.filteredOptions.forEach(o => {
                                                        if (!groups[o.client_name]) groups[o.client_name] = [];
                                                        groups[o.client_name].push(o);
                                                    });
                                                    return groups;
                                                },
                                                select(id) {
                                                    this.selectedId = id ? String(id) : '';
                                                    $wire.set('taskProject', this.selectedId);
                                                    this.open = false;
                                                    this.search = '';
                                                }
                                            }" class="relative">

                                                <!-- Trigger Button -->
                                                <button type="button" @click="open = !open; if (open) $nextTick(() => $refs.searchInput.focus())"
                                                        class="w-full px-3 py-2 bg-slate-50 dark:bg-slate-950 border border-slate-200 dark:border-slate-800 rounded-xl text-xs text-left flex items-center justify-between focus:outline-none focus:ring-2 focus:ring-sky-500/20 focus:border-sky-500 transition-all shadow-sm cursor-pointer">
                                                    <template x-if="selectedItem">
                                                        <div class="flex items-center gap-1.5 truncate">
                                                            <span class="p-1 rounded bg-indigo-50 dark:bg-indigo-950/60 text-indigo-600 dark:text-indigo-400 font-bold text-[10px] uppercase">
                                                                <i class="fa-solid fa-building"></i>
                                                            </span>
                                                            <span class="font-bold text-slate-800 dark:text-slate-100" x-text="selectedItem.name"></span>
                                                            <span class="text-slate-400 dark:text-slate-500 text-[10px]" x-text="'(' + selectedItem.client_name + ')'"></span>
                                                        </div>
                                                    </template>
                                                    <template x-if="!selectedItem">
                                                        <span class="text-slate-400 dark:text-slate-500 font-medium">Unlinked (Global Task)</span>
                                                    </template>

                                                    <div class="flex items-center gap-1.5 ml-2 flex-shrink-0">
                                                        <template x-if="selectedId">
                                                            <span @click.stop="select('')" title="Clear" class="text-slate-400 hover:text-rose-500 p-0.5 rounded transition-colors cursor-pointer">
                                                                <i class="fa-solid fa-xmark text-xs"></i>
                                                            </span>
                                                        </template>
                                                        <i class="fa-solid fa-chevron-down text-[10px] text-slate-400 transition-transform" :class="{ 'rotate-180': open }"></i>
                                                    </div>
                                                </button>

                                                <!-- Dropdown Menu -->
                                                <div x-show="open" @click.outside="open = false" x-transition:enter="transition ease-out duration-150" x-transition:enter-start="opacity-0 scale-95" x-transition:enter-end="opacity-100 scale-100"
                                                     class="absolute z-50 left-0 right-0 mt-1 bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl shadow-xl p-2.5 max-h-72 overflow-hidden flex flex-col">

                                                    <!-- Live Search Input -->
                                                    <div class="relative mb-2 flex-shrink-0">
                                                        <i class="fa-solid fa-magnifying-glass absolute left-3 top-1/2 -translate-y-1/2 text-slate-400 text-xs"></i>
                                                        <input x-ref="searchInput" type="text" x-model="search" placeholder="Type to search company or client..."
                                                               class="w-full pl-8 pr-3 py-1.5 bg-slate-50 dark:bg-slate-950 border border-slate-200 dark:border-slate-800 rounded-xl text-xs text-slate-800 dark:text-slate-100 focus:outline-none focus:ring-2 focus:ring-sky-500/20 focus:border-sky-500">
                                                    </div>

                                                    <!-- Options List -->
                                                    <div class="overflow-y-auto custom-scroll flex-1 space-y-2 pr-1">
                                                        <!-- Unlinked option -->
                                                        <button type="button" @click="select('')"
                                                                class="w-full text-left px-2.5 py-1.5 rounded-xl text-xs transition-colors flex items-center justify-between cursor-pointer"
                                                                :class="!selectedId ? 'bg-indigo-50 dark:bg-indigo-950/40 text-indigo-600 dark:text-indigo-400 font-bold' : 'hover:bg-slate-50 dark:hover:bg-slate-800/60 text-slate-600 dark:text-slate-300'">
                                                            <span>Unlinked (Global Task)</span>
                                                            <template x-if="!selectedId">
                                                                <i class="fa-solid fa-check text-xs text-indigo-500"></i>
                                                            </template>
                                                        </button>

                                                        <!-- Grouped Options -->
                                                        <template x-for="(groupItems, clientName) in groupedOptions" :key="clientName">
                                                            <div class="space-y-1">
                                                                <div class="px-2 pt-1.5 text-[9px] font-extrabold uppercase tracking-wider text-indigo-500 dark:text-indigo-400 flex items-center gap-1">
                                                                    <i class="fa-solid fa-user-tie text-[9px]"></i>
                                                                    <span x-text="clientName"></span>
                                                                </div>
                                                                <template x-for="item in groupItems" :key="item.id">
                                                                    <button type="button" @click="select(item.id)"
                                                                            class="w-full text-left px-3 py-1.5 rounded-xl text-xs transition-colors flex items-center justify-between cursor-pointer"
                                                                            :class="String(selectedId) === String(item.id) ? 'bg-sky-50 dark:bg-sky-950/50 text-sky-600 dark:text-sky-400 font-bold border border-sky-200/50 dark:border-sky-800/50' : 'hover:bg-slate-50 dark:hover:bg-slate-800/60 text-slate-700 dark:text-slate-200'">
                                                                        <div class="flex items-center gap-2 truncate">
                                                                            <i class="fa-solid fa-building text-[10px] text-slate-400"></i>
                                                                            <span x-text="item.name"></span>
                                                                        </div>
                                                                        <template x-if="String(selectedId) === String(item.id)">
                                                                            <i class="fa-solid fa-check text-xs text-sky-500 ml-2"></i>
                                                                        </template>
                                                                    </button>
                                                                </template>
                                                            </div>
                                                        </template>

                                                        <template x-if="Object.keys(groupedOptions).length === 0">
                                                            <div class="py-4 text-center text-xs text-slate-400">
                                                                No matching companies found
                                                            </div>
                                                        </template>
                                                    </div>
                                                </div>
                                            </div>
                                            @error('taskProject') <span class="text-[10px] text-rose-500 mt-1 block">{{ $message }}</span> @enderror
                                        </div>

                                        <!-- Assignee -->
                                        <div>
                                            <div class="flex items-center justify-between mb-1.5">
                                                <label class="block text-[10px] font-bold uppercase tracking-wider text-slate-400 dark:text-slate-500">Assignee</label>
                                                @if($taskAssignee !== auth()->id() && !auth()->user()->hasRole('curator'))
                                                    <button type="button" wire:click="$set('taskAssignee', {{ auth()->id() }})" class="text-[9px] text-indigo-650 hover:text-indigo-800 dark:text-indigo-400 dark:hover:text-indigo-350 font-bold uppercase hover:underline">
                                                        Assign to me
                                                    </button>
                                                @endif
                                            </div>
                                            <select wire:model="taskAssignee" class="w-full px-3 py-1.5 bg-slate-50 dark:bg-slate-950 border border-slate-200 dark:border-slate-800 rounded-xl text-xs text-slate-800 dark:text-slate-100 focus:outline-none focus:ring-2 focus:ring-sky-500/20 focus:border-sky-500 transition-all duration-150">
                                                <option value="">Unassigned</option>
                                                @foreach($users as $u)
                                                    <option value="{{ $u->id }}">{{ $u->name }}</option>
                                                @endforeach
                                            </select>
                                            @error('taskAssignee') <span class="text-[10px] text-rose-500 mt-1 block">{{ $message }}</span> @enderror
                                        </div>

                                        <!-- Reporter (Created By) -->
                                        <div>
                                            <label class="block text-[10px] font-bold uppercase tracking-wider text-slate-400 dark:text-slate-500 mb-1.5">Reporter (Created By)</label>
                                            <div class="flex items-center space-x-2 py-1.5 px-3 bg-slate-50 dark:bg-slate-950 border border-slate-200/60 dark:border-slate-850 rounded-xl">
                                                @if($modalTask && $modalTask->exists)
                                                    @if($modalTask->creator)
                                                        <div class="h-5.5 w-5.5 rounded-full bg-slate-200 dark:bg-slate-850 border border-slate-350 dark:border-slate-700 flex items-center justify-center font-extrabold text-slate-700 dark:text-slate-300 text-[9px] uppercase">
                                                            {{ substr($modalTask->creator->name, 0, 2) }}
                                                        </div>
                                                        <span class="text-xs font-semibold text-slate-750 dark:text-slate-300">{{ $modalTask->creator->name }}</span>
                                                    @else
                                                        @if($modalTask->project && $modalTask->project->client)
                                                            <div class="h-5.5 w-5.5 rounded-full bg-emerald-100 dark:bg-emerald-950/40 border border-emerald-300 dark:border-emerald-850 flex items-center justify-center font-extrabold text-emerald-700 dark:text-emerald-400 text-[9px] uppercase">
                                                                CP
                                                            </div>
                                                            <span class="text-xs font-semibold text-emerald-700 dark:text-emerald-400">Client: {{ $modalTask->project->client->name }} (Portal)</span>
                                                        @else
                                                            <div class="h-5.5 w-5.5 rounded-full bg-slate-200 dark:bg-slate-850 border border-slate-350 dark:border-slate-700 flex items-center justify-center font-extrabold text-slate-700 dark:text-slate-300 text-[9px] uppercase">
                                                                CP
                                                            </div>
                                                            <span class="text-xs font-semibold text-slate-750 dark:text-slate-300">Client Portal</span>
                                                        @endif
                                                    @endif
                                                @else
                                                    <div class="h-5.5 w-5.5 rounded-full bg-slate-200 dark:bg-slate-850 border border-slate-350 dark:border-slate-700 flex items-center justify-center font-extrabold text-slate-700 dark:text-slate-300 text-[9px] uppercase">
                                                        {{ substr(auth()->user()->name, 0, 2) }}
                                                    </div>
                                                    <span class="text-xs font-semibold text-slate-750 dark:text-slate-300">{{ auth()->user()->name }} (You)</span>
                                                @endif
                                            </div>
                                        </div>

                                        <!-- Due Date -->
                                        <div>
                                            <label class="block text-[10px] font-bold uppercase tracking-wider text-slate-400 dark:text-slate-500 mb-1.5">Due Date</label>
                                            <input type="date" onclick="this.showPicker()" wire:model="taskDueDate" class="w-full px-3 py-1.5 bg-slate-50 dark:bg-slate-950 border border-slate-200 dark:border-slate-800 rounded-xl text-xs text-slate-800 dark:text-slate-100 focus:outline-none focus:ring-2 focus:ring-sky-500/20 focus:border-sky-500 transition-all duration-150">
                                            @error('taskDueDate') <span class="text-[10px] text-rose-500 mt-1 block">{{ $message }}</span> @enderror
                                        </div>
                                        @if(config('features.task_time_logs', true))
                                            <!-- Time Tracker -->
                                            <div class="border-t border-slate-200/60 dark:border-slate-800/60 pt-3">
                                                <label class="block text-[10px] font-bold uppercase tracking-wider text-slate-400 dark:text-slate-500 mb-2">Time Tracker</label>
                                                <div class="bg-slate-50 dark:bg-slate-900/40 p-3 rounded-xl border border-slate-200/40 dark:border-slate-800/40 space-y-2.5 shadow-sm">
                                                    @if($modalTask)
                                                        @php
                                                            $activeTimer = $modalTask->activeTimer();
                                                        @endphp
                                                        <div class="flex items-center justify-between text-xs">
                                                        <span class="font-semibold text-slate-700 dark:text-slate-350">Logged Time:</span>
                                                        <span class="font-mono font-bold text-slate-800 dark:text-white bg-slate-100 dark:bg-slate-800 px-2 py-0.5 rounded border border-slate-200/40 dark:border-slate-700/60">
                                                            {{ $modalTask->human_formatted_duration }}
                                                        </span>
                                                    </div>

                                                    <!-- Horizontal progress bar representation -->
                                                    <div class="w-full bg-slate-200 dark:bg-slate-800 h-2 rounded-full overflow-hidden">
                                                        <div class="bg-emerald-500 h-full rounded-full transition-all duration-300" style="width: {{ $modalTask->total_duration > 0 ? '100' : '0' }}%"></div>
                                                    </div>

                                                    <div class="pt-1.5 flex items-center justify-between border-t border-slate-200/40 dark:border-slate-855">
                                                        @if($activeTimer)
                                                            <!-- Stop Timer with Ticking -->
                                                            <button type="button" wire:click="toggleTimer({{ $modalTask->id }})" class="w-full flex items-center justify-center space-x-1.5 px-3 py-2 bg-rose-500 hover:bg-rose-600 text-white rounded-xl text-xs font-bold shadow-sm transition-all duration-150 animate-pulse">
                                                                <span class="w-1.5 h-1.5 rounded-full bg-white"></span>
                                                                <span>Stop Timer</span>
                                                            </button>
                                                        @else
                                                            @if($modalTask->assigned_to === auth()->id() || auth()->user()->hasAnyRole(['admin', 'manager']))
                                                                <!-- Start Timer -->
                                                                <button type="button" wire:click="toggleTimer({{ $modalTask->id }})" class="w-full flex items-center justify-center space-x-1.5 px-3 py-2 bg-emerald-600 hover:bg-emerald-700 text-white rounded-xl text-xs font-bold shadow-sm transition-all duration-150">
                                                                    <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                                                                        <path stroke-linecap="round" stroke-linejoin="round" d="M14.752 11.168l-3.197-2.132A1 1 0 0010 9.87v4.263a1 1 0 001.555.832l3.197-2.132a1 1 0 000-1.664z" />
                                                                        <path stroke-linecap="round" stroke-linejoin="round" d="M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                                                    </svg>
                                                                    <span>Start Timer</span>
                                                                </button>
                                                            @else
                                                                <span class="text-[9px] text-slate-400 italic text-center w-full block">Assign task to track time</span>
                                                            @endif
                                                        @endif
                                                    </div>

                                                    @if($activeTimer)
                                                        <!-- Live ticking timer via Alpine JS -->
                                                        <div x-data="{ 
                                                            elapsed: {{ (int) $activeTimer->started_at->diffInSeconds(now(), true) }},
                                                            init() {
                                                                setInterval(() => {
                                                                    this.elapsed++;
                                                                }, 1000);
                                                            },
                                                            formatTime(seconds) {
                                                                const hrs = Math.floor(seconds / 3600);
                                                                const mins = Math.floor((seconds % 3600) / 60);
                                                                const secs = seconds % 60;
                                                                
                                                                let parts = [];
                                                                if (hrs > 0) parts.push(`${hrs}h`);
                                                                if (mins > 0 || hrs > 0) parts.push(`${mins}m`);
                                                                parts.push(`${secs}s`);
                                                                return parts.join(' ');
                                                            }
                                                        }" class="text-center text-[10px] font-semibold text-emerald-600 dark:text-emerald-400 flex items-center justify-center space-x-1">
                                                            <span class="w-1.5 h-1.5 rounded-full bg-emerald-500 animate-ping"></span>
                                                            <span>Active timer: <span x-text="formatTime(elapsed)" class="font-mono"></span></span>
                                                        </div>
                                                    @endif
                                                @else
                                                    <span class="text-slate-400 text-[10px] italic text-center w-full block">Save task to start tracking</span>
                                                @endif
                                            </div>
                                        </div>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Footer -->
                        <div class="px-6 py-4 border-t border-slate-100 dark:border-slate-800/80 bg-slate-50/50 dark:bg-slate-950/20 flex items-center justify-between">
                            <!-- Left: Delete Button (Admins/Managers only, only in edit mode) -->
                            <div>
                                @if($editingTaskId && auth()->user()->hasAnyRole(['admin', 'manager']))
                                    <button type="button" wire:click="deleteTask({{ $editingTaskId }})" wire:confirm="Are you sure you want to permanently delete this task?" class="px-4 py-2 text-xs font-semibold text-rose-600 hover:text-rose-700 bg-rose-50 hover:bg-rose-100 dark:bg-rose-950/20 dark:text-rose-400 border border-rose-100/40 dark:border-rose-900/30 rounded-xl transition-all duration-150">
                                        Delete Task
                                    </button>
                                @endif
                            </div>

                            <!-- Right: Save / Close -->
                            <div class="flex items-center space-x-2">
                                <button type="button" wire:click="closeModal()" class="px-4 py-2 text-xs font-semibold text-slate-700 dark:text-slate-200 bg-white dark:bg-slate-900 hover:bg-slate-100 dark:hover:bg-slate-800 border border-slate-200 dark:border-slate-800 rounded-xl transition-all duration-150">
                                    Cancel
                                </button>
                                <button type="submit" class="px-4 py-2 text-xs font-semibold text-indigo-700 bg-indigo-50 hover:bg-indigo-100/80 dark:bg-indigo-950/30 dark:text-indigo-400 border border-indigo-200/40 dark:border-indigo-800/40 rounded-xl transition-all duration-150">
                                    Save
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    @endif

    {{-- SortableJS for drag & drop --}}
    <style>
        .kanban-ghost { opacity: 0.4 !important; }
        .kanban-dragged { box-shadow: 0 25px 50px -12px rgba(0,0,0,0.25) !important; transform: rotate(1deg) scale(1.02) !important; }
        .kanban-chosen { outline: 2px solid #38bdf8 !important; outline-offset: 2px !important; }
    </style>
    <script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.2/Sortable.min.js"></script>
    <script>
    document.addEventListener('livewire:initialized', () => {
        initKanbanSortable();
        Livewire.hook('morph.updated', () => { initKanbanSortable(); });
    });

    function initKanbanSortable() {
        const columns = document.querySelectorAll('[data-kanban-column]');
        columns.forEach(column => {
            if (column._sortable) {
                column._sortable.destroy();
            }
            column._sortable = new Sortable(column, {
                group: 'kanban',
                animation: 150,
                ghostClass: 'kanban-ghost',
                dragClass: 'kanban-dragged',
                chosenClass: 'kanban-chosen',
                delay: 80,
                delayOnTouchOnly: true,
                onEnd(evt) {
                    const taskId = parseInt(evt.item.dataset.taskId);
                    const newStatus = evt.to.dataset.kanbanColumn;
                    if (taskId && newStatus) {
                        Livewire.dispatch('statusUpdated', { taskId, newStatus });
                    }
                }
            });
        });
    }
    </script>
</div>
