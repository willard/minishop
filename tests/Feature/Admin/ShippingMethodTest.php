<?php

namespace Tests\Feature\Admin;

use App\Models\ShippingMethod;
use App\Models\User;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ShippingMethodTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RoleAndPermissionSeeder::class);
    }

    public function test_guests_are_redirected_from_index(): void
    {
        $this->get(route('admin.shipping-methods.index'))
            ->assertRedirect(route('login'));
    }

    public function test_guests_are_redirected_from_create(): void
    {
        $this->get(route('admin.shipping-methods.create'))
            ->assertRedirect(route('login'));
    }

    public function test_guests_are_redirected_from_store(): void
    {
        $this->post(route('admin.shipping-methods.store'), [])
            ->assertRedirect(route('login'));
    }

    public function test_guests_are_redirected_from_edit(): void
    {
        $method = ShippingMethod::factory()->create();

        $this->get(route('admin.shipping-methods.edit', $method))
            ->assertRedirect(route('login'));
    }

    public function test_guests_are_redirected_from_destroy(): void
    {
        $method = ShippingMethod::factory()->create();

        $this->delete(route('admin.shipping-methods.destroy', $method))
            ->assertRedirect(route('login'));
    }

    public function test_super_admin_can_view_index(): void
    {
        $user = User::factory()->superAdmin()->create();
        ShippingMethod::factory(3)->create();

        $this->actingAs($user)
            ->get(route('admin.shipping-methods.index'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('admin/ShippingMethods/Index')
                ->has('shippingMethods', 3)
            );
    }

    public function test_super_admin_can_view_create_form(): void
    {
        $user = User::factory()->superAdmin()->create();

        $this->actingAs($user)
            ->get(route('admin.shipping-methods.create'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page->component('admin/ShippingMethods/Create'));
    }

    public function test_super_admin_can_store_a_shipping_method(): void
    {
        $user = User::factory()->superAdmin()->create();

        $this->actingAs($user)
            ->post(route('admin.shipping-methods.store'), [
                'name' => 'Standard Delivery',
                'description' => 'Delivered in 3-5 days',
                'price' => 20000,
                'is_free' => false,
                'is_active' => true,
                'sort_order' => 0,
            ])
            ->assertRedirect(route('admin.shipping-methods.index'))
            ->assertSessionHas('success');

        $this->assertDatabaseHas('shipping_methods', [
            'name' => 'Standard Delivery',
            'price' => 20000,
        ]);
    }

    public function test_free_shipping_sets_price_to_zero(): void
    {
        $user = User::factory()->superAdmin()->create();

        $this->actingAs($user)
            ->post(route('admin.shipping-methods.store'), [
                'name' => 'Free Shipping',
                'is_free' => true,
                'price' => 99999,
                'sort_order' => 0,
            ]);

        $this->assertDatabaseHas('shipping_methods', [
            'name' => 'Free Shipping',
            'price' => 0,
            'is_free' => true,
        ]);
    }

    public function test_store_requires_name(): void
    {
        $user = User::factory()->superAdmin()->create();

        $this->actingAs($user)
            ->post(route('admin.shipping-methods.store'), ['price' => 1000])
            ->assertSessionHasErrors('name');
    }

    public function test_super_admin_can_view_edit_form(): void
    {
        $user = User::factory()->superAdmin()->create();
        $method = ShippingMethod::factory()->create();

        $this->actingAs($user)
            ->get(route('admin.shipping-methods.edit', $method))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('admin/ShippingMethods/Edit')
                ->has('shippingMethod')
            );
    }

    public function test_super_admin_can_update_a_shipping_method(): void
    {
        $user = User::factory()->superAdmin()->create();
        $method = ShippingMethod::factory()->create(['name' => 'Old Name', 'price' => 10000]);

        $this->actingAs($user)
            ->put(route('admin.shipping-methods.update', $method), [
                'name' => 'New Name',
                'price' => 30000,
                'is_free' => false,
                'is_active' => true,
                'sort_order' => 1,
            ])
            ->assertRedirect(route('admin.shipping-methods.index'))
            ->assertSessionHas('success');

        $this->assertDatabaseHas('shipping_methods', [
            'id' => $method->id,
            'name' => 'New Name',
            'price' => 30000,
        ]);
    }

    public function test_super_admin_can_delete_a_shipping_method(): void
    {
        $user = User::factory()->superAdmin()->create();
        $method = ShippingMethod::factory()->create();

        $this->actingAs($user)
            ->delete(route('admin.shipping-methods.destroy', $method))
            ->assertRedirect(route('admin.shipping-methods.index'))
            ->assertSessionHas('success');

        $this->assertDatabaseMissing('shipping_methods', ['id' => $method->id]);
    }

    public function test_scope_active_returns_only_active_methods(): void
    {
        ShippingMethod::factory()->create(['is_active' => true]);
        ShippingMethod::factory()->inactive()->create();

        $active = ShippingMethod::active()->get();

        $this->assertCount(1, $active);
        $this->assertTrue($active->first()->is_active);
    }

    public function test_super_admin_can_store_a_calculated_shipping_method(): void
    {
        $user = User::factory()->superAdmin()->create();

        $this->actingAs($user)
            ->post(route('admin.shipping-methods.store'), [
                'name' => 'Canada Post Expedited',
                'type' => 'calculated',
                'carrier' => 'canada_post',
                'service_code' => 'DOM.EP',
                'price' => 0,
                'is_free' => false,
                'is_active' => true,
                'sort_order' => 1,
            ])
            ->assertRedirect(route('admin.shipping-methods.index'))
            ->assertSessionHas('success');

        $this->assertDatabaseHas('shipping_methods', [
            'name' => 'Canada Post Expedited',
            'type' => 'calculated',
            'carrier' => 'canada_post',
            'service_code' => 'DOM.EP',
        ]);
    }

    public function test_calculated_method_requires_carrier_and_service_code(): void
    {
        $user = User::factory()->superAdmin()->create();

        $this->actingAs($user)
            ->post(route('admin.shipping-methods.store'), [
                'name' => 'Canada Post Expedited',
                'type' => 'calculated',
                'price' => 0,
                'is_free' => false,
                'is_active' => true,
                'sort_order' => 1,
            ])
            ->assertRedirect()
            ->assertSessionHasErrors(['carrier', 'service_code']);
    }

    public function test_flat_rate_method_does_not_require_carrier_or_service_code(): void
    {
        $user = User::factory()->superAdmin()->create();

        $this->actingAs($user)
            ->post(route('admin.shipping-methods.store'), [
                'name' => 'Standard Shipping',
                'type' => 'flat_rate',
                'price' => 15000,
                'is_free' => false,
                'is_active' => true,
                'sort_order' => 1,
            ])
            ->assertRedirect(route('admin.shipping-methods.index'));

        $this->assertDatabaseHas('shipping_methods', [
            'name' => 'Standard Shipping',
            'type' => 'flat_rate',
            'carrier' => null,
        ]);
    }

    public function test_calculated_method_type_is_persisted_via_enum(): void
    {
        $method = ShippingMethod::factory()->calculated()->create();

        $fresh = ShippingMethod::find($method->id);

        $this->assertTrue($fresh->isCalculated());
        $this->assertFalse($fresh->isFlatRate());
        $this->assertSame('canada_post', $fresh->carrier);
        $this->assertSame('DOM.EP', $fresh->service_code);
    }
}
