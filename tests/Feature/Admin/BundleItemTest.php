<?php

namespace Tests\Feature\Admin;

use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\User;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BundleItemTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RoleAndPermissionSeeder::class);
    }

    private function admin(): User
    {
        return User::factory()->admin()->create();
    }

    // ── Authentication ───────────────────────────────────────────────────────

    public function test_bundle_item_store_requires_authentication(): void
    {
        $bundle = Product::factory()->bundledEmpty()->create();
        $component = Product::factory()->create();

        $this->post(route('admin.products.bundle-items.store', $bundle), [
            'component_product_id' => $component->id,
            'quantity' => 1,
        ])->assertRedirect(route('login'));
    }

    public function test_bundle_item_update_requires_authentication(): void
    {
        $bundle = Product::factory()->bundledEmpty()->create();
        $component = Product::factory()->create();
        $item = $bundle->bundleItems()->create(['component_product_id' => $component->id, 'quantity' => 1]);

        $this->put(route('admin.products.bundle-items.update', [$bundle, $item]), [
            'quantity' => 2,
        ])->assertRedirect(route('login'));
    }

    public function test_bundle_item_destroy_requires_authentication(): void
    {
        $bundle = Product::factory()->bundledEmpty()->create();
        $component = Product::factory()->create();
        $item = $bundle->bundleItems()->create(['component_product_id' => $component->id, 'quantity' => 1]);

        $this->delete(route('admin.products.bundle-items.destroy', [$bundle, $item]))
            ->assertRedirect(route('login'));
    }

    // ── Authorization ────────────────────────────────────────────────────────

    public function test_bundle_item_store_forbidden_for_customer_role(): void
    {
        $customer = User::factory()->customer()->create();
        $bundle = Product::factory()->bundledEmpty()->create();
        $component = Product::factory()->create();

        $this->actingAs($customer)
            ->post(route('admin.products.bundle-items.store', $bundle), [
                'component_product_id' => $component->id,
                'quantity' => 1,
            ])->assertForbidden();
    }

    // ── Store — success ──────────────────────────────────────────────────────

    public function test_bundle_item_store_succeeds_for_admin(): void
    {
        $bundle = Product::factory()->bundledEmpty()->create();
        $component = Product::factory()->create();

        $this->actingAs($this->admin())
            ->post(route('admin.products.bundle-items.store', $bundle), [
                'component_product_id' => $component->id,
                'quantity' => 3,
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('bundle_items', [
            'bundle_product_id' => $bundle->id,
            'component_product_id' => $component->id,
            'component_variant_id' => null,
            'quantity' => 3,
        ]);
    }

    public function test_bundle_item_store_with_variant(): void
    {
        $bundle = Product::factory()->bundledEmpty()->create();
        $component = Product::factory()->variable()->create();
        $variant = ProductVariant::factory()->for($component)->create();

        $this->actingAs($this->admin())
            ->post(route('admin.products.bundle-items.store', $bundle), [
                'component_product_id' => $component->id,
                'component_variant_id' => $variant->id,
                'quantity' => 1,
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('bundle_items', [
            'bundle_product_id' => $bundle->id,
            'component_product_id' => $component->id,
            'component_variant_id' => $variant->id,
        ]);
    }

    // ── Store — validation failures ──────────────────────────────────────────

    public function test_bundle_item_store_validates_required_fields(): void
    {
        $bundle = Product::factory()->bundledEmpty()->create();

        $this->actingAs($this->admin())
            ->post(route('admin.products.bundle-items.store', $bundle), [])
            ->assertSessionHasErrors(['component_product_id', 'quantity']);
    }

    public function test_bundle_item_store_rejects_quantity_zero(): void
    {
        $bundle = Product::factory()->bundledEmpty()->create();
        $component = Product::factory()->create();

        $this->actingAs($this->admin())
            ->post(route('admin.products.bundle-items.store', $bundle), [
                'component_product_id' => $component->id,
                'quantity' => 0,
            ])
            ->assertSessionHasErrors('quantity');
    }

    public function test_bundle_item_store_rejects_non_bundled_product(): void
    {
        $simpleProduct = Product::factory()->simple()->create();
        $component = Product::factory()->create();

        $this->actingAs($this->admin())
            ->post(route('admin.products.bundle-items.store', $simpleProduct), [
                'component_product_id' => $component->id,
                'quantity' => 1,
            ])
            ->assertSessionHasErrors('product');
    }

    public function test_bundle_item_store_rejects_self_reference(): void
    {
        $bundle = Product::factory()->bundledEmpty()->create();

        $this->actingAs($this->admin())
            ->post(route('admin.products.bundle-items.store', $bundle), [
                'component_product_id' => $bundle->id,
                'quantity' => 1,
            ])
            ->assertSessionHasErrors('component_product_id');
    }

    public function test_bundle_item_store_rejects_bundled_component(): void
    {
        $bundle = Product::factory()->bundledEmpty()->create();
        $anotherBundle = Product::factory()->bundledEmpty()->create();

        $this->actingAs($this->admin())
            ->post(route('admin.products.bundle-items.store', $bundle), [
                'component_product_id' => $anotherBundle->id,
                'quantity' => 1,
            ])
            ->assertSessionHasErrors('component_product_id');
    }

    public function test_bundle_item_store_rejects_duplicate_component_with_null_variant(): void
    {
        $bundle = Product::factory()->bundledEmpty()->create();
        $component = Product::factory()->create();
        $bundle->bundleItems()->create(['component_product_id' => $component->id, 'quantity' => 1]);

        $this->actingAs($this->admin())
            ->post(route('admin.products.bundle-items.store', $bundle), [
                'component_product_id' => $component->id,
                'quantity' => 2,
            ])
            ->assertSessionHasErrors('component_product_id');
    }

    public function test_bundle_item_store_validates_variant_belongs_to_component_product(): void
    {
        $bundle = Product::factory()->bundledEmpty()->create();
        $component = Product::factory()->variable()->create();
        $otherProduct = Product::factory()->variable()->create();
        $wrongVariant = ProductVariant::factory()->for($otherProduct)->create();

        $this->actingAs($this->admin())
            ->post(route('admin.products.bundle-items.store', $bundle), [
                'component_product_id' => $component->id,
                'component_variant_id' => $wrongVariant->id,
                'quantity' => 1,
            ])
            ->assertSessionHasErrors('component_variant_id');
    }

    public function test_bundle_item_store_enforces_max_component_count(): void
    {
        $bundle = Product::factory()->bundledEmpty()->create();

        for ($i = 0; $i < 50; $i++) {
            $c = Product::factory()->create();
            $bundle->bundleItems()->create(['component_product_id' => $c->id, 'quantity' => 1]);
        }

        $newComponent = Product::factory()->create();

        $this->actingAs($this->admin())
            ->post(route('admin.products.bundle-items.store', $bundle), [
                'component_product_id' => $newComponent->id,
                'quantity' => 1,
            ])
            ->assertSessionHasErrors('component_product_id');
    }

    // ── Update ───────────────────────────────────────────────────────────────

    public function test_bundle_item_update_quantity(): void
    {
        $bundle = Product::factory()->bundledEmpty()->create();
        $component = Product::factory()->create();
        $item = $bundle->bundleItems()->create(['component_product_id' => $component->id, 'quantity' => 1]);

        $this->actingAs($this->admin())
            ->put(route('admin.products.bundle-items.update', [$bundle, $item]), [
                'quantity' => 5,
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('bundle_items', [
            'id' => $item->id,
            'quantity' => 5,
        ]);
    }

    public function test_bundle_item_update_rejects_quantity_zero(): void
    {
        $bundle = Product::factory()->bundledEmpty()->create();
        $component = Product::factory()->create();
        $item = $bundle->bundleItems()->create(['component_product_id' => $component->id, 'quantity' => 1]);

        $this->actingAs($this->admin())
            ->put(route('admin.products.bundle-items.update', [$bundle, $item]), [
                'quantity' => 0,
            ])
            ->assertSessionHasErrors('quantity');
    }

    // ── Destroy ──────────────────────────────────────────────────────────────

    public function test_bundle_item_destroy(): void
    {
        $bundle = Product::factory()->bundledEmpty()->create();
        $component = Product::factory()->create();
        $item = $bundle->bundleItems()->create(['component_product_id' => $component->id, 'quantity' => 1]);

        $this->actingAs($this->admin())
            ->delete(route('admin.products.bundle-items.destroy', [$bundle, $item]))
            ->assertRedirect();

        $this->assertDatabaseMissing('bundle_items', ['id' => $item->id]);
    }

    public function test_bundle_item_destroy_scoped_to_parent_product(): void
    {
        $bundleA = Product::factory()->bundledEmpty()->create();
        $bundleB = Product::factory()->bundledEmpty()->create();
        $component = Product::factory()->create();
        $itemOnB = $bundleB->bundleItems()->create(['component_product_id' => $component->id, 'quantity' => 1]);

        $this->actingAs($this->admin())
            ->delete(route('admin.products.bundle-items.destroy', [$bundleA, $itemOnB]))
            ->assertNotFound();

        $this->assertDatabaseHas('bundle_items', ['id' => $itemOnB->id]);
    }

    // ── Cascade / restrict ───────────────────────────────────────────────────

    public function test_deleting_bundle_product_cascades_to_bundle_items(): void
    {
        $bundle = Product::factory()->bundledEmpty()->create();
        $component = Product::factory()->create();
        $bundle->bundleItems()->create(['component_product_id' => $component->id, 'quantity' => 1]);

        $bundle->delete();

        $this->assertDatabaseCount('bundle_items', 0);
    }

    public function test_deleting_component_product_blocked_when_in_bundle(): void
    {
        $bundle = Product::factory()->bundledEmpty()->create();
        $component = Product::factory()->create();
        $bundle->bundleItems()->create(['component_product_id' => $component->id, 'quantity' => 1]);

        $this->actingAs($this->admin())
            ->delete(route('admin.products.destroy', $component))
            ->assertStatus(409);

        $this->assertDatabaseHas('products', ['id' => $component->id]);
    }
}
