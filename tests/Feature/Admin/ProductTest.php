<?php

namespace Tests\Feature\Admin;

use App\Models\Category;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProductTest extends TestCase
{
    use RefreshDatabase;

    public function test_guests_are_redirected_when_accessing_admin_products(): void
    {
        $this->get(route('admin.products.index'))->assertRedirect(route('login'));
    }

    public function test_authenticated_users_can_view_products_index(): void
    {
        $user = User::factory()->create();
        Product::factory(3)->create();

        $this->actingAs($user)
            ->get(route('admin.products.index'))
            ->assertOk();
    }

    public function test_authenticated_users_can_view_create_product_form(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get(route('admin.products.create'))
            ->assertOk();
    }

    public function test_authenticated_users_can_store_a_product(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->post(route('admin.products.store'), [
                'name' => 'Test Product',
                'price' => 1999,
                'stock_quantity' => 10,
                'is_active' => true,
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('products', ['name' => 'Test Product', 'price' => 1999]);
    }

    public function test_store_product_requires_name(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->post(route('admin.products.store'), ['price' => 1999])
            ->assertSessionHasErrors('name');
    }

    public function test_store_product_requires_valid_price(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->post(route('admin.products.store'), ['name' => 'Test', 'price' => -1])
            ->assertSessionHasErrors('price');
    }

    public function test_store_product_validates_sku_is_unique(): void
    {
        $user = User::factory()->create();
        Product::factory()->create(['sku' => 'SKU-001']);

        $this->actingAs($user)
            ->post(route('admin.products.store'), [
                'name' => 'Another Product',
                'price' => 500,
                'sku' => 'SKU-001',
            ])
            ->assertSessionHasErrors('sku');
    }

    public function test_compare_price_must_be_greater_than_price(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->post(route('admin.products.store'), [
                'name' => 'Test Product',
                'price' => 1000,
                'compare_price' => 500,
            ])
            ->assertSessionHasErrors('compare_price');
    }

    public function test_authenticated_users_can_view_a_product(): void
    {
        $user = User::factory()->create();
        $product = Product::factory()->create();

        $this->actingAs($user)
            ->get(route('admin.products.show', $product))
            ->assertOk();
    }

    public function test_authenticated_users_can_view_edit_product_form(): void
    {
        $user = User::factory()->create();
        $product = Product::factory()->create();

        $this->actingAs($user)
            ->get(route('admin.products.edit', $product))
            ->assertOk();
    }

    public function test_authenticated_users_can_update_a_product(): void
    {
        $user = User::factory()->create();
        $product = Product::factory()->create(['name' => 'Old Name', 'price' => 1000]);

        $this->actingAs($user)
            ->put(route('admin.products.update', $product), [
                'name' => 'New Name',
                'price' => 2000,
                'stock_quantity' => 5,
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('products', ['id' => $product->id, 'name' => 'New Name', 'price' => 2000]);
    }

    public function test_update_product_ignores_own_sku_uniqueness(): void
    {
        $user = User::factory()->create();
        $product = Product::factory()->create(['sku' => 'MYSKU-001', 'price' => 1000]);

        $this->actingAs($user)
            ->put(route('admin.products.update', $product), [
                'name' => $product->name,
                'price' => 1000,
                'sku' => 'MYSKU-001',
            ])
            ->assertSessionDoesntHaveErrors('sku');
    }

    public function test_authenticated_users_can_delete_a_product(): void
    {
        $user = User::factory()->create();
        $product = Product::factory()->create();

        $this->actingAs($user)
            ->delete(route('admin.products.destroy', $product))
            ->assertRedirect(route('admin.products.index'));

        $this->assertDatabaseMissing('products', ['id' => $product->id]);
    }

    public function test_store_product_syncs_categories(): void
    {
        $user = User::factory()->create();
        $categories = Category::factory(2)->create();

        $this->actingAs($user)
            ->post(route('admin.products.store'), [
                'name' => 'Test Product',
                'price' => 1000,
                'category_ids' => $categories->pluck('id')->toArray(),
            ]);

        $product = Product::query()->where('name', 'Test Product')->first();

        $this->assertCount(2, $product->categories);
    }
}
