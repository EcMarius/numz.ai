<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\PlatformSchema;
use App\Services\SchemaService;
use Wave\Plugins\EvenLeads\Models\Platform;
use Illuminate\Http\Request;

/**
 * @group Schema Management
 *
 * API endpoints for managing platform scraping schemas
 */
class SchemaController extends Controller
{
    /**
     * Get all schemas
     *
     * Returns all active platform schemas, optionally filtered by platform and page type.
     *
     * @authenticated
     * @queryParam platform string Filter by platform. Example: linkedin
     * @queryParam page_type string Filter by page type. Example: post
     * @response 200 {
     *   "success": true,
     *   "schemas": [...]
     * }
     */
    public function index(Request $request)
    {
        $query = PlatformSchema::active();

        if ($request->has('platform')) {
            $query->where('platform', $request->input('platform'));
        }

        if ($request->has('page_type')) {
            $query->where('page_type', $request->input('page_type'));
        }

        $schemas = $query->orderBy('platform')
            ->orderBy('page_type')
            ->orderBy('order')
            ->get();

        return response()->json([
            'success' => true,
            'schemas' => $schemas,
        ]);
    }

    /**
     * Get schema for specific platform and page type
     *
     * @authenticated
     * @urlParam platform string required The platform. Example: linkedin
     * @urlParam pageType string required The page type. Example: post
     * @response 200 {
     *   "success": true,
     *   "schema": {...}
     * }
     */
    public function show(string $platform, string $pageType)
    {
        $schema = SchemaService::getSchema($platform, $pageType);

        if (!$schema) {
            return response()->json([
                'success' => false,
                'message' => 'Schema not found',
            ], 404);
        }

        // Get platform messaging configuration
        $platformModel = Platform::where('name', $platform)->first();
        $messagingConfig = null;

        if ($platformModel) {
            $messagingConfig = [
                'input_selectors' => json_decode($platformModel->message_input_selectors ?? '[]'),
                'send_button_selectors' => json_decode($platformModel->message_send_button_selectors ?? '[]'),
                'supports_enter_to_send' => (bool) $platformModel->supports_enter_to_send,
            ];
        }

        return response()->json([
            'success' => true,
            'schema' => $schema,
            'messaging' => $messagingConfig,
        ]);
    }

    /**
     * Import schema from JSON
     *
     * Imports a platform schema from JSON data. Validates the schema before import.
     *
     * @authenticated
     * @bodyParam platform string required The platform. Example: linkedin
     * @bodyParam page_type string required The page type. Example: post
     * @bodyParam version string The schema version. Example: 1.0.0
     * @bodyParam elements array required Array of schema elements
     * @response 200 {
     *   "success": true,
     *   "message": "Schema imported successfully"
     * }
     */
    public function import(Request $request)
    {
        $data = $request->all();

        $result = SchemaService::importSchema($data);

        if ($result['success']) {
            return response()->json($result);
        } else {
            return response()->json($result, 422);
        }
    }

