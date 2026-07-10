<div class="space-y-6">
    <x-slot name="header">Productivity Report</x-slot>

    <!-- Header -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <h1 class="font-outfit font-extrabold text-2xl text-slate-800 dark:text-white tracking-tight">Productivity Report</h1>
            <p class="text-sm text-slate-500 dark:text-slate-400 mt-1">Team performance metrics by period.</p>
        </div>
    </div>

    <!-- Filters -->
    <div class="bg-white dark:bg-slate-900 border border-slate-100 dark:border-slate-800/80 rounded-2xl p-5 shadow-sm">
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
                <label class="block text-xs font-semibold text-slate-400 dark:text-slate-500 uppercase tracking-wider mb-2">Period</label>
                <select wire:model.live="period" class="w-full px-3.5 py-2.5 bg-slate-50 dark:bg-slate-950 border border-slate-200 dark:border-slate-800/80 rounded-xl text-xs text-slate-800 dark:text-slate-100 focus:outline-none focus:ring-2 focus:ring-sky-500/20 focus:border-sky-500 transition-all">
                    <option value="week">This Week</option>
                    <option value="month">This Month</option>
                    <option value="quarter">This Quarter</option>
                </select>
            </div>
            <div>
                <label class="block text-xs font-semibold text-slate-400 dark:text-slate-500 uppercase tracking-wider mb-2">Team Member</label>
                <select wire:model.live="userId" class="w-full px-3.5 py-2.5 bg-slate-50 dark:bg-slate-950 border border-slate-200 dark:border-slate-800/80 rounded-xl text-xs text-slate-800 dark:text-slate-100 focus:outline-none focus:ring-2 focus:ring-sky-500/20 focus:border-sky-500 transition-all">
                    <option value="">All Team Members</option>
                    @foreach($users as $u)
                        <option value="{{ $u->id }}">{{ $u->name }}</option>
                    @endforeach
                </select>
            </div>
        </div>
        <p class="text-xs text-slate-400 mt-3">Period: {{ $from->format('d M Y') }} — {{ $to->format('d M Y') }}</p>
    </div>

    <!-- Summary Stats -->
    <div class="grid grid-cols-1 sm:grid-cols-3 gap-6">
        <div class="bg-white dark:bg-slate-900 border border-slate-100 dark:border-slate-800/80 p-6 rounded-2xl shadow-sm">
            <div class="flex items-center justify-between">
                <span class="text-sm font-semibold text-slate-400">Tasks Completed</span>
                <span class="p-2.5 rounded-xl bg-emerald-50 dark:bg-emerald-950/40 text-emerald-600 dark:text-emerald-400"><i class="fa-solid fa-check-double"></i></span>
            </div>
            <h3 class="font-outfit font-bold text-3xl text-slate-700 dark:text-slate-100 mt-4">{{ $totalTasksDone }}</h3>
            <p class="text-xs text-slate-400 mt-1">In selected period</p>
        </div>
        <div class="bg-white dark:bg-slate-900 border border-slate-100 dark:border-slate-800/80 p-6 rounded-2xl shadow-sm">
            <div class="flex items-center justify-between">
                <span class="text-sm font-semibold text-slate-400">Total Hours Logged</span>
                <span class="p-2.5 rounded-xl bg-sky-50 dark:bg-sky-950/40 text-sky-600 dark:text-sky-400"><i class="fa-solid fa-clock"></i></span>
            </div>
            <h3 class="font-outfit font-bold text-3xl text-slate-700 dark:text-slate-100 mt-4">{{ $totalHours }}h</h3>
            <p class="text-xs text-slate-400 mt-1">Accumulated time</p>
        </div>
        <div class="bg-white dark:bg-slate-900 border border-slate-100 dark:border-slate-800/80 p-6 rounded-2xl shadow-sm">
            <div class="flex items-center justify-between">
                <span class="text-sm font-semibold text-slate-400">Overdue Tasks</span>
                <span class="p-2.5 rounded-xl bg-rose-50 dark:bg-rose-950/40 text-rose-600 dark:text-rose-400"><i class="fa-solid fa-triangle-exclamation"></i></span>
            </div>
            <h3 class="font-outfit font-bold text-3xl text-slate-700 dark:text-slate-100 mt-4">{{ $totalOverdue }}</h3>
            <p class="text-xs text-slate-400 mt-1">Across all team members</p>
        </div>
    </div>

    <!-- Team Leaderboard Table -->
    <div class="bg-white dark:bg-slate-900 border border-slate-100 dark:border-slate-800/80 rounded-2xl p-6 shadow-sm">
        <h2 class="font-outfit font-bold text-lg text-slate-800 dark:text-white mb-5">Team Leaderboard</h2>
        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse">
                <thead>
                    <tr class="text-slate-400 dark:text-slate-500 text-xs font-semibold uppercase border-b border-slate-100 dark:border-slate-800/60">
                        <th class="py-3 px-4">#</th>
                        <th class="py-3 px-4">Member</th>
                        <th class="py-3 px-4 text-center">Completed</th>
                        <th class="py-3 px-4 text-center">Total Tasks</th>
                        <th class="py-3 px-4 text-center">Completion %</th>
                        <th class="py-3 px-4 text-center">Hours</th>
                        <th class="py-3 px-4 text-center">Overdue</th>
                        <th class="py-3 px-4 text-right">Score</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 dark:divide-slate-800/40 text-sm">
                    @forelse($userStats as $index => $row)
                        <tr class="hover:bg-slate-50/50 dark:hover:bg-slate-800/10 transition-colors">
                            <td class="py-3.5 px-4">
                                @if($index === 0)
                                    <span class="text-amber-500 font-bold">🥇</span>
                                @elseif($index === 1)
                                    <span class="text-slate-400 font-bold">🥈</span>
                                @elseif($index === 2)
                                    <span class="text-amber-700 font-bold">🥉</span>
                                @else
                                    <span class="text-slate-400 text-xs">{{ $index + 1 }}</span>
                                @endif
                            </td>
                            <td class="py-3.5 px-4">
                                <div class="flex items-center gap-2">
                                    <div class="h-7 w-7 rounded-full bg-gradient-to-tr from-sky-400 to-indigo-500 text-white font-bold flex items-center justify-center text-xs uppercase">
                                        {{ substr($row['user']->name, 0, 2) }}
                                    </div>
                                    <span class="font-semibold text-slate-800 dark:text-slate-200">{{ $row['user']->name }}</span>
                                </div>
                            </td>
                            <td class="py-3.5 px-4 text-center font-semibold text-emerald-600 dark:text-emerald-400">{{ $row['tasks_completed'] }}</td>
                            <td class="py-3.5 px-4 text-center text-slate-600 dark:text-slate-400">{{ $row['tasks_total'] }}</td>
                            <td class="py-3.5 px-4">
                                <div class="flex items-center gap-2">
                                    <div class="flex-1 bg-slate-100 dark:bg-slate-800 rounded-full h-1.5">
                                        <div class="h-1.5 rounded-full {{ $row['completion_rate'] >= 70 ? 'bg-emerald-500' : ($row['completion_rate'] >= 40 ? 'bg-amber-500' : 'bg-rose-500') }}"
                                             style="width: {{ $row['completion_rate'] }}%"></div>
                                    </div>
                                    <span class="text-xs font-semibold text-slate-600 dark:text-slate-400 w-8">{{ $row['completion_rate'] }}%</span>
                                </div>
                            </td>
                            <td class="py-3.5 px-4 text-center text-sky-600 dark:text-sky-400 font-semibold">{{ $row['hours_logged'] }}h</td>
                            <td class="py-3.5 px-4 text-center">
                                @if($row['overdue'] > 0)
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-semibold bg-rose-50 text-rose-600 dark:bg-rose-950/30 dark:text-rose-400">{{ $row['overdue'] }}</span>
                                @else
                                    <span class="text-slate-400">—</span>
                                @endif
                            </td>
                            <td class="py-3.5 px-4 text-right">
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-bold
                                    {{ $row['score'] >= 50 ? 'bg-emerald-50 text-emerald-700 dark:bg-emerald-950/30 dark:text-emerald-400' : ($row['score'] >= 20 ? 'bg-amber-50 text-amber-700 dark:bg-amber-950/30 dark:text-amber-400' : 'bg-slate-100 text-slate-500 dark:bg-slate-800 dark:text-slate-400') }}">
                                    {{ $row['score'] }} pts
                                </span>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="py-10 text-center text-slate-400">No data for this period.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
