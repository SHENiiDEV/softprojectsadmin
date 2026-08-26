<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Spatie\Permission\Traits\HasRoles;

#[Fillable(['name', 'email', 'password', 'telegram_id', 'telegram_username', 'tg_link_token', 'notification_settings', 'avatar_path', 'timezone', 'language'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, HasRoles, Notifiable;

    /**
     * Get the URL to the user's avatar.
     */
    public function getAvatarUrlAttribute(): string
    {
        return $this->avatar_path
            ? asset('storage/'.$this->avatar_path)
            : 'https://ui-avatars.com/api/?name='.urlencode($this->name).'&color=0EA5E9&background=E0F2FE';
    }

    /**
     * Get the tasks created by the user.
     */
    public function createdTasks(): HasMany
    {
        return $this->hasMany(Task::class, 'creator_id');
    }

    /**
     * Get the tasks assigned to the user.
     */
    public function assignedTasks(): HasMany
    {
        return $this->hasMany(Task::class, 'assigned_to');
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'telegram_id' => 'integer',
            'notification_settings' => 'array',
        ];
    }

    /**
     * Get user notification setting value or default.
     */
    public function getNotificationSetting(string $key, bool $default = true): bool
    {
        $settings = $this->notification_settings ?? [];

        return filter_var($settings[$key] ?? $default, FILTER_VALIDATE_BOOLEAN);
    }

    /**
     * Get a unique gradient background class based on user id.
     */
    public function getGradientAttribute(): string
    {
        $gradients = [
            'from-sky-400 to-indigo-500',
            'from-emerald-400 to-teal-600',
            'from-amber-400 to-orange-500',
            'from-rose-400 to-pink-600',
            'from-purple-400 to-indigo-600',
            'from-fuchsia-400 to-pink-600',
            'from-cyan-400 to-blue-500',
            'from-lime-400 to-green-600',
        ];

        return $gradients[$this->id % count($gradients)];
    }
}
