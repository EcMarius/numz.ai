<?php

namespace App\Filament\Resources\InvoiceResource\Pages;

use App\Filament\Resources\InvoiceResource;
use Filament\Resources\Pages\CreateRecord;

class CreateInvoice extends CreateRecord
{
    protected static string $resource = InvoiceResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        // Set default invoice number if not provided
        if (empty($data['invoice_number'])) {
            $data['invoice_number'] = \App\Models\Invoice::generateInvoiceNumber();
        }

        return $data;
    }

    protected function afterCreate(): void
    {
        // Recalculate totals after items are saved
        $this->record->calculateTotals();
    }
}
