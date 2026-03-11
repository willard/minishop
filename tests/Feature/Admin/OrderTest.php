<?php

namespace Tests\Feature\Admin;

use App\Enums\OrderStatus;
use App\Models\Coupon;
use App\Models\Customer;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\ProductVariant;
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

    public function test_super_admin_can_view_orders_index(): void
    {
        $user = User::factory()->superAdmin()->create();
        Order::factory(3)->create();

        $this->actingAs($user)
            ->get(route('admin.orders.index'))
            ->assertOk();
    }

    public function test_super_admin_can_view_an_order(): void
    {
        $user = User::factory()->superAdmin()->create();
        $order = Order::factory()->create();
        OrderItem::factory(2)->for($order)->create();

        $this->actingAs($user)
            ->get(route('admin.orders.show', $order))
            ->assertOk();
    }

    public function test_super_admin_can_update_order_status(): void
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

    public function test_super_admin_can_delete_an_order(): void
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

    public function test_super_admin_can_download_order_invoice(): void
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

    public function test_guests_are_redirected_from_create_order(): void
    {
        $this->get(route('admin.orders.create'))->assertRedirect(route('login'));
    }

    public function test_guests_cannot_store_an_order(): void
    {
        $this->post(route('admin.orders.store'), [])->assertRedirect(route('login'));
    }

    public function test_super_admin_can_view_create_order_form(): void
    {
        $user = User::factory()->superAdmin()->create();

        $this->actingAs($user)
            ->get(route('admin.orders.create'))
            ->assertOk()
            ->assertInertia(fn (AssertableJson $page) => $page
                ->component('admin/Orders/Create')
                ->has('customers')
                ->has('products')
                ->has('shippingMethods')
                ->has('statuses')
                ->has('taxRate')
            );
    }

    public function test_super_admin_can_create_an_order_manually(): void
    {
        $user = User::factory()->superAdmin()->create();
        $customer = Customer::factory()->create();
        $product = Product::factory()->create(['name' => 'Test Widget', 'price' => 2000, 'sku' => 'TW-001', 'stock_quantity' => 10]);

        $this->actingAs($user)
            ->post(route('admin.orders.store'), [
                'customer_id' => $customer->id,
                'status' => 'pending',
                'items' => [
                    ['product_id' => $product->id, 'quantity' => 2, 'unit_price' => 2000],
                ],
                'shipping_name' => 'Jane Doe',
                'shipping_address_line1' => '123 Main St',
                'shipping_city' => 'Manila',
                'shipping_state' => 'Metro Manila',
                'shipping_postcode' => '1000',
                'shipping_country' => 'PH',
            ])
            ->assertRedirect();

        $order = Order::query()->latest()->first();
        $this->assertNotNull($order);
        $this->assertEquals($customer->id, $order->customer_id);
        $this->assertEquals(4000, $order->subtotal);
        $this->assertCount(1, $order->items);
        $this->assertEquals('Test Widget', $order->items->first()->product_name);
        $this->assertEquals('TW-001', $order->items->first()->product_sku);
        $this->assertNull($order->items->first()->variant_id);
    }

    public function test_creating_an_order_decrements_product_stock(): void
    {
        $user = User::factory()->superAdmin()->create();
        $customer = Customer::factory()->create();
        $product = Product::factory()->create(['stock_quantity' => 10, 'price' => 1000]);

        $this->actingAs($user)
            ->post(route('admin.orders.store'), [
                'customer_id' => $customer->id,
                'status' => 'pending',
                'items' => [['product_id' => $product->id, 'quantity' => 3, 'unit_price' => 1000]],
                'shipping_name' => 'Jane Doe',
                'shipping_address_line1' => '123 Main St',
                'shipping_city' => 'Manila',
                'shipping_state' => 'Metro Manila',
                'shipping_postcode' => '1000',
                'shipping_country' => 'PH',
            ])
            ->assertRedirect();

        $this->assertEquals(7, $product->fresh()->stock_quantity);
    }

    public function test_super_admin_can_create_an_order_with_a_variant(): void
    {
        $user = User::factory()->superAdmin()->create();
        $customer = Customer::factory()->create();
        $product = Product::factory()->create(['name' => 'T-Shirt', 'price' => 1500, 'sku' => 'TS-BASE', 'stock_quantity' => 5]);
        $variant = ProductVariant::factory()->for($product)->create(['sku' => 'TS-RED-L', 'price' => 1800, 'stock_quantity' => 8]);

        $this->actingAs($user)
            ->post(route('admin.orders.store'), [
                'customer_id' => $customer->id,
                'status' => 'pending',
                'items' => [
                    ['product_id' => $product->id, 'variant_id' => $variant->id, 'quantity' => 2, 'unit_price' => 1800],
                ],
                'shipping_name' => 'Jane Doe',
                'shipping_address_line1' => '123 Main St',
                'shipping_city' => 'Manila',
                'shipping_state' => 'Metro Manila',
                'shipping_postcode' => '1000',
                'shipping_country' => 'PH',
            ])
            ->assertRedirect();

        $order = Order::query()->latest()->first();
        $item = $order->items->first();
        $this->assertEquals($variant->id, $item->variant_id);
        $this->assertEquals('TS-RED-L', $item->product_sku);
        $this->assertEquals(6, $variant->fresh()->stock_quantity);
        $this->assertEquals(5, $product->fresh()->stock_quantity);
    }

    public function test_store_order_applies_valid_coupon(): void
    {
        $user = User::factory()->superAdmin()->create();
        $customer = Customer::factory()->create();
        $product = Product::factory()->create(['price' => 10000, 'stock_quantity' => 5]);
        $coupon = Coupon::factory()->create(['code' => 'SAVE500', 'type' => 'fixed', 'value' => 500, 'is_active' => true]);

        $this->actingAs($user)
            ->post(route('admin.orders.store'), [
                'customer_id' => $customer->id,
                'status' => 'pending',
                'items' => [['product_id' => $product->id, 'quantity' => 1, 'unit_price' => 10000]],
                'coupon_code' => 'SAVE500',
                'shipping_name' => 'Jane Doe',
                'shipping_address_line1' => '123 Main St',
                'shipping_city' => 'Manila',
                'shipping_state' => 'Metro Manila',
                'shipping_postcode' => '1000',
                'shipping_country' => 'PH',
            ])
            ->assertRedirect();

        $order = Order::query()->latest()->first();
        $this->assertEquals(500, $order->discount_amount);
        $this->assertEquals($coupon->id, $order->coupon_id);
        $this->assertEquals(1, $coupon->fresh()->used_count);
    }

    public function test_store_order_rejects_invalid_coupon_code(): void
    {
        $user = User::factory()->superAdmin()->create();
        $customer = Customer::factory()->create();
        $product = Product::factory()->create(['stock_quantity' => 5]);

        $this->actingAs($user)
            ->post(route('admin.orders.store'), [
                'customer_id' => $customer->id,
                'status' => 'pending',
                'items' => [['product_id' => $product->id, 'quantity' => 1, 'unit_price' => 1000]],
                'coupon_code' => 'DOESNOTEXIST',
                'shipping_name' => 'Jane Doe',
                'shipping_address_line1' => '123 Main St',
                'shipping_city' => 'Manila',
                'shipping_state' => 'Metro Manila',
                'shipping_postcode' => '1000',
                'shipping_country' => 'PH',
            ])
            ->assertSessionHasErrors('coupon_code');
    }

    public function test_store_order_requires_at_least_one_item(): void
    {
        $user = User::factory()->superAdmin()->create();
        $customer = Customer::factory()->create();

        $this->actingAs($user)
            ->post(route('admin.orders.store'), [
                'customer_id' => $customer->id,
                'status' => 'pending',
                'items' => [],
                'shipping_name' => 'Jane Doe',
                'shipping_address_line1' => '123 Main St',
                'shipping_city' => 'Manila',
                'shipping_state' => 'Metro Manila',
                'shipping_postcode' => '1000',
                'shipping_country' => 'PH',
            ])
            ->assertSessionHasErrors('items');
    }

    public function test_store_order_requires_customer(): void
    {
        $user = User::factory()->superAdmin()->create();
        $product = Product::factory()->create(['stock_quantity' => 5]);

        $this->actingAs($user)
            ->post(route('admin.orders.store'), [
                'status' => 'pending',
                'items' => [['product_id' => $product->id, 'quantity' => 1, 'unit_price' => 1000]],
                'shipping_name' => 'Jane Doe',
                'shipping_address_line1' => '123 Main St',
                'shipping_city' => 'Manila',
                'shipping_state' => 'Metro Manila',
                'shipping_postcode' => '1000',
                'shipping_country' => 'PH',
            ])
            ->assertSessionHasErrors('customer_id');
    }

    public function test_products_are_passed_with_their_variants_on_create(): void
    {
        $user = User::factory()->superAdmin()->create();
        $product = Product::factory()->create(['is_active' => true]);
        ProductVariant::factory()->for($product)->create(['is_active' => true]);

        $this->actingAs($user)
            ->get(route('admin.orders.create'))
            ->assertOk()
            ->assertInertia(fn (AssertableJson $page) => $page
                ->has('products', 1, fn (AssertableJson $p) => $p
                    ->has('variants', 1)
                    ->etc()
                )
            );
    }
}
