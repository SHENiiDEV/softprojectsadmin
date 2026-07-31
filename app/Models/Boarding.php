<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'project_id',
    'provider_name',
    'kyb_completed_at',
    'boarding_completed_at',
    'provider_boarding_completed_at',
    'cfs_verification',
    'cardaq_sumsub',
    'provider_verification_status',
    'bank_verification',
    'companies_house_verification',
])]
class Boarding extends Model
{
    use HasFactory;

    protected static function booted(): void
    {
        static::updated(function (Boarding $boarding) {
            if (! config('features.company_changelog', true)) {
                return;
            }

            $changes = [];
            foreach ($boarding->getChanges() as $key => $value) {
                if (in_array($key, ['updated_at', 'created_at'])) {
                    continue;
                }
                $original = $boarding->getOriginal($key);

                $formattedKey = str_replace('_', ' ', $key);
                $changes[] = "Compliance checklist '".strtoupper($formattedKey)."' updated from '".($original ?? 'none')."' to '".($value ?? 'none')."'";
            }

            foreach ($changes as $change) {
                ActivityLog::create([
                    'user_id' => auth()->id(),
                    'project_id' => $boarding->project_id,
                    'action' => 'compliance_updated',
                    'description' => $change,
                ]);
            }
        });
    }

    /**
     * Get the project that owns the boarding.
     */
    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'kyb_completed_at' => 'date',
            'boarding_completed_at' => 'date',
            'provider_boarding_completed_at' => 'date',
        ];
    }
}
