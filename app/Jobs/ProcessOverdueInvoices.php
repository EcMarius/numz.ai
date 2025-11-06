<?php

namespace App\Jobs;

use App\Models\Invoice;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class ProcessOverdueInvoices implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function handle(): void
    {
        $overdueInvoices = Invoice::overdue()->get();

        foreach ($overdueInvoices as $invoice) {
            $this->processOverdueInvoice($invoice);
        }

        Log::info("Processed overdue invoices", [
            'count' => $overdueInvoices->count(),
        ]);
    }

    private function processOverdueInvoice(Invoice $invoice): void
    {
        $daysOverdue = now()->diffInDays($invoice->due_date);
        $lateFeePercentage = config('numz.billing.late_fee_percentage', 5);
        $lateFeeAmount = config('numz.billing.late_fee_amount', null);

        // Calculate and apply late fee if configured
        if ($lateFeePercentage && $invoice->late_fee == 0) {
            $fee = $invoice->total * ($lateFeePercentage / 100);
            $invoice->addLateFee($fee);

            Log::info("Late fee applied to invoice", [
                'invoice_id' => $invoice->id,
                'invoice_number' => $invoice->invoice_number,
                'late_fee' => $fee,
            ]);
        } elseif ($lateFeeAmount && $invoice->late_fee == 0) {
            $invoice->addLateFee($lateFeeAmount);

            Log::info("Late fee applied to invoice", [
                'invoice_id' => $invoice->id,
                'invoice_number' => $invoice->invoice_number,
                'late_fee' => $lateFeeAmount,
            ]);
        }

        // Check if service should be suspended
        $autoSuspendDays = config('numz.billing.auto_suspend_days', 7);
        if ($daysOverdue >= $autoSuspendDays) {
            $this->suspendServices($invoice);
        }

        // Check if service should be terminated
        $autoTerminateDays = config('numz.billing.auto_terminate_days', 30);
        if ($daysOverdue >= $autoTerminateDays) {
            $this->terminateServices($invoice);
        }
    }

    private function suspendServices(Invoice $invoice): void
    {
        foreach ($invoice->items as $item) {
            if ($item->item_type === 'service' && $item->item_id) {
                $service = $item->item();
                if ($service && $service->status === 'active') {
                    $service->suspend('Overdue invoice: ' . $invoice->invoice_number);

                    Log::info("Service suspended due to overdue invoice", [
                        'service_id' => $service->id,
                        'invoice_id' => $invoice->id,
                        'invoice_number' => $invoice->invoice_number,
                    ]);
                }
            }
        }
    }

    private function terminateServices(Invoice $invoice): void
    {
        foreach ($invoice->items as $item) {
            if ($item->item_type === 'service' && $item->item_id) {
                $service = $item->item();
                if ($service && in_array($service->status, ['active', 'suspended'])) {
                    $service->terminate('Overdue invoice: ' . $invoice->invoice_number);

                    Log::info("Service terminated due to overdue invoice", [
                        'service_id' => $service->id,
                        'invoice_id' => $invoice->id,
                        'invoice_number' => $invoice->invoice_number,
                    ]);
                }
            }
        }
    }
}
