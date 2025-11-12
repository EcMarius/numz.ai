<?php

namespace App\Numz\WHMCS;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;

/**
 * WHMCS Settings Manager
 *
 * Manages WHMCS settings stored in database
 * All settings are configurable via Admin Panel > WHMCS Settings
 */
class Settings
{
    /**
     * Get a WHMCS setting from database
     *
     * @param string $key Setting key (e.g., 'invoicing_auto_create')
     * @param mixed $default Default value if setting not found
     * @return mixed
     */
    public static function get(string $key, $default = null)
    {
        return Cache::remember("whmcs_setting_{$key}", 3600, function () use ($key, $default) {
            $value = DB::table('tblconfiguration')
                ->where('setting', "whmcs.{$key}")
                ->value('value');

            if ($value === null) {
                // Try to get from config defaults
                return self::getDefault($key, $default);
            }

            // Try to decode JSON
            $decoded = json_decode($value, true);
            if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                return $decoded;
            }

            // Convert string booleans
            if ($value === '1' || $value === 'true') {
                return true;
            }
            if ($value === '0' || $value === 'false') {
                return false;
            }

            return $value;
        });
    }

    /**
     * Set a WHMCS setting in database
     *
     * @param string $key Setting key
     * @param mixed $value Setting value
     * @return bool
     */
    public static function set(string $key, $value): bool
    {
        if (is_array($value)) {
            $value = json_encode($value);
        } elseif (is_bool($value)) {
            $value = $value ? '1' : '0';
        }

        DB::table('tblconfiguration')->updateOrInsert(
            ['setting' => "whmcs.{$key}"],
            ['value' => $value, 'updated_at' => now()]
        );

        // Clear cache
        Cache::forget("whmcs_setting_{$key}");

        return true;
    }

    /**
     * Get all WHMCS settings
     *
     * @return array
     */
    public static function all(): array
    {
        return Cache::remember('whmcs_settings_all', 3600, function () {
            $settings = DB::table('tblconfiguration')
                ->where('setting', 'LIKE', 'whmcs.%')
                ->get();

            $data = [];
            foreach ($settings as $setting) {
                $key = str_replace('whmcs.', '', $setting->setting);
                $value = $setting->value;

                // Try to decode JSON
                $decoded = json_decode($value, true);
                if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                    $value = $decoded;
                } elseif ($value === '1' || $value === 'true') {
                    $value = true;
                } elseif ($value === '0' || $value === 'false') {
                    $value = false;
                }

                $data[$key] = $value;
            }

            // Merge with defaults
            return array_merge(self::getAllDefaults(), $data);
        });
    }

    /**
     * Clear all settings cache
     *
     * @return void
     */
    public static function clearCache(): void
    {
        Cache::forget('whmcs_settings_all');
        // Clear individual setting caches
        $keys = DB::table('tblconfiguration')
            ->where('setting', 'LIKE', 'whmcs.%')
            ->pluck('setting');

        foreach ($keys as $key) {
            $shortKey = str_replace('whmcs.', '', $key);
            Cache::forget("whmcs_setting_{$shortKey}");
        }
    }

    /**
     * Get default value for a setting
     *
     * @param string $key
     * @param mixed $fallback
     * @return mixed
     */
    protected static function getDefault(string $key, $fallback = null)
    {
        $defaults = config('whmcs.defaults', []);

        // Handle nested keys (e.g., 'invoicing_auto_create')
        if (str_contains($key, '_')) {
            $parts = explode('_', $key, 2);
            $group = $parts[0];
            $subkey = $parts[1];

            if (isset($defaults[$group][$subkey])) {
                return $defaults[$group][$subkey];
            }
        }

        // Direct key
        if (isset($defaults[$key])) {
            return $defaults[$key];
        }

        return $fallback;
    }

    /**
     * Get all default settings as flat array
     *
     * @return array
     */
    protected static function getAllDefaults(): array
    {
        $defaults = config('whmcs.defaults', []);
        $flat = [];

        foreach ($defaults as $group => $settings) {
            if (is_array($settings)) {
                foreach ($settings as $key => $value) {
                    $flat["{$group}_{$key}"] = $value;
                }
            } else {
                $flat[$group] = $settings;
            }
        }

        return $flat;
    }

    /**
     * Get specific setting group
     *
     * @param string $group Group name (e.g., 'invoicing', 'provisioning')
     * @return array
     */
    public static function getGroup(string $group): array
    {
        $all = self::all();
        $filtered = [];

        foreach ($all as $key => $value) {
            if (str_starts_with($key, $group . '_')) {
                $shortKey = str_replace($group . '_', '', $key);
                $filtered[$shortKey] = $value;
            }
        }

        return $filtered;
    }
}
