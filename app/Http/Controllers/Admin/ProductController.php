<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreProductRequest;
use App\Http\Requests\Admin\UpdateProductRequest;
use App\Models\Category;
use App\Models\Product;
use App\Models\StoreSettings;
use App\Models\Tag;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response as HttpResponse;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ProductController extends Controller
{
    private const ALLOWED_SORTS = ['name', 'sku', 'price', 'stock_quantity', 'is_active', 'created_at'];

    public function index(Request $request): Response
    {
        $this->authorize('viewAny', Product::class);

        $filters = $request->only(['search', 'category_id', 'tag_id', 'stock', 'type', 'sort_by', 'sort_dir']);

        $products = $this->buildProductQuery($filters)
            ->paginate(20)
            ->withQueryString();

        $categories = Category::query()
            ->where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name']);

        $tags = Tag::query()
            ->where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name']);

        return Inertia::render('admin/Products/Index', [
            'products' => $products,
            'filters' => $filters,
            'categories' => $categories,
            'tags' => $tags,
        ]);
    }

    public function export(Request $request): HttpResponse|StreamedResponse
    {
        $this->authorize('viewAny', Product::class);

        $filters = $request->only(['search', 'category_id', 'tag_id', 'stock', 'type', 'sort_by', 'sort_dir']);
        $format = $request->input('format', 'csv');

        $products = $this->buildProductQuery($filters)->get();

        if ($format === 'pdf') {
            return response()->view('admin.products.export-pdf', compact('products'))
                ->header('Content-Type', 'text/html');
        }

        $filename = 'products-'.now()->format('Y-m-d').'.csv';

        $callback = function () use ($products): void {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, ['Name', 'SKU', 'Price', 'Stock', 'Status', 'Categories', 'Tags']);

            foreach ($products as $product) {
                fputcsv($handle, [
                    $product->name,
                    $product->sku ?? '',
                    number_format($product->price / 100, 2),
                    $product->stock_quantity,
                    $product->is_active ? 'Active' : 'Inactive',
                    $product->categories->pluck('name')->join(', '),
                    $product->tags->pluck('name')->join(', '),
                ]);
            }

            fclose($handle);
        };

        return response()->stream($callback, 200, [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ]);
    }

    public function create(): Response
    {
        $this->authorize('create', Product::class);

        $categories = Category::query()
            ->where('is_active', true)
            ->orderBy('name')
            ->get();

        $tags = Tag::query()
            ->where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name', 'color']);

        return Inertia::render('admin/Products/Create', [
            'categories' => $categories,
            'tags' => $tags,
        ]);
    }

    public function store(StoreProductRequest $request): RedirectResponse
    {
        $this->authorize('create', Product::class);

        $data = $request->validated();
        $data['is_active'] = $request->boolean('is_active', true);
        $data['slug'] = $this->uniqueSlug($data['name']);

        $product = Product::query()->create($data);
        $product->categories()->sync($request->input('category_ids', []));
        $product->tags()->sync($request->input('tag_ids', []));

        return redirect()->route('admin.products.show', $product)
            ->with('success', 'Product created successfully.');
    }

    public function show(Product $product): Response
    {
        $this->authorize('view', $product);

        $product->load(['categories', 'tags', 'images', 'options.values', 'variants.optionValues.option', 'variants.images', 'relatedProducts.images']);

        if ($product->isBundled()) {
            $product->load(['bundleItems.componentProduct.images', 'bundleItems.componentVariant.optionValues.option']);
        }

        $availableProducts = Product::query()
            ->where('id', '!=', $product->id)
            ->where('is_active', true)
            ->where('type', '!=', 'bundled')
            ->orderBy('name')
            ->get(['id', 'name', 'slug', 'type']);

        return Inertia::render('admin/Products/Show', [
            'product' => $product,
            'availableProducts' => $availableProducts,
            'effective_stock' => $product->getEffectiveStock(),
        ]);
    }

    public function edit(Product $product): Response
    {
        $this->authorize('update', $product);

        $product->load('categories', 'tags');

        $categories = Category::query()
            ->where('is_active', true)
            ->orderBy('name')
            ->get();

        $tags = Tag::query()
            ->where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name', 'color']);

        return Inertia::render('admin/Products/Edit', [
            'product' => $product,
            'categories' => $categories,
            'tags' => $tags,
        ]);
    }

    public function update(UpdateProductRequest $request, Product $product): RedirectResponse
    {
        $this->authorize('update', $product);

        $data = $request->validated();
        $data['is_active'] = $request->has('is_active') ? $request->boolean('is_active') : $product->is_active;
        unset($data['category_ids'], $data['tag_ids']);

        $product->update($data);
        $product->categories()->sync($request->input('category_ids', []));
        $product->tags()->sync($request->input('tag_ids', []));

        return redirect()->route('admin.products.show', $product)
            ->with('success', 'Product updated successfully.');
    }

    public function destroy(Product $product): RedirectResponse
    {
        $this->authorize('delete', $product);

        $product->delete();

        return redirect()->route('admin.products.index')
            ->with('success', 'Product deleted successfully.');
    }

    private function buildProductQuery(array $filters): Builder
    {
        $sortBy = in_array($filters['sort_by'] ?? null, self::ALLOWED_SORTS)
            ? $filters['sort_by']
            : 'created_at';
        $sortDir = ($filters['sort_dir'] ?? 'desc') === 'asc' ? 'asc' : 'desc';

        return Product::query()
            ->with('categories', 'tags')
            ->when($filters['search'] ?? null, function ($query, $search): void {
                $query->where(function ($q) use ($search): void {
                    $q->where('name', 'like', "%{$search}%")
                        ->orWhere('sku', 'like', "%{$search}%");
                });
            })
            ->when($filters['category_id'] ?? null, function ($query, $categoryId): void {
                $query->whereHas('categories', fn ($q) => $q->where('categories.id', $categoryId));
            })
            ->when($filters['tag_id'] ?? null, function ($query, $tagId): void {
                $query->whereHas('tags', fn ($q) => $q->where('tags.id', $tagId));
            })
            ->when($filters['type'] ?? null, function ($query, $type): void {
                $query->where('type', $type);
            })
            ->when($filters['stock'] ?? null, function ($query, $stock): void {
                // Exclude bundled products from stock filters — their stock is calculated
                $query->where('type', '!=', 'bundled');

                if ($stock === 'in_stock') {
                    $query->where('stock_quantity', '>', 0);
                } elseif ($stock === 'out_of_stock') {
                    $query->where('stock_quantity', 0);
                } elseif ($stock === 'low_stock') {
                    $threshold = StoreSettings::current()->low_stock_threshold;
                    if ($threshold !== null) {
                        $query->where(function ($q) use ($threshold): void {
                            $q->where(function ($q) use ($threshold): void {
                                $q->whereDoesntHave('variants')
                                    ->where('stock_quantity', '<=', $threshold)
                                    ->where('stock_quantity', '>', 0);
                            })->orWhere(function ($q) use ($threshold): void {
                                $q->whereHas('variants', fn ($v) => $v->where('stock_quantity', '<=', $threshold)->where('stock_quantity', '>', 0));
                            });
                        });
                    }
                }
            })
            ->orderBy($sortBy, $sortDir);
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
