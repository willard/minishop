<?php

namespace Tests\Feature\Admin;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Minishop\Database\Seeders\RoleAndPermissionSeeder;
use Minishop\Models\Product;
use Minishop\Models\User;
use Tests\TestCase;

class ProductRelatedTest extends TestCase
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

    // ── Auth & authorization ──────────────────────────────────────────────────

    public function test_guests_cannot_add_related_products(): void
    {
        $product = Product::factory()->create();
        $related = Product::factory()->create();

        $this->post(route('admin.products.related.store', $product), [
            'related_product_id' => $related->id,
        ])->assertRedirect(route('login'));
    }

    public function test_guests_cannot_remove_related_products(): void
    {
        $product = Product::factory()->create();
        $related = Product::factory()->create();
        $product->relatedProducts()->attach($related->id);

        $this->delete(route('admin.products.related.destroy', [$product, $related]))
            ->assertRedirect(route('login'));
    }

    // ── Store ─────────────────────────────────────────────────────────────────

    public function test_admin_can_add_a_related_product(): void
    {
        $product = Product::factory()->create();
        $related = Product::factory()->create();

        $this->actingAs($this->admin())
            ->post(route('admin.products.related.store', $product), [
                'related_product_id' => $related->id,
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('product_related', [
            'product_id' => $product->id,
            'related_product_id' => $related->id,
        ]);
    }

    public function test_adding_same_related_product_twice_is_idempotent(): void
    {
        $product = Product::factory()->create();
        $related = Product::factory()->create();
        $product->relatedProducts()->attach($related->id);

        $this->actingAs($this->admin())
            ->post(route('admin.products.related.store', $product), [
                'related_product_id' => $related->id,
            ])
            ->assertRedirect();

        $this->assertDatabaseCount('product_related', 1);
    }

    public function test_cannot_add_product_as_related_to_itself(): void
    {
        $product = Product::factory()->create();

        $this->actingAs($this->admin())
            ->post(route('admin.products.related.store', $product), [
                'related_product_id' => $product->id,
            ])
            ->assertSessionHasErrors('related_product_id');

        $this->assertDatabaseCount('product_related', 0);
    }

    public function test_related_product_must_exist(): void
    {
        $product = Product::factory()->create();

        $this->actingAs($this->admin())
            ->post(route('admin.products.related.store', $product), [
                'related_product_id' => 99999,
            ])
            ->assertSessionHasErrors('related_product_id');
    }

    // ── Destroy ───────────────────────────────────────────────────────────────

    public function test_admin_can_remove_a_related_product(): void
    {
        $product = Product::factory()->create();
        $related = Product::factory()->create();
        $product->relatedProducts()->attach($related->id);

        $this->actingAs($this->admin())
            ->delete(route('admin.products.related.destroy', [$product, $related]))
            ->assertRedirect();

        $this->assertDatabaseMissing('product_related', [
            'product_id' => $product->id,
            'related_product_id' => $related->id,
        ]);
    }

    // ── Admin Show page ───────────────────────────────────────────────────────

    public function test_admin_show_page_lists_related_products(): void
    {
        $product = Product::factory()->create();
        $related = Product::factory()->create();
        $product->relatedProducts()->attach($related->id);

        $this->actingAs($this->admin())
            ->get(route('admin.products.show', $product))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('admin/Products/Show')
                ->has('product.related_products', 1)
                ->where('product.related_products.0.id', $related->id)
            );
    }

    public function test_admin_show_page_related_products_empty_after_removal(): void
    {
        $product = Product::factory()->create();
        $related = Product::factory()->create();
        $product->relatedProducts()->attach($related->id);

        $this->actingAs($this->admin())
            ->delete(route('admin.products.related.destroy', [$product, $related]))
            ->assertRedirect();

        $this->actingAs($this->admin())
            ->get(route('admin.products.show', $product))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->has('product.related_products', 0)
            );
    }

    public function test_admin_show_available_products_excludes_current_product(): void
    {
        $product = Product::factory()->create();
        Product::factory(2)->create();

        $this->actingAs($this->admin())
            ->get(route('admin.products.show', $product))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->has('availableProducts', 2)
                ->where('availableProducts', fn ($products) => ! collect($products)->contains('id', $product->id))
            );
    }

    public function test_admin_show_available_products_only_includes_active_products(): void
    {
        $product = Product::factory()->create();
        Product::factory()->create();
        Product::factory()->inactive()->create();

        $this->actingAs($this->admin())
            ->get(route('admin.products.show', $product))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->has('availableProducts', 1)
            );
    }

    // ── SEO fields ────────────────────────────────────────────────────────────

    public function test_admin_can_store_product_with_seo_fields(): void
    {
        $this->actingAs($this->admin())
            ->post(route('admin.products.store'), [
                'type' => 'simple',
                'name' => 'SEO Product',
                'price' => 1999,
                'meta_title' => 'Custom Page Title',
                'meta_description' => 'A short description for search engines.',
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('products', [
            'name' => 'SEO Product',
            'meta_title' => 'Custom Page Title',
            'meta_description' => 'A short description for search engines.',
        ]);
    }

    public function test_admin_can_update_product_seo_fields(): void
    {
        $product = Product::factory()->create();

        $this->actingAs($this->admin())
            ->put(route('admin.products.update', $product), [
                'name' => $product->name,
                'price' => $product->price,
                'meta_title' => 'Updated Title',
                'meta_description' => 'Updated description.',
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('products', [
            'id' => $product->id,
            'meta_title' => 'Updated Title',
            'meta_description' => 'Updated description.',
        ]);
    }

    public function test_meta_description_max_500_characters(): void
    {
        $product = Product::factory()->create();

        $this->actingAs($this->admin())
            ->put(route('admin.products.update', $product), [
                'name' => $product->name,
                'price' => $product->price,
                'meta_description' => str_repeat('a', 501),
            ])
            ->assertSessionHasErrors('meta_description');
    }
}
