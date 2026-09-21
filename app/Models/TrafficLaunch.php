<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TrafficLaunch extends Model
{
    use HasFactory;

    protected $fillable = [
        'client_id',
        'project_id',
        'website_id',
        'task_id',
        'target_month',
        'date_range',
        'domain',
        'plan',
        'geo',
        'bounce_rate',
        'pages',
        'time_on_page',
        'referral_traf',
        'referral_links',
        'social_traf',
        'social_links',
        'organic_traf',
        'direct_traf',
        'keywords',
        'comment',
        'status',
    ];

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function website(): BelongsTo
    {
        return $this->belongsTo(Website::class);
    }

    public function task(): BelongsTo
    {
        return $this->belongsTo(Task::class);
    }
}
