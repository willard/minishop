<?php

namespace Tests\Feature\Storefront;

use App\Models\Category;
use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProductTest extends TestCase
{
    use RefreshDatabase;

    public function test_products_index_renders_successfully(): void
    {
        $this->get(route('storefront.products.index'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page->component('storefront/Products/Index'));
    }

    public function test_products_index_only_shows_active_products(): void
    {
        Product::factory(3)->create();
        Product::factory(2)->inactive()->create();

        $this->get(route('storefront.products.index'))
            ->assertInertia(fn ($page) => $page->has('products.data', 3));
    }

    public function test_products_index_can_filter_by_category_slug(): void
    {
        $category = Category::factory()->create(['slug' => 'apparel', 'is_active' => true]);
        $product = Product::factory()->create();
        $product->categories()->attach($category);
        Product::factory()->create();

        $this->get(route('storefront.products.index', ['category' => 'apparel']))
            ->assertInertia(fn ($page) => $page->has('products.data', 1));
    }

    public function test_products_index_can_search_by_name(): void
    {
        Product::factory()->create(['name' => 'Red Ceramic Mug']);
        Product::factory()->create(['name' => 'Blue T-Shirt']);

        $this->get(route('storefront.products.index', ['search' => 'Ceramic']))
            ->assertInertia(fn ($page) => $page->has('products.data', 1));
    }

    public function test_products_index_passes_categories_and_filters(): void
    {
        $this->get(route('storefront.products.index', ['category' => 'home', 'search' => 'mug']))
            ->assertOk()
            ->assertInertia(function ($page) {
                $page->has('categories')
                    ->where('filters.category', 'home')
                    ->where('filters.search', 'mug');
            });
    }

    public function test_product_show_renders_for_active_product(): void
    {
        $product = Product::factory()->create();

        $this->get(route('storefront.products.show', $product))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('storefront/Products/Show')
                ->where('product.slug', $product->slug)
            );
    }

    public function test_inactive_product_returns_404(): void
    {
        $product = Product::factory()->inactive()->create();

        $this->get(route('storefront.products.show', $product))
            ->assertNotFound();
    }

    public function test_nonexistent_product_returns_404(): void
    {
        $this->get(route('storefront.products.show', 'non-existent-slug'))
            ->assertNotFound();
    }
}
