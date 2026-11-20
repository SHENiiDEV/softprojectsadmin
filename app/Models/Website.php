<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['project_id', 'responsible_user_id', 'name', 'url', 'status', 'gateways', 'visa_status', 'mastercard_status'])]
class Website extends Model
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
            'gateways' => 'array',
        ];
    }

    /**
     * Get the project that owns the website.
     */
    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    /**
     * Team member responsible for this website.
     */
    public function responsible(): BelongsTo
    {
        return $this->belongsTo(User::class, 'responsible_user_id');
    }

    /**
     * Get the credentials associated with the website.
     */
    public function credentials(): HasMany
    {
        return $this->hasMany(Credential::class);
    }
}
