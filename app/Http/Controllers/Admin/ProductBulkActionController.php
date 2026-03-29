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
        $data = $request->validated();
        $ids = $data['product_ids'];
        $count = count($ids);
        $noun = Str::plural('product', $count);

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
            'delete' => "{$count} {$noun} deleted successfully.",
            'activate' => "{$count} {$noun} activated.",
            'deactivate' => "{$count} {$noun} deactivated.",
            'assign_category' => "Category assigned to {$count} {$noun}.",
            'update_stock' => "Stock updated for {$count} {$noun}.",
            'update_price' => "Price updated for {$count} {$noun}.",
        };

        return redirect()->route('admin.products.index')->with('success', $message);
    }
}
