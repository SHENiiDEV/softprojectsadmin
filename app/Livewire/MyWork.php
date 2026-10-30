<?php

namespace App\Livewire;

use App\Models\ActivityLog;
use App\Models\Comment;
use App\Models\Task;
use App\Models\TaskTimeLog;
use App\Services\NotificationService;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Livewire\Component;

class MyWork extends Component
{
    // View mode: 'active' (default: todo, in_progress, review) or 'done'
    public string $viewMode = 'active';

    // Filters
    public string $filterStatus = '';

    public string $filterPriority = '';

    public string $sortBy = 'default'; // default | latest_comment | due_date

    public string $search = '';

    protected array $queryString = [
        'viewMode' => ['except' => 'active'],
        'filterStatus' => ['except' => ''],
        'filterPriority' => ['except' => ''],
        'sortBy' => ['except' => 'default'],
        'search' => ['except' => ''],
    ];

    // Tasks data
    public Collection $tasks;

    public array $activeTimers = []; // task_id => started_at timestamp

    // Flash
    public ?string $flashMessage = null;

    public ?string $flashType = null; // success | error

    public function mount(): void
    {
        if ($this->filterStatus === 'done') {
            $this->viewMode = 'done';
        } elseif ($this->viewMode === 'done') {
            $this->filterStatus = 'done';
        }

        $this->tasks = collect();
        $this->loadTasks();
        $this->loadActiveTimers();
    }

    public function setViewMode(string $mode): void
    {
        $this->viewMode = $mode === 'done' ? 'done' : 'active';
        if ($this->viewMode === 'done') {
            $this->filterStatus = 'done';
        } else {
            if ($this->filterStatus === 'done') {
                $this->filterStatus = '';
            }
        }
        $this->loadTasks();
    }

    public function updatedViewMode(): void
    {
        $this->setViewMode($this->viewMode);
    }

    public function updatedFilterStatus(): void
    {
        if ($this->filterStatus === 'done') {
            $this->viewMode = 'done';
        } elseif ($this->viewMode === 'done' && $this->filterStatus !== 'done') {
            $this->viewMode = 'active';
        }
        $this->loadTasks();
    }

    public function updatedFilterPriority(): void
    {
        $this->loadTasks();
    }

    public function updatedSortBy(): void
    {
        $this->loadTasks();
    }

    public function updatedSearch(): void
    {
        $this->loadTasks();
    }

