<?php

namespace App\Livewire\Reports;

use App\Models\TaskTimeLog;
use App\Models\User;
use Carbon\Carbon;
use Livewire\Component;

class TimeReport extends Component
{
    public $userId = ''; // Empty string means 'All'

    public $fromDate;

    public $toDate;

    public $chartData = [];

    public function mount()
    {
        // Check permissions: view_reports permission required
        if (! auth()->user()->can('view_reports')) {
            abort(403, 'Unauthorized.');
        }

        // Default: from the first day of current month to today
        $this->fromDate = Carbon::now()->startOfMonth()->format('Y-m-d');
        $this->toDate = Carbon::now()->format('Y-m-d');
    }

    public function render()
    {
        $users = User::orderBy('name')->get();

        // Base query for time logs within the selected period (inclusive of whole days)
        $start = Carbon::parse($this->fromDate)->startOfDay();
        $end = Carbon::parse($this->toDate)->endOfDay();

        $query = TaskTimeLog::whereNotNull('stopped_at')
            ->whereBetween('started_at', [$start, $end]);

        if ($this->userId !== '') {
            $query->where('user_id', $this->userId);
        }

        // Fetch logs with task, user, and project relations
        $logs = (clone $query)->with(['user', 'task.project'])->latest('started_at')->get();

        // Calculate Stats
        $totalSeconds = $logs->sum('duration_seconds');
        $totalSessions = $logs->count();
        $uniqueTasksCount = $logs->pluck('task_id')->unique()->count();

        $userBreakdown = [];
        $taskBreakdown = [];

        if ($this->userId === '') {
            // All users breakdown
            // Group by user
            $userBreakdown = $logs->groupBy('user_id')->map(function ($userLogs) {
                $user = $userLogs->first()->user;
                $seconds = $userLogs->sum('duration_seconds');

                return [
                    'user_name' => $user?->name ?? 'Deleted User',
                    'role' => $user?->roles->first()?->name ?? 'worker',
                    'total_seconds' => $seconds,
                    'formatted' => $this->formatDuration($seconds),
                    'session_count' => $userLogs->count(),
                ];
            })->sortByDesc('total_seconds')->values()->all();
        } else {
            // Specific user task breakdown
            // Group by task
            $taskBreakdown = $logs->groupBy('task_id')->map(function ($taskLogs) {
                $task = $taskLogs->first()->task;
                $seconds = $taskLogs->sum('duration_seconds');

                return [
                    'task_id' => $task?->id,
                    'task_title' => $task?->title ?? 'Deleted Task',
                    'project_name' => $task?->project?->name ?? 'Global Task',
                    'total_seconds' => $seconds,
                    'formatted' => $this->formatDuration($seconds),
                    'session_count' => $taskLogs->count(),
                ];
            })->sortByDesc('total_seconds')->values()->all();
        }

        $this->chartData = $this->buildChartData($logs, $start, $end);

        return view('livewire.reports.time-report', [
            'users' => $users,
            'logs' => $logs,
            'totalDurationFormatted' => $this->formatDuration($totalSeconds),
            'totalSessions' => $totalSessions,
            'uniqueTasksCount' => $uniqueTasksCount,
            'userBreakdown' => $userBreakdown,
            'taskBreakdown' => $taskBreakdown,
            'chartData' => $this->chartData,
        ]);
    }

    protected function formatDuration(int $seconds): string
    {
        $hours = floor($seconds / 3600);
        $minutes = floor(($seconds % 3600) / 60);
        $remainingSeconds = $seconds % 60;

        $parts = [];
        if ($hours > 0) {
            $parts[] = "{$hours}h";
        }
        if ($minutes > 0) {
            $parts[] = "{$minutes}m";
        }
        if ($remainingSeconds > 0 || empty($parts)) {
            $parts[] = "{$remainingSeconds}s";
        }

        return implode(' ', $parts);
    }

    private function buildChartData($logs, $start, $end): array
    {
        $dateKeys = [];
        $dateLabels = [];
        $temp = clone $start;
        while ($temp->lte($end)) {
            $dateKeys[] = $temp->format('Y-m-d');
            $dateLabels[] = $temp->format('d M');
            $temp->addDay();
        }

        $colors = [
            '#0ea5e9', // sky
            '#6366f1', // indigo
            '#10b981', // emerald
            '#f59e0b', // amber
            '#ef4444', // red
            '#a855f7', // purple
            '#ec4899', // pink
            '#14b8a6', // teal
        ];

        $pointStyles = [
            'circle',
            'rect',
            'triangle',
            'rectRot',
            'star',
            'cross',
            'crossRot',
            'dash',
        ];

        $datasets = [];

        if ($this->userId === '') {
            // Group by user_id
            $grouped = $logs->groupBy('user_id');
            $idx = 0;
            foreach ($grouped as $userId => $userLogs) {
                $user = $userLogs->first()->user;
                $userName = $user?->name ?? 'Deleted User';

                // Map hours per date
                $data = [];
                foreach ($dateKeys as $dateKey) {
                    $dayStart = Carbon::parse($dateKey)->startOfDay();
                    $dayEnd = Carbon::parse($dateKey)->endOfDay();
                    $seconds = $userLogs->filter(function ($log) use ($dayStart, $dayEnd) {
                        return $log->started_at->between($dayStart, $dayEnd);
                    })->sum('duration_seconds');
                    $data[] = round($seconds / 3600, 2);
                }

                $color = $colors[$idx % count($colors)];
                $pointStyle = $pointStyles[$idx % count($pointStyles)];

                $datasets[] = [
                    'label' => $userName,
                    'data' => $data,
                    'borderColor' => $color,
                    'backgroundColor' => $color,
                    'pointStyle' => $pointStyle,
                    'pointRadius' => 6,
                    'pointHoverRadius' => 10,
                    'tension' => 0.35,
                ];
                $idx++;
            }
        } else {
            // Group by task_id
            $grouped = $logs->groupBy('task_id');
            $idx = 0;
            foreach ($grouped as $taskId => $taskLogs) {
                $task = $taskLogs->first()->task;
                $taskTitle = $task?->title ?? 'Deleted Task';

                // Map hours per date
                $data = [];
                foreach ($dateKeys as $dateKey) {
                    $dayStart = Carbon::parse($dateKey)->startOfDay();
                    $dayEnd = Carbon::parse($dateKey)->endOfDay();
                    $seconds = $taskLogs->filter(function ($log) use ($dayStart, $dayEnd) {
                        return $log->started_at->between($dayStart, $dayEnd);
                    })->sum('duration_seconds');
                    $data[] = round($seconds / 3600, 2);
                }

                $color = $colors[$idx % count($colors)];
                $pointStyle = $pointStyles[$idx % count($pointStyles)];

                $datasets[] = [
                    'label' => $taskTitle,
                    'data' => $data,
                    'borderColor' => $color,
                    'backgroundColor' => $color,
                    'pointStyle' => $pointStyle,
                    'pointRadius' => 6,
                    'pointHoverRadius' => 10,
                    'tension' => 0.35,
                ];
                $idx++;
            }
        }

        // If no datasets exist, create a dummy empty dataset
        if (empty($datasets)) {
            $datasets[] = [
                'label' => 'No Data',
                'data' => array_fill(0, count($dateLabels), 0),
                'borderColor' => '#64748b',
                'backgroundColor' => 'transparent',
                'pointStyle' => 'circle',
                'pointRadius' => 4,
            ];
        }

        return [
            'labels' => $dateLabels,
            'datasets' => $datasets,
        ];
    }
}
