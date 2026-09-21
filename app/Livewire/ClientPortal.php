<?php

namespace App\Livewire;

use App\Models\ActivityLog;
use App\Models\Client;
use App\Models\Comment;
use App\Models\Project;
use App\Models\Task;
use App\Models\TrafficLaunch;
use App\Models\Website;
use App\Services\NotificationService;
use App\Services\TrafficExportService;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
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

    // Launch Traffic Spreadsheet Fields
    public string $trafficTargetMonth = '';

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
        return [
            'selectedCompanyId' => 'required|exists:projects,id',
            'selectedWebsiteId' => 'required|exists:websites,id',
            'requestType' => 'required|in:General Question,Design Changes,Integration Changes,Bug Report,Other',
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
        'attachments.*.max' => 'Each file must be no larger than 10MB.',
    ];

    public function mount(string $hash): void
    {
        $this->hash = $hash;
        $this->client = Client::where('hash', $hash)->firstOrFail();
        $this->trafficTargetMonth = now()->format('F Y');
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

    public function getGroupedCountries(): array
    {
        return [
            'Europe 🇪🇺' => [
                'CHE' => 'CHE — Switzerland',
                'GBR' => 'GBR — United Kingdom (UK)',
                'DEU' => 'DEU — Germany',
                'FRA' => 'FRA — France',
                'ESP' => 'ESP — Spain',
                'ITA' => 'ITA — Italy',
                'NLD' => 'NLD — Netherlands',
                'POL' => 'POL — Poland',
                'DNK' => 'DNK — Denmark',
                'AUT' => 'AUT — Austria',
                'BEL' => 'BEL — Belgium',
                'SWE' => 'SWE — Sweden',
                'NOR' => 'NOR — Norway',
                'FIN' => 'FIN — Finland',
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
            ],
            'North America 🌎' => [
                'USA' => 'USA — United States',
                'CAN' => 'CAN — Canada',
                'MEX' => 'MEX — Mexico',
            ],
            'South & Central America 🌎' => [
                'BRA' => 'BRA — Brazil',
                'ARG' => 'ARG — Argentina',
                'CHL' => 'CHL — Chile',
                'COL' => 'COL — Colombia',
                'PER' => 'PER — Peru',
            ],
            'Asia & Pacific 🌏' => [
                'SGP' => 'SGP — Singapore',
                'JPN' => 'JPN — Japan',
                'KOR' => 'KOR — South Korea',
                'TWN' => 'TWN — Taiwan',
                'HKG' => 'HKG — Hong Kong',
                'THA' => 'THA — Thailand',
                'MYS' => 'MYS — Malaysia',
                'IDN' => 'IDN — Indonesia',
                'PHL' => 'PHL — Philippines',
                'VNM' => 'VNM — Vietnam',
                'IND' => 'IND — India',
                'AUS' => 'AUS — Australia',
                'NZL' => 'NZL — New Zealand',
            ],
            'Middle East & Central Asia 🌍' => [
                'ARE' => 'ARE — United Arab Emirates (UAE)',
                'SAU' => 'SAU — Saudi Arabia',
                'TUR' => 'TUR — Turkey',
                'ISR' => 'ISR — Israel',
                'GEO' => 'GEO — Georgia',
                'ARM' => 'ARM — Armenia',
                'KAZ' => 'KAZ — Kazakhstan',
                'UZB' => 'UZB — Uzbekistan',
                'AZE' => 'AZE — Azerbaijan',
                'MDA' => 'MDA — Moldova',
                'KGZ' => 'KGZ — Kyrgyzstan',
            ],
            'Africa 🌍' => [
                'ZAF' => 'ZAF — South Africa',
                'EGY' => 'EGY — Egypt',
                'MAR' => 'MAR — Morocco',
            ],
        ];
    }

    public function getCountriesList(): array
    {
        $flattened = [];
        foreach ($this->getGroupedCountries() as $group => $countries) {
            foreach ($countries as $code => $name) {
                $flattened[$code] = $name;
            }
        }

        return $flattened;
    }

    public function updatedActiveTab(): void
    {
        // Reset modal when switching tabs
        if ($this->showTaskModal) {
            $this->closeModal();
        }
    }

    public function updatedTrafficTargetMonth(string $value): void
    {
        $rows = $this->getPrefilledTrafficData($value);
        $this->dispatch('traffic-data-loaded', data: $rows);
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
    }

    /**
     * Get prefilled traffic data matrix for Jspreadsheet.
     */
    public function getPrefilledTrafficData(?string $month = null): array
    {
        $month = $month ?: $this->trafficTargetMonth;
        if (empty($month)) {
            $month = now()->format('F Y');
        }

        $existing = TrafficLaunch::where('client_id', $this->client->id)
            ->where('target_month', $month)
            ->get()
            ->keyBy(fn ($item) => strtolower($item->domain));

        $companies = $this->client->companies()->with('websites')->get();
        $rows = [];
        $handledDomains = [];

        $defaultDateRange = now()->startOfMonth()->format('d.m.Y').'-'.now()->endOfMonth()->format('d.m.Y');

        foreach ($companies as $company) {
            foreach ($company->websites as $website) {
                $domain = preg_replace('/^https?:\/\//i', '', rtrim($website->url ?: $website->name, '/'));
                $domain = explode('/', $domain)[0];
                $cleanKey = strtolower($domain);

                if (in_array($cleanKey, $handledDomains)) {
                    continue;
                }
                $handledDomains[] = $cleanKey;

                $record = $existing->get($cleanKey);
                $rows[] = [
                    $record?->date_range ?? $defaultDateRange,
                    $domain,
                    $record?->plan ?? '',
                    $record?->geo ?? '',
                    $record?->bounce_rate ?? '50-60%',
                    $record?->pages ?? '2-3',
                    $record?->time_on_page ?? '15-30',
                    $record?->referral_traf ?? '0%',
                    $record?->referral_links ?? '',
                    $record?->social_traf ?? '0%',
                    $record?->social_links ?? '',
                    $record?->organic_traf ?? '0%',
                    $record?->direct_traf ?? '0%',
                    $record?->keywords ?? '',
                    $record?->comment ?? '',
                    $record?->status ?? 'Pending',
                ];
            }
        }

        // Include any additional records for this month not tied to existing websites
        foreach ($existing as $key => $record) {
            if (! in_array($key, $handledDomains)) {
                $rows[] = [
                    $record->date_range ?? $defaultDateRange,
                    $record->domain,
                    $record->plan ?? '',
                    $record->geo ?? '',
                    $record->bounce_rate ?? '50-60%',
                    $record->pages ?? '2-3',
                    $record->time_on_page ?? '15-30',
                    $record->referral_traf ?? '0%',
                    $record->referral_links ?? '',
                    $record->social_traf ?? '0%',
                    $record->social_links ?? '',
                    $record->organic_traf ?? '0%',
                    $record->direct_traf ?? '0%',
                    $record->keywords ?? '',
                    $record->comment ?? '',
                    $record->status ?? 'Pending',
                ];
            }
        }

        // If no websites or records exist, provide at least one empty template row
        if (empty($rows)) {
            $rows[] = [
                $defaultDateRange,
                '',
                '',
                '',
                '50-60%',
                '2-3',
                '15-30',
                '0%',
                '',
                '0%',
                '',
                '0%',
                '0%',
                '',
                '',
                'Pending',
            ];
        }

        return $rows;
    }

    /**
     * Copy traffic parameters from previous month.
     */
    public function copyPreviousMonthTraffic(): void
    {
        $prevLaunch = TrafficLaunch::where('client_id', $this->client->id)
            ->where('target_month', '!=', $this->trafficTargetMonth)
            ->orderByDesc('created_at')
            ->first();

        if (! $prevLaunch) {
            $this->dispatch('notify', message: 'No previous month data found to copy.', type: 'warning');

            return;
        }

        $prevMonth = $prevLaunch->target_month;
        $prevRecords = TrafficLaunch::where('client_id', $this->client->id)
            ->where('target_month', $prevMonth)
            ->get()
            ->keyBy(fn ($item) => strtolower($item->domain));

        $companies = $this->client->companies()->with('websites')->get();
        $rows = [];
        $handledDomains = [];

        $defaultDateRange = now()->startOfMonth()->format('d.m.Y').'-'.now()->endOfMonth()->format('d.m.Y');

        foreach ($companies as $company) {
            foreach ($company->websites as $website) {
                $domain = preg_replace('/^https?:\/\//i', '', rtrim($website->url ?: $website->name, '/'));
                $domain = explode('/', $domain)[0];
                $cleanKey = strtolower($domain);

                if (in_array($cleanKey, $handledDomains)) {
                    continue;
                }
                $handledDomains[] = $cleanKey;

                $prev = $prevRecords->get($cleanKey);

                $rows[] = [
                    $prev?->date_range ?? $defaultDateRange,
                    $domain,
                    $prev?->plan ?? '',
                    $prev?->geo ?? '',
                    $prev?->bounce_rate ?? '50-60%',
                    $prev?->pages ?? '2-3',
                    $prev?->time_on_page ?? '15-30',
                    $prev?->referral_traf ?? '0%',
                    $prev?->referral_links ?? '',
                    $prev?->social_traf ?? '0%',
                    $prev?->social_links ?? '',
                    $prev?->organic_traf ?? '0%',
                    $prev?->direct_traf ?? '0%',
                    $prev?->keywords ?? '',
                    $prev?->comment ?? '',
                    'Pending',
                ];
            }
        }

        foreach ($prevRecords as $key => $prev) {
            if (! in_array($key, $handledDomains)) {
                $rows[] = [
                    $prev->date_range ?? $defaultDateRange,
                    $prev->domain,
                    $prev->plan ?? '',
                    $prev->geo ?? '',
                    $prev->bounce_rate ?? '50-60%',
                    $prev->pages ?? '2-3',
                    $prev->time_on_page ?? '15-30',
                    $prev->referral_traf ?? '0%',
                    $prev->referral_links ?? '',
                    $prev->social_traf ?? '0%',
                    $prev->social_links ?? '',
                    $prev->organic_traf ?? '0%',
                    $prev->direct_traf ?? '0%',
                    $prev->keywords ?? '',
                    $prev->comment ?? '',
                    'Pending',
                ];
            }
        }

        $this->dispatch('traffic-data-loaded', data: $rows);
        $this->dispatch('notify', message: "Data copied from {$prevMonth}!", type: 'success');
    }

    /**
     * Save traffic spreadsheet rows and create Kanban tasks.
     */
    public function saveTrafficLaunch(array $rows, ?string $targetMonth = null): void
    {
        if (! Schema::hasTable('traffic_launches')) {
            $this->dispatch('notify', message: 'Database table "traffic_launches" is missing. Please run "php artisan migrate" on the server.', type: 'error');

            return;
        }

        try {
            // Handle wrapped rows if needed
            if (isset($rows[0]) && is_array($rows[0]) && isset($rows[0][0]) && is_array($rows[0][0])) {
                $rows = $rows[0];
            }

            $month = $targetMonth ?: $this->trafficTargetMonth;
            if (empty($month)) {
                $month = now()->format('F Y');
            }

            $companies = $this->client->companies()->with('websites')->get();
            $websiteLookup = [];
            foreach ($companies as $company) {
                foreach ($company->websites as $website) {
                    $cleanDomain = strtolower(explode('/', preg_replace('/^https?:\/\//i', '', rtrim($website->url ?: $website->name, '/')))[0]);
                    $cleanDomain = preg_replace('/^www\./i', '', $cleanDomain);
                    $websiteLookup[$cleanDomain] = [
                        'website' => $website,
                        'project' => $company,
                    ];
                }
            }

            $createdTasksCount = 0;

            foreach ($rows as $row) {
                $dateRange = trim((string) ($row[0] ?? ''));
                $domain = trim((string) ($row[1] ?? ''));
                $plan = trim((string) ($row[2] ?? ''));
                $geo = trim((string) ($row[3] ?? ''));
                $bounceRate = trim((string) ($row[4] ?? ''));
                $pages = trim((string) ($row[5] ?? ''));
                $timeOnPage = trim((string) ($row[6] ?? ''));
                $referralTraf = trim((string) ($row[7] ?? ''));
                $referralLinks = trim((string) ($row[8] ?? ''));
                $socialTraf = trim((string) ($row[9] ?? ''));
                $socialLinks = trim((string) ($row[10] ?? ''));
                $organicTraf = trim((string) ($row[11] ?? ''));
                $directTraf = trim((string) ($row[12] ?? ''));
                $keywords = trim((string) ($row[13] ?? ''));
                $comment = trim((string) ($row[14] ?? ''));
                $status = trim((string) ($row[15] ?? '')) ?: 'Pending';

                if (empty($domain)) {
                    continue;
                }

                $cleanDomainKey = strtolower(explode('/', preg_replace('/^https?:\/\//i', '', rtrim($domain, '/')))[0]);
                $cleanDomainKey = preg_replace('/^www\./i', '', $cleanDomainKey);
                $matched = $websiteLookup[$cleanDomainKey] ?? null;
                $company = $matched ? $matched['project'] : $companies->first();
                $websiteId = $matched ? $matched['website']->id : null;
                $projectId = $company?->id;

                $hasData = ! empty($plan) || ! empty($geo) || ! empty($keywords) || ! empty($comment);

                $trafficLaunch = TrafficLaunch::updateOrCreate(
                    [
                        'client_id' => $this->client->id,
                        'target_month' => $month,
                        'domain' => $domain,
                    ],
                    [
                        'project_id' => $projectId,
                        'website_id' => $websiteId,
                        'date_range' => $dateRange,
                        'plan' => $plan,
                        'geo' => $geo,
                        'bounce_rate' => $bounceRate,
                        'pages' => $pages,
                        'time_on_page' => $timeOnPage,
                        'referral_traf' => $referralTraf,
                        'referral_links' => $referralLinks,
                        'social_traf' => $socialTraf,
                        'social_links' => $socialLinks,
                        'organic_traf' => $organicTraf,
                        'direct_traf' => $directTraf,
                        'keywords' => $keywords,
                        'comment' => $comment,
                        'status' => $status,
                    ]
                );

                if ($hasData && $company) {
                    $sitesWithData[] = [
                        'record' => $trafficLaunch,
                        'company' => $company,
                        'domain' => $domain,
                        'date_range' => $dateRange,
                        'plan' => $plan,
                        'geo' => $geo,
                        'bounce_rate' => $bounceRate,
                        'pages' => $pages,
                        'time_on_page' => $timeOnPage,
                        'referral_traf' => $referralTraf,
                        'referral_links' => $referralLinks,
                        'social_traf' => $socialTraf,
                        'social_links' => $socialLinks,
                        'organic_traf' => $organicTraf,
                        'direct_traf' => $directTraf,
                        'keywords' => $keywords,
                        'comment' => $comment,
                    ];
                }
            }

            $createdTasksCount = 0;

            if (! empty($sitesWithData)) {
                // Group websites by company/project
                $grouped = collect($sitesWithData)->groupBy(fn ($item) => $item['company']->id);

                foreach ($grouped as $projectId => $groupSites) {
                    $company = $groupSites->first()['company'];
                    $siteList = $groupSites->all();

                    // Title: if 1 website, specify domain; if multiple, consolidated campaign
                    $taskTitle = count($siteList) === 1
                        ? "🚀 Traffic Launch: {$siteList[0]['domain']} ({$month})"
                        : "🚀 Traffic Launch: {$month} (".count($siteList).' websites)';

                    $descriptionHtml = $this->buildTrafficCampaignDescription($month, $siteList);

                    // Check if an existing task for this campaign already exists to update it
                    $existingTaskId = collect($siteList)
                        ->map(fn ($s) => $s['record']->task_id)
                        ->filter()
                        ->first();

                    if (! $existingTaskId) {
                        $existingTaskId = TrafficLaunch::where('client_id', $this->client->id)
                            ->where('target_month', $month)
                            ->where('project_id', $company->id)
                            ->whereNotNull('task_id')
                            ->value('task_id');
                    }

                    if (! $existingTaskId) {
                        $existingTaskId = Task::where('project_id', $company->id)
                            ->where(function ($q) use ($month) {
                                $q->where('title', 'like', "%Traffic Launch%{$month}%")
                                    ->orWhere('title', 'like', "%{$month}%Traffic%");
                            })
                            ->value('id');
                    }

                    $task = $existingTaskId ? Task::find($existingTaskId) : null;

                    if ($task) {
                        $task->update([
                            'title' => $taskTitle,
                            'description' => $descriptionHtml,
                        ]);
                    } else {
                        $task = Task::create([
                            'project_id' => $company->id,
                            'creator_id' => null,
                            'title' => $taskTitle,
                            'description' => $descriptionHtml,
                            'status' => 'todo',
                            'priority' => 'high',
                        ]);
                        $createdTasksCount++;

                        try {
                            ActivityLog::create([
                                'user_id' => null,
                                'client_id' => $this->client->id,
                                'task_id' => $task->id,
                                'project_id' => $company->id,
                                'action' => 'client_portal_task_created',
                                'description' => "Traffic campaign '{$taskTitle}' was launched via client portal by {$this->client->name}",
                            ]);
                        } catch (\Throwable $logEx) {
                            Log::warning('Failed to log activity for traffic launch task: '.$logEx->getMessage());
                        }

                        try {
                            NotificationService::sendClientPortalTaskCreated($task, $this->client, $company);
                        } catch (\Throwable $notifEx) {
                            Log::warning('Failed to send notification for traffic launch task: '.$notifEx->getMessage());
                        }
                    }

                    // Link all campaign records to this task
                    foreach ($siteList as $s) {
                        $s['record']->update(['task_id' => $task->id]);
                    }

                    // Generate & attach Excel spreadsheet to the task
                    try {
                        $xlsxPath = TrafficExportService::generateXlsx($month, $rows, $this->client->name);
                        $excelName = 'Traffic_Launch_'.Str::slug($month, '_').'.xlsx';

                        // Remove older versions of the export for this task
                        $task->getMedia('attachments')
                            ->filter(fn ($m) => str_starts_with($m->name, 'Traffic_Launch_') || str_starts_with($m->file_name, 'Traffic_Launch_'))
                            ->each(fn ($m) => $m->delete());

                        $task->getMedia('documents')
                            ->filter(fn ($m) => str_starts_with($m->name, 'Traffic_Launch_') || str_starts_with($m->file_name, 'Traffic_Launch_'))
                            ->each(fn ($m) => $m->delete());

                        // Attach to attachments collection (Kanban board)
                        $task->addMedia($xlsxPath)
                            ->usingFileName($excelName)
                            ->usingName($excelName)
                            ->preservingOriginal()
                            ->toMediaCollection('attachments');

                        // Attach to documents collection (Client portal)
                        $task->addMedia($xlsxPath)
                            ->usingFileName($excelName)
                            ->usingName($excelName)
                            ->toMediaCollection('documents');

                        if (file_exists($xlsxPath)) {
                            @unlink($xlsxPath);
                        }
                    } catch (\Throwable $mediaEx) {
                        Log::warning('Failed to attach XLSX to traffic task: '.$mediaEx->getMessage());
                    }
                }
            }

            $msg = "Traffic table for {$month} saved successfully!";
            if ($createdTasksCount > 0) {
                $msg .= " ({$createdTasksCount} new task".($createdTasksCount > 1 ? 's' : '').' created on Kanban).';
            }

            $this->dispatch('notify', message: $msg, type: 'success');
        } catch (\Throwable $e) {
            Log::error('saveTrafficLaunch failed: '.$e->getMessage(), [
                'trace' => $e->getTraceAsString(),
                'rows_count' => count($rows),
            ]);
            $this->dispatch('notify', message: 'Failed to save traffic data: '.$e->getMessage(), type: 'error');
        }
    }

    /**
     * Export the current or saved traffic launch table as an .xlsx file.
     */
    public function exportTrafficXlsx(?array $rows = null)
    {
        try {
            if (empty($rows)) {
                $rows = $this->getPrefilledTrafficData($this->trafficTargetMonth);
            } elseif (isset($rows[0]) && is_array($rows[0]) && isset($rows[0][0]) && is_array($rows[0][0])) {
                $rows = $rows[0];
            }

            $month = $this->trafficTargetMonth ?: now()->format('F Y');
            $filePath = TrafficExportService::generateXlsx($month, $rows, $this->client->name);
            $fileName = 'Traffic_Launch_'.Str::slug($month, '_').'.xlsx';

            return response()->download($filePath, $fileName, [
                'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            ])->deleteFileAfterSend(true);
        } catch (\Throwable $e) {
            Log::error('exportTrafficXlsx failed: '.$e->getMessage());
            $this->dispatch('notify', message: 'Failed to generate Excel file: '.$e->getMessage(), type: 'error');

            return null;
        }
    }

    /**
     * Build rich, clean, Quill-compatible description for traffic campaign task.
     */
    protected function buildTrafficCampaignDescription(string $month, array $sitesData): string
    {
        $totalSites = count($sitesData);
        $parts = [];
        $parts[] = "<h2>🚀 Traffic Launch Plan — {$month}</h2>";
        $parts[] = '<p><strong>👤 Client:</strong> '.e($this->client->name).'<br><strong>📅 Month:</strong> '.e($month).'<br><strong>🌐 Websites Total:</strong> '.$totalSites.'</p>';

        foreach ($sitesData as $idx => $site) {
            $siteNum = $idx + 1;
            $domain = trim((string) ($site['domain'] ?? ''));
            $websiteUrl = ! preg_match('/^https?:\/\//i', $domain) ? 'https://'.$domain : $domain;
            $plan = trim((string) ($site['plan'] ?? ''));
            $dateRange = trim((string) ($site['date_range'] ?? ''));
            $geo = trim((string) ($site['geo'] ?? ''));
            $bounceRate = trim((string) ($site['bounce_rate'] ?? '50-60%'));
            $pages = trim((string) ($site['pages'] ?? '2-3'));
            $timeOnPage = trim((string) ($site['time_on_page'] ?? '15-30'));
            $referralTraf = trim((string) ($site['referral_traf'] ?? '0%'));
            $referralLinks = trim((string) ($site['referral_links'] ?? ''));
            $socialTraf = trim((string) ($site['social_traf'] ?? '0%'));
            $socialLinks = trim((string) ($site['social_links'] ?? ''));
            $organicTraf = trim((string) ($site['organic_traf'] ?? '0%'));
            $directTraf = trim((string) ($site['direct_traf'] ?? '0%'));
            $keywords = trim((string) ($site['keywords'] ?? ''));
            $comment = trim((string) ($site['comment'] ?? ''));

            $section = [];
            $section[] = '<hr>';
            $section[] = "<h3>#{$siteNum}. 🌐 <a href='{$websiteUrl}' target='_blank'>".e($domain).'</a></h3>';

            $meta = [];
            if ($plan !== '') {
                $meta[] = '<strong>🎯 Plan / Target:</strong> '.e($plan);
            }
            if ($dateRange !== '') {
                $meta[] = '<strong>📅 Schedule:</strong> '.e($dateRange);
            }
            if (! empty($meta)) {
                $section[] = '<p>'.implode(' &nbsp;|&nbsp; ', $meta).'</p>';
            }

            if ($geo !== '') {
                $section[] = '<p><strong>📍 Target GEO &amp; Distribution:</strong><br>'."\n".nl2br(e($geo)).'</p>';
            }

            $section[] = '<p><strong>📊 User Behavior Parameters:</strong></p>';
            $section[] = '<ul>';
            $section[] = '<li><strong>Bounce Rate:</strong> '.e($bounceRate).'</li>';
            $section[] = '<li><strong>Pages per Visit:</strong> '.e($pages).'</li>';
            $section[] = '<li><strong>Time on Page:</strong> '.e($timeOnPage).' sec</li>';
            $section[] = '</ul>';

            $section[] = '<p><strong>🚦 Traffic Channels Breakdown:</strong></p>';
            $section[] = '<ul>';

            $refText = '<li><strong>Referral Traffic:</strong> '.e($referralTraf);
            if ($referralLinks !== '') {
                $refText .= '<br><em>Referral Sources / Links:</em><br>'.nl2br(e($referralLinks));
            }
            $refText .= '</li>';
            $section[] = $refText;

            $socText = '<li><strong>Social Traffic:</strong> '.e($socialTraf);
            if ($socialLinks !== '') {
                $socText .= '<br><em>Social Sources / Links:</em><br>'.nl2br(e($socialLinks));
            }
            $socText .= '</li>';
            $section[] = $socText;

            $section[] = '<li><strong>Organic Traffic:</strong> '.e($organicTraf).'</li>';
            $section[] = '<li><strong>Direct Traffic:</strong> '.e($directTraf).'</li>';
            $section[] = '</ul>';

            if ($keywords !== '') {
                $section[] = '<p><strong>🔑 Keywords / Queries:</strong><br>'."\n".nl2br(e($keywords)).'</p>';
            }

            if ($comment !== '') {
                $section[] = '<p><strong>💬 Comments / Special Instructions:</strong><br>'."\n".nl2br(e($comment)).'</p>';
            }

            $parts[] = implode("\n", $section);
        }

        $excelName = 'Traffic_Launch_'.Str::slug($month, '_').'.xlsx';
        $parts[] = '<hr>';
        $parts[] = "<p>📥 <strong>Attached Spreadsheet:</strong> Full monthly traffic matrix <em>{$excelName}</em> is generated and attached to this task. You can download and review it directly.</p>";

        return implode("\n", $parts);
    }

    /**
     * Backward-compatible helper for building single row description.
     */
    protected function buildTrafficRowDescription(array $data): string
    {
        return $this->buildTrafficCampaignDescription($data['month'] ?? '', [$data]);
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

    public function deleteComment(int $commentId): void
    {
        $comment = Comment::findOrFail($commentId);

        $isAuthor = $this->client && $comment->client_id === $this->client->id;
        $user = auth()->user();
        $isAdminOrManager = $user && $user->hasAnyRole(['admin', 'manager']);

        if (! $isAuthor && ! $isAdminOrManager) {
            session()->flash('error', 'You do not have permission to delete this comment.');

            return;
        }

        $comment->delete();
        session()->flash('message', 'Comment deleted successfully.');
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
