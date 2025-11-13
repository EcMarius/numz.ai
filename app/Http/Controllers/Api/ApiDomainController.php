<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\DomainResource;
use App\Models\DomainRegistration;
use Illuminate\Http\Request;

class ApiDomainController extends Controller
{
    /**
     * Get all domains
     */
    public function index(Request $request)
    {
        $perPage = $request->input('per_page', 15);
        $userId = $request->input('user_id');
        $status = $request->input('status');

        $query = DomainRegistration::with('user');

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

        $domains = $query->paginate($perPage);

        return DomainResource::collection($domains);
    }

    /**
     * Get single domain
     */
    public function show($id)
    {
        $domain = DomainRegistration::with('user')->findOrFail($id);

        return new DomainResource($domain);
    }

    /**
     * Register new domain
     */
    public function register(Request $request)
    {
        $request->validate([
            'user_id' => 'required|exists:users,id',
            'domain_name' => 'required|string|max:255',
            'years' => 'required|integer|min:1|max:10',
            'auto_renew' => 'boolean',
            'privacy_protection' => 'boolean',
        ]);

        // Check if domain already exists
        $exists = DomainRegistration::where('domain_name', $request->domain_name)->exists();

        if ($exists) {
            return response()->json([
                'success' => false,
                'error' => 'Domain already registered',
            ], 400);
        }

        $domain = DomainRegistration::create([
            'user_id' => $request->user_id,
            'domain_name' => $request->domain_name,
            'status' => 'active',
            'registration_date' => now(),
            'expiry_date' => now()->addYears($request->years),
            'auto_renew' => $request->auto_renew ?? false,
            'privacy_protection' => $request->privacy_protection ?? false,
        ]);

        // Trigger webhook
        app(\App\Services\WebhookService::class)->trigger('domain.registered', [
            'domain_id' => $domain->id,
            'domain_name' => $domain->domain_name,
            'user_id' => $domain->user_id,
            'expiry_date' => $domain->expiry_date->toIso8601String(),
        ], $domain->user_id);

        return response()->json([
            'success' => true,
            'message' => 'Domain registered successfully',
            'data' => new DomainResource($domain->load('user')),
        ], 201);
    }

    /**
     * Renew domain
     */
    public function renew(Request $request, $id)
    {
        $domain = DomainRegistration::findOrFail($id);

        $request->validate([
            'years' => 'required|integer|min:1|max:10',
        ]);

        $domain->update([
            'expiry_date' => $domain->expiry_date->addYears($request->years),
        ]);

        // Trigger webhook
        app(\App\Services\WebhookService::class)->trigger('domain.renewed', [
            'domain_id' => $domain->id,
            'domain_name' => $domain->domain_name,
            'user_id' => $domain->user_id,
            'new_expiry_date' => $domain->expiry_date->toIso8601String(),
            'years' => $request->years,
        ], $domain->user_id);

        return response()->json([
            'success' => true,
            'message' => 'Domain renewed successfully',
            'data' => new DomainResource($domain),
        ]);
    }

    /**
     * Transfer domain
     */
    public function transfer(Request $request, $id)
    {
        $domain = DomainRegistration::findOrFail($id);

        $request->validate([
            'new_user_id' => 'required|exists:users,id',
        ]);

        $oldUserId = $domain->user_id;
        $domain->update(['user_id' => $request->new_user_id]);

        // Trigger webhook
        app(\App\Services\WebhookService::class)->trigger('domain.transferred', [
            'domain_id' => $domain->id,
            'domain_name' => $domain->domain_name,
            'old_user_id' => $oldUserId,
            'new_user_id' => $request->new_user_id,
        ], $request->new_user_id);

        return response()->json([
            'success' => true,
            'message' => 'Domain transferred successfully',
            'data' => new DomainResource($domain->load('user')),
        ]);
    }

    /**
     * Toggle auto-renew
     */
    public function toggleAutoRenew($id)
    {
        $domain = DomainRegistration::findOrFail($id);

        $domain->update([
            'auto_renew' => !$domain->auto_renew,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Auto-renew setting updated',
            'data' => new DomainResource($domain),
        ]);
    }
}
