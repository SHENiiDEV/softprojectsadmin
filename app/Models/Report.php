<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'project_id',
    'reg_number',
    'auth_code',
    'registered_address',
    'ch_pass',
    'accounts_due_by',
    'statements_due_by'
])]
class Report extends Model
{
    use HasFactory;

    /**
     * Get the project that owns the report.
     */
    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'accounts_due_by' => 'date',
            'statements_due_by' => 'date',
        ];
    }
}
