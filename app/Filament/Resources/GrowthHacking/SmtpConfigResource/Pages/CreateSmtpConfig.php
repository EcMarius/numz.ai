<?php

namespace App\Filament\Resources\GrowthHacking\SmtpConfigResource\Pages;

use App\Filament\Resources\GrowthHacking\SmtpConfigResource;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Facades\Auth;

class CreateSmtpConfig extends CreateRecord
{
    protected static string $resource = SmtpConfigResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['created_by'] = Auth::id();

        return $data;
    }
}
