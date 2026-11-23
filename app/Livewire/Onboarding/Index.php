<?php

namespace App\Livewire\Onboarding;

use App\Livewire\Concerns\RequiresPermission;
use App\Models\Comment;
use App\Models\Project;
use App\Models\Task;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Livewire\Component;

class Index extends Component
{
    use RequiresPermission;

    public string $search = '';

    public string $filterStatus = '';

    public string $filterResponsible = '';

    public string $filterClient = '';

    public string $sortBy = 'id'; // id | company | status | updated

    /**
     * @var array<string, array<string, string>>
     */
    protected $queryString = [
        'search' => ['except' => ''],
        'filterStatus' => ['except' => '', 'as' => 'status'],
        'filterResponsible' => ['except' => '', 'as' => 'kyb'],
        'filterClient' => ['except' => '', 'as' => 'client'],
        'sortBy' => ['except' => 'id'],
    ];

    /**
     * Provider boarding status -> badge tone, mirrors the Compliance tab of a company.
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

    public function mount(): void
    {
        $this->requirePermission('view_tasks');
    }

    public function resetFilters(): void
    {
        $this->reset(['search', 'filterStatus', 'filterResponsible', 'filterClient']);
        $this->sortBy = 'id';
    }

    /**
     * Inline edits follow the Kanban rules: manage_tasks, and curators may not edit tasks.
     */
    private function authorizeEdit(): void
    {
        $this->requirePermission('manage_tasks');
        abort_if(auth()->user()->hasRole('curator'), 403);
    }

    private function onboardingTask(int $taskId): Task
    {
        return Task::onboarding()->findOrFail($taskId);
    }

    public function setStatus(int $taskId, string $status): void
    {
        $this->authorizeEdit();

        if (! in_array($status, Task::ONBOARDING_STATUSES, true)) {
            return;
        }

        $this->onboardingTask($taskId)->update(['onboarding_status' => $status]);
        session()->flash('message', 'Onboarding status updated.');
    }

    public function saveCurrentStatus(int $taskId, string $text): void
    {
        $this->authorizeEdit();

        $this->onboardingTask($taskId)->update(['current_status' => trim($text) !== '' ? Str::limit(trim($text), 5000, '') : null]);
        session()->flash('message', 'Current status saved.');
    }

    /**
     * One row per company, built from its Onboarding and KYB tasks.
     *
     * @return Collection<int, array<string, mixed>>
     */
    private function buildRows(): Collection
    {
        $tasks = Task::onboarding()
            ->whereNull('archived_at')
            ->where(fn ($q) => $q->whereNull('project_id')->orWhereHas('project', fn ($p) => $p->notArchived()))
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

        // Tasks of a company are merged into one row; a task that is not linked to any company stays a row of its own.
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
                'updated_at' => $companyTasks->max('updated_at'),
            ];
        })->values();
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

    public function render()
    {
        $allRows = $this->buildRows();

        $statusCounts = $allRows->countBy('status');

        $rows = $allRows
            ->when($this->filterStatus !== '', fn (Collection $c) => $c->where('status', $this->filterStatus))
            ->when($this->filterClient !== '', fn (Collection $c) => $c->where('client_id', (int) $this->filterClient))
            ->when($this->filterResponsible !== '', fn (Collection $c) => $c->filter(
                fn (array $row) => $row['responsible']->contains('id', (int) $this->filterResponsible)
            ))
            ->when(trim($this->search) !== '', function (Collection $c) {
                $needle = Str::lower(trim($this->search));

                return $c->filter(function (array $row) use ($needle) {
                    $haystack = Str::lower(implode(' ', array_filter([
                        $row['code'],
                        $row['company'],
                        $row['client'],
                        $row['director'],
                        $row['curator'],
                        $row['current_status'],
                        $row['websites']->pluck('url')->implode(' '),
                        collect($row['providers'])->pluck('name')->implode(' '),
                    ])));

                    return str_contains($haystack, $needle);
                });
            });

        $statusOrder = array_flip(Task::ONBOARDING_STATUSES);

        $rows = match ($this->sortBy) {
            'company' => $rows->sortBy(fn (array $row) => Str::lower($row['company'])),
            'status' => $rows->sortBy(fn (array $row) => $statusOrder[$row['status']] ?? 99),
            'updated' => $rows->sortByDesc(fn (array $row) => $row['updated_at']),
            default => $rows->sortBy(fn (array $row) => $row['order']),
        };

        $user = auth()->user();

        return view('livewire.onboarding.index', [
            'rows' => $rows->values(),
            'total' => $allRows->count(),
            'statusCounts' => $statusCounts,
            'statuses' => Task::ONBOARDING_STATUSES,
            'responsibles' => User::whereIn('id', $allRows->pluck('responsible')->flatten()->pluck('id')->unique())->orderBy('name')->get(['id', 'name']),
            'clients' => $allRows->filter(fn (array $row) => $row['client_id'])->unique('client_id')->sortBy('client')->values(),
            'canEdit' => $user->can('manage_tasks') && ! $user->hasRole('curator'),
        ])->layout('layouts.app');
    }
}
