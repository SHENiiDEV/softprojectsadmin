<?php

namespace App\Livewire;

use App\Models\ActivityLog;
use App\Models\Project;
use App\Models\Report;
use App\Models\Task;
use App\Models\TaskTimeLog;
use App\Models\User;
use Carbon\Carbon;
use Livewire\Component;

class CalendarView extends Component
{
    // Filter controls
    public string $filterProject = '';

    public string $filterAssignee = '';

    public string $filterStatus = 'active'; // 'active', 'all', 'done'

    public bool $showTasks = true;

    public bool $showReports = true;

    public bool $showTimeLogs = false;

    /**
     * Reschedule task due date via Drag & Drop on Calendar.
     */
    public function updateTaskDueDate(int $taskId, string $newDate): void
    {
        $user = auth()->user();
        if ($user->hasRole('curator')) {
            session()->flash('error', 'Curators are not allowed to reschedule tasks.');

            return;
        }

        $task = Task::findOrFail($taskId);

        if ($user->hasRole('worker') && ! $task->isAssignedToUser($user)) {
            session()->flash('error', 'Workers can only reschedule their own tasks.');

            return;
        }

        $oldDate = $task->due_date ? $task->due_date->format('d.m.Y') : 'No deadline';
        $formattedNewDate = Carbon::parse($newDate)->format('d.m.Y');

        $task->update(['due_date' => $newDate]);

        ActivityLog::create([
            'user_id' => $user->id,
            'task_id' => $task->id,
            'project_id' => $task->project_id,
            'action' => 'task_rescheduled',
            'description' => "Task '{$task->title}' rescheduled from {$oldDate} to {$formattedNewDate} via Calendar",
        ]);

        session()->flash('message', "Rescheduled \"{$task->title}\" to {$formattedNewDate}.");
    }

    public function render()
    {
        $projects = Project::notArchived()->orderBy('name')->get();
        $users = User::orderBy('name')->get();

        $events = [];

        // 1. Fetch Tasks
        if ($this->showTasks) {
            $taskQuery = Task::whereNotNull('due_date')
                ->with(['project', 'assignees', 'assignee']);

            if ($this->filterProject === 'global') {
                $taskQuery->whereNull('project_id');
            } elseif ($this->filterProject) {
                $taskQuery->where('project_id', $this->filterProject);
            }

            if ($this->filterAssignee) {
                $assigneeId = (int) $this->filterAssignee;
                $taskQuery->assignedToUser($assigneeId);
            }

            if ($this->filterStatus === 'active') {
                $taskQuery->whereNotIn('status', ['done']);
            } elseif ($this->filterStatus === 'done') {
                $taskQuery->where('status', 'done');
            }

            $tasks = $taskQuery->get();

            foreach ($tasks as $task) {
                $projectName = $task->project ? $task->project->name : 'Global';

                $color = match ($task->priority) {
                    'critical' => '#EF4444', // Red
                    'high' => '#F97316',     // Orange
                    'medium' => '#0EA5E9',   // Sky
                    default => '#64748B',    // Slate
                };

                if ($task->status === 'done') {
                    $color = '#10B981'; // Emerald for done
                }

                $assigneesStr = '';
                $assignees = $task->assignees->isNotEmpty() ? $task->assignees : ($task->assignee ? collect([$task->assignee]) : collect());
                if ($assignees->isNotEmpty()) {
                    $assigneesStr = ' ['.$assignees->map(fn ($u) => explode(' ', $u->name)[0])->implode(', ').']';
                }

                $events[] = [
                    'id' => 'task_'.$task->id,
                    'title' => '📝 '.$task->title.' ('.$projectName.')'.$assigneesStr,
                    'start' => $task->due_date->format('Y-m-d'),
                    'url' => route('tasks.kanban', ['task_id' => $task->id]),
                    'color' => $color,
                    'editable' => true,
                ];
            }
        }

        // 2. Fetch Compliance Reports
        if ($this->showReports) {
            $reportQuery = Report::where(function ($query) {
                $query->whereNotNull('accounts_due_by')
                    ->orWhereNotNull('statements_due_by');
            })->with('project');

            if ($this->filterProject && $this->filterProject !== 'global') {
                $reportQuery->where('project_id', $this->filterProject);
            }

            $reports = $reportQuery->get();

            foreach ($reports as $report) {
                $projectName = $report->project ? $report->project->name : 'Unknown';
                $url = route('projects.show', $report->project_id).'?tab=reports';

                if ($report->accounts_due_by) {
                    $events[] = [
                        'id' => 'accounts_'.$report->id,
                        'title' => '🏦 Accounts: '.$projectName,
                        'start' => $report->accounts_due_by->format('Y-m-d'),
                        'url' => $url,
                        'color' => '#8B5CF6', // Purple
                        'editable' => false,
                    ];
                }

                if ($report->statements_due_by) {
                    $events[] = [
                        'id' => 'statements_'.$report->id,
                        'title' => '📄 Statement: '.$projectName,
                        'start' => $report->statements_due_by->format('Y-m-d'),
                        'url' => $url,
                        'color' => '#EC4899', // Pink
                        'editable' => false,
                    ];
                }
            }
        }

        // 3. Fetch Time Tracking Logs (if enabled)
        if ($this->showTimeLogs) {
            $logQuery = TaskTimeLog::with(['task', 'user', 'task.project'])
                ->whereNotNull('stopped_at');

            if ($this->filterAssignee) {
                $logQuery->where('user_id', $this->filterAssignee);
            }

            if ($this->filterProject && $this->filterProject !== 'global') {
                $logQuery->whereHas('task', fn ($q) => $q->where('project_id', $this->filterProject));
            }

            // Limit to last 60 days for performance
            $logQuery->where('started_at', '>=', now()->subDays(60));

            $logs = $logQuery->get();

            foreach ($logs as $log) {
                if (! $log->task) {
                    continue;
                }
                $userName = $log->user ? explode(' ', $log->user->name)[0] : 'User';
                $hoursStr = round($log->duration_seconds / 3600, 1).'h';

                $events[] = [
                    'id' => 'timelog_'.$log->id,
                    'title' => "⏱️ {$userName} ({$hoursStr}): {$log->task->title}",
                    'start' => $log->started_at->format('Y-m-d'),
                    'url' => route('tasks.kanban', ['task_id' => $log->task_id]),
                    'color' => '#059669', // Emerald 600
                    'editable' => false,
                ];
            }
        }

        return view('livewire.calendar-view', [
            'projects' => $projects,
            'users' => $users,
            'eventsJson' => json_encode($events),
        ])->layout('layouts.app');
    }
}
