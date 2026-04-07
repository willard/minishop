<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreBundleItemRequest;
use App\Http\Requests\Admin\UpdateBundleItemRequest;
use App\Models\BundleItem;
use App\Models\Product;
use Illuminate\Http\RedirectResponse;

class BundleItemController extends Controller
{
    public function store(StoreBundleItemRequest $request, Product $product): RedirectResponse
    {
        $this->authorize('update', $product);

        $product->bundleItems()->create($request->safe()->only([
            'component_product_id',
            'component_variant_id',
            'quantity',
            'sort_order',
        ]));

        return back()->with('success', 'Component added to bundle.');
    }

    public function update(UpdateBundleItemRequest $request, Product $product, BundleItem $bundleItem): RedirectResponse
    {
        $this->authorize('update', $product);

        $bundleItem->update($request->safe()->only(['quantity', 'sort_order']));

        return back()->with('success', 'Bundle component updated.');
    }

    public function destroy(Product $product, BundleItem $bundleItem): RedirectResponse
    {
        $this->authorize('update', $product);

        $bundleItem->delete();

        return back()->with('success', 'Component removed from bundle.');
    }
}
