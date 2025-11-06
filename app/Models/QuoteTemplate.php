<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class QuoteTemplate extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'description',
        'content',
        'type',
        'is_default',
        'is_active',
        'sections',
        'styling',
    ];

    protected $casts = [
        'is_default' => 'boolean',
        'is_active' => 'boolean',
        'sections' => 'array',
        'styling' => 'array',
    ];

    /**
     * Get default template
     */
    public static function getDefault(): ?self
    {
        return self::where('is_default', true)
            ->where('is_active', true)
            ->first();
    }

    /**
     * Render template with quote data
     */
    public function render(Quote $quote): string
    {
        $content = $this->content;

        // Replace variables
        $variables = [
            'quote.number' => $quote->quote_number,
            'quote.title' => $quote->title,
            'quote.date' => $quote->created_at->format('M d, Y'),
            'quote.valid_until' => $quote->valid_until?->format('M d, Y'),
            'quote.subtotal' => number_format($quote->subtotal, 2),
            'quote.tax' => number_format($quote->tax, 2),
            'quote.discount' => number_format($quote->discount, 2),
            'quote.total' => number_format($quote->total, 2),
            'customer.name' => $quote->user->name,
            'customer.email' => $quote->user->email,
            'company.name' => config('app.name'),
        ];

        foreach ($variables as $key => $value) {
            $content = str_replace("{{ {$key} }}", $value, $content);
        }

        return $content;
    }
}
