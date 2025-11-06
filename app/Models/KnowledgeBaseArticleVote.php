<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class KnowledgeBaseArticleVote extends Model
{
    protected $table = 'kb_article_votes';

    protected $fillable = [
        'article_id',
        'user_id',
        'ip_address',
        'is_helpful',
    ];

    protected $casts = [
        'is_helpful' => 'boolean',
    ];

    public function article(): BelongsTo
    {
        return $this->belongsTo(KnowledgeBaseArticle::class, 'article_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
