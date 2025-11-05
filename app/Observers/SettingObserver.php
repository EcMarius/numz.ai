<?php

namespace App\Observers;

use Wave\Setting;
use Illuminate\Support\Facades\Cache;

class SettingObserver
{
    /**
     * Clear settings cache after any change
     */
    private function clearSettingsCache(): void
    {
        Cache::forget('wave_settings');
        Cache::forget('wave_settings_types');
    }

    /**
     * Handle the Setting "created" event.
     */
    public function created(Setting $setting): void
    {
        $this->clearSettingsCache();
    }

    /**
     * Handle the Setting "updated" event.
     */
    public function updated(Setting $setting): void
    {
        $this->clearSettingsCache();
    }

    /**
     * Handle the Setting "deleted" event.
     */
    public function deleted(Setting $setting): void
    {
        $this->clearSettingsCache();
    }

    /**
     * Handle the Setting "restored" event.
     */
    public function restored(Setting $setting): void
    {
        $this->clearSettingsCache();
    }

    /**
     * Handle the Setting "force deleted" event.
     */
    public function forceDeleted(Setting $setting): void
    {
        $this->clearSettingsCache();
    }
}
