<?php

namespace Tests\Feature\Admin;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Minishop\Database\Seeders\RoleAndPermissionSeeder;
use Minishop\Models\Customer;
use Minishop\Models\Order;
use Minishop\Models\User;
use Tests\TestCase;

class CustomerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RoleAndPermissionSeeder::class);
    }

    public function test_guests_are_redirected_when_accessing_admin_customers(): void
    {
        $this->get(route('admin.customers.index'))->assertRedirect(route('login'));
    }

    public function test_guests_are_redirected_from_customer_show(): void
    {
        $customer = Customer::factory()->create();

        $this->get(route('admin.customers.show', $customer))->assertRedirect(route('login'));
    }

    public function test_super_admin_can_view_customers_index(): void
    {
        $user = User::factory()->superAdmin()->create();
        Customer::factory(3)->create();

        $this->actingAs($user)
            ->get(route('admin.customers.index'))
            ->assertOk();
    }

    public function test_customers_index_includes_orders_count(): void
    {
        $user = User::factory()->superAdmin()->create();
        $customer = Customer::factory()->create();
        Order::factory(2)->for($customer)->create();

        $response = $this->actingAs($user)
            ->get(route('admin.customers.index'));

        $response->assertOk();
        $response->assertInertia(
            fn ($page) => $page
                ->component('admin/Customers/Index')
                ->has('customers.data', 1)
                ->where('customers.data.0.orders_count', 2)
        );
    }

    public function test_super_admin_can_view_a_customer(): void
    {
        $user = User::factory()->superAdmin()->create();
        $customer = Customer::factory()->create();
        Order::factory(2)->for($customer)->create();

        $this->actingAs($user)
            ->get(route('admin.customers.show', $customer))
            ->assertOk();
    }

    public function test_customer_belongs_to_user(): void
    {
        $user = User::factory()->superAdmin()->create();
        $customer = Customer::factory()->for($user)->create();

        $this->assertEquals($user->id, $customer->user_id);
        $this->assertInstanceOf(User::class, $customer->user);
    }

    public function test_user_has_one_customer(): void
    {
        $user = User::factory()->superAdmin()->create();
        $customer = Customer::factory()->for($user)->create();

        $this->assertInstanceOf(Customer::class, $user->customer);
        $this->assertEquals($customer->id, $user->customer->id);
    }
}
