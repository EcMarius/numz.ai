<?php

namespace App\Services;

use App\Models\ActivityLog;
use App\Models\ConsentLog;
use App\Models\DataDeletionRequest;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use ZipArchive;

class GdprService
{
    /**
     * Export all user data (Right to Access)
     */
    public function exportUserData(User $user): string
    {
        $data = [
            'exported_at' => now()->toDateTimeString(),
            'user_information' => $this->getUserInformation($user),
            'subscriptions' => $this->getSubscriptions($user),
            'transactions' => $this->getTransactions($user),
            'orders' => $this->getOrders($user),
            'invoices' => $this->getInvoices($user),
            'support_tickets' => $this->getSupportTickets($user),
            'activity_logs' => $this->getActivityLogs($user),
            'consent_logs' => $this->getConsentLogs($user),
        ];

        // Create a unique filename
        $filename = 'user_data_export_' . $user->id . '_' . time() . '.json';
        $path = 'exports/' . $filename;

        // Store the export
        Storage::put($path, json_encode($data, JSON_PRETTY_PRINT));

        // Log the export
        app(ActivityLogger::class)->log(
            ActivityLogger::TYPE_EXPORT,
            'User data exported',
            $user,
            User::class,
            $user->id,
            ['filename' => $filename]
        );

        return Storage::path($path);
    }

    /**
     * Export user data as ZIP
     */
    public function exportUserDataAsZip(User $user): string
    {
        $jsonPath = $this->exportUserData($user);
        $zipPath = str_replace('.json', '.zip', $jsonPath);

        $zip = new ZipArchive();
        if ($zip->open($zipPath, ZipArchive::CREATE) === true) {
            $zip->addFile($jsonPath, basename($jsonPath));

            // Add user avatar if exists
            if ($user->avatar && Storage::exists($user->avatar)) {
                $zip->addFile(Storage::path($user->avatar), 'avatar/' . basename($user->avatar));
            }

            $zip->close();
        }

        // Clean up JSON file
        @unlink($jsonPath);

        return $zipPath;
    }

    /**
     * Request data deletion (Right to be Forgotten)
     */
    public function requestDataDeletion(User $user, ?string $reason = null): DataDeletionRequest
    {
        // Check if there's already a pending request
        $existingRequest = DataDeletionRequest::where('user_id', $user->id)
            ->where('status', 'pending')
            ->first();

        if ($existingRequest) {
            return $existingRequest;
        }

        $request = DataDeletionRequest::create([
            'user_id' => $user->id,
            'status' => 'pending',
            'reason' => $reason,
            'requested_at' => now(),
        ]);

        // Log the request
        app(ActivityLogger::class)->log(
            ActivityLogger::TYPE_DELETE,
            'Data deletion requested',
            $user,
            DataDeletionRequest::class,
            $request->id
        );

        return $request;
    }

    /**
     * Process data deletion
     */
    public function processDataDeletion(DataDeletionRequest $request): bool
    {
        $user = $request->user;

        if (!$user) {
            $request->update([
                'status' => 'completed',
                'processed_at' => now(),
            ]);
            return true;
        }

        DB::beginTransaction();

        try {
            // Anonymize user data instead of hard delete
            $this->anonymizeUserData($user);

            // Mark request as completed
            $request->update([
                'status' => 'completed',
                'processed_at' => now(),
            ]);

            DB::commit();

            return true;
        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error('Data deletion failed: ' . $e->getMessage());

            $request->update([
                'status' => 'failed',
                'processed_at' => now(),
            ]);

            return false;
        }
    }

    /**
     * Anonymize user data
     */
    protected function anonymizeUserData(User $user): void
    {
        $anonymizedData = [
            'name' => 'Deleted User',
            'email' => 'deleted_' . $user->id . '@deleted.local',
            'username' => 'deleted_' . $user->id,
            'avatar' => null,
            'password' => bcrypt(Str::random(64)),
            'remember_token' => null,
            'two_factor_secret' => null,
            'two_factor_recovery_codes' => null,
        ];

        $user->update($anonymizedData);

        // Delete or anonymize related data
        $user->tokens()->delete(); // API tokens
        $user->sessions()->delete(); // Sessions

        // Keep records for legal/business purposes but anonymize
        ActivityLog::where('user_id', $user->id)
            ->update(['user_id' => null]);
    }

    /**
     * Log consent
     */
    public function logConsent(
        User $user,
        string $type,
        string $consentText,
        ?string $version = null
    ): ConsentLog {
        return ConsentLog::create([
            'user_id' => $user->id,
            'consent_type' => $type,
            'consent_text' => $consentText,
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
            'consented_at' => now(),
            'version' => $version ?? '1.0',
        ]);
    }

    /**
     * Withdraw consent
     */
    public function withdrawConsent(User $user, string $type): bool
    {
        $consent = ConsentLog::where('user_id', $user->id)
            ->where('consent_type', $type)
            ->active()
            ->latest()
            ->first();

        if ($consent) {
            return $consent->withdraw();
        }

        return false;
    }

