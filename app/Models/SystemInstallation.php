<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SystemInstallation extends Model
{
    protected $table = 'system_installation';

    protected $fillable = [
        'is_installed',
        'license_key',
        'license_email',
        'license_status',
        'license_verified_at',
        'installed_at',
        'installation_id',
        'app_version',
    ];

    protected $casts = [
        'is_installed' => 'boolean',
        'license_verified_at' => 'datetime',
        'installed_at' => 'datetime',
    ];

    public static function isInstalled(): bool
    {
        return self::first()?->is_installed ?? false;
    }

    public static function getInstallation()
    {
        return self::first();
    }

    public static function isLicenseValid(): bool
    {
        $installation = self::first();
        return $installation && $installation->license_status === 'active';
    }
}
