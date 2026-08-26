<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Company extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'onboarding_completed', // boolean
        'website_id',
    ];

    /**
     * Relationship to website.
     */
    public function website(): BelongsTo
    {
        return $this->belongsTo(Website::class);
    }

    /**
     * Company credentials.
     */
    public function credentials(): HasMany
    {
        return $this->hasMany(Credential::class);
    }

    /**
     * Projects (companies' tasks).
     */
    public function projects(): HasMany
    {
        return $this->hasMany(Project::class);
    }

    /**
     * Simple health score calculation.
     * Returns an integer 0‑100 and a CSS class for badge colour.
     */
    public function healthScore(): array
    {
        $score = 0;
        // onboarding
        $score += $this->onboarding_completed ? 20 : 0;
        // website live (assume website has boolean `is_live`)
        $score += ($this->website && $this->website->is_live) ? 20 : 0;
        // credentials completeness (at least one)
        $score += $this->credentials()->exists() ? 20 : 0;
        // open critical tasks (assume tasks with high priority)
        $criticalOpen = $this->projects()
            ->whereHas('tasks', fn ($q) => $q->where('priority', 'critical')->whereNull('completed_at'))
            ->count();
        $score += $criticalOpen === 0 ? 20 : 0;
        // unpaid director fee (placeholder, assume no unpaid)
        $score += 20; // assume ok

        // clamp 0‑100
        $score = max(0, min(100, $score));

        // determine badge class
        if ($score >= 80) {
            $class = 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200';
        } elseif ($score >= 50) {
            $class = 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200';
        } else {
            $class = 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200';
        }

        return ['score' => $score, 'class' => $class];
    }
}
