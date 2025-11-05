<?php

namespace App\Filament\Pages;

use BackedEnum;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Wave\Plugins\EvenLeads\Models\Campaign;

class EvenLeadsSystem extends Page
{
    protected static BackedEnum|string|null $navigationIcon = 'phosphor-wrench-duotone';

    protected static ?string $navigationLabel = 'System Controls';

    protected static ?int $navigationSort = 100;

    public function getView(): string
    {
        return 'filament.pages.evenleads-system';
    }

    public int $syncingCampaignsCount = 0;
    public int $pendingJobsCount = 0;
    public int $pendingFollowUpsCount = 0;
    public bool $followUpSystemEnabled = true;
    public string $followUpTestOutput = '';
    public ?int $selectedLeadId = null;

    public function mount(): void
    {
        $this->refreshStats();
        $this->followUpSystemEnabled = setting('site.follow_up_system_enabled', true);
    }

    public function refreshStats(): void
    {
        $this->syncingCampaignsCount = Campaign::where('status', 'syncing')->count();
        $this->pendingJobsCount = DB::table('jobs')
            ->where('queue', 'default')
            ->where('payload', 'like', '%SyncCampaignJob%')
            ->count();

        // Count pending follow-ups
        $this->pendingFollowUpsCount = DB::table('evenleads_lead_messages')
            ->where('is_follow_up', true)
            ->where('status', 'draft')
            ->whereNotNull('scheduled_send_at')
            ->count();
    }

    public function toggleFollowUpSystem(): void
    {
        try {
            $newValue = !$this->followUpSystemEnabled;

            // Update setting
            $setting = \Wave\Setting::where('key', 'follow_up_system_enabled')->first();
            if (!$setting) {
                $setting = new \Wave\Setting();
                $setting->key = 'follow_up_system_enabled';
                $setting->group = 'site';
            }
            $setting->value = $newValue ? '1' : '0';
            $setting->save();

            $this->followUpSystemEnabled = $newValue;

            Notification::make()
                ->title('Follow-Up System ' . ($newValue ? 'Enabled' : 'Disabled'))
                ->body('The automatic follow-up system has been ' . ($newValue ? 'enabled' : 'disabled') . '.')
                ->success()
                ->send();
        } catch (\Exception $e) {
            Notification::make()
                ->title('Error')
                ->body('Failed to toggle follow-up system: ' . $e->getMessage())
                ->danger()
                ->send();
        }
    }

    public function testFollowUpMessage(?int $leadId = null): void
    {
        try {
            $lead = $leadId
                ? \Wave\Plugins\EvenLeads\Models\Lead::find($leadId)
                : \Wave\Plugins\EvenLeads\Models\Lead::where('status', 'contacted')
                    ->whereHas('followUpMessages', function($q) {
                        $q->where('status', 'draft')->whereNotNull('scheduled_send_at');
                    })
                    ->first();

            if (!$lead) {
                $this->followUpTestOutput = "❌ No lead found with pending follow-up.\n\nPlease select a lead ID or ensure there are leads with scheduled follow-ups.";
                return;
            }

            $followUp = $lead->followUpMessages()->where('status', 'draft')->first();

            if (!$followUp) {
                $this->followUpTestOutput = "❌ Lead #{$lead->id} has no pending follow-up message.";
                return;
            }

            // Check if lead has responded
            $hasResponded = $lead->response_received_at !== null;

            $output = "🧪 Follow-Up Test for Lead #{$lead->id}\n\n";
            $output .= "📋 Lead Info:\n";
            $output .= "  • Title: {$lead->title}\n";
            $output .= "  • Platform: {$lead->platform}\n";
            $output .= "  • Author: {$lead->author}\n";
            $output .= "  • Last Contact: " . ($lead->last_contact_at ? $lead->last_contact_at->format('Y-m-d H:i:s') : 'N/A') . "\n";
            $output .= "  • Response Received: " . ($hasResponded ? "✓ Yes at " . $lead->response_received_at->format('Y-m-d H:i:s') : "✗ No") . "\n\n";

            $output .= "📨 Follow-Up Message:\n";
            $output .= "  • Status: {$followUp->status}\n";
            $output .= "  • Scheduled: " . ($followUp->scheduled_send_at ? $followUp->scheduled_send_at->format('Y-m-d H:i:s') : 'N/A') . "\n";
            $output .= "  • AI Generated: " . ($followUp->is_ai_generated ? 'Yes' : 'No') . "\n\n";

            $output .= "💬 Message Text:\n";
            $output .= "  " . str_replace("\n", "\n  ", $followUp->message_text) . "\n\n";

            $output .= "🔍 Decision:\n";
            if ($hasResponded) {
                $output .= "  ❌ WILL NOT SEND - Lead has already responded\n";
            } elseif ($followUp->scheduled_send_at > now()) {
                $output .= "  ⏳ NOT YET - Scheduled for " . $followUp->scheduled_send_at->diffForHumans() . "\n";
            } else {
                $output .= "  ✅ WOULD SEND NOW - Follow-up is due and lead hasn't responded\n";
            }

            $this->followUpTestOutput = $output;

            Notification::make()
                ->title('Follow-Up Test Complete')
                ->success()
                ->send();
        } catch (\Exception $e) {
            $this->followUpTestOutput = "❌ Error: " . $e->getMessage();
            Notification::make()
                ->title('Test Failed')
                ->body($e->getMessage())
                ->danger()
                ->send();
        }
    }

