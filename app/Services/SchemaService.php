<?php

namespace App\Services;

use App\Models\PlatformSchema;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;

class SchemaService
{
    /**
     * Cache duration for schemas (1 hour)
     */
    const CACHE_TTL = 3600;

    /**
     * Get schema for a specific platform and page type
     */
    public static function getSchema(string $platform, string $pageType): ?array
    {
        $cacheKey = "schema_{$platform}_{$pageType}";

        return Cache::remember($cacheKey, self::CACHE_TTL, function () use ($platform, $pageType) {
            $schema = PlatformSchema::getSchemaArray($platform, $pageType);
            return !empty($schema) ? $schema : null;
        });
    }

    /**
     * Get all schemas for a platform (all page types)
     */
    public static function getPlatformSchemas(string $platform): array
    {
        $pageTypes = PlatformSchema::PAGE_TYPES;
        $schemas = [];

        foreach ($pageTypes as $pageType) {
            $schema = self::getSchema($platform, $pageType);
            if ($schema) {
                $schemas[$pageType] = $schema;
            }
        }

        return $schemas;
    }

    /**
     * Get schemas for multiple platforms
     */
    public static function getSchemasForPlatforms(array $platforms): array
    {
        $result = [];

        foreach ($platforms as $platform) {
            $platformSchemas = self::getPlatformSchemas($platform);
            if (!empty($platformSchemas)) {
                $result[$platform] = $platformSchemas;
            }
        }

        return $result;
    }

    /**
     * Check if schema exists for platform and page type
     */
    public static function schemaExists(string $platform, string $pageType): bool
    {
        return self::getSchema($platform, $pageType) !== null;
    }

    /**
     * Validate schema data before import
     */
    public static function validateSchemaData(array $data): array
    {
        $errors = [];

        // Check required fields
        if (!isset($data['platform'])) {
            $errors[] = 'Platform is required';
        } elseif (!in_array($data['platform'], PlatformSchema::PLATFORMS)) {
            $errors[] = 'Invalid platform: ' . $data['platform'];
        }

        if (!isset($data['page_type'])) {
            $errors[] = 'Page type is required';
        } elseif (!in_array($data['page_type'], PlatformSchema::PAGE_TYPES)) {
            $errors[] = 'Invalid page type: ' . $data['page_type'];
        }

        if (!isset($data['elements']) || !is_array($data['elements'])) {
            $errors[] = 'Elements array is required';
        } else {
            // Validate each element
            foreach ($data['elements'] as $index => $element) {
                $elementErrors = self::validateElement($element, $index);
                $errors = array_merge($errors, $elementErrors);
            }
        }

        return $errors;
    }

    /**
     * Validate a single element
     */
    private static function validateElement(array $element, int $index): array
    {
        $errors = [];
        $prefix = "Element {$index}: ";

        // Check element_type
        if (!isset($element['element_type'])) {
            $errors[] = $prefix . 'element_type is required';
        } elseif (!in_array($element['element_type'], PlatformSchema::ELEMENT_TYPES)) {
            $errors[] = $prefix . 'Invalid element_type: ' . $element['element_type'];
        }

        // At least one selector must be provided
        if (empty($element['css_selector']) && empty($element['xpath_selector'])) {
            $errors[] = $prefix . 'At least one selector (CSS or XPath) is required';
        }

        // Validate CSS selector if provided
        if (!empty($element['css_selector'])) {
            if (!PlatformSchema::validateSelector($element['css_selector'], 'css')) {
                $errors[] = $prefix . 'Invalid CSS selector format';
            }
        }

        // Validate XPath selector if provided
        if (!empty($element['xpath_selector'])) {
            if (!PlatformSchema::validateSelector($element['xpath_selector'], 'xpath')) {
                $errors[] = $prefix . 'Invalid XPath selector format';
            }
        }

        return $errors;
    }

