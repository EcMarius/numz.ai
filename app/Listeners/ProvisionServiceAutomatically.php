<?php

namespace App\Listeners;

use App\Events\ServiceCreated;
use App\Models\HostingService;
use Illuminate\Support\Facades\Log;

class ProvisionServiceAutomatically
{
    public function handle(ServiceCreated $event): void
    {
        $service = $event->service;

        // Only provision if service is in pending status and has been paid
        if ($service->status !== 'pending') {
            return;
        }

        try {
            // Get provisioning module
            $product = $service->product;
            $server = $service->server;

            if (!$server || !$product) {
                Log::warning("Cannot provision service {$service->id}: missing server or product");
                return;
            }

            // Check server capacity
            if (!$server->hasCapacity()) {
                Log::error("Cannot provision service {$service->id}: server at capacity");
                $service->update([
                    'status' => 'failed',
                    'notes' => 'Server has reached maximum capacity',
                ]);
                return;
            }

            // Get provisioning module from server
            $module = $server->getProvisioningModule();

            // Create account
            $result = $module->createAccount([
                'domain' => $service->domain,
                'username' => $service->username,
                'password' => $service->password,
                'package' => $product->module_config['package'] ?? 'default',
                'email' => $service->user->email,
            ]);

            if ($result['success'] ?? false) {
                // Update service with returned credentials if different
                $updateData = [
                    'status' => 'active',
                    'activation_date' => now(),
                ];

                // Use credentials returned from API if available
                if (!empty($result['username'])) {
                    $updateData['username'] = $result['username'];
                }
                if (!empty($result['password'])) {
                    $updateData['password'] = $result['password'];
                }

                $service->update($updateData);

                // Increment server account count
                $server->incrementAccounts();

                // Send activation email
                \Mail::to($service->user->email)->send(new \App\Mail\ServiceActivated($service));

                Log::info("Service {$service->id} provisioned successfully", [
                    'server' => $server->name,
                    'username' => $result['username'] ?? $service->username,
                    'domain' => $service->domain,
                ]);
            } else {
                Log::error("Failed to provision service {$service->id}", [
                    'server' => $server->name,
                    'error' => $result['message'] ?? 'Unknown error',
                    'domain' => $service->domain,
                ]);

                // Update service with error status
                $service->update([
                    'status' => 'failed',
                    'notes' => 'Provisioning failed: ' . ($result['message'] ?? 'Unknown error'),
                ]);
            }
        } catch (\Exception $e) {
            Log::error("Exception provisioning service {$service->id}: " . $e->getMessage(), [
                'exception' => get_class($e),
                'trace' => $e->getTraceAsString(),
            ]);

            // Update service with error status
            $service->update([
                'status' => 'failed',
                'notes' => 'Provisioning exception: ' . $e->getMessage(),
            ]);
        }
    }
}
