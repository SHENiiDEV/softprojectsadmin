<?php

namespace App\Livewire\Reports;

use Livewire\Component;
use App\Models\User;
use App\Models\TaskTimeLog;
use Carbon\Carbon;

class TimeReport extends Component
{
    public $userId = ''; // Empty string means 'All'
    public $fromDate;
    public $toDate;

    public function mount()
    {
        // Check permissions: view_reports permission required
        if (!auth()->user()->can('view_reports')) {
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

        $chartData = $this->buildChartData($userBreakdown, $taskBreakdown);

        return view('livewire.reports.time-report', [
            'users' => $users,
            'logs' => $logs,
            'totalDurationFormatted' => $this->formatDuration($totalSeconds),
            'totalSessions' => $totalSessions,
            'uniqueTasksCount' => $uniqueTasksCount,
            'userBreakdown' => $userBreakdown,
            'taskBreakdown' => $taskBreakdown,
            'chartData' => $chartData,
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

    private function buildChartData(array $userBreakdown, array $taskBreakdown): array
    {
        if ($this->userId === '') {
            // All-users mode: bar chart by team member
            $labels = array_column($userBreakdown, 'user_name');
            $hours  = array_map(
                fn($r) => round($r['total_seconds'] / 3600, 2),
                $userBreakdown
            );
        } else {
            // Single-user mode: bar chart by task
            $labels = array_column($taskBreakdown, 'task_title');
            $hours  = array_map(
                fn($r) => round($r['total_seconds'] / 3600, 2),
                $taskBreakdown
            );
        }

        return [
            'labels' => $labels,
            'hours'  => $hours,
        ];
    }
}
