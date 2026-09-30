<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'provider_id',
    'rate_percent',
    'hold_period_days',
    'floor_amount',
    'cap_amount',
    'currency',
    'conditions',
])]
class ProviderReserve extends Model
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
            'rate_percent' => 'float',
            'hold_period_days' => 'integer',
            'floor_amount' => 'float',
            'cap_amount' => 'float',
        ];
    }

    /**
     * Get the provider that owns the reserve configuration.
     */
    public function provider(): BelongsTo
    {
        return $this->belongsTo(Provider::class);
    }

    /**
     * Get human-readable summary of rolling reserve.
     * Example: "10% / 180 days (min €500, max €200,000)"
     */
    public function getFormattedSummaryAttribute(): string
    {
        $symbol = match ($this->currency) {
            'USD' => '$',
            'GBP' => '£',
            default => '€',
        };

        $pct = round($this->rate_percent, 1);
        $res = "{$pct}% / {$this->hold_period_days}d";

        $limits = [];
        if ($this->floor_amount > 0) {
            $limits[] = 'min '.$symbol.number_format($this->floor_amount, 0);
        }
        if ($this->cap_amount > 0) {
            $limits[] = 'max '.$symbol.number_format($this->cap_amount, 0);
        }

        if (! empty($limits)) {
            $res .= ' ('.implode(', ', $limits).')';
        }

        return $res;
    }
}
