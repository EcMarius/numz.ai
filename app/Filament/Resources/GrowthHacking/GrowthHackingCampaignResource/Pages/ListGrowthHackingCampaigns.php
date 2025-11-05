<?php

namespace App\Filament\Resources\GrowthHacking\GrowthHackingCampaignResource\Pages;

use App\Filament\Resources\GrowthHacking\GrowthHackingCampaignResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListGrowthHackingCampaigns extends ListRecords
{
    protected static string $resource = GrowthHackingCampaignResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('new_campaign')
                ->label('New Campaign')
                ->icon('heroicon-o-plus')
                ->url(route('filament.admin.pages.business-lead-finder')),
        ];
    }
}
