<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class CustomerSegmentMember extends Model
{
    use HasFactory;

    protected $fillable = [
        'customer_segment_id',
        'user_id',
        'added_at',
    ];

    protected $casts = [
        'added_at' => 'datetime',
    ];

    public function segment(): BelongsTo
    {
        return $this->belongsTo(CustomerSegment::class, 'customer_segment_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get member count by segment
     */
    public static function getMemberCountBySegment(): array
    {
        return self::selectRaw('customer_segment_id, COUNT(*) as count')
            ->groupBy('customer_segment_id')
            ->pluck('count', 'customer_segment_id')
            ->toArray();
    }

    /**
     * Get segments for a user
     */
    public static function getUserSegments(int $userId): \Illuminate\Database\Eloquent\Collection
    {
        return self::with('segment')
            ->where('user_id', $userId)
            ->get()
            ->pluck('segment');
    }
}
