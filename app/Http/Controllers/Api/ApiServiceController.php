<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\ServiceResource;
use App\Models\HostingService;
use App\Models\HostingProduct;
use Illuminate\Http\Request;

class ApiServiceController extends Controller
{
    /**
     * Get all services
     */
    public function index(Request $request)
    {
        $perPage = $request->input('per_page', 15);
        $userId = $request->input('user_id');
        $status = $request->input('status');

        $query = HostingService::with(['product', 'server', 'user']);

        // Filter by user
        if ($userId) {
            $query->where('user_id', $userId);
        }

        // Filter by status
        if ($status) {
            $query->where('status', $status);
        }

        // Order by latest
        $query->orderBy('created_at', 'desc');

        $services = $query->paginate($perPage);

        return ServiceResource::collection($services);
    }

    /**
     * Get single service
     */
    public function show($id)
    {
        $service = HostingService::with(['product', 'server', 'user'])->findOrFail($id);

        return new ServiceResource($service);
    }

    /**
     * Create new service
     */
    public function store(Request $request)
    {
        $request->validate([
            'user_id' => 'required|exists:users,id',
            'hosting_product_id' => 'required|exists:hosting_products,id',
            'domain' => 'required|string|max:255',
            'billing_cycle' => 'required|in:monthly,quarterly,semi_annually,annually,biennially,triennially',
            'price' => 'required|numeric|min:0',
        ]);

        $service = HostingService::create([
            'user_id' => $request->user_id,
            'hosting_product_id' => $request->hosting_product_id,
            'domain' => $request->domain,
            'billing_cycle' => $request->billing_cycle,
            'price' => $request->price,
            'status' => 'pending',
            'next_due_date' => now()->addMonth(),
        ]);

        // Trigger webhook
        app(\App\Services\WebhookService::class)->trigger('service.created', [
            'service_id' => $service->id,
            'user_id' => $service->user_id,
            'domain' => $service->domain,
            'status' => $service->status,
        ], $service->user_id);

        return response()->json([
            'success' => true,
            'message' => 'Service created successfully',
            'data' => new ServiceResource($service->load(['product', 'user'])),
        ], 201);
    }

    /**
     * Activate service
     */
    public function activate($id)
    {
        $service = HostingService::findOrFail($id);

        $service->update([
            'status' => 'active',
            'activated_at' => now(),
        ]);

        // Trigger webhook
        app(\App\Services\WebhookService::class)->trigger('service.activated', [
            'service_id' => $service->id,
            'user_id' => $service->user_id,
            'domain' => $service->domain,
        ], $service->user_id);

        return response()->json([
            'success' => true,
            'message' => 'Service activated successfully',
            'data' => new ServiceResource($service),
        ]);
    }

    /**
     * Suspend service
     */
    public function suspend(Request $request, $id)
    {
        $service = HostingService::findOrFail($id);

        $request->validate([
            'reason' => 'nullable|string',
        ]);

        $service->update([
            'status' => 'suspended',
            'suspended_at' => now(),
        ]);

        // Trigger webhook
        app(\App\Services\WebhookService::class)->trigger('service.suspended', [
            'service_id' => $service->id,
            'user_id' => $service->user_id,
            'domain' => $service->domain,
            'reason' => $request->reason,
        ], $service->user_id);

        return response()->json([
            'success' => true,
            'message' => 'Service suspended successfully',
            'data' => new ServiceResource($service),
        ]);
    }

    /**
     * Terminate service
     */
    public function terminate(Request $request, $id)
    {
        $service = HostingService::findOrFail($id);

        $request->validate([
            'reason' => 'nullable|string',
        ]);

        $service->update([
            'status' => 'terminated',
            'terminated_at' => now(),
        ]);

        // Trigger webhook
        app(\App\Services\WebhookService::class)->trigger('service.terminated', [
            'service_id' => $service->id,
            'user_id' => $service->user_id,
            'domain' => $service->domain,
            'reason' => $request->reason,
        ], $service->user_id);

        return response()->json([
            'success' => true,
            'message' => 'Service terminated successfully',
            'data' => new ServiceResource($service),
        ]);
    }

    /**
     * Upgrade service
     */
    public function upgrade(Request $request, $id)
    {
        $service = HostingService::findOrFail($id);

        $request->validate([
            'hosting_product_id' => 'required|exists:hosting_products,id',
            'billing_cycle' => 'required|in:monthly,quarterly,semi_annually,annually,biennially,triennially',
        ]);

        $newProduct = HostingProduct::findOrFail($request->hosting_product_id);

        $service->update([
            'hosting_product_id' => $request->hosting_product_id,
            'billing_cycle' => $request->billing_cycle,
        ]);

        // Trigger webhook
        app(\App\Services\WebhookService::class)->trigger('service.upgraded', [
            'service_id' => $service->id,
            'user_id' => $service->user_id,
            'domain' => $service->domain,
            'new_product_id' => $newProduct->id,
            'new_product_name' => $newProduct->name,
        ], $service->user_id);

        return response()->json([
            'success' => true,
            'message' => 'Service upgraded successfully',
            'data' => new ServiceResource($service->load('product')),
        ]);
    }
}
