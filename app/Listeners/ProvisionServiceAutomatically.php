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

            $moduleName = $server->type; // cpanel, plesk, directadmin, oneprovider
            $moduleClass = "App\\Numz\\Modules\\Provisioning\\" . ucfirst($moduleName) . "Provisioning";

            if (!class_exists($moduleClass)) {
                Log::error("Provisioning module not found: {$moduleClass}");
                return;
            }

            $module = new $moduleClass($server);

            // Create account
            $result = $module->createAccount(
                $service->domain,
                $service->username,
                $service->password,
                $product->module_config['package'] ?? 'default'
            );

            if ($result['success'] ?? false) {
                $service->update([
                    'status' => 'active',
                    'activation_date' => now(),
                ]);

                // Send activation email
                \Mail::to($service->user->email)->send(new \App\Mail\ServiceActivated($service));

                Log::info("Service {$service->id} provisioned successfully");
            } else {
                Log::error("Failed to provision service {$service->id}: " . ($result['error'] ?? 'Unknown error'));
            }
        } catch (\Exception $e) {
            Log::error("Exception provisioning service {$service->id}: " . $e->getMessage());
        }
    }
}
