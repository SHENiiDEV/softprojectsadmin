<?php

namespace App\Livewire\Tasks;

use Livewire\Component;
use Livewire\WithFileUploads;
use App\Models\Task;
use App\Models\Project;
use App\Models\User;
use Spatie\MediaLibrary\MediaCollections\Models\Media;
use Illuminate\Support\Facades\Storage;

class KanbanBoard extends Component
{
    use WithFileUploads;

    // Filters
    public $search = '';
    public $filterProject = '';
    public $filterAssignee = '';
    public $filterPriority = '';

    // Modal state
    public $showModal = false;
    public $editingTaskId = null;

    // Form fields
    public $taskTitle = '';
    public $taskDescription = '';
    public $taskProject = ''; // Nullable
    public $taskAssignee = ''; // Nullable
    public $taskPriority = 'medium';
    public $taskStatus = 'todo';
    public $taskDueDate = '';
    public $attachments = [];
    public $existingMedia = [];

    // Comments fields
    public $newCommentContent = '';
    public $newCommentIsPrivate = false;
    public $replyCommentContent = [];

    // Validation rules
    protected function rules()
    {
        return [
            'taskTitle' => 'required|string|max:255',
            'taskDescription' => 'nullable|string',
            'taskProject' => 'nullable|exists:projects,id',
            'taskAssignee' => 'nullable|exists:users,id',
            'taskPriority' => 'required|in:low,medium,high,critical',
            'taskStatus' => 'required|in:todo,in_progress,review,done',
            'taskDueDate' => 'nullable|date',
            'attachments.*' => 'nullable|file|max:10240', // 10MB max
        ];
    }

    protected $listeners = ['statusUpdated' => 'updateTaskStatus'];

    public function mount()
    {
        // Set initial filters from query parameters if available
        $this->filterProject = request()->query('project_id', '');

        $taskId = request()->query('task_id');
        if ($taskId) {
            $this->openTaskModal($taskId);
        }
    }

    public function render()
    {
        // Query tasks with eager loading
        $query = Task::with(['project.client', 'assignee', 'creator']);

        $user = auth()->user();
        if ($user->hasRole('admin')) {
            // Admin sees all tasks
        } elseif ($user->hasRole('curator')) {
            // Curator sees tasks assigned to themselves, managers, workers, or unassigned tasks
            $query->where(function ($q) use ($user) {
                $q->whereNull('assigned_to')
                  ->orWhere('assigned_to', $user->id)
                  ->orWhereHas('assignee', function ($qSub) {
                      $qSub->role(['manager', 'worker']);
                  });
            });
        } elseif ($user->hasRole('manager')) {
            // Manager sees tasks assigned to themselves, workers, or unassigned tasks
            $query->where(function ($q) use ($user) {
                $q->whereNull('assigned_to')
                  ->orWhere('assigned_to', $user->id)
                  ->orWhereHas('assignee', function ($qSub) {
                      $qSub->role('worker');
                  });
            });
        } elseif ($user->hasRole('worker')) {
            // Worker sees only tasks assigned to themselves or unassigned tasks
            $query->where(function ($q) use ($user) {
                $q->whereNull('assigned_to')
                  ->orWhere('assigned_to', $user->id);
            });
        }

        // Apply filters
        if (!empty($this->search)) {
            $query->where(function ($q) {
                $q->where('title', 'ilike', '%' . $this->search . '%')
                  ->orWhere('description', 'ilike', '%' . $this->search . '%');
            });
        }

        if (!empty($this->filterProject)) {
            if ($this->filterProject === 'global') {
                $query->whereNull('project_id');
            } else {
                $query->where('project_id', $this->filterProject);
            }
        }

        if (!empty($this->filterAssignee)) {
            $query->where('assigned_to', $this->filterAssignee);
        }

        if (!empty($this->filterPriority)) {
            $query->where('priority', $this->filterPriority);
        }

        $allTasks = $query->orderBy('order', 'asc')->orderBy('created_at', 'desc')->get();

        // Group by status
        $tasks = [
            'todo' => $allTasks->where('status', 'todo'),
            'in_progress' => $allTasks->where('status', 'in_progress'),
            'review' => $allTasks->where('status', 'review'),
            'done' => $allTasks->where('status', 'done'),
        ];

        return view('livewire.tasks.kanban-board', [
            'tasks' => $tasks,
            'projects' => Project::with('client')->orderBy('name')->get(),
            'users' => User::orderBy('name')->get(),
            'clients' => \App\Models\Client::orderBy('name')->get(),
        ]);
    }

