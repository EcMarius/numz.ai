<?php

namespace App\Filament\Resources\Plans\Pages;

use Filament\Actions\DeleteAction;
use Filament\Actions\Action;
use App\Filament\Resources\Plans\PlanResource;
use App\Services\StripeService;
use Filament\Resources\Pages\EditRecord;
use Filament\Notifications\Notification;

class EditPlan extends EditRecord
{
    protected static string $resource = PlanResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('syncToStripe')
                ->label('Sync to Stripe')
                ->icon('heroicon-o-arrow-path')
                ->color('primary')
                ->requiresConfirmation()
                ->modalHeading('Sync Plan to Stripe')
                ->modalDescription('This will create or update the product in Stripe. If price IDs are already set, they will be kept. Use "Refresh Prices" to create new prices.')
                ->action(function (StripeService $stripeService) {
                    try {
                        if (!$stripeService->isConfigured()) {
                            Notification::make()
                                ->warning()
                                ->title('Stripe Not Configured')
                                ->body('Please configure Stripe API credentials in EvenLeads settings first.')
                                ->send();
                            return;
                        }

                        $result = $stripeService->syncPlanToStripe($this->record, false);

                        if ($result['success']) {
                            // Refresh the record to get updated data
                            $this->record->refresh();

                            // Update form data with new IDs
                            $this->fillForm();

                            Notification::make()
                                ->success()
                                ->title('Plan Synced to Stripe')
                                ->body('Product and prices have been created/updated in Stripe.')
                                ->send();
                        } else {
                            Notification::make()
                                ->danger()
                                ->title('Sync Failed')
                                ->body(implode(', ', $result['errors']))
                                ->send();
                        }
                    } catch (\Exception $e) {
                        Notification::make()
                            ->danger()
                            ->title('Error')
                            ->body($e->getMessage())
                            ->send();
                    }
                }),
            Action::make('refreshPrices')
                ->label('Refresh Prices')
                ->icon('heroicon-o-currency-dollar')
                ->color('warning')
                ->requiresConfirmation()
                ->modalHeading('Refresh Stripe Prices')
                ->modalDescription('This will check if price IDs exist for this plan. If prices don\'t exist in Stripe, new ones will be created with the current monthly_price and yearly_price values. Existing prices will be preserved. This is useful when you\'ve updated the price amounts and want new Stripe price IDs.')
                ->action(function (StripeService $stripeService) {
                    try {
                        if (!$stripeService->isConfigured()) {
                            Notification::make()
                                ->warning()
                                ->title('Stripe Not Configured')
                                ->body('Please configure Stripe API credentials in EvenLeads settings first.')
                                ->send();
                            return;
                        }

                        $result = $stripeService->syncPlanToStripe($this->record, true);

                        if ($result['success']) {
                            // Refresh the record to get updated data
                            $this->record->refresh();

                            // Update form data with new IDs
                            $this->fillForm();

                            $message = 'Prices have been refreshed in Stripe.';
                            if (!empty($result['monthly_price_id']) && !empty($result['yearly_price_id'])) {
                                $message .= ' Both monthly and yearly prices are now set.';
                            } elseif (!empty($result['monthly_price_id'])) {
                                $message .= ' Monthly price is now set.';
                            } elseif (!empty($result['yearly_price_id'])) {
                                $message .= ' Yearly price is now set.';
                            }

                            Notification::make()
                                ->success()
                                ->title('Prices Refreshed')
                                ->body($message)
                                ->duration(7000)
                                ->send();
                        } else {
                            Notification::make()
                                ->danger()
                                ->title('Refresh Failed')
                                ->body(implode(', ', $result['errors']))
                                ->send();
                        }
                    } catch (\Exception $e) {
                        Notification::make()
                            ->danger()
                            ->title('Error')
                            ->body($e->getMessage())
                            ->send();
                    }
                }),
            DeleteAction::make(),
        ];
    }

    protected function mutateFormDataBeforeFill(array $data): array
    {
        // Decode JSON fields for the form
        if (isset($data['features']) && is_string($data['features'])) {
            $data['features'] = json_decode($data['features'], true) ?? [];
        }

        // Ensure custom_properties are properly set for EvenLeads limits
        if (isset($data['custom_properties'])) {
            if (is_string($data['custom_properties'])) {
                $data['custom_properties'] = json_decode($data['custom_properties'], true) ?? [];
            }
        } else {
            $data['custom_properties'] = [];
        }

        // IMPORTANT: Ensure evenleads key exists
        if (!isset($data['custom_properties']['evenleads'])) {
            $data['custom_properties']['evenleads'] = [];
        }

        // Map database column to form field for backward compatibility
        if (isset($data['leads_per_sync'])) {
            $data['custom_properties']['evenleads']['leads_per_sync'] = $data['leads_per_sync'];
        }

        // Set defaults for all EvenLeads fields to ensure they show in form
        $defaults = [
            'campaigns' => -1,
            'keywords_per_campaign' => -1,
            'manual_syncs_per_month' => -1,
            'ai_replies_per_month' => -1,
            'leads_per_sync' => 60,
            'soft_limit_leads' => true,
            'leads_storage' => -1,
            'automated_sync_interval_minutes' => 1440,
            'ai_chat_access' => false,
            'smart_lead_retrieval' => false,
        ];

        // Merge defaults with existing values (existing values take precedence)
        $data['custom_properties']['evenleads'] = array_merge($defaults, $data['custom_properties']['evenleads'] ?? []);

        return $data;
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        // Sync leads_per_sync from custom_properties to the database column for backward compatibility
        if (isset($data['custom_properties']['evenleads']['leads_per_sync'])) {
            $data['leads_per_sync'] = $data['custom_properties']['evenleads']['leads_per_sync'];
        }

        return $data;
    }

    protected function afterSave(): void
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
                        ->title('Plan Saved & Synced to Stripe')
                        ->body('Plan has been updated in both database and Stripe.')
                        ->send();
                } else {
                    Notification::make()
                        ->warning()
                        ->title('Plan Saved (Stripe Sync Failed)')
                        ->body('Plan saved but Stripe sync failed. You can manually sync using the "Sync to Stripe" button.')
                        ->send();
                }
            } catch (\Exception $e) {
                logger()->warning('Auto-sync to Stripe failed', [
                    'plan_id' => $this->record->id,
                    'error' => $e->getMessage()
                ]);

                Notification::make()
                    ->warning()
                    ->title('Plan Saved (Stripe Sync Error)')
                    ->body('Plan saved but could not sync to Stripe: ' . $e->getMessage())
                    ->send();
            }
        }
    }
}
