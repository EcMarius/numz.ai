<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;

class CustomerSegment extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'created_by',
        'name',
        'description',
        'segment_type',
        'criteria',
        'member_count',
        'total_revenue',
        'avg_order_value',
        'is_active',
        'last_calculated_at',
    ];

    protected $casts = [
        'criteria' => 'array',
        'is_active' => 'boolean',
        'last_calculated_at' => 'datetime',
    ];

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function members(): HasMany
    {
        return $this->hasMany(CustomerSegmentMember::class);
    }

    /**
     * Calculate segment membership based on criteria
     */
    public function calculateMembers(): int
    {
        // Build query based on criteria
        $query = User::where('role', 'customer');

        foreach ($this->criteria as $criterion) {
            $query = $this->applyCriterion($query, $criterion);
        }

        $customers = $query->get();

        // Clear existing members
        $this->members()->delete();

        // Add new members
        foreach ($customers as $customer) {
            $this->members()->create([
                'user_id' => $customer->id,
                'added_at' => now(),
            ]);
        }

        // Update counts
        $this->updateStats();

        $this->update(['last_calculated_at' => now()]);

        return $this->member_count;
    }

    /**
     * Apply a single criterion to query
     */
    protected function applyCriterion($query, array $criterion)
    {
        $field = $criterion['field'];
        $operator = $criterion['operator'];
        $value = $criterion['value'];

        return match($operator) {
            'equals' => $query->where($field, $value),
            'not_equals' => $query->where($field, '!=', $value),
            'greater_than' => $query->where($field, '>', $value),
            'less_than' => $query->where($field, '<', $value),
            'contains' => $query->where($field, 'like', "%{$value}%"),
            'in' => $query->whereIn($field, is_array($value) ? $value : [$value]),
            'not_in' => $query->whereNotIn($field, is_array($value) ? $value : [$value]),
            'between' => $query->whereBetween($field, [$value['min'], $value['max']]),
            default => $query,
        };
    }

    /**
     * Update segment statistics
     */
    public function updateStats(): void
    {
        $memberIds = $this->members()->pluck('user_id');

        $totalRevenue = \App\Models\Invoice::whereIn('user_id', $memberIds)
            ->where('status', 'paid')
            ->sum('total');

        $orderCount = \App\Models\Order::whereIn('user_id', $memberIds)
            ->where('status', 'active')
            ->count();

        $avgOrderValue = $orderCount > 0 ? $totalRevenue / $orderCount : 0;

        $this->update([
            'member_count' => $this->members()->count(),
            'total_revenue' => $totalRevenue,
            'avg_order_value' => round($avgOrderValue, 2),
        ]);
    }

    /**
     * Get high-value segments
     */
    public static function getHighValue(int $limit = 10): \Illuminate\Database\Eloquent\Collection
    {
        return self::where('is_active', true)
            ->orderBy('total_revenue', 'desc')
            ->limit($limit)
            ->get();
    }

    /**
     * Get segment types
     */
    public static function getTypes(): array
    {
        return [
            'behavioral' => 'Behavioral',
            'demographic' => 'Demographic',
            'geographic' => 'Geographic',
            'psychographic' => 'Psychographic',
            'technographic' => 'Technographic',
            'value_based' => 'Value-Based',
            'lifecycle' => 'Lifecycle',
            'custom' => 'Custom',
        ];
    }

    /**
     * Activate segment
     */
    public function activate(): void
    {
        $this->update(['is_active' => true]);
        $this->calculateMembers();
    }

    /**
     * Deactivate segment
     */
    public function deactivate(): void
    {
        $this->update(['is_active' => false]);
    }
}
