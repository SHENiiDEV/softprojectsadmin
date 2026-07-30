<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Crypt;
use Throwable;

#[Fillable(['project_id', 'website_id', 'name', 'type', 'provider_url', 'login', 'password', 'fields', 'comments'])]
class Credential extends Model
{
    use HasFactory;

    /**
     * Get the project that owns the credential.
     */
    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    /**
     * Get the website that owns the credential.
     */
    public function website(): BelongsTo
    {
        return $this->belongsTo(Website::class);
    }

    /**
     * Safely decrypt password attribute with unencrypted fallback.
     */
    public function getPasswordAttribute(?string $value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        try {
            return Crypt::decryptString($value);
        } catch (Throwable $e) {
            return $value;
        }
    }

    /**
     * Automatically encrypt password when setting.
     */
    public function setPasswordAttribute(?string $value): void
    {
        if ($value === null || $value === '') {
            $this->attributes['password'] = null;

            return;
        }

        try {
            Crypt::decryptString($value);
            $this->attributes['password'] = $value;
        } catch (Throwable $e) {
            $this->attributes['password'] = Crypt::encryptString($value);
        }
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'fields' => 'array',
        ];
    }
}
