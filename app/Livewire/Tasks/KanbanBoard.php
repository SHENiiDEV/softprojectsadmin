<?php

namespace App\Livewire\Tasks;

use App\Models\Client;
use App\Models\Comment;
use App\Models\Project;
use App\Models\Task;
use App\Models\User;
use App\Services\EmailReplyService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Livewire\Component;
use Livewire\WithFileUploads;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

class KanbanBoard extends Component
{
    use WithFileUploads;

    // Filters
    public $search = '';

    public $filterProject = '';

    public $filterAssignee = '';

    public $filterPriority = '';

    // Archive view mode ('0' = Active tasks, '1' = Archived tasks)
    public string $showArchived = '0';

    // Per-column pagination limits for ultra-fast rendering
    public array $perPage = [
        'todo' => 30,
        'in_progress' => 30,
        'review' => 30,
        'done' => 30,
    ];

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

    // Client Email Reply fields
    public string $emailReplyBody = '';

    public bool $isSendingEmailReply = false;

    protected $queryString = [
        'showArchived' => ['except' => '0'],
    ];

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

    public function loadMoreStatus(string $status): void
    {
        if (isset($this->perPage[$status])) {
            $this->perPage[$status] += 30;
        }
    }

    public function restoreTask($taskId): void
    {
        $task = Task::findOrFail($taskId);
        $task->unarchive();
        session()->flash('message', "Task \"{$task->title}\" restored from archive.");
    }

    public function archiveTask($taskId): void
    {
        $task = Task::findOrFail($taskId);
        $task->archive();
        session()->flash('message', "Task \"{$task->title}\" moved to archive.");
    }

