<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreProductVariantRequest;
use App\Http\Requests\Admin\UpdateProductVariantRequest;
use App\Models\Product;
use App\Models\ProductVariant;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class ProductVariantController extends Controller
{
    public function create(Product $product): Response
    {
        return Inertia::render('admin/Products/Variants/Create', [
            'product' => $product,
            'optionTypes' => $product->options()->with('values')->get(),
        ]);
    }

    public function store(StoreProductVariantRequest $request, Product $product): RedirectResponse
    {
        $data = $request->validated();
        $optionValueIds = $data['option_value_ids'];
        unset($data['option_value_ids']);

        $variant = $product->variants()->create($data);
        $variant->optionValues()->sync($optionValueIds);

        return redirect()->route('admin.products.show', $product)
            ->with('success', 'Variant added successfully.');
    }

    public function edit(Product $product, ProductVariant $variant): Response
    {
        return Inertia::render('admin/Products/Variants/Edit', [
            'product' => $product,
            'variant' => $variant->load('optionValues'),
            'optionTypes' => $product->options()->with('values')->get(),
        ]);
    }

    public function update(UpdateProductVariantRequest $request, Product $product, ProductVariant $variant): RedirectResponse
    {
        $data = $request->validated();
        $optionValueIds = $data['option_value_ids'];
        unset($data['option_value_ids']);

        $variant->update($data);
        $variant->optionValues()->sync($optionValueIds);

        return redirect()->route('admin.products.show', $product)
            ->with('success', 'Variant updated successfully.');
    }

    public function destroy(Product $product, ProductVariant $variant): RedirectResponse
    {
        $variant->delete();

        return redirect()->route('admin.products.show', $product)
            ->with('success', 'Variant deleted successfully.');
    }
}
