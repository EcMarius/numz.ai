<?php

namespace App\Filament\Resources\GrowthHacking\GrowthHackingCampaignResource\Pages;

use App\Filament\Resources\GrowthHacking\GrowthHackingCampaignResource;
use App\Jobs\SendGrowthHackingEmail;
use Filament\Resources\Pages\Page;
use Filament\Notifications\Notification;

class ReviewCampaign extends Page
{
    protected static string $resource = GrowthHackingCampaignResource::class;

    public $campaign;
    public $prospects;
    public $skippedProspects = [];

    public function getView(): string
    {
        return 'filament.resources.growth-hacking.pages.review-campaign';
    }

    public function mount($record): void
    {
        $this->campaign = $record;
        $this->loadProspects();
    }

    public function loadProspects(): void
    {
        $this->prospects = $this->campaign->prospects()
            ->where('status', '!=', 'skipped')
            ->whereNotNull('email')
            ->with('leads')
            ->get();
    }

    public function skipProspect($prospectId): void
    {
        $this->skippedProspects[] = $prospectId;

        Notification::make()
            ->success()
            ->title('Prospect Skipped')
            ->body('This prospect will not receive an email.')
            ->send();
    }

    public function sendAllEmails(): void
    {
        try {
            $prospectsToEmail = $this->prospects->filter(function ($prospect) {
                return !in_array($prospect->id, $this->skippedProspects);
            });

            if ($prospectsToEmail->isEmpty()) {
                Notification::make()
                    ->warning()
                    ->title('No Prospects to Email')
                    ->body('All prospects have been skipped.')
                    ->send();
                return;
            }

            foreach ($prospectsToEmail as $prospect) {
                SendGrowthHackingEmail::dispatch($this->campaign, $prospect);
            }

            $this->campaign->update(['status' => 'sent']);

            Notification::make()
                ->success()
                ->title('Emails Queued!')
                ->body("Queued {$prospectsToEmail->count()} emails for sending.")
                ->send();

            $this->redirect(route('filament.admin.resources.growth-hacking-campaigns.index'));

        } catch (\Exception $e) {
            Notification::make()
                ->danger()
                ->title('Error Sending Emails')
                ->body($e->getMessage())
                ->send();
        }
    }
}
