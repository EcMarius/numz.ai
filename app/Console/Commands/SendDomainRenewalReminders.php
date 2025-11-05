<?php

namespace App\Console\Commands;

use App\Models\DomainRegistration;
use App\Mail\DomainRenewalReminder;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;

class SendDomainRenewalReminders extends Command
{
    protected $signature = 'numz:domain-renewal-reminders';
    protected $description = 'Send domain renewal reminder emails';

    public function handle()
    {
        $this->info('Sending domain renewal reminders...');

        $reminderDays = [60, 30, 14, 7, 3, 1];
        $count = 0;

        foreach ($reminderDays as $days) {
            $domains = DomainRegistration::where('status', 'active')
                ->whereDate('expiry_date', now()->addDays($days))
                ->with('user')
                ->get();

            foreach ($domains as $domain) {
                try {
                    Mail::to($domain->user->email)->send(new DomainRenewalReminder($domain));
                    $this->info("Sent {$days}-day reminder for {$domain->domain}");
                    $count++;
                } catch (\Exception $e) {
                    $this->error("Failed to send email: {$e->getMessage()}");
                }
            }
        }

        $this->info("Sent {$count} domain renewal reminders");

        return 0;
    }
}