    /**
     * Check if user has consented
     */
    public function hasConsent(User $user, string $type): bool
    {
        return ConsentLog::where('user_id', $user->id)
            ->where('consent_type', $type)
            ->active()
            ->exists();
    }

    /**
     * Get user information
     */
    protected function getUserInformation(User $user): array
    {
        return [
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'username' => $user->username,
            'created_at' => $user->created_at->toDateTimeString(),
            'email_verified_at' => $user->email_verified_at?->toDateTimeString(),
        ];
    }

    /**
     * Get user subscriptions
     */
    protected function getSubscriptions(User $user): array
    {
        $subscriptions = \Wave\Subscription::where('billable_id', $user->id)
            ->where('billable_type', 'user')
            ->get();

        return $subscriptions->map(function ($subscription) {
            return [
                'plan' => $subscription->plan?->name,
                'status' => $subscription->status,
                'starts_at' => $subscription->starts_at?->toDateTimeString(),
                'ends_at' => $subscription->ends_at?->toDateTimeString(),
            ];
        })->toArray();
    }

    /**
     * Get user transactions
     */
    protected function getTransactions(User $user): array
    {
        // Assuming there's a Transaction model
        if (!class_exists(\App\Models\Transaction::class)) {
            return [];
        }

        $transactions = \App\Models\Transaction::where('user_id', $user->id)->get();

        return $transactions->map(function ($transaction) {
            return [
                'amount' => $transaction->amount,
                'currency' => $transaction->currency,
                'status' => $transaction->status,
                'created_at' => $transaction->created_at->toDateTimeString(),
            ];
        })->toArray();
    }

    /**
     * Get user orders
     */
    protected function getOrders(User $user): array
    {
        if (!class_exists(\App\Models\Order::class)) {
            return [];
        }

        $orders = \App\Models\Order::where('user_id', $user->id)->get();

        return $orders->map(function ($order) {
            return [
                'order_number' => $order->order_number,
                'total' => $order->total_amount,
                'status' => $order->status,
                'created_at' => $order->created_at->toDateTimeString(),
            ];
        })->toArray();
    }

    /**
     * Get user invoices
     */
    protected function getInvoices(User $user): array
    {
        if (!class_exists(\App\Models\Invoice::class)) {
            return [];
        }

        $invoices = \App\Models\Invoice::where('user_id', $user->id)->get();

        return $invoices->map(function ($invoice) {
            return [
                'invoice_number' => $invoice->invoice_number,
                'amount' => $invoice->total_amount,
                'status' => $invoice->status,
                'created_at' => $invoice->created_at->toDateTimeString(),
            ];
        })->toArray();
    }

    /**
     * Get user support tickets
     */
    protected function getSupportTickets(User $user): array
    {
        if (!class_exists(\App\Models\SupportTicket::class)) {
            return [];
        }

        $tickets = \App\Models\SupportTicket::where('user_id', $user->id)->get();

        return $tickets->map(function ($ticket) {
            return [
                'subject' => $ticket->subject,
                'status' => $ticket->status,
                'created_at' => $ticket->created_at->toDateTimeString(),
            ];
        })->toArray();
    }

    /**
     * Get user activity logs
     */
    protected function getActivityLogs(User $user): array
    {
        return app(ActivityLogger::class)->exportActivities($user);
    }

    /**
     * Get consent logs
     */
    protected function getConsentLogs(User $user): array
    {
        $consents = ConsentLog::where('user_id', $user->id)->get();

        return $consents->map(function ($consent) {
            return [
                'type' => $consent->consent_type,
                'consented_at' => $consent->consented_at->toDateTimeString(),
                'withdrawn_at' => $consent->withdrawn_at?->toDateTimeString(),
            ];
        })->toArray();
    }

    /**
     * Generate privacy policy acceptance
     */
    public function acceptPrivacyPolicy(User $user): ConsentLog
    {
        return $this->logConsent(
            $user,
            ConsentLog::TYPE_PRIVACY_POLICY,
            'I accept the Privacy Policy',
            config('app.privacy_policy_version', '1.0')
        );
    }

    /**
     * Generate terms of service acceptance
     */
    public function acceptTermsOfService(User $user): ConsentLog
    {
        return $this->logConsent(
            $user,
            ConsentLog::TYPE_TERMS_OF_SERVICE,
            'I accept the Terms of Service',
            config('app.terms_version', '1.0')
        );
    }

    /**
     * Check if user needs to accept updated policies
     */
    public function needsPolicyAcceptance(User $user): bool
    {
        $currentVersion = config('app.privacy_policy_version', '1.0');

        $lastConsent = ConsentLog::where('user_id', $user->id)
            ->where('consent_type', ConsentLog::TYPE_PRIVACY_POLICY)
            ->active()
            ->latest()
            ->first();

        if (!$lastConsent) {
            return true;
        }

        return $lastConsent->version !== $currentVersion;
    }
}
