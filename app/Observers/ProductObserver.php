<?php

namespace App\Observers;

use App\Models\ActivityLog;
use App\Models\Product;
use App\Models\StoreSettings;
use App\Models\User;
use App\Notifications\LowStockAlert;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;

class ProductObserver
{
    public function created(Product $product): void
    {
        ActivityLog::query()->create([
            'user_id' => Auth::id(),
            'action' => 'created',
            'subject_type' => 'Product',
            'subject_id' => $product->id,
            'description' => "Created product \"{$product->name}\"",
            'properties' => null,
        ]);
    }

    public function updated(Product $product): void
    {
        $changed = $product->getChanges();
        unset($changed['updated_at']);

        if (empty($changed)) {
            return;
        }

        ActivityLog::query()->create([
            'user_id' => Auth::id(),
            'action' => 'updated',
            'subject_type' => 'Product',
            'subject_id' => $product->id,
            'description' => "Updated product \"{$product->name}\"",
            'properties' => $changed,
        ]);

        $this->checkLowStock($product);
    }

    private function checkLowStock(Product $product): void
    {
        if (! $product->wasChanged('stock_quantity')) {
            return;
        }

        $threshold = StoreSettings::current()->low_stock_threshold;

        if ($product->stock_quantity <= $threshold && ! $product->low_stock_notified) {
            $product->updateQuietly(['low_stock_notified' => true]);

            Notification::send(User::whereDoesntHave('customer')->get(), new LowStockAlert($product));
        }

        if ($product->stock_quantity > $threshold && $product->low_stock_notified) {
            $product->updateQuietly(['low_stock_notified' => false]);
        }
    }

    public function deleting(Product $product): void
    {
        foreach ($product->images as $image) {
            Storage::disk('public')->delete($image->path);
        }
    }

    public function deleted(Product $product): void
    {
        ActivityLog::query()->create([
            'user_id' => Auth::id(),
            'action' => 'deleted',
            'subject_type' => 'Product',
            'subject_id' => $product->id,
            'description' => "Deleted product \"{$product->name}\"",
            'properties' => null,
        ]);
    }
}
