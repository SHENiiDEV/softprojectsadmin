<?php

namespace App\Services;

use App\Models\Setting;
use App\Models\Website;
use Illuminate\Support\Facades\Schema;

class GatewayService
{
    /**
     * Get all available gateway providers.
     *
     * Combines:
     *  1. Defaults from config('gateways.available')
     *  2. Custom gateways added via UI or Artisan command (stored in settings)
     *  3. Any gateways currently assigned to websites in the database
     *
     * @return array<int, string>
     */
    public static function all(): array
    {
        $gateways = config('gateways.available', []);

        // Merge custom gateways saved in settings
        try {
            if (class_exists(Setting::class) && Schema::hasTable('settings')) {
                $customGateways = Setting::get('custom_gateways', []);
                if (is_array($customGateways)) {
                    $gateways = array_merge($gateways, $customGateways);
                }
            }
        } catch (\Throwable $e) {
            // Fallback gracefully during early migrations
        }

        // Merge any gateways from existing websites
        try {
            if (class_exists(Website::class) && Schema::hasTable('websites')) {
                $existing = Website::whereNotNull('gateways')->pluck('gateways');
                foreach ($existing as $gwList) {
                    if (is_array($gwList)) {
                        $gateways = array_merge($gateways, $gwList);
                    }
                }
            }
        } catch (\Throwable $e) {
            // Fallback gracefully
        }

        // Clean, trim, unique and sort
        $cleaned = [];
        foreach ($gateways as $gw) {
            if (is_string($gw)) {
                $trimmed = trim($gw);
                if ($trimmed !== '' && ! in_array($trimmed, $cleaned, true)) {
                    $cleaned[] = $trimmed;
                }
            }
        }

        natcasesort($cleaned);

        return array_values($cleaned);
    }

    /**
     * Add a new gateway provider dynamically.
     *
     * @return string Normalized name
     */
    public static function add(string $name): string
    {
        $normalized = trim($name);

        if ($normalized === '') {
            throw new \InvalidArgumentException('Gateway provider name cannot be empty.');
        }

        $currentCustom = Setting::get('custom_gateways', []);
        if (! is_array($currentCustom)) {
            $currentCustom = [];
        }

        if (! in_array($normalized, $currentCustom, true)) {
            $currentCustom[] = $normalized;
            Setting::set('custom_gateways', array_values(array_unique($currentCustom)), 'json');
        }

        return $normalized;
    }

    /**
     * Check if a gateway provider name is valid.
     */
    public static function isValid(string $name): bool
    {
        return in_array(trim($name), self::all(), true);
    }
}
