<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class Client extends Model
{
    use HasFactory;

    protected $fillable = ['name', 'hash'];

    protected static function boot()
    {
        parent::boot();

        static::creating(function (Client $client) {
            if (empty($client->hash)) {
                $client->hash = Str::random(32);
            }
        });
    }

    /**
     * Get the companies (projects) associated with the client.
     */
    public function companies(): HasMany
    {
        return $this->hasMany(Project::class, 'client_id');
    }
}
