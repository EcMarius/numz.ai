<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;

class CustomReport extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'created_by',
        'name',
        'slug',
        'description',
        'type',
        'category',
        'data_sources',
        'columns',
        'filters',
        'grouping',
        'sorting',
        'calculations',
        'chart_type',
        'chart_config',
        'is_public',
        'shared_with_users',
        'shared_with_roles',
        'is_favorite',
        'view_count',
        'last_generated_at',
    ];

    protected $casts = [
        'data_sources' => 'array',
        'columns' => 'array',
        'filters' => 'array',
        'grouping' => 'array',
        'sorting' => 'array',
        'calculations' => 'array',
        'chart_config' => 'array',
        'shared_with_users' => 'array',
        'shared_with_roles' => 'array',
        'is_public' => 'boolean',
        'is_favorite' => 'boolean',
        'last_generated_at' => 'datetime',
    ];

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function schedules(): HasMany
    {
        return $this->hasMany(ReportSchedule::class);
    }

    public function executions(): HasMany
    {
        return $this->hasMany(ReportExecution::class);
    }

    public function exports(): HasMany
    {
        return $this->hasMany(ReportExport::class);
    }

    /**
     * Generate unique slug
     */
    public static function generateSlug(string $name): string
    {
        $slug = \Illuminate\Support\Str::slug($name);
        $count = 1;
        $originalSlug = $slug;

        while (self::where('slug', $slug)->exists()) {
            $slug = $originalSlug . '-' . $count++;
        }

        return $slug;
    }

    /**
     * Check if user can access report
     */
    public function canAccess(User $user): bool
    {
        if ($this->is_public) {
            return true;
        }

        if ($this->created_by === $user->id) {
            return true;
        }

        if ($this->shared_with_users && in_array($user->id, $this->shared_with_users)) {
            return true;
        }

        if ($this->shared_with_roles && method_exists($user, 'hasAnyRole')) {
            return $user->hasAnyRole($this->shared_with_roles);
        }

        return false;
    }

    /**
     * Increment view count
     */
    public function incrementViews(): void
    {
        $this->increment('view_count');
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

    /**
     * Get report types
     */
    public static function getTypes(): array
    {
        return [
            'revenue' => 'Revenue Report',
            'customers' => 'Customer Report',
            'products' => 'Product Report',
            'services' => 'Service Report',
            'invoices' => 'Invoice Report',
            'payments' => 'Payment Report',
            'custom' => 'Custom Report',
        ];
    }

    /**
     * Get popular reports
     */
    public static function getPopular(int $limit = 10): \Illuminate\Database\Eloquent\Collection
    {
        return self::orderBy('view_count', 'desc')
            ->limit($limit)
            ->get();
    }
}
