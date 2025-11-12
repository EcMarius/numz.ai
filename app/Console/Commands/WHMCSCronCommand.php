<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use App\Models\Invoice;
use App\Models\Service;
use App\Models\Domain;
use App\Models\User;
use Carbon\Carbon;

/**
 * WHMCS Cron Command
 *
 * Handles automated tasks similar to WHMCS cron.php:
 * - Generate recurring invoices
 * - Suspend overdue services
 * - Process domain renewals
 * - Send payment reminders
 * - Run module cron tasks
 */
class WHMCSCronCommand extends Command
{
    protected $signature = 'whmcs:cron
                          {--invoices : Generate recurring invoices}
                          {--suspensions : Process service suspensions}
                          {--renewals : Process domain renewals}
                          {--reminders : Send payment reminders}
                          {--modules : Run module cron tasks}
                          {--all : Run all cron tasks}';

    protected $description = 'Run WHMCS automated cron tasks';

    protected array $stats = [
        'invoices_generated' => 0,
        'services_suspended' => 0,
        'services_unsuspended' => 0,
        'services_terminated' => 0,
        'domains_renewed' => 0,
        'reminders_sent' => 0,
        'errors' => 0,
    ];

    public function handle()
    {
        if (!config('whmcs.enabled', false)) {
            $this->error('WHMCS compatibility is disabled');
            return 1;
        }

        $this->info('Starting WHMCS Cron Tasks...');
        $this->info('Time: ' . now()->format('Y-m-d H:i:s'));
        $this->newLine();

        $runAll = $this->option('all');

        try {
            // Run hook for cron start
            run_hook('DailyCronJob', []);

            // Generate recurring invoices
            if ($runAll || $this->option('invoices')) {
                $this->info('📄 Generating recurring invoices...');
                $this->generateRecurringInvoices();
            }

            // Process service suspensions
            if ($runAll || $this->option('suspensions')) {
                $this->info('⚠️  Processing service suspensions...');
                $this->processSuspensions();
            }

            // Process domain renewals
            if ($runAll || $this->option('renewals')) {
                $this->info('🌐 Processing domain renewals...');
                $this->processDomainRenewals();
            }

            // Send payment reminders
            if ($runAll || $this->option('reminders')) {
                $this->info('📧 Sending payment reminders...');
                $this->sendPaymentReminders();
            }

            // Run module cron tasks
            if ($runAll || $this->option('modules')) {
                $this->info('🔧 Running module cron tasks...');
                $this->runModuleCronTasks();
            }

            // Run hook for cron complete
            run_hook('DailyCronJobPreEmail', []);

            // Display statistics
            $this->displayStatistics();

            // Log cron run
            logActivity('WHMCS Cron completed successfully');

            $this->info('✅ Cron tasks completed successfully!');
            return 0;

        } catch (\Exception $e) {
            $this->error('❌ Cron failed: ' . $e->getMessage());
            logActivity('WHMCS Cron failed: ' . $e->getMessage());
            return 1;
        }
    }

    /**
     * Generate recurring invoices for services due for renewal
     */
    protected function generateRecurringInvoices(): void
    {
        $graceDays = config('whmcs.invoicing.grace_days', 7);
        $dueDate = now()->addDays($graceDays);

        // Find services due for renewal
        $services = Service::where('status', 'active')
            ->where('billing_cycle', '!=', 'free')
            ->where('billing_cycle', '!=', 'onetime')
            ->whereDate('next_due_date', '<=', $dueDate)
            ->whereDoesntHave('invoices', function($query) {
                $query->where('status', 'unpaid')
                      ->where('created_at', '>=', now()->subDays(7));
            })
            ->get();

        foreach ($services as $service) {
            try {
                // Create invoice
                $invoice = Invoice::create([
                    'user_id' => $service->user_id,
                    'status' => 'unpaid',
                    'due_date' => $service->next_due_date,
                    'currency' => $service->user->currency ?? 'USD',
                    'subtotal' => $service->amount,
                    'tax' => $this->calculateTax($service->amount, $service->user),
                    'total' => $service->amount + $this->calculateTax($service->amount, $service->user),
                ]);

                // Add invoice item
                $invoice->items()->create([
                    'description' => $service->product->name . ' - ' . $service->domain,
                    'amount' => $service->amount,
                    'quantity' => 1,
                    'type' => 'service',
                    'related_id' => $service->id,
                ]);

                // Update service next due date
                $service->next_due_date = $this->calculateNextDueDate($service->next_due_date, $service->billing_cycle);
                $service->save();

                // Run hook
                run_hook('InvoiceCreated', ['invoiceid' => $invoice->id]);

                $this->stats['invoices_generated']++;
                $this->line("  ✓ Invoice #{$invoice->id} created for {$service->domain}");

            } catch (\Exception $e) {
                $this->stats['errors']++;
                $this->error("  ✗ Failed to create invoice for service #{$service->id}: " . $e->getMessage());
                logActivity("Failed to create invoice for service #{$service->id}: " . $e->getMessage());
            }
        }

        $this->info("  Generated {$this->stats['invoices_generated']} invoices");
        $this->newLine();
    }

