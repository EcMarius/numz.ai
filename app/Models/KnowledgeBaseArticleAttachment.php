<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

class KnowledgeBaseArticleAttachment extends Model
{
    protected $table = 'kb_article_attachments';

    protected $fillable = [
        'article_id',
        'filename',
        'original_filename',
        'mime_type',
        'file_size',
        'storage_path',
    ];

    public function article(): BelongsTo
    {
        return $this->belongsTo(KnowledgeBaseArticle::class, 'article_id');
    }

    /**
     * Get the full URL to the attachment
     */
    public function getUrlAttribute(): string
    {
        return Storage::url($this->storage_path);
    }

    /**
     * Get human-readable file size
     */
    public function getHumanFileSizeAttribute(): string
    {
        $bytes = $this->file_size;
        $units = ['B', 'KB', 'MB', 'GB'];

        for ($i = 0; $bytes > 1024 && $i < count($units) - 1; $i++) {
            $bytes /= 1024;
        }

        return round($bytes, 2) . ' ' . $units[$i];
    }

    /**
     * Check if file is an image
     */
    public function isImage(): bool
    {
        return str_starts_with($this->mime_type, 'image/');
    }

    /**
     * Delete attachment file when model is deleted
     */
    protected static function booted(): void
    {
        static::deleting(function (KnowledgeBaseArticleAttachment $attachment) {
            if (Storage::exists($attachment->storage_path)) {
                Storage::delete($attachment->storage_path);
            }
        });
    }
}
