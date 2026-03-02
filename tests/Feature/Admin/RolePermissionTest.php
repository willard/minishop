<?php

namespace Tests\Feature\Admin;

use App\Enums\OrderStatus;
use App\Models\Category;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\User;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RolePermissionTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RoleAndPermissionSeeder::class);
    }

    public function test_user_without_role_gets_403_on_admin_routes(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get(route('dashboard'))
            ->assertForbidden();

        $this->actingAs($user)
            ->get(route('admin.products.index'))
            ->assertForbidden();
    }

    public function test_super_admin_can_access_all_routes(): void
    {
        $user = User::factory()->superAdmin()->create();

        $this->actingAs($user)->get(route('dashboard'))->assertOk();
        $this->actingAs($user)->get(route('admin.products.index'))->assertOk();
        $this->actingAs($user)->get(route('admin.categories.index'))->assertOk();
        $this->actingAs($user)->get(route('admin.orders.index'))->assertOk();
        $this->actingAs($user)->get(route('admin.customers.index'))->assertOk();
        $this->actingAs($user)->get(route('admin.coupons.index'))->assertOk();
        $this->actingAs($user)->get(route('admin.shipping-methods.index'))->assertOk();
        $this->actingAs($user)->get(route('admin.activity-log.index'))->assertOk();
        $this->actingAs($user)->get(route('admin.settings.edit'))->assertOk();
    }

    public function test_admin_can_access_most_routes(): void
    {
        $user = User::factory()->admin()->create();

        $this->actingAs($user)->get(route('dashboard'))->assertOk();
        $this->actingAs($user)->get(route('admin.products.index'))->assertOk();
        $this->actingAs($user)->get(route('admin.categories.index'))->assertOk();
        $this->actingAs($user)->get(route('admin.orders.index'))->assertOk();
        $this->actingAs($user)->get(route('admin.customers.index'))->assertOk();
        $this->actingAs($user)->get(route('admin.coupons.index'))->assertOk();
        $this->actingAs($user)->get(route('admin.shipping-methods.index'))->assertOk();
        $this->actingAs($user)->get(route('admin.activity-log.index'))->assertOk();
    }

    public function test_admin_cannot_access_settings(): void
    {
        $user = User::factory()->admin()->create();

        $this->actingAs($user)
            ->get(route('admin.settings.edit'))
            ->assertForbidden();

        $this->actingAs($user)
            ->put(route('admin.settings.update'), [
                'currency' => 'USD',
                'currency_locale' => 'en-US',
                'tax_rate' => 10,
                'active_payment_gateway' => 'cod',
                'low_stock_threshold' => 10,
            ])
            ->assertForbidden();
    }

    public function test_manager_can_view_products_but_not_delete(): void
    {
        $user = User::factory()->manager()->create();
        $product = Product::factory()->create();

        $this->actingAs($user)
            ->get(route('admin.products.index'))
            ->assertOk();

        $this->actingAs($user)
            ->delete(route('admin.products.destroy', $product))
            ->assertForbidden();
    }

    public function test_manager_can_view_orders_but_not_delete(): void
    {
        $user = User::factory()->manager()->create();
        $order = \App\Models\Order::factory()->create();

        $this->actingAs($user)
            ->get(route('admin.orders.index'))
            ->assertOk();

        $this->actingAs($user)
            ->delete(route('admin.orders.destroy', $order))
            ->assertForbidden();
    }

    public function test_manager_cannot_access_coupons(): void
    {
        $user = User::factory()->manager()->create();

        $this->actingAs($user)
            ->get(route('admin.coupons.index'))
            ->assertForbidden();
    }

    public function test_manager_cannot_access_shipping_methods(): void
    {
        $user = User::factory()->manager()->create();

        $this->actingAs($user)
            ->get(route('admin.shipping-methods.index'))
            ->assertForbidden();
    }

    public function test_manager_cannot_access_settings(): void
    {
        $user = User::factory()->manager()->create();

        $this->actingAs($user)
            ->get(route('admin.settings.edit'))
            ->assertForbidden();
    }

    public function test_manager_cannot_access_activity_log(): void
    {
        $user = User::factory()->manager()->create();

        $this->actingAs($user)
            ->get(route('admin.activity-log.index'))
            ->assertForbidden();
    }

    public function test_manager_can_create_products(): void
    {
        $user = User::factory()->manager()->create();

        $this->actingAs($user)
            ->post(route('admin.products.store'), [
                'name' => 'Manager Product',
                'price' => 1000,
                'stock_quantity' => 10,
            ])
            ->assertRedirect()
            ->assertSessionDoesntHaveErrors();

        $this->assertDatabaseHas('products', ['name' => 'Manager Product']);
    }

    public function test_manager_can_update_order_status(): void
    {
        $user = User::factory()->manager()->create();
        $order = Order::factory()->pending()->create();

        $this->actingAs($user)
            ->put(route('admin.orders.update', $order), [
                'status' => OrderStatus::Processing->value,
            ])
            ->assertRedirect()
            ->assertSessionDoesntHaveErrors();
    }

    public function test_manager_cannot_update_a_category(): void
    {
        $user = User::factory()->manager()->create();
        $category = Category::factory()->create();

        $this->actingAs($user)
            ->put(route('admin.categories.update', $category), [
                'name' => 'Updated Name',
                'is_active' => true,
            ])
            ->assertForbidden();
    }

    public function test_manager_cannot_delete_a_category(): void
    {
        $user = User::factory()->manager()->create();
        $category = Category::factory()->create();

        $this->actingAs($user)
            ->delete(route('admin.categories.destroy', $category))
            ->assertForbidden();
    }

    public function test_manager_can_view_customers(): void
    {
        $user = User::factory()->manager()->create();

        $this->actingAs($user)
            ->get(route('admin.customers.index'))
            ->assertOk();
    }

    public function test_manager_can_view_categories_but_not_create(): void
    {
        $user = User::factory()->manager()->create();

        $this->actingAs($user)
            ->get(route('admin.categories.index'))
            ->assertOk();

        $this->actingAs($user)
            ->post(route('admin.categories.store'), [
                'name' => 'New Category',
                'is_active' => true,
            ])
            ->assertForbidden();
    }

    public function test_admin_can_download_order_invoice(): void
    {
        $user = User::factory()->admin()->create();
        $order = Order::factory()->create();
        OrderItem::factory(2)->for($order)->create();

        $this->actingAs($user)
            ->get(route('admin.orders.invoice', $order))
            ->assertOk()
            ->assertHeader('Content-Type', 'application/pdf');
    }

    public function test_manager_can_download_order_invoice(): void
    {
        $user = User::factory()->manager()->create();
        $order = Order::factory()->create();
        OrderItem::factory(2)->for($order)->create();

        $this->actingAs($user)
            ->get(route('admin.orders.invoice', $order))
            ->assertOk()
            ->assertHeader('Content-Type', 'application/pdf');
    }

    public function test_user_without_role_cannot_download_invoice(): void
    {
        $user = User::factory()->create();
        $order = Order::factory()->create();

        $this->actingAs($user)
            ->get(route('admin.orders.invoice', $order))
            ->assertForbidden();
    }

    public function test_inertia_shares_roles_and_permissions(): void
    {
        $user = User::factory()->admin()->create();

        $this->actingAs($user)
            ->get(route('dashboard'))
            ->assertInertia(fn ($page) => $page
                ->has('auth.roles')
                ->has('auth.permissions')
                ->where('auth.roles', fn ($roles) => in_array('admin', $roles->toArray()))
            );
    }
}
