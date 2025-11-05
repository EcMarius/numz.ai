<?php

namespace App\Console\Commands;

use App\Models\Invoice;
use App\Mail\InvoiceOverdue;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;

class SendOverdueNotices extends Command
{
    protected $signature = 'numz:send-overdue-notices';
    protected $description = 'Send notifications for overdue invoices';

    public function handle()
    {
        $this->info('Sending overdue notices...');

        $overdueInvoices = Invoice::where('status', 'unpaid')
            ->where('due_date', '<', now())
            ->with('user')
            ->get();

        $count = 0;

        foreach ($overdueInvoices as $invoice) {
            $daysOverdue = now()->diffInDays($invoice->due_date);

            // Send reminders at specific intervals
            if (in_array($daysOverdue, [1, 3, 7, 14, 30])) {
                try {
                    Mail::to($invoice->user->email)->send(new InvoiceOverdue($invoice, $daysOverdue));
                    $this->info("Sent overdue notice for invoice #{$invoice->invoice_number} ({$daysOverdue} days overdue)");
                    $count++;
                } catch (\Exception $e) {
                    $this->error("Failed to send email: {$e->getMessage()}");
                }
            }
        }

        $this->info("Sent {$count} overdue notices");

        return 0;
    }
}
