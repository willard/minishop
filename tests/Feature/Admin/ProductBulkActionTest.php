<?php

namespace Tests\Feature\Admin;

use App\Models\Category;
use App\Models\Product;
use App\Models\User;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class ProductBulkActionTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RoleAndPermissionSeeder::class);
    }

    private function superAdmin(): User
    {
        return User::factory()->superAdmin()->create();
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
        $this->actingAs($this->superAdmin())
            ->post(route('admin.products.bulk'), ['action' => 'activate'])
            ->assertSessionHasErrors('product_ids');
    }

    public static function validationProvider(): array
    {
        return [
            'invalid action' => [['action' => 'invalid_action'], 'action'],
            'assign_category missing category' => [['action' => 'assign_category'], 'category_id'],
            'update_stock missing quantity' => [['action' => 'update_stock'], 'stock_quantity'],
            'update_price missing price' => [['action' => 'update_price'], 'price'],
            'update_stock negative quantity' => [['action' => 'update_stock', 'stock_quantity' => -1], 'stock_quantity'],
            'update_price negative price' => [['action' => 'update_price', 'price' => -100], 'price'],
        ];
    }

    #[DataProvider('validationProvider')]
    public function test_bulk_action_validation(array $payload, string $errorField): void
    {
        $product = Product::factory()->create();

        $this->actingAs($this->superAdmin())
            ->post(route('admin.products.bulk'), array_merge(['product_ids' => [$product->id]], $payload))
            ->assertSessionHasErrors($errorField);
    }

    // ── Delete ────────────────────────────────────────────────────────────────

    public function test_super_admin_can_bulk_delete_products(): void
    {
        $products = Product::factory(3)->create();

        $this->actingAs($this->superAdmin())
            ->post(route('admin.products.bulk'), [
                'product_ids' => $products->pluck('id')->toArray(),
                'action' => 'delete',
            ])
            ->assertRedirect(route('admin.products.index'))
            ->assertSessionHas('success');

        foreach ($products as $product) {
            $this->assertModelMissing($product);
        }
    }

    public function test_bulk_delete_only_deletes_selected_products(): void
    {
        $toDelete = Product::factory(2)->create();
        $toKeep = Product::factory()->create();

        $this->actingAs($this->superAdmin())
            ->post(route('admin.products.bulk'), [
                'product_ids' => $toDelete->pluck('id')->toArray(),
                'action' => 'delete',
            ]);

        $this->assertModelExists($toKeep);
        foreach ($toDelete as $product) {
            $this->assertModelMissing($product);
        }
    }

    // ── Activate / Deactivate ─────────────────────────────────────────────────

    public function test_super_admin_can_bulk_activate_products(): void
    {
        $products = Product::factory(3)->create(['is_active' => false]);

        $this->actingAs($this->superAdmin())
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
        $products = Product::factory(3)->create(['is_active' => true]);

        $this->actingAs($this->superAdmin())
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
        $category = Category::factory()->create();
        $products = Product::factory(3)->create();

        $this->actingAs($this->superAdmin())
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
        $existing = Category::factory()->create();
        $new = Category::factory()->create();
        $product = Product::factory()->create();
        $product->categories()->attach($existing);

        $this->actingAs($this->superAdmin())
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
        $products = Product::factory(3)->create(['stock_quantity' => 5]);

        $this->actingAs($this->superAdmin())
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
        $product = Product::factory()->create(['stock_quantity' => 2, 'low_stock_notified' => true]);

        $this->actingAs($this->superAdmin())
            ->post(route('admin.products.bulk'), [
                'product_ids' => [$product->id],
                'action' => 'update_stock',
                'stock_quantity' => 100,
            ]);

        $this->assertDatabaseHas('products', ['id' => $product->id, 'low_stock_notified' => false]);
    }

    // ── Update Price ──────────────────────────────────────────────────────────

    public function test_super_admin_can_bulk_update_price(): void
    {
        $products = Product::factory(3)->create(['price' => 1000]);

        $this->actingAs($this->superAdmin())
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

    public function test_manager_cannot_bulk_delete_products(): void
    {
        $user = User::factory()->manager()->create();
        $products = Product::factory(2)->create();

        $this->actingAs($user)
            ->post(route('admin.products.bulk'), [
                'product_ids' => $products->pluck('id')->toArray(),
                'action' => 'delete',
            ])
            ->assertForbidden();

        foreach ($products as $product) {
            $this->assertModelExists($product);
        }
    }

    // ── Success message ───────────────────────────────────────────────────────

    public function test_success_message_uses_plural_when_multiple_products(): void
    {
        $products = Product::factory(3)->create(['is_active' => false]);

        $this->actingAs($this->superAdmin())
            ->post(route('admin.products.bulk'), [
                'product_ids' => $products->pluck('id')->toArray(),
                'action' => 'activate',
            ])
            ->assertSessionHas('success', '3 products activated.');
    }

    public function test_success_message_uses_singular_for_one_product(): void
    {
        $product = Product::factory()->create(['is_active' => false]);

        $this->actingAs($this->superAdmin())
            ->post(route('admin.products.bulk'), [
                'product_ids' => [$product->id],
                'action' => 'activate',
            ])
            ->assertSessionHas('success', '1 product activated.');
    }
}
