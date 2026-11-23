@php
    $tones = include resource_path('views/onboarding/tones.php');
    $statusTones = $tones['status'];
    $providerTones = $tones['provider'];
    $input = 'px-3.5 py-2 bg-slate-50 dark:bg-slate-950 border border-slate-200 dark:border-slate-800/80 rounded-xl text-xs text-slate-800 dark:text-slate-100 focus:outline-none focus:ring-2 focus:ring-sky-500/20 focus:border-sky-500 transition-all duration-200';
    $taskTypes = \App\Models\Task::TYPES;
    $names = fn ($people) => $people->pluck('name')->implode(', ');
@endphp

<div class="space-y-6">
    <x-flash-message />

    @if($row === null)
        <div class="text-center py-12 border-2 border-dashed border-slate-200 dark:border-slate-800 rounded-2xl">
            <i class="fa-solid fa-clipboard-check text-2xl text-slate-300 dark:text-slate-600 mb-3 block"></i>
            <p class="text-sm font-semibold text-slate-600 dark:text-slate-300">No onboarding yet</p>
            <p class="text-xs text-slate-400 mt-1">Create a KYB or Onboarding task for this company and its status will be tracked here.</p>
            @if($canEdit)
                <div class="flex justify-center gap-2 mt-4">
                    <a href="{{ route('tasks.kanban', ['new' => 'kyb', 'project_id' => $project->id]) }}" wire:navigate class="inline-flex items-center gap-1.5 px-4 py-2 rounded-xl text-xs font-bold text-white bg-gradient-to-r from-sky-500 to-indigo-600 hover:from-sky-400 hover:to-indigo-500 transition"><i class="fa-solid fa-plus"></i> KYB task</a>
                    <a href="{{ route('tasks.kanban', ['new' => 'onboarding', 'project_id' => $project->id]) }}" wire:navigate class="inline-flex items-center gap-1.5 px-4 py-2 rounded-xl text-xs font-semibold text-slate-700 dark:text-slate-200 border border-slate-200 dark:border-slate-700 hover:bg-slate-50 dark:hover:bg-slate-800 transition"><i class="fa-solid fa-plus"></i> Onboarding task</a>
                </div>
            @endif
        </div>
    @else
        <div class="flex items-center justify-between gap-3">
            <div>
                <h3 class="font-outfit font-bold text-lg text-slate-800 dark:text-white">Onboarding</h3>
                <p class="text-xs text-slate-400">{{ $row['code'] }}</p>
            </div>
            <a href="{{ route('onboarding.index', ['open' => $row['code']]) }}" wire:navigate class="text-xs font-semibold text-indigo-600 dark:text-indigo-400 hover:underline">Open in Onboarding Status <i class="fa-solid fa-arrow-right ml-0.5"></i></a>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            {{-- Status --}}
            <div class="space-y-4">
                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block text-[10px] font-bold uppercase tracking-wider text-slate-400 mb-1.5">Status</label>
                        @if($canEdit)
                            <select wire:change="setStatus({{ $row['primary_task_id'] }}, $event.target.value)" class="w-full px-3 py-2 rounded-xl border text-xs font-semibold focus:outline-none focus:ring-2 focus:ring-sky-500/30 {{ $statusTones[$row['status']] ?? $statusTones['Not started'] }}">
                                @foreach($statuses as $status)
                                    <option value="{{ $status }}" @selected($row['status'] === $status)>{{ $status }}</option>
                                @endforeach
                            </select>
                        @else
                            <span class="inline-flex px-2.5 py-1.5 rounded-xl border text-xs font-semibold {{ $statusTones[$row['status']] ?? $statusTones['Not started'] }}">{{ $row['status'] }}</span>
                        @endif
                    </div>
                    <div>
                        <label class="block text-[10px] font-bold uppercase tracking-wider text-slate-400 mb-1.5">KYB responsible</label>
                        <div class="py-2 text-sm text-slate-700 dark:text-slate-200">{{ $row['responsible']->isNotEmpty() ? $names($row['responsible']) : 'Unassigned' }}</div>
                    </div>
                </div>

                <div>
                    <label class="block text-[10px] font-bold uppercase tracking-wider text-slate-400 mb-1.5">Current status</label>
                    @if($canEdit)
                        <textarea rows="4" wire:change="saveCurrentStatus({{ $row['primary_task_id'] }}, $event.target.value)" placeholder="Latest news, provider replies, what we wait for..." class="{{ $input }} w-full resize-y">{{ $row['current_status'] }}</textarea>
                        <p class="mt-1 text-[10px] text-slate-400">Saved automatically when you leave the field.</p>
                    @else
                        <p class="text-sm text-slate-700 dark:text-slate-200 whitespace-pre-line">{{ $row['current_status'] ?: '—' }}</p>
                    @endif
                </div>

                <div>
                    <div class="text-[10px] font-bold uppercase tracking-wider text-slate-400 mb-1.5">Providers</div>
                    <div class="flex flex-wrap gap-1.5">
                        @forelse($row['providers'] as $provider)
                            <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-md border text-[11px] font-semibold {{ $providerTones[$provider['tone']] }}">{{ $provider['name'] }}<span class="font-normal opacity-80">· {{ $provider['status'] }}</span></span>
                        @empty
                            <span class="text-sm text-slate-400">No providers yet. Add gateways to the websites or fill the Compliance tab.</span>
                        @endforelse
                    </div>
                </div>
            </div>

            {{-- Tasks + history --}}
            <div class="space-y-5">
                <div>
                    <div class="text-[10px] font-bold uppercase tracking-wider text-slate-400 mb-2">Tasks</div>
                    <ul class="space-y-2">
                        @foreach($row['tasks'] as $task)
                            <li>
                                <a href="{{ route('tasks.kanban', ['task_id' => $task->id]) }}" wire:navigate class="flex items-center gap-3 p-3 rounded-xl border border-slate-100 dark:border-slate-800 hover:border-sky-300 dark:hover:border-sky-700 hover:bg-slate-50 dark:hover:bg-slate-800/40 transition">
                                    <span class="shrink-0 px-1.5 py-0.5 rounded text-[9px] font-bold uppercase tracking-wider bg-indigo-50 text-indigo-700 dark:bg-indigo-950/30 dark:text-indigo-400">{{ $taskTypes[$task->type] ?? $task->type }}</span>
                                    <div class="min-w-0 flex-1">
                                        <div class="text-sm font-medium text-slate-700 dark:text-slate-200 truncate">{{ $task->title }}</div>
                                        <div class="text-[11px] text-slate-400 truncate">{{ $task->onboarding_status ?: 'Not started' }} · {{ $task->assignees->isNotEmpty() ? $names($task->assignees) : ($task->assignee?->name ?? 'Unassigned') }}</div>
                                    </div>
                                    <span class="shrink-0 text-xs font-semibold text-sky-600 dark:text-sky-400">Open task <i class="fa-solid fa-arrow-up-right-from-square ml-0.5 text-[10px]"></i></span>
                                </a>
                            </li>
                        @endforeach
                    </ul>

                    @if($canEdit && (! $row['kyb_task_id'] || ! $row['onboarding_task_id']))
                        <div class="flex flex-wrap gap-2 mt-3">
                            @unless($row['kyb_task_id'])
                                <a href="{{ route('tasks.kanban', ['new' => 'kyb', 'project_id' => $project->id]) }}" wire:navigate class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-xs font-semibold text-slate-600 dark:text-slate-300 border border-dashed border-slate-300 dark:border-slate-700 hover:bg-slate-50 dark:hover:bg-slate-800 transition"><i class="fa-solid fa-plus"></i> KYB task</a>
                            @endunless
                            @unless($row['onboarding_task_id'])
                                <a href="{{ route('tasks.kanban', ['new' => 'onboarding', 'project_id' => $project->id]) }}" wire:navigate class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-xs font-semibold text-slate-600 dark:text-slate-300 border border-dashed border-slate-300 dark:border-slate-700 hover:bg-slate-50 dark:hover:bg-slate-800 transition"><i class="fa-solid fa-plus"></i> Onboarding task</a>
                            @endunless
                        </div>
                    @endif
                </div>

                <div>
                    <div class="text-[10px] font-bold uppercase tracking-wider text-slate-400 mb-2">History</div>
                    @include('onboarding.history', ['history' => $history])
                </div>

                @if($comments->isNotEmpty())
                    <div>
                        <div class="text-[10px] font-bold uppercase tracking-wider text-slate-400 mb-2">Latest comments</div>
                        <ul class="space-y-2.5">
                            @foreach($comments as $comment)
                                <li class="text-sm">
                                    <div class="text-[11px] text-slate-400"><span class="font-semibold text-slate-600 dark:text-slate-300">{{ $comment->user?->name }}</span> · {{ $comment->created_at->format('d.m.Y H:i') }}</div>
                                    <p class="text-slate-700 dark:text-slate-200 whitespace-pre-line">{{ \Illuminate\Support\Str::limit(trim(strip_tags(html_entity_decode((string) $comment->content))), 240) }}</p>
                                </li>
                            @endforeach
                        </ul>
                    </div>
                @endif
            </div>
        </div>
    @endif
</div>
