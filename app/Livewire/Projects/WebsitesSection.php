<?php

namespace App\Livewire\Projects;

use App\Models\Project;
use App\Models\Website;
use Livewire\Component;

class WebsitesSection extends Component
{
    public const AVAILABLE_GATEWAYS = [
        'Cardaq',
        'Madfin',
        'ThePaymentFactory',
        'Decta',
        'Xsell',
        'Fenige',
        'Walletto',
        'Vialet',
        'Exactly',
        'JFX',
        'NexPay',
        'Paytently',
        'EcomPay',
        'Trustpayments',
        'Skrill',
        'PaySafeCard',
        'Blik',
        'Paystrax',
        'Rapyd',
        'MIA',
        'Pixxles',
        'Syspay',
        'Revolut',
    ];

    public Project $project;

    public bool $showForm = false;

    public ?int $editingId = null;

    // Form fields
    public string $name = '';

    public string $url = '';

    public string $status = 'No integration';

    public array $gateways = [];

    protected function rules(): array
    {
        return [
            'name' => 'required|string|max:255',
            'url' => 'required|url',
            'status' => 'required|in:Live,Test,No integration',
            'gateways' => 'nullable|array',
            'gateways.*' => 'string|in:'.implode(',', self::AVAILABLE_GATEWAYS),
        ];
    }

    public function mount(Project $project): void
    {
        $this->project = $project;
    }

    public function openCreateForm(): void
    {
        $this->resetForm();
        $this->showForm = true;
    }

    public function edit(int $id): void
    {
        $this->resetForm();
        $website = Website::findOrFail($id);
        $this->editingId = $website->id;
        $this->name = $website->name;
        $this->url = $website->url;
        $this->status = $website->status ?? 'No integration';
        $this->gateways = $website->gateways ?? [];
        $this->showForm = true;
    }

    public function save(): void
    {
        $this->validate();

        if ($this->editingId) {
            $website = Website::findOrFail($this->editingId);
            $website->update([
                'name' => $this->name,
                'url' => $this->url,
                'status' => $this->status,
                'gateways' => $this->gateways,
            ]);
            session()->flash('message', 'Website successfully updated.');
        } else {
            $this->project->websites()->create([
                'name' => $this->name,
                'url' => $this->url,
                'status' => $this->status,
                'gateways' => $this->gateways,
            ]);
            session()->flash('message', 'New website successfully added.');
        }

        $this->resetForm();
    }

    public function delete(int $id): void
    {
        $website = Website::findOrFail($id);
        $website->delete();
        session()->flash('message', 'Website successfully deleted.');
    }

    public function cancel(): void
    {
        $this->resetForm();
    }

    private function resetForm(): void
    {
        $this->showForm = false;
        $this->editingId = null;
        $this->name = '';
        $this->url = '';
        $this->status = 'No integration';
        $this->gateways = [];
        $this->resetValidation();
    }

    public function render()
    {
        $websites = $this->project->websites()->orderBy('id', 'desc')->get();

        return view('livewire.projects.websites-section', [
            'websites' => $websites,
        ]);
    }
}
