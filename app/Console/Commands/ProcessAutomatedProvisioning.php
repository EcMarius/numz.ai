<?php

namespace App\Console\Commands;

use App\Models\HostingService;
use App\Numz\Modules\Provisioning\OneProviderProvisioning;
use App\Mail\ServiceActivated;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;

class ProcessAutomatedProvisioning extends Command
{
    protected $signature = 'numz:process-provisioning';
    protected $description = 'Process automated provisioning for pending services';

    public function handle()
    {
        $this->info('Processing automated provisioning...');

        // Get active services in pending status that need provisioning
        $pendingServices = HostingService::where('status', 'pending')
            ->whereHas('product', function ($query) {
                $query->whereNotNull('provisioning_module');
            })
            ->with(['product', 'server', 'user'])
            ->get();

        $successCount = 0;
        $failureCount = 0;

        foreach ($pendingServices as $service) {
            try {
                $this->info("Provisioning service #{$service->id} - {$service->domain}");

                // Get provisioning module
                $module = match($service->product->provisioning_module) {
                    'oneprovider' => new OneProviderProvisioning(),
                    // Add other provisioning modules here
                    default => null,
                };

                if (!$module) {
                    $this->warn("No provisioning module configured for {$service->product->provisioning_module}");
                    continue;
                }

                // Provision the service
                $result = $module->createAccount([
                    'package_id' => $service->product->module_config['package_id'] ?? 1,
                    'domain' => $service->domain,
                    'username' => $this->generateUsername($service->domain),
                    'password' => $this->generatePassword(),
                ]);

                if ($result['success']) {
                    // Update service with account details
                    $service->update([
                        'status' => 'active',
                        'server_account_id' => $result['server_id'] ?? null,
                        'username' => $result['username'] ?? null,
                        'password' => encrypt($result['password'] ?? null),
                    ]);

                    // Send activation email
                    Mail::to($service->user->email)->send(new ServiceActivated($service));

                    $this->info("✓ Service #{$service->id} provisioned successfully");
                    $successCount++;
                } else {
                    $this->error("✗ Provisioning failed: {$result['error']}");
                    $failureCount++;
                }

            } catch (\Exception $e) {
                $this->error("✗ Error provisioning service #{$service->id}: {$e->getMessage()}");
                $failureCount++;
            }
        }

        $this->info("Provisioning completed: {$successCount} successful, {$failureCount} failed");

        return 0;
    }

    protected function generateUsername($domain)
    {
        return substr(str_replace(['.', '-'], '', $domain), 0, 8) . rand(100, 999);
    }

    protected function generatePassword($length = 16)
    {
        return bin2hex(random_bytes($length / 2));
    }
}
