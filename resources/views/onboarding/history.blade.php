@php
    $historyIcons = [
        'onboarding_status_updated' => ['fa-arrow-right-arrow-left', 'text-sky-500'],
        'onboarding_current_status_updated' => ['fa-pen', 'text-indigo-500'],
        'task_created' => ['fa-plus', 'text-emerald-500'],
        'task_assigned' => ['fa-user-plus', 'text-amber-500'],
        'task_unassigned' => ['fa-user-minus', 'text-rose-500'],
    ];
@endphp

@if($history->isEmpty())
    <p class="text-sm text-slate-400">No changes recorded yet.</p>
@else
    <ol class="relative space-y-3 border-l border-slate-200 dark:border-slate-800 ml-2 pl-5">
        @foreach($history as $entry)
            @php
                [$icon, $iconTone] = $historyIcons[$entry->action] ?? ['fa-circle', 'text-slate-400'];
            @endphp
            <li class="relative">
                <span class="absolute -left-[31px] top-0.5 h-5 w-5 rounded-full bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-700 flex items-center justify-center">
                    <i class="fa-solid {{ $icon }} {{ $iconTone }} text-[9px]"></i>
                </span>
                <p class="text-xs leading-snug text-slate-700 dark:text-slate-200">{{ $entry->description }}</p>
                <p class="text-[10px] text-slate-400 mt-0.5" title="{{ $entry->created_at->format('d.m.Y H:i:s') }}">
                    {{ $entry->user?->name ?? 'System' }} · {{ $entry->created_at->diffForHumans() }}
                </p>
            </li>
        @endforeach
    </ol>
@endif
