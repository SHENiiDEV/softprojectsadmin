<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\HasMany;
use App\Models\ProjectNote;
use App\Models\ActivityLog;
use App\Models\User;
use App\Models\Client;

#[Fillable(['name', 'status', 'integration_status', 'ubo', 'mcc', 'phones', 'emails', 'notes', 'archived_at', 'manager_id', 'client_id'])]
class Project extends Model
{
    /** @use HasFactory<\Database\Factories\ProjectFactory> */
    use HasFactory;

    protected static function booted(): void
    {
        static::updated(function (Project $project) {
            if (!config('features.company_changelog', true)) {
                return;
            }

            $changes = [];
            foreach ($project->getChanges() as $key => $value) {
                if (in_array($key, ['updated_at', 'created_at'])) {
                    continue;
                }
                $original = $project->getOriginal($key);

                if ($key === 'manager_id') {
                    $oldManager = $original ? (User::find($original)?->name ?? 'Unknown') : 'None';
                    $newManager = $value ? (User::find($value)?->name ?? 'Unknown') : 'None';
                    $changes[] = "Project manager changed from '{$oldManager}' to '{$newManager}'";
                } elseif ($key === 'client_id') {
                    $oldClient = $original ? (Client::find($original)?->name ?? 'Unknown') : 'None';
                    $newClient = $value ? (Client::find($value)?->name ?? 'Unknown') : 'None';
                    $changes[] = "Client changed from '{$oldClient}' to '{$newClient}'";
                } elseif ($key === 'status') {
                    $changes[] = "Company status updated from '{$original}' to '{$value}'";
                } elseif ($key === 'integration_status') {
                    $changes[] = "Integration status updated from '" . ($original ?? 'pending') . "' to '" . ($value ?? 'pending') . "'";
                } elseif ($key === 'ubo') {
                    $changes[] = "UBO updated from '" . ($original ?? 'not specified') . "' to '" . ($value ?? 'not specified') . "'";
                } elseif ($key === 'mcc') {
                    $changes[] = "MCC Code updated from '" . ($original ?? 'not specified') . "' to '" . ($value ?? 'not specified') . "'";
                } elseif ($key === 'archived_at') {
                    if ($value === null) {
                        $changes[] = "Company was restored from archive";
                    } else {
                        $changes[] = "Company was archived";
                    }
                } else {
                    if (is_array($value) || is_array($original)) {
                        $changes[] = "Company contact details (fields: " . $key . ") were updated";
                    } else {
                        $changes[] = "Field '{$key}' changed from '" . ($original ?? 'none') . "' to '" . ($value ?? 'none') . "'";
                    }
                }
            }

            foreach ($changes as $change) {
                ActivityLog::create([
                    'user_id' => auth()->id(),
                    'project_id' => $project->id,
                    'action' => 'project_updated',
                    'description' => $change,
                ]);
            }
        });
    }

    /**
     * Get the websites associated with the project.
     */
    public function websites(): HasMany
    {
        return $this->hasMany(Website::class);
    }

    /**
     * Get the director associated with the project.
     */
    public function director(): HasOne
    {
        return $this->hasOne(Director::class);
    }

    /**
     * Get the credentials associated with the project.
     */
    public function credentials(): HasMany
    {
        return $this->hasMany(Credential::class);
    }

    /**
     * Get the boarding information associated with the project.
     */
    public function boarding(): HasOne
    {
        return $this->hasOne(Boarding::class);
    }

    /**
     * Get the reports associated with the project.
     */
    public function report(): HasOne
    {
        return $this->hasOne(Report::class);
    }

    /**
     * Get the tasks associated with the project.
     */
    public function tasks(): HasMany
    {
        return $this->hasMany(Task::class);
    }

    /**
     * Get the internal notes for the project.
     */
    public function notes(): HasMany
    {
        return $this->hasMany(ProjectNote::class);
    }

    /**
     * Get the general company comments.
     */
    public function comments(): HasMany
    {
        return $this->hasMany(Comment::class)->whereNull('task_id');
    }

    /**
     * Get SMM posts associated with the company.
     */
    public function smmPosts(): HasMany
    {
        return $this->hasMany(SmmPost::class)->orderBy('created_at', 'desc');
    }

    /**
     * Get reviews associated with the company.
     */
    public function reviews(): HasMany
    {
        return $this->hasMany(Review::class)->orderBy('created_at', 'desc');
    }

    /**
     * Get the manager that owns the project.
     */
    public function manager(): BelongsTo
    {
        return $this->belongsTo(User::class, 'manager_id');
    }

    /**
     * Get the client that owns the project.
     */
    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'phones'      => 'array',
            'emails'      => 'array',
            'archived_at' => 'datetime',
        ];
    }

    public function scopeNotArchived($query)
    {
        return $query->whereNull('archived_at');
    }

    public function scopeArchived($query)
    {
        return $query->whereNotNull('archived_at');
    }
}
