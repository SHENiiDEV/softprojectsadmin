<?php

namespace App\Livewire\Projects;

use App\Models\Project;
use App\Models\Report;
use Livewire\Component;

class ReportsSection extends Component
{
    public Project $project;

    public Report $report;

    // Form fields
    public string $reg_number = '';

    public string $auth_code = '';

    public string $registered_address = '';

    public string $ch_pass = '';

    public ?string $accounts_due_by = null;

    public ?string $statements_due_by = null;

    protected array $rules = [
        'reg_number' => 'nullable|string|max:255',
        'auth_code' => 'nullable|string|max:255',
        'registered_address' => 'nullable|string',
        'ch_pass' => 'nullable|string|max:255',
        'accounts_due_by' => 'nullable|date',
        'statements_due_by' => 'nullable|date',
    ];

    public function mount(Project $project): void
    {
        $this->project = $project;
        $this->report = $project->report ?? $project->report()->create();

        $this->reg_number = $this->report->reg_number ?? '';
        $this->auth_code = $this->report->auth_code ?? '';
        $this->registered_address = $this->report->registered_address ?? '';
        $this->ch_pass = $this->report->ch_pass ?? '';
        $this->accounts_due_by = $this->report->accounts_due_by?->format('Y-m-d');
        $this->statements_due_by = $this->report->statements_due_by?->format('Y-m-d');
    }

    public function save(): void
    {
        $this->validate();

        $this->report->update([
            'reg_number' => $this->reg_number ?: null,
            'auth_code' => $this->auth_code ?: null,
            'registered_address' => $this->registered_address ?: null,
            'ch_pass' => $this->ch_pass ?: null,
            'accounts_due_by' => $this->accounts_due_by ?: null,
            'statements_due_by' => $this->statements_due_by ?: null,
        ]);

        session()->flash('message', 'Reports and deadlines successfully updated.');
    }

    public function render()
    {
        $now = now()->startOfDay();
        $daysUntilAccounts = $this->report->accounts_due_by
            ? $now->diffInDays($this->report->accounts_due_by, false)
            : null;

        $daysUntilStatements = $this->report->statements_due_by
            ? $now->diffInDays($this->report->statements_due_by, false)
            : null;

        return view('livewire.projects.reports-section', [
            'daysUntilAccounts' => $daysUntilAccounts,
            'daysUntilStatements' => $daysUntilStatements,
        ]);
    }
}
