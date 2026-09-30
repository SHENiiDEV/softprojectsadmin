<?php

namespace App\Livewire\Tariffs;

use App\Models\Provider;
use App\Models\ProviderProcessingRate;
use App\Models\ProviderReserve;
use App\Models\ProviderServiceFee;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Str;
use Livewire\Component;

class Index extends Component
{
    // Active navigation tab: cards | banking | fees | simulator | providers
    public string $activeTab = 'cards';

    // Global Filters
    public string $search = '';

    public string $filterType = 'all'; // all | acquiring | emi | hybrid

    public string $filterCurrency = 'all'; // all | EUR | USD | GBP

    public bool $onlyActive = true;

    // Simulator State
    public float $simAmount = 100.00;

    public string $simMethod = 'card'; // card | sepa | swift | internal

    public string $simFlow = 'payin'; // payin | payout

    public string $simRegion = 'eu'; // eu | non_eu | corporate | international

    public string $simAudience = 'all'; // all | b2b | b2c | c2b

    public float $simMonthlyVolume = 0.00;

    public string $simCurrency = 'EUR';

    // Provider Modal State
    public bool $showProviderModal = false;

    public ?int $editingProviderId = null;

    public array $providerForm = [
        'name' => '',
        'code' => '',
        'type' => 'hybrid',
        'base_currency' => 'EUR',
        'website' => '',
        'contact_person' => '',
        'contact_email' => '',
        'account_manager' => '',
        'notes' => '',
        'is_active' => true,
    ];

    // Rate Modal State
    public bool $showRateModal = false;

    public ?int $editingRateId = null;

    public ?int $rateProviderId = null;

    public array $rateForm = [
        'provider_id' => null,
        'payment_method' => 'card',
        'flow_type' => 'payin',
        'target_audience' => 'all',
        'card_region' => 'eu',
        'percent_rate' => 0.0,
        'fixed_fee' => 0.0,
        'min_fee' => null,
        'max_fee' => null,
        'currency' => 'EUR',
        'tier_min_volume' => 0.0,
        'tier_max_volume' => null,
        'is_active' => true,
        'notes' => '',
    ];

    // Fee Modal State
    public bool $showFeeModal = false;

    public ?int $editingFeeId = null;

    public ?int $feeProviderId = null;

    public array $feeForm = [
        'provider_id' => null,
        'fee_code' => 'onboarding',
        'title' => '',
        'fee_type' => 'fixed',
        'billing_period' => 'one_time',
        'amount' => 0.0,
        'amount_max' => null,
        'percentage' => null,
        'currency' => 'EUR',
        'comment' => '',
    ];

    // Reserve Modal State
    public bool $showReserveModal = false;

    public ?int $reserveProviderId = null;

    public array $reserveForm = [
        'provider_id' => null,
        'rate_percent' => 10.0,
        'hold_period_days' => 180,
        'floor_amount' => null,
        'cap_amount' => null,
        'currency' => 'EUR',
        'conditions' => '',
    ];

    // Notification message
    public ?string $successMessage = null;

    public function setTab(string $tab): void
    {
        $this->activeTab = $tab;
    }

    public function toggleProviderStatus(int $providerId): void
    {
        $provider = Provider::findOrFail($providerId);
        $provider->is_active = ! $provider->is_active;
        $provider->save();

        $this->dispatchNotify($provider->name.' is now '.($provider->is_active ? 'active' : 'inactive'));
    }

    // --- Provider CRUD ---
    public function openCreateProviderModal(): void
    {
        $this->resetValidation();
        $this->editingProviderId = null;
        $this->providerForm = [
            'name' => '',
            'code' => '',
            'type' => 'hybrid',
            'base_currency' => 'EUR',
            'website' => '',
            'contact_person' => '',
            'contact_email' => '',
            'account_manager' => '',
            'notes' => '',
            'is_active' => true,
        ];
        $this->showProviderModal = true;
    }

