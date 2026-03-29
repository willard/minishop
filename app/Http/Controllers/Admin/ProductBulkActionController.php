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

        $products = Product::query()->whereIn('id', $data['product_ids'])->get();
        $count = $products->count();
        $noun = Str::plural('product', $count);

        match ($data['action']) {
            'delete' => $products->each->delete(),
            'activate' => $products->each(fn (Product $product) => $product->update(['is_active' => true])),
            'deactivate' => $products->each(fn (Product $product) => $product->update(['is_active' => false])),
            'assign_category' => $products->each(fn (Product $product) => $product->categories()->syncWithoutDetaching([$data['category_id']])),
            'update_stock' => $products->each(fn (Product $product) => $product->update(['stock_quantity' => $data['stock_quantity']])),
            'update_price' => $products->each(fn (Product $product) => $product->update(['price' => $data['price']])),
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
