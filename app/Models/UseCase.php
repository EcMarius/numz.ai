<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class UseCase extends Model
{
    protected $fillable = [
        'title',
        'description',
        'icon',
        'color',
        'target_audience',
        'order',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'order' => 'integer',
    ];

    /**
     * Scope to get only active use cases
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope to get use cases ordered
     */
    public function scopeOrdered($query)
    {
        return $query->orderBy('order', 'asc')->orderBy('created_at', 'asc');
    }
}
