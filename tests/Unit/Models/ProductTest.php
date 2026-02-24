<?php

namespace Tests\Unit\Models;

use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProductTest extends TestCase
{
    use RefreshDatabase;

    public function test_slug_is_auto_generated_from_name_on_create(): void
    {
        $product = Product::factory()->create(['name' => 'My Awesome Product', 'slug' => '']);

        $this->assertSame('my-awesome-product', $product->slug);
    }

    public function test_slug_is_not_overwritten_on_update(): void
    {
        $product = Product::factory()->create(['name' => 'Original Name']);
        $originalSlug = $product->slug;

        $product->update(['name' => 'Updated Name']);

        $this->assertSame($originalSlug, $product->fresh()->slug);
    }

    public function test_out_of_stock_state_sets_stock_quantity_to_zero(): void
    {
        $product = Product::factory()->outOfStock()->create();

        $this->assertSame(0, $product->stock_quantity);
    }

    public function test_inactive_state_sets_is_active_to_false(): void
    {
        $product = Product::factory()->inactive()->create();

        $this->assertFalse($product->is_active);
    }

    public function test_route_key_name_is_slug(): void
    {
        $product = new Product;

        $this->assertSame('slug', $product->getRouteKeyName());
    }
}
