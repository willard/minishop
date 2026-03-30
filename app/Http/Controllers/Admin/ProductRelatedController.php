<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Product;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class ProductRelatedController extends Controller
{
    public function store(Request $request, Product $product): RedirectResponse
    {
        $this->authorize('update', $product);

        $request->validate([
            'related_product_id' => ['required', 'integer', 'exists:products,id', 'different:product_id'],
        ]);

        $relatedId = (int) $request->input('related_product_id');

        if ($relatedId === $product->id) {
            return back()->withErrors(['related_product_id' => 'A product cannot be related to itself.']);
        }

        $product->relatedProducts()->syncWithoutDetaching([$relatedId]);

        return back()->with('success', 'Related product added.');
    }

    public function destroy(Product $product, Product $related): RedirectResponse
    {
        $this->authorize('update', $product);

        $product->relatedProducts()->detach($related->id);

        return back()->with('success', 'Related product removed.');
    }
}
