<?php

namespace App\Livewire\Onboarding;

use App\Livewire\Concerns\EditsOnboardingTasks;
use App\Livewire\Concerns\RequiresPermission;
use App\Models\Task;
use App\Models\User;
use App\Services\OnboardingBoard;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Livewire\Component;

class Index extends Component
{
    use EditsOnboardingTasks;
    use RequiresPermission;

    public string $search = '';

    public string $filterStatus = '';

    public string $filterResponsible = '';

    public string $filterClient = '';

    public string $sortBy = 'id'; // id | company | status | updated

    /** Code (ONB-001 / TASK-27) of the row shown in the side panel. */
    public ?string $selected = null;

    /**
     * @var array<string, array<string, string|null>>
     */
    protected $queryString = [
        'search' => ['except' => ''],
        'filterStatus' => ['except' => '', 'as' => 'status'],
        'filterResponsible' => ['except' => '', 'as' => 'kyb'],
        'filterClient' => ['except' => '', 'as' => 'client'],
        'sortBy' => ['except' => 'id'],
        'selected' => ['except' => null, 'as' => 'open'],
    ];

    public function mount(): void
    {
        $this->requirePermission('view_tasks');
    }

    public function select(string $code): void
    {
        $this->selected = $code;
    }

    public function closeDetail(): void
    {
        $this->selected = null;
    }

    public function resetFilters(): void
    {
        $this->reset(['search', 'filterStatus', 'filterResponsible', 'filterClient']);
        $this->sortBy = 'id';
    }

    public function render(OnboardingBoard $board)
    {
        $allRows = $board->rows();

        // Everything except the status filter: the status chips and the total count what is left for this
        // client / person / search, so picking a KYB responsible updates the numbers on top.
        $scoped = $allRows
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

        $statusCounts = $scoped->countBy('status');

        $rows = $scoped->when($this->filterStatus !== '', fn (Collection $c) => $c->where('status', $this->filterStatus));

        $statusOrder = array_flip(Task::ONBOARDING_STATUSES);

        $rows = match ($this->sortBy) {
            'company' => $rows->sortBy(fn (array $row) => Str::lower($row['company'])),
            'status' => $rows->sortBy(fn (array $row) => $statusOrder[$row['status']] ?? 99),
            'updated' => $rows->sortByDesc(fn (array $row) => $row['updated_at']),
            default => $rows->sortBy(fn (array $row) => $row['order']),
        };

        $detail = $this->selected !== null ? $allRows->firstWhere('code', $this->selected) : null;
        $taskIds = $detail === null ? collect() : $detail['tasks']->pluck('id');

        return view('livewire.onboarding.index', [
            'detail' => $detail,
            'detailComments' => $detail === null ? collect() : $board->comments($taskIds),
            'detailHistory' => $detail === null ? collect() : $board->history($taskIds),
            'rows' => $rows->values(),
            'total' => $scoped->count(),
            'statusCounts' => $statusCounts,
            'statuses' => Task::ONBOARDING_STATUSES,
            'responsibles' => User::whereIn('id', $allRows->pluck('responsible')->flatten()->pluck('id')->unique())->orderBy('name')->get(['id', 'name'])
                ->each(fn (User $person) => $person->onboarding_count = $allRows->filter(fn (array $row) => $row['responsible']->contains('id', $person->id))->count()),
            'clients' => $allRows->filter(fn (array $row) => $row['client_id'])->unique('client_id')->sortBy('client')->values(),
            'canEdit' => $this->canEditOnboarding(),
        ])->layout('layouts.app');
    }
}
