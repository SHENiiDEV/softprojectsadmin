<?php

namespace App\Services;

use App\Models\ActivityLog;
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
    ];

    /**
     * Provider boarding status -> tone, mirrors the Compliance tab of a company.
     */
    private const PROVIDER_TONES = [
        'verified' => 'emerald',
        'completed' => 'emerald',
        'boarding_completed' => 'emerald',
        'in_progress' => 'sky',
        'sent' => 'sky',
        'need_to_complete' => 'amber',
        'need_to_send' => 'amber',
        'pending' => 'slate',
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
     * @return list<array{name: string, status: string, tone: string}>
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

            $status = (string) ($item['verification_status'] ?? $item['boarding_status'] ?? 'pending');
            $providers[$name] = $this->betterProvider($providers[$name] ?? null, $status);
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
                'status' => Str::headline($status),
                'tone' => self::PROVIDER_TONES[$status] ?? (str_contains($status, 'reject') ? 'rose' : 'slate'),
            ];
        }

        return $list;
    }

    private function betterProvider(?string $current, string $candidate): string
    {
        if ($current === null) {
            return $candidate;
        }

        $order = array_keys(self::PROVIDER_TONES);
        $rank = fn (string $status) => ($i = array_search($status, $order, true)) === false ? count($order) : $i;

        return $rank($candidate) < $rank($current) ? $candidate : $current;
    }
}
