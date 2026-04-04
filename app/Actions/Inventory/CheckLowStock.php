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
            // No threshold configured — can't determine low-stock state.
            // Still reset a stale flag if stock was explicitly changed.
            if ($model->low_stock_notified) {
                $model->newQueryWithoutScopes()
                    ->where($model->getKeyName(), $model->getKey())
                    ->update(['low_stock_notified' => false]);
            }

            return;
        }

        if ($model->stock_quantity <= $threshold && ! $model->low_stock_notified) {
            // Use a direct query to set the flag — avoids Eloquent's fill/dirty
            // tracking and bypasses $fillable without re-triggering the observer.
            $model->newQueryWithoutScopes()
                ->where($model->getKeyName(), $model->getKey())
                ->update(['low_stock_notified' => true]);

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
            $model->newQueryWithoutScopes()
                ->where($model->getKeyName(), $model->getKey())
                ->update(['low_stock_notified' => false]);
        }
    }
}
