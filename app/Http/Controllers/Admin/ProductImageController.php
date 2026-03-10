<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\ReorderProductImagesRequest;
use App\Http\Requests\Admin\StoreProductImageRequest;
use App\Models\Product;
use App\Models\ProductImage;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class ProductImageController extends Controller
{
    public function store(StoreProductImageRequest $request, Product $product): RedirectResponse
    {
        Gate::authorize('products.update');

        $files = $request->file('images');
        $variantId = $request->integer('variant_id') ?: null;

        $maxSortOrder = ProductImage::query()
            ->where('product_id', $product->id)
            ->where('variant_id', $variantId)
            ->max('sort_order') ?? -1;

        foreach ($files as $file) {
            $filename = Str::uuid().'.'.$file->getClientOriginalExtension();
            $path = $file->storeAs("products/{$product->id}", $filename, 'public');

            ProductImage::create([
                'product_id' => $product->id,
                'variant_id' => $variantId,
                'path' => $path,
                'alt_text' => $request->input('alt_text'),
                'sort_order' => ++$maxSortOrder,
            ]);
        }

        return redirect()->route('admin.products.show', $product)
            ->with('success', 'Images uploaded successfully.');
    }

    public function destroy(Product $product, ProductImage $image): RedirectResponse
    {
        Gate::authorize('products.update');

        if ($image->product_id !== $product->id) {
            abort(404);
        }

        Storage::disk('public')->delete($image->path);
        $image->delete();

        return redirect()->route('admin.products.show', $product)
            ->with('success', 'Image deleted successfully.');
    }

    public function reorder(ReorderProductImagesRequest $request, Product $product): RedirectResponse
    {
        Gate::authorize('products.update');

        foreach ($request->validated('image_ids') as $index => $imageId) {
            ProductImage::query()
                ->where('id', $imageId)
                ->where('product_id', $product->id)
                ->update(['sort_order' => $index]);
        }

        return redirect()->route('admin.products.show', $product)
            ->with('success', 'Image order updated.');
    }
}
