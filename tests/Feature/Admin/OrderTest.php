<?php

namespace Tests\Feature\Admin;

use App\Enums\OrderStatus;
use App\Models\Customer;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OrderTest extends TestCase
{
    use RefreshDatabase;

    public function test_guests_are_redirected_when_accessing_admin_orders(): void
    {
        $this->get(route('admin.orders.index'))->assertRedirect(route('login'));
    }

    public function test_guests_are_redirected_from_order_show(): void
    {
        $order = Order::factory()->create();

        $this->get(route('admin.orders.show', $order))->assertRedirect(route('login'));
    }

    public function test_guests_cannot_update_an_order(): void
    {
        $order = Order::factory()->create();

        $this->put(route('admin.orders.update', $order), ['status' => 'processing'])
            ->assertRedirect(route('login'));
    }

    public function test_guests_cannot_delete_an_order(): void
    {
        $order = Order::factory()->create();

        $this->delete(route('admin.orders.destroy', $order))->assertRedirect(route('login'));
    }

    public function test_authenticated_users_can_view_orders_index(): void
    {
        $user = User::factory()->create();
        Order::factory(3)->create();

        $this->actingAs($user)
            ->get(route('admin.orders.index'))
            ->assertOk();
    }

    public function test_authenticated_users_can_view_an_order(): void
    {
        $user = User::factory()->create();
        $order = Order::factory()->create();
        OrderItem::factory(2)->for($order)->create();

        $this->actingAs($user)
            ->get(route('admin.orders.show', $order))
            ->assertOk();
    }

    public function test_authenticated_users_can_update_order_status(): void
    {
        $user = User::factory()->create();
        $order = Order::factory()->create(['status' => OrderStatus::Pending->value]);

        $this->actingAs($user)
            ->put(route('admin.orders.update', $order), [
                'status' => OrderStatus::Processing->value,
            ])
            ->assertRedirect(route('admin.orders.show', $order));

        $this->assertDatabaseHas('orders', [
            'id' => $order->id,
            'status' => OrderStatus::Processing->value,
        ]);
    }

    public function test_update_order_rejects_invalid_status(): void
    {
        $user = User::factory()->create();
        $order = Order::factory()->create();

        $this->actingAs($user)
            ->put(route('admin.orders.update', $order), ['status' => 'invalid-status'])
            ->assertSessionHasErrors('status');
    }

    public function test_update_order_requires_status(): void
    {
        $user = User::factory()->create();
        $order = Order::factory()->create();

        $this->actingAs($user)
            ->put(route('admin.orders.update', $order), [])
            ->assertSessionHasErrors('status');
    }

    public function test_authenticated_users_can_delete_an_order(): void
    {
        $user = User::factory()->create();
        $order = Order::factory()->create();

        $this->actingAs($user)
            ->delete(route('admin.orders.destroy', $order))
            ->assertRedirect(route('admin.orders.index'));

        $this->assertDatabaseMissing('orders', ['id' => $order->id]);
    }

    public function test_order_number_is_generated_on_creation(): void
    {
        $order = Order::factory()->create();

        $this->assertNotEmpty($order->order_number);
        $this->assertStringStartsWith('ORD-', $order->fresh()->order_number);
    }

    public function test_order_belongs_to_customer(): void
    {
        $customer = Customer::factory()->create();
        $order = Order::factory()->for($customer)->create();

        $this->assertEquals($customer->id, $order->customer_id);
        $this->assertInstanceOf(Customer::class, $order->customer);
    }
}