    public function openEditProviderModal(int $id): void
    {
        $this->resetValidation();
        $provider = Provider::findOrFail($id);
        $this->editingProviderId = $provider->id;
        $this->providerForm = [
            'name' => $provider->name,
            'code' => $provider->code,
            'type' => $provider->type,
            'base_currency' => $provider->base_currency,
            'website' => $provider->website ?? '',
            'contact_person' => $provider->contact_person ?? '',
            'contact_email' => $provider->contact_email ?? '',
            'account_manager' => $provider->account_manager ?? '',
            'notes' => $provider->notes ?? '',
            'is_active' => (bool) $provider->is_active,
        ];
        $this->showProviderModal = true;
    }

    public function saveProvider(): void
    {
        $rules = [
            'providerForm.name' => 'required|string|max:100',
            'providerForm.code' => 'required|string|max:50|unique:providers,code,'.($this->editingProviderId ?? 'NULL').',id',
            'providerForm.type' => 'required|in:acquiring,emi,hybrid',
            'providerForm.base_currency' => 'required|string|size:3',
            'providerForm.website' => 'nullable|url|max:255',
            'providerForm.contact_email' => 'nullable|email|max:150',
        ];

        $this->validate($rules);

        $data = $this->providerForm;
        if (empty($data['code'])) {
            $data['code'] = Str::slug($data['name']);
        }

        if ($this->editingProviderId) {
            $provider = Provider::findOrFail($this->editingProviderId);
            $provider->update($data);
            $this->dispatchNotify('Provider updated successfully.');
        } else {
            Provider::create($data);
            $this->dispatchNotify('Provider created successfully.');
        }

        $this->showProviderModal = false;
    }

    public function deleteProvider(int $id): void
    {
        $provider = Provider::findOrFail($id);
        $name = $provider->name;
        $provider->delete();

        $this->dispatchNotify("Provider '{$name}' deleted.");
    }

    // --- Rate CRUD ---
    public function openCreateRateModal(?int $providerId = null): void
    {
        $this->resetValidation();
        $this->editingRateId = null;
        $this->rateProviderId = $providerId ?: (Provider::first()?->id);
        $this->rateForm = [
            'provider_id' => $this->rateProviderId,
            'payment_method' => 'card',
            'flow_type' => 'payin',
            'target_audience' => 'all',
            'card_region' => 'eu',
            'percent_rate' => 3.5, // Input as % (e.g. 3.5 = 3.5%)
            'fixed_fee' => 0.0,
            'min_fee' => null,
            'max_fee' => null,
            'currency' => 'EUR',
            'tier_min_volume' => 0.0,
            'tier_max_volume' => null,
            'is_active' => true,
            'notes' => '',
        ];
        $this->showRateModal = true;
    }

    public function openEditRateModal(int $rateId): void
    {
        $this->resetValidation();
        $rate = ProviderProcessingRate::findOrFail($rateId);
        $this->editingRateId = $rate->id;
        $this->rateProviderId = $rate->provider_id;
        $this->rateForm = [
            'provider_id' => $rate->provider_id,
            'payment_method' => $rate->payment_method,
            'flow_type' => $rate->flow_type,
            'target_audience' => $rate->target_audience,
            'card_region' => $rate->card_region,
            'percent_rate' => round($rate->percent_rate * 100, 4),
            'fixed_fee' => $rate->fixed_fee,
            'min_fee' => $rate->min_fee,
            'max_fee' => $rate->max_fee,
            'currency' => $rate->currency,
            'tier_min_volume' => $rate->tier_min_volume,
            'tier_max_volume' => $rate->tier_max_volume,
            'is_active' => (bool) $rate->is_active,
            'notes' => $rate->notes ?? '',
        ];
        $this->showRateModal = true;
    }

    public function saveRate(): void
    {
        $this->validate([
            'rateForm.provider_id' => 'required|exists:providers,id',
            'rateForm.payment_method' => 'required|in:card,sepa,swift,crypto,internal',
            'rateForm.flow_type' => 'required|in:payin,payout,refund',
            'rateForm.percent_rate' => 'required|numeric|min:0',
            'rateForm.fixed_fee' => 'required|numeric|min:0',
            'rateForm.min_fee' => 'nullable|numeric|min:0',
            'rateForm.max_fee' => 'nullable|numeric|min:0',
        ]);

        $data = $this->rateForm;
        $data['percent_rate'] = $data['percent_rate'] / 100; // convert 4.2% -> 0.0420

        if ($this->editingRateId) {
            $rate = ProviderProcessingRate::findOrFail($this->editingRateId);
            $rate->update($data);
            $this->dispatchNotify('Rate updated successfully.');
        } else {
            ProviderProcessingRate::create($data);
            $this->dispatchNotify('Rate added successfully.');
        }

        $this->showRateModal = false;
    }

