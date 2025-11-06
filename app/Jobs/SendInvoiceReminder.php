<?php

namespace App\Jobs;

use App\Models\InvoiceReminder;
use App\Mail\InvoiceReminderMail;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;

class SendInvoiceReminder implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        public InvoiceReminder $reminder
    ) {}

    public function handle(): void
    {
        try {
            $invoice = $this->reminder->invoice;
            $user = $invoice->user;

            // Send reminder email
            Mail::to($user->email)->send(new InvoiceReminderMail($invoice, $this->reminder->type));

            // Mark reminder as sent
            $this->reminder->markAsSent();

            // Increment invoice reminder count
            $invoice->incrementReminderCount();

            Log::info("Invoice reminder sent", [
                'invoice_id' => $invoice->id,
                'invoice_number' => $invoice->invoice_number,
                'reminder_type' => $this->reminder->type,
                'user_email' => $user->email,
            ]);

        } catch (\Exception $e) {
            Log::error("Failed to send invoice reminder", [
                'invoice_id' => $this->reminder->invoice_id,
                'error' => $e->getMessage(),
            ]);

            $this->reminder->markAsFailed($e->getMessage());

            throw $e;
        }
    }
}
