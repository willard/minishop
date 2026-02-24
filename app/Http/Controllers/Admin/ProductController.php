<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreProductRequest;
use App\Http\Requests\Admin\UpdateProductRequest;
use App\Models\Category;
use App\Models\Product;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

class ProductController extends Controller
{
    public function index(): Response
    {
        $products = Product::query()
            ->with('categories')
            ->orderByDesc('created_at')
            ->paginate(20);

        return Inertia::render('admin/Products/Index', [
            'products' => $products,
        ]);
    }

    public function create(): Response
    {
        $categories = Category::query()
            ->where('is_active', true)
            ->orderBy('name')
            ->get();

        return Inertia::render('admin/Products/Create', [
            'categories' => $categories,
        ]);
    }

    public function store(StoreProductRequest $request): RedirectResponse
    {
        $data = $request->validated();
        $data['is_active'] = $request->boolean('is_active', true);
        $data['slug'] = $this->uniqueSlug($data['name']);

        $product = Product::query()->create($data);
        $product->categories()->sync($request->input('category_ids', []));

        return redirect()->route('admin.products.show', $product)
            ->with('success', 'Product created successfully.');
    }

    public function show(Product $product): Response
    {
        $product->load(['categories', 'images', 'options.values', 'variants.optionValues.option']);

        return Inertia::render('admin/Products/Show', [
            'product' => $product,
        ]);
    }

    public function edit(Product $product): Response
    {
        $product->load('categories');

        $categories = Category::query()
            ->where('is_active', true)
            ->orderBy('name')
            ->get();

        return Inertia::render('admin/Products/Edit', [
            'product' => $product,
            'categories' => $categories,
        ]);
    }

    public function update(UpdateProductRequest $request, Product $product): RedirectResponse
    {
        $data = $request->validated();
        $data['is_active'] = $request->boolean('is_active', true);
        unset($data['category_ids']);

        $product->update($data);
        $product->categories()->sync($request->input('category_ids', []));

        return redirect()->route('admin.products.show', $product)
            ->with('success', 'Product updated successfully.');
    }

    public function destroy(Product $product): RedirectResponse
    {
        $product->delete();

        return redirect()->route('admin.products.index')
            ->with('success', 'Product deleted successfully.');
    }

    private function uniqueSlug(string $name): string
    {
        $slug = Str::slug($name);
        $original = $slug;
        $count = 2;

        while (Product::query()->where('slug', $slug)->exists()) {
            $slug = "{$original}-{$count}";
            $count++;
        }

        return $slug;
    }
}
