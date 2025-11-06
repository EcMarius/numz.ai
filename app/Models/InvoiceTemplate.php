<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class InvoiceTemplate extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'slug',
        'description',
        'html_template',
        'css',
        'variables',
        'is_default',
        'is_active',
    ];

    protected $casts = [
        'variables' => 'array',
        'is_default' => 'boolean',
        'is_active' => 'boolean',
    ];

    /**
     * Get the default template
     */
    public static function getDefault(): ?self
    {
        return self::where('is_default', true)
            ->where('is_active', true)
            ->first();
    }

    /**
     * Set this template as default
     */
    public function setAsDefault(): void
    {
        // Remove default flag from all other templates
        self::where('id', '!=', $this->id)->update(['is_default' => false]);
        $this->update(['is_default' => true]);
    }

    /**
     * Render the template with data
     */
    public function render(array $data): string
    {
        $html = $this->html_template;

        // Replace variables in template
        foreach ($data as $key => $value) {
            if (is_scalar($value)) {
                $html = str_replace("{{" . $key . "}}", $value, $html);
            }
        }

        return $html;
    }
}
