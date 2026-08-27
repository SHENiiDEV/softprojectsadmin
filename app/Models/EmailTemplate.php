<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['name', 'category', 'subject', 'body_text', 'created_by'])]
class EmailTemplate extends Model
{
    use HasFactory;

    /**
     * Get creator user.
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Replace template placeholders with actual data context.
     */
    public function renderBody(array $context = []): string
    {
        $text = $this->body_text;

        $replacements = [
            '{client_name}' => $context['client_name'] ?? 'Customer',
            '{client_email}' => $context['client_email'] ?? '',
            '{company_name}' => $context['company_name'] ?? 'Support Team',
            '{website_url}' => $context['website_url'] ?? '',
            '{ticket_number}' => $context['ticket_number'] ?? '',
            '{agent_name}' => $context['agent_name'] ?? auth()->user()?->name ?? 'Support Team',
        ];

        return strtr($text, $replacements);
    }
}
