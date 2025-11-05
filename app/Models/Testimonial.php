<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class Testimonial extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'position',
        'company',
        'content',
        'avatar',
        'avatar_fallback',
        'gradient_from',
        'gradient_to',
        'order',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'order' => 'integer',
    ];

    /**
     * Scope to get only active testimonials
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope to get testimonials ordered by order field and creation date
     */
    public function scopeOrdered($query)
    {
        return $query->orderBy('order', 'asc')->orderBy('created_at', 'desc');
    }

    /**
     * Get the avatar URL
     */
    public function getAvatarUrlAttribute()
    {
        if ($this->avatar) {
            // If avatar starts with 'testimonials/', prepend /storage/
            if (str_starts_with($this->avatar, 'testimonials/')) {
                return '/storage/' . $this->avatar;
            }
            // If avatar starts with /storage/, return as is
            if (str_starts_with($this->avatar, '/storage/')) {
                return $this->avatar;
            }
            // Otherwise assume it's a full path
            return $this->avatar;
        }

        return null;
    }

    /**
     * Get initials from name
     */
    public function getInitialsAttribute()
    {
        if ($this->avatar_fallback) {
            return $this->avatar_fallback;
        }

        // Generate initials from name
        $words = explode(' ', $this->name);
        if (count($words) >= 2) {
            return strtoupper(substr($words[0], 0, 1) . substr($words[1], 0, 1));
        }

        return strtoupper(substr($this->name, 0, 2));
    }
}
