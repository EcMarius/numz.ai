<?php

namespace App\Filament\Resources\GrowthHacking\GrowthHackingCampaignResource\Pages;

use App\Filament\Resources\GrowthHacking\GrowthHackingCampaignResource;
use Filament\Resources\Pages\Page;

class ViewCampaignStats extends Page
{
    protected static string $resource = GrowthHackingCampaignResource::class;

    public $campaign;

    public function getView(): string
    {
        return 'filament.resources.growth-hacking.pages.view-campaign-stats';
    }

    public function mount($record): void
    {
        $this->campaign = $record;
    }
}
