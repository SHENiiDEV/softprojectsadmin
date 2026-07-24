<?php

namespace App\Livewire\Projects;

use App\Models\Client;
use App\Models\Project;
use App\Models\ProjectNote;
use Illuminate\Support\Facades\DB;
use Livewire\Component;
use Livewire\WithPagination;

class Index extends Component
{
    use WithPagination;

    public string $search = '';

    public string $status = '';

    public string $integrationStatus = '';

    public string $filterClient = '';

    public string $showArchived = '0'; // 0 = active, 1 = archived

    public string $layout = 'table'; // table | grid

    public bool $showNoteModal = false;

    public ?int $noteProjectId = null;

    public string $noteProjectName = '';

    public string $noteContent = '';

    protected $queryString = [
        'search' => ['except' => ''],
        'status' => ['except' => ''],
        'integrationStatus' => ['except' => ''],
        'filterClient' => ['except' => ''],
        'showArchived' => ['except' => '0'],
        'layout' => ['except' => 'table'],
    ];

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function updatingStatus(): void
    {
        $this->resetPage();
    }

    public function updatingIntegrationStatus(): void
    {
        $this->resetPage();
    }

    public function updatingFilterClient(): void
    {
        $this->resetPage();
    }

    public function updatingShowArchived(): void
    {
        $this->resetPage();
    }

    public function updatingLayout(): void
    {
        $this->resetPage();
    }

    public function archiveProject(int $id): void
    {
        $project = Project::findOrFail($id);
        $project->update(['archived_at' => now()]);
        session()->flash('message', 'Company archived.');
    }

    public function unarchiveProject(int $id): void
    {
        $project = Project::findOrFail($id);
        $project->update(['archived_at' => null]);
        session()->flash('message', 'Company restored.');
    }

    public function deleteProject(int $id): void
    {
        $project = Project::findOrFail($id);
        $project->delete();
        session()->flash('message', 'Company successfully deleted.');
    }

    public function openNoteModal(int $projectId): void
    {
        $project = Project::findOrFail($projectId);
        $this->noteProjectId = $project->id;
        $this->noteProjectName = $project->name;
        $this->noteContent = '';
        $this->showNoteModal = true;
        $this->resetErrorBag();
    }

    public function saveNote(): void
    {
        $this->validate([
            'noteContent' => 'required|string|min:2|max:5000',
        ]);

        ProjectNote::create([
            'project_id' => $this->noteProjectId,
            'user_id' => auth()->id(),
            'content' => $this->noteContent,
        ]);

        $this->showNoteModal = false;
        $this->noteProjectId = null;
        $this->noteContent = '';

        session()->flash('message', 'Note successfully added.');
    }

    public function render()
    {
        $like = DB::connection()->getDriverName() === 'pgsql' ? 'ilike' : 'like';

        $projects = Project::query()
            ->with(['director', 'manager', 'client'])
            ->when($this->search, function ($query) use ($like) {
                $query->where(function ($q) use ($like) {
                    $q->where('name', $like, '%'.$this->search.'%')
                        ->orWhere('ubo', $like, '%'.$this->search.'%')
                        ->orWhere('mcc', $like, '%'.$this->search.'%')
                        ->orWhereHas('director', function ($dq) use ($like) {
                            $dq->where('name', $like, '%'.$this->search.'%');
                        });
                });
            })
            ->when($this->status, function ($query) {
                $query->where('status', $this->status);
            })
            ->when($this->integrationStatus, function ($query) {
                $query->where('integration_status', $this->integrationStatus);
            })
            ->when($this->filterClient, function ($query) {
                if ($this->filterClient === 'none') {
                    $query->whereNull('client_id');
                } else {
                    $query->where('client_id', $this->filterClient);
                }
            })
            ->when(
                $this->showArchived === '1',
                fn ($q) => $q->archived(),
                fn ($q) => $q->notArchived()
            )
            ->orderBy('id', 'desc')
            ->paginate(12); // Upped to 12 as it splits better for 2/3/4 grid columns

        $clients = Client::orderBy('name')->get();

        $stats = [
            'total' => Project::notArchived()->count(),
            'active' => Project::notArchived()->where('status', 'active')->count(),
            'onboarding' => Project::notArchived()->where('status', 'onboarding')->count(),
            'suspended' => Project::notArchived()->where('status', 'suspended')->count(),
        ];

        return view('livewire.projects.index', [
            'projects' => $projects,
            'clients' => $clients,
            'stats' => $stats,
        ]);
    }
}
