<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['project_id', 'responsible_user_id', 'name', 'url', 'status', 'gateways', 'socials', 'visa_status', 'mastercard_status'])]
class Website extends Model
{
    use HasFactory;

    /**
     * Social networks a site can have (name => Font Awesome brand icon). Others can be added by hand.
     *
     * @var array<string, string>
     */
    public const SOCIAL_NETWORKS = [
        'Facebook' => 'fa-brands fa-facebook',
        'Instagram' => 'fa-brands fa-instagram',
        'LinkedIn' => 'fa-brands fa-linkedin',
        'Twitter/X' => 'fa-brands fa-x-twitter',
        'TikTok' => 'fa-brands fa-tiktok',
        'YouTube' => 'fa-brands fa-youtube',
        'Telegram' => 'fa-brands fa-telegram',
        'Pinterest' => 'fa-brands fa-pinterest',
        'Reddit' => 'fa-brands fa-reddit',
        'WhatsApp' => 'fa-brands fa-whatsapp',
        'Discord' => 'fa-brands fa-discord',
    ];

    /**
     * Status of a social profile of the site.
     *
     * @var array<string, string>
     */
    public const SOCIAL_STATUSES = [
        'not_created' => 'Not created',
        'created' => 'Created',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'gateways' => 'array',
            'socials' => 'array',
        ];
    }

    /**
     * Get the project that owns the website.
     */
    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    /**
     * Team member responsible for this website.
     */
    public function responsible(): BelongsTo
    {
        return $this->belongsTo(User::class, 'responsible_user_id');
    }

    /**
     * Get the credentials associated with the website.
     */
    public function credentials(): HasMany
    {
        return $this->hasMany(Credential::class);
    }

    /**
     * Social profiles as a clean list: network, url (or null), status and the SMM checklist
     * (post made / checked, each with who and when).
     *
     * @return list<array{network: string, url: ?string, status: string, post_done: bool, post_done_at: ?string, post_done_by: ?int, checked: bool, checked_at: ?string, checked_by: ?int}>
     */
    public function socialProfiles(): array
    {
        $profiles = [];

        foreach ((array) $this->socials as $profile) {
            $network = trim((string) ($profile['network'] ?? ''));

            if ($network === '') {
                continue;
            }

            $profiles[] = [
                'network' => $network,
                'url' => filled($profile['url'] ?? null) ? (string) $profile['url'] : null,
                'status' => array_key_exists($profile['status'] ?? '', self::SOCIAL_STATUSES) ? $profile['status'] : 'not_created',
                'post_done' => (bool) ($profile['post_done'] ?? false),
                'post_done_at' => $profile['post_done_at'] ?? null,
                'post_done_by' => isset($profile['post_done_by']) ? (int) $profile['post_done_by'] : null,
                'checked' => (bool) ($profile['checked'] ?? false),
                'checked_at' => $profile['checked_at'] ?? null,
                'checked_by' => isset($profile['checked_by']) ? (int) $profile['checked_by'] : null,
            ];
        }

        return $profiles;
    }

    /**
     * Font Awesome icon of a network (generic share icon for custom ones).
     */
    public static function socialIcon(string $network): string
    {
        return self::SOCIAL_NETWORKS[$network] ?? 'fa-solid fa-share-nodes';
    }
}
