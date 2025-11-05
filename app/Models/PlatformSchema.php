<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PlatformSchema extends Model
{
    /**
     * Boot the model and register event listeners
     */
    protected static function boot()
    {
        parent::boot();

        // Log schema creation
        static::created(function ($schema) {
            PlatformSchemaHistory::logChange(
                $schema->id,
                'created',
                null,
                $schema->toArray(),
                $schema->version,
                'Schema element created',
                auth()->id()
            );
        });

        // Log schema updates
        static::updated(function ($schema) {
            PlatformSchemaHistory::logChange(
                $schema->id,
                'updated',
                $schema->getOriginal(),
                $schema->toArray(),
                $schema->version,
                'Schema element updated',
                auth()->id()
            );
        });

        // Log schema deletion
        static::deleted(function ($schema) {
            PlatformSchemaHistory::logChange(
                $schema->id,
                'deleted',
                $schema->toArray(),
                null,
                $schema->version,
                'Schema element deleted',
                auth()->id()
            );
        });
    }
    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'platform',
        'page_type',
        'element_type',
        'css_selector',
        'xpath_selector',
        'is_required',
        'fallback_value',
        'parent_element',
        'multiple',
        'is_wrapper',
        'version',
        'is_active',
        'description',
        'notes',
        'order',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'is_required' => 'boolean',
        'multiple' => 'boolean',
        'is_wrapper' => 'boolean',
        'is_active' => 'boolean',
        'order' => 'integer',
    ];

    /**
     * Supported platforms
     */
    const PLATFORMS = [
        'linkedin',
        'reddit',
        'facebook',
        'x',
        'fiverr',
        'upwork',
    ];

    /**
     * Supported page types
     */
    const PAGE_TYPES = [
        'search_list',   // Search results page showing multiple posts
        'post_page',     // Single post detail page
        'profile',       // Person's profile page
        'group',         // Group page
        'person_feed',   // Person's activity feed
        'messaging',     // Messaging/DM page
    ];

    /**
     * Standardized element types
     */
    const ELEMENT_TYPES = [
        // Wrappers/Containers
        'post_wrapper',
        'person_wrapper',
        'group_wrapper',
        'comment_wrapper',

        // Post elements
        'post_title',
        'post_description',
        'post_content',
        'post_url',

        // Person elements
        'person_name',
        'person_headline',
        'person_bio',
        'person_url',
        'person_avatar',

        // Group elements
        'group_name',
        'group_description',
        'group_url',

        // Meta elements
        'author_name',
        'author_url',
        'author_avatar',
        'timestamp',

        // Engagement metrics
        'like_count',
        'comment_count',
        'share_count',
        'view_count',

        // Comment elements
        'comment_text',
        'comment_author',
        'comment_time',

        // Action buttons
        'like_button',
        'comment_button',
        'share_button',

        // Interactive elements (for automation)
        'search_input',
        'search_button',
        'search_form',
        'next_page_button',
        'load_more_button',

        // Messaging elements
        'message_input',              // Message text input field
        'message_send_button',        // Send button in messaging interface
        'profile_message_button',     // "Message" button on profile page to open chat
        'message_conversation_wrapper', // Conversation container
        'message_recipient_name',     // Recipient name in message thread
        'message_timestamp',          // Message timestamp
    ];

    /**
     * Scope: Get schema for specific platform and page type
     */
    public function scopeForPlatform(Builder $query, string $platform, string $pageType): Builder
    {
        return $query->where('platform', $platform)
                     ->where('page_type', $pageType)
                     ->where('is_active', true)
                     ->orderBy('order');
    }

    /**
     * Scope: Get only active schemas
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    /**
     * Get history records for this schema
     */
    public function history(): HasMany
    {
        return $this->hasMany(PlatformSchemaHistory::class);
    }

    /**
     * Get schema as array grouped by element type
     */
    public static function getSchemaArray(string $platform, string $pageType): array
    {
        $schemas = self::forPlatform($platform, $pageType)->get();

        $result = [];
        foreach ($schemas as $schema) {
            $result[$schema->element_type] = [
                'css_selector' => $schema->css_selector,
                'xpath_selector' => $schema->xpath_selector,
                'is_required' => $schema->is_required,
                'fallback_value' => $schema->fallback_value,
                'parent_element' => $schema->parent_element,
                'multiple' => $schema->multiple,
            ];
        }

        return $result;
    }

    /**
     * Get schema as JSON export format
     */
    public static function exportSchema(string $platform, string $pageType): array
    {
        $schemas = self::forPlatform($platform, $pageType)->get();

        return [
            'platform' => $platform,
            'page_type' => $pageType,
            'version' => $schemas->first()?->version ?? '1.0.0',
            'elements' => $schemas->map(function ($schema) {
                return [
                    'element_type' => $schema->element_type,
                    'css_selector' => $schema->css_selector,
                    'xpath_selector' => $schema->xpath_selector,
                    'is_required' => $schema->is_required,
                    'fallback_value' => $schema->fallback_value,
                    'parent_element' => $schema->parent_element,
                    'multiple' => $schema->multiple,
                    'description' => $schema->description,
                ];
            })->toArray(),
        ];
    }

    /**
     * Import schema from JSON
     */
    public static function importSchema(array $data): bool
    {
        if (!isset($data['platform'], $data['page_type'], $data['elements'])) {
            return false;
        }

        $platform = $data['platform'];
        $pageType = $data['page_type'];
        $version = $data['version'] ?? '1.0.0';

        // Deactivate existing schemas for this platform/page_type
        self::where('platform', $platform)
            ->where('page_type', $pageType)
            ->update(['is_active' => false]);

        // Import new schemas
        $order = 0;
        foreach ($data['elements'] as $element) {
            self::create([
                'platform' => $platform,
                'page_type' => $pageType,
                'element_type' => $element['element_type'],
                'css_selector' => $element['css_selector'] ?? null,
                'xpath_selector' => $element['xpath_selector'] ?? null,
                'is_required' => $element['is_required'] ?? false,
                'fallback_value' => $element['fallback_value'] ?? null,
                'parent_element' => $element['parent_element'] ?? null,
                'multiple' => $element['multiple'] ?? false,
                'version' => $version,
                'is_active' => true,
                'description' => $element['description'] ?? null,
                'order' => $order++,
            ]);
        }

        return true;
    }

    /**
     * Validate selector format
     */
    public static function validateSelector(string $selector, string $type): bool
    {
        if (empty($selector)) {
            return false;
        }

        if ($type === 'css') {
            // Basic CSS selector validation
            // Check for invalid characters or patterns
            return !preg_match('/[<>{}]/', $selector);
        }

        if ($type === 'xpath') {
            // Basic XPath validation
            return str_starts_with($selector, '/') || str_starts_with($selector, '.');
        }

        return false;
    }
}
