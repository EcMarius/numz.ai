<?php

namespace App\Filament\Resources\ServiceResource\Pages;

use App\Filament\Resources\ServiceResource;
use Filament\Resources\Pages\CreateRecord;

class CreateService extends CreateRecord
{
    protected static string $resource = ServiceResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    protected function getCreatedNotificationTitle(): ?string
    {
        return 'Service created successfully';
    }

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        // Auto-generate username if not provided
        if (empty($data['username'])) {
            $data['username'] = 'user_' . strtolower(substr(uniqid(), -8));
        }

        // Auto-generate password if not provided
        if (empty($data['password'])) {
            $data['password'] = bin2hex(random_bytes(8));
        }

        return $data;
    }
}
