<?php

namespace App\Livewire;

use App\Models\Client;
use App\Models\Project;
use App\Models\Task;
use App\Models\Website;
use Livewire\Component;
use Livewire\WithFileUploads;
use Livewire\WithPagination;

class ClientPortal extends Component
{
    use WithFileUploads;
    use WithPagination;

    public string $hash;
    public Client $client;

    // Navigation
    public string $activeTab = 'dashboard';

    // View task modal properties
    public ?int $viewTaskId = null;
    public bool $showTaskModal = false;
    public string $newCommentContent = '';
    public array $replyCommentContent = [];

    // Selections
    public ?int $selectedCompanyId = null;
    public ?int $selectedWebsiteId = null;
    public ?Website $selectedWebsite = null;

    // Form fields
    public string $requestType = 'General Question';
    public string $urgency = 'medium'; // low | medium | high | critical
    public string $description = '';
    public $attachments = [];

    // Success state
    public bool $submitted = false;
    public ?string $createdTaskTitle = null;
    public ?int $createdTaskId = null;

    // Filters (My Tickets tab)
    public string $searchQuery = '';
    public string $statusFilter = 'all'; // all | open | in_progress | review | done
    public string $sortDirection = 'desc';

    protected array $queryString = [
        'activeTab' => ['except' => 'dashboard'],
        'statusFilter' => ['except' => 'all'],
        'searchQuery' => ['except' => ''],
    ];

    protected array $rules = [
        'selectedCompanyId' => 'required|exists:projects,id',
        'selectedWebsiteId' => 'required|exists:websites,id',
        'requestType' => 'required|in:General Question,Design Changes,Integration Changes,Bug Report,Other',
        'urgency' => 'required|in:low,medium,high,critical',
        'description' => 'required|string|min:10',
        'attachments.*' => 'nullable|file|max:10240', // 10MB max
    ];

    protected array $messages = [
        'selectedCompanyId.required' => 'Please select a company.',
        'selectedWebsiteId.required' => 'Please select a website.',
        'requestType.required' => 'Please select a request type.',
        'description.required' => 'Please enter a description.',
        'description.min' => 'Description must be at least 10 characters.',
        'attachments.*.max' => 'Each file must be no larger than 10MB.',
    ];

    public function mount(string $hash): void
    {
        $this->hash = $hash;
        $this->client = Client::where('hash', $hash)->firstOrFail();
    }

    public function updatedActiveTab(): void
    {
        // Reset modal when switching tabs
        if ($this->showTaskModal) {
            $this->closeModal();
        }
    }

    public function updatedSearchQuery(): void
    {
        $this->resetPage();
    }

    public function updatedStatusFilter(): void
    {
        $this->resetPage();
    }

    public function updatedSelectedCompanyId($value): void
    {
        $this->selectedWebsiteId = null;
        $this->selectedWebsite = null;
    }

    public function updatedRequestType($value): void
    {
        if ($value === 'Bug Report') {
            $this->urgency = 'high';
        } else {
            $this->urgency = 'medium';
        }
    }

    public function updatedSelectedWebsiteId($value): void
    {
        if ($value) {
            $this->selectedWebsite = Website::findOrFail($value);
        } else {
            $this->selectedWebsite = null;
        }
    }

    public function removeAttachment($index): void
    {
        if (isset($this->attachments[$index])) {
            unset($this->attachments[$index]);
            $this->attachments = array_values($this->attachments);
        }
    }

    public function toggleSort(): void
    {
        $this->sortDirection = $this->sortDirection === 'desc' ? 'asc' : 'desc';
    }

    /**
     * Get ticket stats for the dashboard.
     *
     * @return array{total: int, open: int, in_review: int, resolved: int}
     */
    public function getStats(): array
    {
        $projectIds = $this->client->companies()->pluck('id');
        $base = Task::whereIn('project_id', $projectIds);

        return [
            'total' => (clone $base)->count(),
            'open' => (clone $base)->whereIn('status', ['todo', 'in_progress'])->count(),
            'in_review' => (clone $base)->where('status', 'review')->count(),
            'resolved' => (clone $base)->where('status', 'done')->count(),
        ];
    }

    public function submitReport(): void
    {
        $this->validate();

        $website = Website::findOrFail($this->selectedWebsiteId);
        $company = Project::findOrFail($this->selectedCompanyId);

        // Map request type translation
        $typeTranslations = [
            'General Question' => 'General Question',
            'Design Changes' => 'Design Changes',
            'Integration Changes' => 'Integration Changes',
            'Bug Report' => 'Bug Report',
            'Other' => 'Other',
        ];

        $translatedType = $typeTranslations[$this->requestType] ?? $this->requestType;
        $taskTitle = "[Portal] {$translatedType}: {$website->name}";

        // Create the task on Kanban Board
        $priority = $this->urgency;
        $task = Task::create([
            'project_id' => $company->id,
            'creator_id' => null, // Anonymous client submission
            'title' => $taskTitle,
            'description' => $this->description,
            'status' => 'todo',
            'priority' => $priority,
        ]);

        // Log to ActivityLog
        \App\Models\ActivityLog::create([
            'user_id' => null,
            'client_id' => $this->client->id,
            'task_id' => $task->id,
            'project_id' => $company->id,
            'action' => 'client_portal_task_created',
            'description' => "Task '{$taskTitle}' was submitted via client portal by client {$this->client->name}",
        ]);

        // Dispatch in-app and Telegram notification
        \App\Services\NotificationService::sendClientPortalTaskCreated($task, $this->client, $company);

        // Upload attachments
        if (! empty($this->attachments)) {
            foreach ($this->attachments as $file) {
                $task->addMedia($file->getRealPath())
                    ->usingFileName($file->getClientOriginalName())
                    ->usingName(pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME))
                    ->toMediaCollection('documents');
            }
        }

