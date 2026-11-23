@php
    $statusTones = [
        'Not started' => 'bg-slate-100 text-slate-600 border-slate-200 dark:bg-slate-800 dark:text-slate-300 dark:border-slate-700',
        'In progress' => 'bg-sky-50 text-sky-700 border-sky-200 dark:bg-sky-950/30 dark:text-sky-400 dark:border-sky-900/50',
        'KYB in process' => 'bg-indigo-50 text-indigo-700 border-indigo-200 dark:bg-indigo-950/30 dark:text-indigo-400 dark:border-indigo-900/50',
        'Waiting for update' => 'bg-amber-50 text-amber-700 border-amber-200 dark:bg-amber-950/30 dark:text-amber-400 dark:border-amber-900/50',
        'Waiting for website completion' => 'bg-orange-50 text-orange-700 border-orange-200 dark:bg-orange-950/30 dark:text-orange-400 dark:border-orange-900/50',
        'Blocked' => 'bg-rose-50 text-rose-700 border-rose-200 dark:bg-rose-950/30 dark:text-rose-400 dark:border-rose-900/50',
        'Under review' => 'bg-violet-50 text-violet-700 border-violet-200 dark:bg-violet-950/30 dark:text-violet-400 dark:border-violet-900/50',
        'Approved' => 'bg-emerald-50 text-emerald-700 border-emerald-200 dark:bg-emerald-950/30 dark:text-emerald-400 dark:border-emerald-900/50',
        'Rejected' => 'bg-rose-50 text-rose-700 border-rose-200 dark:bg-rose-950/30 dark:text-rose-400 dark:border-rose-900/50',
        'On hold' => 'bg-slate-100 text-slate-600 border-slate-200 dark:bg-slate-800 dark:text-slate-300 dark:border-slate-700',
        'Completed' => 'bg-emerald-50 text-emerald-700 border-emerald-200 dark:bg-emerald-950/30 dark:text-emerald-400 dark:border-emerald-900/50',
    ];
    $providerTones = [
        'emerald' => 'bg-emerald-50 text-emerald-700 border-emerald-200 dark:bg-emerald-950/30 dark:text-emerald-400 dark:border-emerald-900/50',
        'sky' => 'bg-sky-50 text-sky-700 border-sky-200 dark:bg-sky-950/30 dark:text-sky-400 dark:border-sky-900/50',
        'amber' => 'bg-amber-50 text-amber-700 border-amber-200 dark:bg-amber-950/30 dark:text-amber-400 dark:border-amber-900/50',
        'rose' => 'bg-rose-50 text-rose-700 border-rose-200 dark:bg-rose-950/30 dark:text-rose-400 dark:border-rose-900/50',
        'slate' => 'bg-slate-100 text-slate-600 border-slate-200 dark:bg-slate-800 dark:text-slate-300 dark:border-slate-700',
    ];
    $input = 'px-3.5 py-2 bg-slate-50 dark:bg-slate-950 border border-slate-200 dark:border-slate-800/80 rounded-xl text-xs text-slate-800 dark:text-slate-100 focus:outline-none focus:ring-2 focus:ring-sky-500/20 focus:border-sky-500 transition-all duration-200';
    $hasFilters = $search !== '' || $filterStatus !== '' || $filterResponsible !== '' || $filterClient !== '';
@endphp

