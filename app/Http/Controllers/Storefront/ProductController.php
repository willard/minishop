<?php

namespace App\Http\Controllers\Storefront;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Product;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ProductController extends Controller
{
    public function index(Request $request): Response
    {
        $products = Product::query()
            ->where('is_active', true)
            ->with(['categories', 'images', 'options.values', 'variants.optionValues', 'variants.images'])
            ->when($request->filled('category'), function ($query) use ($request) {
                $query->whereHas('categories', fn ($q) => $q->where('slug', $request->string('category')));
            })
            ->when($request->filled('search'), function ($query) use ($request) {
                $query->where('name', 'like', '%'.$request->string('search').'%');
            })
            ->paginate(24)
            ->withQueryString();

        $categories = Category::query()
            ->where('is_active', true)
            ->whereNull('parent_id')
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();

        return Inertia::render('storefront/Products/Index', [
            'products' => $products,
            'categories' => $categories,
            'filters' => $request->only(['category', 'search']),
        ]);
    }

    public function show(Product $product): Response
    {
        abort_unless($product->is_active, 404);

        $product->load([
            'categories',
            'images',
            'options.values',
            'variants.optionValues',
            'variants.images',
            'relatedProducts' => function ($query): void {
                $query->where('is_active', true)->with('images')->limit(8);
            },
        ]);

        if ($product->isBundled()) {
            $product->load(['bundleItems.componentProduct.images', 'bundleItems.componentVariant.optionValues.option']);
        }

        return Inertia::render('storefront/Products/Show', [
            'product' => $product,
            'in_stock' => $product->getEffectiveStock() > 0,
        ]);
    }
}
