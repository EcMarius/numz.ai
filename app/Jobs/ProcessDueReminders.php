<?php

namespace App\Jobs;

use App\Models\InvoiceReminder;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class ProcessDueReminders implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function handle(): void
    {
        $dueReminders = InvoiceReminder::due()->get();

        foreach ($dueReminders as $reminder) {
            SendInvoiceReminder::dispatch($reminder);
        }

        Log::info("Dispatched invoice reminders", [
            'count' => $dueReminders->count(),
        ]);
    }
}
