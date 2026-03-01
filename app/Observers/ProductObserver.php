<?php

namespace App\Observers;

use App\Models\ActivityLog;
use App\Models\Product;
use Illuminate\Support\Facades\Auth;

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