        // Set success state
        $this->createdTaskTitle = $taskTitle;
        $this->createdTaskId = $task->id;
        $this->submitted = true;

        // Reset form fields
        $this->description = '';
        $this->attachments = [];
        $this->requestType = 'General Question';
        $this->urgency = 'medium';
    }

    public function resetFormState(): void
    {
        $this->submitted = false;
        $this->createdTaskTitle = null;
        $this->createdTaskId = null;
    }

    public function goToTickets(): void
    {
        $this->submitted = false;
        $this->createdTaskTitle = null;
        $this->createdTaskId = null;
        $this->activeTab = 'tickets';
    }

    public function openTaskModal(int $taskId): void
    {
        $task = Task::findOrFail($taskId);

        $projectIds = $this->client->companies()->pluck('id')->toArray();
        if (in_array($task->project_id, $projectIds)) {
            $this->viewTaskId = $task->id;
            $this->showTaskModal = true;
        }
    }

    public function closeModal(): void
    {
        $this->showTaskModal = false;
        $this->viewTaskId = null;
    }

    public function addComment(): void
    {
        $this->validate([
            'newCommentContent' => 'required|string|min:1',
        ]);

        if (! $this->viewTaskId) {
            return;
        }

        $task = Task::findOrFail($this->viewTaskId);

        $comment = \App\Models\Comment::create([
            'task_id' => $task->id,
            'client_id' => $this->client->id,
            'content' => $this->newCommentContent,
            'is_private' => false,
        ]);

        // Log to ActivityLog
        \App\Models\ActivityLog::create([
            'client_id' => $this->client->id,
            'task_id' => $task->id,
            'project_id' => $task->project_id,
            'action' => 'task_updated',
            'description' => "Comment was added to task '{$task->title}' by client " . $this->client->name,
        ]);

        // Send notifications
        \App\Services\NotificationService::sendNewCommentNotification($comment);

        $this->newCommentContent = '';
    }

    public function addReply(int $parentId): void
    {
        $content = $this->replyCommentContent[$parentId] ?? '';
        if (empty(trim($content))) {
            return;
        }

        if (! $this->viewTaskId) {
            return;
        }

        $task = Task::findOrFail($this->viewTaskId);
        $parent = \App\Models\Comment::findOrFail($parentId);

        if ($parent->is_private) {
            return;
        }

        $comment = \App\Models\Comment::create([
            'task_id' => $task->id,
            'client_id' => $this->client->id,
            'parent_id' => $parentId,
            'content' => $content,
            'is_private' => false,
        ]);

        // Send notifications
        \App\Services\NotificationService::sendNewCommentNotification($comment);

        unset($this->replyCommentContent[$parentId]);
    }

    public function render()
    {
        $companies = $this->client->companies()->orderBy('name')->get();

        $websites = collect();
        if ($this->selectedCompanyId) {
            $websites = Website::where('project_id', $this->selectedCompanyId)->orderBy('name')->get();
        }

        $projectIds = $this->client->companies()->pluck('id');

        // Stats for dashboard
        $stats = $this->getStats();

        // Recent tickets for dashboard (last 5)
        $recentTasks = Task::whereIn('project_id', $projectIds)
            ->orderBy('updated_at', 'desc')
            ->limit(5)
            ->get();

        // Filtered & paginated tickets for "My Tickets" tab
        $ticketsQuery = Task::whereIn('project_id', $projectIds);

        // Status filter
        if ($this->statusFilter === 'open') {
            $ticketsQuery->whereIn('status', ['todo', 'in_progress']);
        } elseif ($this->statusFilter !== 'all') {
            $ticketsQuery->where('status', $this->statusFilter);
        }

        // Search filter
        if (! empty($this->searchQuery)) {
            $search = $this->searchQuery;
            $ticketsQuery->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%");
            });
        }

        $tickets = $ticketsQuery
            ->orderBy('created_at', $this->sortDirection)
            ->paginate(10);

        return view('livewire.client-portal', [
            'companies' => $companies,
            'websites' => $websites,
            'stats' => $stats,
            'recentTasks' => $recentTasks,
            'tickets' => $tickets,
        ])->layout('layouts.portal');
    }
}