    protected function loadTasks(): void
    {
        $query = Task::assignedToUser(Auth::id())
            ->with([
                'project',
                'assignee',
                'assignees',
                'parent',
                'latestComment.user',
                'latestComment.client',
                'timeLogs' => fn ($q) => $q->whereNull('stopped_at'),
            ])
            ->withCount(['timeLogs']);

        if ($this->sortBy === 'latest_comment') {
            $query->select('tasks.*')
                ->selectSub(
                    Comment::select('created_at')
                        ->whereColumn('comments.task_id', 'tasks.id')
                        ->latest('created_at')
                        ->limit(1),
                    'latest_comment_at'
                )
                ->orderByRaw('latest_comment_at IS NULL, latest_comment_at DESC')
                ->orderBy('id', 'desc');
        } elseif ($this->sortBy === 'due_date') {
            $query->orderByRaw('due_date IS NULL, due_date ASC')
                ->orderBy('id', 'desc');
        } else {
            if ($this->viewMode === 'done' || $this->filterStatus === 'done') {
                $query->orderBy('updated_at', 'desc')->orderBy('id', 'desc');
            } else {
                $query->orderByRaw("CASE status
                    WHEN 'in_progress' THEN 1
                    WHEN 'review'      THEN 2
                    WHEN 'todo'        THEN 3
                    ELSE 4 END")
                    ->orderByRaw('due_date IS NULL, due_date ASC');
            }
        }

        if ($this->viewMode === 'done' || $this->filterStatus === 'done') {
            $query->where('status', 'done');
        } elseif ($this->filterStatus && in_array($this->filterStatus, ['todo', 'in_progress', 'review'])) {
            $query->where('status', $this->filterStatus);
        } else {
            $query->whereIn('status', ['todo', 'in_progress', 'review']);
        }

        if ($this->filterPriority) {
            $query->where('priority', $this->filterPriority);
        }

        if ($this->search) {
            $like = DB::connection()->getDriverName() === 'pgsql' ? 'ilike' : 'like';
            $query->where(fn ($q) => $q->where('title', $like, '%'.$this->search.'%')
                ->orWhere('description', $like, '%'.$this->search.'%')
            );
        }

        $this->tasks = $query->get();
    }

    protected function loadActiveTimers(): void
    {
        $this->activeTimers = TaskTimeLog::where('user_id', Auth::id())
            ->whereNull('stopped_at')
            ->pluck('started_at', 'task_id')
            ->map(fn ($v) => Carbon::parse($v)->timestamp)
            ->toArray();
    }

    /**
     * Toggle timer on/off for a task (same logic as KanbanBoard)
     */
    public function toggleTimer(int $taskId): void
    {
        $user = Auth::user();
        $task = Task::findOrFail($taskId);

        if (! $task->isAssignedToUser($user)) {
            session()->flash('error', 'This is not your task! You can only start timers on tasks assigned to you.');

            return;
        }

        $activeTimer = $task->timeLogs()
            ->where('user_id', $user->id)
            ->whereNull('stopped_at')
            ->first();

        if ($activeTimer) {
            $activeTimer->stopped_at = now();
            $durationSeconds = (int) $activeTimer->started_at->diffInSeconds(now(), true);
            $activeTimer->duration_seconds = $durationSeconds;
            $activeTimer->save();

            ActivityLog::create([
                'user_id' => $user->id,
                'task_id' => $task->id,
                'project_id' => $task->project_id,
                'action' => 'timer_stopped',
                'description' => "Timer stopped on task '{$task->title}' after ".gmdate('H:i:s', $durationSeconds).' by '.$user->name,
            ]);

            NotificationService::sendTimerAction($task, 'stopped', $durationSeconds, $user);
            $this->flash('Timer stopped. Recorded: '.gmdate('H:i:s', $durationSeconds), 'success');
        } else {
            // Stop other active timers first
            TaskTimeLog::where('user_id', $user->id)
                ->whereNull('stopped_at')
                ->each(function ($log) use ($user) {
                    $log->stopped_at = now();
                    $dur = (int) $log->started_at->diffInSeconds(now(), true);
                    $log->duration_seconds = $dur;
                    $log->save();
                    if ($log->task) {
                        ActivityLog::create([
                            'user_id' => $user->id,
                            'task_id' => $log->task->id,
                            'project_id' => $log->task->project_id,
                            'action' => 'timer_stopped',
                            'description' => "Timer auto-stopped on task '{$log->task->title}' by ".$user->name,
                        ]);
                        NotificationService::sendTimerAction($log->task, 'stopped', $dur, $user);
                    }
                });

            $task->timeLogs()->create([
                'user_id' => $user->id,
                'started_at' => now(),
            ]);

            ActivityLog::create([
                'user_id' => $user->id,
                'task_id' => $task->id,
                'project_id' => $task->project_id,
                'action' => 'timer_started',
                'description' => "Timer started on task '{$task->title}' by ".$user->name,
            ]);

            NotificationService::sendTimerAction($task, 'started', 0, $user);
            $this->flash('Timer started!', 'success');
        }

        $this->loadActiveTimers();
        $this->loadTasks();
    }

    /**
     * Change task status
     */
    public function changeStatus(int $taskId, string $newStatus): void
    {
        if (! in_array($newStatus, ['todo', 'in_progress', 'review', 'done'])) {
            return;
        }

        $task = Task::findOrFail($taskId);
        $task->status = $newStatus;
        $task->save();

        if ($newStatus === 'done') {
            $this->flash('Task marked as completed and moved to Done archive.', 'success');
        } else {
            $this->flash('Task status updated to '.str_replace('_', ' ', ucfirst($newStatus)).'.', 'success');
        }

        $this->loadTasks();
        $this->loadActiveTimers();
    }

    /**
     * Total logged seconds for a task
     */
    public function getTaskTotalSeconds(int $taskId): int
    {
        $task = Task::find($taskId);

        return $task ? (int) $task->total_duration : 0;
    }

    private function flash(string $message, string $type = 'success'): void
    {
        $this->flashMessage = $message;
        $this->flashType = $type;
    }

    public function dismissFlash(): void
    {
        $this->flashMessage = null;
        $this->flashType = null;
    }

    public function render()
    {
        $userId = Auth::id();
        $baseQuery = Task::assignedToUser($userId);

        $activeTotal = (clone $baseQuery)->whereIn('status', ['todo', 'in_progress', 'review'])->count();
        $todoCount = (clone $baseQuery)->where('status', 'todo')->count();
        $inProgressCount = (clone $baseQuery)->where('status', 'in_progress')->count();
        $reviewCount = (clone $baseQuery)->where('status', 'review')->count();
        $overdueCount = (clone $baseQuery)
            ->whereNotNull('due_date')
            ->where('due_date', '<', now()->startOfDay())
            ->whereIn('status', ['todo', 'in_progress', 'review'])
            ->count();

        $doneTotal = (clone $baseQuery)->where('status', 'done')->count();
        $doneToday = (clone $baseQuery)->where('status', 'done')->whereDate('updated_at', today())->count();
        $doneThisWeek = (clone $baseQuery)->where('status', 'done')->where('updated_at', '>=', now()->startOfWeek())->count();
        $doneThisMonth = (clone $baseQuery)->where('status', 'done')->where('updated_at', '>=', now()->startOfMonth())->count();

        $stats = [
            'total' => $activeTotal, // Done is explicitly excluded from total tasks
            'active_total' => $activeTotal,
            'todo' => $todoCount,
            'in_progress' => $inProgressCount,
            'review' => $reviewCount,
            'overdue' => $overdueCount,
            'done_total' => $doneTotal,
            'done_today' => $doneToday,
            'done_this_week' => $doneThisWeek,
            'done_this_month' => $doneThisMonth,
        ];

        // Active timer info for currently running
        $runningTimer = null;
        if (! empty($this->activeTimers)) {
            $taskId = array_key_first($this->activeTimers);
            $startedAt = $this->activeTimers[$taskId];
            $task = $this->tasks->firstWhere('id', $taskId)
                         ?? Task::with('project')->find($taskId);
            if ($task) {
                $runningTimer = [
                    'task' => $task,
                    'started_at' => $startedAt,
                ];
            }
        }

        return view('livewire.my-work', compact('stats', 'runningTimer'));
    }
}
