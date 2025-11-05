<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class KnowledgeBaseArticleComment extends Model
{
    protected $table = 'kb_article_comments';

    protected $fillable = [
        'article_id',
        'user_id',
        'comment',
        'is_staff_reply',
        'status',
    ];

    protected $casts = [
        'is_staff_reply' => 'boolean',
    ];

    public function article(): BelongsTo
    {
        return $this->belongsTo(KnowledgeBaseArticle::class, 'article_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Scope to get approved comments
     */
    public function scopeApproved($query)
    {
        return $query->where('status', 'approved');
    }
}
