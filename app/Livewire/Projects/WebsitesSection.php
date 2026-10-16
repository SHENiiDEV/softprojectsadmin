<?php

namespace App\Livewire\Projects;

use App\Models\Project;
use App\Models\Website;
use App\Services\GatewayService;
use Livewire\Component;

class WebsitesSection extends Component
{
    public const AVAILABLE_GATEWAYS = [
        'Blik',
        'Cardaq',
        'Colibrix',
        'Decta',
        'Dimoco',
        'EcomPay',
        'Exactly',
        'Ex-Payments',
        'Fenige',
        'Fibonatix',
        'GuruPay',
        'JFX',
        'Madfin',
        'MIA',
        'Moneliq',
        'NexPay',
        'OroPay',
        'Payally',
        'Paynt',
        'PaySafeCard',
        'Paystrax',
        'Paytently',
        'Pixxles',
        'Rapyd',
        'Revolut',
        'Skrill',
        'Stripe',
        'Syspay',
        'ThePaymentFactory',
        'Trustpayments',
        'Vialet',
        'Walletto',
        'Xsell',
    ];

    public Project $project;

    public bool $showForm = false;

    public ?int $editingId = null;

    // Form fields
    public string $name = '';

    public string $url = '';

    public string $status = 'No integration';

    public array $gateways = [];

    public string $visa_status = 'Stopped';

    public string $mastercard_status = 'Stopped';

    // Quick add & search for gateways
    public string $newGatewayName = '';

    public string $gatewaySearch = '';

    // Transfer Modal
    public bool $showTransferModal = false;

    public ?int $transferWebsiteId = null;

    public string $transferWebsiteName = '';

    public ?int $transferToProjectId = null;

    /**
     * Get all available gateway providers, filtered by search if present.
     *
     * @return array<int, string>
     */
    public function getAvailableGatewaysProperty(): array
    {
        $all = GatewayService::all();

        if (trim($this->gatewaySearch) !== '') {
            $search = mb_strtolower(trim($this->gatewaySearch));
            $all = array_filter($all, fn ($g) => str_contains(mb_strtolower($g), $search));
        }

        return array_values($all);
    }

    /**
     * Quickly add a new gateway provider right from the UI.
     */
    public function addNewGateway(): void
    {
        $name = trim($this->newGatewayName);
        if ($name === '') {
            $this->addError('newGatewayName', 'Gateway name cannot be empty.');

            return;
        }

        $added = GatewayService::add($name);

        if (! in_array($added, $this->gateways, true)) {
            $this->gateways[] = $added;
        }

        $this->newGatewayName = '';
        $this->resetErrorBag('newGatewayName');
        session()->flash('gateway_added_message', "Gateway '{$added}' added and selected!");
    }

    /**
     * Remove a gateway provider from the available list.
     */
    public function removeGateway(string $name): void
    {
        GatewayService::remove($name);

        $this->gateways = array_values(array_filter($this->gateways, fn ($g) => $g !== $name));

        session()->flash('gateway_added_message', "Gateway '{$name}' has been removed from available providers.");
    }

    protected function rules(): array
    {
        $available = GatewayService::all();

        return [
            'name' => 'required|string|max:255',
            'url' => 'required|url',
            'status' => 'required|in:Live,Test,No integration',
            'gateways' => 'nullable|array',
            'gateways.*' => 'string|in:'.implode(',', $available),
            'visa_status' => 'required|in:In Progress,Working,Stopped',
            'mastercard_status' => 'required|in:In Progress,Working,Stopped',
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
        $this->visa_status = $website->visa_status ?? 'Stopped';
        $this->mastercard_status = $website->mastercard_status ?? 'Stopped';
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
                'visa_status' => $this->visa_status,
                'mastercard_status' => $this->mastercard_status,
            ]);
            session()->flash('message', 'Website successfully updated.');
        } else {
            $this->project->websites()->create([
                'name' => $this->name,
                'url' => $this->url,
                'status' => $this->status,
                'gateways' => $this->gateways,
                'visa_status' => $this->visa_status,
                'mastercard_status' => $this->mastercard_status,
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

    public function openTransferModal(int $websiteId): void
    {
        $website = Website::findOrFail($websiteId);
        $this->transferWebsiteId = $website->id;
        $this->transferWebsiteName = $website->name.' ('.$website->url.')';
        $this->transferToProjectId = null;
        $this->showTransferModal = true;
        $this->resetErrorBag();
    }

    public function closeTransferModal(): void
    {
        $this->showTransferModal = false;
        $this->transferWebsiteId = null;
        $this->transferWebsiteName = '';
        $this->transferToProjectId = null;
    }

    public function transferWebsite(): void
    {
        $this->validate([
            'transferToProjectId' => 'required|exists:projects,id|different:project.id',
        ], [
            'transferToProjectId.required' => 'Please select a target company.',
            'transferToProjectId.different' => 'The target company must be different from the current one.',
        ]);

        $website = Website::findOrFail($this->transferWebsiteId);
        $targetProject = Project::findOrFail($this->transferToProjectId);

        $website->update(['project_id' => $this->transferToProjectId]);

        $this->closeTransferModal();

        session()->flash('message', "Website '{$website->name}' successfully transferred to '{$targetProject->name}'.");
    }

    private function resetForm(): void
    {
        $this->showForm = false;
        $this->editingId = null;
        $this->name = '';
        $this->url = '';
        $this->status = 'No integration';
        $this->gateways = [];
        $this->visa_status = 'Stopped';
        $this->mastercard_status = 'Stopped';
        $this->newGatewayName = '';
        $this->gatewaySearch = '';
        $this->resetValidation();
    }

    public function render()
    {
        $websites = $this->project->websites()->orderBy('id', 'desc')->get();

        $allProjects = Project::where('id', '!=', $this->project->id)
            ->notArchived()
            ->orderBy('name')
            ->get(['id', 'name']);

        return view('livewire.projects.websites-section', [
            'websites' => $websites,
            'allProjects' => $allProjects,
        ]);
    }
}
