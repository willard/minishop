<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreProductOptionRequest;
use App\Models\Product;
use App\Models\ProductOption;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class ProductOptionController extends Controller
{
    public function create(Product $product): Response
    {
        return Inertia::render('admin/Products/Options/Create', [
            'product' => $product,
        ]);
    }

    public function store(StoreProductOptionRequest $request, Product $product): RedirectResponse
    {
        $position = $product->options()->count();
        $option = $product->options()->create([
            'name' => $request->validated('name'),
            'position' => $position,
        ]);

        foreach ($request->validated('values') as $index => $value) {
            $option->values()->create([
                'value' => $value,
                'position' => $index,
            ]);
        }

        return redirect()->route('admin.products.show', $product)
            ->with('success', 'Option type added successfully.');
    }

    public function destroy(Product $product, ProductOption $option): RedirectResponse
    {
        $option->delete();

        return redirect()->route('admin.products.show', $product)
            ->with('success', 'Option type deleted.');
    }
}
