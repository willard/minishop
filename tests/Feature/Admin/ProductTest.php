<?php

namespace Tests\Feature\Admin;

use App\Models\Category;
use App\Models\Product;
use App\Models\User;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProductTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RoleAndPermissionSeeder::class);
    }

    public function test_guests_are_redirected_when_accessing_admin_products(): void
    {
        $this->get(route('admin.products.index'))->assertRedirect(route('login'));
    }

    public function test_super_admin_can_view_products_index(): void
    {
        $user = User::factory()->superAdmin()->create();
        Product::factory(3)->create();

        $this->actingAs($user)
            ->get(route('admin.products.index'))
            ->assertOk();
    }

    public function test_super_admin_can_view_create_product_form(): void
    {
        $user = User::factory()->superAdmin()->create();

        $this->actingAs($user)
            ->get(route('admin.products.create'))
            ->assertOk();
    }

    public function test_super_admin_can_store_a_product(): void
    {
        $user = User::factory()->superAdmin()->create();

        $this->actingAs($user)
            ->post(route('admin.products.store'), [
                'type' => 'simple',
                'name' => 'Test Product',
                'price' => 1999,
                'stock_quantity' => 10,
                'is_active' => true,
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('products', ['name' => 'Test Product', 'price' => 1999, 'type' => 'simple']);
    }

    public function test_store_product_requires_name(): void
    {
        $user = User::factory()->superAdmin()->create();

        $this->actingAs($user)
            ->post(route('admin.products.store'), ['price' => 1999])
            ->assertSessionHasErrors('name');
    }

    public function test_store_product_requires_valid_price(): void
    {
        $user = User::factory()->superAdmin()->create();

        $this->actingAs($user)
            ->post(route('admin.products.store'), ['name' => 'Test', 'price' => -1])
            ->assertSessionHasErrors('price');
    }

    public function test_store_product_validates_sku_is_unique(): void
    {
        $user = User::factory()->superAdmin()->create();
        Product::factory()->create(['sku' => 'SKU-001']);

        $this->actingAs($user)
            ->post(route('admin.products.store'), [
                'name' => 'Another Product',
                'price' => 500,
                'sku' => 'SKU-001',
            ])
            ->assertSessionHasErrors('sku');
    }

    public function test_store_product_defaults_stock_quantity_to_zero_when_omitted(): void
    {
        $user = User::factory()->superAdmin()->create();

        $this->actingAs($user)
            ->post(route('admin.products.store'), [
                'type' => 'simple',
                'name' => 'Test Product',
                'price' => 1999,
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('products', ['name' => 'Test Product', 'stock_quantity' => 0]);
    }

    public function test_update_product_defaults_stock_quantity_to_zero_when_omitted(): void
    {
        $user = User::factory()->superAdmin()->create();
        $product = Product::factory()->create(['stock_quantity' => 0]);

        $this->actingAs($user)
            ->put(route('admin.products.update', $product), [
                'name' => $product->name,
                'price' => $product->price,
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('products', ['id' => $product->id, 'stock_quantity' => 0]);
    }

    public function test_update_product_preserves_existing_stock_quantity_when_omitted(): void
    {
        $user = User::factory()->superAdmin()->create();
        $product = Product::factory()->create(['stock_quantity' => 99]);

        $this->actingAs($user)
            ->put(route('admin.products.update', $product), [
                'name' => 'Updated Name',
                'price' => $product->price,
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('products', ['id' => $product->id, 'stock_quantity' => 99]);
    }

    public function test_store_product_defaults_is_active_to_true_when_omitted(): void
    {
        $user = User::factory()->superAdmin()->create();

        $this->actingAs($user)
            ->post(route('admin.products.store'), [
                'type' => 'simple',
                'name' => 'Test Product',
                'price' => 1999,
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('products', ['name' => 'Test Product', 'is_active' => true]);
    }

    public function test_update_product_preserves_existing_is_active_when_omitted(): void
    {
        $user = User::factory()->superAdmin()->create();
        $product = Product::factory()->create(['is_active' => false]);

        $this->actingAs($user)
            ->put(route('admin.products.update', $product), [
                'name' => $product->name,
                'price' => $product->price,
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('products', ['id' => $product->id, 'is_active' => false]);
    }

    public function test_compare_price_must_be_greater_than_price(): void
    {
        $user = User::factory()->superAdmin()->create();

        $this->actingAs($user)
            ->post(route('admin.products.store'), [
                'name' => 'Test Product',
                'price' => 1000,
                'compare_price' => 500,
            ])
            ->assertSessionHasErrors('compare_price');
    }

    public function test_super_admin_can_view_a_product(): void
    {
        $user = User::factory()->superAdmin()->create();
        $product = Product::factory()->create();

        $this->actingAs($user)
            ->get(route('admin.products.show', $product))
            ->assertOk();
    }

    public function test_super_admin_can_view_edit_product_form(): void
    {
        $user = User::factory()->superAdmin()->create();
        $product = Product::factory()->create();

        $this->actingAs($user)
            ->get(route('admin.products.edit', $product))
            ->assertOk();
    }

    public function test_super_admin_can_update_a_product(): void
    {
        $user = User::factory()->superAdmin()->create();
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
        $user = User::factory()->superAdmin()->create();
        $product = Product::factory()->create(['sku' => 'MYSKU-001', 'price' => 1000]);

        $this->actingAs($user)
            ->put(route('admin.products.update', $product), [
                'name' => $product->name,
                'price' => 1000,
                'sku' => 'MYSKU-001',
            ])
            ->assertSessionDoesntHaveErrors('sku');
    }

    public function test_super_admin_can_delete_a_product(): void
    {
        $user = User::factory()->superAdmin()->create();
        $product = Product::factory()->create();

        $this->actingAs($user)
            ->delete(route('admin.products.destroy', $product))
            ->assertRedirect(route('admin.products.index'));

        $this->assertDatabaseMissing('products', ['id' => $product->id]);
    }

    public function test_store_product_syncs_categories(): void
    {
        $user = User::factory()->superAdmin()->create();
        $categories = Category::factory(2)->create();

        $this->actingAs($user)
            ->post(route('admin.products.store'), [
                'type' => 'simple',
                'name' => 'Test Product',
                'price' => 1000,
                'category_ids' => $categories->pluck('id')->toArray(),
            ]);

        $product = Product::query()->where('name', 'Test Product')->first();

        $this->assertCount(2, $product->categories);
    }

    public function test_products_index_passes_filters_and_categories_as_props(): void
    {
        $user = User::factory()->superAdmin()->create();
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
        $user = User::factory()->superAdmin()->create();
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
        $user = User::factory()->superAdmin()->create();
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
        $user = User::factory()->superAdmin()->create();
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
        $user = User::factory()->superAdmin()->create();
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
        $user = User::factory()->superAdmin()->create();
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
        $user = User::factory()->superAdmin()->create();
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

    public function test_products_index_can_be_sorted_by_name_ascending(): void
    {
        $user = User::factory()->superAdmin()->create();
        Product::factory()->create(['name' => 'Zebra Bag']);
        Product::factory()->create(['name' => 'Apple Watch']);
        Product::factory()->create(['name' => 'Mango Shirt']);

        $this->actingAs($user)
            ->get(route('admin.products.index', ['sort_by' => 'name', 'sort_dir' => 'asc']))
            ->assertInertia(fn ($page) => $page
                ->where('products.data.0.name', 'Apple Watch')
                ->where('products.data.1.name', 'Mango Shirt')
                ->where('products.data.2.name', 'Zebra Bag')
            );
    }

    public function test_products_index_can_be_sorted_by_price_descending(): void
    {
        $user = User::factory()->superAdmin()->create();
        Product::factory()->create(['name' => 'Cheap', 'price' => 500]);
        Product::factory()->create(['name' => 'Mid', 'price' => 2000]);
        Product::factory()->create(['name' => 'Expensive', 'price' => 9999]);

        $this->actingAs($user)
            ->get(route('admin.products.index', ['sort_by' => 'price', 'sort_dir' => 'desc']))
            ->assertInertia(fn ($page) => $page
                ->where('products.data.0.name', 'Expensive')
                ->where('products.data.1.name', 'Mid')
                ->where('products.data.2.name', 'Cheap')
            );
    }

    public function test_products_index_ignores_invalid_sort_column(): void
    {
        $user = User::factory()->superAdmin()->create();
        Product::factory(3)->create();

        $this->actingAs($user)
            ->get(route('admin.products.index', ['sort_by' => 'invalid_column', 'sort_dir' => 'asc']))
            ->assertOk();
    }

    public function test_export_csv_returns_csv_download(): void
    {
        $user = User::factory()->superAdmin()->create();
        Product::factory()->create(['name' => 'Export Me', 'price' => 1500]);

        $response = $this->actingAs($user)
            ->get(route('admin.products.export', ['format' => 'csv']));

        $response->assertOk();
        $response->assertHeader('Content-Type', 'text/csv; charset=UTF-8');
    }

    public function test_export_csv_respects_search_filter(): void
    {
        $user = User::factory()->superAdmin()->create();
        Product::factory()->create(['name' => 'Widget Alpha']);
        Product::factory()->create(['name' => 'Gadget Beta']);

        $response = $this->actingAs($user)
            ->get(route('admin.products.export', ['format' => 'csv', 'search' => 'Alpha']));

        $response->assertOk();
        $this->assertStringContainsString('Widget Alpha', $response->streamedContent());
        $this->assertStringNotContainsString('Gadget Beta', $response->streamedContent());
    }

    public function test_export_pdf_returns_html_view(): void
    {
        $user = User::factory()->superAdmin()->create();
        Product::factory()->create(['name' => 'PDF Product']);

        $response = $this->actingAs($user)
            ->get(route('admin.products.export', ['format' => 'pdf']));

        $response->assertOk();
        $response->assertSee('PDF Product');
    }

    public function test_export_requires_authentication(): void
    {
        $this->get(route('admin.products.export'))->assertRedirect(route('login'));
    }

    // ── Product Types ───────────────────────────────────────────────────────

    public function test_create_simple_product(): void
    {
        $user = User::factory()->superAdmin()->create();

        $this->actingAs($user)
            ->post(route('admin.products.store'), [
                'type' => 'simple',
                'name' => 'Simple Widget',
                'price' => 1000,
                'stock_quantity' => 15,
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('products', [
            'name' => 'Simple Widget',
            'type' => 'simple',
            'stock_quantity' => 15,
        ]);
    }

    public function test_create_variable_product(): void
    {
        $user = User::factory()->superAdmin()->create();

        $this->actingAs($user)
            ->post(route('admin.products.store'), [
                'type' => 'variable',
                'name' => 'Variable Widget',
                'price' => 2000,
                'stock_quantity' => 0,
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('products', [
            'name' => 'Variable Widget',
            'type' => 'variable',
        ]);
    }

    public function test_create_bundled_product_forces_zero_stock(): void
    {
        $user = User::factory()->superAdmin()->create();

        $this->actingAs($user)
            ->post(route('admin.products.store'), [
                'type' => 'bundled',
                'name' => 'Starter Kit',
                'price' => 5000,
                'stock_quantity' => 999,
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('products', [
            'name' => 'Starter Kit',
            'type' => 'bundled',
            'stock_quantity' => 0,
        ]);
    }

    public function test_store_product_requires_type(): void
    {
        $user = User::factory()->superAdmin()->create();

        $this->actingAs($user)
            ->post(route('admin.products.store'), [
                'name' => 'No Type Product',
                'price' => 1000,
            ])
            ->assertSessionHasErrors('type');
    }

    public function test_store_product_rejects_invalid_type(): void
    {
        $user = User::factory()->superAdmin()->create();

        $this->actingAs($user)
            ->post(route('admin.products.store'), [
                'type' => 'nonexistent',
                'name' => 'Bad Type',
                'price' => 1000,
            ])
            ->assertSessionHasErrors('type');
    }

    public function test_product_type_cannot_be_changed_via_update_endpoint(): void
    {
        $user = User::factory()->superAdmin()->create();
        $product = Product::factory()->simple()->create(['name' => 'My Simple']);

        $this->actingAs($user)
            ->put(route('admin.products.update', $product), [
                'type' => 'bundled',
                'name' => 'My Simple',
                'price' => $product->price,
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('products', [
            'id' => $product->id,
            'type' => 'simple',
        ]);
    }

    public function test_update_bundled_product_cannot_set_stock_quantity(): void
    {
        $user = User::factory()->superAdmin()->create();
        $bundle = Product::factory()->bundledEmpty()->create(['name' => 'My Bundle']);

        $this->actingAs($user)
            ->put(route('admin.products.update', $bundle), [
                'name' => 'My Bundle',
                'price' => $bundle->price,
                'stock_quantity' => 100,
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('products', [
            'id' => $bundle->id,
            'stock_quantity' => 0,
        ]);
    }

    public function test_store_variant_rejected_for_bundled_product(): void
    {
        $user = User::factory()->superAdmin()->create();
        $bundle = Product::factory()->bundledEmpty()->create();

        $this->actingAs($user)
            ->post(route('admin.products.variants.store', $bundle), [
                'sku' => 'V-001',
                'stock_quantity' => 10,
                'option_value_ids' => [1],
            ])
            ->assertSessionHasErrors('product');
    }

    public function test_store_option_rejected_for_bundled_product(): void
    {
        $user = User::factory()->superAdmin()->create();
        $bundle = Product::factory()->bundledEmpty()->create();

        $this->actingAs($user)
            ->post(route('admin.products.options.store', $bundle), [
                'name' => 'Size',
                'values' => ['S', 'M', 'L'],
            ])
            ->assertSessionHasErrors('product');
    }

    public function test_index_filters_by_product_type(): void
    {
        $user = User::factory()->superAdmin()->create();
        Product::factory()->simple()->create(['name' => 'Simple Widget']);
        Product::factory()->bundledEmpty()->create(['name' => 'Bundle Kit']);

        $this->actingAs($user)
            ->get(route('admin.products.index', ['type' => 'bundled']))
            ->assertInertia(fn ($page) => $page
                ->has('products.data', 1)
                ->where('products.data.0.name', 'Bundle Kit')
            );
    }

    public function test_products_index_can_be_filtered_by_price_min(): void
    {
        $user = User::factory()->superAdmin()->create();
        Product::factory()->create(['name' => 'Cheap', 'price' => 500]);
        Product::factory()->create(['name' => 'Mid', 'price' => 2000]);
        Product::factory()->create(['name' => 'Expensive', 'price' => 5000]);

        $this->actingAs($user)
            ->get(route('admin.products.index', ['price_min' => '15', 'sort_by' => 'price', 'sort_dir' => 'desc']))
            ->assertInertia(fn ($page) => $page
                ->has('products.data', 2)
                ->where('products.data.0.name', 'Expensive')
                ->where('products.data.1.name', 'Mid')
            );
    }

    public function test_products_index_can_be_filtered_by_price_max(): void
    {
        $user = User::factory()->superAdmin()->create();
        Product::factory()->create(['name' => 'Cheap', 'price' => 500]);
        Product::factory()->create(['name' => 'Mid', 'price' => 2000]);
        Product::factory()->create(['name' => 'Expensive', 'price' => 5000]);

        $this->actingAs($user)
            ->get(route('admin.products.index', ['price_max' => '20']))
            ->assertInertia(fn ($page) => $page
                ->has('products.data', 2)
            );
    }

    public function test_products_index_can_be_filtered_by_price_range(): void
    {
        $user = User::factory()->superAdmin()->create();
        Product::factory()->create(['name' => 'Cheap', 'price' => 500]);
        Product::factory()->create(['name' => 'Mid', 'price' => 2000]);
        Product::factory()->create(['name' => 'Expensive', 'price' => 5000]);

        $this->actingAs($user)
            ->get(route('admin.products.index', ['price_min' => '15', 'price_max' => '25']))
            ->assertInertia(fn ($page) => $page
                ->has('products.data', 1)
                ->where('products.data.0.name', 'Mid')
            );
    }

    public function test_products_index_search_includes_description(): void
    {
        $user = User::factory()->superAdmin()->create();
        Product::factory()->create(['name' => 'Widget A', 'sku' => 'SKU-001', 'description' => 'A lightweight travel pillow']);
        Product::factory()->create(['name' => 'Widget B', 'sku' => 'SKU-002', 'description' => 'A durable backpack']);

        $this->actingAs($user)
            ->get(route('admin.products.index', ['search' => 'travel pillow']))
            ->assertInertia(fn ($page) => $page
                ->has('products.data', 1)
                ->where('products.data.0.name', 'Widget A')
            );
    }

    public function test_products_index_price_range_combines_with_category_filter(): void
    {
        $user = User::factory()->superAdmin()->create();
        $apparel = Category::factory()->create(['name' => 'Apparel']);

        $shirt = Product::factory()->create(['name' => 'Shirt', 'price' => 2500]);
        $shirt->categories()->attach($apparel);

        $expensiveShirt = Product::factory()->create(['name' => 'Luxury Shirt', 'price' => 10000]);
        $expensiveShirt->categories()->attach($apparel);

        Product::factory()->create(['name' => 'Gadget', 'price' => 2500]);

        $this->actingAs($user)
            ->get(route('admin.products.index', ['category_id' => $apparel->id, 'price_max' => '50']))
            ->assertInertia(fn ($page) => $page
                ->has('products.data', 1)
                ->where('products.data.0.name', 'Shirt')
            );
    }

    public function test_export_csv_respects_price_range_filter(): void
    {
        $user = User::factory()->superAdmin()->create();
        Product::factory()->create(['name' => 'Cheap Item', 'price' => 500]);
        Product::factory()->create(['name' => 'Pricey Item', 'price' => 9999]);

        $response = $this->actingAs($user)
            ->get(route('admin.products.export', ['format' => 'csv', 'price_min' => '50']));

        $response->assertOk();
        $this->assertStringContainsString('Pricey Item', $response->streamedContent());
        $this->assertStringNotContainsString('Cheap Item', $response->streamedContent());
    }

    public function test_show_loads_bundle_items_for_bundled_product(): void
    {
        $user = User::factory()->superAdmin()->create();
        $bundle = Product::factory()->bundledEmpty()->create();
        $component = Product::factory()->create(['name' => 'Component A']);
        $bundle->bundleItems()->create(['component_product_id' => $component->id, 'quantity' => 2]);

        $this->actingAs($user)
            ->get(route('admin.products.show', $bundle))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->has('product.bundle_items', 1)
                ->has('effective_stock')
            );
    }

    public function test_product_on_sale_defaults_to_false(): void
    {
        $user = User::factory()->superAdmin()->create();

        $this->actingAs($user)
            ->post(route('admin.products.store'), [
                'type' => 'simple',
                'name' => 'Test Product',
                'price' => 1999,
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('products', ['name' => 'Test Product', 'on_sale' => false]);
    }

    public function test_product_can_be_created_with_on_sale_flag(): void
    {
        $user = User::factory()->superAdmin()->create();

        $this->actingAs($user)
            ->post(route('admin.products.store'), [
                'type' => 'simple',
                'name' => 'On Sale Product',
                'price' => 2999,
                'on_sale' => true,
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('products', ['name' => 'On Sale Product', 'on_sale' => true]);
    }

    public function test_update_product_preserves_existing_on_sale_when_omitted(): void
    {
        $user = User::factory()->superAdmin()->create();
        $product = Product::factory()->onSale()->create(['price' => 1999]);

        $this->actingAs($user)
            ->put(route('admin.products.update', $product), [
                'name' => $product->name,
                'price' => $product->price,
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('products', ['id' => $product->id, 'on_sale' => true]);
    }
}
