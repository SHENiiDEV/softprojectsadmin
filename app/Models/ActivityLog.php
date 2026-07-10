<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ActivityLog extends Model
{
    protected $fillable = [
        'user_id',
        'client_id',
        'task_id',
        'project_id',
        'action',
        'description',
    ];

    /**
     * Get the user that triggered the activity.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the client associated with the activity (e.g. via portal).
     */
    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    /**
     * Get the task associated with the activity.
     */
    public function task(): BelongsTo
    {
        return $this->belongsTo(Task::class);
    }

    /**
     * Get the project (company) associated with the activity.
     */
    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }
}
