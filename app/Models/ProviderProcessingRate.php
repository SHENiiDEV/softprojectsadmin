<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'provider_id',
    'payment_method',
    'flow_type',
    'target_audience',
    'card_region',
    'percent_rate',
    'fixed_fee',
    'min_fee',
    'max_fee',
    'currency',
    'tier_min_volume',
    'tier_max_volume',
    'is_active',
    'notes',
])]
class ProviderProcessingRate extends Model
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
            'percent_rate' => 'float',
            'fixed_fee' => 'float',
            'min_fee' => 'float',
            'max_fee' => 'float',
            'tier_min_volume' => 'float',
            'tier_max_volume' => 'float',
            'is_active' => 'boolean',
        ];
    }

    /**
     * Get the provider that owns the processing rate.
     */
    public function provider(): BelongsTo
    {
        return $this->belongsTo(Provider::class);
    }

    /**
     * Get human-readable formatted rate string.
     * Examples:
     * - "4.20% (min €0.60)"
     * - "0.20% + €5.00"
     * - "€50.00 fixed"
     */
    public function getFormattedRateAttribute(): string
    {
        $symbol = match ($this->currency) {
            'USD' => '$',
            'GBP' => '£',
            default => '€',
        };

        $percent = round($this->percent_rate * 100, 2);
        $parts = [];

        if ($percent > 0) {
            $parts[] = "{$percent}%";
        }

        if ($this->fixed_fee > 0) {
            $fixedStr = number_format($this->fixed_fee, 2);
            $parts[] = $percent > 0 ? "+ {$symbol}{$fixedStr}" : "{$symbol}{$fixedStr}";
        }

        if (empty($parts)) {
            $parts[] = '0.00%';
        }

        $main = implode(' ', $parts);

        if ($this->min_fee !== null && $this->min_fee > 0) {
            $minStr = number_format($this->min_fee, 2);
            $main .= " (min {$symbol}{$minStr})";
        }

        if ($this->max_fee !== null && $this->max_fee > 0) {
            $maxStr = number_format($this->max_fee, 2);
            $main .= " (max {$symbol}{$maxStr})";
        }

        return $main;
    }

    /**
     * Get human-readable tier label.
     */
    public function getTierLabelAttribute(): string
    {
        $symbol = match ($this->currency) {
            'USD' => '$',
            'GBP' => '£',
            default => '€',
        };

        if ($this->tier_min_volume == 0 && $this->tier_max_volume === null) {
            return 'All Volumes';
        }

        $minM = $this->tier_min_volume >= 1000000 ? round($this->tier_min_volume / 1000000, 1).'M' : number_format($this->tier_min_volume);
        if ($this->tier_max_volume === null) {
            return ">{$symbol}{$minM}";
        }

        $maxM = $this->tier_max_volume >= 1000000 ? round($this->tier_max_volume / 1000000, 1).'M' : number_format($this->tier_max_volume);

        return "{$symbol}{$minM} – {$symbol}{$maxM}";
    }
}
