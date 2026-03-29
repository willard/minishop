<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\BulkProductActionRequest;
use App\Models\Product;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Str;

class ProductBulkActionController extends Controller
{
    public function __invoke(BulkProductActionRequest $request): RedirectResponse
    {
        $this->authorize('viewAny', Product::class);

        $data = $request->validated();
        $ids = $data['product_ids'];
        $count = count($ids);

        match ($data['action']) {
            'delete' => Product::query()->whereIn('id', $ids)->get()->each->delete(),
            'activate' => Product::query()->whereIn('id', $ids)->update(['is_active' => true]),
            'deactivate' => Product::query()->whereIn('id', $ids)->update(['is_active' => false]),
            'assign_category' => Product::query()->whereIn('id', $ids)
                ->get()
                ->each(fn (Product $product) => $product->categories()->syncWithoutDetaching([$data['category_id']])),
            'update_stock' => Product::query()->whereIn('id', $ids)->update([
                'stock_quantity' => $data['stock_quantity'],
                'low_stock_notified' => false,
            ]),
            'update_price' => Product::query()->whereIn('id', $ids)->update(['price' => $data['price']]),
        };

        $message = match ($data['action']) {
            'delete' => "{$count} ".Str::plural('product', $count).' deleted successfully.',
            'activate' => "{$count} ".Str::plural('product', $count).' activated.',
            'deactivate' => "{$count} ".Str::plural('product', $count).' deactivated.',
            'assign_category' => "Category assigned to {$count} ".Str::plural('product', $count).'.',
            'update_stock' => "Stock updated for {$count} ".Str::plural('product', $count).'.',
            'update_price' => "Price updated for {$count} ".Str::plural('product', $count).'.',
        };

        return redirect()->route('admin.products.index')->with('success', $message);
    }
}
