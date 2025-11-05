<?php

use Wave\Setting;
use Illuminate\Support\Facades\Blade;
use Wave\Plan;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\Facades\Cache;

if (! function_exists('setting')) {
    function setting($key, $default = null)
    {
        static $settingsCache = null;
        static $settingsTypeCache = null;

        // Fetch all settings from cache or database
        if ($settingsCache === null) {
            $settingsCache = Cache::rememberForever('wave_settings', function () {
                return Setting::pluck('value', 'key')->toArray();
            });
        }

        // Fetch all setting types from cache
        if ($settingsTypeCache === null) {
            $settingsTypeCache = Cache::rememberForever('wave_settings_types', function () {
                return Setting::pluck('type', 'key')->toArray();
            });
        }

        // Get the value
        $value = $settingsCache[$key] ?? $default;

        // If it's an image or file type and value starts with 'settings/', add /storage/ prefix
        if (!empty($value)) {
            $type = $settingsTypeCache[$key] ?? null;
            if (in_array($type, ['image', 'file']) && str_starts_with($value, 'settings/')) {
                $value = '/storage/' . $value;
            }
        }

        return $value;
    }
}

if (! function_exists('blade')) {
    function blade($string)
    {
        return Blade::render($string);
    }
}

if (! function_exists('getMorphAlias')) {
    /**
     * Get the morph alias for a given class.
     *
     * @param  string  $class
     * @return string|null
     */
    function getMorphAlias($class)
    {
        $morphMap = Relation::morphMap();
        $alias = array_search($class, $morphMap);

        return $alias ?: null;
    }
}

if (! function_exists('has_monthly_yearly_toggle')) {
    function has_monthly_yearly_toggle(): bool
    {
        // Cache for 30 minutes to reduce DB queries
        return Cache::remember('has_monthly_yearly_toggle', 1800, function () {
            $plans = Plan::where('active', 1)->get();
            $hasMonthly = false;
            $hasYearly = false;

            foreach ($plans as $plan) {
                if ($plan->active) {
                    // Check if monthly price exists (either price_id or price amount)
                    if (! empty($plan->monthly_price_id) || (! empty($plan->monthly_price) && $plan->monthly_price > 0)) {
                        $hasMonthly = true;
                    }
                    // Check if yearly price exists (either price_id or price amount)
                    if (! empty($plan->yearly_price_id) || (! empty($plan->yearly_price) && $plan->yearly_price > 0)) {
                        $hasYearly = true;
                    }
                }
            }

            // Return true if both monthly and yearly plans exist
            return ($hasMonthly && $hasYearly);
        });
    }
}

if (! function_exists('get_default_billing_cycle')) {
    function get_default_billing_cycle()
    {
        // Cache for 30 minutes to reduce DB queries
        return Cache::remember('default_billing_cycle', 1800, function () {
            $plans = Plan::where('active', 1)->get();
            $hasMonthly = false;
            $hasYearly = false;

            foreach ($plans as $plan) {
                if (! empty($plan->monthly_price_id)) {
                    $hasMonthly = true;
                }
                if (! empty($plan->yearly_price_id)) {
                    $hasYearly = true;
                }
            }

            // Return 'Yearly' if only yearly ID is present
            if ($hasYearly && ! $hasMonthly) {
                return 'Yearly';
            }

            // Return null or a default value if neither is present
            return 'Monthly'; // or any default value you prefer
        });
    }
}

if (!function_exists('wave_version')) {
    /**
     * Get the current Wave version
     *
     * @return string
     */
    function wave_version()
    {
        $waveJsonPath = base_path('wave/wave.json');

        if (file_exists($waveJsonPath)) {
            $waveData = json_decode(file_get_contents($waveJsonPath), true);
            return $waveData['version'] ?? 'Unknown';
        }

        return 'Unknown';
    }
}
