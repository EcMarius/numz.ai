<?php

namespace App\Numz\Services;

use App\Models\User;
use App\Models\HostingService;
use App\Models\PaymentTransaction;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class BillingService
{
    /**
     * Generate invoice for a service
     */
    public function generateInvoice(HostingService $service): array
    {
        try {
            $invoice = [
                'user_id' => $service->user_id,
                'service_id' => $service->id,
                'amount' => $service->price,
                'due_date' => $service->next_due_date,
                'status' => 'unpaid',
            ];

            // In a real implementation, this would create an invoice record
            return [
                'success' => true,
                'invoice' => $invoice,
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Process payment for a service
     */
    public function processPayment(User $user, HostingService $service, string $gateway, array $paymentData): array
    {
        DB::beginTransaction();

        try {
            // Create payment transaction
            $transaction = PaymentTransaction::create([
                'user_id' => $user->id,
                'transaction_id' => uniqid('txn_'),
                'gateway' => $gateway,
                'amount' => $service->price,
                'currency' => config('numz.billing.currency', 'USD'),
                'status' => 'pending',
                'description' => "Payment for {$service->domain}",
                'metadata' => $paymentData,
            ]);

            // Update service status if payment successful
            if ($paymentData['success'] ?? false) {
                $service->update([
                    'status' => 'active',
                    'next_due_date' => $this->calculateNextDueDate($service->billing_cycle),
                    'activated_at' => now(),
                ]);

                $transaction->update([
                    'status' => 'completed',
                    'completed_at' => now(),
                ]);
            }

            DB::commit();

            return [
                'success' => true,
                'transaction' => $transaction,
            ];
        } catch (\Exception $e) {
            DB::rollBack();
            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Calculate next due date based on billing cycle
     */
    protected function calculateNextDueDate(string $cycle): Carbon
    {
        return match($cycle) {
            'monthly' => now()->addMonth(),
            'yearly' => now()->addYear(),
            default => now()->addMonth(),
        };
    }

    /**
     * Suspend overdue services
     */
    public function suspendOverdueServices(): int
    {
        $suspendAfterDays = config('numz.billing.auto_suspend_days', 7);
        $suspendDate = now()->subDays($suspendAfterDays);

        $services = HostingService::where('status', 'active')
            ->where('next_due_date', '<', $suspendDate)
            ->get();

        $suspended = 0;
        foreach ($services as $service) {
            $service->update([
                'status' => 'suspended',
                'suspended_at' => now(),
            ]);
            $suspended++;
        }

        return $suspended;
    }

    /**
     * Terminate long-overdue services
     */
    public function terminateOverdueServices(): int
    {
        $terminateAfterDays = config('numz.billing.auto_terminate_days', 30);
        $terminateDate = now()->subDays($terminateAfterDays);

        $services = HostingService::where('status', 'suspended')
            ->where('suspended_at', '<', $terminateDate)
            ->get();

        $terminated = 0;
        foreach ($services as $service) {
            $service->update([
                'status' => 'terminated',
                'terminated_at' => now(),
            ]);
            $terminated++;
        }

        return $terminated;
    }
}
