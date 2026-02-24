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
        ]);
    }

    public function store(StoreProductVariantRequest $request, Product $product): RedirectResponse
    {
        $data = $request->validated();
        $data['options'] = $this->transformOptions($data['options']);
        $product->variants()->create($data);

        return redirect()->route('admin.products.show', $product)
            ->with('success', 'Variant added successfully.');
    }

    public function edit(Product $product, ProductVariant $variant): Response
    {
        return Inertia::render('admin/Products/Variants/Edit', [
            'product' => $product,
            'variant' => $variant,
        ]);
    }

    public function update(UpdateProductVariantRequest $request, Product $product, ProductVariant $variant): RedirectResponse
    {
        $data = $request->validated();
        $data['options'] = $this->transformOptions($data['options']);
        $variant->update($data);

        return redirect()->route('admin.products.show', $product)
            ->with('success', 'Variant updated successfully.');
    }

    public function destroy(Product $product, ProductVariant $variant): RedirectResponse
    {
        $variant->delete();

        return redirect()->route('admin.products.show', $product)
            ->with('success', 'Variant deleted successfully.');
    }

    /**
     * Transform [{name: 'Size', value: 'M'}] → {"Size": "M"}
     *
     * @param  array<int, array{name: string, value: string}>  $options
     * @return array<string, string>
     */
    private function transformOptions(array $options): array
    {
        $result = [];
        foreach ($options as $option) {
            $result[$option['name']] = $option['value'];
        }

        return $result;
    }
}