    public function render()
    {
        $user = auth()->user();

        // Build base query
        $baseQuery = Task::query();

        // Apply Archiving Filter (Active vs Archived)
        if ($this->showArchived === '1') {
            $baseQuery->archived();
        } else {
            $baseQuery->notArchived();
        }

        if ($user->hasRole('admin')) {
            // Admin sees all tasks
        } elseif ($user->hasRole('curator')) {
            $baseQuery->where(function ($q) use ($user) {
                $q->whereNull('assigned_to')
                    ->orWhere('assigned_to', $user->id)
                    ->orWhereHas('assignee', function ($qSub) {
                        $qSub->role(['manager', 'worker']);
                    });
            });
        } elseif ($user->hasRole('manager')) {
            $baseQuery->where(function ($q) use ($user) {
                $q->whereNull('assigned_to')
                    ->orWhere('assigned_to', $user->id)
                    ->orWhereHas('assignee', function ($qSub) {
                        $qSub->role('worker');
                    });
            });
        } elseif ($user->hasRole('worker')) {
            $baseQuery->where(function ($q) use ($user) {
                $q->whereNull('assigned_to')
                    ->orWhere('assigned_to', $user->id);
            });
        }

        // Apply search & dropdown filters
        if (! empty($this->search)) {
            $like = DB::connection()->getDriverName() === 'pgsql' ? 'ilike' : 'like';
            $baseQuery->where(function ($q) use ($like) {
                $q->where('title', $like, '%'.$this->search.'%')
                    ->orWhere('description', $like, '%'.$this->search.'%');
            });
        }

        if (! empty($this->filterProject)) {
            if ($this->filterProject === 'global') {
                $baseQuery->whereNull('project_id');
            } else {
                $baseQuery->where('project_id', $this->filterProject);
            }
        }

        if (! empty($this->filterAssignee)) {
            $baseQuery->where('assigned_to', $this->filterAssignee);
        }

        if (! empty($this->filterPriority)) {
            $baseQuery->where('priority', $this->filterPriority);
        }

        // 1. Calculate total counts for column headers
        $statusCounts = [
            'todo' => (clone $baseQuery)->where('status', 'todo')->count(),
            'in_progress' => (clone $baseQuery)->where('status', 'in_progress')->count(),
            'review' => (clone $baseQuery)->where('status', 'review')->count(),
            'done' => (clone $baseQuery)->where('status', 'done')->count(),
        ];

        // Total count of archived tasks for header button badge
        $archivedCount = Task::archived()->count();

        // 2. Fetch tasks per column with column-specific limits & optimized selects
        $statuses = ['todo', 'in_progress', 'review', 'done'];
        $tasks = [];

        foreach ($statuses as $status) {
            $limit = $this->perPage[$status] ?? 30;
            $tasks[$status] = (clone $baseQuery)
                ->where('status', $status)
                ->select('id', 'title', 'description', 'status', 'priority', 'due_date', 'assigned_to', 'project_id', 'created_at', 'updated_at', 'archived_at')
                ->with(['project:id,name,client_id', 'assignee:id,name'])
                ->orderBy('order', 'asc')
                ->orderBy('created_at', 'desc')
                ->limit($limit)
                ->get();
        }

        return view('livewire.tasks.kanban-board', [
            'tasks' => $tasks,
            'statusCounts' => $statusCounts,
            'archivedCount' => $archivedCount,
            'projects' => Project::select('id', 'name', 'client_id')->with('client:id,name')->orderBy('name')->get(),
            'users' => User::select('id', 'name')->orderBy('name')->get(),
            'clients' => Client::select('id', 'name')->orderBy('name')->get(),
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

        if (! in_array($newStatus, ['todo', 'in_progress', 'review', 'done'])) {
            return;
        }

        $task->status = $newStatus;
        $task->save();

        session()->flash('message', "Task status of \"{$task->title}\" successfully updated.");
    }

    /**
     * Close Modal
     */
    public function closeModal()
    {
        $this->showModal = false;
        $this->editingTaskId = null;
    }

    /**
     * Open Modal for Create or Edit
     */
    public function openTaskModal($taskId = null)
    {
        $this->resetValidation();
        $this->attachments = [];
        $this->emailReplyBody = '';

        if ($taskId) {
            $task = Task::with('media')->findOrFail($taskId);
            $this->editingTaskId = $task->id;
            $this->taskTitle = $task->title;
            $this->taskDescription = $task->description;
            $this->taskProject = $task->project_id;
            $this->taskAssignee = $task->assigned_to;
            $this->taskPriority = $task->priority;
            $this->taskStatus = $task->status;
            $this->taskDueDate = $task->due_date ? $task->due_date->format('Y-m-d') : '';
            $this->existingMedia = $task->getMedia('attachments');
        } else {
            $this->editingTaskId = null;
            $this->taskTitle = '';
            $this->taskDescription = '';
            $this->taskProject = $this->filterProject !== 'global' ? $this->filterProject : '';
            $this->taskAssignee = '';
            $this->taskPriority = 'medium';
            $this->taskStatus = 'todo';
            $this->taskDueDate = '';
            $this->existingMedia = [];
        }

        $this->showModal = true;
    }

    public function saveTask()
    {
        $this->validate();

        if ($this->editingTaskId) {
            $task = Task::findOrFail($this->editingTaskId);
            $task->update([
                'title' => $this->taskTitle,
                'description' => $this->taskDescription,
                'project_id' => $this->taskProject ?: null,
                'assigned_to' => $this->taskAssignee ?: null,
                'priority' => $this->taskPriority,
                'status' => $this->taskStatus,
                'due_date' => $this->taskDueDate ?: null,
            ]);

            session()->flash('message', 'Task updated successfully.');
        } else {
            $task = Task::create([
                'title' => $this->taskTitle,
                'description' => $this->taskDescription,
                'project_id' => $this->taskProject ?: null,
                'assigned_to' => $this->taskAssignee ?: null,
                'creator_id' => auth()->id(),
                'priority' => $this->taskPriority,
                'status' => $this->taskStatus,
                'due_date' => $this->taskDueDate ?: null,
            ]);

            session()->flash('message', 'Task created successfully.');
        }

        // Attachments
        if (! empty($this->attachments)) {
            foreach ($this->attachments as $file) {
                $task->addMedia($file->getRealPath())
                    ->usingFileName($file->getClientOriginalName())
                    ->toMediaCollection('attachments');
            }
        }

        $this->showModal = false;
    }

    public function deleteTask($taskId)
    {
        $task = Task::findOrFail($taskId);

        if (! auth()->user()->hasAnyRole(['admin', 'manager'])) {
            session()->flash('error', 'You do not have permission to delete tasks.');

            return;
        }

        $task->delete();
        $this->showModal = false;
        session()->flash('message', 'Task deleted successfully.');
    }

    public function deleteMedia($mediaId)
    {
        $media = Media::findOrFail($mediaId);
        $media->delete();

        if ($this->editingTaskId) {
            $task = Task::with('media')->findOrFail($this->editingTaskId);
            $this->existingMedia = $task->getMedia('attachments');
        }

        session()->flash('message', 'Attachment deleted successfully.');
    }

    public function deleteAttachment($mediaId): void
    {
        $this->deleteMedia($mediaId);
    }

    public function addComment()
    {
        $this->validate(['newCommentContent' => 'required|string']);

        if (! $this->editingTaskId) {
            return;
        }

        Comment::create([
            'task_id' => $this->editingTaskId,
            'user_id' => auth()->id(),
            'content' => $this->newCommentContent,
            'is_private' => $this->newCommentIsPrivate,
        ]);

        $this->newCommentContent = '';
        $this->newCommentIsPrivate = false;
        session()->flash('message', 'Comment added.');
    }

    public function deleteComment(int $commentId): void
    {
        $comment = Comment::findOrFail($commentId);

        $user = auth()->user();
        $isAuthor = $user && $comment->user_id === $user->id;
        $isAdminOrManager = $user && $user->hasAnyRole(['admin', 'manager']);

        if (! $isAuthor && ! $isAdminOrManager) {
            session()->flash('error', 'You do not have permission to delete this comment.');

            return;
        }

        $comment->delete();
        session()->flash('message', 'Comment deleted successfully.');
    }

    public function sendClientEmailReply(EmailReplyService $replyService): void
    {
        $this->validate([
            'emailReplyBody' => 'required|string|min:2',
        ], [
            'emailReplyBody.required' => 'Please enter a reply message for the client.',
        ]);

        if (! $this->editingTaskId) {
            $this->addError('emailReplyBody', 'No task selected.');

            return;
        }

        $task = Task::with('supportTicket')->find($this->editingTaskId);

        if (! $task || ! $task->supportTicket) {
            $this->addError('emailReplyBody', 'This task is not linked to an email support ticket.');

            return;
        }

        $this->isSendingEmailReply = true;
        $ticket = $task->supportTicket;

        try {
            $result = $replyService->sendReply($ticket, $this->emailReplyBody);

            // Log clean comment on task
            Comment::create([
                'task_id' => $task->id,
                'user_id' => auth()->id(),
                'content' => "✉️ **Outgoing Reply Sent to {$ticket->customer_email}** via {$result['sent_via']}:\n\n".$this->emailReplyBody,
            ]);

            $this->emailReplyBody = '';

            session()->flash('message', "Email reply successfully sent to {$ticket->customer_email} via {$result['sent_via']}!");
        } catch (\Throwable $e) {
            Log::error('Failed to send client email reply from CRM task modal: '.$e->getMessage());
            $this->addError('emailReplyBody', 'Failed sending email: '.$e->getMessage());
        } finally {
            $this->isSendingEmailReply = false;
        }
    }
}
