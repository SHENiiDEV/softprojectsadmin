<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

#[Fillable([
    'support_ticket_id',
    'gmail_message_id',
    'from',
    'to',
    'is_outgoing',
    'body_text',
    'sent_at',
])]
class SupportTicketMessage extends Model
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
            'is_outgoing' => 'boolean',
            'sent_at' => 'datetime',
        ];
    }

    /**
     * Get the parent support ticket.
     */
    public function ticket(): BelongsTo
    {
        return $this->belongsTo(SupportTicket::class, 'support_ticket_id');
    }

    /**
     * Get attachments for this specific message.
     */
    public function attachments(): HasMany
    {
        return $this->hasMany(SupportTicketAttachment::class);
    }

    /**
     * Extract clean sender display name from "Name <email@domain.com>".
     */
    public function getSenderNameAttribute(): string
    {
        $rawFrom = trim((string) $this->from);

        if (preg_match('/^([^<]+)<[^>]+>/', $rawFrom, $matches)) {
            $name = trim(trim($matches[1]), '"\'');
            if (! empty($name) && ! str_contains($name, '@')) {
                return $name;
            }
        }

        // Fallback: extract username part before @ from email
        $email = $this->sender_email;
        if (str_contains($email, '@')) {
            $parts = explode('@', $email);

            return Str::title(str_replace(['.', '_', '-'], ' ', $parts[0]));
        }

        return 'Customer';
    }

    /**
     * Extract clean email address from "Name <email@domain.com>".
     */
    public function getSenderEmailAttribute(): string
    {
        $rawFrom = trim((string) $this->from);

        if (preg_match('/<([^>]+)>/', $rawFrom, $matches)) {
            return strtolower(trim($matches[1]));
        }

        return strtolower($rawFrom);
    }
}
