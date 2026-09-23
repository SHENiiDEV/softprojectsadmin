<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PciDssDocument extends Model
{
    use HasFactory;

    public const DOCUMENT_TYPES = [
        'PCI DSS',
        'AOC',
        'Scan',
        'SAQ',
        'Agreement gateway',
        'Other',
    ];

    protected $fillable = [
        'client_id',
        'project_id',
        'document_type',
        'custom_type',
        'title',
        'file_path',
        'file_name',
        'file_size',
        'mime_type',
        'valid_until',
        'notes',
        'uploaded_by',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'valid_until' => 'date',
            'file_size' => 'integer',
        ];
    }

    /**
     * Associated client.
     */
    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    /**
     * Associated company / project (optional).
     */
    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    /**
     * User who uploaded the document.
     */
    public function uploader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }

    /**
     * Resolved document type label (resolves "Other" to custom_type).
     */
    public function getDisplayTypeAttribute(): string
    {
        if ($this->document_type === 'Other' && ! empty($this->custom_type)) {
            return $this->custom_type;
        }

        return $this->document_type;
    }

    /**
     * Human-readable file size.
     */
    public function getFormattedFileSizeAttribute(): string
    {
        $bytes = (int) $this->file_size;

        if ($bytes >= 1048576) {
            return number_format($bytes / 1048576, 2).' MB';
        }

        if ($bytes >= 1024) {
            return number_format($bytes / 1024, 1).' KB';
        }

        return $bytes.' B';
    }

    /**
     * Check if document has an expiration date and is expired.
     */
    public function getIsExpiredAttribute(): bool
    {
        if (! $this->valid_until) {
            return false;
        }

        return $this->valid_until->isPast() && ! $this->valid_until->isToday();
    }

    /**
     * Check if document expires within the next 30 days.
     */
    public function getIsExpiringSoonAttribute(): bool
    {
        if (! $this->valid_until) {
            return false;
        }

        $now = Carbon::today();

        return $this->valid_until->greaterThanOrEqualTo($now)
            && $this->valid_until->diffInDays($now) <= 30;
    }
}
