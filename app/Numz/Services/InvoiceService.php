<?php

namespace App\Numz\Services;

use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\HostingService;
use App\Models\DomainRegistration;
use App\Models\User;

class InvoiceService
{
    /**
     * Generate invoice for a hosting service
     */
    public function generateServiceInvoice(HostingService $service): Invoice
    {
        $invoice = Invoice::create([
            'user_id' => $service->user_id,
            'invoice_number' => Invoice::generateInvoiceNumber(),
            'status' => 'unpaid',
            'subtotal' => 0,
            'tax' => 0,
            'total' => 0,
            'currency' => config('numz.currency', 'USD'),
            'due_date' => $service->next_due_date,
        ]);

        $invoice->addItem(
            description: $service->product->name . ' - ' . $service->domain,
            amount: $service->price,
            quantity: 1,
            itemType: 'service',
            itemId: $service->id
        );

        $invoice->calculateTotals();

        return $invoice;
    }

    /**
     * Generate invoice for a domain registration
     */
    public function generateDomainInvoice(DomainRegistration $domain, int $years = 1): Invoice
    {
        $invoice = Invoice::create([
            'user_id' => $domain->user_id,
            'invoice_number' => Invoice::generateInvoiceNumber(),
            'status' => 'unpaid',
            'subtotal' => 0,
            'tax' => 0,
            'total' => 0,
            'currency' => config('numz.currency', 'USD'),
            'due_date' => now(),
        ]);

        $invoice->addItem(
            description: "Domain Registration - {$domain->domain} ({$years} year" . ($years > 1 ? 's' : '') . ")",
            amount: $domain->renewal_price,
            quantity: $years,
            itemType: 'domain',
            itemId: $domain->id
        );

        $invoice->calculateTotals();

        return $invoice;
    }

    /**
     * Generate invoices for all services due for renewal
     */
    public function generateRenewalInvoices(): array
    {
        $invoices = [];

        // Services due for renewal
        $services = HostingService::where('status', 'active')
            ->where('next_due_date', '<=', now())
            ->whereDoesntHave('invoices', function ($query) {
                $query->where('status', 'unpaid')
                    ->where('due_date', '>=', now()->subDays(30));
            })
            ->get();

        foreach ($services as $service) {
            $invoices[] = $this->generateServiceInvoice($service);
        }

        // Domains due for renewal (30 days before expiry)
        $domains = DomainRegistration::where('status', 'active')
            ->where('auto_renew', true)
            ->where('expiry_date', '<=', now()->addDays(30))
            ->where('expiry_date', '>', now())
            ->get();

        foreach ($domains as $domain) {
            $invoices[] = $this->generateDomainInvoice($domain);
        }

        return $invoices;
    }

    /**
     * Mark invoice as paid and activate related services
     */
    public function markInvoiceAsPaid(Invoice $invoice, string $paymentMethod, string $transactionId = null): void
    {
        $invoice->markAsPaid($paymentMethod, $transactionId);

        // Activate or extend services
        foreach ($invoice->items as $item) {
            if ($item->item_type === 'service') {
                $service = HostingService::find($item->item_id);
                if ($service) {
                    if ($service->status === 'pending' || $service->status === 'suspended') {
                        $service->update(['status' => 'active']);
                    }

                    // Extend next due date
                    $service->update([
                        'next_due_date' => $this->calculateNextDueDate(
                            $service->next_due_date,
                            $service->billing_cycle
                        ),
                    ]);
                }
            } elseif ($item->item_type === 'domain') {
                $domain = DomainRegistration::find($item->item_id);
                if ($domain) {
                    $domain->update([
                        'expiry_date' => $domain->expiry_date->addYears($item->quantity),
                        'status' => 'active',
                    ]);
                }
            }
        }
    }

    /**
     * Calculate next due date based on billing cycle
     */
    protected function calculateNextDueDate($currentDate, string $billingCycle)
    {
        $date = \Carbon\Carbon::parse($currentDate);

        return match ($billingCycle) {
            'monthly' => $date->addMonth(),
            'quarterly' => $date->addMonths(3),
            'semi_annually' => $date->addMonths(6),
            'annually' => $date->addYear(),
            'biennially' => $date->addYears(2),
            'triennially' => $date->addYears(3),
            default => $date->addMonth(),
        };
    }

    /**
     * Send overdue notices for unpaid invoices
     */
    public function sendOverdueNotices(): int
    {
        $overdueInvoices = Invoice::where('status', 'unpaid')
            ->where('due_date', '<', now())
            ->get();

        $count = 0;

        foreach ($overdueInvoices as $invoice) {
            // TODO: Send email notification
            // Mail::to($invoice->user)->send(new OverdueInvoiceNotification($invoice));
            $count++;
        }

        return $count;
    }

    /**
     * Suspend services with overdue invoices
     */
    public function suspendOverdueServices(int $gracePeriodDays = 7): int
    {
        $cutoffDate = now()->subDays($gracePeriodDays);

        $overdueInvoices = Invoice::where('status', 'unpaid')
            ->where('due_date', '<', $cutoffDate)
            ->get();

        $count = 0;

        foreach ($overdueInvoices as $invoice) {
            foreach ($invoice->items as $item) {
                if ($item->item_type === 'service') {
                    $service = HostingService::find($item->item_id);
                    if ($service && $service->status === 'active') {
                        $service->update(['status' => 'suspended']);
                        $count++;
                    }
                }
            }
        }

        return $count;
    }
}
