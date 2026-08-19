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

    // Multi-Provider data keyed by composite key: "{websiteId}_{gatewayName}"
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

    /**
     * Build providers list keyed by "{websiteId}_{gateway}" so each
     * website + gateway combination gets its own compliance entry.
     */
    public function loadProviders(): void
    {
        $websites = $this->project->websites()->get();
        $savedData = is_array($this->boarding->providers_data) ? $this->boarding->providers_data : [];

        $providersMap = [];

        foreach ($websites as $website) {
            $gateways = is_array($website->gateways) ? array_filter(array_map('trim', $website->gateways)) : [];

            foreach ($gateways as $gName) {
                if (empty($gName)) {
                    continue;
                }

                $compositeKey = "{$website->id}_{$gName}";

                // Try composite key first, then legacy global gateway key
                $existing = $savedData[$compositeKey] ?? $savedData[$gName] ?? null;

                // Fallback: search numerically-indexed legacy entries
                if (! is_array($existing) && is_array($savedData)) {
                    foreach ($savedData as $item) {
                        if (is_array($item) && ($item['name'] ?? '') === $gName) {
                            $existing = $item;
                            break;
                        }
                    }
                }

                // Fallback for primary provider from boarding record
                if (! is_array($existing) && $gName === ($this->boarding->provider_name ?: 'Cardaq')) {
                    $existing = [
                        'boarding_completed_at' => $this->boarding->provider_boarding_completed_at?->format('Y-m-d'),
                        'kyb_sent_at' => null,
                        'kyb_status' => 'sent',
                        'boarding_status' => $this->boarding->provider_verification_status ?: 'pending',
                        'verification_status' => $this->boarding->provider_verification_status ?: 'pending',
                    ];
                }

                if (! is_array($existing)) {
                    $existing = [];
                }

                $providersMap[$compositeKey] = [
                    'composite_key' => $compositeKey,
                    'website_id' => $website->id,
                    'website_name' => $website->name ?: $website->url,
                    'website_url' => $website->url,
                    'name' => $gName,
                    'boarding_completed_at' => $existing['boarding_completed_at'] ?? null,
                    'kyb_sent_at' => $existing['kyb_sent_at'] ?? null,
                    'kyb_status' => $existing['kyb_status'] ?? 'sent',
                    'boarding_status' => $existing['boarding_status'] ?? 'pending',
                    'verification_status' => $existing['verification_status'] ?? 'pending',
                ];
            }
        }

        // Fallback if no websites have gateways
        if (empty($providersMap)) {
            $fallback = $this->boarding->provider_name ?: 'Cardaq';
            $key = "0_{$fallback}";
            $existing = $savedData[$fallback] ?? $savedData[$key] ?? [];

            $providersMap[$key] = [
                'composite_key' => $key,
                'website_id' => 0,
                'website_name' => 'Company',
                'website_url' => '',
                'name' => $fallback,
                'boarding_completed_at' => $existing['boarding_completed_at'] ?? null,
                'kyb_sent_at' => $existing['kyb_sent_at'] ?? null,
                'kyb_status' => $existing['kyb_status'] ?? 'sent',
                'boarding_status' => $existing['boarding_status'] ?? 'pending',
                'verification_status' => $existing['verification_status'] ?? 'pending',
            ];
        }

        $this->providers = $providersMap;
        $this->activeGateways = collect($providersMap)->pluck('name')->unique()->values()->toArray();
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
