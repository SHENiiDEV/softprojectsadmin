<?php

namespace App\Livewire;

use App\Models\ActivityLog;
use App\Models\Credential;
use App\Models\Project;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

class CredentialVault extends Component
{
    // Filters
    public string $search = '';

    public string $filterType = '';

    public int|string $filterProject = '';

    public string $groupBy = 'project'; // project | type

    // Collections
    public Collection $credentials;

    public Collection $projects;

    public array $types = [];

    // Modal
    public ?Credential $selectedCredential = null;

    public bool $showModal = false;

    public bool $showPassword = false;

    public function mount(): void
    {
        $this->credentials = collect();
        $this->projects = collect();
        $this->loadData();
    }

    public function updatedSearch(): void
    {
        $this->loadData();
    }

    public function updatedFilterType(): void
    {
        $this->loadData();
    }

    public function updatedFilterProject(): void
    {
        $this->loadData();
    }

    public function updatedGroupBy(): void
    {
        $this->loadData();
    }

    protected function loadData(): void
    {
        $user = Auth::user();
        $query = Credential::with(['project', 'website']);

        if ($this->search) {
            $s = $this->search;
            $query->where(fn ($q) => $q->where('name', 'ilike', "%$s%")
                ->orWhere('type', 'ilike', "%$s%")
                ->orWhere('login', 'ilike', "%$s%")
                ->orWhereHas('project', fn ($q2) => $q2->where('name', 'ilike', "%$s%"))
                ->orWhereHas('website', fn ($q2) => $q2->where('url', 'ilike', "%$s%"))
            );
        }

        if ($this->filterType) {
            $query->where('type', $this->filterType);
        }

        if ($this->filterProject) {
            $query->where('project_id', $this->filterProject);
        }

        $this->credentials = $query->orderBy('project_id')->orderBy('type')->get();

        // Sidebar data
        $this->projects = Project::orderBy('name')->get(['id', 'name', 'status']);

        $this->types = $this->credentials->pluck('type')->unique()->filter()->sort()->values()->toArray();
    }

    public function openCredential(int $id): void
    {
        $this->selectedCredential = Credential::with(['project', 'website'])->findOrFail($id);
        $this->showPassword = false;
        $this->showModal = true;

        ActivityLog::create([
            'user_id' => auth()->id(),
            'project_id' => $this->selectedCredential->project_id,
            'action' => 'credential_viewed',
            'description' => 'Credential "'.$this->selectedCredential->name.'" was viewed by '.auth()->user()->name,
        ]);
    }

    public function closeModal(): void
    {
        $this->showModal = false;
        $this->selectedCredential = null;
        $this->showPassword = false;
    }

    public function togglePassword(): void
    {
        $this->showPassword = ! $this->showPassword;

        // Log only when the password is being revealed (not when it's being hidden)
        if ($this->showPassword && $this->selectedCredential) {
            ActivityLog::create([
                'user_id' => auth()->id(),
                'project_id' => $this->selectedCredential->project_id,
                'action' => 'credential_password_revealed',
                'description' => 'Password for credential "'.$this->selectedCredential->name.'" was revealed by '.auth()->user()->name,
            ]);
        }
    }

    public function copyToClipboard(string $field): void
    {
        if ($this->selectedCredential) {
            $this->dispatch('copy-to-clipboard', value: $this->selectedCredential->$field);
        }
    }

    /**
     * Group credentials by project or type
     */
    public function getGroupedCredentials(): Collection
    {
        if ($this->groupBy === 'type') {
            return $this->credentials
                ->groupBy('type')
                ->map(fn ($items, $key) => ['label' => $key ?: 'Other', 'items' => $items])
                ->sortKeys()
                ->values();
        }

        // group by project
        return $this->credentials
            ->groupBy(fn ($c) => $c->project?->name ?? 'Without Company')
            ->map(fn ($items, $key) => ['label' => $key, 'items' => $items])
            ->sortKeys()
            ->values();
    }

    public function render()
    {
        return view('livewire.credential-vault', [
            'grouped' => $this->getGroupedCredentials(),
        ]);
    }
}
