@php
    $tones = include resource_path('views/onboarding/tones.php');
    $statusTones = $tones['status'];
    $statusAccents = $tones['accent'];
    $providerTones = $tones['provider'];
    $input = 'px-3.5 py-2 bg-slate-50 dark:bg-slate-950 border border-slate-200 dark:border-slate-800/80 rounded-xl text-xs text-slate-800 dark:text-slate-100 focus:outline-none focus:ring-2 focus:ring-sky-500/20 focus:border-sky-500 transition-all duration-200';
    $card = 'bg-white dark:bg-slate-900 border border-slate-100 dark:border-slate-800/80 rounded-2xl shadow-sm';
    $hasFilters = $search !== '' || $filterStatus !== '' || $filterResponsible !== '' || $filterClient !== '';
    $host = fn ($url) => preg_replace('#^https?://(www\.)?#i', '', rtrim((string) $url, '/'));
    $names = fn ($people) => $people->pluck('name')->implode(', ');
    $taskTypes = \App\Models\Task::TYPES;
@endphp

<div class="space-y-5 animate-fade-in">
    @unless($detail)
        <x-flash-message />
    @endunless

    {{-- Header --}}
    <div class="flex flex-col md:flex-row md:items-end md:justify-between gap-4">
        <div>
            <h1 class="font-outfit font-extrabold text-2xl md:text-3xl text-slate-800 dark:text-white tracking-tight">Onboarding Status</h1>
            <p class="text-sm text-slate-500 dark:text-slate-400 mt-1">
                One row per company, built from <span class="font-semibold">Onboarding</span> and <span class="font-semibold">KYB</span> tasks. Click a row to see details and open the task.
            </p>
        </div>
        @if($canEdit)
            <div class="flex flex-wrap gap-2">
                <a href="{{ route('tasks.kanban', ['new' => 'kyb']) }}" wire:navigate class="inline-flex items-center gap-2 px-4 py-2.5 rounded-xl text-xs font-bold text-white bg-gradient-to-r from-sky-500 to-indigo-600 hover:from-sky-400 hover:to-indigo-500 shadow-md shadow-indigo-500/20 transition">
                    <i class="fa-solid fa-plus"></i> KYB task
                </a>
                <a href="{{ route('tasks.kanban', ['new' => 'onboarding']) }}" wire:navigate class="inline-flex items-center gap-2 px-4 py-2.5 rounded-xl text-xs font-semibold text-slate-700 dark:text-slate-200 bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 hover:bg-slate-50 dark:hover:bg-slate-800 transition">
                    <i class="fa-solid fa-plus"></i> Onboarding task
                </a>
            </div>
        @endif
    </div>

    {{-- Status overview: every chip filters the table --}}
    <div class="flex gap-2 overflow-x-auto pb-1 -mx-1 px-1">
        <button type="button" wire:click="$set('filterStatus', '')" class="shrink-0 px-3 py-1.5 rounded-lg border text-xs font-semibold transition {{ $filterStatus === '' ? 'bg-slate-800 text-white border-slate-800 dark:bg-white dark:text-slate-900 dark:border-white' : 'bg-white text-slate-600 border-slate-200 hover:bg-slate-50 dark:bg-slate-900 dark:text-slate-300 dark:border-slate-800' }}">
            All <span class="ml-1 font-outfit font-bold">{{ $total }}</span>
        </button>
        @foreach($statuses as $status)
            @if(($statusCounts[$status] ?? 0) > 0 || $filterStatus === $status)
                <button type="button" wire:click="$set('filterStatus', '{{ $filterStatus === $status ? '' : $status }}')" class="shrink-0 px-3 py-1.5 rounded-lg border text-xs font-semibold transition {{ $statusTones[$status] }} {{ $filterStatus === $status ? 'ring-2 ring-sky-500/40' : 'hover:opacity-80' }}">
                    {{ $status }} <span class="ml-1 font-outfit font-bold">{{ $statusCounts[$status] ?? 0 }}</span>
                </button>
            @endif
        @endforeach
    </div>

    {{-- Filters --}}
    <div class="grid grid-cols-2 md:grid-cols-12 gap-3">
        <div class="col-span-2 md:col-span-4 relative">
            <i class="fa-solid fa-magnifying-glass absolute left-3.5 top-1/2 -translate-y-1/2 text-slate-400 text-xs"></i>
            <input type="text" wire:model.live.debounce.300ms="search" placeholder="Search company, client, website, director, provider..." class="{{ $input }} w-full pl-9">
        </div>
        <div class="md:col-span-2">
            <select wire:model.live="filterClient" class="{{ $input }} w-full">
                <option value="">All clients</option>
                @foreach($clients as $clientRow)
                    <option value="{{ $clientRow['client_id'] }}">{{ $clientRow['client'] }}</option>
                @endforeach
            </select>
        </div>
        <div class="md:col-span-2">
            <select wire:model.live="filterResponsible" class="{{ $input }} w-full">
                <option value="">All KYB responsible</option>
                @foreach($responsibles as $person)
                    <option value="{{ $person->id }}">{{ $person->name }} ({{ $person->onboarding_count }})</option>
                @endforeach
            </select>
        </div>
        <div class="md:col-span-2">
            <select wire:model.live="sortBy" class="{{ $input }} w-full">
                <option value="id">Sort: ID</option>
                <option value="company">Sort: Company</option>
                <option value="status">Sort: Status</option>
                <option value="updated">Sort: Recently updated</option>
            </select>
        </div>
        <div class="md:col-span-2 flex items-center">
            @if($hasFilters)
                <button type="button" wire:click="resetFilters" class="w-full px-3.5 py-2 rounded-xl text-xs font-semibold text-slate-600 dark:text-slate-300 bg-slate-100 dark:bg-slate-800 hover:bg-slate-200 dark:hover:bg-slate-700 transition">Reset filters</button>
            @endif
        </div>
    </div>

    {{-- Empty state --}}
    @if($rows->isEmpty())
        <div class="{{ $card }} py-14 px-6 text-center text-sm text-slate-400">
            @if($hasFilters)
                Nothing matches these filters.
            @else
                <i class="fa-solid fa-clipboard-check text-2xl mb-3 block"></i>
                No onboarding yet. Create a task with type <span class="font-semibold">KYB</span> or <span class="font-semibold">Onboarding</span> for a company and it will appear here.
            @endif
        </div>
    @else
        {{-- Desktop table --}}
        <div class="{{ $card }} overflow-hidden hidden md:block">
            <table class="w-full text-left border-collapse table-fixed">
                <thead>
                    <tr class="text-[11px] font-semibold uppercase tracking-wider text-slate-400 dark:text-slate-500 border-b border-slate-100 dark:border-slate-800/60 bg-slate-50/60 dark:bg-slate-950/30">
                        <th class="py-3 px-4 w-[24%]">Company</th>
                        <th class="py-3 px-4 w-[16%]">Director / Curator</th>
                        <th class="py-3 px-4 w-[16%]">Providers</th>
                        <th class="py-3 px-4 w-[17%]">Status</th>
                        <th class="py-3 px-4">Current status</th>
                        <th class="py-3 px-2 w-10"></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 dark:divide-slate-800/40 text-sm align-top">
                    @foreach($rows as $row)
                        <tr wire:key="onb-{{ $row['code'] }}" wire:click="select('{{ $row['code'] }}')" class="cursor-pointer hover:bg-slate-50/70 dark:hover:bg-slate-800/20 transition-colors {{ $selected === $row['code'] ? 'bg-sky-50/60 dark:bg-sky-950/10' : '' }}">
                            <td class="py-3.5 px-4 border-l-4 {{ $statusAccents[$row['status']] ?? 'border-l-slate-300' }}">
                                <div class="flex items-center gap-2 text-[10px] text-slate-400">
                                    <span class="font-mono font-semibold">{{ $row['code'] }}</span>
                                    @if($row['client'])<span class="truncate">· {{ $row['client'] }}</span>@endif
                                </div>
                                <div class="font-semibold text-slate-800 dark:text-white truncate" title="{{ $row['company'] }}">{{ $row['company'] }}</div>
                                @if(! $row['linked'])
                                    <div class="mt-0.5 text-[10px] font-semibold text-amber-600 dark:text-amber-400"><i class="fa-solid fa-link-slash mr-0.5"></i>Not linked to a company</div>
                                @endif
                                @if($row['websites']->isNotEmpty())
                                    <div class="mt-0.5 text-[11px] text-slate-500 dark:text-slate-400 truncate">
                                        {{ $host($row['websites']->first()->url) }}@if($row['websites']->count() > 1) <span class="text-slate-400">+{{ $row['websites']->count() - 1 }}</span>@endif
                                    </div>
                                @endif
                            </td>

                            <td class="py-3.5 px-4 text-xs">
                                <div class="text-slate-700 dark:text-slate-200 truncate" title="Director / UBO"><i class="fa-solid fa-user mr-1 text-slate-300 dark:text-slate-600"></i>{{ $row['director'] ?? '—' }}</div>
                                <div class="text-slate-500 dark:text-slate-400 truncate mt-1" title="Director Curator"><i class="fa-solid fa-user-shield mr-1 text-slate-300 dark:text-slate-600"></i>{{ $row['curator'] ?: '—' }}</div>
                            </td>

                            <td class="py-3.5 px-4">
                                <div class="flex flex-wrap gap-1">
                                    @forelse(array_slice($row['providers'], 0, 4) as $provider)
                                        <span class="inline-flex items-center gap-1 px-1.5 py-0.5 rounded-md border text-[10px] font-semibold {{ $providerTones[$provider['tone']] }}" title="{{ $provider['name'] }} · {{ $provider['status'] }}">{{ $provider['name'] }}</span>
                                    @empty
                                        <span class="text-slate-400 text-xs">—</span>
                                    @endforelse
                                    @if(count($row['providers']) > 4)
                                        <span class="text-[10px] text-slate-400 self-center">+{{ count($row['providers']) - 4 }}</span>
                                    @endif
                                </div>
                            </td>

                            <td class="py-3.5 px-4">
                                @if($canEdit)
                                    <select @click.stop wire:change="setStatus({{ $row['primary_task_id'] }}, $event.target.value)" class="w-full px-2 py-1.5 rounded-lg border text-xs font-semibold focus:outline-none focus:ring-2 focus:ring-sky-500/30 {{ $statusTones[$row['status']] ?? $statusTones['Not started'] }}" aria-label="Onboarding status">
                                        @foreach($statuses as $status)
                                            <option value="{{ $status }}" @selected($row['status'] === $status)>{{ $status }}</option>
                                        @endforeach
                                    </select>
                                @else
                                    <span class="inline-flex px-2.5 py-1 rounded-lg border text-xs font-semibold {{ $statusTones[$row['status']] ?? $statusTones['Not started'] }}">{{ $row['status'] }}</span>
                                @endif
                                <div class="mt-1.5 text-[11px] text-slate-500 dark:text-slate-400 truncate" title="KYB responsible">
                                    <i class="fa-solid fa-file-shield mr-0.5 text-slate-300 dark:text-slate-600"></i>{{ $row['responsible']->isNotEmpty() ? $names($row['responsible']) : 'Unassigned' }}
                                </div>
                                @if($row['kyb_status'] && $row['kyb_task_id'] !== $row['primary_task_id'])
                                    <div class="text-[10px] text-slate-400">KYB: {{ $row['kyb_status'] }}</div>
                                @endif
                            </td>

                            <td class="py-3.5 px-4">
                                @if($row['current_status'])
                                    <p class="text-xs text-slate-700 dark:text-slate-200 line-clamp-3 whitespace-pre-line">{{ $row['current_status'] }}</p>
                                @elseif($row['description'] !== '')
                                    <p class="text-xs text-slate-400 line-clamp-2"><i class="fa-solid fa-align-left mr-0.5"></i>{{ $row['description'] }}</p>
                                @else
                                    <p class="text-xs text-slate-400">—</p>
                                @endif
                                @if($row['comment'])
                                    <p class="mt-1.5 text-[11px] leading-snug text-slate-400 dark:text-slate-500 line-clamp-2">
                                        <i class="fa-regular fa-comment mr-0.5"></i><span class="font-semibold">{{ $row['comment']->user?->name }}</span>
                                        · {{ $row['comment']->created_at->format('d.m.Y') }}: {{ \Illuminate\Support\Str::limit(strip_tags((string) $row['comment']->content), 120) }}
                                    </p>
                                @endif
                            </td>

                            <td class="py-3.5 px-2 text-slate-300 dark:text-slate-600"><i class="fa-solid fa-chevron-right text-xs mt-1"></i></td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        {{-- Mobile cards --}}
        <div class="md:hidden space-y-3">
            @foreach($rows as $row)
                <button type="button" wire:key="onb-card-{{ $row['code'] }}" wire:click="select('{{ $row['code'] }}')" class="{{ $card }} block w-full text-left p-4 border-l-4 {{ $statusAccents[$row['status']] ?? 'border-l-slate-300' }}">
                    <div class="flex items-start justify-between gap-3">
                        <div class="min-w-0">
                            <div class="text-[10px] text-slate-400"><span class="font-mono font-semibold">{{ $row['code'] }}</span>@if($row['client']) · {{ $row['client'] }}@endif</div>
                            <div class="font-semibold text-slate-800 dark:text-white truncate">{{ $row['company'] }}</div>
                        </div>
                        <span class="shrink-0 inline-flex px-2 py-0.5 rounded-lg border text-[11px] font-semibold {{ $statusTones[$row['status']] ?? $statusTones['Not started'] }}">{{ $row['status'] }}</span>
                    </div>
                    @if(! $row['linked'])
                        <div class="mt-1 text-[10px] font-semibold text-amber-600 dark:text-amber-400"><i class="fa-solid fa-link-slash mr-0.5"></i>Not linked to a company</div>
                    @endif
                    <div class="mt-2 text-xs text-slate-500 dark:text-slate-400">
                        {{ $row['director'] ?? '—' }}@if($row['curator']) · {{ $row['curator'] }}@endif
                        · KYB: {{ $row['responsible']->isNotEmpty() ? $names($row['responsible']) : 'Unassigned' }}
                    </div>
                    @if($row['providers'])
                        <div class="flex flex-wrap gap-1 mt-2">
                            @foreach($row['providers'] as $provider)
                                <span class="px-1.5 py-0.5 rounded-md border text-[10px] font-semibold {{ $providerTones[$provider['tone']] }}">{{ $provider['name'] }}</span>
                            @endforeach
                        </div>
                    @endif
                    @if($row['current_status'] || $row['description'] !== '')
                        <p class="mt-2 text-xs text-slate-600 dark:text-slate-300 line-clamp-3 whitespace-pre-line">{{ $row['current_status'] ?: $row['description'] }}</p>
                    @endif
                </button>
            @endforeach
        </div>
    @endif

    {{-- Side panel --}}
    @if($detail)
        <div class="fixed inset-0 z-50" x-data @keydown.escape.window="$wire.closeDetail()" role="dialog" aria-modal="true" aria-labelledby="onb-title">
            <div class="absolute inset-0 bg-slate-950/50 backdrop-blur-sm" wire:click="closeDetail"></div>

            <aside class="absolute inset-y-0 right-0 w-full sm:max-w-xl bg-white dark:bg-slate-900 shadow-2xl flex flex-col border-l border-slate-200 dark:border-slate-800">
                {{-- Header --}}
                <div class="flex items-start justify-between gap-4 px-6 py-5 border-b border-slate-100 dark:border-slate-800/60">
                    <div class="min-w-0">
                        <div class="text-[11px] text-slate-400"><span class="font-mono font-semibold">{{ $detail['code'] }}</span>@if($detail['client']) · {{ $detail['client'] }}@endif</div>
                        <h2 id="onb-title" class="font-outfit font-bold text-xl text-slate-800 dark:text-white truncate">{{ $detail['company'] }}</h2>
                        @if(! $detail['linked'])
                            <div class="mt-1 text-[11px] font-semibold text-amber-600 dark:text-amber-400"><i class="fa-solid fa-link-slash mr-0.5"></i>Not linked to a company: open the task and choose Client &amp; Company</div>
                        @endif
                    </div>
                    <button type="button" wire:click="closeDetail" class="p-2 -mr-2 rounded-xl text-slate-400 hover:text-slate-600 dark:hover:text-slate-200 hover:bg-slate-100 dark:hover:bg-slate-800 transition" aria-label="Close">
                        <i class="fa-solid fa-xmark text-lg"></i>
                    </button>
                </div>

                <div class="flex-1 overflow-y-auto px-6 py-5 space-y-6">
                    <x-flash-message />

                    {{-- Status + current status --}}
                    <section class="space-y-3">
                        <div class="grid grid-cols-2 gap-3">
                            <div>
                                <label class="block text-[10px] font-bold uppercase tracking-wider text-slate-400 mb-1.5">Status</label>
                                @if($canEdit)
                                    <select wire:change="setStatus({{ $detail['primary_task_id'] }}, $event.target.value)" class="w-full px-3 py-2 rounded-xl border text-xs font-semibold focus:outline-none focus:ring-2 focus:ring-sky-500/30 {{ $statusTones[$detail['status']] ?? $statusTones['Not started'] }}">
                                        @foreach($statuses as $status)
                                            <option value="{{ $status }}" @selected($detail['status'] === $status)>{{ $status }}</option>
                                        @endforeach
                                    </select>
                                @else
                                    <span class="inline-flex px-2.5 py-1.5 rounded-xl border text-xs font-semibold {{ $statusTones[$detail['status']] ?? $statusTones['Not started'] }}">{{ $detail['status'] }}</span>
                                @endif
                            </div>
                            <div>
                                <label class="block text-[10px] font-bold uppercase tracking-wider text-slate-400 mb-1.5">KYB responsible</label>
                                <div class="py-2 text-sm text-slate-700 dark:text-slate-200">{{ $detail['responsible']->isNotEmpty() ? $names($detail['responsible']) : 'Unassigned' }}</div>
                            </div>
                        </div>

                        <div>
                            <label class="block text-[10px] font-bold uppercase tracking-wider text-slate-400 mb-1.5">Current status</label>
                            @if($canEdit)
                                <textarea rows="4" wire:change="saveCurrentStatus({{ $detail['primary_task_id'] }}, $event.target.value)" placeholder="Latest news, provider replies, what we wait for..." class="{{ $input }} w-full resize-y">{{ $detail['current_status'] }}</textarea>
                                <p class="mt-1 text-[10px] text-slate-400">Saved automatically when you leave the field.</p>
                            @else
                                <p class="text-sm text-slate-700 dark:text-slate-200 whitespace-pre-line">{{ $detail['current_status'] ?: '—' }}</p>
                            @endif
                        </div>
                    </section>

                    {{-- Company facts --}}
                    <section class="grid grid-cols-2 gap-x-4 gap-y-4 text-sm">
                        <div>
                            <div class="text-[10px] font-bold uppercase tracking-wider text-slate-400 mb-1">UBO / Director</div>
                            <div class="text-slate-700 dark:text-slate-200">{{ $detail['director'] ?? '—' }}</div>
                        </div>
                        <div>
                            <div class="text-[10px] font-bold uppercase tracking-wider text-slate-400 mb-1">Director Curator</div>
                            <div class="text-slate-700 dark:text-slate-200">{{ $detail['curator'] ?: '—' }}</div>
                        </div>
                        <div class="col-span-2">
                            <div class="text-[10px] font-bold uppercase tracking-wider text-slate-400 mb-1">Websites</div>
                            @forelse($detail['websites'] as $website)
                                <div class="flex items-center justify-between gap-3 py-0.5">
                                    <a href="{{ $website->url }}" target="_blank" rel="noopener" class="text-indigo-600 dark:text-indigo-400 hover:underline truncate">{{ $host($website->url) }}</a>
                                    <span class="text-[11px] text-slate-400 shrink-0">{{ $website->responsible?->name ?? 'No responsible' }}</span>
                                </div>
                            @empty
                                <div class="text-slate-400">—</div>
                            @endforelse
                        </div>
                        <div class="col-span-2">
                            <div class="text-[10px] font-bold uppercase tracking-wider text-slate-400 mb-1.5">Providers @if($canEdit && $detail['linked']) <span class="normal-case font-normal tracking-normal text-slate-400">· click a status to change it</span>@endif</div>
                            @include('onboarding.providers', ['providers' => $detail['providers'], 'projectId' => $detail['project']?->id, 'canEdit' => $canEdit && $detail['linked']])
                        </div>
                    </section>

                    {{-- Tasks --}}
                    <section>
                        <div class="text-[10px] font-bold uppercase tracking-wider text-slate-400 mb-2">Tasks</div>
                        <ul class="space-y-2">
                            @foreach($detail['tasks'] as $task)
                                <li>
                                    <a href="{{ route('tasks.kanban', ['task_id' => $task->id]) }}" wire:navigate class="flex items-center gap-3 p-3 rounded-xl border border-slate-100 dark:border-slate-800 hover:border-sky-300 dark:hover:border-sky-700 hover:bg-slate-50 dark:hover:bg-slate-800/40 transition">
                                        <span class="shrink-0 px-1.5 py-0.5 rounded text-[9px] font-bold uppercase tracking-wider bg-indigo-50 text-indigo-700 dark:bg-indigo-950/30 dark:text-indigo-400">{{ $taskTypes[$task->type] ?? $task->type }}</span>
                                        <div class="min-w-0 flex-1">
                                            <div class="text-sm font-medium text-slate-700 dark:text-slate-200 truncate">{{ $task->title }}</div>
                                            <div class="text-[11px] text-slate-400 truncate">
                                                {{ $task->onboarding_status ?: 'Not started' }} ·
                                                {{ $task->assignees->isNotEmpty() ? $names($task->assignees) : ($task->assignee?->name ?? 'Unassigned') }}
                                            </div>
                                        </div>
                                        <span class="shrink-0 text-xs font-semibold text-sky-600 dark:text-sky-400">Open task <i class="fa-solid fa-arrow-up-right-from-square ml-0.5 text-[10px]"></i></span>
                                    </a>
                                </li>
                            @endforeach
                        </ul>

                        @if($canEdit && $detail['linked'] && (! $detail['kyb_task_id'] || ! $detail['onboarding_task_id']))
                            <div class="flex flex-wrap gap-2 mt-3">
                                @unless($detail['kyb_task_id'])
                                    <a href="{{ route('tasks.kanban', ['new' => 'kyb', 'project_id' => $detail['project']->id]) }}" wire:navigate class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-xs font-semibold text-slate-600 dark:text-slate-300 border border-dashed border-slate-300 dark:border-slate-700 hover:bg-slate-50 dark:hover:bg-slate-800 transition"><i class="fa-solid fa-plus"></i> KYB task</a>
                                @endunless
                                @unless($detail['onboarding_task_id'])
                                    <a href="{{ route('tasks.kanban', ['new' => 'onboarding', 'project_id' => $detail['project']->id]) }}" wire:navigate class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-xs font-semibold text-slate-600 dark:text-slate-300 border border-dashed border-slate-300 dark:border-slate-700 hover:bg-slate-50 dark:hover:bg-slate-800 transition"><i class="fa-solid fa-plus"></i> Onboarding task</a>
                                @endunless
                            </div>
                        @endif
                    </section>

                    {{-- History --}}
                    <section>
                        <div class="text-[10px] font-bold uppercase tracking-wider text-slate-400 mb-2">History</div>
                        @include('onboarding.history', ['history' => $detailHistory])
                    </section>

                    {{-- Comments --}}
                    <section>
                        <div class="text-[10px] font-bold uppercase tracking-wider text-slate-400 mb-2">Latest comments</div>
                        <ul class="space-y-3">
                            @forelse($detailComments as $comment)
                                <li class="text-sm">
                                    <div class="text-[11px] text-slate-400"><span class="font-semibold text-slate-600 dark:text-slate-300">{{ $comment->user?->name }}</span> · {{ $comment->created_at->format('d.m.Y H:i') }}</div>
                                    <p class="text-slate-700 dark:text-slate-200 whitespace-pre-line">{{ \Illuminate\Support\Str::limit(trim(strip_tags(html_entity_decode((string) $comment->content))), 300) }}</p>
                                </li>
                            @empty
                                <li class="text-sm text-slate-400">No comments yet. Add them in the task.</li>
                            @endforelse
                        </ul>
                    </section>
                </div>

                {{-- Footer --}}
                <div class="flex items-center justify-between gap-3 px-6 py-4 border-t border-slate-100 dark:border-slate-800/60 bg-slate-50/60 dark:bg-slate-950/30">
                    @if($detail['linked'])
                        <a href="{{ route('projects.show', $detail['project']) }}" wire:navigate class="text-xs font-semibold text-slate-600 dark:text-slate-300 hover:text-sky-600 dark:hover:text-sky-400"><i class="fa-solid fa-building mr-1"></i>Open company</a>
                    @else
                        <span></span>
                    @endif
                    <a href="{{ route('tasks.kanban', ['task_id' => $detail['primary_task_id']]) }}" wire:navigate class="inline-flex items-center gap-2 px-4 py-2.5 rounded-xl text-xs font-bold text-white bg-gradient-to-r from-sky-500 to-indigo-600 hover:from-sky-400 hover:to-indigo-500 shadow-md shadow-indigo-500/20 transition">
                        Open task <i class="fa-solid fa-arrow-right"></i>
                    </a>
                </div>
            </aside>
        </div>
    @endif
</div>
