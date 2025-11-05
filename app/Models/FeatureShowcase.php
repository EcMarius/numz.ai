<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FeatureShowcase extends Model
{
    protected $fillable = [
        'title',
        'description',
        'media_path',
        'media_type',
        'order',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'order' => 'integer',
    ];

    /**
     * Scope to get only active feature showcases
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope to get feature showcases ordered
     */
    public function scopeOrdered($query)
    {
        return $query->orderBy('order', 'asc')->orderBy('created_at', 'asc');
    }

    /**
     * Get the full URL for the media
     */
    public function getMediaUrlAttribute(): ?string
    {
        if (!$this->media_path) {
            return null;
        }

        // If it's already a full URL, return it
        if (str_starts_with($this->media_path, 'http://') || str_starts_with($this->media_path, 'https://')) {
            return $this->media_path;
        }

        // If it starts with /storage/, it's already correct
        if (str_starts_with($this->media_path, '/storage/')) {
            return $this->media_path;
        }

        // Otherwise, prepend /storage/
        return '/storage/' . $this->media_path;
    }
}
