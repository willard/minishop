<?php

namespace Tests\Feature\Admin;

use App\Enums\OrderStatus;
use App\Models\Customer;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\User;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Testing\Fluent\AssertableJson;
use Tests\TestCase;

class OrderTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RoleAndPermissionSeeder::class);
    }

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
        $user = User::factory()->superAdmin()->create();
        Order::factory(3)->create();

        $this->actingAs($user)
            ->get(route('admin.orders.index'))
            ->assertOk();
    }

    public function test_authenticated_users_can_view_an_order(): void
    {
        $user = User::factory()->superAdmin()->create();
        $order = Order::factory()->create();
        OrderItem::factory(2)->for($order)->create();

        $this->actingAs($user)
            ->get(route('admin.orders.show', $order))
            ->assertOk();
    }

    public function test_authenticated_users_can_update_order_status(): void
    {
        $user = User::factory()->superAdmin()->create();
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
        $user = User::factory()->superAdmin()->create();
        $order = Order::factory()->create();

        $this->actingAs($user)
            ->put(route('admin.orders.update', $order), ['status' => 'invalid-status'])
            ->assertSessionHasErrors('status');
    }

    public function test_update_order_requires_status(): void
    {
        $user = User::factory()->superAdmin()->create();
        $order = Order::factory()->create();

        $this->actingAs($user)
            ->put(route('admin.orders.update', $order), [])
            ->assertSessionHasErrors('status');
    }

    public function test_authenticated_users_can_delete_an_order(): void
    {
        $user = User::factory()->superAdmin()->create();
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

    public function test_guests_cannot_access_order_invoice(): void
    {
        $order = Order::factory()->create();

        $this->get(route('admin.orders.invoice', $order))->assertRedirect(route('login'));
    }

    public function test_authenticated_users_can_download_order_invoice(): void
    {
        $user = User::factory()->superAdmin()->create();
        $order = Order::factory()->create();
        OrderItem::factory(2)->for($order)->create();

        $response = $this->actingAs($user)
            ->get(route('admin.orders.invoice', $order));

        $response->assertOk();
        $response->assertHeader('Content-Type', 'application/pdf');
    }

    public function test_invoice_content_disposition_contains_order_number(): void
    {
        $user = User::factory()->superAdmin()->create();
        $order = Order::factory()->create();
        OrderItem::factory(2)->for($order)->create();

        $response = $this->actingAs($user)
            ->get(route('admin.orders.invoice', $order));

        $response->assertHeader(
            'Content-Disposition',
            'attachment; filename=invoice-'.$order->order_number.'.pdf'
        );
    }

    public function test_orders_index_passes_filters_and_statuses_as_props(): void
    {
        $user = User::factory()->superAdmin()->create();

        $this->actingAs($user)
            ->get(route('admin.orders.index'))
            ->assertInertia(fn (AssertableJson $page) => $page
                ->has('filters')
                ->has('statuses')
            );
    }

    public function test_orders_index_can_be_filtered_by_status(): void
    {
        $user = User::factory()->superAdmin()->create();
        Order::factory()->pending()->create();
        Order::factory()->processing()->create();

        $this->actingAs($user)
            ->get(route('admin.orders.index', ['status' => 'pending']))
            ->assertOk()
            ->assertInertia(fn (AssertableJson $page) => $page
                ->component('admin/Orders/Index')
                ->where('filters.status', 'pending')
                ->has('orders.data', 1)
            );
    }

    public function test_orders_index_can_be_searched_by_order_number(): void
    {
        $user = User::factory()->superAdmin()->create();
        $target = Order::factory()->create(['order_number' => 'ORD-SEARCH-001']);
        Order::factory()->create(['order_number' => 'ORD-SEARCH-002']);

        $this->actingAs($user)
            ->get(route('admin.orders.index', ['search' => 'ORD-SEARCH-001']))
            ->assertOk()
            ->assertInertia(fn (AssertableJson $page) => $page
                ->has('orders.data', 1)
                ->where('orders.data.0.order_number', $target->order_number)
            );
    }

    public function test_orders_index_can_be_searched_by_customer_name(): void
    {
        $user = User::factory()->superAdmin()->create();
        $namedUser = User::factory()->create(['name' => 'Specific Customer']);
        $customer = Customer::factory()->for($namedUser, 'user')->create();
        Order::factory()->for($customer)->create();
        Order::factory()->create();

        $this->actingAs($user)
            ->get(route('admin.orders.index', ['search' => 'Specific Customer']))
            ->assertOk()
            ->assertInertia(fn (AssertableJson $page) => $page
                ->has('orders.data', 1)
            );
    }

    public function test_valid_status_transition_is_accepted(): void
    {
        $user = User::factory()->superAdmin()->create();
        $order = Order::factory()->pending()->create();

        $this->actingAs($user)
            ->put(route('admin.orders.update', $order), ['status' => 'processing'])
            ->assertRedirect(route('admin.orders.show', $order));

        $this->assertDatabaseHas('orders', ['id' => $order->id, 'status' => 'processing']);
    }

    public function test_invalid_backwards_transition_is_rejected(): void
    {
        $user = User::factory()->superAdmin()->create();
        $order = Order::factory()->delivered()->create();

        $this->actingAs($user)
            ->put(route('admin.orders.update', $order), ['status' => 'pending'])
            ->assertSessionHasErrors('status');
    }

    public function test_terminal_cancelled_order_cannot_be_updated(): void
    {
        $user = User::factory()->superAdmin()->create();
        $order = Order::factory()->cancelled()->create();

        $this->actingAs($user)
            ->put(route('admin.orders.update', $order), ['status' => 'processing'])
            ->assertSessionHasErrors('status');
    }

    public function test_terminal_refunded_order_cannot_be_updated(): void
    {
        $user = User::factory()->superAdmin()->create();
        $order = Order::factory()->create(['status' => OrderStatus::Refunded->value]);

        $this->actingAs($user)
            ->put(route('admin.orders.update', $order), ['status' => 'delivered'])
            ->assertSessionHasErrors('status');
    }

    public function test_delivered_order_can_be_refunded(): void
    {
        $user = User::factory()->superAdmin()->create();
        $order = Order::factory()->delivered()->create();

        $this->actingAs($user)
            ->put(route('admin.orders.update', $order), ['status' => 'refunded'])
            ->assertRedirect(route('admin.orders.show', $order));

        $this->assertDatabaseHas('orders', ['id' => $order->id, 'status' => 'refunded']);
    }

    public function test_shipped_order_can_be_cancelled(): void
    {
        $user = User::factory()->superAdmin()->create();
        $order = Order::factory()->shipped()->create();

        $this->actingAs($user)
            ->put(route('admin.orders.update', $order), ['status' => 'cancelled'])
            ->assertRedirect(route('admin.orders.show', $order));

        $this->assertDatabaseHas('orders', ['id' => $order->id, 'status' => 'cancelled']);
    }
}
