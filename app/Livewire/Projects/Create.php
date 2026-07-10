<?php

namespace App\Livewire\Projects;

use App\Models\Client;
use App\Models\Director;
use App\Models\Project;
use App\Models\User;
use Livewire\Component;

class Create extends Component
{
    // Project fields
    public string $name = '';

    public string $website = '';

    public string $status = 'onboarding';

    public string $integration_status = 'pending';

    public string $ubo = '';

    public string $mcc = '';

    public string $phone_krisp = '';

    public string $phone_zadarma = '';

    public string $email_corporate = '';

    public string $email_private = '';

    public string $notes = '';

    public ?int $manager_id = null;

    public ?int $client_id = null;

    // Director fields
    public string $director_name = '';

    public string $director_fee_status = 'unpaid';

    public ?int $director_managed_by = null;

    protected array $rules = [
        'name' => 'required|string|max:255',
        'website' => 'nullable|url',
        'status' => 'required|string',
        'integration_status' => 'required|string',
        'ubo' => 'nullable|string|max:255',
        'mcc' => 'nullable|string|max:255',
        'phone_krisp' => 'nullable|string',
        'phone_zadarma' => 'nullable|string',
        'email_corporate' => 'nullable|email',
        'email_private' => 'nullable|email',
        'notes' => 'nullable|string',
        'manager_id' => 'nullable|exists:users,id',
        'client_id' => 'nullable|exists:clients,id',
        'director_name' => 'required|string|max:255',
        'director_fee_status' => 'required|string',
        'director_managed_by' => 'nullable|exists:users,id',
    ];

    public function save(): void
    {
        $this->validate();

        $phones = [];
        if ($this->phone_krisp) {
            $phones['Krisp'] = $this->phone_krisp;
        }
        if ($this->phone_zadarma) {
            $phones['Zadarma'] = $this->phone_zadarma;
        }

        $emails = [];
        if ($this->email_corporate) {
            $emails['Corporate'] = $this->email_corporate;
        }
        if ($this->email_private) {
            $emails['Private'] = $this->email_private;
        }

        $project = Project::create([
            'name' => $this->name,
            'status' => $this->status,
            'integration_status' => $this->integration_status ?: null,
            'ubo' => $this->ubo ?: null,
            'mcc' => $this->mcc ?: null,
            'phones' => $phones,
            'emails' => $emails,
            'notes' => $this->notes ?: null,
            'manager_id' => $this->manager_id ?: null,
            'client_id' => $this->client_id ?: null,
        ]);

        if ($this->website) {
            $project->websites()->create([
                'name' => 'Main Website',
                'url' => $this->website,
            ]);
        }

        Director::create([
            'project_id' => $project->id,
            'name' => $this->director_name,
            'fee_paid_status' => $this->director_fee_status,
            'managed_by' => $this->director_managed_by ?: null,
        ]);

        session()->flash('message', 'Company and director successfully added.');
        $this->redirectRoute('projects.index', navigate: true);
    }

    public function render()
    {
        $managers = User::role(['admin', 'manager'])->orderBy('name')->get();
        $allUsers = User::role(['admin', 'manager', 'curator', 'worker'])->orderBy('name')->get();
        $clients = Client::orderBy('name')->get();

        return view('livewire.projects.create', [
            'managers' => $managers,
            'allUsers' => $allUsers,
            'clients' => $clients,
        ]);
    }
}
