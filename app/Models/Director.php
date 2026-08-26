<?php

namespace App\Models;

use Database\Factories\DirectorFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['project_id', 'name', 'fee_paid_status', 'managed_by'])]
class Director extends Model
{
    /** @use HasFactory<DirectorFactory> */
    use HasFactory;

    protected static function booted(): void
    {
        static::updated(function (Director $director) {
            if (! config('features.company_changelog', true)) {
                return;
            }

            $changes = [];
            foreach ($director->getChanges() as $key => $value) {
                if (in_array($key, ['updated_at', 'created_at'])) {
                    continue;
                }
                $original = $director->getOriginal($key);

                if ($key === 'managed_by') {
                    $oldUser = $original ? (User::find($original)?->name ?? 'Unknown') : 'None';
                    $newUser = $value ? (User::find($value)?->name ?? 'Unknown') : 'None';
                    $changes[] = "Director curator changed from '{$oldUser}' to '{$newUser}'";
                } elseif ($key === 'fee_paid_status') {
                    $changes[] = "Director fee paid status updated from '{$original}' to '{$value}'";
                } elseif ($key === 'name') {
                    $changes[] = "Director name changed from '{$original}' to '{$value}'";
                } else {
                    $changes[] = "Director field '{$key}' changed from '".($original ?? 'none')."' to '".($value ?? 'none')."'";
                }
            }

            foreach ($changes as $change) {
                ActivityLog::create([
                    'user_id' => auth()->id(),
                    'project_id' => $director->project_id,
                    'action' => 'director_updated',
                    'description' => $change,
                ]);
            }
        });
    }

    /**
     * Get the project that owns the director.
     */
    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    /**
     * Get the user (agent/manager) that manages the director.
     */
    public function manager(): BelongsTo
    {
        return $this->belongsTo(User::class, 'managed_by');
    }
}
