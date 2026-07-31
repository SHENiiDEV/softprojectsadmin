<?php

namespace App\Livewire;

use App\Models\ActivityLog;
use App\Models\Client;
use App\Models\Credential;
use App\Models\Project;
use App\Models\Website;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Livewire\Component;
use Livewire\WithFileUploads;

class CredentialVault extends Component
{
    use WithFileUploads;

    // Filters
    public string $search = '';

    public string $filterType = '';

    public int|string $filterProject = '';

    public string $groupBy = 'project'; // project | type

    // Collections
    public Collection $credentials;

    public Collection $projects;

    public array $types = [];

    // View Modal
    public ?Credential $selectedCredential = null;

    public bool $showModal = false;

    public bool $showPassword = false;

    // Import Modal
    public bool $showImportModal = false;

    public $importFile = null;

    public string $rawJsonInput = '';

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
        $query = Credential::with(['project', 'website']);

        if ($this->search) {
            $s = $this->search;
            $like = DB::connection()->getDriverName() === 'pgsql' ? 'ilike' : 'like';
            $query->where(fn ($q) => $q->where('name', $like, "%$s%")
                ->orWhere('type', $like, "%$s%")
                ->orWhere('login', $like, "%$s%")
                ->orWhereHas('project', fn ($q2) => $q2->where('name', $like, "%$s%"))
                ->orWhereHas('website', fn ($q2) => $q2->where('url', $like, "%$s%"))
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

    public function openImportModal(): void
    {
        $this->importFile = null;
        $this->rawJsonInput = '';
        $this->showImportModal = true;
    }

    public function closeImportModal(): void
    {
        $this->showImportModal = false;
        $this->importFile = null;
        $this->rawJsonInput = '';
    }

    public function processImport(): void
    {
        $jsonString = '';

        if ($this->importFile) {
            $jsonString = file_get_contents($this->importFile->getRealPath());
        } elseif (! empty($this->rawJsonInput)) {
            $jsonString = $this->rawJsonInput;
        } else {
            session()->flash('error', 'Please upload a JSON file or paste JSON content.');

            return;
        }

        $data = json_decode($jsonString, true);
        if (! is_array($data)) {
            session()->flash('error', 'Invalid JSON format. Please check the JSON syntax.');

            return;
        }

        $like = DB::connection()->getDriverName() === 'pgsql' ? 'ilike' : 'like';
        $imported = 0;
        $createdProjects = 0;
        $defaultClient = Client::firstOrCreate(['name' => 'Imported Clients']);

        foreach ($data as $item) {
            $projectId = $item['project_id'] ?? $item['company_id'] ?? null;
            $companyName = trim($item['company_name'] ?? $item['project_name'] ?? $item['company'] ?? $item['project'] ?? '');
            $websiteUrl = trim($item['website_url'] ?? $item['url'] ?? $item['website'] ?? '');
            $name = trim($item['name'] ?? $item['title'] ?? $item['label'] ?? 'Access Credential');
            $type = strtolower(trim($item['type'] ?? 'other'));
            $providerUrl = trim($item['provider_url'] ?? $item['login_url'] ?? $item['portal'] ?? '');
            $login = trim($item['login'] ?? $item['username'] ?? $item['email'] ?? $item['user'] ?? '');
            $password = $item['password'] ?? $item['pass'] ?? $item['secret'] ?? '';
            $comments = trim($item['comments'] ?? $item['notes'] ?? $item['description'] ?? '');
            $fields = $item['fields'] ?? null;

            if (empty($companyName) && empty($websiteUrl) && empty($projectId)) {
                continue;
            }

            // Match Project
            $project = null;
            if ($projectId) {
                $project = Project::find($projectId);
            }

            if (! $project && $companyName) {
                $project = Project::where('name', $like, $companyName)->first();
            }

            if (! $project && $websiteUrl) {
                $host = parse_url($websiteUrl, PHP_URL_HOST) ?? $websiteUrl;
                $project = Project::whereHas('websites', fn ($q) => $q->where('url', $like, "%{$host}%"))->first();
            }

            if (! $project) {
                $projectName = $companyName ?: ($host ?? 'Imported Project');
                $project = Project::create([
                    'client_id' => $defaultClient->id,
                    'name' => $projectName,
                    'status' => 'active',
                    'ubo' => 'Imported',
                ]);
                $createdProjects++;
            }

            // Match Website
            $websiteId = null;
            if ($websiteUrl) {
                $website = Website::firstOrCreate(
                    ['project_id' => $project->id, 'url' => $websiteUrl],
                    ['name' => 'Main Website']
                );
                $websiteId = $website->id;
            }

            // Save Credential
            Credential::create([
                'project_id' => $project->id,
                'website_id' => $websiteId,
                'name' => $name,
                'type' => $type ?: 'other',
                'provider_url' => $providerUrl ?: null,
                'login' => $login ?? '',
                'password' => $password !== null ? (string) $password : '',
                'comments' => $comments ?: null,
                'fields' => is_array($fields) ? $fields : null,
            ]);

            $imported++;
        }

        $this->closeImportModal();
        $this->loadData();

        session()->flash('message', "Successfully imported {$imported} credentials ({$createdProjects} new companies created).");
    }

    public function togglePassword(): void
    {
        $this->showPassword = ! $this->showPassword;

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

    public function getGroupedCredentials(): Collection
    {
        if ($this->groupBy === 'type') {
            return $this->credentials
                ->groupBy('type')
                ->map(fn ($items, $key) => ['label' => $key ?: 'Other', 'items' => $items])
                ->sortKeys()
                ->values();
        }

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
