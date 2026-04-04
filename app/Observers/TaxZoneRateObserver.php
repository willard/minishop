<?php

namespace App\Observers;

use App\Models\TaxZoneRate;
use Illuminate\Support\Facades\Cache;

class TaxZoneRateObserver
{
    /**
     * Flush all cached tax zone lookups when a rate is saved or deleted.
     * Uses tagged cache so all zone entries are invalidated atomically.
     */
    public function saved(TaxZoneRate $taxZoneRate): void
    {
        Cache::tags(['tax-zones'])->flush();
    }

    public function deleted(TaxZoneRate $taxZoneRate): void
    {
        Cache::tags(['tax-zones'])->flush();
    }
}
