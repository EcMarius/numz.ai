<?php

namespace App\Console\Commands;

use App\Models\Invoice;
use App\Models\HostingService;
use App\Mail\ServiceTerminated;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;

class TerminateOverdueServices extends Command
{
    protected $signature = 'numz:terminate-services {--grace-days=30}';
    protected $description = 'Terminate services with invoices overdue beyond grace period';

    public function handle()
    {
        $graceDays = $this->option('grace-days');
        $cutoffDate = now()->subDays($graceDays);

        $this->info("Terminating services with invoices overdue by more than {$graceDays} days...");

        $overdueInvoices = Invoice::where('status', 'unpaid')
            ->where('due_date', '<', $cutoffDate)
            ->with(['items', 'user'])
            ->get();

        $count = 0;

        foreach ($overdueInvoices as $invoice) {
            foreach ($invoice->items as $item) {
                if ($item->item_type === 'service') {
                    $service = HostingService::find($item->item_id);

                    if ($service && in_array($service->status, ['active', 'suspended'])) {
                        try {
                            // Terminate service on server if provisioned
                            if ($service->server && $service->username) {
                                $provisioning = $service->server->getProvisioningModule();
                                if ($provisioning) {
                                    $provisioning->terminate($service);
                                }
                            }

                            $service->update([
                                'status' => 'terminated',
                                'terminated_at' => now(),
                            ]);

                            // Send termination email
                            Mail::to($invoice->user->email)->send(new ServiceTerminated($service, $invoice));

                            $this->info("Terminated service #{$service->id} for invoice {$invoice->invoice_number}");
                            $count++;

                            // Log the action
                            Log::info("Service terminated", [
                                'service_id' => $service->id,
                                'invoice_id' => $invoice->id,
                                'days_overdue' => now()->diffInDays($invoice->due_date),
                            ]);
                        } catch (\Exception $e) {
                            $this->error("Failed to terminate service #{$service->id}: {$e->getMessage()}");
                            Log::error("Service termination failed", [
                                'service_id' => $service->id,
                                'error' => $e->getMessage(),
                            ]);
                        }
                    }
                }
            }
        }

        $this->info("Terminated {$count} services");

        return 0;
    }
}
