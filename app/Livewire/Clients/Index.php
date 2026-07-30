<?php

namespace App\Livewire\Clients;

use App\Models\Client;
use Illuminate\Support\Facades\DB;
use Livewire\Component;
use Livewire\WithPagination;

class Index extends Component
{
    use WithPagination;

    public string $search = '';

    // Modal controls
    public bool $showModal = false;

    public ?int $clientId = null;

    // Form inputs
    public string $name = '';

    protected $queryString = [
        'search' => ['except' => ''],
    ];

    /**
     * Authorize user access in mount.
     */
    public function mount(): void
    {
        // Allowed for all authenticated users
    }

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    /**
     * Open Modal for creation.
     */
    public function openCreateModal(): void
    {
        $this->resetValidation();
        $this->clientId = null;
        $this->name = '';
        $this->showModal = true;
    }

    /**
     * Open Modal for editing.
     */
    public function openEditModal(int $id): void
    {
        $this->resetValidation();
        $client = Client::findOrFail($id);
        $this->clientId = $client->id;
        $this->name = $client->name;
        $this->showModal = true;
    }

    /**
     * Save newly created or edited client.
     */
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

    /**
     * Delete client from the system. (Admin/Manager only)
     */
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

        $clients = Client::query()
            ->withCount('companies')
            ->when($this->search, function ($query) use ($like) {
                $query->where('name', $like, '%'.$this->search.'%');
            })
            ->orderBy('id', 'desc')
            ->paginate(20);

        return view('livewire.clients.index', [
            'clients' => $clients,
        ]);
    }
}
