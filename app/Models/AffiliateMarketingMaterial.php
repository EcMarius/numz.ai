<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class AffiliateMarketingMaterial extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'type',
        'description',
        'content',
        'image_url',
        'size',
        'metadata',
        'usage_count',
        'is_active',
    ];

    protected $casts = [
        'metadata' => 'array',
        'is_active' => 'boolean',
    ];

    /**
     * Get HTML code for banner
     */
    public function getHtmlCode(Affiliate $affiliate): string
    {
        $url = $affiliate->getReferralUrl();

        return match($this->type) {
            'banner' => "<a href=\"{$url}\"><img src=\"{$this->image_url}\" alt=\"{$this->name}\" /></a>",
            'text_link' => "<a href=\"{$url}\">{$this->content}</a>",
            'email_template' => str_replace('{{affiliate_url}}', $url, $this->content),
            default => $this->content,
        };
    }

    /**
     * Increment usage count
     */
    public function incrementUsage(): void
    {
        $this->increment('usage_count');
    }

    /**
     * Get active materials
     */
    public static function getActive(string $type = null): \Illuminate\Database\Eloquent\Collection
    {
        $query = self::where('is_active', true);

        if ($type) {
            $query->where('type', $type);
        }

        return $query->orderBy('usage_count', 'desc')->get();
    }
}
