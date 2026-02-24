<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\ProductCollection;
use App\Http\Resources\ProductResource;
use App\Models\Product;

class ProductController extends Controller
{
    public function index(): ProductCollection
    {
        $products = Product::query()
            ->where('is_active', true)
            ->with(['categories', 'images'])
            ->paginate(20);

        return new ProductCollection($products);
    }

    public function show(Product $product): ProductResource
    {
        abort_unless($product->is_active, 404);

        $product->load(['categories', 'images']);

        return new ProductResource($product);
    }
}
