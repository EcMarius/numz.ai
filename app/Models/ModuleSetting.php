<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Crypt;

class ModuleSetting extends Model
{
    protected $fillable = ['module_type', 'module_name', 'key', 'value', 'encrypted'];

    protected $casts = [
        'encrypted' => 'boolean',
    ];

    /**
     * Get setting value (decrypt if needed)
     */
    public function getValueAttribute($value)
    {
        if ($this->encrypted && $value) {
            try {
                return Crypt::decryptString($value);
            } catch (\Exception $e) {
                return $value;
            }
        }
        return $value;
    }

    /**
     * Set setting value (encrypt if needed)
     */
    public function setValueAttribute($value)
    {
        if ($this->encrypted && $value) {
            $this->attributes['value'] = Crypt::encryptString($value);
        } else {
            $this->attributes['value'] = $value;
        }
    }

    /**
     * Get setting value statically
     */
    public static function get(string $moduleType, string $moduleName, string $key, $default = null)
    {
        $setting = self::where([
            'module_type' => $moduleType,
            'module_name' => $moduleName,
            'key' => $key,
        ])->first();

        return $setting ? $setting->value : $default;
    }

    /**
     * Set setting value statically
     */
    public static function set(string $moduleType, string $moduleName, string $key, $value, bool $encrypted = false)
    {
        return self::updateOrCreate(
            [
                'module_type' => $moduleType,
                'module_name' => $moduleName,
                'key' => $key,
            ],
            [
                'value' => $value,
                'encrypted' => $encrypted,
            ]
        );
    }

    /**
     * Get all settings for a module
     */
    public static function getModuleSettings(string $moduleType, string $moduleName): array
    {
        return self::where([
            'module_type' => $moduleType,
            'module_name' => $moduleName,
        ])->pluck('value', 'key')->toArray();
    }
}