    /**
     * Update task status (triggered via Drag & Drop or manual dropdown)
     */
    public function updateTaskStatus($taskId, $newStatus)
    {
        $task = Task::findOrFail($taskId);
        $user = auth()->user();

        // Authorization checks
        if ($user->hasRole('curator')) {
            session()->flash('error', 'Curators are not allowed to change task status.');
            return;
        }

        if ($user->hasRole('worker') && $task->assigned_to !== $user->id) {
            session()->flash('error', 'Workers are only allowed to modify their own tasks.');
            return;
        }

        if (!in_array($newStatus, ['todo', 'in_progress', 'review', 'done'])) {
            return;
        }

        $task->status = $newStatus;
        $task->save();

        session()->flash('message', "Task status of \"{$task->title}\" successfully updated.");
    }

    /**
     * Open Modal for Create or Edit
     */
    public function openTaskModal($taskId = null)
    {
        $this->resetErrorBag();
        $this->resetValidation();
        $this->attachments = [];

        if ($taskId) {
            $task = Task::findOrFail($taskId);
            $this->editingTaskId = $task->id;
            $this->taskTitle = $task->title;
            $this->taskDescription = $task->description;
            $this->taskProject = $task->project_id ?: '';
            $this->taskAssignee = $task->assigned_to ?: '';
            $this->taskPriority = $task->priority;
            $this->taskStatus = $task->status;
            $this->taskDueDate = $task->due_date ?: '';
            $this->existingMedia = $task->getMedia('documents')->toArray();
        } else {
            $this->editingTaskId = null;
            $this->taskTitle = '';
            $this->taskDescription = '';
            $this->taskProject = '';
            $this->taskAssignee = '';
            $this->taskPriority = 'medium';
            $this->taskStatus = 'todo';
            $this->taskDueDate = '';
            $this->existingMedia = [];
        }

        $this->showModal = true;
    }

    /**
     * Close the modal
     */
    public function closeModal()
    {
        $this->showModal = false;
        $this->attachments = [];
    }

    /**
     * Remove a selected attachment
     */
    public function removeAttachment($index)
    {
        if (isset($this->attachments[$index])) {
            unset($this->attachments[$index]);
            // Re-index array to keep indexes sequential for Livewire
            $this->attachments = array_values($this->attachments);
        }
    }

    /**
     * Create or Update a Task
     */
    public function saveTask()
    {
        $this->validate();

        $user = auth()->user();

        // Worker check for editing
        if ($this->editingTaskId) {
            $task = Task::findOrFail($this->editingTaskId);
            if ($user->hasRole('curator')) {
                session()->flash('error', 'Curators are not allowed to modify tasks.');
                return;
            }
            if ($user->hasRole('worker') && $task->assigned_to !== $user->id) {
                session()->flash('error', 'Workers are only allowed to modify their own tasks.');
                return;
            }
        } else {
            // Creation check
            if ($user->hasRole('curator')) {
                session()->flash('error', 'Curators are not allowed to create tasks.');
                return;
            }
        }

        $data = [
            'title' => $this->taskTitle,
            'description' => $this->taskDescription,
            'project_id' => $this->taskProject ?: null,
            'assigned_to' => $this->taskAssignee ?: null,
            'priority' => $this->taskPriority,
            'status' => $this->taskStatus,
            'due_date' => $this->taskDueDate ?: null,
        ];

        if ($this->editingTaskId) {
            $task = Task::findOrFail($this->editingTaskId);
            $task->update($data);
            $message = "Task \"{$task->title}\" successfully updated.";
        } else {
            $data['creator_id'] = $user->id;
            $task = Task::create($data);
            $message = "Task \"{$task->title}\" successfully created.";
        }

        // Handle file uploads
        if (!empty($this->attachments)) {
            foreach ($this->attachments as $file) {
                $task->addMedia($file->getRealPath())
                     ->usingFileName($file->getClientOriginalName())
                     ->usingName(pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME))
                     ->toMediaCollection('documents');
            }
        }