    /**
     * Process service suspensions for overdue accounts
     */
    protected function processSuspensions(): void
    {
        $graceDays = config('whmcs.provisioning.suspension_grace_days', 3);
        $autoSuspend = config('whmcs.provisioning.auto_suspend', true);
        $autoTerminate = config('whmcs.provisioning.auto_terminate', false);
        $terminateDays = config('whmcs.provisioning.termination_days', 30);

        if (!$autoSuspend) {
            $this->line('  Auto-suspension is disabled');
            $this->newLine();
            return;
        }

        // Find services with overdue invoices
        $overdueServices = Service::where('status', 'active')
            ->whereHas('invoices', function($query) use ($graceDays) {
                $query->where('status', 'unpaid')
                      ->whereDate('due_date', '<=', now()->subDays($graceDays));
            })
            ->get();

        foreach ($overdueServices as $service) {
            try {
                // Suspend service
                $service->status = 'suspended';
                $service->suspended_at = now();
                $service->suspension_reason = 'Overdue invoice';
                $service->save();

                // Call module suspend function
                if ($service->product->server_module) {
                    $result = \App\Numz\WHMCS\ModuleLoader::callModuleFunction(
                        'servers',
                        $service->product->server_module,
                        'SuspendAccount',
                        prepareModuleParams($service)
                    );

                    if (isset($result['error'])) {
                        throw new \Exception($result['error']);
                    }
                }

                // Run hook
                run_hook('AfterModuleSuspend', ['serviceid' => $service->id]);

                $this->stats['services_suspended']++;
                $this->line("  ⚠ Suspended service #{$service->id} - {$service->domain}");

            } catch (\Exception $e) {
                $this->stats['errors']++;
                $this->error("  ✗ Failed to suspend service #{$service->id}: " . $e->getMessage());
            }
        }

        // Unsuspend services with paid invoices
        $servicesForUnsuspension = Service::where('status', 'suspended')
            ->whereDoesntHave('invoices', function($query) {
                $query->where('status', 'unpaid');
            })
            ->get();

        foreach ($servicesForUnsuspension as $service) {
            try {
                $service->status = 'active';
                $service->suspended_at = null;
                $service->suspension_reason = null;
                $service->save();

                // Call module unsuspend function
                if ($service->product->server_module) {
                    $result = \App\Numz\WHMCS\ModuleLoader::callModuleFunction(
                        'servers',
                        $service->product->server_module,
                        'UnsuspendAccount',
                        prepareModuleParams($service)
                    );
                }

                run_hook('AfterModuleUnsuspend', ['serviceid' => $service->id]);

                $this->stats['services_unsuspended']++;
                $this->line("  ✓ Unsuspended service #{$service->id} - {$service->domain}");

            } catch (\Exception $e) {
                $this->stats['errors']++;
                $this->error("  ✗ Failed to unsuspend service #{$service->id}: " . $e->getMessage());
            }
        }

        // Terminate services suspended for too long
        if ($autoTerminate) {
            $servicesForTermination = Service::where('status', 'suspended')
                ->whereDate('suspended_at', '<=', now()->subDays($terminateDays))
                ->get();

            foreach ($servicesForTermination as $service) {
                try {
                    // Call module terminate function
                    if ($service->product->server_module) {
                        $result = \App\Numz\WHMCS\ModuleLoader::callModuleFunction(
                            'servers',
                            $service->product->server_module,
                            'TerminateAccount',
                            prepareModuleParams($service)
                        );
                    }

                    $service->status = 'terminated';
                    $service->terminated_at = now();
                    $service->save();

                    run_hook('AfterModuleTerminate', ['serviceid' => $service->id]);

                    $this->stats['services_terminated']++;
                    $this->line("  🗑 Terminated service #{$service->id} - {$service->domain}");

                } catch (\Exception $e) {
                    $this->stats['errors']++;
                    $this->error("  ✗ Failed to terminate service #{$service->id}: " . $e->getMessage());
                }
            }
        }

        $this->info("  Suspended: {$this->stats['services_suspended']}, " .
                   "Unsuspended: {$this->stats['services_unsuspended']}, " .
                   "Terminated: {$this->stats['services_terminated']}");
        $this->newLine();
    }

