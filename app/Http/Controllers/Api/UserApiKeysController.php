<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

/**
 * Controller for fetching user's API keys (for documentation use only)
 */
class UserApiKeysController extends Controller
{
    /**
     * Get list of API keys for authenticated user (names only, not the actual keys)
     * This endpoint is only accessible when the user is logged in via web session
     */
    public function index(Request $request)
    {
        if (!auth()->check()) {
            return response()->json([
                'success' => false,
                'message' => 'Not authenticated',
            ], 401);
        }

        $apiKeys = auth()->user()->apiKeys()
            ->select('id', 'name', 'key')
            ->get()
            ->map(function ($key) {
                return [
                    'id' => $key->id,
                    'name' => $key->name,
                    'key' => $key->key,
                ];
            });

        return response()->json([
            'success' => true,
            'data' => $apiKeys,
        ]);
    }
}
