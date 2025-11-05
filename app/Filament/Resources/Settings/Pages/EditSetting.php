<?php

namespace App\Filament\Resources\Settings\Pages;

use Filament\Actions\DeleteAction;
use App\Filament\Resources\Settings\SettingResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Support\Facades\Cache;

class EditSetting extends EditRecord
{
    protected static string $resource = SettingResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }

    protected function mutateFormDataBeforeFill(array $data): array
    {
        // Ensure value is always a string, not an object or array
        if (isset($data['value'])) {
            // Convert arrays/objects to JSON string
            if (is_array($data['value']) || is_object($data['value'])) {
                $data['value'] = json_encode($data['value']);
            } else {
                // Ensure it's a string
                $data['value'] = (string) $data['value'];
            }
        } else {
            // Set empty string if value is not set
            $data['value'] = '';
        }

        return $data;
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        \Log::info('EditSetting - mutateFormDataBeforeSave:', [
            'data' => $data,
            'has_value' => isset($data['value']),
            'value_type' => isset($data['value']) ? gettype($data['value']) : 'not set',
            'value_content' => $data['value'] ?? 'not set',
        ]);

        // Filament might not capture the value from closure-based Groups
        // So we manually ensure it's present
        if (!isset($data['value'])) {
            $formState = $this->form->getState();
            \Log::info('Value not in data, checking form state:', ['form_state' => $formState]);

            if (isset($formState['value'])) {
                $data['value'] = $formState['value'];
            }
        }

        // Handle file uploads that return array of paths (Filament sometimes does this)
        if (isset($data['value']) && is_array($data['value'])) {
            \Log::info('Value is array, taking first element:', ['array' => $data['value']]);
            $data['value'] = $data['value'][0] ?? '';
        }

        \Log::info('EditSetting - After mutation:', ['final_value' => $data['value'] ?? 'not set']);

        return $data;
    }

    protected function fillForm(): void
    {
        $this->callHook('beforeFill');

        $data = $this->record->attributesToArray();

        // Explicitly ensure value is a plain string
        if (isset($data['value'])) {
            if (is_array($data['value']) || is_object($data['value'])) {
                $data['value'] = json_encode($data['value']);
            } else {
                $data['value'] = (string) $data['value'];
            }
        }

        $data = $this->mutateFormDataBeforeFill($data);

        $this->form->fill($data);

        $this->callHook('afterFill');
    }

    protected function afterSave(): void
    {
        Cache::forget('wave_settings');
    }
}
