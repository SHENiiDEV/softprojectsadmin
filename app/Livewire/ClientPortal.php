<?php

namespace App\Livewire;

use App\Models\ActivityLog;
use App\Models\Client;
use App\Models\Comment;
use App\Models\Project;
use App\Models\Task;
use App\Models\Website;
use App\Services\NotificationService;
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

    // Traffic Launch Specific Fields
    public string $trafficMonth = '';

    public string $trafficPlan = '';

    public array $trafficGeo = [
        ['code' => 'CHE', 'percent' => 70],
        ['code' => 'POL', 'percent' => 15],
        ['code' => 'DNK', 'percent' => 15],
    ];

    public string $trafficBounceRate = '';

    public string $trafficPages = '';

    public string $trafficTime = '';

    public string $trafficReferralPercent = '';

    public string $trafficReferralLinks = '';

    public string $trafficSocialPercent = '';

    public string $trafficSocialFbPercent = '';

    public string $trafficSocialFbLink = '';

    public string $trafficSocialInstPercent = '';

    public string $trafficSocialInstLink = '';

    public string $trafficOrganicPercent = '';

    public string $trafficDirectPercent = '';

    public string $trafficComment = '';

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

    protected function rules(): array
    {
        if ($this->requestType === 'Traffic Launch') {
            return [
                'selectedCompanyId' => 'required|exists:projects,id',
                'selectedWebsiteId' => 'required|exists:websites,id',
                'requestType' => 'required',
                'urgency' => 'required|in:low,medium,high,critical',
                'trafficMonth' => 'required|string',
                'trafficPlan' => 'required|string',
                'trafficGeo' => 'required|array|min:1',
                'trafficGeo.*.code' => 'required|string',
                'trafficGeo.*.percent' => 'required|numeric|min:1|max:100',
                'trafficBounceRate' => 'required|string',
                'trafficPages' => 'required|string',
                'trafficTime' => 'required|string',
                'trafficReferralPercent' => 'nullable|numeric|min:0|max:100',
                'trafficSocialPercent' => 'nullable|numeric|min:0|max:100',
                'trafficOrganicPercent' => 'nullable|numeric|min:0|max:100',
                'trafficDirectPercent' => 'nullable|numeric|min:0|max:100',
                'attachments.*' => 'nullable|file|max:10240', // 10MB max
            ];
        }

        return [
            'selectedCompanyId' => 'required|exists:projects,id',
            'selectedWebsiteId' => 'required|exists:websites,id',
            'requestType' => 'required|in:General Question,Traffic Launch,Design Changes,Integration Changes,Bug Report,Other',
            'urgency' => 'required|in:low,medium,high,critical',
            'description' => 'required|string|min:10',
            'attachments.*' => 'nullable|file|max:10240', // 10MB max
        ];
    }

    protected array $messages = [
        'selectedCompanyId.required' => 'Please select a company.',
        'selectedWebsiteId.required' => 'Please select a website.',
        'requestType.required' => 'Please select a request type.',
        'description.required' => 'Please enter a description.',
        'description.min' => 'Description must be at least 10 characters.',
        'trafficMonth.required' => 'Please select a target month.',
        'trafficPlan.required' => 'Please enter a plan name.',
        'trafficGeo.*.code.required' => 'Please select a country for all GEO entries.',
        'trafficGeo.*.percent.required' => 'Please enter a percentage for all GEO entries.',
        'trafficBounceRate.required' => 'Please enter the expected bounce rate.',
        'trafficPages.required' => 'Please enter the number of pages.',
        'trafficTime.required' => 'Please enter the time / schedule.',
        'attachments.*.max' => 'Each file must be no larger than 10MB.',
    ];

    public function mount(string $hash): void
    {
        $this->hash = $hash;
        $this->client = Client::where('hash', $hash)->firstOrFail();
        if (empty($this->trafficMonth)) {
            $this->trafficMonth = now()->format('F Y');
        }
    }

    public function getMonthOptions(): array
    {
        $months = [];
        $date = now();
        for ($i = 0; $i < 12; $i++) {
            $key = $date->format('F Y');
            $months[$key] = $key;
            $date->addMonth();
        }

        return $months;
    }

    public function getCountriesList(): array
    {
        return [
            'CHE' => 'CHE — Switzerland',
            'POL' => 'POL — Poland',
            'DNK' => 'DNK — Denmark',
            'GBR' => 'GBR — United Kingdom (UK)',
            'USA' => 'USA — United States',
            'DEU' => 'DEU — Germany',
            'FRA' => 'FRA — France',
            'ESP' => 'ESP — Spain',
            'ITA' => 'ITA — Italy',
            'NLD' => 'NLD — Netherlands',
            'AUT' => 'AUT — Austria',
            'BEL' => 'BEL — Belgium',
            'SWE' => 'SWE — Sweden',
            'NOR' => 'NOR — Norway',
            'FIN' => 'FIN — Finland',
            'CAN' => 'CAN — Canada',
            'AUS' => 'AUS — Australia',
            'NZL' => 'NZL — New Zealand',
            'BRA' => 'BRA — Brazil',
            'MEX' => 'MEX — Mexico',
            'ARG' => 'ARG — Argentina',
            'CHL' => 'CHL — Chile',
            'COL' => 'COL — Colombia',
            'PER' => 'PER — Peru',
            'SGP' => 'SGP — Singapore',
            'JPN' => 'JPN — Japan',
            'KOR' => 'KOR — South Korea',
            'TWN' => 'TWN — Taiwan',
            'HKG' => 'HKG — Hong Kong',
            'ARE' => 'ARE — United Arab Emirates (UAE)',
            'SAU' => 'SAU — Saudi Arabia',
            'TUR' => 'TUR — Turkey',
            'ISR' => 'ISR — Israel',
            'ZAF' => 'ZAF — South Africa',
            'PRT' => 'PRT — Portugal',
            'GRC' => 'GRC — Greece',
            'CZE' => 'CZE — Czech Republic',
            'HUN' => 'HUN — Hungary',
            'ROU' => 'ROU — Romania',
            'IRL' => 'IRL — Ireland',
            'EST' => 'EST — Estonia',
            'LVA' => 'LVA — Latvia',
            'LTU' => 'LTU — Lithuania',
            'SVK' => 'SVK — Slovakia',
            'SVN' => 'SVN — Slovenia',
            'HRV' => 'HRV — Croatia',
            'CYP' => 'CYP — Cyprus',
            'MLT' => 'MLT — Malta',
            'ISL' => 'ISL — Iceland',
            'LUX' => 'LUX — Luxembourg',
            'THA' => 'THA — Thailand',
            'MYS' => 'MYS — Malaysia',
            'IDN' => 'IDN — Indonesia',
            'PHL' => 'PHL — Philippines',
            'VNM' => 'VNM — Vietnam',
            'IND' => 'IND — India',
            'EGY' => 'EGY — Egypt',
            'MAR' => 'MAR — Morocco',
            'GEO' => 'GEO — Georgia',
            'ARM' => 'ARM — Armenia',
            'KAZ' => 'KAZ — Kazakhstan',
            'UZB' => 'UZB — Uzbekistan',
            'AZE' => 'AZE — Azerbaijan',
            'MDA' => 'MDA — Moldova',
            'KGZ' => 'KGZ — Kyrgyzstan',
        ];
    }

    public function addGeoRow(): void
    {
        $this->trafficGeo[] = ['code' => '', 'percent' => 0];
    }

    public function removeGeoRow(int $index): void
    {
        if (count($this->trafficGeo) > 1) {
            unset($this->trafficGeo[$index]);
            $this->trafficGeo = array_values($this->trafficGeo);
        }
    }

    public function getGeoTotalPercentProperty(): int
    {
        return (int) collect($this->trafficGeo)->sum(fn ($item) => (int) ($item['percent'] ?? 0));
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

        if ($this->requestType === 'Traffic Launch') {
            $totalGeo = $this->geoTotalPercent;
            if ($totalGeo !== 100) {
                $this->addError('trafficGeo', "Total GEO percentage must equal 100% (currently {$totalGeo}%).");

                return;
            }

            $taskTitle = "{$this->trafficMonth} Traffic";

            $geoLines = [];
            foreach ($this->trafficGeo as $geo) {
                $code = $geo['code'] ?? '';
                $percent = $geo['percent'] ?? 0;
                if ($code) {
                    $geoLines[] = "• **{$code}:** {$percent}%";
                }
            }
            $geoFormatted = implode("\n", $geoLines);

            $descParts = [];
            $descParts[] = '🚀 **Traffic Launch Plan**';
            $descParts[] = '';
            $descParts[] = "📌 **Plan:** {$this->trafficPlan}";
            $descParts[] = "📅 **Month:** {$this->trafficMonth}";
            $descParts[] = "🌐 **Website:** {$website->name} ({$website->url})";
            $descParts[] = '';
            $descParts[] = "📍 **GEO Distribution (100% Total):**\n".$geoFormatted;
            $descParts[] = '';
            $descParts[] = '📊 **Parameters:**';
            $descParts[] = "• **Bounce Rate:** {$this->trafficBounceRate}";
            $descParts[] = "• **Pages:** {$this->trafficPages}";
            $descParts[] = "• **Time:** {$this->trafficTime}";
            $descParts[] = '';
            $descParts[] = '🚦 **Traffic Channels Breakdown:**';

            if ($this->trafficReferralPercent !== '') {
                $descParts[] = "• **Referral Traffic:** {$this->trafficReferralPercent}%";
                if (! empty($this->trafficReferralLinks)) {
                    $descParts[] = "  **Referral Links:**\n".trim($this->trafficReferralLinks);
                }
            }

            if ($this->trafficSocialPercent !== '') {
                $descParts[] = "• **Social Traffic:** {$this->trafficSocialPercent}%";
                if ($this->trafficSocialFbPercent !== '' || ! empty($this->trafficSocialFbLink)) {
                    $descParts[] = "  - **Facebook:** {$this->trafficSocialFbPercent}% ".($this->trafficSocialFbLink ? "({$this->trafficSocialFbLink})" : '');
                }
                if ($this->trafficSocialInstPercent !== '' || ! empty($this->trafficSocialInstLink)) {
                    $descParts[] = "  - **Instagram:** {$this->trafficSocialInstPercent}% ".($this->trafficSocialInstLink ? "({$this->trafficSocialInstLink})" : '');
                }
            }

            if ($this->trafficOrganicPercent !== '') {
                $descParts[] = "• **Organic Traffic:** {$this->trafficOrganicPercent}%";
            }

            if ($this->trafficDirectPercent !== '') {
                $descParts[] = "• **Direct Traffic:** {$this->trafficDirectPercent}%";
            }

            if (! empty($this->trafficComment)) {
                $descParts[] = '';
                $descParts[] = "💬 **Comment:**\n".trim($this->trafficComment);
            }

            $taskDescription = implode("\n", $descParts);

        } else {
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
            $taskDescription = $this->description;
        }

        // Create the task on Kanban Board
        $priority = $this->urgency;
        $task = Task::create([
            'project_id' => $company->id,
            'creator_id' => null, // Anonymous client submission
            'title' => $taskTitle,
            'description' => $taskDescription,
            'status' => 'todo',
            'priority' => $priority,
        ]);

        // Log to ActivityLog
        ActivityLog::create([
            'user_id' => null,
            'client_id' => $this->client->id,
            'task_id' => $task->id,
            'project_id' => $company->id,
            'action' => 'client_portal_task_created',
            'description' => "Task '{$taskTitle}' was submitted via client portal by client {$this->client->name}",
        ]);

        // Dispatch in-app and Telegram notification
        NotificationService::sendClientPortalTaskCreated($task, $this->client, $company);

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

        $this->trafficPlan = '';
        $this->trafficGeo = [
            ['code' => 'CHE', 'percent' => 70],
            ['code' => 'POL', 'percent' => 15],
            ['code' => 'DNK', 'percent' => 15],
        ];
        $this->trafficBounceRate = '';
        $this->trafficPages = '';
        $this->trafficTime = '';
        $this->trafficReferralPercent = '';
        $this->trafficReferralLinks = '';
        $this->trafficSocialPercent = '';
        $this->trafficSocialFbPercent = '';
        $this->trafficSocialFbLink = '';
        $this->trafficSocialInstPercent = '';
        $this->trafficSocialInstLink = '';
        $this->trafficOrganicPercent = '';
        $this->trafficDirectPercent = '';
        $this->trafficComment = '';
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

        $comment = Comment::create([
            'task_id' => $task->id,
            'client_id' => $this->client->id,
            'content' => $this->newCommentContent,
            'is_private' => false,
        ]);

        // Log to ActivityLog
        ActivityLog::create([
            'client_id' => $this->client->id,
            'task_id' => $task->id,
            'project_id' => $task->project_id,
            'action' => 'task_updated',
            'description' => "Comment was added to task '{$task->title}' by client ".$this->client->name,
        ]);

        // Send notifications
        NotificationService::sendNewCommentNotification($comment);

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
        $parent = Comment::findOrFail($parentId);

        if ($parent->is_private) {
            return;
        }

        $comment = Comment::create([
            'task_id' => $task->id,
            'client_id' => $this->client->id,
            'parent_id' => $parentId,
            'content' => $content,
            'is_private' => false,
        ]);

        // Send notifications
        NotificationService::sendNewCommentNotification($comment);

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
