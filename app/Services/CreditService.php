<?php

namespace App\Services;

use App\Models\CreditBalance;
use App\Models\CreditPackage;
use App\Models\CreditPackagePurchase;
use App\Models\CreditTransaction;
use App\Models\Invoice;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class CreditService
{
    /**
     * Get or create credit balance for user
     */
    public function getBalance(User $user): CreditBalance
    {
        return CreditBalance::firstOrCreate(
            ['user_id' => $user->id],
            [
                'balance' => 0,
                'total_earned' => 0,
                'total_spent' => 0,
                'total_purchased' => 0,
            ]
        );
    }

    /**
     * Add credits to user's account
     */
    public function addCredits(
        User $user,
        float $amount,
        string $type,
        string $description,
        array $options = []
    ): CreditTransaction {
        $balance = $this->getBalance($user);

        $transaction = $balance->addCredits($amount, $type, $description, [
            'invoice_id' => $options['invoice_id'] ?? null,
            'payment_transaction_id' => $options['payment_transaction_id'] ?? null,
            'reference_type' => $options['reference_type'] ?? null,
            'reference_id' => $options['reference_id'] ?? null,
            'admin_id' => $options['admin_id'] ?? null,
            'admin_notes' => $options['admin_notes'] ?? null,
        ]);

        return $transaction;
    }

    /**
     * Deduct credits from user's account
     */
    public function deductCredits(
        User $user,
        float $amount,
        string $type,
        string $description,
        array $options = []
    ): CreditTransaction {
        $balance = $this->getBalance($user);

        if (!$balance->hasSufficientCredits($amount)) {
            throw new \Exception('Insufficient credit balance');
        }

        $transaction = $balance->deductCredits($amount, $type, $description, [
            'invoice_id' => $options['invoice_id'] ?? null,
            'payment_transaction_id' => $options['payment_transaction_id'] ?? null,
            'reference_type' => $options['reference_type'] ?? null,
            'reference_id' => $options['reference_id'] ?? null,
        ]);

        return $transaction;
    }

    /**
     * Apply credits to invoice payment
     */
    public function applyToInvoice(User $user, Invoice $invoice, float $amount): array
    {
        DB::beginTransaction();

        try {
            $balance = $this->getBalance($user);

            if (!$balance->hasSufficientCredits($amount)) {
                throw new \Exception('Insufficient credit balance');
            }

            // Don't exceed invoice total
            $maxApplicable = min($amount, $invoice->total - $invoice->amount_paid);

            if ($maxApplicable <= 0) {
                throw new \Exception('Invoice is already fully paid');
            }

            // Deduct credits
            $transaction = $this->deductCredits(
                $user,
                $maxApplicable,
                'payment',
                "Payment for invoice #{$invoice->invoice_number}",
                ['invoice_id' => $invoice->id]
            );

            // Update invoice
            $invoice->amount_paid += $maxApplicable;

            if ($invoice->amount_paid >= $invoice->total) {
                $invoice->status = 'paid';
                $invoice->paid_date = now();
            }

            $invoice->save();

            DB::commit();

            return [
                'success' => true,
                'applied_amount' => $maxApplicable,
                'remaining_balance' => $balance->fresh()->balance,
                'transaction' => $transaction,
                'invoice_status' => $invoice->status,
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
     * Purchase credit package
     */
    public function purchasePackage(
        User $user,
        CreditPackage $package,
        ?int $paymentTransactionId = null
    ): CreditPackagePurchase {
        DB::beginTransaction();

        try {
            if (!$package->canBePurchasedBy($user)) {
                throw new \Exception('You cannot purchase this package');
            }

            // Create purchase record
            $purchase = CreditPackagePurchase::create([
                'user_id' => $user->id,
                'credit_package_id' => $package->id,
                'payment_transaction_id' => $paymentTransactionId,
                'price_paid' => $package->price,
                'credits_received' => $package->credit_amount,
                'bonus_credits' => $package->bonus_credits,
                'status' => $paymentTransactionId ? 'completed' : 'pending',
            ]);

            // If payment completed, add credits immediately
            if ($paymentTransactionId) {
                $this->completePurchase($purchase);
            }

            DB::commit();

            return $purchase;
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Complete credit package purchase
     */
    public function completePurchase(CreditPackagePurchase $purchase): void
    {
        DB::beginTransaction();

        try {
            // Add base credits
            $this->addCredits(
                $purchase->user,
                $purchase->credits_received,
                'purchase',
                "Purchased {$purchase->creditPackage->name}",
                [
                    'reference_type' => CreditPackagePurchase::class,
                    'reference_id' => $purchase->id,
                    'payment_transaction_id' => $purchase->payment_transaction_id,
                ]
            );

            // Add bonus credits if any
            if ($purchase->bonus_credits > 0) {
                $this->addCredits(
                    $purchase->user,
                    $purchase->bonus_credits,
                    'bonus',
                    "Bonus credits from {$purchase->creditPackage->name}",
                    [
                        'reference_type' => CreditPackagePurchase::class,
                        'reference_id' => $purchase->id,
                    ]
                );
            }

            $purchase->markCompleted();

            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Admin grant credits
     */
    public function adminGrant(
        User $user,
        float $amount,
        User $admin,
        string $reason
    ): CreditTransaction {
        return $this->addCredits(
            $user,
            $amount,
            'grant',
            $reason,
            [
                'admin_id' => $admin->id,
                'admin_notes' => $reason,
            ]
        );
    }

    /**
     * Admin adjustment (can be positive or negative)
     */
    public function adminAdjustment(
        User $user,
        float $amount,
        User $admin,
        string $reason
    ): CreditTransaction {
        if ($amount > 0) {
            return $this->addCredits(
                $user,
                $amount,
                'adjustment',
                $reason,
                [
                    'admin_id' => $admin->id,
                    'admin_notes' => $reason,
                ]
            );
        } else {
            return $this->deductCredits(
                $user,
                abs($amount),
                'adjustment',
                $reason,
                [
                    'admin_id' => $admin->id,
                    'admin_notes' => $reason,
                ]
            );
        }
    }

    /**
     * Refund credits
     */
    public function refund(
        User $user,
        float $amount,
        string $reason,
        ?int $invoiceId = null
    ): CreditTransaction {
        return $this->addCredits(
            $user,
            $amount,
            'refund',
            $reason,
            ['invoice_id' => $invoiceId]
        );
    }

    /**
     * Get user's transaction history
     */
    public function getTransactionHistory(User $user, int $limit = 50)
    {
        return CreditTransaction::where('user_id', $user->id)
            ->with(['invoice', 'paymentTransaction', 'admin'])
            ->orderBy('created_at', 'desc')
            ->limit($limit)
            ->get();
    }

    /**
     * Get available credit packages
     */
    public function getAvailablePackages(User $user)
    {
        return CreditPackage::active()
            ->ordered()
            ->get()
            ->filter(function ($package) use ($user) {
                return $package->canBePurchasedBy($user);
            });
    }

    /**
     * Calculate partial payment split
     */
    public function calculatePartialPayment(float $invoiceTotal, float $userBalance): array
    {
        $creditsToApply = min($userBalance, $invoiceTotal);
        $remainingToPay = max(0, $invoiceTotal - $creditsToApply);

        return [
            'credits_to_apply' => round($creditsToApply, 2),
            'remaining_to_pay' => round($remainingToPay, 2),
            'can_pay_fully_with_credits' => $userBalance >= $invoiceTotal,
        ];
    }

    /**
     * Get credit statistics for user
     */
    public function getUserStats(User $user): array
    {
        $balance = $this->getBalance($user);
        $transactions = CreditTransaction::where('user_id', $user->id);

        return [
            'current_balance' => $balance->balance,
            'total_earned' => $balance->total_earned,
            'total_spent' => $balance->total_spent,
            'total_purchased' => $balance->total_purchased,
            'total_transactions' => $transactions->count(),
            'last_transaction' => $transactions->latest()->first(),
            'packages_purchased' => CreditPackagePurchase::where('user_id', $user->id)
                ->where('status', 'completed')
                ->count(),
        ];
    }
}
