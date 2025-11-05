<?php

namespace App\Filament\Resources\Plans\Pages;

use App\Filament\Resources\Plans\PlanResource;
use App\Services\StripeService;
use Filament\Resources\Pages\CreateRecord;
use Filament\Notifications\Notification;

class CreatePlan extends CreateRecord
{
    protected static string $resource = PlanResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        // Sync leads_per_sync from custom_properties to the database column for backward compatibility
        if (isset($data['custom_properties']['evenleads']['leads_per_sync'])) {
            $data['leads_per_sync'] = $data['custom_properties']['evenleads']['leads_per_sync'];
        }

        return $data;
    }

    protected function afterCreate(): void
    {
        // Auto-sync to Stripe if configured and price fields are set
        $stripeService = app(StripeService::class);

        if ($stripeService->isConfigured() &&
            (!empty($this->record->monthly_price) || !empty($this->record->yearly_price))) {
            try {
                $result = $stripeService->syncPlanToStripe($this->record, false);

                if ($result['success']) {
                    // Refresh the record to get updated price IDs
                    $this->record->refresh();

                    Notification::make()
                        ->success()
                        ->title('Plan Created & Synced to Stripe')
                        ->body('Plan has been created in both database and Stripe.')
                        ->send();
                } else {
                    Notification::make()
                        ->warning()
                        ->title('Plan Created (Stripe Sync Failed)')
                        ->body('Plan created but Stripe sync failed. You can manually sync from the edit page.')
                        ->send();
                }
            } catch (\Exception $e) {
                logger()->warning('Auto-sync to Stripe failed', [
                    'plan_id' => $this->record->id,
                    'error' => $e->getMessage()
                ]);

                Notification::make()
                    ->warning()
                    ->title('Plan Created (Stripe Sync Error)')
                    ->body('Plan created but could not sync to Stripe: ' . $e->getMessage())
                    ->send();
            }
        }
    }
}
