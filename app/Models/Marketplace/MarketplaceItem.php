<?php

namespace App\Models\Marketplace;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Storage;

class MarketplaceItem extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'user_id',
        'category_id',
        'name',
        'slug',
        'short_description',
        'description',
        'installation_instructions',
        'changelog',
        'price',
        'creator_revenue_percentage',
        'is_free',
        'current_version',
        'file_path',
        'file_size',
        'demo_url',
        'documentation_url',
        'support_url',
        'repository_url',
        'minimum_php_version',
        'minimum_laravel_version',
        'required_packages',
        'screenshots',
        'icon',
        'banner',
        'video_url',
        'status',
        'rejection_reason',
        'approved_at',
        'approved_by',
        'downloads_count',
        'purchases_count',
        'average_rating',
        'reviews_count',
        'views_count',
        'is_featured',
        'featured_at',
        'is_active',
    ];

    protected $casts = [
        'price' => 'decimal:2',
        'creator_revenue_percentage' => 'decimal:2',
        'is_free' => 'boolean',
        'file_size' => 'integer',
        'required_packages' => 'array',
        'screenshots' => 'array',
        'approved_at' => 'datetime',
        'downloads_count' => 'integer',
        'purchases_count' => 'integer',
        'average_rating' => 'decimal:2',
        'reviews_count' => 'integer',
        'views_count' => 'integer',
        'is_featured' => 'boolean',
        'featured_at' => 'datetime',
        'is_active' => 'boolean',
    ];

    /**
     * The creator of this item
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * The category this item belongs to
     */
    public function category(): BelongsTo
    {
        return $this->belongsTo(MarketplaceCategory::class, 'category_id');
    }

    /**
     * Admin who approved this item
     */
    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    /**
     * All purchases of this item
     */
    public function purchases(): HasMany
    {
        return $this->hasMany(MarketplacePurchase::class);
    }

    /**
     * All reviews of this item
     */
    public function reviews(): HasMany
    {
        return $this->hasMany(MarketplaceReview::class);
    }

    /**
     * Approved reviews only
     */
    public function approvedReviews(): HasMany
    {
        return $this->reviews()->where('is_approved', true);
    }

    /**
     * All versions of this item
     */
    public function versions(): HasMany
    {
        return $this->hasMany(MarketplaceItemVersion::class);
    }

    /**
     * The current version of this item
     */
    public function currentVersion()
    {
        return $this->versions()->where('is_current', true)->first();
    }

    /**
     * All earnings from this item
     */
    public function earnings(): HasMany
    {
        return $this->hasMany(MarketplaceEarning::class);
    }

    /**
     * All download logs for this item
     */
    public function downloadLogs(): HasMany
    {
        return $this->hasMany(MarketplaceDownloadLog::class);
    }

    /**
     * Scope for approved items only
     */
    public function scopeApproved($query)
    {
        return $query->where('status', 'approved');
    }

    /**
     * Scope for active items
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope for featured items
     */
    public function scopeFeatured($query)
    {
        return $query->where('is_featured', true);
    }

    /**
     * Scope for free items
     */
    public function scopeFree($query)
    {
        return $query->where('is_free', true);
    }

    /**
     * Scope for paid items
     */
    public function scopePaid($query)
    {
        return $query->where('is_free', false);
    }

    /**
     * Check if user has purchased this item
     */
    public function isPurchasedBy(User $user): bool
    {
        return $this->purchases()
            ->where('user_id', $user->id)
            ->where('payment_status', 'completed')
            ->exists();
    }

    /**
     * Check if user has reviewed this item
     */
    public function isReviewedBy(User $user): bool
    {
        return $this->reviews()->where('user_id', $user->id)->exists();
    }

    /**
     * Get download URL
     */
    public function getDownloadUrl(): ?string
    {
        if (! $this->file_path) {
            return null;
        }

        return Storage::disk('private')->url($this->file_path);
    }

    /**
     * Get icon URL
     */
    public function getIconUrl(): ?string
    {
        if (! $this->icon) {
            return null;
        }

        return Storage::url($this->icon);
    }

    /**
     * Get banner URL
     */
    public function getBannerUrl(): ?string
    {
        if (! $this->banner) {
            return null;
        }

        return Storage::url($this->banner);
    }

    /**
     * Increment view count
     */
    public function incrementViews(): void
    {
        $this->increment('views_count');
    }

    /**
     * Increment download count
     */
    public function incrementDownloads(): void
    {
        $this->increment('downloads_count');
    }

    /**
     * Increment purchase count
     */
    public function incrementPurchases(): void
    {
        $this->increment('purchases_count');
    }

    /**
     * Update average rating
     */
    public function updateAverageRating(): void
    {
        $averageRating = $this->approvedReviews()->avg('rating');
        $reviewsCount = $this->approvedReviews()->count();

        $this->update([
            'average_rating' => $averageRating ?? 0,
            'reviews_count' => $reviewsCount,
        ]);
    }

    /**
     * Calculate creator earnings from price
     */
    public function getCreatorEarnings(): float
    {
        return $this->price * ($this->creator_revenue_percentage / 100);
    }

    /**
     * Calculate platform fee from price
     */
    public function getPlatformFee(): float
    {
        return $this->price * ((100 - $this->creator_revenue_percentage) / 100);
    }

    /**
     * Get the route key for the model.
     */
    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    /**
     * Approve this item
     */
    public function approve(User $approver): void
    {
        $this->update([
            'status' => 'approved',
            'approved_at' => now(),
            'approved_by' => $approver->id,
            'rejection_reason' => null,
        ]);
    }

    /**
     * Reject this item
     */
    public function reject(string $reason): void
    {
        $this->update([
            'status' => 'rejected',
            'rejection_reason' => $reason,
            'approved_at' => null,
            'approved_by' => null,
        ]);
    }

    /**
     * Submit for review
     */
    public function submitForReview(): void
    {
        $this->update([
            'status' => 'pending_review',
        ]);
    }
}
