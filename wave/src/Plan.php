<?php

namespace Wave;

use Exception;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Cache;
use Spatie\Permission\Models\Role;

class Plan extends Model
{
    protected $guarded = [];

    protected $casts = [
        'custom_properties' => 'array',
        'openai_models' => 'array',
    ];

    public function role(): BelongsTo
    {
        return $this->belongsTo(Role::class);
    }

    /**
     * Get custom property value for a specific plugin
     */
    public function getCustomProperty(string $plugin, string $key, $default = null)
    {
        $properties = $this->custom_properties ?? [];
        return $properties[$plugin][$key] ?? $default;
    }

    /**
     * Get all custom properties for a plugin
     */
    public function getPluginProperties(string $plugin): array
    {
        $properties = $this->custom_properties ?? [];
        return $properties[$plugin] ?? [];
    }

    /**
     * Get all active plans (no caching)
     */
    public static function getActivePlans()
    {
        return self::where('active', 1)->with('role')->get();
    }

    /**
     * Get plan by name (no caching)
     */
    public static function getByName($name)
    {
        return self::where('name', $name)->with('role')->first();
    }

    /**
     * Clear plan cache - no-op since we don't cache anymore
     */
    public static function clearCache()
    {
        // No caching, nothing to clear
    }
}
