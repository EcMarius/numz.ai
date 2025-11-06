<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class CustomReport extends Model
{
    use HasFactory;

    protected $fillable = [
        'created_by',
        'name',
        'slug',
        'description',
        'type',
        'columns',
        'filters',
        'grouping',
        'sorting',
        'chart_type',
        'is_public',
        'scheduled_delivery',
    ];

    protected $casts = [
        'columns' => 'array',
        'filters' => 'array',
        'grouping' => 'array',
        'sorting' => 'array',
        'scheduled_delivery' => 'array',
        'is_public' => 'boolean',
    ];

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function schedules(): HasMany
    {
        return $this->hasMany(ReportSchedule::class);
    }

    /**
     * Execute report and get data
     */
    public function execute(): array
    {
        // This would contain logic to execute the report based on configuration
        // For now, return empty array
        return [];
    }
}
