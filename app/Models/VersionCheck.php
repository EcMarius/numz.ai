<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class VersionCheck extends Model
{
    use HasFactory;

    protected $fillable = [
        'current_version',
        'latest_version',
        'update_available',
        'check_status',
        'error_message',
        'release_info',
        'checked_at',
    ];

    protected $casts = [
        'update_available' => 'boolean',
        'release_info' => 'array',
        'checked_at' => 'datetime',
    ];

    /**
     * Get the latest version check
     */
    public static function getLatest(): ?self
    {
        return self::orderBy('checked_at', 'desc')->first();
    }

    /**
     * Check if a new version check is needed
     */
    public static function needsCheck(): bool
    {
        $latest = self::getLatest();

        if (!$latest) {
            return true;
        }

        $interval = config('updater.check_interval', 24);
        return $latest->checked_at->addHours($interval)->isPast();
    }

    /**
     * Get changelog from release info
     */
    public function getChangelogAttribute(): ?string
    {
        return $this->release_info['body'] ?? null;
    }

    /**
     * Get download URL from release info
     */
    public function getDownloadUrlAttribute(): ?string
    {
        $assets = $this->release_info['assets'] ?? [];

        foreach ($assets as $asset) {
            if (str_ends_with($asset['name'], '.zip')) {
                return $asset['browser_download_url'] ?? null;
            }
        }

        return $this->release_info['zipball_url'] ?? null;
    }

    /**
     * Get release date
     */
    public function getReleaseDateAttribute(): ?string
    {
        $publishedAt = $this->release_info['published_at'] ?? null;

        if ($publishedAt) {
            return \Carbon\Carbon::parse($publishedAt)->format('Y-m-d H:i');
        }

        return null;
    }
}
