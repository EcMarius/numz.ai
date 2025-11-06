<?php

namespace App\Filament\Resources\ApiCredentialResource\Pages;

use App\Filament\Resources\ApiCredentialResource;
use Filament\Resources\Pages\CreateRecord;

class CreateApiCredential extends CreateRecord
{
    protected static string $resource = ApiCredentialResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        // Initialize usage tracking
        $data['usage_count'] = 0;

        // Set rate limit remaining to rate limit if set
        if (isset($data['rate_limit']) && $data['rate_limit']) {
            $data['rate_limit_remaining'] = $data['rate_limit'];
            $data['rate_limit_reset_at'] = now()->addHour();
        }

        return $data;
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
