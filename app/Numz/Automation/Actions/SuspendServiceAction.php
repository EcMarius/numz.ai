<?php

namespace App\Numz\Automation\Actions;

use App\Models\HostingService;
use Illuminate\Support\Facades\Log;

class SuspendServiceAction implements ActionInterface
{
    public function execute(array $params, array $data): array
    {
        try {
            $serviceId = $params['service_id'] ?? $data['service_id'] ?? null;
            $reason = $params['reason'] ?? 'Automated suspension';

            if (!$serviceId) {
                throw new \Exception('Service ID is required');
            }

            $service = HostingService::find($serviceId);

            if (!$service) {
                throw new \Exception("Service with ID {$serviceId} not found");
            }

            if ($service->status === 'suspended') {
                return [
                    'success' => true,
                    'action' => 'suspend_service',
                    'message' => "Service {$serviceId} is already suspended",
                ];
            }

            // Suspend the service
            $service->update([
                'status' => 'suspended',
                'suspension_reason' => $reason,
                'suspended_at' => now(),
            ]);

            Log::info("Service suspended by automation", [
                'service_id' => $serviceId,
                'reason' => $reason,
            ]);

            // TODO: Trigger provisioning module to actually suspend on the server
            // This would call the appropriate provisioning module's suspend method

            return [
                'success' => true,
                'action' => 'suspend_service',
                'message' => "Service {$serviceId} has been suspended",
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'action' => 'suspend_service',
                'error' => $e->getMessage(),
            ];
        }
    }

    public function getName(): string
    {
        return 'Suspend Service';
    }

    public function getDescription(): string
    {
        return 'Suspend a hosting service';
    }

    public function getRequiredParams(): array
    {
        return [
            'service_id' => 'Service ID to suspend',
            'reason' => 'Suspension reason (optional)',
        ];
    }
}
