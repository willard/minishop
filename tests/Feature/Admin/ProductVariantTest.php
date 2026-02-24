<?php

namespace Tests\Feature\Admin;

use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProductVariantTest extends TestCase
{
    use RefreshDatabase;

    public function test_guests_are_redirected_from_create_form(): void
    {
        $product = Product::factory()->create();

        $this->get(route('admin.products.variants.create', $product))
            ->assertRedirect(route('login'));
    }

    public function test_guests_are_redirected_from_store(): void
    {
        $product = Product::factory()->create();

        $this->post(route('admin.products.variants.store', $product), [])
            ->assertRedirect(route('login'));
    }

    public function test_guests_are_redirected_from_edit_form(): void
    {
        $product = Product::factory()->create();
        $variant = ProductVariant::factory()->for($product)->create();

        $this->get(route('admin.products.variants.edit', [$product, $variant]))
            ->assertRedirect(route('login'));
    }

    public function test_authenticated_users_can_view_create_variant_form(): void
    {
        $user = User::factory()->create();
        $product = Product::factory()->create();

        $this->actingAs($user)
            ->get(route('admin.products.variants.create', $product))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('admin/Products/Variants/Create')
                ->has('product')
            );
    }

    public function test_authenticated_users_can_view_edit_variant_form(): void
    {
        $user = User::factory()->create();
        $product = Product::factory()->create();
        $variant = ProductVariant::factory()->for($product)->create();

        $this->actingAs($user)
            ->get(route('admin.products.variants.edit', [$product, $variant]))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('admin/Products/Variants/Edit')
                ->has('product')
                ->has('variant')
            );
    }

    public function test_authenticated_users_can_store_a_variant(): void
    {
        $user = User::factory()->create();
        $product = Product::factory()->create();

        $this->actingAs($user)
            ->post(route('admin.products.variants.store', $product), [
                'options' => [
                    ['name' => 'Size', 'value' => 'M'],
                ],
                'stock_quantity' => 10,
                'is_active' => true,
            ])
            ->assertRedirect(route('admin.products.show', $product));

        $this->assertDatabaseHas('product_variants', [
            'product_id' => $product->id,
            'stock_quantity' => 10,
        ]);

        $variant = ProductVariant::query()->where('product_id', $product->id)->first();
        $this->assertEquals(['Size' => 'M'], $variant->options);
    }

    public function test_store_transforms_options_into_keyed_object(): void
    {
        $user = User::factory()->create();
        $product = Product::factory()->create();

        $this->actingAs($user)
            ->post(route('admin.products.variants.store', $product), [
                'options' => [
                    ['name' => 'Size', 'value' => 'L'],
                    ['name' => 'Color', 'value' => 'Blue'],
                ],
                'stock_quantity' => 5,
            ]);

        $variant = ProductVariant::query()->where('product_id', $product->id)->first();
        $this->assertEquals(['Size' => 'L', 'Color' => 'Blue'], $variant->options);
    }

    public function test_store_requires_stock_quantity(): void
    {
        $user = User::factory()->create();
        $product = Product::factory()->create();

        $this->actingAs($user)
            ->post(route('admin.products.variants.store', $product), [
                'options' => [['name' => 'Size', 'value' => 'S']],
            ])
            ->assertSessionHasErrors('stock_quantity');
    }

    public function test_store_requires_at_least_one_option(): void
    {
        $user = User::factory()->create();
        $product = Product::factory()->create();

        $this->actingAs($user)
            ->post(route('admin.products.variants.store', $product), [
                'options' => [],
                'stock_quantity' => 5,
            ])
            ->assertSessionHasErrors('options');
    }

    public function test_store_rejects_more_than_three_options(): void
    {
        $user = User::factory()->create();
        $product = Product::factory()->create();

        $this->actingAs($user)
            ->post(route('admin.products.variants.store', $product), [
                'options' => [
                    ['name' => 'A', 'value' => '1'],
                    ['name' => 'B', 'value' => '2'],
                    ['name' => 'C', 'value' => '3'],
                    ['name' => 'D', 'value' => '4'],
                ],
                'stock_quantity' => 5,
            ])
            ->assertSessionHasErrors('options');
    }

    public function test_store_rejects_duplicate_sku(): void
    {
        $user = User::factory()->create();
        $product = Product::factory()->create();
        ProductVariant::factory()->for($product)->create(['sku' => 'UNIQUE-SKU']);

        $this->actingAs($user)
            ->post(route('admin.products.variants.store', $product), [
                'options' => [['name' => 'Size', 'value' => 'M']],
                'stock_quantity' => 5,
                'sku' => 'UNIQUE-SKU',
            ])
            ->assertSessionHasErrors('sku');
    }

    public function test_authenticated_users_can_update_a_variant(): void
    {
        $user = User::factory()->create();
        $product = Product::factory()->create();
        $variant = ProductVariant::factory()->for($product)->create([
            'stock_quantity' => 5,
            'options' => ['Size' => 'S'],
        ]);

        $this->actingAs($user)
            ->put(route('admin.products.variants.update', [$product, $variant]), [
                'options' => [['name' => 'Size', 'value' => 'L']],
                'stock_quantity' => 20,
                'is_active' => true,
            ])
            ->assertRedirect(route('admin.products.show', $product));

        $this->assertDatabaseHas('product_variants', [
            'id' => $variant->id,
            'stock_quantity' => 20,
        ]);

        $variant->refresh();
        $this->assertEquals(['Size' => 'L'], $variant->options);
    }

    public function test_update_ignores_own_sku_uniqueness(): void
    {
        $user = User::factory()->create();
        $product = Product::factory()->create();
        $variant = ProductVariant::factory()->for($product)->create(['sku' => 'MY-SKU']);

        $this->actingAs($user)
            ->put(route('admin.products.variants.update', [$product, $variant]), [
                'options' => [['name' => 'Size', 'value' => 'M']],
                'stock_quantity' => 5,
                'sku' => 'MY-SKU',
            ])
            ->assertSessionDoesntHaveErrors('sku');
    }

    public function test_authenticated_users_can_delete_a_variant(): void
    {
        $user = User::factory()->create();
        $product = Product::factory()->create();
        $variant = ProductVariant::factory()->for($product)->create();

        $this->actingAs($user)
            ->delete(route('admin.products.variants.destroy', [$product, $variant]))
            ->assertRedirect(route('admin.products.show', $product));

        $this->assertDatabaseMissing('product_variants', ['id' => $variant->id]);
    }

    public function test_variant_scoped_to_parent_product(): void
    {
        $user = User::factory()->create();
        $productA = Product::factory()->create();
        $productB = Product::factory()->create();
        $variantOfB = ProductVariant::factory()->for($productB)->create();

        $this->actingAs($user)
            ->get(route('admin.products.variants.edit', [$productA, $variantOfB]))
            ->assertNotFound();
    }
}
