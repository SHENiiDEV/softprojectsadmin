<?php

namespace App\Services;

use App\Models\ActivityLog;
use App\Models\Boarding;
use App\Models\Comment;
use App\Models\Project;
use App\Models\Task;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

/**
 * Builds the rows of the onboarding tracker: one row per company, made from its Onboarding and KYB tasks.
 * A task that is not linked to a company stays a row of its own.
 */
class OnboardingBoard
{
    /**
     * Statuses after which a company needs no more attention.
     *
     * @var list<string>
     */
    public const FINISHED_STATUSES = ['Approved', 'Completed', 'Rejected'];

    /**
     * Activity log actions shown in the history of an onboarding.
     *
     * @var list<string>
     */
    public const HISTORY_ACTIONS = [
        'onboarding_status_updated',
        'onboarding_current_status_updated',
        'task_created',
        'task_assigned',
        'task_unassigned',
        'provider_status_updated',
    ];

    /**
     * Status of a company at one provider (key => label), in the order of the selector.
     *
     * @var array<string, string>
     */
    public const PROVIDER_STATUSES = [
        'completed' => 'Completed',
        'decline' => 'Decline',
        'in_process' => 'In process',
        'pending' => 'Pending',
    ];

    /**
     * What a provider status is stored as in the Compliance tab (boarding_status / verification_status).
     *
     * @var array<string, array{boarding_status: string, verification_status: string}>
     */
    private const LEGACY_VALUES = [
        'completed' => ['boarding_status' => 'boarding_completed', 'verification_status' => 'completed'],
        'decline' => ['boarding_status' => 'rejected', 'verification_status' => 'rejected'],
        'in_process' => ['boarding_status' => 'in_progress', 'verification_status' => 'in_progress'],
        'pending' => ['boarding_status' => 'pending', 'verification_status' => 'pending'],
    ];

    /**
     * Provider status -> badge tone.
     */
    private const PROVIDER_TONES = [
        'completed' => 'emerald',
        'in_process' => 'sky',
        'pending' => 'slate',
        'decline' => 'rose',
    ];

    /**
     * @return Collection<int, array<string, mixed>>
     */
    public function rows(?int $projectId = null): Collection
    {
        $tasks = Task::onboarding()
            ->whereNull('archived_at')
            ->when(
                $projectId !== null,
                fn ($q) => $q->where('project_id', $projectId),
                fn ($q) => $q->where(fn ($w) => $w->whereNull('project_id')->orWhereHas('project', fn ($p) => $p->notArchived())),
            )
            ->with(['project.client', 'project.director', 'project.websites.responsible', 'project.boarding', 'assignee', 'assignees'])
            ->orderByDesc('id')
            ->get();

        $latestComments = Comment::whereIn('task_id', $tasks->pluck('id'))
            ->whereNull('parent_id')
            ->whereNotNull('user_id')
            ->with('user:id,name')
            ->orderByDesc('id')
            ->get()
            ->groupBy('task_id')
            ->map(fn (Collection $comments) => $comments->first());

        return $tasks->groupBy(fn (Task $task) => $task->project_id ?? 'task-'.$task->id)->map(function (Collection $companyTasks) use ($latestComments) {
            /** @var Project|null $project */
            $project = $companyTasks->first()->project;

            $onboarding = $companyTasks->firstWhere('type', 'onboarding');
            $kyb = $companyTasks->firstWhere('type', 'kyb');
            $primary = $onboarding ?? $kyb;
            $responsibleTask = $kyb ?? $onboarding;

            $responsible = $responsibleTask->assignees->isNotEmpty()
                ? $responsibleTask->assignees
                : collect([$responsibleTask->assignee])->filter();

            $comment = $companyTasks
                ->map(fn (Task $task) => $latestComments->get($task->id))
                ->filter()
                ->sortByDesc('created_at')
                ->first();

            return [
                'project' => $project,
                'linked' => $project !== null,
                'order' => $project?->id ?? 1_000_000 + $primary->id,
                'code' => $project ? 'ONB-'.str_pad((string) $project->id, 3, '0', STR_PAD_LEFT) : 'TASK-'.$primary->id,
                'company' => $project?->name ?? $primary->title,
                'client' => $project?->client?->name,
                'client_id' => $project?->client_id,
                'director' => $project?->director?->name,
                'curator' => $project?->ubo,
                'websites' => $project ? $project->websites : collect(),
                'providers' => $project ? $this->providersFor($project) : [],
                'status' => $primary->onboarding_status ?: 'Not started',
                'primary_task_id' => $primary->id,
                'kyb_status' => $kyb?->onboarding_status,
                'kyb_task_id' => $kyb?->id,
                'onboarding_task_id' => $onboarding?->id,
                'responsible' => $responsible,
                'current_status' => $primary->current_status,
                'description' => Str::limit(trim(strip_tags(html_entity_decode((string) $primary->description))), 220),
                'comment' => $comment,
                'tasks' => $companyTasks->sortBy('type')->values(),
                'updated_at' => $companyTasks->max('updated_at'),
            ];
        })->values();
    }

    /**
     * The onboarding row of one company, or null when it has no Onboarding / KYB task.
     *
     * @return array<string, mixed>|null
     */
    public function rowFor(Project $project): ?array
    {
        return $this->rows($project->id)->first();
    }

