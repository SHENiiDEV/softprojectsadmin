<?php

namespace App\Livewire\Projects;

use App\Models\Boarding;
use App\Models\Project;
use App\Services\NotificationService;
use Livewire\Component;

class BoardingSection extends Component
{
    public Project $project;

    public Boarding $boarding;

    // Legacy fields
    public ?string $kyb_completed_at = null;

    public ?string $boarding_completed_at = null;

    public string $cfs_verification = 'need_to_complete';

    public string $cardaq_sumsub = 'pending';

    public string $bank_verification = 'not_started';

    public string $companies_house_verification = 'not_started';

    // Multi-Provider data (Keyed by provider name: 'Cardaq', 'Rapyd', etc.)
    public array $providers = [];

    public array $activeGateways = [];

    public function mount(Project $project): void
    {
        $this->project = $project;

        $this->boarding = $project->boarding ?? $project->boarding()->create([
            'provider_name' => 'Cardaq',
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

        $this->loadProviders();
    }

    public function loadProviders(): void
    {
        // Automatically fetch unique gateways from Company Websites
        $gateways = $this->project->websites()
            ->get()
            ->pluck('gateways')
            ->filter()
            ->flatten()
            ->map(fn ($g) => is_string($g) ? trim($g) : '')
            ->filter()
            ->unique()
            ->values()
            ->toArray();

        if (empty($gateways)) {
            $gateways = [$this->boarding->provider_name ?: 'Cardaq'];
        }

        $this->activeGateways = $gateways;

        $savedData = $this->boarding->providers_data;
        if (! is_array($savedData)) {
            $savedData = [];
        }

        $providersMap = [];

        foreach ($this->activeGateways as $gName) {
            $existing = $savedData[$gName] ?? null;

            // Fallback for legacy data format (numerically indexed array or single record)
            if (! $existing && is_array($savedData)) {
                foreach ($savedData as $item) {
                    if (is_array($item) && ($item['name'] ?? '') === $gName) {
                        $existing = $item;
                        break;
                    }
                }
            }

            if (! $existing && $gName === ($this->boarding->provider_name ?: 'Cardaq')) {
                $existing = [
                    'boarding_completed_at' => $this->boarding->provider_boarding_completed_at?->format('Y-m-d'),
                    'kyb_sent_at' => null,
                    'kyb_status' => 'sent',
                    'boarding_status' => $this->boarding->provider_verification_status ?: 'pending',
                    'verification_status' => $this->boarding->provider_verification_status ?: 'pending',
                ];
            }

            $providersMap[$gName] = [
                'name' => $gName,
                'boarding_completed_at' => $existing['boarding_completed_at'] ?? null,
                'kyb_sent_at' => $existing['kyb_sent_at'] ?? null,
                'kyb_status' => $existing['kyb_status'] ?? 'sent',
                'boarding_status' => $existing['boarding_status'] ?? 'pending',
                'verification_status' => $existing['verification_status'] ?? 'pending',
            ];
        }

        $this->providers = $providersMap;
    }

    public function save(): void
    {
        $oldProviders = $this->boarding->providers_data ?: [];

        // Determine primary provider from active gateways
        $firstGateway = $this->activeGateways[0] ?? 'Cardaq';
        $firstProviderData = $this->providers[$firstGateway] ?? null;

        $this->boarding->update([
            'kyb_completed_at' => $this->kyb_completed_at ?: null,
            'boarding_completed_at' => $this->boarding_completed_at ?: null,
            'cfs_verification' => $this->cfs_verification,
            'cardaq_sumsub' => $this->cardaq_sumsub,
            'bank_verification' => $this->bank_verification,
            'companies_house_verification' => $this->companies_house_verification,
            'provider_name' => $firstGateway,
            'provider_boarding_completed_at' => ! empty($firstProviderData['boarding_completed_at']) ? $firstProviderData['boarding_completed_at'] : null,
            'provider_verification_status' => $firstProviderData['verification_status'] ?? 'pending',
            'providers_data' => $this->providers,
        ]);

        // Check if any provider boarding status transitioned to boarding_completed / verified
        $manager = $this->project->manager;
        if ($manager) {
            foreach ($this->providers as $pName => $prov) {
                $newStatus = $prov['boarding_status'] ?? $prov['verification_status'] ?? '';

                $oldStatus = '';
                if (isset($oldProviders[$pName])) {
                    $oldStatus = $oldProviders[$pName]['boarding_status'] ?? $oldProviders[$pName]['verification_status'] ?? '';
                } elseif (is_array($oldProviders)) {
                    foreach ($oldProviders as $oldP) {
                        if (is_array($oldP) && ($oldP['name'] ?? '') === $pName) {
                            $oldStatus = $oldP['boarding_status'] ?? $oldP['verification_status'] ?? '';
                            break;
                        }
                    }
                }

                $isCompleted = in_array($newStatus, ['boarding_completed', 'completed', 'verified']);
                $wasNotCompleted = ! in_array($oldStatus, ['boarding_completed', 'completed', 'verified']);

                if ($isCompleted && $wasNotCompleted) {
                    NotificationService::sendProviderBoardingCompletedNotification(
                        $this->project,
                        $pName,
                        $manager,
                        auth()->user()
                    );
                }
            }
        }

        session()->flash('message', 'Compliance and provider parameters successfully updated.');
    }

    public function render()
    {
        return view('livewire.projects.boarding-section');
    }
}
