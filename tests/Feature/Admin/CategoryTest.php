<?php

namespace Tests\Feature\Admin;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Minishop\Database\Seeders\RoleAndPermissionSeeder;
use Minishop\Models\Category;
use Minishop\Models\User;
use Tests\TestCase;

class CategoryTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RoleAndPermissionSeeder::class);
    }

    public function test_guests_are_redirected_when_accessing_admin_categories(): void
    {
        $this->get(route('admin.categories.index'))->assertRedirect(route('login'));
    }

    public function test_category_show_route_is_not_accessible(): void
    {
        $category = Category::factory()->create();

        // show was removed from the resource; GET on the category URI returns 405
        $this->get("/dashboard/categories/{$category->slug}")->assertStatus(405);
    }

    public function test_super_admin_can_view_categories_index(): void
    {
        $user = User::factory()->superAdmin()->create();
        Category::factory(3)->create();

        $this->actingAs($user)
            ->get(route('admin.categories.index'))
            ->assertOk();
    }

    public function test_super_admin_can_store_a_category(): void
    {
        $user = User::factory()->superAdmin()->create();

        $this->actingAs($user)
            ->post(route('admin.categories.store'), [
                'name' => 'Electronics',
                'is_active' => true,
            ])
            ->assertRedirect(route('admin.categories.index'));

        $this->assertDatabaseHas('categories', ['name' => 'Electronics']);
    }

    public function test_store_category_requires_name(): void
    {
        $user = User::factory()->superAdmin()->create();

        $this->actingAs($user)
            ->post(route('admin.categories.store'), [])
            ->assertSessionHasErrors('name');
    }

    public function test_store_category_validates_parent_exists(): void
    {
        $user = User::factory()->superAdmin()->create();

        $this->actingAs($user)
            ->post(route('admin.categories.store'), [
                'name' => 'Child Category',
                'parent_id' => 9999,
            ])
            ->assertSessionHasErrors('parent_id');
    }

    public function test_category_cannot_set_itself_as_parent(): void
    {
        $user = User::factory()->superAdmin()->create();
        $category = Category::factory()->create();

        $this->actingAs($user)
            ->put(route('admin.categories.update', $category), [
                'name' => $category->name,
                'parent_id' => $category->id,
            ])
            ->assertSessionHasErrors('parent_id');
    }

    public function test_super_admin_can_update_a_category(): void
    {
        $user = User::factory()->superAdmin()->create();
        $category = Category::factory()->create(['name' => 'Old Name']);

        $this->actingAs($user)
            ->put(route('admin.categories.update', $category), [
                'name' => 'New Name',
            ])
            ->assertRedirect(route('admin.categories.index'));

        $this->assertDatabaseHas('categories', ['id' => $category->id, 'name' => 'New Name']);
    }

    public function test_super_admin_can_delete_a_category(): void
    {
        $user = User::factory()->superAdmin()->create();
        $category = Category::factory()->create();

        $this->actingAs($user)
            ->delete(route('admin.categories.destroy', $category))
            ->assertRedirect(route('admin.categories.index'));

        $this->assertDatabaseMissing('categories', ['id' => $category->id]);
    }
}
