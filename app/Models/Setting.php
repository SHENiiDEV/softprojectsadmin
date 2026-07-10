<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Setting extends Model
{
    protected $primaryKey = 'key';
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = ['key', 'value', 'type'];

    /**
     * Get a setting by key.
     */
    public static function get(string $key, $default = null)
    {
        $setting = self::find($key);
        if (!$setting) {
            return $default;
        }

        return match ($setting->type) {
            'boolean' => filter_var($setting->value, FILTER_VALIDATE_BOOLEAN),
            'integer' => (int) $setting->value,
            'json' => json_decode($setting->value, true),
            default => $setting->value,
        };
    }

    /**
     * Set a setting by key.
     */
    public static function set(string $key, $value, string $type = 'string'): void
    {
        $serializedValue = match ($type) {
            'json' => json_encode($value),
            'boolean' => $value ? '1' : '0',
            default => $value === null ? null : (string) $value,
        };

        self::updateOrCreate(
            ['key' => $key],
            ['value' => $serializedValue, 'type' => $type]
        );
    }
}
