<?php

namespace App\Actions\Inventory;

use App\Data\LowStockSubject;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\StoreSettings;
use App\Models\User;
use App\Notifications\LowStockAlert;
use Illuminate\Support\Facades\Notification;

class CheckLowStock
{
    public function execute(Product|ProductVariant $model): void
    {
        if (! $model->wasChanged('stock_quantity')) {
            return;
        }

        $threshold = $model->low_stock_threshold
            ?? StoreSettings::current()->low_stock_threshold;

        if ($threshold === null) {
            return;
        }

        if ($model->stock_quantity <= $threshold && ! $model->low_stock_notified) {
            // forceFill bypasses $fillable so low_stock_notified can be toggled
            // internally without exposing it to mass assignment from user input.
            $model->forceFill(['low_stock_notified' => true])->saveQuietly();

            $subject = $model instanceof ProductVariant
                ? LowStockSubject::fromVariant($model->loadMissing('product'))
                : LowStockSubject::fromProduct($model);

            Notification::send(
                User::role(['super-admin', 'admin'])
                    ->whereNotNull('email_verified_at')
                    ->get(),
                new LowStockAlert($subject),
            );
        }

        if ($model->stock_quantity > $threshold && $model->low_stock_notified) {
            $model->forceFill(['low_stock_notified' => false])->saveQuietly();
        }
    }
}
