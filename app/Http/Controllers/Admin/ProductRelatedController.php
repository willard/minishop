<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreProductRelatedRequest;
use App\Models\Product;
use Illuminate\Http\RedirectResponse;

class ProductRelatedController extends Controller
{
    public function store(StoreProductRelatedRequest $request, Product $product): RedirectResponse
    {
        $this->authorize('update', $product);

        $product->relatedProducts()->syncWithoutDetaching([$request->integer('related_product_id')]);

        return back()->with('success', 'Related product added.');
    }

    public function destroy(Product $product, Product $related): RedirectResponse
    {
        $this->authorize('update', $product);

        $product->relatedProducts()->detach($related->id);

        return back()->with('success', 'Related product removed.');
    }
}
