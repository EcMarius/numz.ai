<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class KnowledgeBaseArticle extends Model
{
    protected $table = 'kb_articles';

    protected $fillable = [
        'category_id',
        'author_id',
        'title',
        'slug',
        'excerpt',
        'content',
        'status',
        'view_count',
        'helpful_count',
        'not_helpful_count',
        'is_featured',
        'allow_comments',
        'tags',
        'order',
        'published_at',
    ];

    protected $casts = [
        'view_count' => 'integer',
        'helpful_count' => 'integer',
        'not_helpful_count' => 'integer',
        'is_featured' => 'boolean',
        'allow_comments' => 'boolean',
        'tags' => 'array',
        'order' => 'integer',
        'published_at' => 'datetime',
    ];

    public function category(): BelongsTo
    {
        return $this->belongsTo(KnowledgeBaseCategory::class, 'category_id');
    }

    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'author_id');
    }

    public function votes(): HasMany
    {
        return $this->hasMany(KnowledgeBaseArticleVote::class, 'article_id');
    }

    public function comments(): HasMany
    {
        return $this->hasMany(KnowledgeBaseArticleComment::class, 'article_id');
    }

    public function attachments(): HasMany
    {
        return $this->hasMany(KnowledgeBaseArticleAttachment::class, 'article_id');
    }

    /**
     * Scope to get only published articles
     */
    public function scopePublished($query)
    {
        return $query->where('status', 'published')
            ->whereNotNull('published_at')
            ->where('published_at', '<=', now());
    }

    /**
     * Scope to get featured articles
     */
    public function scopeFeatured($query)
    {
        return $query->where('is_featured', true);
    }

    /**
     * Scope to search articles
     */
    public function scopeSearch($query, string $search)
    {
        return $query->where(function ($q) use ($search) {
            $q->where('title', 'like', "%{$search}%")
                ->orWhere('content', 'like', "%{$search}%")
                ->orWhere('excerpt', 'like', "%{$search}%");
        });
    }

    /**
     * Increment view count
     */
    public function incrementViewCount(): void
    {
        $this->increment('view_count');
    }

    /**
     * Mark as helpful
     */
    public function markAsHelpful(User $user = null, string $ipAddress = null): void
    {
        $data = [
            'article_id' => $this->id,
            'is_helpful' => true,
        ];

        if ($user) {
            $data['user_id'] = $user->id;
        }

        if ($ipAddress) {
            $data['ip_address'] = $ipAddress;
        }

        KnowledgeBaseArticleVote::updateOrCreate(
            array_filter(['article_id' => $this->id, 'user_id' => $user?->id, 'ip_address' => $ipAddress]),
            $data
        );

        $this->refreshVoteCounts();
    }

    /**
     * Mark as not helpful
     */
    public function markAsNotHelpful(User $user = null, string $ipAddress = null): void
    {
        $data = [
            'article_id' => $this->id,
            'is_helpful' => false,
        ];

        if ($user) {
            $data['user_id'] = $user->id;
        }

        if ($ipAddress) {
            $data['ip_address'] = $ipAddress;
        }

        KnowledgeBaseArticleVote::updateOrCreate(
            array_filter(['article_id' => $this->id, 'user_id' => $user?->id, 'ip_address' => $ipAddress]),
            $data
        );

        $this->refreshVoteCounts();
    }

    /**
     * Refresh vote counts
     */
    public function refreshVoteCounts(): void
    {
        $this->update([
            'helpful_count' => $this->votes()->where('is_helpful', true)->count(),
            'not_helpful_count' => $this->votes()->where('is_helpful', false)->count(),
        ]);
    }

    /**
     * Get helpfulness percentage
     */
    public function getHelpfulnessPercentageAttribute(): int
    {
        $total = $this->helpful_count + $this->not_helpful_count;

        if ($total === 0) {
            return 0;
        }

        return (int) round(($this->helpful_count / $total) * 100);
    }

    /**
     * Auto-generate slug from title
     */
    protected static function booted(): void
    {
        static::creating(function (KnowledgeBaseArticle $article) {
            if (empty($article->slug)) {
                $article->slug = Str::slug($article->title);

                // Ensure uniqueness
                $count = 1;
                while (self::where('slug', $article->slug)->exists()) {
                    $article->slug = Str::slug($article->title) . '-' . $count;
                    $count++;
                }
            }
        });
    }
}
