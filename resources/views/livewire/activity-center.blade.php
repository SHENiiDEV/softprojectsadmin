<div class="space-y-6">
    {{-- Header --}}
    <div class="flex items-center justify-between">
        <h2 class="text-2xl font-bold text-slate-800 dark:text-slate-200">Activity Center</h2>
    </div>

    {{-- Filters --}}
    <div class="flex flex-wrap gap-3">
        <select wire:model.live="filterProject"
                class="rounded-xl bg-white dark:bg-slate-800 border border-slate-300 dark:border-slate-600 px-3 py-1.5 text-sm text-slate-700 dark:text-slate-200 focus:outline-none focus:ring-2 focus:ring-indigo-500">
            <option value="">All Companies</option>
            @foreach($projects as $p)
                <option value="{{ $p->id }}">{{ $p->name }}</option>
            @endforeach
        </select>

        <select wire:model.live="filterUser"
                class="rounded-xl bg-white dark:bg-slate-800 border border-slate-300 dark:border-slate-600 px-3 py-1.5 text-sm text-slate-700 dark:text-slate-200 focus:outline-none focus:ring-2 focus:ring-indigo-500">
            <option value="">All Users</option>
            @foreach($users as $u)
                <option value="{{ $u->id }}">{{ $u->name }}</option>
            @endforeach
        </select>

        <select wire:model.live="filterType"
                class="rounded-xl bg-white dark:bg-slate-800 border border-slate-300 dark:border-slate-600 px-3 py-1.5 text-sm text-slate-700 dark:text-slate-200 focus:outline-none focus:ring-2 focus:ring-indigo-500">
            <option value="">All Actions</option>
            <option value="task_created">Task Created</option>
            <option value="task_assigned">Task Assigned</option>
            <option value="task_claimed">Task Claimed</option>
            <option value="task_unassigned">Task Unassigned</option>
            <option value="task_status_updated">Task Status Updated</option>
            <option value="task_updated">Task Updated</option>
        </select>
    </div>

    {{-- Activity feed --}}
    <div class="space-y-2">
        @forelse($activities as $activity)
            @php
                $icons = [
                    'task_created'       => ['icon' => '➕', 'color' => 'bg-green-100 text-green-700 dark:bg-green-900 dark:text-green-300'],
                    'task_assigned'      => ['icon' => '👤', 'color' => 'bg-blue-100 text-blue-700 dark:bg-blue-900 dark:text-blue-300'],
                    'task_claimed'       => ['icon' => '🙋', 'color' => 'bg-indigo-100 text-indigo-700 dark:bg-indigo-900 dark:text-indigo-300'],
                    'task_unassigned'    => ['icon' => '➖', 'color' => 'bg-slate-100 text-slate-600 dark:bg-slate-700 dark:text-slate-300'],
                    'task_status_updated'=> ['icon' => '🔄', 'color' => 'bg-yellow-100 text-yellow-700 dark:bg-yellow-900 dark:text-yellow-300'],
                    'task_updated'       => ['icon' => '✏️', 'color' => 'bg-purple-100 text-purple-700 dark:bg-purple-900 dark:text-purple-300'],
                ];
                $meta = $icons[$activity->action] ?? ['icon' => '📋', 'color' => 'bg-slate-100 text-slate-600 dark:bg-slate-700 dark:text-slate-300'];
            @endphp
            <div class="flex items-start gap-3 p-3 bg-white dark:bg-slate-800 rounded-xl shadow-sm border border-slate-100 dark:border-slate-700 hover:shadow-md transition">
                <span class="flex-shrink-0 w-8 h-8 flex items-center justify-center rounded-full text-sm {{ $meta['color'] }}">
                    {{ $meta['icon'] }}
                </span>
                <div class="flex-1 min-w-0">
                    <p class="text-sm text-slate-700 dark:text-slate-200">{{ $activity->description }}</p>
                    <div class="flex flex-wrap gap-2 mt-1">
                        @if($activity->project)
                            <span class="text-xs text-indigo-600 dark:text-indigo-400 font-medium">{{ $activity->project->name }}</span>
                        @endif
                        @if($activity->user)
                            <span class="text-xs text-slate-500 dark:text-slate-400">· {{ $activity->user->name }}</span>
                        @endif
                        <span class="text-xs text-slate-400 dark:text-slate-500">· {{ $activity->created_at->diffForHumans() }}</span>
                    </div>
                </div>
            </div>
        @empty
            <div class="flex flex-col items-center justify-center py-16 text-center">
                <div class="text-5xl mb-4">📋</div>
                <p class="text-slate-500 dark:text-slate-400 font-medium">No activity found.</p>
                <p class="text-slate-400 dark:text-slate-500 text-sm mt-1">Activities will appear here as actions are performed in the system.</p>
            </div>
        @endforelse
    </div>
</div>