    public function deleteRate(int $rateId): void
    {
        $rate = ProviderProcessingRate::findOrFail($rateId);
        $rate->delete();
        $this->dispatchNotify('Rate deleted.');
    }

    // --- Service Fee CRUD ---
    public function openCreateFeeModal(?int $providerId = null): void
    {
        $this->resetValidation();
        $this->editingFeeId = null;
        $this->feeProviderId = $providerId ?: (Provider::first()?->id);
        $this->feeForm = [
            'provider_id' => $this->feeProviderId,
            'fee_code' => 'onboarding',
            'title' => 'Onboarding Fee',
            'fee_type' => 'fixed',
            'billing_period' => 'one_time',
            'amount' => 1000.0,
            'amount_max' => null,
            'percentage' => null,
            'currency' => 'EUR',
            'comment' => '',
        ];
        $this->showFeeModal = true;
    }

    public function openEditFeeModal(int $feeId): void
    {
        $this->resetValidation();
        $fee = ProviderServiceFee::findOrFail($feeId);
        $this->editingFeeId = $fee->id;
        $this->feeProviderId = $fee->provider_id;
        $this->feeForm = [
            'provider_id' => $fee->provider_id,
            'fee_code' => $fee->fee_code,
            'title' => $fee->title,
            'fee_type' => $fee->fee_type,
            'billing_period' => $fee->billing_period,
            'amount' => $fee->amount,
            'amount_max' => $fee->amount_max,
            'percentage' => $fee->percentage !== null ? round($fee->percentage * 100, 2) : null,
            'currency' => $fee->currency,
            'comment' => $fee->comment ?? '',
        ];
        $this->showFeeModal = true;
    }

    public function saveFee(): void
    {
        $this->validate([
            'feeForm.provider_id' => 'required|exists:providers,id',
            'feeForm.fee_code' => 'required|string|max:50',
            'feeForm.title' => 'required|string|max:150',
            'feeForm.fee_type' => 'required|in:fixed,percentage,range',
            'feeForm.billing_period' => 'required|in:one_time,monthly,yearly,per_event',
            'feeForm.amount' => 'required|numeric|min:0',
        ]);

        $data = $this->feeForm;
        if ($data['fee_type'] === 'percentage' && $data['percentage'] !== null) {
            $data['percentage'] = $data['percentage'] / 100;
        }

        if ($this->editingFeeId) {
            $fee = ProviderServiceFee::findOrFail($this->editingFeeId);
            $fee->update($data);
            $this->dispatchNotify('Service fee updated successfully.');
        } else {
            ProviderServiceFee::create($data);
            $this->dispatchNotify('Service fee added successfully.');
        }

        $this->showFeeModal = false;
    }

    public function deleteFee(int $feeId): void
    {
        $fee = ProviderServiceFee::findOrFail($feeId);
        $fee->delete();
        $this->dispatchNotify('Service fee deleted.');
    }

    // --- Rolling Reserve Modal ---
    public function openEditReserveModal(int $providerId): void
    {
        $this->resetValidation();
        $this->reserveProviderId = $providerId;
        $reserve = ProviderReserve::where('provider_id', $providerId)->first();

        $this->reserveForm = [
            'provider_id' => $providerId,
            'rate_percent' => $reserve ? (float) $reserve->rate_percent : 10.0,
            'hold_period_days' => $reserve ? (int) $reserve->hold_period_days : 180,
            'floor_amount' => $reserve?->floor_amount,
            'cap_amount' => $reserve?->cap_amount,
            'currency' => $reserve?->currency ?? 'EUR',
            'conditions' => $reserve?->conditions ?? '',
        ];
        $this->showReserveModal = true;
    }

