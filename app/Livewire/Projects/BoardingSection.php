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

    // Multi-Provider data
    public array $providers = [];

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

        // Load multi-providers array or initialize default if empty
        $existing = $this->boarding->providers_data;
        if (! is_array($existing) || empty($existing)) {
            $initialProviderName = $this->boarding->provider_name ?: 'Cardaq';
            $this->providers = [
                [
                    'name' => $initialProviderName,
                    'boarding_completed_at' => $this->boarding->provider_boarding_completed_at?->format('Y-m-d'),
                    'kyb_sent_at' => null,
                    'kyb_status' => 'sent',
                    'boarding_status' => $this->boarding->provider_verification_status ?: 'pending',
                    'verification_status' => $this->boarding->provider_verification_status ?: 'pending',
                ],
            ];
        } else {
            $this->providers = $existing;
        }
    }

    public function addProvider(): void
    {
        $presetNames = ['Cardaq', 'Madfin', 'SumSub', 'Stripe', 'Revolut', 'Wirecard'];
        $usedNames = array_column($this->providers, 'name');
        $available = array_values(array_diff($presetNames, $usedNames));
        $nextName = $available[0] ?? 'Provider '.(count($this->providers) + 1);

        $this->providers[] = [
            'name' => $nextName,
            'boarding_completed_at' => null,
            'kyb_sent_at' => null,
            'kyb_status' => 'need_to_send',
            'boarding_status' => 'pending',
            'verification_status' => 'pending',
        ];
    }

    public function removeProvider(int $index): void
    {
        if (count($this->providers) > 1) {
            unset($this->providers[$index]);
            $this->providers = array_values($this->providers);
        }
    }

    public function save(): void
    {
        $oldProviders = $this->boarding->providers_data ?: [];

        // Save legacy fields + multi-providers json
        $firstProvider = $this->providers[0] ?? null;

        $this->boarding->update([
            'kyb_completed_at' => $this->kyb_completed_at ?: null,
            'boarding_completed_at' => $this->boarding_completed_at ?: null,
            'cfs_verification' => $this->cfs_verification,
            'cardaq_sumsub' => $this->cardaq_sumsub,
            'bank_verification' => $this->bank_verification,
            'companies_house_verification' => $this->companies_house_verification,
            'provider_name' => $firstProvider['name'] ?? 'Cardaq',
            'provider_boarding_completed_at' => ! empty($firstProvider['boarding_completed_at']) ? $firstProvider['boarding_completed_at'] : null,
            'provider_verification_status' => $firstProvider['verification_status'] ?? 'pending',
            'providers_data' => $this->providers,
        ]);

        // Check if any provider boarding status transitioned to boarding_completed / verified
        $manager = $this->project->manager;
        if ($manager) {
            foreach ($this->providers as $prov) {
                $pName = $prov['name'] ?? 'Provider';
                $newStatus = $prov['boarding_status'] ?? $prov['verification_status'] ?? '';

                // Find previous status for this provider
                $oldStatus = '';
                foreach ($oldProviders as $oldP) {
                    if (($oldP['name'] ?? '') === $pName) {
                        $oldStatus = $oldP['boarding_status'] ?? $oldP['verification_status'] ?? '';
                        break;
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
