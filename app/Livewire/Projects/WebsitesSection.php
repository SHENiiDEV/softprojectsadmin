<?php

namespace App\Livewire\Projects;

use App\Livewire\Concerns\RequiresPermission;
use App\Models\Project;
use App\Models\User;
use App\Models\Website;
use App\Services\GatewayService;
use Illuminate\Support\Str;
use Livewire\Component;

class WebsitesSection extends Component
{
    use RequiresPermission;

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
        'FMPay',
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

    public ?int $responsible_user_id = null;

    public array $gateways = [];

    /**
     * Social networks of the site, keyed by slug: ['instagram' => ['network' => 'Instagram', 'url' => '', 'status' => 'created']].
     *
     * @var array<string, array{network: string, url: string, status: string}>
     */
    public array $socials = [];

    public string $newSocialName = '';

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
        $this->requirePermission('manage_projects');

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
        $this->requirePermission('manage_projects');

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
            'responsible_user_id' => 'nullable|exists:users,id',
            'status' => 'required|in:Live,Test,No integration',
            'gateways' => 'nullable|array',
            'gateways.*' => 'string|in:'.implode(',', $available),
            'socials' => 'nullable|array',
            'socials.*.network' => 'required|string|max:50',
            'socials.*.url' => 'nullable|url|max:500',
            'socials.*.status' => 'required|in:'.implode(',', array_keys(Website::SOCIAL_STATUSES)),
            'visa_status' => 'required|in:In Progress,Working,Stopped',
            'mastercard_status' => 'required|in:In Progress,Working,Stopped',
        ];
    }

    /**
     * Networks offered in the form: the common ones plus any custom network already added to this site.
     *
     * @return array<string, string> name => icon
     */
    public function getAvailableSocialsProperty(): array
    {
        $networks = Website::SOCIAL_NETWORKS;

        foreach ($this->socials as $profile) {
            $networks[$profile['network']] ??= Website::socialIcon($profile['network']);
        }

        return $networks;
    }

    /**
     * Select / unselect a social network of the site.
     */
    public function toggleSocial(string $network): void
    {
        $this->requirePermission('manage_projects');

        $network = trim($network);
        $key = Str::slug($network);

        if ($network === '' || $key === '') {
            return;
        }

        if (isset($this->socials[$key])) {
            unset($this->socials[$key]);

            return;
        }

        $this->socials[$key] = ['network' => $network, 'url' => '', 'status' => 'not_created'];
    }

    /**
     * Add a network that is not in the list (same name as a listed one in other letter case selects the listed one).
     */
    public function addCustomSocial(): void
    {
        $this->requirePermission('manage_projects');

        $this->validate(['newSocialName' => 'required|string|max:50'], [], ['newSocialName' => 'social network']);

        $name = trim($this->newSocialName);
        $listed = collect(array_keys(Website::SOCIAL_NETWORKS))->first(fn (string $network) => strcasecmp($network, $name) === 0);
        $name = $listed ?? $name;
        $key = Str::slug($name);

        if ($key !== '' && ! isset($this->socials[$key])) {
            $this->socials[$key] = ['network' => $name, 'url' => '', 'status' => 'not_created'];
        }

        $this->newSocialName = '';
    }

    /**
     * The form state as stored on the website: a list of network, url and status.
     *
     * @return list<array{network: string, url: ?string, status: string}>
     */
    private function socialsPayload(): array
    {
        return collect($this->socials)->map(fn (array $profile) => [
            'network' => trim($profile['network']),
            'url' => filled($profile['url'] ?? null) ? trim($profile['url']) : null,
            'status' => $profile['status'],
        ])->values()->all();
    }

    public function mount(Project $project): void
    {
        $this->project = $project;
    }

    public function openCreateForm(): void
    {
        $this->requirePermission('manage_projects');

        $this->resetForm();
        $this->showForm = true;
    }

    public function edit(int $id): void
    {
        $this->requirePermission('manage_projects');

        $this->resetForm();
        $website = Website::findOrFail($id);
        $this->editingId = $website->id;
        $this->name = $website->name;
        $this->url = $website->url;
        $this->status = $website->status ?? 'No integration';
        $this->responsible_user_id = $website->responsible_user_id;
        $this->gateways = $website->gateways ?? [];
        $this->socials = collect($website->socialProfiles())
            ->mapWithKeys(fn (array $profile) => [Str::slug($profile['network']) => [
                'network' => $profile['network'],
                'url' => (string) $profile['url'],
                'status' => $profile['status'],
            ]])
            ->all();
        $this->visa_status = $website->visa_status ?? 'Stopped';
        $this->mastercard_status = $website->mastercard_status ?? 'Stopped';
        $this->showForm = true;
    }

    public function save(): void
    {
        $this->requirePermission('manage_projects');

        $this->validate();

        if ($this->editingId) {
            $website = Website::findOrFail($this->editingId);
            $website->update([
                'name' => $this->name,
                'url' => $this->url,
                'responsible_user_id' => $this->responsible_user_id ?: null,
                'status' => $this->status,
                'gateways' => $this->gateways,
                'socials' => $this->socialsPayload(),
                'visa_status' => $this->visa_status,
                'mastercard_status' => $this->mastercard_status,
            ]);
            session()->flash('message', 'Website successfully updated.');
        } else {
            $this->project->websites()->create([
                'name' => $this->name,
                'url' => $this->url,
                'responsible_user_id' => $this->responsible_user_id ?: null,
                'status' => $this->status,
                'gateways' => $this->gateways,
                'socials' => $this->socialsPayload(),
                'visa_status' => $this->visa_status,
                'mastercard_status' => $this->mastercard_status,
            ]);
            session()->flash('message', 'New website successfully added.');
        }

        $this->resetForm();
    }

    public function delete(int $id): void
    {
        $this->requirePermission('manage_projects');

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
        $this->requirePermission('manage_projects');

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
        $this->requirePermission('manage_projects');

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
        $this->responsible_user_id = null;
        $this->gateways = [];
        $this->socials = [];
        $this->newSocialName = '';
        $this->visa_status = 'Stopped';
        $this->mastercard_status = 'Stopped';
        $this->newGatewayName = '';
        $this->gatewaySearch = '';
        $this->resetValidation();
    }

    public function render()
    {
        $websites = $this->project->websites()->with('responsible:id,name')->orderBy('id', 'desc')->get();

        $allProjects = Project::where('id', '!=', $this->project->id)
            ->notArchived()
            ->orderBy('name')
            ->get(['id', 'name']);

        return view('livewire.projects.websites-section', [
            'websites' => $websites,
            'allProjects' => $allProjects,
            'users' => User::orderBy('name')->get(['id', 'name']),
        ]);
    }
}