    public function getPendingFollowUps(): array
    {
        return \Wave\Plugins\EvenLeads\Models\LeadMessage::where('is_follow_up', true)
            ->where('status', 'draft')
            ->whereNotNull('scheduled_send_at')
            ->with('lead')
            ->orderBy('scheduled_send_at', 'asc')
            ->limit(20)
            ->get()
            ->map(function($message) {
                $lead = $message->lead;
                return [
                    'id' => $message->id,
                    'lead_id' => $lead->id,
                    'lead_title' => $lead->title,
                    'platform' => $lead->platform,
                    'scheduled_at' => $message->scheduled_send_at->format('Y-m-d H:i:s'),
                    'scheduled_diff' => $message->scheduled_send_at->diffForHumans(),
                    'message_preview' => \Str::limit($message->message_text, 60),
                ];
            })
            ->toArray();
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('stopAllSyncs')
                ->label('Stop All Syncs')
                ->icon('heroicon-o-stop-circle')
                ->color('danger')
                ->requiresConfirmation()
                ->modalHeading('Stop All Campaign Syncs')
                ->modalDescription('This will stop all currently running campaign syncs and remove pending sync jobs from the queue. Are you sure?')
                ->modalSubmitActionLabel('Yes, stop all syncs')
                ->action(function () {
                    try {
                        // Run the command
                        Artisan::call('evenleads:stop-all-syncs', ['--force' => true]);
                        $output = Artisan::output();

                        $this->refreshStats();

                        Notification::make()
                            ->title('All Syncs Stopped')
                            ->body('All campaign syncs have been stopped successfully.')
                            ->success()
                            ->send();
                    } catch (\Exception $e) {
                        Notification::make()
                            ->title('Error')
                            ->body('Failed to stop syncs: ' . $e->getMessage())
                            ->danger()
                            ->send();
                    }
                })
                ->visible(fn () => $this->syncingCampaignsCount > 0 || $this->pendingJobsCount > 0),

            Action::make('refresh')
                ->label('Refresh')
                ->icon('heroicon-o-arrow-path')
                ->action(function () {
                    $this->refreshStats();
                    Notification::make()
                        ->title('Stats Refreshed')
                        ->success()
                        ->send();
                }),

            Action::make('restartQueueWorker')
                ->label('Restart Queue Worker')
                ->icon('heroicon-o-arrow-path-rounded-square')
                ->color('danger')
                ->requiresConfirmation()
                ->modalHeading('Restart Queue Worker')
                ->modalDescription('This will restart the queue worker. This is useful if jobs are stuck or not processing. Are you sure?')
                ->modalSubmitActionLabel('Yes, restart queue worker')
                ->action(function () {
                    try {
                        // First, restart the queue (gracefully stops current jobs and allows new workers)
                        Artisan::call('queue:restart');

                        // Give it a moment to restart
                        sleep(1);

                        // Start a new queue worker in the background
                        $queueWorkerService = app(\App\Services\QueueWorkerService::class);
                        $queueWorkerService->start();

                        Notification::make()
                            ->title('Queue Worker Restarted')
                            ->body('The queue worker has been restarted successfully.')
                            ->success()
                            ->send();
                    } catch (\Exception $e) {
                        Notification::make()
                            ->title('Error')
                            ->body('Failed to restart queue worker: ' . $e->getMessage())
                            ->danger()
                            ->send();
                    }
                }),
        ];
    }
}
