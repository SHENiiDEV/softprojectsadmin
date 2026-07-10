<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use App\Services\TelegramService;
use App\Jobs\SendTelegramMessageJob;
use Carbon\Carbon;

#[Fillable(['project_id', 'creator_id', 'assigned_to', 'title', 'description', 'status', 'priority', 'due_date', 'order'])]
class Task extends Model implements HasMedia
{
    use HasFactory, InteractsWithMedia;

    /**
     * Boot the model and register created/updated event listeners.
     */
    protected static function booted(): void
    {
        static::created(function (Task $task) {
            $task->load('assignee', 'project');

            if (auth()->check()) {
                ActivityLog::create([
                    'user_id' => auth()->id(),
                    'task_id' => $task->id,
                    'project_id' => $task->project_id,
                    'action' => 'task_created',
                    'description' => "Task '{$task->title}' was created by " . auth()->user()->name,
                ]);
            }

            // Dispatch notification regardless of who created it (e.g., API or system)
            if ($task->assignee) {
                \App\Services\NotificationService::sendTaskAssigned($task, $task->assignee, auth()->user(), true);
            } else {
                if (auth()->check()) {
                    \App\Services\NotificationService::sendTaskCreated($task, auth()->user());
                }
            }
        });

        static::updated(function (Task $task) {
            $task->load('assignee', 'project');
            $actor = auth()->user();

            if ($task->wasChanged('assigned_to')) {
                $newAssigneeId = $task->assigned_to;
                
                if ($actor) {
                    if ($newAssigneeId === $actor->id) {
                        ActivityLog::create([
                            'user_id' => $actor->id,
                            'task_id' => $task->id,
                            'project_id' => $task->project_id,
                            'action' => 'task_claimed',
                            'description' => "Task '{$task->title}' was claimed by " . $actor->name,
                        ]);
                    } elseif ($newAssigneeId) {
                        $assigneeName = $task->assignee?->name ?? 'User';
                        ActivityLog::create([
                            'user_id' => $actor->id,
                            'task_id' => $task->id,
                            'project_id' => $task->project_id,
                            'action' => 'task_assigned',
                            'description' => "Task '{$task->title}' was assigned to {$assigneeName} by " . $actor->name,
                        ]);
                    } else {
                        ActivityLog::create([
                            'user_id' => $actor->id,
                            'task_id' => $task->id,
                            'project_id' => $task->project_id,
                            'action' => 'task_unassigned',
                            'description' => "Task '{$task->title}' was unassigned by " . $actor->name,
                        ]);
                    }
                }

                // Dispatch assignment notification
                if ($newAssigneeId && $task->assignee) {
                    \App\Services\NotificationService::sendTaskAssigned($task, $task->assignee, $actor, false);
                }

                $originalAssigneeId = $task->getOriginal('assigned_to');
                if ($originalAssigneeId && $originalAssigneeId !== $newAssigneeId) {
                    $oldAssignee = User::find($originalAssigneeId);
                    if ($oldAssignee && $oldAssignee->telegram_id) {
                        $escapedTitle = TelegramService::escapeMarkdownV2($task->title);
                        $text = "➖ *Task has been unassigned from you:*\n*Title:* {$escapedTitle}";
                        SendTelegramMessageJob::dispatch($oldAssignee->telegram_id, $text);
                    }
                }
            }

            if ($task->wasChanged('status')) {
                $readableStatus = str_replace('_', ' ', $task->status);
                if ($actor) {
                    ActivityLog::create([
                        'user_id' => $actor->id,
                        'task_id' => $task->id,
                        'project_id' => $task->project_id,
                        'action' => 'task_status_updated',
                        'description' => "Task '{$task->title}' status changed to '{$readableStatus}' by " . $actor->name,
                    ]);
                }

                \App\Services\NotificationService::sendTaskStatusUpdated(
                    $task, 
                    $task->getOriginal('status') ?? 'todo', 
                    $task->status, 
                    $actor
                );
            }

            $relevant = ['title', 'description', 'priority', 'due_date'];
            $changed = array_filter($relevant, fn($field) => $task->wasChanged($field));
            if (!empty($changed) && !$task->wasChanged('status') && !$task->wasChanged('assigned_to')) {
                if ($actor) {
                    ActivityLog::create([
                        'user_id' => $actor->id,
                        'task_id' => $task->id,
                        'project_id' => $task->project_id,
                        'action' => 'task_updated',
                        'description' => "Task '{$task->title}' details were updated by " . $actor->name,
                    ]);
                }

                // Send details updated notification to assignee (if any)
                if ($task->assignee && (!$actor || $task->assignee->id !== $actor->id)) {
                    \App\Services\NotificationService::sendTaskStatusUpdated($task, $task->status, $task->status, $actor);
                }
            }
        });
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'due_date' => 'date',
        ];
    }

    /**
     * Get the project that owns the task.
     */
    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    /**
     * Get the user who created the task.
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'creator_id');
    }

    /**
     * Get the user assigned to the task.
     */
    public function assignee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    /**
     * Register media collections.
     */
    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('documents');
    }

    /**
     * Get root comments for the task.
     */
    public function comments(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(Comment::class)->whereNull('parent_id')->orderBy('created_at', 'asc');
    }

    /**
     * Get all comments for the task.
     */
    public function allComments(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(Comment::class)->orderBy('created_at', 'asc');
    }

    /**
     * Get the time logs for the task.
     */
    public function timeLogs(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(TaskTimeLog::class);
    }

    /**
     * Get the activity logs for the task.
     */
    public function activityLogs(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(ActivityLog::class);
    }

    /**
     * Get the currently active timer for the logged-in user on this task, if any.
     */
    public function activeTimer(): ?TaskTimeLog
    {
        return $this->timeLogs()
            ->where('user_id', auth()->id())
            ->whereNull('stopped_at')
            ->first();
    }

    /**
     * Get the total duration in seconds.
     */
    public function getTotalDurationAttribute(): int
    {
        $logged = $this->timeLogs()->whereNotNull('stopped_at')->sum('duration_seconds');
        
        $active = $this->timeLogs()->whereNull('stopped_at')->first();
        if ($active) {
            $logged += $active->started_at->diffInSeconds(now(), true);
        }
        
        return (int) $logged;
    }

    /**
     * Get formatted total duration (e.g. 02:45:10).
     */
    public function getFormattedDurationAttribute(): string
    {
        $seconds = $this->total_duration;
        $hours = floor($seconds / 3600);
        $minutes = floor(($seconds / 60) % 60);
        $seconds = $seconds % 60;

        return sprintf('%02d:%02d:%02d', $hours, $minutes, $seconds);
    }

    /**
     * Get formatted total duration in human readable format (e.g. 2h 15m 5s).
     */
    public function getHumanFormattedDurationAttribute(): string
    {
        $seconds = $this->total_duration;
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

    /**
     * Get duration grouped by user.
     * Returns an array of arrays, e.g. [['user' => User, 'duration' => 7440, 'formatted' => '02:04:00', 'human' => '2h 4m']]
     */
    public function getDurationByUser(): array
    {
        // Get all completed logs + currently active logs
        $logs = $this->timeLogs()->with('user')->get();
        
        $users = [];
        foreach ($logs as $log) {
            if (!$log->user) {
                continue;
            }
            $userId = $log->user_id;
            if (!isset($users[$userId])) {
                $users[$userId] = [
                    'user' => $log->user,
                    'duration' => 0,
                ];
            }
            
            $duration = $log->duration_seconds;
            if ($duration === null) {
                // Active log
                $duration = $log->started_at->diffInSeconds(now(), true);
            }
            
            $users[$userId]['duration'] += $duration;
        }
        
        // Convert to formatting
        $result = [];
        foreach ($users as $data) {
            $seconds = $data['duration'];
            $hours = floor($seconds / 3600);
            $minutes = floor(($seconds / 60) % 60);
            $secs = $seconds % 60;
            
            $data['formatted'] = sprintf('%02d:%02d:%02d', $hours, $minutes, $secs);
            
            // Format also in human format
            $parts = [];
            if ($hours > 0) $parts[] = "{$hours}h";
            if ($minutes > 0 || $hours > 0) $parts[] = "{$minutes}m";
            if ($secs > 0 || empty($parts)) $parts[] = "{$secs}s";
            $data['human'] = implode(' ', $parts);
            
            $result[] = $data;
        }
        
        // Sort by duration descending
        usort($result, fn($a, $b) => $b['duration'] <=> $a['duration']);
        
        return $result;
    }
}
