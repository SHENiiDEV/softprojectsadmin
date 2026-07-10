<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['project_id', 'name', 'url', 'status'])]
class Website extends Model
{
    use HasFactory;

    /**
     * Get the project that owns the website.
     */
    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    /**
     * Get the credentials associated with the website.
     */
    public function credentials(): HasMany
    {
        return $this->hasMany(Credential::class);
    }
}