    /**
     * Export schema as JSON
     *
     * Exports a platform schema as JSON.
     *
     * @authenticated
     * @urlParam platform string required The platform. Example: linkedin
     * @urlParam pageType string required The page type. Example: post
     * @response 200 {
     *   "success": true,
     *   "schema": {...}
     * }
     */
    public function export(string $platform, string $pageType)
    {
        $schema = SchemaService::exportSchema($platform, $pageType);

        if (!$schema) {
            return response()->json([
                'success' => false,
                'message' => 'Schema not found or export failed',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'schema' => $schema,
        ]);
    }

    /**
     * Test selector on HTML
     *
     * Tests a CSS or XPath selector on provided HTML content.
     *
     * @authenticated
     * @bodyParam html string required The HTML content to test against
     * @bodyParam selector string required The selector to test
     * @bodyParam type string required The selector type (css or xpath). Example: css
     * @response 200 {
     *   "success": true,
     *   "matches": 3,
     *   "results": [...]
     * }
     */
    public function testSelector(Request $request)
    {
        $request->validate([
            'html' => 'required|string',
            'selector' => 'required|string',
            'type' => 'required|string|in:css,xpath',
        ]);

        $result = SchemaService::testSelector(
            $request->input('html'),
            $request->input('selector'),
            $request->input('type')
        );

        return response()->json($result);
    }

    /**
     * Create or update a schema element
     *
     * @authenticated
     * @bodyParam platform string required The platform. Example: linkedin
     * @bodyParam page_type string required The page type. Example: post
     * @bodyParam element_type string required The element type. Example: post_title
     * @bodyParam css_selector string The CSS selector
     * @bodyParam xpath_selector string The XPath selector
     * @bodyParam is_required boolean Whether the element is required. Example: true
     * @bodyParam multiple boolean Whether the element can match multiple items. Example: false
     * @response 200 {
     *   "success": true,
     *   "schema": {...}
     * }
     */
    public function store(Request $request)
    {
        $request->validate([
            'platform' => 'required|string|in:' . implode(',', PlatformSchema::PLATFORMS),
            'page_type' => 'required|string|in:' . implode(',', PlatformSchema::PAGE_TYPES),
            'element_type' => 'required|string|in:' . implode(',', PlatformSchema::ELEMENT_TYPES),
            'css_selector' => 'nullable|string',
            'xpath_selector' => 'nullable|string',
            'is_required' => 'boolean',
            'multiple' => 'boolean',
            'parent_element' => 'nullable|string',
            'fallback_value' => 'nullable|string',
            'description' => 'nullable|string',
        ]);

        // Validate that at least one selector is provided
        if (empty($request->input('css_selector')) && empty($request->input('xpath_selector'))) {
            return response()->json([
                'success' => false,
                'message' => 'At least one selector (CSS or XPath) is required',
            ], 422);
        }

        try {
            $schema = PlatformSchema::create($request->all());

            // Clear cache
            SchemaService::clearCache($request->input('platform'), $request->input('page_type'));

            return response()->json([
                'success' => true,
                'schema' => $schema,
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to create schema: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Update a schema element
     *
     * @authenticated
     * @urlParam id integer required The schema element ID
     * @response 200 {
     *   "success": true,
     *   "schema": {...}
     * }
     */
    public function update(Request $request, int $id)
    {
        $schema = PlatformSchema::find($id);

        if (!$schema) {
            return response()->json([
                'success' => false,
                'message' => 'Schema not found',
            ], 404);
        }

        $request->validate([
            'css_selector' => 'nullable|string',
            'xpath_selector' => 'nullable|string',
            'is_required' => 'boolean',
            'multiple' => 'boolean',
            'parent_element' => 'nullable|string',
            'fallback_value' => 'nullable|string',
            'description' => 'nullable|string',
            'is_active' => 'boolean',
        ]);

        try {
            $schema->update($request->all());

            // Clear cache
            SchemaService::clearCache($schema->platform, $schema->page_type);

            return response()->json([
                'success' => true,
                'schema' => $schema->fresh(),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update schema: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Delete a schema element
     *
     * @authenticated
     * @urlParam id integer required The schema element ID
     * @response 200 {
     *   "success": true,
     *   "message": "Schema deleted successfully"
     * }
     */
    public function destroy(int $id)
    {
        $schema = PlatformSchema::find($id);

        if (!$schema) {
            return response()->json([
                'success' => false,
                'message' => 'Schema not found',
            ], 404);
        }

        $platform = $schema->platform;
        $pageType = $schema->page_type;

        try {
            $schema->delete();

            // Clear cache
            SchemaService::clearCache($platform, $pageType);

            return response()->json([
                'success' => true,
                'message' => 'Schema deleted successfully',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete schema: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get missing schemas
     *
     * Returns platforms/page types that don't have schemas configured.
     *
     * @authenticated
     * @queryParam platforms array Platforms to check. Example: ["linkedin", "reddit"]
     * @response 200 {
     *   "success": true,
     *   "missing": {...}
     * }
     */
    public function missing(Request $request)
    {
        $platforms = $request->input('platforms', PlatformSchema::PLATFORMS);

        $missing = SchemaService::getMissingSchemas($platforms);

        return response()->json([
            'success' => true,
            'missing' => $missing,
        ]);
    }

    /**
     * Clear schema cache
     *
     * @authenticated
     * @queryParam platform string Optional platform filter
     * @queryParam page_type string Optional page type filter
     * @response 200 {
     *   "success": true,
     *   "message": "Cache cleared successfully"
     * }
     */
    public function clearCache(Request $request)
    {
        SchemaService::clearCache(
            $request->input('platform'),
            $request->input('page_type')
        );

        return response()->json([
            'success' => true,
            'message' => 'Cache cleared successfully',
        ]);
    }

    /**
     * Bulk import multiple schemas
     *
     * @authenticated
     * @bodyParam schemas array required Array of schema objects
     * @response 200 {
     *   "success": true,
     *   "total": 5,
     *   "imported": 5,
     *   "failed": 0,
     *   "results": [...]
     * }
     */
    public function bulkImport(Request $request)
    {
        $request->validate([
            'schemas' => 'required|array',
            'schemas.*.platform' => 'required|string',
            'schemas.*.page_type' => 'required|string',
            'schemas.*.elements' => 'required|array',
        ]);

        $result = SchemaService::bulkImport($request->input('schemas'));

        return response()->json($result, $result['success'] ? 200 : 422);
    }

    /**
     * Get schema history
     *
     * @authenticated
     * @urlParam platform string required The platform
     * @urlParam pageType string required The page type
     * @queryParam limit int Number of history records to return. Example: 20
     * @response 200 {
     *   "success": true,
     *   "history": [...]
     * }
     */
    public function history(Request $request, string $platform, string $pageType)
    {
        $limit = $request->input('limit', 20);
        $history = SchemaService::getHistory($platform, $pageType, $limit);

        return response()->json([
            'success' => true,
            'history' => $history,
        ]);
    }

    /**
     * Rollback to previous schema version
     *
     * @authenticated
     * @urlParam platform string required The platform
     * @urlParam pageType string required The page type
     * @bodyParam history_id int required The history record ID to rollback to
     * @response 200 {
     *   "success": true,
     *   "message": "Schema rolled back successfully"
     * }
     */
    public function rollback(Request $request, string $platform, string $pageType)
    {
        $request->validate([
            'history_id' => 'required|integer|exists:platform_schema_history,id',
        ]);

        $result = SchemaService::rollback($platform, $pageType, $request->input('history_id'));

        return response()->json($result, $result['success'] ? 200 : 422);
    }
}

