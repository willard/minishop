<?php

namespace App\Observers;

use App\Models\StoreSettings;
use Illuminate\Support\Facades\Cache;

class StoreSettingsObserver
{
    /**
     * Flush the cached StoreSettings when the record is saved.
     */
    public function saved(StoreSettings $storeSettings): void
    {
        Cache::forget('store_settings');
    }
}
