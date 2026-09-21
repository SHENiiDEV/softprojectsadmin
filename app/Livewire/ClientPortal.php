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
        $month = $targetMonth ?: $this->trafficTargetMonth;
        if (empty($month)) {
            $month = now()->format('F Y');
        }

        $companies = $this->client->companies()->with('websites')->get();
        $websiteLookup = [];
        foreach ($companies as $company) {
            foreach ($company->websites as $website) {
                $cleanDomain = strtolower(explode('/', preg_replace('/^https?:\/\//i', '', rtrim($website->url ?: $website->name, '/')))[0]);
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

            if ($hasData && $company && ! $trafficLaunch->task_id) {
                $taskTitle = "🚀 Traffic Launch: {$domain} ({$month})";
                $descriptionHtml = $this->buildTrafficRowDescription([
                    'domain' => $domain,
                    'month' => $month,
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
                ]);

                $task = Task::create([
                    'project_id' => $company->id,
                    'creator_id' => null,
                    'title' => $taskTitle,
                    'description' => $descriptionHtml,
                    'status' => 'todo',
                    'priority' => 'high',
                ]);

                $trafficLaunch->update(['task_id' => $task->id]);
                $createdTasksCount++;

                ActivityLog::create([
                    'user_id' => null,
                    'client_id' => $this->client->id,
                    'task_id' => $task->id,
                    'project_id' => $company->id,
                    'action' => 'client_portal_task_created',
                    'description' => "Traffic campaign '{$taskTitle}' was launched via client portal by {$this->client->name}",
                ]);

                NotificationService::sendClientPortalTaskCreated($task, $this->client, $company);
            }
        }

        $msg = "Traffic table for {$month} saved successfully!";
        if ($createdTasksCount > 0) {
            $msg .= " ({$createdTasksCount} new task".($createdTasksCount > 1 ? 's' : '').' created on Kanban).';
        }

        $this->dispatch('notify', message: $msg, type: 'success');
    }

    /**
     * Build rich HTML description for a traffic campaign task.
     */
    protected function buildTrafficRowDescription(array $data): string
    {
        $domain = e($data['domain'] ?? '');
        $month = e($data['month'] ?? '');
        $dateRange = e($data['date_range'] ?? '');
        $plan = e($data['plan'] ?? '');
        $geo = nl2br(e($data['geo'] ?? ''));
        $bounceRate = e($data['bounce_rate'] ?? '');
        $pages = e($data['pages'] ?? '');
        $timeOnPage = e($data['time_on_page'] ?? '');
        $referralTraf = e($data['referral_traf'] ?? '');
        $referralLinks = nl2br(e($data['referral_links'] ?? ''));
        $socialTraf = e($data['social_traf'] ?? '');
        $socialLinks = nl2br(e($data['social_links'] ?? ''));
        $organicTraf = e($data['organic_traf'] ?? '');
        $directTraf = e($data['direct_traf'] ?? '');
        $keywords = nl2br(e($data['keywords'] ?? ''));
        $comment = nl2br(e($data['comment'] ?? ''));

        $geoHtml = ! empty($geo) ? "
        <div style='background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 12px; padding: 14px; margin-bottom: 16px;'>
            <div style='font-size: 11px; font-weight: 800; text-transform: uppercase; color: #475569; letter-spacing: 0.05em; margin-bottom: 8px;'>📍 Target GEO</div>
            <div style='font-size: 13px; font-weight: 600; color: #0f172a; line-height: 1.5;'>{$geo}</div>
        </div>" : '';

        $keywordsHtml = ! empty($keywords) ? "
        <div style='background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 12px; padding: 14px; margin-bottom: 16px;'>
            <div style='font-size: 11px; font-weight: 800; text-transform: uppercase; color: #475569; letter-spacing: 0.05em; margin-bottom: 6px;'>🔑 Keywords</div>
            <div style='font-size: 12px; font-family: monospace; color: #334155;'>{$keywords}</div>
        </div>" : '';

        $commentHtml = ! empty($comment) ? "
        <div style='background: #fffbe6; border: 1px solid #ffe58f; border-radius: 12px; padding: 14px;'>
            <div style='font-size: 11px; font-weight: 800; color: #d48806; text-transform: uppercase; letter-spacing: 0.05em; margin-bottom: 4px;'>💬 Client Comment</div>
            <div style='font-size: 12px; color: #595959; line-height: 1.5;'>{$comment}</div>
        </div>" : '';

        return "<div style='font-family: system-ui, -apple-system, sans-serif; color: #1e293b; max-width: 100%;'>
            <div style='background: linear-gradient(135deg, #0284c7 0%, #4f46e5 100%); color: #ffffff; padding: 16px 20px; border-radius: 12px; margin-bottom: 16px;'>
                <div style='font-size: 10px; font-weight: 800; text-transform: uppercase; letter-spacing: 0.1em; opacity: 0.9;'>🚀 TRAFFIC LAUNCH CAMPAIGN</div>
                <div style='font-size: 18px; font-weight: 800; margin-top: 4px;'>{$domain} &bull; {$month}</div>
                <div style='font-size: 12px; opacity: 0.95; margin-top: 6px;'>
                    Period: <strong>{$dateRange}</strong> &bull; Plan: <strong>{$plan}</strong>
                </div>
            </div>

            <div style='display: grid; grid-template-columns: repeat(3, 1fr); gap: 10px; margin-bottom: 16px;'>
                <div style='background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 10px; padding: 12px; text-align: center;'>
                    <div style='font-size: 10px; font-weight: 800; color: #64748b; text-transform: uppercase;'>Bounce Rate</div>
                    <div style='font-size: 16px; font-weight: 800; color: #0284c7; margin-top: 2px;'>{$bounceRate}</div>
                </div>
                <div style='background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 10px; padding: 12px; text-align: center;'>
                    <div style='font-size: 10px; font-weight: 800; color: #64748b; text-transform: uppercase;'>Pages</div>
                    <div style='font-size: 16px; font-weight: 800; color: #0284c7; margin-top: 2px;'>{$pages}</div>
                </div>
                <div style='background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 10px; padding: 12px; text-align: center;'>
                    <div style='font-size: 10px; font-weight: 800; color: #64748b; text-transform: uppercase;'>Time on Page</div>
                    <div style='font-size: 16px; font-weight: 800; color: #0284c7; margin-top: 2px;'>{$timeOnPage}</div>
                </div>
            </div>

            {$geoHtml}

            <div style='background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 12px; padding: 14px; margin-bottom: 16px;'>
                <div style='font-size: 11px; font-weight: 800; text-transform: uppercase; color: #475569; letter-spacing: 0.05em; margin-bottom: 10px;'>🚦 Traffic Channels</div>
                <div style='display: grid; grid-template-columns: repeat(2, 1fr); gap: 8px;'>
                    <div style='padding: 10px 12px; background: #ffffff; border-radius: 8px; border: 1px solid #e2e8f0;'>
                        <div style='font-weight: 700; color: #334155; font-size: 12px;'>🔗 Referral: <span style='color: #0284c7; font-weight: 800;'>{$referralTraf}</span></div>
                        ".(! empty($referralLinks) ? "<div style='margin-top: 4px; font-size: 11px; font-family: monospace; color: #64748b;'>{$referralLinks}</div>" : '')."
                    </div>
                    <div style='padding: 10px 12px; background: #ffffff; border-radius: 8px; border: 1px solid #e2e8f0;'>
                        <div style='font-weight: 700; color: #334155; font-size: 12px;'>📱 Social: <span style='color: #0284c7; font-weight: 800;'>{$socialTraf}</span></div>
                        ".(! empty($socialLinks) ? "<div style='margin-top: 4px; font-size: 11px; font-family: monospace; color: #64748b;'>{$socialLinks}</div>" : '')."
                    </div>
                    <div style='padding: 10px 12px; background: #ffffff; border-radius: 8px; border: 1px solid #e2e8f0;'>
                        <div style='font-weight: 700; color: #334155; font-size: 12px;'>🔍 Organic: <span style='color: #0284c7; font-weight: 800;'>{$organicTraf}</span></div>
                    </div>
                    <div style='padding: 10px 12px; background: #ffffff; border-radius: 8px; border: 1px solid #e2e8f0;'>
                        <div style='font-weight: 700; color: #334155; font-size: 12px;'>🎯 Direct: <span style='color: #0284c7; font-weight: 800;'>{$directTraf}</span></div>
                    </div>
                </div>
            </div>

            {$keywordsHtml}

            {$commentHtml}
        </div>";
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
