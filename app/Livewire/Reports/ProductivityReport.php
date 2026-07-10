<?php

namespace App\Livewire\Reports;

use Livewire\Component;
use App\Models\User;
use App\Models\Task;
use App\Models\TaskTimeLog;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class ProductivityReport extends Component
{
    public string $period = 'week'; // week, month, quarter
    public string $userId = '';

    public function updatedPeriod(): void {}
    public function updatedUserId(): void {}

    private function dateRange(): array
    {
        $now = Carbon::now();
        return match ($this->period) {
            'week'    => [$now->copy()->startOfWeek(), $now->copy()->endOfWeek()],
            'month'   => [$now->copy()->startOfMonth(), $now->copy()->endOfMonth()],
            'quarter' => [$now->copy()->startOfQuarter(), $now->copy()->endOfQuarter()],
            default   => [$now->copy()->startOfWeek(), $now->copy()->endOfWeek()],
        };
    }

    private function buildUserStats(): Collection
    {
        [$from, $to] = $this->dateRange();

        $query = User::query();
        if ($this->userId) {
            $query->where('id', $this->userId);
        }
        $users = $query->get();

        return $users->map(function (User $user) use ($from, $to) {
            $tasksCompleted = Task::where('assigned_to', $user->id)
                ->where('status', 'done')
                ->whereBetween('updated_at', [$from, $to])
                ->count();

            $tasksTotal = Task::where('assigned_to', $user->id)
                ->whereBetween('created_at', [$from, $to])
                ->count();

            $overdueTasks = Task::where('assigned_to', $user->id)
                ->whereNotNull('due_date')
                ->where('due_date', '<', now()->startOfDay())
                ->whereNotIn('status', ['done'])
                ->count();

            $hoursLogged = TaskTimeLog::where('user_id', $user->id)
                ->whereNotNull('stopped_at')
                ->whereBetween('started_at', [$from, $to])
                ->sum('duration_seconds');

            $completionRate = $tasksTotal > 0
                ? round(($tasksCompleted / $tasksTotal) * 100)
                : 0;

            return [
                'user'             => $user,
                'tasks_completed'  => $tasksCompleted,
                'tasks_total'      => $tasksTotal,
                'overdue'          => $overdueTasks,
                'hours_logged'     => round($hoursLogged / 3600, 1),
                'completion_rate'  => $completionRate,
                'score'            => $this->calcScore($tasksCompleted, $hoursLogged, $overdueTasks),
            ];
        })->sortByDesc('score')->values();
    }

    private function calcScore(int $done, int $seconds, int $overdue): int
    {
        return max(0, ($done * 10) + (int)($seconds / 360) - ($overdue * 5));
    }

    public function render()
    {
        [$from, $to] = $this->dateRange();

        $userStats = $this->buildUserStats();
        $users = User::orderBy('name')->get();

        $totalTasksDone = $userStats->sum('tasks_completed');
        $totalHours     = $userStats->sum('hours_logged');
        $totalOverdue   = $userStats->sum('overdue');

        return view('livewire.reports.productivity-report', compact(
            'userStats', 'users', 'totalTasksDone', 'totalHours', 'totalOverdue', 'from', 'to'
        ));
    }
}
