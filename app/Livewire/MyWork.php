<?php

namespace App\Livewire;

use App\Models\ActivityLog;
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
    // Filters
    public string $filterStatus = '';

    public string $filterPriority = '';

    public string $search = '';

    // Tasks data
    public Collection $tasks;

    public array $activeTimers = []; // task_id => started_at timestamp

    // Flash
    public ?string $flashMessage = null;

    public ?string $flashType = null; // success | error

    public function mount(): void
    {
        $this->tasks = collect();
        $this->loadTasks();
        $this->loadActiveTimers();
    }

    public function updatedFilterStatus(): void
    {
        $this->loadTasks();
    }

    public function updatedFilterPriority(): void
    {
        $this->loadTasks();
    }

    public function updatedSearch(): void
    {
        $this->loadTasks();
    }

    protected function loadTasks(): void
    {
        $query = Task::where('assigned_to', Auth::id())
            ->with(['project', 'assignee', 'timeLogs' => fn ($q) => $q->whereNull('stopped_at')])
            ->withCount(['timeLogs'])
            ->orderByRaw("CASE status
                WHEN 'in_progress' THEN 1
                WHEN 'review'      THEN 2
                WHEN 'todo'        THEN 3
                WHEN 'done'        THEN 4
                ELSE 5 END")
            ->orderByRaw('due_date IS NULL, due_date ASC');

        if ($this->filterStatus) {
            $query->where('status', $this->filterStatus);
        } else {
            $query->whereNotIn('status', ['done']);
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

        if ($task->assigned_to !== $user->id) {
            session()->flash('error', 'Это не ваш таск! Вы можете запускать таймер только на своих задачах.');

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

        $this->flash('Task status updated.', 'success');
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
        $stats = [
            'total' => Task::where('assigned_to', Auth::id())->count(),
            'in_progress' => Task::where('assigned_to', Auth::id())->where('status', 'in_progress')->count(),
            'overdue' => Task::where('assigned_to', Auth::id())->whereNotNull('due_date')->where('due_date', '<', now()->startOfDay())->whereNotIn('status', ['done'])->count(),
            'done_today' => Task::where('assigned_to', Auth::id())->where('status', 'done')->whereDate('updated_at', today())->count(),
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
