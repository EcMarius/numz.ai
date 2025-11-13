<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Wave\ApiKey;

class ApiAuthController extends Controller
{
    /**
     * Login and get API token
     */
    public function login(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'password' => 'required',
        ]);

        $user = User::where('email', $request->email)->first();

        if (!$user || !Hash::check($request->password, $user->password)) {
            throw ValidationException::withMessages([
                'email' => ['The provided credentials are incorrect.'],
            ]);
        }

        // Create Sanctum token
        $token = $user->createToken('api-token')->plainTextToken;

        return response()->json([
            'success' => true,
            'message' => 'Login successful',
            'data' => [
                'user' => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                ],
                'token' => $token,
                'token_type' => 'Bearer',
            ],
        ]);
    }

    /**
     * Register new user
     */
    public function register(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:8|confirmed',
        ]);

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
        ]);

        // Create Sanctum token
        $token = $user->createToken('api-token')->plainTextToken;

        return response()->json([
            'success' => true,
            'message' => 'Registration successful',
            'data' => [
                'user' => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                ],
                'token' => $token,
                'token_type' => 'Bearer',
            ],
        ], 201);
    }

    /**
     * Get all API keys for authenticated user
     */
    public function getApiKeys(Request $request)
    {
        $user = auth()->user();
        $apiKeys = ApiKey::where('user_id', $user->id)->get();

        return response()->json([
            'success' => true,
            'data' => $apiKeys->map(function ($key) {
                return [
                    'id' => $key->id,
                    'name' => $key->name,
                    'key' => $key->key,
                    'last_used_at' => $key->last_used_at?->toIso8601String(),
                    'created_at' => $key->created_at?->toIso8601String(),
                ];
            }),
        ]);
    }

    /**
     * Create new API key
     */
    public function createApiKey(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
        ]);

        $user = auth()->user();

        $apiKey = ApiKey::create([
            'user_id' => $user->id,
            'name' => $request->name,
            'key' => 'sk_' . Str::random(40),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'API key created successfully',
            'data' => [
                'id' => $apiKey->id,
                'name' => $apiKey->name,
                'key' => $apiKey->key,
                'created_at' => $apiKey->created_at?->toIso8601String(),
            ],
        ], 201);
    }

    /**
     * Delete API key
     */
    public function deleteApiKey(Request $request, $id)
    {
        $user = auth()->user();

        $apiKey = ApiKey::where('user_id', $user->id)->where('id', $id)->first();

        if (!$apiKey) {
            return response()->json([
                'success' => false,
                'error' => 'API key not found',
            ], 404);
        }

        $apiKey->delete();

        return response()->json([
            'success' => true,
            'message' => 'API key deleted successfully',
        ]);
    }
}
