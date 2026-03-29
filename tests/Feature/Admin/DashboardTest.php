<?php

namespace Tests\Feature\Admin;

use App\Enums\OrderStatus;
use App\Models\Order;
use App\Models\User;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DashboardTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RoleAndPermissionSeeder::class);
        $this->admin = User::factory()->superAdmin()->create();
    }

    public function test_guests_are_redirected_from_dashboard(): void
    {
        $this->get(route('dashboard'))->assertRedirect(route('login'));
    }

    public function test_admin_can_view_dashboard(): void
    {
        $this->actingAs($this->admin)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertInertia(
                fn ($page) => $page
                    ->component('Dashboard')
                    ->has('totalRevenue')
                    ->has('totalOrders')
                    ->has('totalCustomers')
                    ->has('lowStockCount')
                    ->has('revenueChart'),
            );
    }

    public function test_revenue_chart_has_12_months(): void
    {
        $this->actingAs($this->admin)
            ->get(route('dashboard'))
            ->assertInertia(
                fn ($page) => $page
                    ->has('revenueChart', 12)
                    ->where('revenueChart.0.revenue', fn ($v) => is_int($v))
                    ->where('revenueChart.0.label', fn ($v) => is_string($v)),
            );
    }

    public function test_revenue_chart_excludes_cancelled_and_refunded_orders(): void
    {
        Order::factory()->create(['total_amount' => 5000, 'status' => OrderStatus::Delivered]);
        Order::factory()->create(['total_amount' => 3000, 'status' => OrderStatus::Cancelled]);
        Order::factory()->create(['total_amount' => 2000, 'status' => OrderStatus::Refunded]);

        // revenueChart index 11 is the current month (range: 11 months ago → now)
        $this->actingAs($this->admin)
            ->get(route('dashboard'))
            ->assertInertia(
                fn ($page) => $page
                    ->where('totalRevenue', 5000)
                    ->where('revenueChart.11.revenue', 5000),
            );
    }

    public function test_revenue_chart_sums_revenue_per_month(): void
    {
        Order::factory()->count(3)->create([
            'total_amount' => 1000,
            'status' => OrderStatus::Delivered,
            'created_at' => now(),
        ]);

        // revenueChart index 11 is the current month (range: 11 months ago → now)
        $this->actingAs($this->admin)
            ->get(route('dashboard'))
            ->assertInertia(
                fn ($page) => $page->where('revenueChart.11.revenue', 3000),
            );
    }
}