    public function saveReserve(): void
    {
        $this->validate([
            'reserveForm.provider_id' => 'required|exists:providers,id',
            'reserveForm.rate_percent' => 'required|numeric|min:0|max:100',
            'reserveForm.hold_period_days' => 'required|integer|min:0',
        ]);

        ProviderReserve::updateOrCreate(
            ['provider_id' => $this->reserveForm['provider_id']],
            $this->reserveForm
        );

        $this->dispatchNotify('Rolling reserve policy updated.');
        $this->showReserveModal = false;
    }

    protected function dispatchNotify(string $message): void
    {
        $this->successMessage = $message;
        $this->dispatch('tariff-updated', message: $message);
    }

    /**
     * Compute simulation rankings across all active providers.
     */
    public function getSimulationResultsProperty(): array
    {
        $amount = (float) $this->simAmount;
        if ($amount <= 0) {
            return [];
        }

        $providers = Provider::query()
            ->where('is_active', true)
            ->with(['processingRates', 'serviceFees', 'reserve'])
            ->get();

        $results = [];

        foreach ($providers as $provider) {
            $sim = $provider->simulateTransactionCost(
                amount: $amount,
                paymentMethod: $this->simMethod,
                flowType: $this->simFlow,
                cardRegion: $this->simRegion,
                audience: $this->simAudience,
                monthlyVolume: (float) $this->simMonthlyVolume
            );

            if ($sim['is_supported']) {
                $results[] = [
                    'provider' => $provider,
                    'rate' => $sim['rate'],
                    'fee' => $sim['fee'],
                    'effective_percent' => $sim['effective_percent'],
                    'reserve_amount' => $sim['reserve_amount'],
                    'reserve_percent' => $sim['reserve_percent'],
                    'settlement_amount' => $sim['settlement_amount'],
                    'currency' => $this->simCurrency,
                ];
            }
        }

        // Sort by lowest fee (Cheapest first)
        usort($results, fn ($a, $b) => $a['fee'] <=> $b['fee']);

        return $results;
    }

    public function render(): View
    {
        // Query providers with filters
        $query = Provider::query()
            ->with(['processingRates', 'serviceFees', 'reserve'])
            ->when($this->onlyActive, fn ($q) => $q->where('is_active', true))
            ->when($this->filterType !== 'all', fn ($q) => $q->where('type', $this->filterType))
            ->when($this->filterCurrency !== 'all', fn ($q) => $q->where('base_currency', $this->filterCurrency))
            ->when($this->search, function ($q) {
                $term = '%'.$this->search.'%';
                $q->where(function ($sub) use ($term) {
                    $sub->where('name', 'like', $term)
                        ->orWhere('code', 'like', $term)
                        ->orWhere('notes', 'like', $term)
                        ->orWhereHas('processingRates', fn ($r) => $r->where('notes', 'like', $term))
                        ->orWhereHas('serviceFees', fn ($f) => $f->where('title', 'like', $term));
                });
            })
            ->orderBy('name');

        $providers = $query->get();

        // Calculate quick summary metrics
        $totalProviders = Provider::count();
        $activeProviders = Provider::where('is_active', true)->count();
        $acquiringCount = Provider::whereIn('type', ['acquiring', 'hybrid'])->count();
        $emiCount = Provider::whereIn('type', ['emi', 'hybrid'])->count();

        // Best Card Rate in DB
        $lowestCardRate = ProviderProcessingRate::where('is_active', true)
            ->where('payment_method', 'card')
            ->where('flow_type', 'payin')
            ->where('card_region', 'eu')
            ->orderBy('percent_rate')
            ->first();

        // Best SEPA Rate in DB
        $lowestSepaRate = ProviderProcessingRate::where('is_active', true)
            ->where('payment_method', 'sepa')
            ->where('flow_type', 'payin')
            ->orderBy('percent_rate')
            ->first();

        return view('livewire.tariffs.index', [
            'providers' => $providers,
            'allProvidersList' => Provider::orderBy('name')->get(),
            'totalProviders' => $totalProviders,
            'activeProviders' => $activeProviders,
            'acquiringCount' => $acquiringCount,
            'emiCount' => $emiCount,
            'lowestCardRate' => $lowestCardRate,
            'lowestSepaRate' => $lowestSepaRate,
            'simulationResults' => $this->simulationResults,
        ])->layout('layouts.app');
    }
}
