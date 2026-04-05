<?php

namespace App\Observers;

use App\Actions\Inventory\CheckLowStock;
use App\Models\ProductVariant;

class ProductVariantObserver
{
    public function updated(ProductVariant $variant): void
    {
        app(CheckLowStock::class)->execute($variant);
    }
}
