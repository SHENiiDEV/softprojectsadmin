<?php

namespace App\Livewire\ScamLinks;

use App\Livewire\Concerns\RequiresPermission;
use App\Models\ActivityLog;
use App\Models\ScamLink;
use App\Services\ScamLinkChecker;
use Livewire\Component;
use Livewire\WithPagination;

class Index extends Component
{
    use RequiresPermission;
    use WithPagination;

    /** Links to check against the list. */
    public string $checkText = '';

    /** Domains to add to the list. */
    public string $newLinks = '';

    public string $newNote = '';

    public string $search = '';

    /** Result of the last "Add to list". */
    public ?array $addReport = null;

    /**
     * @var array<string, array<string, string|null>>
     */
    protected $queryString = [
        'search' => ['except' => ''],
    ];

    public function mount(): void
    {
        $this->requirePermission('view_scam_links');
    }

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function clearCheck(): void
    {
        $this->checkText = '';
    }

    public function addLinks(ScamLinkChecker $checker): void
    {
        $this->requirePermission('manage_scam_links');

        $this->validate([
            'newLinks' => 'required|string|max:'.ScamLinkChecker::MAX_INPUT_LENGTH,
            'newNote' => 'nullable|string|max:500',
        ], [], ['newLinks' => 'links', 'newNote' => 'note']);

        $report = $checker->import($this->newLinks, $this->newNote, auth()->id());

        if ($report['added'] !== []) {
            ActivityLog::create([
                'user_id' => auth()->id(),
                'action' => 'scam_links_added',
                'description' => 'Scam links: added '.count($report['added']).' ('.collect($report['added'])->take(5)->implode(', ').(count($report['added']) > 5 ? ', …' : '').') by '.auth()->user()->name,
            ]);
        }

        $this->addReport = $report;

        // keep only what could not be used, so it can be fixed and sent again
        $this->newLinks = implode("\n", $report['invalid']);
        if ($report['invalid'] === []) {
            $this->newNote = '';
        }

        $this->resetPage();
    }

    public function dismissReport(): void
    {
        $this->addReport = null;
    }

    public function deleteLink(int $id): void
    {
        $this->requirePermission('manage_scam_links');

        $link = ScamLink::findOrFail($id);
        $link->delete();

        ActivityLog::create([
            'user_id' => auth()->id(),
            'action' => 'scam_link_removed',
            'description' => "Scam links: removed {$link->domain} by ".auth()->user()->name,
        ]);

        session()->flash('message', "{$link->domain} removed from the list.");
    }

    public function updateNote(int $id, string $note): void
    {
        $this->requirePermission('manage_scam_links');

        ScamLink::findOrFail($id)->update(['note' => filled($note) ? mb_substr(trim($note), 0, 500) : null]);
    }

    public function render(ScamLinkChecker $checker)
    {
        $results = trim($this->checkText) === '' ? [] : $checker->check($this->checkText);

        $links = ScamLink::query()
            ->with('creator:id,name')
            ->when(trim($this->search) !== '', function ($query) {
                $needle = '%'.str_replace(['!', '%', '_'], ['!!', '!%', '!_'], trim($this->search)).'%';
                $query->where(fn ($q) => $q->whereRaw("domain like ? escape '!'", [$needle])->orWhereRaw("note like ? escape '!'", [$needle]));
            })
            ->orderBy('domain')
            ->paginate(30);

        return view('livewire.scam-links.index', [
            'results' => $results,
            'blocked' => array_values(array_filter($results, fn (array $row) => $row['blocked'])),
            'clean' => array_values(array_filter($results, fn (array $row) => ! $row['blocked'])),
            'links' => $links,
            'total' => ScamLink::count(),
            'canManage' => auth()->user()->can('manage_scam_links'),
        ])->layout('layouts.app');
    }
}
