<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Attributes\Fillable;

#[Fillable(['task_id', 'user_id', 'started_at', 'stopped_at', 'duration_seconds'])]
class TaskTimeLog extends Model
{
    protected function casts(): array
    {
        return [
            'started_at' => 'datetime',
            'stopped_at' => 'datetime',
        ];
    }

    /**
     * Get the task associated with the log.
     */
    public function task(): BelongsTo
    {
        return $this->belongsTo(Task::class);
    }

    /**
     * Get the user associated with the log.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get human-readable duration of this log (e.g. 5s, 10m, 1h 20m).
     */
    public function getHumanDurationAttribute(): string
    {
        $seconds = $this->duration_seconds;
        if ($seconds === null) {
            // If active and running
            if ($this->started_at && !$this->stopped_at) {
                $seconds = $this->started_at->diffInSeconds(now(), true);
            } else {
                return '0s';
            }
        }
        
        if ($seconds <= 0) {
            return '0s';
        }
        
        $parts = [];
        
        $days = floor($seconds / 86400);
        if ($days > 0) {
            $parts[] = "{$days}d";
            $seconds %= 86400;
        }
        
        $hours = floor($seconds / 3600);
        if ($hours > 0) {
            $parts[] = "{$hours}h";
            $seconds %= 3600;
        }
        
        $minutes = floor($seconds / 60);
        if ($minutes > 0) {
            $parts[] = "{$minutes}m";
            $seconds %= 60;
        }
        
        if ($seconds > 0 || empty($parts)) {
            $parts[] = "{$seconds}s";
        }
        
        return implode(' ', $parts);
    }
}
