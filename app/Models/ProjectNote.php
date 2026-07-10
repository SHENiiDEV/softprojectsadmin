<?php

namespace App\Models;

use App\Models\Concerns\HasFormattedContent;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Attributes\Fillable;

#[Fillable(['project_id', 'user_id', 'content', 'pinned'])]
class ProjectNote extends Model
{
    use HasFormattedContent;
    /**
     * Get the project that owns the note.
     */
    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    /**
     * Get the user who created the note.
     */
    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
