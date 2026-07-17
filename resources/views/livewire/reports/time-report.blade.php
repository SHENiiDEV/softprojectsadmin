<div class="space-y-6">
    <x-slot name="header">
        Time Reports
    </x-slot>

    <!-- Header Actions -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <h1 class="font-outfit font-extrabold text-2xl text-slate-800 dark:text-white tracking-tight">Time Reports</h1>
            <p class="text-sm text-slate-500 dark:text-slate-400 mt-1">Review accumulated work hours by team members and tasks.</p>
        </div>
    </div>

    <!-- Filters Bar -->
    <div class="bg-white dark:bg-slate-900 border border-slate-100 dark:border-slate-800/80 rounded-2xl p-5 shadow-sm">
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <!-- User Filter -->
            <div>
                <label for="filter-user" class="block text-xs font-semibold text-slate-400 dark:text-slate-500 uppercase tracking-wider mb-2">Team Member</label>
                <select id="filter-user" wire:model.live="userId" class="w-full px-3.5 py-2.5 bg-slate-50 dark:bg-slate-950 border border-slate-200 dark:border-slate-800/80 rounded-xl text-xs text-slate-800 dark:text-slate-100 focus:outline-none focus:ring-2 focus:ring-sky-500/20 focus:border-sky-500 transition-all duration-150">
                    <option value="">All Team Members</option>
                    @foreach($users as $user)
                        <option value="{{ $user->id }}">{{ $user->name }} ({{ $user->roles->first()?->name ?? 'worker' }})</option>
                    @endforeach
                </select>
            </div>

            <!-- From Date -->
            <div>
                <label for="filter-from-date" class="block text-xs font-semibold text-slate-400 dark:text-slate-500 uppercase tracking-wider mb-2">From Date</label>
                <input id="filter-from-date" type="date" onclick="this.showPicker()" wire:model.live="fromDate" class="w-full px-3.5 py-2.5 bg-slate-50 dark:bg-slate-950 border border-slate-200 dark:border-slate-800/80 rounded-xl text-xs text-slate-800 dark:text-slate-100 focus:outline-none focus:ring-2 focus:ring-sky-500/20 focus:border-sky-500 transition-all duration-150">
            </div>

            <!-- To Date -->
            <div>
                <label for="filter-to-date" class="block text-xs font-semibold text-slate-400 dark:text-slate-500 uppercase tracking-wider mb-2">To Date</label>
                <input id="filter-to-date" type="date" onclick="this.showPicker()" wire:model.live="toDate" class="w-full px-3.5 py-2.5 bg-slate-50 dark:bg-slate-950 border border-slate-200 dark:border-slate-800/80 rounded-xl text-xs text-slate-800 dark:text-slate-100 focus:outline-none focus:ring-2 focus:ring-sky-500/20 focus:border-sky-500 transition-all duration-150">
            </div>
        </div>
    </div>

    <!-- Stats Grid -->
    <div class="grid grid-cols-1 sm:grid-cols-3 gap-6">
        <!-- Total Tracked Time -->
        <div class="relative group overflow-hidden bg-white dark:bg-slate-900 border border-slate-100 dark:border-slate-800/80 p-6 rounded-2xl shadow-sm transition-all duration-300 hover:shadow-md">
            <div class="flex items-center justify-between">
                <span class="text-sm font-semibold text-slate-400 dark:text-slate-500">Total Tracked Time</span>
                <span class="p-2.5 rounded-xl bg-sky-50 dark:bg-sky-950/40 text-sky-700 dark:text-sky-400">
                    <i class="fa-solid fa-clock text-lg"></i>
                </span>
            </div>
            <div class="mt-4">
                <h3 class="font-outfit font-bold text-2xl md:text-3xl text-slate-700 dark:text-slate-100">{{ $totalDurationFormatted }}</h3>
                <p class="text-xs text-slate-400 mt-1">Accumulated work sessions</p>
            </div>
        </div>

        <!-- Total Sessions -->
        <div class="relative group overflow-hidden bg-white dark:bg-slate-900 border border-slate-100 dark:border-slate-800/80 p-6 rounded-2xl shadow-sm transition-all duration-300 hover:shadow-md">
            <div class="flex items-center justify-between">
                <span class="text-sm font-semibold text-slate-400 dark:text-slate-500">Total Work Sessions</span>
                <span class="p-2.5 rounded-xl bg-indigo-50 dark:bg-indigo-950/40 text-indigo-700 dark:text-indigo-400">
                    <i class="fa-solid fa-circle-play text-lg"></i>
                </span>
            </div>
            <div class="mt-4">
                <h3 class="font-outfit font-bold text-2xl md:text-3xl text-slate-700 dark:text-slate-100">{{ $totalSessions }}</h3>
                <p class="text-xs text-slate-400 mt-1">Individual timer entries</p>
            </div>
        </div>

        <!-- Unique Tasks -->
        <div class="relative group overflow-hidden bg-white dark:bg-slate-900 border border-slate-100 dark:border-slate-800/80 p-6 rounded-2xl shadow-sm transition-all duration-300 hover:shadow-md">
            <div class="flex items-center justify-between">
                <span class="text-sm font-semibold text-slate-400 dark:text-slate-500">Unique Tasks Worked On</span>
                <span class="p-2.5 rounded-xl bg-emerald-50 dark:bg-emerald-950/40 text-emerald-700 dark:text-emerald-400">
                    <i class="fa-solid fa-list-check text-lg"></i>
                </span>
            </div>
            <div class="mt-4">
                <h3 class="font-outfit font-bold text-2xl md:text-3xl text-slate-700 dark:text-slate-100">{{ $uniqueTasksCount }}</h3>
                <p class="text-xs text-slate-400 mt-1">Distinct tasks in period</p>
            </div>
        </div>
    </div>

    {{-- Chart Section --}}
    @if(!empty($chartData['labels']))
    <div class="bg-white dark:bg-slate-900 border border-slate-100 dark:border-slate-800/80 rounded-2xl p-6 shadow-sm"
         x-data="{
             labels: @entangle('chartData.labels'),
             hours: @entangle('chartData.hours'),
             chart: null,
             init() {
                 this.$nextTick(() => {
                     this.renderChart();
                 });
                 this.$watch('labels', () => this.renderChart());
                 this.$watch('hours', () => this.renderChart());
             },
             renderChart() {
                 if (this.chart) {
                     this.chart.destroy();
                 }
                 const canvas = document.getElementById('timeReportChart');
                 if (!canvas) return;
                 const ctx = canvas.getContext('2d');
                 const isDark = document.documentElement.classList.contains('dark');
                 const textColor = isDark ? '#94a3b8' : '#64748b';
                 const colors = [
                     'rgba(14, 165, 233, 0.85)',
                     'rgba(99, 102, 241, 0.85)',
                     'rgba(16, 185, 129, 0.85)',
                     'rgba(245, 158, 11, 0.85)',
                     'rgba(239, 68, 68, 0.85)',
                     'rgba(168, 85, 247, 0.85)',
                 ];
                 this.chart = new Chart(ctx, {
                     type: 'doughnut',
                     data: {
                         labels: this.labels,
                         datasets: [{
                             data: this.hours,
                             backgroundColor: this.labels.map((_, i) => colors[i % colors.length]),
                             borderWidth: 2,
                             borderColor: isDark ? '#0f172a' : '#ffffff',
                         }]
                     },
                     options: {
                         responsive: true,
                         maintainAspectRatio: false,
                         cutout: '72%',
                         plugins: {
                             legend: {
                                 display: true,
                                 position: 'bottom',
                                 labels: {
                                     color: textColor,
                                     usePointStyle: true,
                                     pointStyle: 'rectRounded',
                                     padding: 20,
                                     font: {
                                         family: 'Inter, sans-serif',
                                         size: 11
                                     }
                                 }
                             },
                             tooltip: {
                                 backgroundColor: isDark ? '#1e293b' : '#ffffff',
                                 titleColor: isDark ? '#ffffff' : '#0f172a',
                                 bodyColor: isDark ? '#94a3b8' : '#475569',
                                 borderColor: isDark ? '#334155' : '#e2e8f0',
                                 borderWidth: 1,
                                 padding: 10,
                                 callbacks: {
                                     label: ctx => ` ${ctx.label}: ${ctx.raw}h`
                                 }
                             }
                         }
                     }
                 });
             }
         }"
         wire:ignore>
        <h2 class="font-outfit font-bold text-lg text-slate-800 dark:text-white mb-4">
            {{ $userId === '' ? 'Time Distribution by Team Member' : 'Time Distribution by Task' }}
        </h2>
        <div style="height: 320px; position: relative;">
            <canvas id="timeReportChart"></canvas>
        </div>
    </div>
    @endif

    <!-- Main Content Grid -->
    <div class="grid grid-cols-1 lg:grid-cols-12 gap-8">
        <!-- Left: Breakdown Table (7 columns) -->
        <div class="lg:col-span-7 bg-white dark:bg-slate-900 border border-slate-100 dark:border-slate-800/80 rounded-2xl p-6 shadow-sm">
            <div class="pb-5 border-b border-slate-100 dark:border-slate-800/60">
                <h2 class="font-outfit font-bold text-lg text-slate-800 dark:text-white">
                    {{ $userId === '' ? 'Team Breakdown' : 'Task Breakdown' }}
                </h2>
                <p class="text-xs text-slate-400 mt-1">
                    {{ $userId === '' ? 'Time tracked grouped by team member' : 'Time tracked grouped by task for selected user' }}
                </p>
            </div>

            <div class="overflow-x-auto mt-4">
                @if($userId === '')
                    <!-- Team Breakdown Table -->
                    <table class="w-full text-left border-collapse">
                        <thead>
                            <tr class="text-slate-400 dark:text-slate-500 text-xs font-semibold uppercase border-b border-slate-100 dark:border-slate-800/60 font-outfit">
                                <th class="py-3 px-4">Name</th>
                                <th class="py-3 px-4">Role</th>
                                <th class="py-3 px-4 text-center">Sessions</th>
                                <th class="py-3 px-4 text-right">Time Spent</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 dark:divide-slate-800/40 text-sm">
                            @forelse($userBreakdown as $row)
                                <tr class="hover:bg-slate-50/50 dark:hover:bg-slate-800/10 transition-colors duration-150">
                                    <td class="py-3.5 px-4 font-semibold text-slate-800 dark:text-slate-200">
                                        {{ $row['user_name'] }}
                                    </td>
                                    <td class="py-3.5 px-4 text-slate-500 dark:text-slate-400 capitalize">
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-300">
                                            {{ $row['role'] }}
                                        </span>
                                    </td>
                                    <td class="py-3.5 px-4 text-center text-slate-600 dark:text-slate-400">
                                        {{ $row['session_count'] }}
                                    </td>
                                    <td class="py-3.5 px-4 text-right font-semibold text-slate-800 dark:text-slate-200">
                                        {{ $row['formatted'] }}
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="4" class="py-8 text-center text-slate-400 dark:text-slate-500">
                                        No logs registered in this date range.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                @else
                    <!-- Task Breakdown Table -->
                    <table class="w-full text-left border-collapse">
                        <thead>
                            <tr class="text-slate-400 dark:text-slate-500 text-xs font-semibold uppercase border-b border-slate-100 dark:border-slate-800/60 font-outfit">
                                <th class="py-3 px-4">Task</th>
                                <th class="py-3 px-4">Company</th>
                                <th class="py-3 px-4 text-center">Sessions</th>
                                <th class="py-3 px-4 text-right">Time Spent</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 dark:divide-slate-800/40 text-sm">
                            @forelse($taskBreakdown as $row)
                                <tr class="hover:bg-slate-50/50 dark:hover:bg-slate-800/10 transition-colors duration-150">
                                    <td class="py-3.5 px-4 font-semibold text-slate-800 dark:text-slate-200">
                                        @if($row['task_id'])
                                            <a href="{{ route('tasks.kanban', ['task_id' => $row['task_id']]) }}" wire:navigate class="text-indigo-600 dark:text-indigo-400 hover:underline">
                                                {{ $row['task_title'] }}
                                            </a>
                                        @else
                                            {{ $row['task_title'] }}
                                        @endif
                                    </td>
                                    <td class="py-3.5 px-4 text-slate-500 dark:text-slate-400">
                                        {{ $row['project_name'] }}
                                    </td>
                                    <td class="py-3.5 px-4 text-center text-slate-600 dark:text-slate-400">
                                        {{ $row['session_count'] }}
                                    </td>
                                    <td class="py-3.5 px-4 text-right font-semibold text-slate-800 dark:text-slate-200">
                                        {{ $row['formatted'] }}
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="4" class="py-8 text-center text-slate-400 dark:text-slate-500">
                                        No logs registered for this user in this date range.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                @endif
            </div>
        </div>

        <!-- Right: Recent Session Logs (5 columns) -->
        <div class="lg:col-span-5 bg-white dark:bg-slate-900 border border-slate-100 dark:border-slate-800/80 rounded-2xl p-6 shadow-sm">
            <div class="pb-5 border-b border-slate-100 dark:border-slate-800/60">
                <h2 class="font-outfit font-bold text-lg text-slate-800 dark:text-white">Recent Work Logs</h2>
                <p class="text-xs text-slate-400 mt-1">Detailed list of individual session logs</p>
            </div>

            <div class="mt-4 overflow-y-auto max-h-[400px] pr-1 space-y-4">
                @forelse($logs->take(20) as $log)
                    <div class="p-4 rounded-xl border border-slate-100 dark:border-slate-800 bg-slate-50/50 dark:bg-slate-950/20 text-xs">
                        <div class="flex justify-between items-start">
                            <div class="space-y-1">
                                <span class="font-semibold text-slate-700 dark:text-slate-200 block">
                                    @if($log->task)
                                        <a href="{{ route('tasks.kanban', ['task_id' => $log->task_id]) }}" wire:navigate class="text-indigo-600 dark:text-indigo-400 hover:underline font-bold">
                                            {{ $log->task->title }}
                                        </a>
                                    @else
                                        Deleted Task
                                    @endif
                                </span>
                                <span class="text-slate-400 dark:text-slate-500 block">
                                    Company: {{ $log->task?->project?->name ?? 'Global Task' }}
                                </span>
                                <span class="text-slate-400 dark:text-slate-500 block">
                                    Worker: <span class="font-medium text-slate-600 dark:text-slate-400">{{ $log->user?->name ?? 'Deleted User' }}</span>
                                </span>
                            </div>
                            <span class="inline-flex items-center px-2 py-0.5 rounded text-[10px] font-semibold bg-sky-50 dark:bg-sky-950/30 text-sky-700 dark:text-sky-400 border border-sky-100 dark:border-sky-900/30">
                                {{ $log->human_duration }}
                            </span>
                        </div>
                        <div class="mt-2.5 pt-2 border-t border-slate-100 dark:border-slate-800/80 flex items-center justify-between text-[10px] text-slate-400">
                            <span>Started: {{ $log->started_at->format('M d, H:i') }}</span>
                            <span>Stopped: {{ $log->stopped_at->format('M d, H:i') }}</span>
                        </div>
                    </div>
                @empty
                    <div class="py-12 text-center text-slate-400 dark:text-slate-500">
                        <div class="flex flex-col items-center justify-center space-y-2">
                            <i class="fa-solid fa-folder-open text-2xl text-slate-300 dark:text-slate-700"></i>
                            <span class="text-xs">No individual logs for this range.</span>
                        </div>
                    </div>
                @endforelse
            </div>
        </div>
    </div>
</div>
