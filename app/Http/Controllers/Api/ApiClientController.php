<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\ClientResource;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class ApiClientController extends Controller
{
    /**
     * Get all clients (users)
     */
    public function index(Request $request)
    {
        $perPage = $request->input('per_page', 15);
        $search = $request->input('search');
        $status = $request->input('status');

        $query = User::query();

        // Search
        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%")
                  ->orWhere('company_name', 'like', "%{$search}%");
            });
        }

        // Filter by status
        if ($status === 'active') {
            $query->where('verified', 1);
        } elseif ($status === 'inactive') {
            $query->where('verified', 0);
        }

        // Order by latest
        $query->orderBy('created_at', 'desc');

        $clients = $query->paginate($perPage);

        return ClientResource::collection($clients);
    }

    /**
     * Get single client
     */
    public function show($id)
    {
        $client = User::with('subscription')->findOrFail($id);

        return new ClientResource($client);
    }

    /**
     * Create new client
     */
    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:8',
            'company_name' => 'nullable|string|max:255',
            'country' => 'nullable|string|max:2',
        ]);

        $client = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'company_name' => $request->company_name,
            'country' => $request->country,
            'verified' => 1,
        ]);

        // Trigger webhook
        app(\App\Services\WebhookService::class)->trigger('client.created', [
            'client_id' => $client->id,
            'email' => $client->email,
            'name' => $client->name,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Client created successfully',
            'data' => new ClientResource($client),
        ], 201);
    }

    /**
     * Update client
     */
    public function update(Request $request, $id)
    {
        $client = User::findOrFail($id);

        $request->validate([
            'name' => 'sometimes|required|string|max:255',
            'email' => 'sometimes|required|string|email|max:255|unique:users,email,' . $id,
            'company_name' => 'nullable|string|max:255',
            'country' => 'nullable|string|max:2',
            'password' => 'nullable|string|min:8',
        ]);

        $data = $request->only(['name', 'email', 'company_name', 'country']);

        if ($request->filled('password')) {
            $data['password'] = Hash::make($request->password);
        }

        $client->update($data);

        // Trigger webhook
        app(\App\Services\WebhookService::class)->trigger('client.updated', [
            'client_id' => $client->id,
            'email' => $client->email,
            'name' => $client->name,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Client updated successfully',
            'data' => new ClientResource($client),
        ]);
    }

    /**
     * Delete client
     */
    public function destroy($id)
    {
        $client = User::findOrFail($id);

        // Prevent deletion of admin users
        if ($client->hasRole('admin')) {
            return response()->json([
                'success' => false,
                'error' => 'Cannot delete admin users via API',
            ], 403);
        }

        $client->delete();

        return response()->json([
            'success' => true,
            'message' => 'Client deleted successfully',
        ]);
    }
}
