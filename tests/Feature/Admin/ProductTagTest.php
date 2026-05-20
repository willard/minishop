<?php

namespace Tests\Feature\Admin;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Minishop\Database\Seeders\RoleAndPermissionSeeder;
use Minishop\Models\Product;
use Minishop\Models\Tag;
use Minishop\Models\User;
use Tests\TestCase;

class ProductTagTest extends TestCase
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

    // ── Product create/update syncs tags ─────────────────────────────────────

    public function test_store_product_syncs_tags(): void
    {
        $tags = Tag::factory(2)->create();

        $this->actingAs($this->superAdmin())
            ->post(route('admin.products.store'), [
                'type' => 'simple',
                'name' => 'Tagged Product',
                'price' => 2500,
                'tag_ids' => $tags->pluck('id')->toArray(),
            ]);

        $product = Product::query()->where('name', 'Tagged Product')->first();

        $this->assertNotNull($product);
        $this->assertCount(2, $product->tags);
    }

    public function test_update_product_syncs_tags(): void
    {
        $product = Product::factory()->create();
        $oldTag = Tag::factory()->create();
        $newTag = Tag::factory()->create();
        $product->tags()->attach($oldTag);

        $this->actingAs($this->superAdmin())
            ->put(route('admin.products.update', $product), [
                'name' => $product->name,
                'price' => $product->price,
                'tag_ids' => [$newTag->id],
            ]);

        $product->load('tags');
        $this->assertCount(1, $product->tags);
        $this->assertTrue($product->tags->contains($newTag));
        $this->assertFalse($product->tags->contains($oldTag));
    }

    public function test_store_product_with_no_tags_is_fine(): void
    {
        $this->actingAs($this->superAdmin())
            ->post(route('admin.products.store'), [
                'type' => 'simple',
                'name' => 'No Tags Product',
                'price' => 1000,
            ]);

        $product = Product::query()->where('name', 'No Tags Product')->first();
        $this->assertNotNull($product);
        $this->assertCount(0, $product->tags);
    }

    public function test_store_product_validates_tag_ids_exist(): void
    {
        $this->actingAs($this->superAdmin())
            ->post(route('admin.products.store'), [
                'type' => 'simple',
                'name' => 'Bad Tags',
                'price' => 1000,
                'tag_ids' => [9999],
            ])
            ->assertSessionHasErrors('tag_ids.0');
    }

    // ── Bulk assign tag ──────────────────────────────────────────────────────

    public function test_super_admin_can_bulk_assign_tag(): void
    {
        $tag = Tag::factory()->create();
        $products = Product::factory(3)->create();

        $this->actingAs($this->superAdmin())
            ->post(route('admin.products.bulk'), [
                'product_ids' => $products->pluck('id')->toArray(),
                'action' => 'assign_tag',
                'tag_id' => $tag->id,
            ])
            ->assertRedirect(route('admin.products.index'))
            ->assertSessionHas('success');

        foreach ($products as $product) {
            $this->assertDatabaseHas('product_tag', [
                'product_id' => $product->id,
                'tag_id' => $tag->id,
            ]);
        }
    }

    public function test_assign_tag_does_not_remove_existing_tags(): void
    {
        $existing = Tag::factory()->create();
        $new = Tag::factory()->create();
        $product = Product::factory()->create();
        $product->tags()->attach($existing);

        $this->actingAs($this->superAdmin())
            ->post(route('admin.products.bulk'), [
                'product_ids' => [$product->id],
                'action' => 'assign_tag',
                'tag_id' => $new->id,
            ]);

        $this->assertDatabaseHas('product_tag', ['product_id' => $product->id, 'tag_id' => $existing->id]);
        $this->assertDatabaseHas('product_tag', ['product_id' => $product->id, 'tag_id' => $new->id]);
    }

    public function test_bulk_assign_tag_requires_tag_id(): void
    {
        $products = Product::factory(2)->create();

        $this->actingAs($this->superAdmin())
            ->post(route('admin.products.bulk'), [
                'product_ids' => $products->pluck('id')->toArray(),
                'action' => 'assign_tag',
            ])
            ->assertSessionHasErrors('tag_id');
    }

    public function test_bulk_assign_tag_validates_tag_exists(): void
    {
        $products = Product::factory(2)->create();

        $this->actingAs($this->superAdmin())
            ->post(route('admin.products.bulk'), [
                'product_ids' => $products->pluck('id')->toArray(),
                'action' => 'assign_tag',
                'tag_id' => 9999,
            ])
            ->assertSessionHasErrors('tag_id');
    }

    // ── Index filter by tag ──────────────────────────────────────────────────

    public function test_products_index_passes_tags_as_prop(): void
    {
        Tag::factory()->create(['name' => 'Bestseller']);

        $this->actingAs($this->superAdmin())
            ->get(route('admin.products.index'))
            ->assertInertia(fn ($page) => $page->has('tags'));
    }

    public function test_products_index_filters_by_tag_id(): void
    {
        $tag = Tag::factory()->create();
        $tagged = Product::factory()->create(['name' => 'Tagged']);
        $tagged->tags()->attach($tag);
        Product::factory()->create(['name' => 'Untagged']);

        $this->actingAs($this->superAdmin())
            ->get(route('admin.products.index', ['tag_id' => $tag->id]))
            ->assertInertia(fn ($page) => $page
                ->has('products.data', 1)
                ->where('products.data.0.name', 'Tagged')
            );
    }
}
