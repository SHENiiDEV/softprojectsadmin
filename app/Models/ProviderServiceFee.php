<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'provider_id',
    'fee_code',
    'title',
    'fee_type',
    'billing_period',
    'amount',
    'amount_max',
    'percentage',
    'currency',
    'comment',
])]
class ProviderServiceFee extends Model
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
            'amount' => 'float',
            'amount_max' => 'float',
            'percentage' => 'float',
        ];
    }

    /**
     * Get the provider that owns the service fee.
     */
    public function provider(): BelongsTo
    {
        return $this->belongsTo(Provider::class);
    }

    /**
     * Get human-readable formatted fee value.
     * Examples:
     * - "€1,250.00"
     * - "€800.00 – €3,000.00"
     * - "2.00%"
     * - "€200.00 / month"
     */
    public function getFormattedFeeAttribute(): string
    {
        $symbol = match ($this->currency) {
            'USD' => '$',
            'GBP' => '£',
            default => '€',
        };

        if ($this->fee_type === 'percentage' && $this->percentage !== null) {
            $pct = round($this->percentage * 100, 2);

            return "{$pct}%";
        }

        if ($this->fee_type === 'range' && $this->amount_max !== null) {
            $minStr = number_format($this->amount, 0);
            $maxStr = number_format($this->amount_max, 0);

            return "{$symbol}{$minStr} – {$symbol}{$maxStr}";
        }

        $amountStr = number_format($this->amount, 2);

        return "{$symbol}{$amountStr}";
    }
}
