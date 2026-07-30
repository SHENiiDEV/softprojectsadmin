<?php

namespace App\Livewire\Clients;

use App\Models\Client;
use App\Models\Project;
use Illuminate\Support\Facades\DB;
use Livewire\Component;
use Livewire\WithPagination;

class Index extends Component
{
    use WithPagination;

    public string $search = '';

    public string $viewMode = 'table'; // table | grid

    public string $filterCompanies = 'all'; // all | with_companies | empty

    public string $sortBy = 'id_desc'; // id_desc | name_asc | companies_desc

    public array $expandedClientIds = [];

    // Modal controls
    public bool $showModal = false;

    public ?int $clientId = null;

    // Form inputs
    public string $name = '';

    protected $queryString = [
        'search' => ['except' => ''],
        'viewMode' => ['except' => 'table'],
        'filterCompanies' => ['except' => 'all'],
        'sortBy' => ['except' => 'id_desc'],
    ];

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function updatingFilterCompanies(): void
    {
        $this->resetPage();
    }

    public function updatingSortBy(): void
    {
        $this->resetPage();
    }

    public function toggleExpand(int $id): void
    {
        if (in_array($id, $this->expandedClientIds)) {
            $this->expandedClientIds = array_values(array_diff($this->expandedClientIds, [$id]));
        } else {
            $this->expandedClientIds[] = $id;
        }
    }

    public function openCreateModal(): void
    {
        $this->resetValidation();
        $this->clientId = null;
        $this->name = '';
        $this->showModal = true;
    }

    public function openEditModal(int $id): void
    {
        $this->resetValidation();
        $client = Client::findOrFail($id);
        $this->clientId = $client->id;
        $this->name = $client->name;
        $this->showModal = true;
    }

    public function saveClient(): void
    {
        if (! auth()->user()->hasAnyRole(['admin', 'manager'])) {
            session()->flash('error', 'You do not have permission to modify clients.');

            return;
        }

        $this->validate([
            'name' => 'required|string|max:255',
        ]);

        if ($this->clientId) {
            $client = Client::findOrFail($this->clientId);
            $client->update([
                'name' => $this->name,
            ]);
            session()->flash('message', 'Client successfully updated.');
        } else {
            Client::create([
                'name' => $this->name,
            ]);
            session()->flash('message', 'Client successfully created.');
        }

        $this->showModal = false;
    }

    public function deleteClient(int $id): void
    {
        if (! auth()->user()->hasAnyRole(['admin', 'manager'])) {
            session()->flash('error', 'You do not have permission to delete clients.');

            return;
        }

        $client = Client::findOrFail($id);
        $client->delete();
        session()->flash('message', 'Client successfully deleted.');
    }

    public function render()
    {
        $like = DB::connection()->getDriverName() === 'pgsql' ? 'ilike' : 'like';

        // KPI stats
        $totalClients = Client::count();
        $totalCompanies = Project::count();

        $query = Client::query()
            ->withCount('companies')
            ->with(['companies' => function ($q) {
                $q->select('id', 'client_id', 'name', 'status', 'ubo', 'archived_at')
                    ->with(['websites' => fn ($w) => $w->select('id', 'project_id', 'url', 'status')]);
            }])
            ->when($this->search, function ($q) use ($like) {
                $q->where('name', $like, '%'.$this->search.'%');
            })
            ->when($this->filterCompanies === 'with_companies', function ($q) {
                $q->has('companies');
            })
            ->when($this->filterCompanies === 'empty', function ($q) {
                $q->doesntHave('companies');
            });

        // Sorting
        match ($this->sortBy) {
            'name_asc' => $query->orderBy('name', 'asc'),
            'companies_desc' => $query->orderBy('companies_count', 'desc'),
            default => $query->orderBy('id', 'desc'),
        };

        $clients = $query->paginate(50);

        return view('livewire.clients.index', [
            'clients' => $clients,
            'totalClients' => $totalClients,
            'totalCompanies' => $totalCompanies,
        ]);
    }
}