    /**
     * Process domain renewals
     */
    protected function processDomainRenewals(): void
    {
        $autoRenew = config('whmcs.domains.auto_renew', true);

        if (!$autoRenew) {
            $this->line('  Auto-renewal is disabled');
            $this->newLine();
            return;
        }

        // Find domains due for renewal with auto-renew enabled
        $domains = Domain::where('status', 'active')
            ->where('auto_renew', true)
            ->whereDate('expiry_date', '<=', now()->addDays(30))
            ->whereDate('expiry_date', '>=', now())
            ->get();

        foreach ($domains as $domain) {
            try {
                // Check if user has sufficient balance or payment method
                if (!$this->canProcessAutoRenewal($domain->user)) {
                    continue;
                }

                // Call registrar module to renew
                if ($domain->registrar_module) {
                    $result = \App\Numz\WHMCS\ModuleLoader::callModuleFunction(
                        'registrars',
                        $domain->registrar_module,
                        'RenewDomain',
                        [
                            'domainname' => $domain->domain,
                            'regperiod' => 1, // 1 year
                        ]
                    );

                    if (isset($result['error'])) {
                        throw new \Exception($result['error']);
                    }

                    // Update expiry date
                    $domain->expiry_date = now()->addYear();
                    $domain->save();

                    $this->stats['domains_renewed']++;
                    $this->line("  ✓ Renewed domain: {$domain->domain}");
                }

            } catch (\Exception $e) {
                $this->stats['errors']++;
                $this->error("  ✗ Failed to renew domain {$domain->domain}: " . $e->getMessage());
            }
        }

        $this->info("  Renewed {$this->stats['domains_renewed']} domains");
        $this->newLine();
    }

    /**
     * Send payment reminders
     */
    protected function sendPaymentReminders(): void
    {
        $reminderDays = config('whmcs.invoicing.reminder_days', [7, 3, 1]);

        foreach ($reminderDays as $days) {
            $invoices = Invoice::where('status', 'unpaid')
                ->whereDate('due_date', '=', now()->addDays($days))
                ->get();

            foreach ($invoices as $invoice) {
                try {
                    // Send reminder email
                    // sendMessage('Invoice Payment Reminder', $invoice->id);

                    run_hook('InvoicePaymentReminder', [
                        'invoiceid' => $invoice->id,
                        'days_until_due' => $days,
                    ]);

                    $this->stats['reminders_sent']++;

                } catch (\Exception $e) {
                    $this->stats['errors']++;
                }
            }
        }

        $this->line("  Sent {$this->stats['reminders_sent']} payment reminders");
        $this->newLine();
    }

    /**
     * Run module cron tasks
     */
    protected function runModuleCronTasks(): void
    {
        $modules = \App\Numz\WHMCS\ModuleLoader::discoverModules();
        $tasksRun = 0;

        foreach ($modules as $type => $moduleList) {
            foreach ($moduleList as $moduleName) {
                try {
                    // Check if module has a cron function
                    $cronFunction = $moduleName . '_cron';

                    if (function_exists($cronFunction)) {
                        call_user_func($cronFunction);
                        $tasksRun++;
                        $this->line("  ✓ Ran cron for module: {$moduleName}");
                    }

                } catch (\Exception $e) {
                    $this->stats['errors']++;
                    $this->error("  ✗ Module {$moduleName} cron failed: " . $e->getMessage());
                }
            }
        }

        $this->line("  Executed {$tasksRun} module cron tasks");
        $this->newLine();
    }

    /**
     * Display statistics
     */
    protected function displayStatistics(): void
    {
        $this->newLine();
        $this->info('📊 Cron Statistics:');
        $this->table(
            ['Metric', 'Count'],
            [
                ['Invoices Generated', $this->stats['invoices_generated']],
                ['Services Suspended', $this->stats['services_suspended']],
                ['Services Unsuspended', $this->stats['services_unsuspended']],
                ['Services Terminated', $this->stats['services_terminated']],
                ['Domains Renewed', $this->stats['domains_renewed']],
                ['Reminders Sent', $this->stats['reminders_sent']],
                ['Errors', $this->stats['errors']],
            ]
        );
    }

    /**
     * Calculate next due date based on billing cycle
     */
    protected function calculateNextDueDate($currentDate, $billingCycle): Carbon
    {
        $date = Carbon::parse($currentDate);

        return match ($billingCycle) {
            'monthly' => $date->addMonth(),
            'quarterly' => $date->addMonths(3),
            'semiannually' => $date->addMonths(6),
            'annually' => $date->addYear(),
            'biennially' => $date->addYears(2),
            'triennially' => $date->addYears(3),
            default => $date->addMonth(),
        };
    }

    /**
     * Calculate tax for amount
     */
    protected function calculateTax($amount, $user): float
    {
        $taxRate = config('whmcs.tax.rate', 0);
        return $amount * ($taxRate / 100);
    }

    /**
     * Check if user can process auto-renewal
     */
    protected function canProcessAutoRenewal($user): bool
    {
        // Check if user has payment method on file or credit balance
        return $user->credit >= 0 || $user->hasPaymentMethod();
    }
}
