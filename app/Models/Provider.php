<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

#[Fillable(['name', 'code', 'type', 'base_currency', 'website', 'contact_person', 'contact_email', 'account_manager', 'notes', 'is_active'])]
class Provider extends Model
{
    use HasFactory;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }

    /**
     * Get all processing rates for the provider.
     */
    public function processingRates(): HasMany
    {
        return $this->hasMany(ProviderProcessingRate::class);
    }

    /**
     * Get active processing rates.
     */
    public function activeRates(): HasMany
    {
        return $this->hasMany(ProviderProcessingRate::class)->where('is_active', true);
    }

    /**
     * Get all service and fixed fees for the provider.
     */
    public function serviceFees(): HasMany
    {
        return $this->hasMany(ProviderServiceFee::class);
    }

    /**
     * Get rolling reserve configuration for the provider.
     */
    public function reserve(): HasOne
    {
        return $this->hasOne(ProviderReserve::class);
    }

    /**
     * Scope to only include active providers.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Helper to find best matching rate for transaction simulation.
     */
    public function findMatchingRate(
        string $paymentMethod = 'card',
        string $flowType = 'payin',
        string $cardRegion = 'all',
        string $audience = 'all',
        float $monthlyVolume = 0.0
    ): ?ProviderProcessingRate {
        $rates = $this->processingRates()
            ->where('is_active', true)
            ->where('payment_method', $paymentMethod)
            ->where('flow_type', $flowType)
            ->get();

        if ($rates->isEmpty()) {
            return null;
        }

        // Filter by volume tier
        $tieredRates = $rates->filter(function (ProviderProcessingRate $rate) use ($monthlyVolume) {
            $minMatch = $monthlyVolume >= (float) $rate->tier_min_volume;
            $maxMatch = $rate->tier_max_volume === null || $monthlyVolume <= (float) $rate->tier_max_volume;

            return $minMatch && $maxMatch;
        });

        if ($tieredRates->isEmpty()) {
            $tieredRates = $rates;
        }

        // Filter by region if card
        if ($paymentMethod === 'card') {
            $matched = $tieredRates->first(function ($r) use ($cardRegion) {
                return $r->card_region === $cardRegion;
            }) ?: $tieredRates->first(function ($r) {
                return $r->card_region === 'all' || $r->card_region === 'international';
            });

            return $matched ?: $tieredRates->first();
        }

        // Filter by audience (B2B, B2C, etc.)
        if ($audience !== 'all') {
            $matched = $tieredRates->first(function ($r) use ($audience) {
                return $r->target_audience === $audience;
            });

            if ($matched) {
                return $matched;
            }
        }

        return $tieredRates->first();
    }

    /**
     * Calculate fee details for a given amount.
     *
     * @return array{
     *     rate: ?ProviderProcessingRate,
     *     fee: float,
     *     effective_percent: float,
     *     reserve_amount: float,
     *     reserve_percent: float,
     *     settlement_amount: float,
     *     is_supported: bool
     * }
     */
    public function simulateTransactionCost(
        float $amount,
        string $paymentMethod = 'card',
        string $flowType = 'payin',
        string $cardRegion = 'all',
        string $audience = 'all',
        float $monthlyVolume = 0.0
    ): array {
        $rate = $this->findMatchingRate($paymentMethod, $flowType, $cardRegion, $audience, $monthlyVolume);

        if (! $rate) {
            return [
                'rate' => null,
                'fee' => 0.0,
                'effective_percent' => 0.0,
                'reserve_amount' => 0.0,
                'reserve_percent' => 0.0,
                'settlement_amount' => 0.0,
                'is_supported' => false,
            ];
        }

        // Fee = max(Amount * percent_rate + fixed_fee, min_fee)
        $percentPortion = $amount * (float) $rate->percent_rate;
        $calculatedFee = $percentPortion + (float) $rate->fixed_fee;

        if ($rate->min_fee !== null && $calculatedFee < (float) $rate->min_fee) {
            $calculatedFee = (float) $rate->min_fee;
        }

        if ($rate->max_fee !== null && $calculatedFee > (float) $rate->max_fee) {
            $calculatedFee = (float) $rate->max_fee;
        }

        $calculatedFee = round($calculatedFee, 2);
        $effectivePercent = $amount > 0 ? round(($calculatedFee / $amount) * 100, 2) : 0.0;

        // Rolling Reserve
        $reservePercent = 0.0;
        $reserveAmount = 0.0;

        if ($paymentMethod === 'card' && $flowType === 'payin' && $this->reserve) {
            $reservePercent = (float) $this->reserve->rate_percent;
            $reserveAmount = round($amount * ($reservePercent / 100), 2);
        }

        $settlementAmount = round(max(0, $amount - $calculatedFee - $reserveAmount), 2);

        return [
            'rate' => $rate,
            'fee' => $calculatedFee,
            'effective_percent' => $effectivePercent,
            'reserve_amount' => $reserveAmount,
            'reserve_percent' => $reservePercent,
            'settlement_amount' => $settlementAmount,
            'is_supported' => true,
        ];
    }
}