<div class="space-y-6 animate-fade-in">
    <x-flash-message />

    {{-- Header --}}
    <div class="flex flex-col md:flex-row md:items-end md:justify-between gap-4">
        <div>
            <h1 class="font-outfit font-extrabold text-2xl md:text-3xl text-slate-800 dark:text-white tracking-tight">Onboarding Status</h1>
            <p class="text-sm text-slate-500 dark:text-slate-400 mt-1">
                Built from tasks of type <span class="font-semibold">Onboarding</span> and <span class="font-semibold">KYB</span>. One row per company.
            </p>
        </div>
        <a href="{{ route('tasks.kanban', ['type' => 'onboarding']) }}" wire:navigate class="inline-flex items-center gap-2 px-4 py-2.5 rounded-xl text-xs font-semibold text-slate-700 dark:text-slate-200 bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 hover:bg-slate-50 dark:hover:bg-slate-800 transition">
            <i class="fa-solid fa-list-check"></i> Onboarding tasks in Kanban
        </a>
    </div>

    {{-- Status overview: every chip filters the table --}}
    <div class="flex flex-wrap gap-2">
        <button type="button" wire:click="$set('filterStatus', '')" class="px-3 py-1.5 rounded-lg border text-xs font-semibold transition {{ $filterStatus === '' ? 'bg-slate-800 text-white border-slate-800 dark:bg-white dark:text-slate-900 dark:border-white' : 'bg-white text-slate-600 border-slate-200 hover:bg-slate-50 dark:bg-slate-900 dark:text-slate-300 dark:border-slate-800' }}">
            All <span class="ml-1 font-outfit font-bold">{{ $total }}</span>
        </button>
        @foreach($statuses as $status)
            @if(($statusCounts[$status] ?? 0) > 0 || $filterStatus === $status)
                <button type="button" wire:click="$set('filterStatus', '{{ $filterStatus === $status ? '' : $status }}')" class="px-3 py-1.5 rounded-lg border text-xs font-semibold transition {{ $statusTones[$status] }} {{ $filterStatus === $status ? 'ring-2 ring-sky-500/40' : 'hover:opacity-80' }}">
                    {{ $status }} <span class="ml-1 font-outfit font-bold">{{ $statusCounts[$status] ?? 0 }}</span>
                </button>
            @endif
        @endforeach
    </div>

    {{-- Filters --}}
    <div class="grid grid-cols-1 md:grid-cols-12 gap-3">
        <div class="md:col-span-4 relative">
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
                    <option value="{{ $person->id }}">{{ $person->name }}</option>
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
        <div class="md:col-span-2">
            @if($hasFilters)
                <button type="button" wire:click="resetFilters" class="w-full px-3.5 py-2 rounded-xl text-xs font-semibold text-slate-600 dark:text-slate-300 bg-slate-100 dark:bg-slate-800 hover:bg-slate-200 dark:hover:bg-slate-700 transition">Reset filters</button>
            @endif
        </div>
    </div>

    {{-- Table --}}
    <div class="bg-white dark:bg-slate-900 border border-slate-100 dark:border-slate-800/80 rounded-2xl shadow-sm overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse min-w-[1250px]">
                <thead>
                    <tr class="text-[11px] font-semibold uppercase tracking-wider text-slate-400 dark:text-slate-500 border-b border-slate-100 dark:border-slate-800/60 bg-slate-50/60 dark:bg-slate-950/30">
                        <th class="py-3.5 px-4">ID</th>
                        <th class="py-3.5 px-4">Client</th>
                        <th class="py-3.5 px-4">Company</th>
                        <th class="py-3.5 px-4">UBO / Director</th>
                        <th class="py-3.5 px-4">Director Curator</th>
                        <th class="py-3.5 px-4">Website</th>
                        <th class="py-3.5 px-4">Providers</th>
                        <th class="py-3.5 px-4">Status</th>
                        <th class="py-3.5 px-4">KYB responsible</th>
                        <th class="py-3.5 px-4 w-[26%]">Current status</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 dark:divide-slate-800/40 text-sm align-top">
                    @forelse($rows as $row)
                        <tr wire:key="onb-{{ $row['code'] }}" class="hover:bg-slate-50/50 dark:hover:bg-slate-800/10 transition-colors">
                            <td class="py-3.5 px-4 whitespace-nowrap font-mono text-xs font-semibold text-slate-500 dark:text-slate-400">{{ $row['code'] }}</td>

                            <td class="py-3.5 px-4 text-slate-700 dark:text-slate-200">{{ $row['client'] ?? '—' }}</td>

                            <td class="py-3.5 px-4">
                                @if($row['linked'])
                                    <a href="{{ route('projects.show', $row['project']) }}" wire:navigate class="font-semibold text-slate-800 dark:text-white hover:text-sky-600 dark:hover:text-sky-400 transition-colors">{{ $row['company'] }}</a>
                                @else
                                    <span class="font-semibold text-slate-800 dark:text-white">{{ $row['company'] }}</span>
                                    <span class="mt-1 flex items-start gap-1 text-[10px] font-semibold text-amber-600 dark:text-amber-400" title="Open the task and choose Client &amp; Company">
                                        <i class="fa-solid fa-link-slash mt-0.5"></i> Not linked to a company
                                    </span>
                                @endif
                                <div class="flex gap-2 mt-1 text-[10px] text-slate-400">
                                    @if($row['onboarding_task_id'])
                                        <a href="{{ route('tasks.kanban', ['task_id' => $row['onboarding_task_id']]) }}" wire:navigate class="hover:text-sky-500"><i class="fa-solid fa-clipboard-check mr-0.5"></i>Onboarding task</a>
                                    @endif
                                    @if($row['kyb_task_id'])
                                        <a href="{{ route('tasks.kanban', ['task_id' => $row['kyb_task_id']]) }}" wire:navigate class="hover:text-sky-500"><i class="fa-solid fa-file-shield mr-0.5"></i>KYB task</a>
                                    @endif
                                </div>
                            </td>

                            <td class="py-3.5 px-4 text-slate-700 dark:text-slate-200">{{ $row['director'] ?? '—' }}</td>

                            <td class="py-3.5 px-4 text-slate-700 dark:text-slate-200">{{ $row['curator'] ?: '—' }}</td>

                            <td class="py-3.5 px-4 text-xs">
                                @forelse($row['websites']->take(3) as $website)
                                    <div class="mb-1">
                                        <a href="{{ $website->url }}" target="_blank" rel="noopener" class="text-indigo-600 dark:text-indigo-400 hover:underline break-all">{{ preg_replace('#^https?://(www\.)?#i', '', rtrim((string) $website->url, '/')) }}</a>
                                        @if($website->responsible)
                                            <span class="block text-[10px] text-slate-400"><i class="fa-solid fa-user-tie mr-0.5"></i>{{ $website->responsible->name }}</span>
                                        @endif
                                    </div>
                                @empty
                                    <span class="text-slate-400">—</span>
                                @endforelse
                                @if($row['websites']->count() > 3)
                                    <span class="text-[10px] text-slate-400">+{{ $row['websites']->count() - 3 }} more</span>
                                @endif
                            </td>

                            <td class="py-3.5 px-4">
                                <div class="flex flex-col gap-1 items-start">
                                    @forelse($row['providers'] as $provider)
                                        <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-md border text-[11px] font-semibold {{ $providerTones[$provider['tone']] }}">
                                            {{ $provider['name'] }}<span class="font-normal opacity-80">· {{ $provider['status'] }}</span>
                                        </span>
                                    @empty
                                        <span class="text-slate-400 text-xs">—</span>
                                    @endforelse
                                </div>
                            </td>

                            <td class="py-3.5 px-4 whitespace-nowrap">
                                @if($canEdit)
                                    <select wire:change="setStatus({{ $row['primary_task_id'] }}, $event.target.value)" class="px-2.5 py-1.5 rounded-lg border text-xs font-semibold focus:outline-none focus:ring-2 focus:ring-sky-500/30 {{ $statusTones[$row['status']] ?? $statusTones['Not started'] }}" aria-label="Onboarding status">
                                        @foreach($statuses as $status)
                                            <option value="{{ $status }}" @selected($row['status'] === $status)>{{ $status }}</option>
                                        @endforeach
                                    </select>
                                @else
                                    <span class="inline-flex px-2.5 py-1 rounded-lg border text-xs font-semibold {{ $statusTones[$row['status']] ?? $statusTones['Not started'] }}">{{ $row['status'] }}</span>
                                @endif

                                @if($row['kyb_status'] && $row['kyb_task_id'] !== $row['primary_task_id'])
                                    <div class="text-[10px] text-slate-400 mt-1">KYB: {{ $row['kyb_status'] }}</div>
                                @endif
                            </td>

                            <td class="py-3.5 px-4 text-slate-700 dark:text-slate-200">
                                @forelse($row['responsible'] as $person)
                                    <div class="text-sm">{{ $person->name }}</div>
                                @empty
                                    <span class="text-slate-400">Unassigned</span>
                                @endforelse
                            </td>

                            <td class="py-3.5 px-4">
                                @if($canEdit)
                                    <textarea rows="3" wire:change="saveCurrentStatus({{ $row['primary_task_id'] }}, $event.target.value)" placeholder="Latest news, provider replies, what we wait for..." class="{{ $input }} w-full resize-y">{{ $row['current_status'] }}</textarea>
                                @else
                                    <p class="text-xs text-slate-700 dark:text-slate-200 whitespace-pre-line">{{ $row['current_status'] ?: '—' }}</p>
                                @endif

                                @if(! $row['current_status'] && $row['description'] !== '')
                                    <p class="mt-1.5 text-[11px] leading-snug text-slate-400 dark:text-slate-500"><i class="fa-solid fa-align-left mr-0.5"></i>{{ $row['description'] }}</p>
                                @endif

                                @if($row['comment'])
                                    <p class="mt-2 text-[11px] leading-snug text-slate-400 dark:text-slate-500">
                                        <i class="fa-regular fa-comment mr-0.5"></i>
                                        <span class="font-semibold">{{ $row['comment']->user?->name }}</span>
                                        · {{ $row['comment']->created_at->format('d.m.Y') }}:
                                        {{ \Illuminate\Support\Str::limit(strip_tags((string) $row['comment']->content), 140) }}
                                    </p>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="10" class="py-14 text-center text-sm text-slate-400">
                                @if($hasFilters)
                                    Nothing matches these filters.
                                @else
                                    <i class="fa-solid fa-clipboard-check text-2xl mb-3 block"></i>
                                    No onboarding yet. Create a task with type <span class="font-semibold">KYB</span> or <span class="font-semibold">Onboarding</span> for a company in the Kanban board and it will appear here.
                                @endif
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
