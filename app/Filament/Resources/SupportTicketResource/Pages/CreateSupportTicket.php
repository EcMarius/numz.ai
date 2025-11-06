<?php

namespace App\Filament\Resources\SupportTicketResource\Pages;

use App\Filament\Resources\SupportTicketResource;
use Filament\Resources\Pages\CreateRecord;

class CreateSupportTicket extends CreateRecord
{
    protected static string $resource = SupportTicketResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        if (empty($data['ticket_number'])) {
            $data['ticket_number'] = 'TKT-' . strtoupper(substr(uniqid(), -8));
        }
        return $data;
    }
}
