<?php

namespace Tests\Feature\Admin;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Minishop\Database\Seeders\RoleAndPermissionSeeder;
use Minishop\Models\Product;
use Minishop\Models\Tag;
use Minishop\Models\User;
use Tests\TestCase;

class TagTest extends TestCase
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

    // ── Auth ─────────────────────────────────────────────────────────────────

    public function test_guests_are_redirected_when_accessing_admin_tags(): void
    {
        $this->get(route('admin.tags.index'))->assertRedirect(route('login'));
    }

    public function test_show_route_is_not_accessible(): void
    {
        $tag = Tag::factory()->create();

        $this->get("/dashboard/tags/{$tag->slug}")->assertStatus(405);
    }

    // ── Index ────────────────────────────────────────────────────────────────

    public function test_super_admin_can_view_tags_index(): void
    {
        $user = $this->superAdmin();
        Tag::factory(3)->create();

        $this->actingAs($user)
            ->get(route('admin.tags.index'))
            ->assertOk();
    }

    public function test_manager_can_view_tags_index(): void
    {
        $user = User::factory()->manager()->create();
        Tag::factory(2)->create();

        $this->actingAs($user)
            ->get(route('admin.tags.index'))
            ->assertOk();
    }

    // ── Store ────────────────────────────────────────────────────────────────

    public function test_super_admin_can_store_a_tag(): void
    {
        $this->actingAs($this->superAdmin())
            ->post(route('admin.tags.store'), [
                'name' => 'New Arrival',
                'is_active' => true,
            ])
            ->assertRedirect(route('admin.tags.index'));

        $this->assertDatabaseHas('tags', ['name' => 'New Arrival', 'slug' => 'new-arrival']);
    }

    public function test_store_tag_requires_name(): void
    {
        $this->actingAs($this->superAdmin())
            ->post(route('admin.tags.store'), [])
            ->assertSessionHasErrors('name');
    }

    public function test_store_tag_validates_name_uniqueness(): void
    {
        Tag::factory()->create(['name' => 'Bestseller']);

        $this->actingAs($this->superAdmin())
            ->post(route('admin.tags.store'), [
                'name' => 'Bestseller',
            ])
            ->assertSessionHasErrors('name');
    }

    public function test_store_tag_validates_color_hex_format(): void
    {
        $this->actingAs($this->superAdmin())
            ->post(route('admin.tags.store'), [
                'name' => 'Sale',
                'color' => 'not-hex',
            ])
            ->assertSessionHasErrors('color');
    }

    public function test_store_tag_rejects_short_hex_color(): void
    {
        $this->actingAs($this->superAdmin())
            ->post(route('admin.tags.store'), [
                'name' => 'Sale',
                'color' => '#FFF',
            ])
            ->assertSessionHasErrors('color');
    }

    public function test_store_tag_accepts_valid_hex_color(): void
    {
        $this->actingAs($this->superAdmin())
            ->post(route('admin.tags.store'), [
                'name' => 'Clearance',
                'color' => '#FF5733',
                'is_active' => true,
            ])
            ->assertRedirect(route('admin.tags.index'));

        $this->assertDatabaseHas('tags', ['name' => 'Clearance', 'color' => '#FF5733']);
    }

    // ── Update ───────────────────────────────────────────────────────────────

    public function test_super_admin_can_update_a_tag(): void
    {
        $tag = Tag::factory()->create(['name' => 'Old Name']);

        $this->actingAs($this->superAdmin())
            ->put(route('admin.tags.update', $tag), [
                'name' => 'New Name',
            ])
            ->assertRedirect(route('admin.tags.index'));

        $this->assertDatabaseHas('tags', ['id' => $tag->id, 'name' => 'New Name']);
    }

    public function test_update_tag_allows_keeping_same_name(): void
    {
        $tag = Tag::factory()->create(['name' => 'Unique']);

        $this->actingAs($this->superAdmin())
            ->put(route('admin.tags.update', $tag), [
                'name' => 'Unique',
                'color' => '#000000',
            ])
            ->assertRedirect(route('admin.tags.index'));
    }

    public function test_update_tag_can_deactivate_tag(): void
    {
        $tag = Tag::factory()->create(['is_active' => true]);

        $this->actingAs($this->superAdmin())
            ->put(route('admin.tags.update', $tag), [
                'name' => $tag->name,
                // is_active not submitted — simulates unchecked checkbox
            ])
            ->assertRedirect(route('admin.tags.index'));

        $this->assertDatabaseHas('tags', ['id' => $tag->id, 'is_active' => false]);
    }

    public function test_update_tag_prevents_duplicate_name(): void
    {
        Tag::factory()->create(['name' => 'Taken']);
        $tag = Tag::factory()->create(['name' => 'Other']);

        $this->actingAs($this->superAdmin())
            ->put(route('admin.tags.update', $tag), [
                'name' => 'Taken',
            ])
            ->assertSessionHasErrors('name');
    }

    // ── Delete ───────────────────────────────────────────────────────────────

    public function test_super_admin_can_delete_a_tag(): void
    {
        $tag = Tag::factory()->create();

        $this->actingAs($this->superAdmin())
            ->delete(route('admin.tags.destroy', $tag))
            ->assertRedirect(route('admin.tags.index'));

        $this->assertDatabaseMissing('tags', ['id' => $tag->id]);
    }

    public function test_deleting_tag_removes_pivot_rows_but_keeps_products(): void
    {
        $tag = Tag::factory()->create();
        $product = Product::factory()->create();
        $product->tags()->attach($tag);

        $this->actingAs($this->superAdmin())
            ->delete(route('admin.tags.destroy', $tag));

        $this->assertDatabaseMissing('product_tag', ['tag_id' => $tag->id]);
        $this->assertDatabaseHas('products', ['id' => $product->id]);
    }

    // ── Slug uniqueness ──────────────────────────────────────────────────────

    public function test_slug_is_unique_for_similar_names(): void
    {
        Tag::factory()->create(['name' => 'Sale', 'slug' => 'sale']);

        $this->actingAs($this->superAdmin())
            ->post(route('admin.tags.store'), ['name' => 'Sale Items', 'is_active' => true]);

        // The second tag should have a different slug
        $slugs = Tag::pluck('slug')->toArray();
        $this->assertCount(2, array_unique($slugs));
    }

    // ── Manager authorization ────────────────────────────────────────────────

    public function test_manager_cannot_create_tags(): void
    {
        $user = User::factory()->manager()->create();

        $this->actingAs($user)
            ->get(route('admin.tags.create'))
            ->assertForbidden();

        $this->actingAs($user)
            ->post(route('admin.tags.store'), ['name' => 'Blocked'])
            ->assertForbidden();
    }

    public function test_manager_cannot_edit_tags(): void
    {
        $user = User::factory()->manager()->create();
        $tag = Tag::factory()->create();

        $this->actingAs($user)
            ->get(route('admin.tags.edit', $tag))
            ->assertForbidden();

        $this->actingAs($user)
            ->put(route('admin.tags.update', $tag), ['name' => 'Blocked'])
            ->assertForbidden();
    }

    public function test_manager_cannot_delete_tags(): void
    {
        $user = User::factory()->manager()->create();
        $tag = Tag::factory()->create();

        $this->actingAs($user)
            ->delete(route('admin.tags.destroy', $tag))
            ->assertForbidden();
    }
}
