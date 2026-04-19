<?php

namespace Tests\Feature\Admin;

use App\Enums\MenuLocation;
use App\Models\MenuItem;
use App\Models\User;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MenuTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RoleAndPermissionSeeder::class);
    }

    public function test_guests_are_redirected_from_index(): void
    {
        $this->get(route('admin.menus.index'))
            ->assertRedirect(route('login'));
    }

    public function test_super_admin_can_view_menus_index(): void
    {
        $user = User::factory()->superAdmin()->create();
        MenuItem::factory(2)->create();

        $this->actingAs($user)
            ->get(route('admin.menus.index'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('admin/Menus/Index')
                ->has('menus')
                ->has('locations')
            );
    }

    public function test_super_admin_can_store_a_menu_item(): void
    {
        $user = User::factory()->superAdmin()->create();

        $this->actingAs($user)
            ->post(route('admin.menus.store'), [
                'menu_location' => MenuLocation::HeaderPrimary->value,
                'label' => 'About',
                'url' => '/pages/about',
                'target' => '_self',
                'sort_order' => 1,
            ])
            ->assertRedirect(route('admin.menus.index'))
            ->assertSessionHas('success');

        $this->assertDatabaseHas('menu_items', [
            'label' => 'About',
            'url' => '/pages/about',
            'menu_location' => MenuLocation::HeaderPrimary->value,
        ]);
    }

    public function test_store_requires_label_and_url(): void
    {
        $user = User::factory()->superAdmin()->create();

        $this->actingAs($user)
            ->post(route('admin.menus.store'), [
                'menu_location' => MenuLocation::HeaderPrimary->value,
            ])
            ->assertSessionHasErrors(['label', 'url']);
    }

    public function test_super_admin_can_update_a_menu_item(): void
    {
        $user = User::factory()->superAdmin()->create();
        $item = MenuItem::factory()->create(['label' => 'Old']);

        $this->actingAs($user)
            ->put(route('admin.menus.update', $item), [
                'menu_location' => $item->menu_location->value,
                'label' => 'New Label',
                'url' => $item->url,
                'target' => '_blank',
                'sort_order' => 5,
            ])
            ->assertRedirect(route('admin.menus.index'));

        $this->assertDatabaseHas('menu_items', [
            'id' => $item->id,
            'label' => 'New Label',
            'target' => '_blank',
        ]);
    }

    public function test_super_admin_can_delete_a_menu_item(): void
    {
        $user = User::factory()->superAdmin()->create();
        $item = MenuItem::factory()->create();

        $this->actingAs($user)
            ->delete(route('admin.menus.destroy', $item))
            ->assertRedirect(route('admin.menus.index'));

        $this->assertDatabaseMissing('menu_items', ['id' => $item->id]);
    }

    public function test_super_admin_can_reorder_items(): void
    {
        $user = User::factory()->superAdmin()->create();
        $first = MenuItem::factory()->create(['sort_order' => 1]);
        $second = MenuItem::factory()->create(['sort_order' => 2]);

        $this->actingAs($user)
            ->post(route('admin.menus.reorder'), [
                'items' => [
                    ['id' => $first->id, 'sort_order' => 2],
                    ['id' => $second->id, 'sort_order' => 1],
                ],
            ])
            ->assertRedirect(route('admin.menus.index'));

        $this->assertSame(2, $first->fresh()->sort_order);
        $this->assertSame(1, $second->fresh()->sort_order);
    }

    public function test_manager_cannot_create_menu_item(): void
    {
        $user = User::factory()->manager()->create();

        $this->actingAs($user)
            ->post(route('admin.menus.store'), [
                'menu_location' => MenuLocation::HeaderPrimary->value,
                'label' => 'Blocked',
                'url' => '/blocked',
            ])
            ->assertForbidden();
    }
}
