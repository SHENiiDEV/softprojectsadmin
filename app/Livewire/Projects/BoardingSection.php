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
    public ?string $kyb_completed_at = null;

    public ?string $boarding_completed_at = null;

    public string $cfs_verification = 'need_to_complete';

    public string $cardaq_sumsub = 'pending';

    public string $bank_verification = 'not_started';

    public string $companies_house_verification = 'not_started';

    protected array $rules = [
        'kyb_completed_at' => 'nullable|date',
        'boarding_completed_at' => 'nullable|date',
        'cfs_verification' => 'required|string',
        'cardaq_sumsub' => 'required|string',
        'bank_verification' => 'required|string',
        'companies_house_verification' => 'required|string',
    ];

    public function mount(Project $project): void
    {
        $this->project = $project;

        // Eagerly resolve or create associated boarding details
        $this->boarding = $project->boarding ?? $project->boarding()->create([
            'cfs_verification' => 'need_to_complete',
            'cardaq_sumsub' => 'pending',
            'bank_verification' => 'not_started',
            'companies_house_verification' => 'not_started',
        ]);

        $this->kyb_completed_at = $this->boarding->kyb_completed_at?->format('Y-m-d');
        $this->boarding_completed_at = $this->boarding->boarding_completed_at?->format('Y-m-d');
        $this->cfs_verification = $this->boarding->cfs_verification;
        $this->cardaq_sumsub = $this->boarding->cardaq_sumsub;
        $this->bank_verification = $this->boarding->bank_verification;
        $this->companies_house_verification = $this->boarding->companies_house_verification;
    }

    public function save(): void
    {
        $this->validate();

        $this->boarding->update([
            'kyb_completed_at' => $this->kyb_completed_at ?: null,
            'boarding_completed_at' => $this->boarding_completed_at ?: null,
            'cfs_verification' => $this->cfs_verification,
            'cardaq_sumsub' => $this->cardaq_sumsub,
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
