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
        ['code' => '', 'percent' => ''],
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
        'trafficTime.required' => 'Please enter time on page (e.g. 15).',
        'attachments.*.max' => 'Each file must be no larger than 10MB.',
    ];

    public function mount(string $hash): void
    {
        $this->hash = $hash;
        $this->client = Client::where('hash', $hash)->firstOrFail();
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

            $geoRowsHtml = '';
            foreach ($this->trafficGeo as $geo) {
                $code = e($geo['code'] ?? '');
                $percent = e($geo['percent'] ?? 0);
                $countryName = e($this->getCountriesList()[$code] ?? $code);
                if ($code) {
                    $geoRowsHtml .= "
                    <tr style='border-bottom: 1px solid #f1f5f9;'>
                        <td style='padding: 8px 12px; font-weight: 600; color: #1e293b;'>{$countryName}</td>
                        <td style='padding: 8px 12px; text-align: right; font-weight: 800; color: #0284c7;'>{$percent}%</td>
                    </tr>";
                }
            }

            $channelsHtml = '';
            if ($this->trafficReferralPercent !== '') {
                $refPercent = e($this->trafficReferralPercent);
                $refLinksHtml = '';
                if (! empty($this->trafficReferralLinks)) {
                    $links = array_filter(explode("\n", trim($this->trafficReferralLinks)));
                    $linksList = implode('', array_map(function ($l) {
                        $cleanL = e(trim($l));

                        return "<li style='margin-bottom: 2px;'><a href='{$cleanL}' target='_blank' style='color: #0284c7; text-decoration: underline;'>{$cleanL}</a></li>";
                    }, $links));
                    $refLinksHtml = "<ul style='margin: 6px 0 0 0; padding-left: 18px; font-size: 11px; font-family: monospace;'>{$linksList}</ul>";
                }
                $channelsHtml .= "
                <div style='padding: 10px 12px; background: #ffffff; border-radius: 8px; margin-bottom: 8px; border: 1px solid #e2e8f0;'>
                    <div style='display: flex; justify-content: space-between; align-items: center;'>
                        <span style='font-weight: 700; color: #334155; font-size: 12px;'>🔗 Referral Traffic</span>
                        <span style='font-weight: 800; color: #0284c7; font-size: 13px;'>{$refPercent}%</span>
                    </div>
                    {$refLinksHtml}
                </div>";
            }

            if ($this->trafficSocialPercent !== '') {
                $socPercent = e($this->trafficSocialPercent);
                $socialSubHtml = '';
                if ($this->trafficSocialFbPercent !== '' || ! empty($this->trafficSocialFbLink)) {
                    $fbPercent = e($this->trafficSocialFbPercent);
                    $fbLink = ! empty($this->trafficSocialFbLink) ? " — <a href='".e($this->trafficSocialFbLink)."' target='_blank' style='color: #0284c7;'>Link</a>" : '';
                    $socialSubHtml .= "<div style='font-size: 11px; color: #475569; margin-top: 4px;'>• <strong>Facebook:</strong> {$fbPercent}%{$fbLink}</div>";
                }
                if ($this->trafficSocialInstPercent !== '' || ! empty($this->trafficSocialInstLink)) {
                    $instPercent = e($this->trafficSocialInstPercent);
                    $instLink = ! empty($this->trafficSocialInstLink) ? " — <a href='".e($this->trafficSocialInstLink)."' target='_blank' style='color: #0284c7;'>Link</a>" : '';
                    $socialSubHtml .= "<div style='font-size: 11px; color: #475569; margin-top: 2px;'>• <strong>Instagram:</strong> {$instPercent}%{$instLink}</div>";
                }
                $channelsHtml .= "
                <div style='padding: 10px 12px; background: #ffffff; border-radius: 8px; margin-bottom: 8px; border: 1px solid #e2e8f0;'>
                    <div style='display: flex; justify-content: space-between; align-items: center;'>
                        <span style='font-weight: 700; color: #334155; font-size: 12px;'>📱 Social Traffic</span>
                        <span style='font-weight: 800; color: #0284c7; font-size: 13px;'>{$socPercent}%</span>
                    </div>
                    {$socialSubHtml}
                </div>";
            }

            if ($this->trafficOrganicPercent !== '') {
                $orgPercent = e($this->trafficOrganicPercent);
                $channelsHtml .= "
                <div style='padding: 10px 12px; background: #ffffff; border-radius: 8px; margin-bottom: 8px; border: 1px solid #e2e8f0; display: flex; justify-content: space-between; align-items: center;'>
                    <span style='font-weight: 700; color: #334155; font-size: 12px;'>🔍 Organic Traffic</span>
                    <span style='font-weight: 800; color: #0284c7; font-size: 13px;'>{$orgPercent}%</span>
                </div>";
            }

            if ($this->trafficDirectPercent !== '') {
                $dirPercent = e($this->trafficDirectPercent);
                $channelsHtml .= "
                <div style='padding: 10px 12px; background: #ffffff; border-radius: 8px; margin-bottom: 8px; border: 1px solid #e2e8f0; display: flex; justify-content: space-between; align-items: center;'>
                    <span style='font-weight: 700; color: #334155; font-size: 12px;'>🎯 Direct Traffic</span>
                    <span style='font-weight: 800; color: #0284c7; font-size: 13px;'>{$dirPercent}%</span>
                </div>";
            }

            $commentHtml = '';
            if (! empty($this->trafficComment)) {
                $commentContent = nl2br(e(trim($this->trafficComment)));
                $commentHtml = "
                <div style='margin-top: 14px; background: #fffbe6; border: 1px solid #ffe58f; border-radius: 10px; padding: 12px 14px;'>
                    <div style='font-size: 11px; font-weight: 800; color: #d48806; text-transform: uppercase; letter-spacing: 0.05em; margin-bottom: 4px;'>💬 Client Comment</div>
                    <div style='font-size: 12px; color: #595959; line-height: 1.5;'>{$commentContent}</div>
                </div>";
            }

            $trafficMonth = e($this->trafficMonth);
            $trafficPlan = e($this->trafficPlan);
            $bounceRate = e($this->trafficBounceRate);
            $pages = e($this->trafficPages);
            $timeOnPage = e($this->trafficTime);
            $websiteName = e($website->name);
            $websiteUrl = e($website->url);

            $taskDescription = "<div style='font-family: system-ui, -apple-system, BlinkMacSystemFont, \"Segoe UI\", Roboto, sans-serif; color: #1e293b; max-width: 100%;'>
    <div style='background: linear-gradient(135deg, #0284c7 0%, #4f46e5 100%); color: #ffffff; padding: 16px 20px; border-radius: 12px; margin-bottom: 16px;'>
        <div style='font-size: 10px; font-weight: 800; text-transform: uppercase; letter-spacing: 0.1em; opacity: 0.9;'>🚀 TRAFFIC LAUNCH CAMPAIGN</div>
        <div style='font-size: 18px; font-weight: 800; margin-top: 4px;'>{$trafficMonth} Traffic</div>
        <div style='font-size: 12px; opacity: 0.95; margin-top: 6px;'>
            Plan: <strong>{$trafficPlan}</strong> &bull; Website: <a href='{$websiteUrl}' target='_blank' style='color: #ffffff; text-decoration: underline;'>{$websiteUrl}</a>
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
            <div style='font-size: 16px; font-weight: 800; color: #0284c7; margin-top: 2px;'>{$timeOnPage} sec</div>
        </div>
    </div>

    <div style='background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 12px; padding: 14px; margin-bottom: 16px;'>
        <div style='font-size: 11px; font-weight: 800; text-transform: uppercase; color: #475569; letter-spacing: 0.05em; margin-bottom: 8px;'>📍 GEO Distribution (100% Total)</div>
        <table style='width: 100%; border-collapse: collapse; font-size: 12px;'>
            <thead>
                <tr style='border-bottom: 2px solid #cbd5e1; color: #64748b; font-size: 10px; text-transform: uppercase; font-weight: 800;'>
                    <th style='text-align: left; padding: 6px 12px;'>Country</th>
                    <th style='text-align: right; padding: 6px 12px;'>Percentage</th>
                </tr>
            </thead>
            <tbody>
                {$geoRowsHtml}
            </tbody>
        </table>
    </div>

    <div style='background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 12px; padding: 14px;'>
        <div style='font-size: 11px; font-weight: 800; text-transform: uppercase; color: #475569; letter-spacing: 0.05em; margin-bottom: 10px;'>🚦 Traffic Sources</div>
        {$channelsHtml}
    </div>

    {$commentHtml}
</div>";

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

        $this->trafficMonth = '';
        $this->trafficPlan = '';
        $this->trafficGeo = [
            ['code' => '', 'percent' => ''],
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
