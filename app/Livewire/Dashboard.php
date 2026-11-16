<?php

namespace App\Livewire;

use App\Models\ActivityLog;
use App\Models\Project;
use App\Models\Report;
use App\Models\Task;
use Illuminate\Support\Collection;
use Livewire\Component;

class Dashboard extends Component
{
    /**
     * How many days ahead "upcoming" deadlines look.
     */
    public const UPCOMING_DAYS = 14;

    /**
     * Greeting based on the current hour.
     */
    private function greeting(): string
    {
        $hour = (int) now()->format('G');

        return match (true) {
            $hour < 5 => 'Good night',
            $hour < 12 => 'Good morning',
            $hour < 18 => 'Good afternoon',
            default => 'Good evening',
        };
    }

    /**
     * Open tasks of active (not archived) companies.
     */
    private function openTasksQuery()
    {
        return Task::whereNotNull('due_date')
            ->where('status', '!=', 'done')
            ->whereHas('project', fn ($q) => $q->notArchived());
    }

    /**
     * Report filing deadlines (accounts / confirmation statement) within the window, overdue included.
     *
     * @return Collection<int, array{type: string, label: string, date: \Carbon\Carbon, is_overdue: bool, project: Project}>
     */
    private function reportDeadlines(): Collection
    {
        $until = today()->addDays(self::UPCOMING_DAYS);

        $reports = Report::with('project')
            ->whereHas('project', fn ($q) => $q->notArchived())
            ->where(function ($q) use ($until) {
                $q->where('accounts_due_by', '<=', $until)
                    ->orWhere('statements_due_by', '<=', $until);
            })
            ->get();

        $items = collect();

        foreach ($reports as $report) {
            foreach (['accounts_due_by' => 'Accounts due', 'statements_due_by' => 'Confirmation statement due'] as $field => $label) {
                $date = $report->{$field};

                if ($date !== null && $date->lte($until)) {
                    $items->push([
                        'type' => 'report',
                        'label' => $label,
                        'date' => $date,
                        'is_overdue' => $date->lt(today()),
                        'project' => $report->project,
                    ]);
                }
            }
        }

        return $items;
    }

    public function render()
    {
        $user = auth()->user();
        $canViewTasks = $user->can('view_tasks');
        $canViewProjects = $user->can('view_projects');
        $canViewDeadlines = $user->can('view_deadlines') && config('features.deadline_center');
        $canViewActivity = $user->can('view_activity') && config('features.activity_center', true);

        $overdueTasks = 0;
        $dueTodayTasks = 0;
        $myTasks = collect();
        $upcoming = collect();

        if ($canViewTasks) {
            $overdueTasks = $this->openTasksQuery()->where('due_date', '<', today())->count();
            $dueTodayTasks = $this->openTasksQuery()->whereDate('due_date', today())->count();

            $myTasks = Task::assignedToUser($user->id)
                ->where('status', '!=', 'done')
                ->whereNull('archived_at')
                ->with('project:id,name')
                ->orderByRaw('due_date IS NULL, due_date ASC')
                ->limit(6)
                ->get();
        }

        $reportDeadlines = $canViewDeadlines ? $this->reportDeadlines() : collect();

        if ($canViewDeadlines && $canViewTasks) {
            $upcoming = $this->openTasksQuery()
                ->whereBetween('due_date', [today(), today()->addDays(self::UPCOMING_DAYS)])
                ->with('project:id,name')
                ->orderBy('due_date')
                ->limit(10)
                ->get()
                ->map(fn (Task $task) => [
                    'type' => 'task',
                    'label' => $task->title,
                    'date' => $task->due_date,
                    'is_overdue' => false,
                    'project' => $task->project,
                    'task_id' => $task->id,
                ]);
        }

        $deadlines = $upcoming->concat($reportDeadlines)->sortBy(fn (array $item) => $item['date']->timestamp)->take(8)->values();
        $overdueReports = $reportDeadlines->where('is_overdue', true)->count();

        $projectCounts = $canViewProjects
            ? Project::notArchived()->selectRaw('status, count(*) as total')->groupBy('status')->pluck('total', 'status')
            : collect();

        $activity = collect();

        if ($canViewActivity) {
            $activity = ActivityLog::with(['user', 'client', 'project'])
                ->when(! $user->can('view_credentials'), fn ($q) => $q->where('action', 'not like', 'credential_%'))
                ->latest()
                ->limit(8)
                ->get();
        }

        return view('livewire.dashboard', [
            'greeting' => $this->greeting(),
            'canViewTasks' => $canViewTasks,
            'canViewProjects' => $canViewProjects,
            'canViewDeadlines' => $canViewDeadlines,
            'canViewActivity' => $canViewActivity,
            'overdueTasks' => $overdueTasks,
            'dueTodayTasks' => $dueTodayTasks,
            'overdueReports' => $overdueReports,
            'myTasks' => $myTasks,
            'deadlines' => $deadlines,
            'projectCounts' => $projectCounts,
            'recentProjects' => $canViewProjects ? Project::notArchived()->latest('updated_at')->limit(5)->get() : collect(),
            'activity' => $activity,
        ])->layout('layouts.app');
    }
}
