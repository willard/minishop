<?php

namespace Tests\Feature\Admin;

use App\Models\Category;
use App\Models\Product;
use App\Models\User;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProductBulkActionTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RoleAndPermissionSeeder::class);
    }

    // ── Auth & authorization ──────────────────────────────────────────────────

    public function test_guests_cannot_perform_bulk_actions(): void
    {
        $products = Product::factory(2)->create();

        $this->post(route('admin.products.bulk'), [
            'product_ids' => $products->pluck('id')->toArray(),
            'action' => 'activate',
        ])->assertRedirect(route('login'));
    }

    public function test_customers_cannot_perform_bulk_actions(): void
    {
        $user = User::factory()->customer()->create();
        $products = Product::factory(2)->create();

        $this->actingAs($user)
            ->post(route('admin.products.bulk'), [
                'product_ids' => $products->pluck('id')->toArray(),
                'action' => 'activate',
            ])->assertForbidden();
    }

    // ── Validation ────────────────────────────────────────────────────────────

    public function test_bulk_action_requires_product_ids(): void
    {
        $user = User::factory()->superAdmin()->create();

        $this->actingAs($user)
            ->post(route('admin.products.bulk'), ['action' => 'activate'])
            ->assertSessionHasErrors('product_ids');
    }

    public function test_bulk_action_requires_valid_action(): void
    {
        $user = User::factory()->superAdmin()->create();
        $product = Product::factory()->create();

        $this->actingAs($user)
            ->post(route('admin.products.bulk'), [
                'product_ids' => [$product->id],
                'action' => 'invalid_action',
            ])->assertSessionHasErrors('action');
    }

    public function test_assign_category_requires_category_id(): void
    {
        $user = User::factory()->superAdmin()->create();
        $product = Product::factory()->create();

        $this->actingAs($user)
            ->post(route('admin.products.bulk'), [
                'product_ids' => [$product->id],
                'action' => 'assign_category',
            ])->assertSessionHasErrors('category_id');
    }

    public function test_update_stock_requires_stock_quantity(): void
    {
        $user = User::factory()->superAdmin()->create();
        $product = Product::factory()->create();

        $this->actingAs($user)
            ->post(route('admin.products.bulk'), [
                'product_ids' => [$product->id],
                'action' => 'update_stock',
            ])->assertSessionHasErrors('stock_quantity');
    }

    public function test_update_price_requires_price(): void
    {
        $user = User::factory()->superAdmin()->create();
        $product = Product::factory()->create();

        $this->actingAs($user)
            ->post(route('admin.products.bulk'), [
                'product_ids' => [$product->id],
                'action' => 'update_price',
            ])->assertSessionHasErrors('price');
    }

    // ── Delete ────────────────────────────────────────────────────────────────

    public function test_super_admin_can_bulk_delete_products(): void
    {
        $user = User::factory()->superAdmin()->create();
        $products = Product::factory(3)->create();
        $ids = $products->pluck('id')->toArray();

        $this->actingAs($user)
            ->post(route('admin.products.bulk'), [
                'product_ids' => $ids,
                'action' => 'delete',
            ])
            ->assertRedirect(route('admin.products.index'))
            ->assertSessionHas('success');

        foreach ($ids as $id) {
            $this->assertDatabaseMissing('products', ['id' => $id]);
        }
    }

    public function test_bulk_delete_only_deletes_selected_products(): void
    {
        $user = User::factory()->superAdmin()->create();
        $toDelete = Product::factory(2)->create();
        $toKeep = Product::factory()->create();

        $this->actingAs($user)
            ->post(route('admin.products.bulk'), [
                'product_ids' => $toDelete->pluck('id')->toArray(),
                'action' => 'delete',
            ]);

        $this->assertDatabaseHas('products', ['id' => $toKeep->id]);
        foreach ($toDelete as $product) {
            $this->assertDatabaseMissing('products', ['id' => $product->id]);
        }
    }

    // ── Activate / Deactivate ─────────────────────────────────────────────────

    public function test_super_admin_can_bulk_activate_products(): void
    {
        $user = User::factory()->superAdmin()->create();
        $products = Product::factory(3)->create(['is_active' => false]);

        $this->actingAs($user)
            ->post(route('admin.products.bulk'), [
                'product_ids' => $products->pluck('id')->toArray(),
                'action' => 'activate',
            ])
            ->assertRedirect(route('admin.products.index'))
            ->assertSessionHas('success');

        foreach ($products as $product) {
            $this->assertDatabaseHas('products', ['id' => $product->id, 'is_active' => true]);
        }
    }

    public function test_super_admin_can_bulk_deactivate_products(): void
    {
        $user = User::factory()->superAdmin()->create();
        $products = Product::factory(3)->create(['is_active' => true]);

        $this->actingAs($user)
            ->post(route('admin.products.bulk'), [
                'product_ids' => $products->pluck('id')->toArray(),
                'action' => 'deactivate',
            ])
            ->assertRedirect(route('admin.products.index'))
            ->assertSessionHas('success');

        foreach ($products as $product) {
            $this->assertDatabaseHas('products', ['id' => $product->id, 'is_active' => false]);
        }
    }

    // ── Assign Category ───────────────────────────────────────────────────────

    public function test_super_admin_can_bulk_assign_category(): void
    {
        $user = User::factory()->superAdmin()->create();
        $category = Category::factory()->create();
        $products = Product::factory(3)->create();

        $this->actingAs($user)
            ->post(route('admin.products.bulk'), [
                'product_ids' => $products->pluck('id')->toArray(),
                'action' => 'assign_category',
                'category_id' => $category->id,
            ])
            ->assertRedirect(route('admin.products.index'))
            ->assertSessionHas('success');

        foreach ($products as $product) {
            $this->assertDatabaseHas('category_product', [
                'product_id' => $product->id,
                'category_id' => $category->id,
            ]);
        }
    }

    public function test_assign_category_does_not_remove_existing_categories(): void
    {
        $user = User::factory()->superAdmin()->create();
        $existing = Category::factory()->create();
        $new = Category::factory()->create();
        $product = Product::factory()->create();
        $product->categories()->attach($existing);

        $this->actingAs($user)
            ->post(route('admin.products.bulk'), [
                'product_ids' => [$product->id],
                'action' => 'assign_category',
                'category_id' => $new->id,
            ]);

        $this->assertDatabaseHas('category_product', ['product_id' => $product->id, 'category_id' => $existing->id]);
        $this->assertDatabaseHas('category_product', ['product_id' => $product->id, 'category_id' => $new->id]);
    }

    // ── Update Stock ──────────────────────────────────────────────────────────

    public function test_super_admin_can_bulk_update_stock(): void
    {
        $user = User::factory()->superAdmin()->create();
        $products = Product::factory(3)->create(['stock_quantity' => 5]);

        $this->actingAs($user)
            ->post(route('admin.products.bulk'), [
                'product_ids' => $products->pluck('id')->toArray(),
                'action' => 'update_stock',
                'stock_quantity' => 50,
            ])
            ->assertRedirect(route('admin.products.index'))
            ->assertSessionHas('success');

        foreach ($products as $product) {
            $this->assertDatabaseHas('products', ['id' => $product->id, 'stock_quantity' => 50]);
        }
    }

    public function test_bulk_update_stock_resets_low_stock_notified(): void
    {
        $user = User::factory()->superAdmin()->create();
        $product = Product::factory()->create(['stock_quantity' => 2, 'low_stock_notified' => true]);

        $this->actingAs($user)
            ->post(route('admin.products.bulk'), [
                'product_ids' => [$product->id],
                'action' => 'update_stock',
                'stock_quantity' => 100,
            ]);

        $this->assertDatabaseHas('products', ['id' => $product->id, 'low_stock_notified' => false]);
    }

    public function test_stock_quantity_cannot_be_negative(): void
    {
        $user = User::factory()->superAdmin()->create();
        $product = Product::factory()->create();

        $this->actingAs($user)
            ->post(route('admin.products.bulk'), [
                'product_ids' => [$product->id],
                'action' => 'update_stock',
                'stock_quantity' => -1,
            ])->assertSessionHasErrors('stock_quantity');
    }

    // ── Update Price ──────────────────────────────────────────────────────────

    public function test_super_admin_can_bulk_update_price(): void
    {
        $user = User::factory()->superAdmin()->create();
        $products = Product::factory(3)->create(['price' => 1000]);

        $this->actingAs($user)
            ->post(route('admin.products.bulk'), [
                'product_ids' => $products->pluck('id')->toArray(),
                'action' => 'update_price',
                'price' => 2499,
            ])
            ->assertRedirect(route('admin.products.index'))
            ->assertSessionHas('success');

        foreach ($products as $product) {
            $this->assertDatabaseHas('products', ['id' => $product->id, 'price' => 2499]);
        }
    }

    public function test_price_cannot_be_negative(): void
    {
        $user = User::factory()->superAdmin()->create();
        $product = Product::factory()->create();

        $this->actingAs($user)
            ->post(route('admin.products.bulk'), [
                'product_ids' => [$product->id],
                'action' => 'update_price',
                'price' => -100,
            ])->assertSessionHasErrors('price');
    }

    // ── Manager role ──────────────────────────────────────────────────────────

    public function test_manager_can_perform_bulk_actions(): void
    {
        $user = User::factory()->manager()->create();
        $products = Product::factory(2)->create(['is_active' => false]);

        $this->actingAs($user)
            ->post(route('admin.products.bulk'), [
                'product_ids' => $products->pluck('id')->toArray(),
                'action' => 'activate',
            ])
            ->assertRedirect(route('admin.products.index'));
    }

    // ── Success message ───────────────────────────────────────────────────────

    public function test_success_message_uses_plural_when_multiple_products(): void
    {
        $user = User::factory()->superAdmin()->create();
        $products = Product::factory(3)->create(['is_active' => false]);

        $this->actingAs($user)
            ->post(route('admin.products.bulk'), [
                'product_ids' => $products->pluck('id')->toArray(),
                'action' => 'activate',
            ])
            ->assertSessionHas('success', '3 products activated.');
    }

    public function test_success_message_uses_singular_for_one_product(): void
    {
        $user = User::factory()->superAdmin()->create();
        $product = Product::factory()->create(['is_active' => false]);

        $this->actingAs($user)
            ->post(route('admin.products.bulk'), [
                'product_ids' => [$product->id],
                'action' => 'activate',
            ])
            ->assertSessionHas('success', '1 product activated.');
    }
}
