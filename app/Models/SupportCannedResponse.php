<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SupportCannedResponse extends Model
{
    protected $fillable = [
        'title',
        'category',
        'content',
        'is_active',
        'usage_count',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'usage_count' => 'integer',
    ];

    /**
     * Scope to get only active responses
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope to get responses by category
     */
    public function scopeByCategory($query, string $category)
    {
        return $query->where('category', $category);
    }

    /**
     * Increment usage count
     */
    public function incrementUsage(): void
    {
        $this->increment('usage_count');
    }

    /**
     * Get available categories
     */
    public static function getCategories(): array
    {
        return [
            'general' => 'General',
            'technical' => 'Technical Support',
            'billing' => 'Billing',
            'sales' => 'Sales',
            'account' => 'Account Management',
            'other' => 'Other',
        ];
    }
}
