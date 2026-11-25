<?php

namespace App\Livewire\Projects;

use App\Livewire\Concerns\RequiresPermission;
use App\Models\Boarding;
use App\Models\Project;
use App\Services\NotificationService;
use App\Services\OnboardingBoard;
use Illuminate\Validation\Rule;
use Livewire\Component;

class BoardingSection extends Component
{
    use RequiresPermission;

    public Project $project;

    public Boarding $boarding;

    /**
     * Per provider and website, keyed "{websiteId}_{gatewayName}": status, send date, completion date.
     *
     * @var array<string, array<string, mixed>>
     */
    public array $providers = [];

    /** @var list<string> */
    public array $activeGateways = [];

    protected function rules(): array
    {
        return [
            'providers.*.status' => ['required', Rule::in(array_keys(OnboardingBoard::FORM_STATUSES))],
            'providers.*.kyb_sent_at' => 'nullable|date',
            'providers.*.boarding_completed_at' => 'nullable|date',
        ];
    }

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

        $this->loadProviders();
    }

    /**
     * Build providers list keyed by "{websiteId}_{gateway}" so each
     * website + gateway combination gets its own entry.
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
                        'boarding_status' => $this->boarding->provider_verification_status ?: 'pending',
                        'verification_status' => $this->boarding->provider_verification_status ?: 'pending',
                    ];
                }

                $providersMap[$compositeKey] = $this->entry($existing, [
                    'composite_key' => $compositeKey,
                    'website_id' => $website->id,
                    'website_name' => $website->name ?: $website->url,
                    'website_url' => $website->url,
                    'name' => $gName,
                ]);
            }
        }

        // Fallback if no websites have gateways
        if (empty($providersMap)) {
            $fallback = $this->boarding->provider_name ?: 'Cardaq';
            $key = "0_{$fallback}";
            $existing = $savedData[$fallback] ?? $savedData[$key] ?? [];

            $providersMap[$key] = $this->entry($existing, [
                'composite_key' => $key,
                'website_id' => 0,
                'website_name' => 'Company',
                'website_url' => '',
                'name' => $fallback,
            ]);
        }

        $this->providers = $providersMap;
        $this->activeGateways = collect($providersMap)->pluck('name')->unique()->sort(SORT_NATURAL | SORT_FLAG_CASE)->values()->toArray();
    }

    /**
     * One provider record as the form edits it. Everything else already saved on the record is kept.
     *
     * @param  array<string, mixed>|mixed  $existing
     * @param  array<string, mixed>  $identity
     * @return array<string, mixed>
     */
    private function entry(mixed $existing, array $identity): array
    {
        $existing = is_array($existing) ? $existing : [];

        return array_merge($existing, $identity, [
            'status' => OnboardingBoard::formStatus($existing),
            'kyb_sent_at' => $existing['kyb_sent_at'] ?? null,
            'boarding_completed_at' => $existing['boarding_completed_at'] ?? null,
        ]);
    }

    /**
     * Dates fill themselves: In Progress sets the send date, Completed sets the completion date (only when empty).
     */
    public function updated(string $property, mixed $value): void
    {
        if (! preg_match('/^providers\.(.+)\.status$/', $property, $match) || ! isset($this->providers[$match[1]])) {
            return;
        }

        $key = $match[1];
        $today = now()->toDateString();

        if ($value === 'in_progress' && empty($this->providers[$key]['kyb_sent_at'])) {
            $this->providers[$key]['kyb_sent_at'] = $today;
        }

        if ($value === 'completed' && empty($this->providers[$key]['boarding_completed_at'])) {
            $this->providers[$key]['boarding_completed_at'] = $today;
        }
    }

    /**
     * "Took 12 days" / "12 days so far" for the provider record in the form (null when it cannot be told yet).
     *
     * @param  array<string, mixed>  $provider
     */
    public function durationOf(array $provider): ?string
    {
        return OnboardingBoard::durationLabel(OnboardingBoard::duration(
            $provider['kyb_sent_at'] ?? null,
            $provider['boarding_completed_at'] ?? null,
            (string) ($provider['status'] ?? 'pending'),
        ));
    }

    public function save(): void
    {
        $this->requirePermission('manage_projects');

        $this->validate();

        $oldProviders = is_array($this->boarding->providers_data) ? $this->boarding->providers_data : [];

        $saved = [];
        foreach ($this->providers as $key => $provider) {
            $status = $provider['status'];
            unset($provider['status']);

            // Both legacy status fields carry the one status, so every screen reads the same value
            $provider['boarding_status'] = $status;
            $provider['verification_status'] = $status;
            $provider['kyb_sent_at'] = $provider['kyb_sent_at'] ?: null;
            $provider['boarding_completed_at'] = $provider['boarding_completed_at'] ?: null;

            $saved[$key] = $provider;
        }

        // Primary provider from active gateways (kept for the summary fields of the boarding record)
        $firstGateway = $this->activeGateways[0] ?? 'Cardaq';
        $first = collect($saved)->first(fn (array $provider) => ($provider['name'] ?? null) === $firstGateway) ?? [];

        $this->boarding->update([
            'provider_name' => $firstGateway,
            'provider_boarding_completed_at' => $first['boarding_completed_at'] ?? null,
            'provider_verification_status' => $first['verification_status'] ?? 'pending',
            'providers_data' => $saved,
        ]);

        $this->notifyCompletedProviders($oldProviders, $saved);

        $this->loadProviders();

        session()->flash('message', 'Provider boarding successfully updated.');
    }

    /**
     * The company manager hears when a provider boarding becomes Completed.
     *
     * @param  array<string, mixed>  $old
     * @param  array<string, array<string, mixed>>  $new
     */
    private function notifyCompletedProviders(array $old, array $new): void
    {
        $manager = $this->project->manager;
        if (! $manager) {
            return;
        }

        $wasCompleted = [];
        foreach ($old as $item) {
            if (is_array($item) && OnboardingBoard::formStatus($item) === 'completed') {
                $wasCompleted[strtolower((string) ($item['name'] ?? ''))] = true;
            }
        }

        $notified = [];
        foreach ($new as $item) {
            $name = (string) ($item['name'] ?? '');
            $lower = strtolower($name);

            if ($item['boarding_status'] === 'completed' && ! isset($wasCompleted[$lower]) && ! isset($notified[$lower])) {
                NotificationService::sendProviderBoardingCompletedNotification($this->project, $name, $manager, auth()->user());
                $notified[$lower] = true;
            }
        }
    }

    public function render()
    {
        return view('livewire.projects.boarding-section');
    }
}
