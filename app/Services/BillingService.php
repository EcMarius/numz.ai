<?php

namespace App\Services;

use App\Models\Client;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\Service;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class BillingService
{
    /**
     * Generate recurring invoices for all active services
     */
    public function generateRecurringInvoices()
    {
        $services = Service::where('status', 'active')
            ->where('next_invoice_date', '<=', now())
            ->get();

        $invoicesCreated = 0;

        foreach ($services as $service) {
            $invoice = $this->createInvoiceForService($service);
            if ($invoice) {
                $invoicesCreated++;
            }
        }

        return $invoicesCreated;
    }

    /**
     * Create an invoice for a service
     */
    public function createInvoiceForService(Service $service): ?Invoice
    {
        DB::beginTransaction();

        try {
            $invoice = Invoice::create([
                'client_id' => $service->client_id,
                'invoice_number' => $this->generateInvoiceNumber(),
                'date' => now(),
                'due_date' => now()->addDays(14),
                'status' => 'unpaid',
            ]);

            $invoice->items()->create([
                'service_id' => $service->id,
                'type' => 'hosting',
                'description' => $service->product->name . ' - ' . $service->domain,
                'amount' => $service->amount,
                'taxed' => true,
            ]);

            $this->calculateInvoiceTotals($invoice);

            // Update service next invoice date
            $service->update([
                'next_invoice_date' => $this->calculateNextInvoiceDate($service->billing_cycle),
            ]);

            DB::commit();

            return $invoice;
        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error('Failed to create invoice for service: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Calculate invoice totals including tax
     */
    public function calculateInvoiceTotals(Invoice $invoice): void
    {
        $subtotal = $invoice->items()->sum('amount');
        $taxRate = 0; // Get from system settings
        $tax = $subtotal * ($taxRate / 100);

        $invoice->update([
            'subtotal' => $subtotal,
            'tax' => $tax,
            'total' => $subtotal + $tax,
        ]);
    }

    /**
     * Process payment for an invoice
     */
    public function processPayment(Invoice $invoice, string $gateway, string $transactionId, float $amount): bool
    {
        DB::beginTransaction();

        try {
            $invoice->update([
                'status' => 'paid',
                'date_paid' => now(),
                'payment_method' => $gateway,
            ]);

            $invoice->transactions()->create([
                'client_id' => $invoice->client_id,
                'transaction_id' => $transactionId,
                'gateway' => $gateway,
                'date' => now(),
                'amount_in' => $amount,
                'status' => 'success',
                'description' => "Payment for invoice #{$invoice->invoice_number}",
            ]);

            // Activate any pending services on this invoice
            foreach ($invoice->items as $item) {
                if ($item->service && $item->service->status === 'pending') {
                    $item->service->update(['status' => 'active']);
                }
            }

            DB::commit();

            return true;
        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error('Failed to process payment: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Generate unique invoice number
     */
    protected function generateInvoiceNumber(): string
    {
        $lastInvoice = Invoice::orderBy('id', 'desc')->first();
        $nextNumber = $lastInvoice ? $lastInvoice->id + 1 : 1;
        
        return 'INV-' . date('Y') . '-' . str_pad($nextNumber, 6, '0', STR_PAD_LEFT);
    }

    /**
     * Calculate next invoice date based on billing cycle
     */
    protected function calculateNextInvoiceDate(string $cycle): Carbon
    {
        return match($cycle) {
            'monthly' => now()->addMonth(),
            'quarterly' => now()->addMonths(3),
            'semiannually' => now()->addMonths(6),
            'annually' => now()->addYear(),
            'biennially' => now()->addYears(2),
            'triennially' => now()->addYears(3),
            default => now()->addMonth(),
        };
    }

    /**
     * Apply credit to client account
     */
    public function applyCredit(Client $client, float $amount): void
    {
        $client->increment('credit', $amount);
    }

    /**
     * Suspend overdue services
     */
    public function suspendOverdueServices(): int
    {
        $overdueInvoices = Invoice::where('status', 'unpaid')
            ->where('due_date', '<', now()->subDays(7))
            ->get();

        $suspended = 0;

        foreach ($overdueInvoices as $invoice) {
            foreach ($invoice->items as $item) {
                if ($item->service && $item->service->status === 'active') {
                    $item->service->update(['status' => 'suspended']);
                    $suspended++;
                }
            }
        }

        return $suspended;
    }

    /**
     * Calculate prorata price for service
     */
    public function calculateProrata(float $basePrice, Carbon $startDate, Carbon $endDate, string $cycle): float
    {
        $totalDays = $startDate->diffInDays($endDate);
        $cycleDays = match($cycle) {
            'monthly' => 30,
            'quarterly' => 90,
            'semiannually' => 180,
            'annually' => 365,
            default => 30,
        };

        return ($basePrice / $cycleDays) * $totalDays;
    }
}
