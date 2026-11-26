<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['domain', 'note', 'created_by'])]
class ScamLink extends Model
{
    use HasFactory;

    /**
     * Entries without a dot (e.g. "highflybet") are keywords: they match any domain that contains them.
     */
    public function isKeyword(): bool
    {
        return ! str_contains($this->domain, '.');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
