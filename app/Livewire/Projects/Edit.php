<?php

namespace App\Livewire\Projects;

use App\Models\Client;
use App\Models\Director;
use App\Models\Project;
use App\Models\User;
use Livewire\Component;

class Edit extends Component
{
    public Project $project;

    // Project fields
    public string $name = '';

    public string $website = '';

    public string $status = '';

    public string $integration_status = '';

    public string $ubo = '';

    public string $mcc = '';

    public string $phone_krisp = '';

    public string $phone_zadarma = '';

    public string $email_corporate = '';

    public string $email_private = '';

    public string $notes = '';

    public ?int $manager_id = null;

    public ?int $client_id = null;

    public string $settlement_bank_1 = '';

    public string $settlement_bank_2 = '';

    // Director fields
    public string $director_name = '';

    public string $director_fee_status = '';

    public ?int $director_managed_by = null;

    public function mount(Project $project): void
    {
        $this->project = $project;
        $this->name = $project->name;
        $primaryWebsite = $project->websites()->orderBy('id')->first();
        $this->website = $primaryWebsite ? $primaryWebsite->url : '';
        $this->status = $project->status;
        $this->integration_status = $project->integration_status ?? 'pending';
        $this->ubo = $project->ubo ?? '';
        $this->mcc = $project->mcc ?? '';
        $this->notes = $project->notes ?? '';
        $this->manager_id = $project->manager_id;
        $this->client_id = $project->client_id;
        $this->settlement_bank_1 = $project->settlement_bank_1 ?? '';
        $this->settlement_bank_2 = $project->settlement_bank_2 ?? '';

        $phones = $project->phones ?? [];
        $this->phone_krisp = $phones['Krisp'] ?? '';
        $this->phone_zadarma = $phones['Zadarma'] ?? '';

        $emails = $project->emails ?? [];
        $this->email_corporate = $emails['Corporate'] ?? '';
        $this->email_private = $emails['Private'] ?? '';

        $director = $project->director;
        if ($director) {
            $this->director_name = $director->name;
            $this->director_fee_status = $director->fee_paid_status;
            $this->director_managed_by = $director->managed_by;
        } else {
            $this->director_name = '';
            $this->director_fee_status = 'unpaid';
            $this->director_managed_by = null;
        }
    }

    protected function rules(): array
    {
        return [
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
            'settlement_bank_1' => 'nullable|string|max:2000',
            'settlement_bank_2' => 'nullable|string|max:2000',
            'director_name' => 'required|string|max:255',
            'director_fee_status' => 'required|string',
            'director_managed_by' => 'nullable|exists:users,id',
        ];
    }

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

        $this->project->update([
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
            'settlement_bank_1' => $this->settlement_bank_1 ?: null,
            'settlement_bank_2' => $this->settlement_bank_2 ?: null,
        ]);

        if ($this->website) {
            $primaryWebsite = $this->project->websites()->orderBy('id')->first();
            if ($primaryWebsite) {
                $primaryWebsite->update(['url' => $this->website]);
            } else {
                $this->project->websites()->create([
                    'name' => 'Main Website',
                    'url' => $this->website,
                ]);
            }
        }

        $this->project->director()->updateOrCreate(
            ['project_id' => $this->project->id],
            [
                'name' => $this->director_name,
                'fee_paid_status' => $this->director_fee_status,
                'managed_by' => $this->director_managed_by ?: null,
            ]
        );

        session()->flash('message', 'Company and director details successfully updated.');
        $this->redirectRoute('projects.show', ['project' => $this->project->id], navigate: true);
    }

    public function render()
    {
        $managers = User::role(['admin', 'manager'])->orderBy('name')->get();
        $allUsers = User::role(['admin', 'manager', 'curator', 'worker'])->orderBy('name')->get();
        $clients = Client::orderBy('name')->get();

        return view('livewire.projects.edit', [
            'managers' => $managers,
            'allUsers' => $allUsers,
            'clients' => $clients,
        ]);
    }
}
