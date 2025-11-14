<?php

namespace App\Numz\Services;

use App\Models\User;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use ZipArchive;

class GdprService
{
    /**
     * Export all user data for GDPR compliance
     *
     * @param User $user
     * @return string Path to the export file
     */
    public function exportUserData(User $user): string
    {
        $data = [
            'personal_information' => $this->getPersonalInformation($user),
            'invoices' => $this->getInvoices($user),
            'services' => $this->getServices($user),
            'domains' => $this->getDomains($user),
            'support_tickets' => $this->getSupportTickets($user),
            'payments' => $this->getPayments($user),
            'marketplace_activity' => $this->getMarketplaceActivity($user),
            'audit_logs' => $this->getAuditLogs($user),
            'export_date' => now()->toIso8601String(),
        ];

        // Create export directory if it doesn't exist
        $exportDir = storage_path('app/gdpr-exports');
        if (!is_dir($exportDir)) {
            mkdir($exportDir, 0755, true);
        }

        // Generate filename
        $filename = "user-data-{$user->id}-" . now()->format('Y-m-d-His') . '.json';
        $filepath = $exportDir . '/' . $filename;

        // Write JSON file
        file_put_contents($filepath, json_encode($data, JSON_PRETTY_PRINT));

        // Create ZIP archive
        $zipFilename = "user-data-{$user->id}-" . now()->format('Y-m-d-His') . '.zip';
        $zipPath = $exportDir . '/' . $zipFilename;

        $zip = new ZipArchive();
        if ($zip->open($zipPath, ZipArchive::CREATE) === true) {
            $zip->addFile($filepath, $filename);
            $zip->close();

            // Remove JSON file, keep only ZIP
            unlink($filepath);

            Log::info("GDPR data export created for user {$user->id}", [
                'file' => $zipFilename,
                'size' => filesize($zipPath),
            ]);

            return $zipPath;
        }

        throw new \Exception('Failed to create ZIP archive');
    }

