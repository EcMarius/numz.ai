<?php

namespace App\Console\Commands;

use App\Numz\Services\InvoiceService;
use App\Models\HostingService;
use App\Mail\ServiceSuspended;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;

class SuspendOverdueServices extends Command
{
    protected $signature = 'numz:suspend-overdue {--grace-days=7}';
    protected $description = 'Suspend services with overdue invoices';

    public function handle(InvoiceService $invoiceService)
    {
        $graceDays = $this->option('grace-days');

        $this->info("Suspending services overdue by more than {$graceDays} days...");

        $count = $invoiceService->suspendOverdueServices($graceDays);

        // Send suspension emails
        $suspendedServices = HostingService::where('status', 'suspended')
            ->whereDate('updated_at', today())
            ->with('user')
            ->get();

        foreach ($suspendedServices as $service) {
            try {
                Mail::to($service->user->email)->send(new ServiceSuspended($service));
                $this->info("Sent suspension email for service #{$service->id}");
            } catch (\Exception $e) {
                $this->error("Failed to send email: {$e->getMessage()}");
            }
        }

        $this->info("Suspended {$count} services");

        return 0;
    }
}