        $this->showModal = false;
        $this->attachments = [];
        session()->flash('message', $message);
    }

    /**
     * Delete Task (Admins/Managers only)
     */
    public function deleteTask($taskId)
    {
        $user = auth()->user();

        if (!$user->hasAnyRole(['admin', 'manager'])) {
            session()->flash('error', 'Only admins and managers can delete tasks.');
            return;
        }

        $task = Task::findOrFail($taskId);
        $title = $task->title;
        $task->delete();

        session()->flash('message', "Task \"{$title}\" successfully deleted.");
    }

    /**
     * Delete an uploaded file attachment
     */
    public function deleteAttachment($mediaId)
    {
        $user = auth()->user();
        $media = Media::findOrFail($mediaId);
        $task = $media->model;

        // Authorization check
        if ($user->hasRole('curator')) {
            session()->flash('error', 'Curators are not allowed to modify attachments.');
            return;
        }
        if ($user->hasRole('worker') && $task->assigned_to !== $user->id) {
            session()->flash('error', 'Workers are only allowed to modify attachments in their own tasks.');
            return;
        }

        $media->delete();

        // Refresh media list
        if ($this->editingTaskId) {
            $this->existingMedia = Task::findOrFail($this->editingTaskId)->getMedia('documents')->toArray();
        }

        session()->flash('message', 'File successfully deleted.');
    }

    /**
     * Take Task (Assign it to currently logged in user)
     */
    public function takeTask($taskId)
    {
        $task = Task::findOrFail($taskId);
        
        // Assign to currently logged in user
        $task->assigned_to = auth()->id();
        $task->save();

        session()->flash('message', 'You have successfully taken the task.');
    }

    /**
     * Start/Stop timer for a task
     */
    public function toggleTimer($taskId)
    {
        $user = auth()->user();
        $task = Task::findOrFail($taskId);

        // Make sure only the assignee (or admin/manager) can track time
        if ($task->assigned_to !== $user->id && !$user->hasAnyRole(['admin', 'manager'])) {
            session()->flash('error', 'Only the assignee can track time on this task.');
            return;
        }

        // Check if there is an active timer for this user on this task
        $activeTimer = $task->timeLogs()
            ->where('user_id', $user->id)
            ->whereNull('stopped_at')
            ->first();

        if ($activeTimer) {
            // Stop the active timer
            $activeTimer->stopped_at = now();
            $durationSeconds = (int) $activeTimer->started_at->diffInSeconds(now(), true);
            $activeTimer->duration_seconds = $durationSeconds;
            $activeTimer->save();

            // Format duration for logging (H:i:s)
            $formattedDuration = gmdate('H:i:s', $durationSeconds);

            // Log timer stopped
            \App\Models\ActivityLog::create([
                'user_id' => $user->id,
                'task_id' => $task->id,
                'project_id' => $task->project_id,
                'action' => 'timer_stopped',
                'description' => "Timer stopped on task '{$task->title}' after working for {$formattedDuration} by " . $user->name,
            ]);

            // Dispatch notification
            \App\Services\NotificationService::sendTimerAction($task, 'stopped', $durationSeconds, $user);

            session()->flash('message', 'Timer stopped. Tracked: ' . $task->formatted_duration);
        } else {
            // Stop any other active timers for this user first
            \App\Models\TaskTimeLog::where('user_id', $user->id)
                ->whereNull('stopped_at')
                ->each(function ($log) use ($user) {
                    $log->stopped_at = now();
                    $durationSeconds = (int) $log->started_at->diffInSeconds(now(), true);
                    $log->duration_seconds = $durationSeconds;
                    $log->save();

                    $otherTask = $log->task;
                    $formattedDuration = gmdate('H:i:s', $durationSeconds);

                    if ($otherTask) {
                        \App\Models\ActivityLog::create([
                            'user_id' => $user->id,
                            'task_id' => $otherTask->id,
                            'project_id' => $otherTask->project_id,
                            'action' => 'timer_stopped',
                            'description' => "Timer automatically stopped on task '{$otherTask->title}' (conflict override) after working for {$formattedDuration} by " . $user->name,
                        ]);

                        // Dispatch notification for override stopped timer
                        \App\Services\NotificationService::sendTimerAction($otherTask, 'stopped', $durationSeconds, $user);
                    }
                });

            // Start a new timer for this task
            $task->timeLogs()->create([
                'user_id' => $user->id,
                'started_at' => now(),
            ]);

            // Log timer started
            \App\Models\ActivityLog::create([
                'user_id' => $user->id,
                'task_id' => $task->id,
                'project_id' => $task->project_id,
                'action' => 'timer_started',
                'description' => "Timer started on task '{$task->title}' by " . $user->name,
            ]);

            // Dispatch notification for started timer
            \App\Services\NotificationService::sendTimerAction($task, 'started', 0, $user);

            session()->flash('message', 'Timer started for task.');
        }
    }

    /**
     * Add a root comment to the active task.
     */
    public function addComment()
    {
        $this->validate([
            'newCommentContent' => 'required|string|min:1',
        ]);

        if (!$this->editingTaskId) {
            return;
        }

        $task = Task::findOrFail($this->editingTaskId);

        $comment = \App\Models\Comment::create([
            'task_id' => $task->id,
            'user_id' => auth()->id(),
            'content' => $this->newCommentContent,
            'is_private' => $this->newCommentIsPrivate,
        ]);

        // Log to ActivityLog
        \App\Models\ActivityLog::create([
            'user_id' => auth()->id(),
            'task_id' => $task->id,
            'project_id' => $task->project_id,
            'action' => 'task_updated',
            'description' => "Comment was added to task '{$task->title}' by " . auth()->user()->name,
        ]);

        // Send notifications
        \App\Services\NotificationService::sendNewCommentNotification($comment);

        // Reset inputs
        $this->newCommentContent = '';
        $this->newCommentIsPrivate = false;
    }

    /**
     * Add a reply to a specific comment.
     */
    public function addReply($parentId)
    {
        $content = $this->replyCommentContent[$parentId] ?? '';
        if (empty(trim($content))) {
            return;
        }

        if (!$this->editingTaskId) {
            return;
        }

        $task = Task::findOrFail($this->editingTaskId);
        $parent = \App\Models\Comment::findOrFail($parentId);

        $comment = \App\Models\Comment::create([
            'task_id' => $task->id,
            'user_id' => auth()->id(),
            'parent_id' => $parentId,
            'content' => $content,
            'is_private' => $parent->is_private, // reply inherits parent's privacy
        ]);

        // Send notifications
        \App\Services\NotificationService::sendNewCommentNotification($comment);

        // Reset input for this comment
        unset($this->replyCommentContent[$parentId]);
    }

    /**
     * Delete a comment.
     */
    public function deleteComment($commentId)
    {
        $comment = \App\Models\Comment::findOrFail($commentId);
        
        $user = auth()->user();
        if ($user->hasRole('admin') || $user->hasRole('manager') || $comment->user_id === $user->id) {
            $comment->delete();
        }
    }
}