    /**
     * Import schema from JSON data
     */
    public static function importSchema(array $data): array
    {
        // Validate first
        $errors = self::validateSchemaData($data);
        if (!empty($errors)) {
            return [
                'success' => false,
                'errors' => $errors,
            ];
        }

        try {
            $success = PlatformSchema::importSchema($data);

            if ($success) {
                // Clear cache for this platform/page_type
                self::clearCache($data['platform'], $data['page_type']);

                return [
                    'success' => true,
                    'message' => 'Schema imported successfully',
                ];
            } else {
                return [
                    'success' => false,
                    'errors' => ['Failed to import schema'],
                ];
            }
        } catch (\Exception $e) {
            Log::error('Schema import failed', [
                'platform' => $data['platform'] ?? null,
                'page_type' => $data['page_type'] ?? null,
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'errors' => ['Import failed: ' . $e->getMessage()],
            ];
        }
    }

    /**
     * Export schema as JSON
     */
    public static function exportSchema(string $platform, string $pageType): ?array
    {
        try {
            return PlatformSchema::exportSchema($platform, $pageType);
        } catch (\Exception $e) {
            Log::error('Schema export failed', [
                'platform' => $platform,
                'page_type' => $pageType,
                'error' => $e->getMessage(),
            ]);
            return null;
        }
    }

    /**
     * Test selector on provided HTML
     */
    public static function testSelector(string $html, string $selector, string $type = 'css'): array
    {
        try {
            $dom = new \DOMDocument();
            @$dom->loadHTML($html, LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);

            if ($type === 'css') {
                // For CSS, we'd need a CSS-to-XPath converter or use a library
                // For now, return a placeholder response
                return [
                    'success' => true,
                    'matches' => 0,
                    'message' => 'CSS selector testing requires client-side implementation',
                ];
            } elseif ($type === 'xpath') {
                $xpath = new \DOMXPath($dom);
                $nodes = $xpath->query($selector);

                $results = [];
                foreach ($nodes as $node) {
                    $results[] = [
                        'tag' => $node->nodeName,
                        'text' => trim($node->textContent),
                        'html' => $dom->saveHTML($node),
                    ];
                }

                return [
                    'success' => true,
                    'matches' => $nodes->length,
                    'results' => $results,
                ];
            }
        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }

        return [
            'success' => false,
            'error' => 'Invalid selector type',
        ];
    }

    /**
     * Clear schema cache
     */
    public static function clearCache(?string $platform = null, ?string $pageType = null): void
    {
        if ($platform && $pageType) {
            // Clear specific cache
            Cache::forget("schema_{$platform}_{$pageType}");
        } elseif ($platform) {
            // Clear all caches for platform
            foreach (PlatformSchema::PAGE_TYPES as $pageType) {
                Cache::forget("schema_{$platform}_{$pageType}");
            }
        } else {
            // Clear all schema caches
            foreach (PlatformSchema::PLATFORMS as $platform) {
                foreach (PlatformSchema::PAGE_TYPES as $pageType) {
                    Cache::forget("schema_{$platform}_{$pageType}");
                }
            }
        }
    }

    /**
     * Get schema version
     */
    public static function getSchemaVersion(string $platform, string $pageType): ?string
    {
        $schema = PlatformSchema::forPlatform($platform, $pageType)->first();
        return $schema?->version;
    }

    /**
     * Update schema version
     */
    public static function updateSchemaVersion(string $platform, string $pageType, string $version): bool
    {
        try {
            PlatformSchema::where('platform', $platform)
                ->where('page_type', $pageType)
                ->where('is_active', true)
                ->update(['version' => $version]);

            self::clearCache($platform, $pageType);
            return true;
        } catch (\Exception $e) {
            Log::error('Failed to update schema version', [
                'platform' => $platform,
                'page_type' => $pageType,
                'error' => $e->getMessage(),
            ]);
            return false;
        }
    }

    /**
     * Get all missing schemas for enabled platforms
     */
    public static function getMissingSchemas(array $platforms): array
    {
        $missing = [];

        foreach ($platforms as $platform) {
            foreach (PlatformSchema::PAGE_TYPES as $pageType) {
                if (!self::schemaExists($platform, $pageType)) {
                    if (!isset($missing[$platform])) {
                        $missing[$platform] = [];
                    }
                    $missing[$platform][] = $pageType;
                }
            }
        }

        return $missing;
    }

    /**
     * Bulk import multiple schemas at once
     */
    public static function bulkImport(array $schemas): array
    {
        $results = [];
        $successCount = 0;
        $errorCount = 0;

        foreach ($schemas as $index => $schema) {
            $result = self::importSchema($schema);

            if ($result['success']) {
                $successCount++;
                $results[] = [
                    'index' => $index,
                    'platform' => $schema['platform'] ?? 'unknown',
                    'page_type' => $schema['page_type'] ?? 'unknown',
                    'status' => 'success',
                ];
            } else {
                $errorCount++;
                $results[] = [
                    'index' => $index,
                    'platform' => $schema['platform'] ?? 'unknown',
                    'page_type' => $schema['page_type'] ?? 'unknown',
                    'status' => 'error',
                    'errors' => $result['errors'],
                ];
            }
        }

        return [
            'success' => $errorCount === 0,
            'total' => count($schemas),
            'imported' => $successCount,
            'failed' => $errorCount,
            'results' => $results,
        ];
    }

    /**
     * Get schema history for a platform/page type
     */
    public static function getHistory(string $platform, string $pageType, int $limit = 20): array
    {
        $schemaIds = PlatformSchema::where('platform', $platform)
            ->where('page_type', $pageType)
            ->pluck('id');

        return \App\Models\PlatformSchemaHistory::whereIn('platform_schema_id', $schemaIds)
            ->orderBy('created_at', 'desc')
            ->limit($limit)
            ->with('platformSchema')
            ->get()
            ->toArray();
    }

    /**
     * Rollback to a previous schema version
     */
    public static function rollback(string $platform, string $pageType, int $historyId): array
    {
        try {
            $history = \App\Models\PlatformSchemaHistory::find($historyId);

            if (!$history || !$history->old_data) {
                return [
                    'success' => false,
                    'errors' => ['History record not found or no old data available'],
                ];
            }

            // Deactivate current schemas
            PlatformSchema::where('platform', $platform)
                ->where('page_type', $pageType)
                ->update(['is_active' => false]);

            // Restore old schema
            $oldData = $history->old_data;
            PlatformSchema::create($oldData);

            self::clearCache($platform, $pageType);

            return [
                'success' => true,
                'message' => 'Schema rolled back successfully',
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'errors' => [$e->getMessage()],
            ];
        }
    }
}

