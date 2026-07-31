<?php

namespace App\Livewire\Projects;

use App\Models\Boarding;
use App\Models\Project;
use Livewire\Component;

class BoardingSection extends Component
{
    public Project $project;

    public Boarding $boarding;

    // Form fields
    public string $provider_name = 'Cardaq';

    public ?string $kyb_completed_at = null;

    public ?string $boarding_completed_at = null;

    public ?string $provider_boarding_completed_at = null;

    public string $cfs_verification = 'need_to_complete';

    public string $cardaq_sumsub = 'pending';

    public string $provider_verification_status = 'pending';

    public string $bank_verification = 'not_started';

    public string $companies_house_verification = 'not_started';

    protected array $rules = [
        'provider_name' => 'required|string|max:100',
        'kyb_completed_at' => 'nullable|date',
        'boarding_completed_at' => 'nullable|date',
        'provider_boarding_completed_at' => 'nullable|date',
        'cfs_verification' => 'required|string',
        'cardaq_sumsub' => 'required|string',
        'provider_verification_status' => 'required|string',
        'bank_verification' => 'required|string',
        'companies_house_verification' => 'required|string',
    ];

    public function mount(Project $project): void
    {
        $this->project = $project;

        // Eagerly resolve or create associated boarding details
        $this->boarding = $project->boarding ?? $project->boarding()->create([
            'provider_name' => 'Cardaq',
            'cfs_verification' => 'need_to_complete',
            'cardaq_sumsub' => 'pending',
            'provider_verification_status' => 'pending',
            'bank_verification' => 'not_started',
            'companies_house_verification' => 'not_started',
        ]);

        $this->provider_name = $this->boarding->provider_name ?: 'Cardaq';
        $this->kyb_completed_at = $this->boarding->kyb_completed_at?->format('Y-m-d');
        $this->boarding_completed_at = $this->boarding->boarding_completed_at?->format('Y-m-d');
        $this->provider_boarding_completed_at = $this->boarding->provider_boarding_completed_at?->format('Y-m-d');

        $this->cfs_verification = $this->boarding->cfs_verification;
        $this->cardaq_sumsub = $this->boarding->cardaq_sumsub;
        $this->provider_verification_status = $this->boarding->provider_verification_status ?: 'pending';
        $this->bank_verification = $this->boarding->bank_verification;
        $this->companies_house_verification = $this->boarding->companies_house_verification;
    }

    public function save(): void
    {
        $this->validate();

        $this->boarding->update([
            'provider_name' => $this->provider_name,
            'kyb_completed_at' => $this->kyb_completed_at ?: null,
            'boarding_completed_at' => $this->boarding_completed_at ?: null,
            'provider_boarding_completed_at' => $this->provider_boarding_completed_at ?: null,
            'cfs_verification' => $this->cfs_verification,
            'cardaq_sumsub' => $this->cardaq_sumsub,
            'provider_verification_status' => $this->provider_verification_status,
            'bank_verification' => $this->bank_verification,
            'companies_house_verification' => $this->companies_house_verification,
        ]);

        session()->flash('message', 'Compliance parameters successfully updated.');
    }

    public function render()
    {
        return view('livewire.projects.boarding-section');
    }
}