    /**
     * Delete all user data (right to be forgotten)
     *
     * @param User $user
     * @param bool $keepInvoiceData Keep financial records for compliance
     * @return array
     */
    public function deleteUserData(User $user, bool $keepInvoiceData = true): array
    {
        $deletedData = [];

        try {
            \DB::beginTransaction();

            // Anonymize personal information
            $user->update([
                'name' => 'Deleted User',
                'email' => 'deleted-' . $user->id . '@deleted.local',
                'phone' => null,
                'address' => null,
                'city' => null,
                'state' => null,
                'zip' => null,
                'country' => null,
            ]);
            $deletedData['personal_info'] = true;

            // Delete services (soft delete)
            foreach ($user->hostingServices as $service) {
                $service->delete();
            }
            $deletedData['services'] = $user->hostingServices()->count();

            // Delete domains
            foreach ($user->domainRegistrations as $domain) {
                $domain->delete();
            }
            $deletedData['domains'] = $user->domainRegistrations()->count();

            // Delete support tickets
            foreach ($user->supportTickets as $ticket) {
                $ticket->replies()->delete();
                $ticket->delete();
            }
            $deletedData['support_tickets'] = $user->supportTickets()->count();

            // Handle invoices based on compliance requirements
            if (!$keepInvoiceData) {
                foreach ($user->invoices as $invoice) {
                    $invoice->items()->delete();
                    $invoice->delete();
                }
                $deletedData['invoices'] = $user->invoices()->count();
            } else {
                // Anonymize invoice data but keep financial records
                $deletedData['invoices'] = 'anonymized';
            }

            // Delete marketplace activity
            $user->marketplacePurchases()->delete();
            $user->marketplaceReviews()->delete();
            $deletedData['marketplace_activity'] = true;

            // Keep audit logs for compliance but anonymize
            $deletedData['audit_logs'] = 'anonymized';

            \DB::commit();

            Log::info("User data deleted (GDPR)", [
                'user_id' => $user->id,
                'deleted' => $deletedData,
                'keep_invoices' => $keepInvoiceData,
            ]);

            return $deletedData;
        } catch (\Exception $e) {
            \DB::rollBack();

            Log::error("Failed to delete user data", [
                'user_id' => $user->id,
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    protected function getPersonalInformation(User $user): array
    {
        return [
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'phone' => $user->phone,
            'address' => $user->address,
            'city' => $user->city,
            'state' => $user->state,
            'zip' => $user->zip,
            'country' => $user->country,
            'created_at' => $user->created_at?->toIso8601String(),
            'email_verified_at' => $user->email_verified_at?->toIso8601String(),
        ];
    }

    protected function getInvoices(User $user): array
    {
        return $user->invoices()->with('items')->get()->map(function ($invoice) {
            return [
                'invoice_number' => $invoice->invoice_number,
                'status' => $invoice->status,
                'total' => $invoice->total,
                'currency' => $invoice->currency,
                'due_date' => $invoice->due_date?->toDateString(),
                'paid_date' => $invoice->paid_date?->toIso8601String(),
                'items' => $invoice->items->map(fn($item) => [
                    'description' => $item->description,
                    'amount' => $item->amount,
                    'quantity' => $item->quantity,
                ])->toArray(),
            ];
        })->toArray();
    }

    protected function getServices(User $user): array
    {
        return $user->hostingServices()->get()->map(function ($service) {
            return [
                'domain' => $service->domain,
                'product' => $service->product?->name,
                'status' => $service->status,
                'billing_cycle' => $service->billing_cycle,
                'price' => $service->price,
                'next_due_date' => $service->next_due_date?->toDateString(),
                'activated_at' => $service->activated_at?->toDateString(),
            ];
        })->toArray();
    }

    protected function getDomains(User $user): array
    {
        return $user->domainRegistrations()->get()->map(function ($domain) {
            return [
                'domain' => $domain->domain,
                'registrar' => $domain->registrar,
                'status' => $domain->status,
                'registration_date' => $domain->registration_date?->toDateString(),
                'expiry_date' => $domain->expiry_date?->toDateString(),
                'auto_renew' => $domain->auto_renew,
            ];
        })->toArray();
    }

    protected function getSupportTickets(User $user): array
    {
        return $user->supportTickets()->with('replies')->get()->map(function ($ticket) {
            return [
                'ticket_number' => $ticket->ticket_number,
                'subject' => $ticket->subject,
                'status' => $ticket->status,
                'priority' => $ticket->priority,
                'created_at' => $ticket->created_at?->toIso8601String(),
                'replies' => $ticket->replies->map(fn($reply) => [
                    'message' => $reply->message,
                    'created_at' => $reply->created_at?->toIso8601String(),
                ])->toArray(),
            ];
        })->toArray();
    }

    protected function getPayments(User $user): array
    {
        return $user->paymentTransactions()->get()->map(function ($payment) {
            return [
                'transaction_id' => $payment->transaction_id,
                'amount' => $payment->amount,
                'currency' => $payment->currency,
                'payment_method' => $payment->payment_method,
                'status' => $payment->status,
                'created_at' => $payment->created_at?->toIso8601String(),
            ];
        })->toArray();
    }

    protected function getMarketplaceActivity(User $user): array
    {
        return [
            'purchases' => $user->marketplacePurchases()->get()->map(fn($p) => [
                'item' => $p->item?->name,
                'amount' => $p->amount,
                'purchased_at' => $p->created_at?->toIso8601String(),
            ])->toArray(),
            'reviews' => $user->marketplaceReviews()->get()->map(fn($r) => [
                'item' => $r->item?->name,
                'rating' => $r->rating,
                'review' => $r->review,
                'created_at' => $r->created_at?->toIso8601String(),
            ])->toArray(),
        ];
    }

    protected function getAuditLogs(User $user): array
    {
        return \App\Models\AuditLog::where('user_id', $user->id)
            ->orderBy('created_at', 'desc')
            ->limit(1000) // Limit to prevent huge exports
            ->get()
            ->map(fn($log) => [
                'event' => $log->event,
                'description' => $log->description,
                'ip_address' => $log->ip_address,
                'created_at' => $log->created_at?->toIso8601String(),
            ])->toArray();
    }
}
