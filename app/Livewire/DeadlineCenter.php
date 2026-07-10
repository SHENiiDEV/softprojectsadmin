<?php
namespace App\Livewire;

use Livewire\Component;
use App\Models\Task;
use App\Models\Report;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class DeadlineCenter extends Component
{
    public string $filter = 'all'; // all, today, week, month, overdue
    public string $typeFilter = ''; // '', 'task', 'report'

    public Collection $grouped;
    public array $stats = [];

    public function mount(): void
    {
        $this->grouped = collect();
        $this->loadDeadlines();
    }

    public function updatedFilter(): void    { $this->loadDeadlines(); }
    public function updatedTypeFilter(): void { $this->loadDeadlines(); }

    public function loadDeadlines(): void
    {
        $now   = Carbon::now();
        $items = collect();

        // ── Tasks ────────────────────────────────────────────────────────────
        if ($this->typeFilter === '' || $this->typeFilter === 'task') {
            $tasksQuery = Task::query()
                ->whereNotNull('due_date')
                ->whereHas('project', fn($q) => $q->notArchived())
                ->with(['project', 'assignee']);

            $this->applyDateFilter($tasksQuery, 'due_date');

            $tasks = $tasksQuery->get()->map(fn($t) => (object)[
                'sort_key'    => Carbon::parse($t->due_date)->timestamp,
                'due_at'      => Carbon::parse($t->due_date),
                'type'        => 'task',
                'title'       => $t->title,
                'description' => $t->description,
                'project'     => $t->project,
                'assignee'    => $t->assignee,
                'status'      => $t->status,
                'priority'    => $t->priority,
                'model_id'    => $t->id,
            ]);

            $items = $items->concat($tasks);
        }

        // ── Reports ──────────────────────────────────────────────────────────
        if ($this->typeFilter === '' || $this->typeFilter === 'report') {
            $reports = Report::whereNotNull('accounts_due_by')
                ->orWhereNotNull('statements_due_by')
                ->whereHas('project', fn($q) => $q->notArchived())
                ->with('project')
                ->get();

            foreach ($reports as $report) {
                foreach ([
                    'accounts_due_by'   => 'Accounts Due',
                    'statements_due_by' => 'Confirmation Statements',
                ] as $column => $label) {
                    if (!$report->$column) continue;

                    $date = Carbon::parse($report->$column);
                    if (!$this->dateInFilter($date)) continue;

                    $items->push((object)[
                        'sort_key'    => $date->timestamp,
                        'due_at'      => $date,
                        'type'        => 'report',
                        'title'       => $label,
                        'description' => null,
                        'project'     => $report->project,
                        'assignee'    => null,
                        'status'      => null,
                        'priority'    => null,
                        'model_id'    => $report->id,
                    ]);
                }
            }
        }

        $sorted = $items->sortBy('sort_key')->values();

        // ── Stats ─────────────────────────────────────────────────────────────
        $allItems = $this->getAllItems();
        $this->stats = [
            'overdue' => $allItems->filter(fn($i) => $i->due_at->lt($now->copy()->startOfDay()))->count(),
            'today'   => $allItems->filter(fn($i) => $i->due_at->isToday())->count(),
            'week'    => $allItems->filter(fn($i) => $i->due_at->isBetween($now->copy()->startOfWeek(), $now->copy()->endOfWeek()))->count(),
            'total'   => $allItems->count(),
        ];

        // ── Group by date section ─────────────────────────────────────────────
        $this->grouped = $this->groupBySection($sorted, $now);
    }

    private function getAllItems(): Collection
    {
        $items = collect();

        $tasks = Task::whereNotNull('due_date')
            ->whereHas('project', fn($q) => $q->notArchived())
            ->with(['project', 'assignee'])->get()
            ->map(fn($t) => (object)[
                'due_at' => Carbon::parse($t->due_date),
                'type'   => 'task',
            ]);
        $items = $items->concat($tasks);

        $reports = Report::where(fn($q) => $q->whereNotNull('accounts_due_by')->orWhereNotNull('statements_due_by'))
            ->whereHas('project', fn($q) => $q->notArchived())
            ->get();
        foreach ($reports as $r) {
            if ($r->accounts_due_by)   $items->push((object)['due_at' => Carbon::parse($r->accounts_due_by),   'type' => 'report']);
            if ($r->statements_due_by) $items->push((object)['due_at' => Carbon::parse($r->statements_due_by), 'type' => 'report']);
        }

        return $items;
    }

    private function applyDateFilter($query, string $column): void
    {
        $now = Carbon::now();
        match ($this->filter) {
            'today'  => $query->whereBetween($column, [$now->copy()->startOfDay(), $now->copy()->endOfDay()]),
            'week'   => $query->whereBetween($column, [$now->copy()->startOfWeek(), $now->copy()->endOfWeek()]),
            'month'  => $query->whereBetween($column, [$now->copy()->startOfMonth(), $now->copy()->endOfMonth()]),
            'overdue'=> $query->where($column, '<', $now->copy()->startOfDay())->whereNotIn('status', ['done']),
            default  => null, // all
        };
    }

    private function dateInFilter(Carbon $date): bool
    {
        $now = Carbon::now();
        return match ($this->filter) {
            'today'  => $date->isToday(),
            'week'   => $date->isBetween($now->copy()->startOfWeek(), $now->copy()->endOfWeek()),
            'month'  => $date->isBetween($now->copy()->startOfMonth(), $now->copy()->endOfMonth()),
            'overdue'=> $date->lt($now->copy()->startOfDay()),
            default  => true,
        };
    }

    private function groupBySection(Collection $items, Carbon $now): Collection
    {
        $sections = collect();

        $overdue  = $items->filter(fn($i) => $i->due_at->lt($now->copy()->startOfDay()) && $i->type === 'task' && $i->status !== 'done' || ($i->type === 'report' && $i->due_at->lt($now->copy()->startOfDay())));
        $today    = $items->filter(fn($i) => $i->due_at->isToday());
        $tomorrow = $items->filter(fn($i) => $i->due_at->isTomorrow());
        $thisWeek = $items->filter(fn($i) => !$i->due_at->isToday() && !$i->due_at->isTomorrow() && $i->due_at->isBetween($now->copy()->startOfWeek(), $now->copy()->endOfWeek()) && $i->due_at->gte($now->copy()->startOfDay()));
        $later    = $items->filter(fn($i) => $i->due_at->gt($now->copy()->endOfWeek()));

        if ($overdue->isNotEmpty())  $sections->push(['label' => 'Overdue', 'key' => 'overdue', 'items' => $overdue->values()]);
        if ($today->isNotEmpty())    $sections->push(['label' => 'Today', 'key' => 'today', 'items' => $today->values()]);
        if ($tomorrow->isNotEmpty()) $sections->push(['label' => 'Tomorrow', 'key' => 'tomorrow', 'items' => $tomorrow->values()]);
        if ($thisWeek->isNotEmpty()) $sections->push(['label' => 'This Week', 'key' => 'week', 'items' => $thisWeek->values()]);
        if ($later->isNotEmpty())    $sections->push(['label' => 'Later', 'key' => 'later', 'items' => $later->values()]);

        return $sections;
    }

    public function render()
    {
        return view('livewire.deadline-center');
    }
}
