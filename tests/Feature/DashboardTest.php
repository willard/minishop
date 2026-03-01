<?php

namespace Tests\Feature;

use App\Enums\OrderStatus;
use App\Models\Customer;
use App\Models\Order;
use App\Models\Product;
use App\Models\StoreSettings;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DashboardTest extends TestCase
{
    use RefreshDatabase;

    public function test_guests_are_redirected_to_the_login_page(): void
    {
        $response = $this->get(route('dashboard'));
        $response->assertRedirect(route('login'));
    }

    public function test_authenticated_users_can_visit_the_dashboard(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $response = $this->get(route('dashboard'));
        $response->assertOk();
    }

    public function test_dashboard_passes_required_props(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get(route('dashboard'))
            ->assertInertia(
                fn ($page) => $page
                    ->component('Dashboard')
                    ->has('totalRevenue')
                    ->has('totalOrders')
                    ->has('totalCustomers')
                    ->has('lowStockCount')
                    ->has('lowStockThreshold')
                    ->has('recentOrders')
                    ->has('lowStockProducts')
            );
    }

    public function test_dashboard_excludes_cancelled_orders_from_revenue(): void
    {
        $user = User::factory()->create();

        $customer = Customer::factory()->create();
        Order::factory()->for($customer)->create([
            'status' => OrderStatus::Delivered->value,
            'total_amount' => 5000,
        ]);
        Order::factory()->for($customer)->create([
            'status' => OrderStatus::Cancelled->value,
            'total_amount' => 3000,
        ]);
        Order::factory()->for($customer)->create([
            'status' => OrderStatus::Refunded->value,
            'total_amount' => 2000,
        ]);

        $this->actingAs($user)
            ->get(route('dashboard'))
            ->assertInertia(
                fn ($page) => $page
                    ->component('Dashboard')
                    ->where('totalRevenue', 5000)
            );
    }

    public function test_dashboard_counts_low_stock_products_correctly(): void
    {
        $user = User::factory()->create();
        StoreSettings::current()->update(['low_stock_threshold' => 10]);

        Product::factory()->create(['stock_quantity' => 5, 'is_active' => true]);
        Product::factory()->create(['stock_quantity' => 10, 'is_active' => true]);
        Product::factory()->create(['stock_quantity' => 11, 'is_active' => true]);
        Product::factory()->create(['stock_quantity' => 3, 'is_active' => false]);

        $this->actingAs($user)
            ->get(route('dashboard'))
            ->assertInertia(
                fn ($page) => $page
                    ->component('Dashboard')
                    ->where('lowStockCount', 2)
                    ->where('lowStockThreshold', 10)
            );
    }

    public function test_dashboard_uses_custom_low_stock_threshold(): void
    {
        $user = User::factory()->create();
        StoreSettings::current()->update(['low_stock_threshold' => 5]);

        Product::factory()->create(['stock_quantity' => 5, 'is_active' => true]);
        Product::factory()->create(['stock_quantity' => 10, 'is_active' => true]);
        Product::factory()->create(['stock_quantity' => 3, 'is_active' => true]);

        $this->actingAs($user)
            ->get(route('dashboard'))
            ->assertInertia(
                fn ($page) => $page
                    ->component('Dashboard')
                    ->where('lowStockCount', 2)
                    ->where('lowStockThreshold', 5)
            );
    }

    public function test_dashboard_recent_orders_are_limited_to_five(): void
    {
        $user = User::factory()->create();
        $customer = Customer::factory()->create();
        Order::factory(7)->for($customer)->create();

        $this->actingAs($user)
            ->get(route('dashboard'))
            ->assertInertia(
                fn ($page) => $page
                    ->component('Dashboard')
                    ->has('recentOrders', 5)
            );
    }
}
