<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class SystemSetting extends Model
{
    use HasFactory;

    protected $fillable = [
        'group',
        'key',
        'value',
        'type',
        'description',
        'validation_rules',
        'options',
        'is_public',
        'requires_restart',
    ];

    protected $casts = [
        'validation_rules' => 'array',
        'options' => 'array',
        'is_public' => 'boolean',
        'requires_restart' => 'boolean',
    ];

    /**
     * Get typed value
     */
    public function getTypedValue()
    {
        return match($this->type) {
            'boolean' => filter_var($this->value, FILTER_VALIDATE_BOOLEAN),
            'integer' => (int) $this->value,
            'float', 'decimal' => (float) $this->value,
            'json' => json_decode($this->value, true),
            'array' => json_decode($this->value, true),
            'encrypted' => decrypt($this->value),
            default => $this->value,
        };
    }

    /**
     * Set typed value
     */
    public function setTypedValue($value): void
    {
        $storedValue = match($this->type) {
            'boolean' => $value ? '1' : '0',
            'json', 'array' => json_encode($value),
            'encrypted' => encrypt($value),
            default => (string) $value,
        };

        $this->update(['value' => $storedValue]);
    }

    /**
     * Get setting by key (helper)
     */
    public static function get(string $key, $default = null)
    {
        $setting = self::where('key', $key)->first();
        return $setting ? $setting->getTypedValue() : $default;
    }

    /**
     * Set setting by key (helper)
     */
    public static function set(string $key, $value, string $group = 'general'): self
    {
        $setting = self::firstOrCreate(
            ['key' => $key],
            ['group' => $group, 'type' => self::inferType($value)]
        );

        $setting->setTypedValue($value);
        return $setting;
    }

    /**
     * Infer type from value
     */
    private static function inferType($value): string
    {
        if (is_bool($value)) return 'boolean';
        if (is_int($value)) return 'integer';
        if (is_float($value)) return 'float';
        if (is_array($value)) return 'json';
        return 'string';
    }

    /**
     * Get all settings in a group
     */
    public static function getGroup(string $group): array
    {
        return self::where('group', $group)
            ->get()
            ->mapWithKeys(fn($setting) => [$setting->key => $setting->getTypedValue()])
            ->toArray();
    }

    /**
     * Scope by group
     */
    public function scopeGroup($query, string $group)
    {
        return $query->where('group', $group);
    }

    /**
     * Scope for public settings
     */
    public function scopePublic($query)
    {
        return $query->where('is_public', true);
    }
}
