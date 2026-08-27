<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

#[Fillable([
    'gmail_thread_id',
    'customer_email',
    'recipient_email',
    'subject',
    'status',
    'categories',
    'project_id',
    'website_id',
    'task_id',
])]
class SupportTicket extends Model
{
    use HasFactory;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'categories' => 'array',
        ];
    }

    /**
     * Get the project associated with the ticket.
     */
    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    /**
     * Get the website associated with the ticket.
     */
    public function website(): BelongsTo
    {
        return $this->belongsTo(Website::class);
    }

    /**
     * Get the CRM task associated with the ticket.
     */
    public function task(): BelongsTo
    {
        return $this->belongsTo(Task::class);
    }

    /**
     * Get all messages for this support ticket.
     */
    public function messages(): HasMany
    {
        return $this->hasMany(SupportTicketMessage::class)->orderBy('sent_at', 'asc');
    }

    /**
     * Get all attachments for this support ticket.
     */
    public function attachments(): HasMany
    {
        return $this->hasMany(SupportTicketAttachment::class);
    }

    /**
     * Get the customer's display name from the first incoming message or email.
     */
    public function getCustomerNameAttribute(): string
    {
        $firstMsg = $this->messages()->where('is_outgoing', false)->orderBy('id', 'asc')->first();

        if ($firstMsg && ! empty($firstMsg->sender_name)) {
            return $firstMsg->sender_name;
        }

        if ($this->customer_email && str_contains($this->customer_email, '@')) {
            $parts = explode('@', $this->customer_email);

            return Str::title(str_replace(['.', '_', '-'], ' ', $parts[0]));
        }

        return 'Customer';
    }
}
