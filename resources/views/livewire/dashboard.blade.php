@php
    $statusStyles = [
        'active' => 'bg-emerald-50 text-emerald-700 dark:bg-emerald-950/30 dark:text-emerald-400',
        'onboarding' => 'bg-amber-50 text-amber-700 dark:bg-amber-950/30 dark:text-amber-400',
        'suspended' => 'bg-rose-50 text-rose-700 dark:bg-rose-950/30 dark:text-rose-400',
    ];
    $priorityDots = ['critical' => 'bg-rose-500', 'high' => 'bg-orange-500', 'medium' => 'bg-amber-400', 'low' => 'bg-slate-300 dark:bg-slate-600'];
    $card = 'bg-white dark:bg-slate-900 border border-slate-100 dark:border-slate-800/80 rounded-2xl shadow-sm';
    $needsAttention = $overdueTasks + $overdueReports;
    $formatDuration = fn (int $seconds) => sprintf('%d:%02d', intdiv($seconds, 3600), intdiv($seconds % 3600, 60));
@endphp

<div class="space-y-6 animate-fade-in">
    {{-- Header + quick actions --}}
    <div class="flex flex-col md:flex-row md:items-end md:justify-between gap-4">
        <div>
            <h1 class="font-outfit font-extrabold text-2xl md:text-3xl text-slate-800 dark:text-white tracking-tight">
                {{ $greeting }}, {{ \Illuminate\Support\Str::of(auth()->user()->name)->before(' ') }}
            </h1>
            <p class="text-sm text-slate-500 dark:text-slate-400 mt-1">
                {{ now()->translatedFormat('l, j F Y') }}
                @if($canViewTasks || $canViewDeadlines)
                    ·
                    @if($needsAttention > 0)
                        <span class="font-semibold text-rose-600 dark:text-rose-400">{{ $needsAttention }} overdue</span>
                    @else
                        <span class="font-semibold text-emerald-600 dark:text-emerald-400">Nothing overdue</span>
                    @endif
                @endif
            </p>
        </div>

        <div class="flex flex-wrap gap-2">
            @can('manage_projects')
                <a href="{{ route('projects.create') }}" wire:navigate class="inline-flex items-center gap-2 px-4 py-2.5 rounded-xl text-xs font-bold text-white bg-gradient-to-r from-sky-500 to-indigo-600 hover:from-sky-400 hover:to-indigo-500 shadow-md shadow-indigo-500/20 transition">
                    <i class="fa-solid fa-plus"></i> New company
                </a>
            @endcan
            @if($canViewTasks && config('features.kanban'))
                <a href="{{ route('tasks.kanban') }}" wire:navigate class="inline-flex items-center gap-2 px-4 py-2.5 rounded-xl text-xs font-semibold text-slate-700 dark:text-slate-200 bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 hover:bg-slate-50 dark:hover:bg-slate-800 transition">
                    <i class="fa-solid fa-list-check"></i> Kanban board
                </a>
            @endif
            @if($canViewTasks && config('features.my_work'))
                <a href="{{ route('my.work') }}" wire:navigate class="inline-flex items-center gap-2 px-4 py-2.5 rounded-xl text-xs font-semibold text-slate-700 dark:text-slate-200 bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 hover:bg-slate-50 dark:hover:bg-slate-800 transition">
                    <i class="fa-solid fa-user-check"></i> My work
                </a>
            @endif
        </div>
    </div>

    {{-- Attention strip: everything here is a link to where the problem can be fixed --}}
    @if($canViewTasks || $canViewDeadlines)
        <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
            @php
                $tiles = [];
                if ($canViewTasks) {
                    $tiles[] = ['label' => 'Overdue tasks', 'value' => $overdueTasks, 'icon' => 'fa-triangle-exclamation', 'tone' => $overdueTasks ? 'rose' : 'slate', 'url' => $canViewDeadlines ? route('deadlines') : route('tasks.kanban')];
                    $tiles[] = ['label' => 'Due today', 'value' => $dueTodayTasks, 'icon' => 'fa-calendar-day', 'tone' => $dueTodayTasks ? 'amber' : 'slate', 'url' => $canViewDeadlines ? route('deadlines') : route('tasks.kanban')];
                    $tiles[] = ['label' => 'My open tasks', 'value' => $myTasks->count() >= 6 ? '6+' : $myTasks->count(), 'icon' => 'fa-user-check', 'tone' => 'sky', 'url' => config('features.my_work') ? route('my.work') : route('tasks.kanban')];
                }
                if ($canViewDeadlines) {
                    $tiles[] = ['label' => 'Overdue reports', 'value' => $overdueReports, 'icon' => 'fa-file-circle-exclamation', 'tone' => $overdueReports ? 'rose' : 'slate', 'url' => route('deadlines')];
                }
                $tones = [
                    'rose' => 'text-rose-600 dark:text-rose-400 bg-rose-50 dark:bg-rose-950/30',
                    'amber' => 'text-amber-600 dark:text-amber-400 bg-amber-50 dark:bg-amber-950/30',
                    'sky' => 'text-sky-600 dark:text-sky-400 bg-sky-50 dark:bg-sky-950/30',
                    'slate' => 'text-slate-400 dark:text-slate-500 bg-slate-100 dark:bg-slate-800/60',
                ];
            @endphp
            @foreach($tiles as $tile)
                <a href="{{ $tile['url'] }}" wire:navigate class="{{ $card }} p-5 flex items-center gap-4 hover:border-sky-300 dark:hover:border-sky-700 transition group">
                    <div class="h-11 w-11 rounded-xl flex items-center justify-center {{ $tones[$tile['tone']] }}">
                        <i class="fa-solid {{ $tile['icon'] }}"></i>
                    </div>
                    <div>
                        <p class="font-outfit font-bold text-2xl text-slate-800 dark:text-white leading-none">{{ $tile['value'] }}</p>
                        <p class="text-xs text-slate-500 dark:text-slate-400 mt-1.5">{{ $tile['label'] }}</p>
                    </div>
                </a>
            @endforeach
        </div>
    @endif

    <div class="grid grid-cols-1 lg:grid-cols-12 gap-6">
        {{-- Left column --}}
        <div class="lg:col-span-8 space-y-6">
            {{-- My tasks --}}
            @if($canViewTasks)
                <section class="{{ $card }} p-6">
                    <div class="flex items-center justify-between pb-4 border-b border-slate-100 dark:border-slate-800/60">
                        <div>
                            <h2 class="font-outfit font-bold text-lg text-slate-800 dark:text-white">My tasks</h2>
                            <p class="text-xs text-slate-400 mt-0.5">Assigned to you, nearest deadline first</p>
                        </div>
                        <a href="{{ config('features.my_work') ? route('my.work') : route('tasks.kanban') }}" wire:navigate class="text-xs font-semibold text-indigo-600 dark:text-indigo-400 hover:underline">View all</a>
                    </div>

                    <ul class="divide-y divide-slate-100 dark:divide-slate-800/40">
                        @forelse($myTasks as $task)
                            @php($isOverdue = $task->due_date && $task->due_date->lt(today()))
                            <li>
                                <a href="{{ route('tasks.kanban', ['task_id' => $task->id]) }}" wire:navigate class="flex items-center gap-3 py-3 px-1 -mx-1 rounded-lg hover:bg-slate-50 dark:hover:bg-slate-800/30 transition">
                                    <span class="h-2.5 w-2.5 rounded-full flex-shrink-0 {{ $priorityDots[$task->priority] ?? 'bg-slate-300' }}" title="{{ ucfirst((string) $task->priority) }} priority"></span>
                                    <div class="min-w-0 flex-1">
                                        <p class="text-sm font-medium text-slate-700 dark:text-slate-200 truncate">{{ $task->title }}</p>
                                        <p class="text-[11px] text-slate-400 truncate">{{ $task->project?->name ?? 'No company' }} · {{ str_replace('_', ' ', ucfirst($task->status)) }}</p>
                                    </div>
                                    @if($task->due_date)
                                        <span class="text-xs font-semibold flex-shrink-0 {{ $isOverdue ? 'text-rose-600 dark:text-rose-400' : 'text-slate-500 dark:text-slate-400' }}">
                                            {{ $task->due_date->format('d.m') }}{{ $isOverdue ? ' · overdue' : '' }}
                                        </span>
                                    @endif
                                </a>
                            </li>
                        @empty
                            <li class="py-8 text-center text-sm text-slate-400">
                                <i class="fa-solid fa-circle-check text-emerald-500 text-xl mb-2 block"></i>
                                You have no open tasks. Nice!
                            </li>
                        @endforelse
                    </ul>
                </section>
            @endif

            {{-- Open email tickets (Gmail inbox) --}}
            @if($canViewTasks && $tickets['count'] > 0)
                <section class="{{ $card }} p-6">
                    <div class="flex items-center justify-between pb-4 border-b border-slate-100 dark:border-slate-800/60">
                        <div>
                            <h2 class="font-outfit font-bold text-lg text-slate-800 dark:text-white">Open email tickets</h2>
                            <p class="text-xs text-slate-400 mt-0.5">{{ $tickets['count'] }} waiting for a reply</p>
                        </div>
                    </div>

                    <ul class="divide-y divide-slate-100 dark:divide-slate-800/40">
                        @foreach($tickets['items'] as $ticket)
                            <li>
                                <a href="{{ $ticket->task_id ? route('tasks.kanban', ['task_id' => $ticket->task_id]) : route('tasks.kanban') }}" wire:navigate class="flex items-center gap-3 py-3 px-1 -mx-1 rounded-lg hover:bg-slate-50 dark:hover:bg-slate-800/30 transition">
                                    <div class="h-8 w-8 rounded-lg bg-sky-50 text-sky-600 dark:bg-sky-950/30 dark:text-sky-400 flex items-center justify-center flex-shrink-0"><i class="fa-solid fa-envelope"></i></div>
                                    <div class="min-w-0 flex-1">
                                        <p class="text-sm font-medium text-slate-700 dark:text-slate-200 truncate">{{ $ticket->subject ?: '(no subject)' }}</p>
                                        <p class="text-[11px] text-slate-400 truncate">{{ $ticket->customer_email }}@if($ticket->project) · {{ $ticket->project->name }}@endif</p>
                                    </div>
                                    <span class="text-[11px] text-slate-400 flex-shrink-0">{{ $ticket->created_at->diffForHumans(short: true) }}</span>
                                </a>
                            </li>
                        @endforeach
                    </ul>
                </section>
            @endif

            {{-- Companies --}}
            @if($canViewProjects)
                <section class="{{ $card }} p-6">
                    <div class="flex items-center justify-between pb-4 border-b border-slate-100 dark:border-slate-800/60">
                        <div>
                            <h2 class="font-outfit font-bold text-lg text-slate-800 dark:text-white">Companies</h2>
                            <p class="text-xs text-slate-400 mt-0.5">Pipeline overview and recently updated</p>
                        </div>
                        <a href="{{ route('projects.index') }}" wire:navigate class="text-xs font-semibold text-indigo-600 dark:text-indigo-400 hover:underline">View all</a>
                    </div>

                    <div class="flex flex-wrap gap-2 mt-4">
                        @foreach(['onboarding' => 'Onboarding', 'active' => 'Active', 'suspended' => 'Suspended'] as $status => $label)
                            <a href="{{ route('projects.index', ['status' => $status]) }}" wire:navigate class="inline-flex items-center gap-2 px-3 py-1.5 rounded-lg text-xs font-semibold {{ $statusStyles[$status] }} hover:opacity-80 transition">
                                {{ $label }}
                                <span class="font-outfit font-bold">{{ $projectCounts[$status] ?? 0 }}</span>
                            </a>
                        @endforeach
                        <span class="inline-flex items-center px-3 py-1.5 rounded-lg text-xs text-slate-500 dark:text-slate-400 bg-slate-100 dark:bg-slate-800/60">
                            Total <span class="ml-2 font-outfit font-bold">{{ $projectCounts->sum() }}</span>
                        </span>
                    </div>

                    <ul class="mt-4 divide-y divide-slate-100 dark:divide-slate-800/40">
                        @forelse($recentProjects as $project)
                            <li>
                                <a href="{{ route('projects.show', $project) }}" wire:navigate class="flex items-center justify-between gap-3 py-3 px-1 -mx-1 rounded-lg hover:bg-slate-50 dark:hover:bg-slate-800/30 transition">
                                    <div class="min-w-0">
                                        <p class="text-sm font-medium text-slate-700 dark:text-slate-200 truncate">{{ $project->name }}</p>
                                        <p class="text-[11px] text-slate-400">Updated {{ $project->updated_at->diffForHumans() }}</p>
                                    </div>
                                    <span class="px-2.5 py-1 rounded-lg text-[11px] font-semibold capitalize flex-shrink-0 {{ $statusStyles[$project->status] ?? 'bg-slate-100 text-slate-600 dark:bg-slate-800 dark:text-slate-300' }}">{{ $project->status }}</span>
                                </a>
                            </li>
                        @empty
                            <li class="py-8 text-center text-sm text-slate-400">No companies yet.</li>
                        @endforelse
                    </ul>
                </section>
            @endif

            {{-- Team workload (managers / admins) --}}
            @if($workload->isNotEmpty())
                <section class="{{ $card }} p-6">
                    <div class="pb-4 border-b border-slate-100 dark:border-slate-800/60">
                        <h2 class="font-outfit font-bold text-lg text-slate-800 dark:text-white">Team workload</h2>
                        <p class="text-xs text-slate-400 mt-0.5">Open tasks per person, most overdue first</p>
                    </div>

                    <table class="w-full mt-2 text-sm">
                        <thead>
                            <tr class="text-[11px] uppercase tracking-wide text-slate-400">
                                <th class="text-left font-semibold py-2">Person</th>
                                <th class="text-right font-semibold py-2">Open</th>
                                <th class="text-right font-semibold py-2">Overdue</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 dark:divide-slate-800/40">
                            @foreach($workload as $row)
                                <tr>
                                    <td class="py-2.5 text-slate-700 dark:text-slate-200">{{ $row['user']->name }}</td>
                                    <td class="py-2.5 text-right font-semibold text-slate-600 dark:text-slate-300">{{ $row['open'] }}</td>
                                    <td class="py-2.5 text-right font-semibold {{ $row['overdue'] ? 'text-rose-600 dark:text-rose-400' : 'text-slate-300 dark:text-slate-600' }}">{{ $row['overdue'] }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </section>
            @endif
        </div>

        {{-- Right column --}}
        <div class="lg:col-span-4 space-y-6">
            @if($time !== null)
                <section class="{{ $card }} p-6">
                    <div class="flex items-center justify-between pb-4 border-b border-slate-100 dark:border-slate-800/60">
                        <h2 class="font-outfit font-bold text-lg text-slate-800 dark:text-white">My time</h2>
                        @if($time['running'])
                            <span class="inline-flex items-center gap-1.5 text-[11px] font-semibold text-emerald-600 dark:text-emerald-400">
                                <span class="h-2 w-2 rounded-full bg-emerald-500 animate-pulse"></span> Timer running
                            </span>
                        @endif
                    </div>
                    <div class="grid grid-cols-2 gap-4 mt-4">
                        <div>
                            <p class="font-outfit font-bold text-2xl text-slate-800 dark:text-white">{{ $formatDuration($time['today']) }}</p>
                            <p class="text-xs text-slate-400 mt-1">Today (h:mm)</p>
                        </div>
                        <div>
                            <p class="font-outfit font-bold text-2xl text-slate-800 dark:text-white">{{ $formatDuration($time['week']) }}</p>
                            <p class="text-xs text-slate-400 mt-1">This week (h:mm)</p>
                        </div>
                    </div>
                    @can('view_reports')
                        @if(config('features.time_tracking'))
                            <a href="{{ route('reports.time') }}" wire:navigate class="inline-block mt-4 text-xs font-semibold text-indigo-600 dark:text-indigo-400 hover:underline">Time report</a>
                        @endif
                    @endcan
                </section>
            @endif
            @if($canViewDeadlines)
                <section class="{{ $card }} p-6">
                    <div class="flex items-center justify-between pb-4 border-b border-slate-100 dark:border-slate-800/60">
                        <div>
                            <h2 class="font-outfit font-bold text-lg text-slate-800 dark:text-white">Upcoming deadlines</h2>
                            <p class="text-xs text-slate-400 mt-0.5">Next {{ \App\Livewire\Dashboard::UPCOMING_DAYS }} days</p>
                        </div>
                        <a href="{{ route('deadlines') }}" wire:navigate class="text-xs font-semibold text-indigo-600 dark:text-indigo-400 hover:underline">All</a>
                    </div>

                    <ul class="mt-3 space-y-3">
                        @forelse($deadlines as $item)
                            <li class="flex items-start gap-3">
                                <div class="mt-0.5 h-8 w-8 rounded-lg flex items-center justify-center text-xs flex-shrink-0 {{ $item['is_overdue'] ? 'bg-rose-50 text-rose-600 dark:bg-rose-950/30 dark:text-rose-400' : 'bg-slate-100 text-slate-500 dark:bg-slate-800 dark:text-slate-400' }}">
                                    <i class="fa-solid {{ $item['type'] === 'report' ? 'fa-file-lines' : 'fa-list-check' }}"></i>
                                </div>
                                <div class="min-w-0 flex-1">
                                    @if($item['type'] === 'task')
                                        <a href="{{ route('tasks.kanban', ['task_id' => $item['task_id']]) }}" wire:navigate class="text-sm font-medium text-slate-700 dark:text-slate-200 hover:text-sky-600 dark:hover:text-sky-400 truncate block">{{ $item['label'] }}</a>
                                    @else
                                        <a href="{{ route('projects.show', $item['project']) }}" wire:navigate class="text-sm font-medium text-slate-700 dark:text-slate-200 hover:text-sky-600 dark:hover:text-sky-400 truncate block">{{ $item['label'] }}</a>
                                    @endif
                                    <p class="text-[11px] text-slate-400 truncate">{{ $item['project']?->name }}</p>
                                </div>
                                <span class="text-xs font-semibold flex-shrink-0 {{ $item['is_overdue'] ? 'text-rose-600 dark:text-rose-400' : 'text-slate-500 dark:text-slate-400' }}">{{ $item['date']->format('d.m') }}</span>
                            </li>
                        @empty
                            <li class="py-6 text-center text-sm text-slate-400">No deadlines coming up.</li>
                        @endforelse
                    </ul>
                </section>
            @endif

            @if($documents['expired'] + $documents['expiring'] > 0)
                <section class="{{ $card }} p-6">
                    <div class="flex items-center justify-between pb-4 border-b border-slate-100 dark:border-slate-800/60">
                        <div>
                            <h2 class="font-outfit font-bold text-lg text-slate-800 dark:text-white">Document validity</h2>
                            <p class="text-xs text-slate-400 mt-0.5">PCI DSS and compliance documents</p>
                        </div>
                        <a href="{{ route('pci-dss.index') }}" wire:navigate class="text-xs font-semibold text-indigo-600 dark:text-indigo-400 hover:underline">All</a>
                    </div>

                    <div class="flex gap-2 mt-4">
                        <a href="{{ route('pci-dss.index', ['status' => 'expired']) }}" wire:navigate class="px-3 py-1.5 rounded-lg text-xs font-semibold {{ $documents['expired'] ? 'bg-rose-50 text-rose-700 dark:bg-rose-950/30 dark:text-rose-400' : 'bg-slate-100 text-slate-400 dark:bg-slate-800/60' }}">
                            Expired <span class="font-outfit font-bold ml-1">{{ $documents['expired'] }}</span>
                        </a>
                        <a href="{{ route('pci-dss.index', ['status' => 'expiring_soon']) }}" wire:navigate class="px-3 py-1.5 rounded-lg text-xs font-semibold {{ $documents['expiring'] ? 'bg-amber-50 text-amber-700 dark:bg-amber-950/30 dark:text-amber-400' : 'bg-slate-100 text-slate-400 dark:bg-slate-800/60' }}">
                            Expiring in 30 days <span class="font-outfit font-bold ml-1">{{ $documents['expiring'] }}</span>
                        </a>
                    </div>

                    <ul class="mt-3 space-y-2.5">
                        @foreach($documents['items'] as $document)
                            <li class="flex items-center justify-between gap-3 text-sm">
                                <div class="min-w-0">
                                    <p class="font-medium text-slate-700 dark:text-slate-200 truncate">{{ $document->title }}</p>
                                    <p class="text-[11px] text-slate-400 truncate">{{ $document->client?->name ?? $document->project?->name ?? '—' }} · {{ $document->document_type }}</p>
                                </div>
                                <span class="text-xs font-semibold flex-shrink-0 {{ $document->valid_until->lt(today()) ? 'text-rose-600 dark:text-rose-400' : 'text-amber-600 dark:text-amber-400' }}">{{ $document->valid_until->format('d.m.Y') }}</span>
                            </li>
                        @endforeach
                    </ul>
                </section>
            @endif

            @if($canViewActivity)
                <section class="{{ $card }} p-6">
                    <div class="pb-4 border-b border-slate-100 dark:border-slate-800/60 flex items-center justify-between">
                        <h2 class="font-outfit font-bold text-lg text-slate-800 dark:text-white">Recent activity</h2>
                        <a href="{{ route('activity.center') }}" wire:navigate class="text-xs font-semibold text-indigo-600 dark:text-indigo-400 hover:underline">All</a>
                    </div>

                    <ul class="mt-4 space-y-4">
                        @forelse($activity as $log)
                            <li class="flex items-start gap-3">
                                <div class="h-8 w-8 rounded-full flex-shrink-0 flex items-center justify-center text-[10px] font-bold text-white {{ $log->user ? 'bg-gradient-to-tr from-sky-400 to-indigo-500' : ($log->client ? 'bg-gradient-to-tr from-emerald-400 to-teal-500' : 'bg-slate-300 dark:bg-slate-700') }}">
                                    {{ $log->user ? mb_strtoupper(mb_substr($log->user->name, 0, 2)) : ($log->client ? 'CP' : '•') }}
                                </div>
                                <div class="min-w-0 flex-1">
                                    <p class="text-xs leading-snug text-slate-600 dark:text-slate-300">{{ $log->description }}</p>
                                    <p class="text-[10px] text-slate-400 mt-1">
                                        {{ $log->created_at->diffForHumans() }}@if($log->project) · {{ $log->project->name }}@endif
                                    </p>
                                </div>
                            </li>
                        @empty
                            <li class="py-6 text-center text-sm text-slate-400">No activity yet.</li>
                        @endforelse
                    </ul>
                </section>
            @endif
        </div>
    </div>
</div>
