<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class EmailTemplate extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'slug',
        'category',
        'subject',
        'html_body',
        'text_body',
        'available_variables',
        'is_active',
        'is_system',
        'attachments',
        'from_name',
        'from_email',
        'reply_to',
        'created_by',
    ];

    protected $casts = [
        'available_variables' => 'array',
        'is_active' => 'boolean',
        'is_system' => 'boolean',
        'attachments' => 'array',
    ];

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Render template with variables
     */
    public function render(array $variables): string
    {
        $html = $this->html_body;

        foreach ($variables as $key => $value) {
            // Support nested array notation: {{ user.name }}
            if (is_array($value)) {
                foreach ($value as $subKey => $subValue) {
                    $html = str_replace("{{ {$key}.{$subKey} }}", $subValue, $html);
                }
            } else {
                $html = str_replace("{{ {$key} }}", $value, $html);
            }
        }

        return $html;
    }

    /**
     * Render subject with variables
     */
    public function renderSubject(array $variables): string
    {
        $subject = $this->subject;

        foreach ($variables as $key => $value) {
            if (is_scalar($value)) {
                $subject = str_replace("{{ {$key} }}", $value, $subject);
            }
        }

        return $subject;
    }

    /**
     * Get default template for category
     */
    public static function getDefault(string $category): ?self
    {
        return self::where('category', $category)
            ->where('is_active', true)
            ->where('is_system', true)
            ->first();
    }

    /**
     * Scope for active templates
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope by category
     */
    public function scopeCategory($query, string $category)
    {
        return $query->where('category', $category);
    }
}