    /**
     * Latest comments of the given tasks (system messages excluded).
     *
     * @param  Collection<int, int>  $taskIds
     * @return Collection<int, Comment>
     */
    public function comments(Collection $taskIds, int $limit = 8): Collection
    {
        return Comment::whereIn('task_id', $taskIds)
            ->whereNull('parent_id')
            ->whereNotNull('user_id')
            ->with('user:id,name')
            ->orderByDesc('id')
            ->limit($limit)
            ->get();
    }

    /**
     * Who changed what and when on the given tasks.
     *
     * @param  Collection<int, int>  $taskIds
     * @return Collection<int, ActivityLog>
     */
    public function history(Collection $taskIds, int $limit = 15): Collection
    {
        return ActivityLog::whereIn('task_id', $taskIds)
            ->whereIn('action', self::HISTORY_ACTIONS)
            ->with('user:id,name')
            ->orderByDesc('id')
            ->limit($limit)
            ->get();
    }

    /**
     * Providers the company is being onboarded with, from its Compliance tab (falls back to website gateways).
     *
     * @return list<array{name: string, key: string, status: string, tone: string}>
     */
    private function providersFor(Project $project): array
    {
        $providers = [];
        $data = is_array($project->boarding?->providers_data) ? $project->boarding->providers_data : [];

        foreach ($data as $key => $item) {
            if (! is_array($item)) {
                continue;
            }

            $name = trim((string) ($item['name'] ?? Str::after((string) $key, '_')));
            if ($name === '') {
                continue;
            }

            $providers[$name] = $this->betterProvider($providers[$name] ?? null, $this->providerStatus($item));
        }

        if ($providers === []) {
            foreach ($project->websites as $website) {
                foreach ((array) $website->gateways as $gateway) {
                    $name = trim((string) $gateway);
                    if ($name !== '') {
                        $providers[$name] ??= 'pending';
                    }
                }
            }
        }

        $list = [];
        foreach ($providers as $name => $status) {
            $list[] = [
                'name' => $name,
                'key' => $status,
                'status' => self::PROVIDER_STATUSES[$status],
                'tone' => self::PROVIDER_TONES[$status],
            ];
        }

        return $list;
    }

    /**
     * One status out of the two Compliance fields: declined beats everything, then completed, then in process.
     *
     * @param  array<string, mixed>  $item
     */
    private function providerStatus(array $item): string
    {
        $values = array_map(
            fn ($value) => strtolower((string) $value),
            array_filter([$item['verification_status'] ?? null, $item['boarding_status'] ?? null], fn ($value) => $value !== null),
        );

        $in = fn (array $options) => count(array_intersect($values, $options)) > 0;

        return match (true) {
            $in(['rejected', 'decline', 'declined']) => 'decline',
            $in(['verified', 'completed', 'boarding_completed']) => 'completed',
            $in(['in_progress', 'sent']) => 'in_process',
            default => 'pending',
        };
    }

    /**
     * When one provider sits on several websites, the best status wins.
     */
    private function betterProvider(?string $current, string $candidate): string
    {
        if ($current === null) {
            return $candidate;
        }

        $order = ['completed', 'in_process', 'pending', 'decline'];

        return array_search($candidate, $order, true) < array_search($current, $order, true) ? $candidate : $current;
    }

    /**
     * Set the status of one provider on every website of the company (creates the record when needed).
     * Written in the Compliance tab format, so both screens show the same. Returns the previous label.
     */
    public function setProviderStatus(Project $project, string $provider, string $status): ?string
    {
        if (! isset(self::PROVIDER_STATUSES[$status])) {
            return null;
        }

        $project->loadMissing(['boarding', 'websites']);
        $previous = collect($this->providersFor($project))->firstWhere('name', $provider)['status'] ?? 'Pending';

        $boarding = $project->boarding ?? Boarding::create(['project_id' => $project->id]);
        $data = is_array($boarding->providers_data) ? $boarding->providers_data : [];
        $legacy = self::LEGACY_VALUES[$status];
        // The Compliance tab keeps the date boarding was completed per provider: fill it the first time
        $completedAt = $status === 'completed' ? now()->toDateString() : null;
        $matched = false;

        foreach ($data as $key => $item) {
            if (! is_array($item)) {
                continue;
            }

            $name = trim((string) ($item['name'] ?? Str::after((string) $key, '_')));
            if (strcasecmp($name, $provider) === 0) {
                $data[$key] = array_merge($item, $legacy, ['name' => $name]);
                if ($completedAt !== null && empty($item['boarding_completed_at'])) {
                    $data[$key]['boarding_completed_at'] = $completedAt;
                }
                $matched = true;
            }
        }

        if (! $matched) {
            $sites = $project->websites->filter(fn ($website) => collect((array) $website->gateways)
                ->contains(fn ($gateway) => strcasecmp(trim((string) $gateway), $provider) === 0));

            $entries = $sites->isEmpty()
                ? [[0, 'Company', '']]
                : $sites->map(fn ($website) => [$website->id, $website->name ?: $website->url, (string) $website->url])->all();

            foreach ($entries as [$websiteId, $websiteName, $websiteUrl]) {
                $key = "{$websiteId}_{$provider}";
                $data[$key] = array_merge([
                    'composite_key' => $key,
                    'website_id' => $websiteId,
                    'website_name' => $websiteName,
                    'website_url' => $websiteUrl,
                    'name' => $provider,
                    'boarding_completed_at' => $completedAt,
                    'kyb_sent_at' => null,
                    'kyb_status' => 'sent',
                ], $legacy);
            }
        }

        $boarding->providers_data = $data;
        $boarding->save();

        return $previous;
    }
}
