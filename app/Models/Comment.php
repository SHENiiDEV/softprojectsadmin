<?php

namespace App\Models;

use App\Models\Concerns\HasFormattedContent;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Collection;

class Comment extends Model
{
    use HasFactory;
    use HasFormattedContent;

    protected $fillable = [
        'task_id',
        'project_id',
        'user_id',
        'client_id',
        'parent_id',
        'content',
        'is_private',
    ];

    protected $casts = [
        'is_private' => 'boolean',
    ];

    /**
     * Get the project that owns the comment.
     */
    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    /**
     * Get the task that owns the comment.
     */
    public function task(): BelongsTo
    {
        return $this->belongsTo(Task::class);
    }

    /**
     * Get the team member who wrote the comment.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the client who wrote the comment.
     */
    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    /**
     * Get the parent comment (if this is a reply).
     */
    public function parent(): BelongsTo
    {
        return $this->belongsTo(Comment::class, 'parent_id');
    }

    /**
     * Get the nested replies.
     */
    public function replies(): HasMany
    {
        return $this->hasMany(Comment::class, 'parent_id')->orderBy('created_at', 'asc');
    }

    public function getMentionedUsers(): Collection
    {
        preg_match_all('/\B@([a-zA-Z0-9_]+)\b/', $this->content, $matches);
        $usernames = $matches[1] ?? [];

        if (empty($usernames)) {
            return collect();
        }

        $query = User::query()->whereIn('telegram_username', $usernames);

        foreach ($usernames as $uname) {
            $cleanName = strtolower(preg_replace('/[^a-zA-Z0-9_]/', '', $uname));
            $query->orWhereRaw("lower(replace(name, ' ', '')) = ?", [$cleanName])
                ->orWhereRaw("lower(replace(name, ' ', '_')) = ?", [$cleanName]);
        }

        return $query->get();
    }
}
