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

    public function test_products_index_passes_filters_and_categories_as_props(): void
    {
        $user = User::factory()->create();
        Category::factory()->create(['name' => 'Apparel']);

        $this->actingAs($user)
            ->get(route('admin.products.index'))
            ->assertInertia(fn ($page) => $page
                ->has('filters')
                ->has('categories')
            );
    }

    public function test_products_index_can_be_searched_by_name(): void
    {
        $user = User::factory()->create();
        Product::factory()->create(['name' => 'Red Sneakers']);
        Product::factory()->create(['name' => 'Blue Jeans']);

        $this->actingAs($user)
            ->get(route('admin.products.index', ['search' => 'Sneakers']))
            ->assertInertia(fn ($page) => $page
                ->component('admin/Products/Index')
                ->has('products.data', 1)
                ->where('products.data.0.name', 'Red Sneakers')
            );
    }

    public function test_products_index_can_be_searched_by_sku(): void
    {
        $user = User::factory()->create();
        Product::factory()->create(['name' => 'Widget A', 'sku' => 'SKU-WIDGET-001']);
        Product::factory()->create(['name' => 'Widget B', 'sku' => 'SKU-OTHER-002']);

        $this->actingAs($user)
            ->get(route('admin.products.index', ['search' => 'WIDGET-001']))
            ->assertInertia(fn ($page) => $page
                ->has('products.data', 1)
                ->where('products.data.0.sku', 'SKU-WIDGET-001')
            );
    }

    public function test_products_index_can_be_filtered_by_category(): void
    {
        $user = User::factory()->create();
        $apparel = Category::factory()->create(['name' => 'Apparel']);
        $electronics = Category::factory()->create(['name' => 'Electronics']);

        $shirt = Product::factory()->create(['name' => 'Shirt']);
        $shirt->categories()->attach($apparel);

        $phone = Product::factory()->create(['name' => 'Phone']);
        $phone->categories()->attach($electronics);

        $this->actingAs($user)
            ->get(route('admin.products.index', ['category_id' => $apparel->id]))
            ->assertInertia(fn ($page) => $page
                ->has('products.data', 1)
                ->where('products.data.0.name', 'Shirt')
            );
    }

    public function test_products_index_can_be_filtered_by_in_stock(): void
    {
        $user = User::factory()->create();
        Product::factory()->create(['name' => 'Available', 'stock_quantity' => 5]);
        Product::factory()->create(['name' => 'Sold Out', 'stock_quantity' => 0]);

        $this->actingAs($user)
            ->get(route('admin.products.index', ['stock' => 'in_stock']))
            ->assertInertia(fn ($page) => $page
                ->has('products.data', 1)
                ->where('products.data.0.name', 'Available')
            );
    }

    public function test_products_index_can_be_filtered_by_out_of_stock(): void
    {
        $user = User::factory()->create();
        Product::factory()->create(['name' => 'Available', 'stock_quantity' => 5]);
        Product::factory()->create(['name' => 'Sold Out', 'stock_quantity' => 0]);

        $this->actingAs($user)
            ->get(route('admin.products.index', ['stock' => 'out_of_stock']))
            ->assertInertia(fn ($page) => $page
                ->has('products.data', 1)
                ->where('products.data.0.name', 'Sold Out')
            );
    }

    public function test_products_index_search_and_stock_filters_can_be_combined(): void
    {
        $user = User::factory()->create();
        Product::factory()->create(['name' => 'Red Widget', 'stock_quantity' => 10]);
        Product::factory()->create(['name' => 'Red Gadget', 'stock_quantity' => 0]);
        Product::factory()->create(['name' => 'Blue Widget', 'stock_quantity' => 5]);

        $this->actingAs($user)
            ->get(route('admin.products.index', ['search' => 'Red', 'stock' => 'in_stock']))
            ->assertInertia(fn ($page) => $page
                ->has('products.data', 1)
                ->where('products.data.0.name', 'Red Widget')
            );
    }
}
