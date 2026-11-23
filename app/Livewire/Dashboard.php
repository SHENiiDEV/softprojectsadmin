<?php

namespace App\Livewire;

use App\Models\ActivityLog;
use App\Models\PciDssDocument;
use App\Models\Project;
use App\Models\Report;
use App\Models\SupportTicket;
use App\Models\Task;
use App\Models\TaskTimeLog;
use App\Models\User;
use App\Services\OnboardingBoard;
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

    /**
     * Seconds the user logged within the given period, plus whether a timer is running now.
     *
     * @return array{today: int, week: int, running: bool}
     */
    private function timeSummary(User $user): array
    {
        $logs = TaskTimeLog::where('user_id', $user->id)
            ->where(fn ($q) => $q->where('started_at', '>=', now()->startOfWeek())->orWhereNull('stopped_at'))
            ->get(['started_at', 'stopped_at', 'duration_seconds']);

        return [
            'today' => (int) $logs->filter(fn ($log) => $log->started_at->isToday())->sum('duration_seconds'),
            'week' => (int) $logs->filter(fn ($log) => $log->started_at->gte(now()->startOfWeek()))->sum('duration_seconds'),
            'running' => $logs->contains(fn ($log) => $log->stopped_at === null),
        ];
    }

    /**
     * Open tasks and overdue counts per assignee (primary assignee only), busiest first.
     *
     * @return Collection<int, array{user: User, open: int, overdue: int}>
     */
    private function teamWorkload(): Collection
    {
        $tasks = Task::whereNotNull('assigned_to')
            ->where('status', '!=', 'done')
            ->whereNull('archived_at')
            ->get(['assigned_to', 'due_date']);

        $users = User::whereIn('id', $tasks->pluck('assigned_to')->unique())->get()->keyBy('id');

        return $tasks->groupBy('assigned_to')
            ->map(fn (Collection $group, $userId) => [
                'user' => $users[$userId] ?? null,
                'open' => $group->count(),
                'overdue' => $group->filter(fn (Task $task) => $task->due_date !== null && $task->due_date->lt(today()))->count(),
            ])
            ->filter(fn (array $row) => $row['user'] !== null)
            ->sortByDesc(fn (array $row) => [$row['overdue'], $row['open']])
            ->take(6)
            ->values();
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

        $documents = ['expired' => 0, 'expiring' => 0, 'items' => collect()];

        if ($user->can('view_pci_dss') && config('features.pci_dss')) {
            $threshold = today()->addDays(30);
            $dated = PciDssDocument::whereNotNull('valid_until')->where('valid_until', '<=', $threshold);

            $documents['expired'] = (clone $dated)->where('valid_until', '<', today())->count();
            $documents['expiring'] = (clone $dated)->where('valid_until', '>=', today())->count();
            $documents['items'] = (clone $dated)->with(['client:id,name', 'project:id,name'])->orderBy('valid_until')->limit(5)->get();
        }

        $tickets = ['count' => 0, 'items' => collect()];

        if ($canViewTasks) {
            $tickets['count'] = SupportTicket::where('status', 'open')->count();
            $tickets['items'] = SupportTicket::where('status', 'open')->with('project:id,name')->latest()->limit(5)->get();
        }

        $time = config('features.task_time_logs') ? $this->timeSummary($user) : null;
        $workload = $user->can('view_reports') ? $this->teamWorkload() : collect();

        $onboarding = null;

        if ($canViewTasks && config('features.onboarding')) {
            $onboardingRows = app(OnboardingBoard::class)->rows();

            if ($onboardingRows->isNotEmpty()) {
                $finished = OnboardingBoard::FINISHED_STATUSES;
                $statusOrder = array_flip(Task::ONBOARDING_STATUSES);

                $onboarding = [
                    'total' => $onboardingRows->count(),
                    'active' => $onboardingRows->reject(fn (array $row) => in_array($row['status'], $finished, true))->count(),
                    'counts' => collect(Task::ONBOARDING_STATUSES)
                        ->mapWithKeys(fn (string $status) => [$status => $onboardingRows->where('status', $status)->count()])
                        ->filter(),
                    'mine' => $onboardingRows
                        ->filter(fn (array $row) => $row['responsible']->contains('id', $user->id) && ! in_array($row['status'], $finished, true))
                        ->sortBy(fn (array $row) => $statusOrder[$row['status']] ?? 99)
                        ->take(5)
                        ->values(),
                ];
            }
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
            'onboarding' => $onboarding,
            'documents' => $documents,
            'tickets' => $tickets,
            'time' => $time,
            'workload' => $workload,
        ])->layout('layouts.app');
    }
}
