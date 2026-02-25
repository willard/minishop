<?php

namespace Tests\Feature\Mail;

use App\Enums\OrderStatus;
use App\Mail\OrderConfirmationMail;
use App\Mail\OrderStatusChangedMail;
use App\Models\Customer;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\ShippingMethod;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class OrderEmailTest extends TestCase
{
    use RefreshDatabase;

    private ?ShippingMethod $shippingMethod = null;

    protected function setUp(): void
    {
        parent::setUp();
        $this->shippingMethod = ShippingMethod::factory()->create(['price' => 20000, 'is_free' => false]);
    }

    /**
     * @return array<string, mixed>
     */
    private function validPayload(array $overrides = []): array
    {
        return array_merge([
            'name' => 'Maria Santos',
            'email' => 'maria@example.com',
            'address_line1' => '123 Rizal St.',
            'city' => 'Makati',
            'state' => 'Metro Manila',
            'postcode' => '1200',
            'country' => 'PH',
            'shipping_method_id' => $this->shippingMethod->id,
            'items' => [],
        ], $overrides);
    }

    public function test_confirmation_email_queued_after_checkout(): void
    {
        Mail::fake();

        $product = Product::factory()->create(['price' => 5000, 'stock_quantity' => 5]);

        $this->post(route('storefront.checkout.store'), $this->validPayload([
            'items' => [['product_id' => $product->id, 'variant_id' => null, 'quantity' => 1]],
        ]));

        Mail::assertQueued(OrderConfirmationMail::class);
    }

    public function test_confirmation_email_sent_to_customer_address(): void
    {
        Mail::fake();

        $product = Product::factory()->create(['price' => 5000, 'stock_quantity' => 5]);

        $this->post(route('storefront.checkout.store'), $this->validPayload([
            'items' => [['product_id' => $product->id, 'variant_id' => null, 'quantity' => 1]],
        ]));

        Mail::assertQueued(OrderConfirmationMail::class, function (OrderConfirmationMail $mail) {
            return $mail->hasTo('maria@example.com');
        });
    }

    public function test_status_email_queued_when_shipped(): void
    {
        Mail::fake();

        $admin = User::factory()->create();
        $order = $this->createOrderWithCustomer();

        $this->actingAs($admin)
            ->put(route('admin.orders.update', $order), ['status' => 'shipped']);

        Mail::assertQueued(OrderStatusChangedMail::class, function (OrderStatusChangedMail $mail) {
            return $mail->order->status === OrderStatus::Shipped;
        });
    }

    public function test_status_email_queued_when_delivered(): void
    {
        Mail::fake();

        $admin = User::factory()->create();
        $order = $this->createOrderWithCustomer();

        $this->actingAs($admin)
            ->put(route('admin.orders.update', $order), ['status' => 'delivered']);

        Mail::assertQueued(OrderStatusChangedMail::class, function (OrderStatusChangedMail $mail) {
            return $mail->order->status === OrderStatus::Delivered;
        });
    }

    public function test_status_email_queued_when_cancelled(): void
    {
        Mail::fake();

        $admin = User::factory()->create();
        $order = $this->createOrderWithCustomer();

        $this->actingAs($admin)
            ->put(route('admin.orders.update', $order), ['status' => 'cancelled']);

        Mail::assertQueued(OrderStatusChangedMail::class, function (OrderStatusChangedMail $mail) {
            return $mail->order->status === OrderStatus::Cancelled;
        });
    }

    public function test_no_email_on_processing_status_change(): void
    {
        Mail::fake();

        $admin = User::factory()->create();
        $order = $this->createOrderWithCustomer();

        $this->actingAs($admin)
            ->put(route('admin.orders.update', $order), ['status' => 'processing']);

        Mail::assertNotQueued(OrderStatusChangedMail::class);
    }

    public function test_no_email_when_only_notes_updated(): void
    {
        Mail::fake();

        $admin = User::factory()->create();
        $order = $this->createOrderWithCustomer();

        $this->actingAs($admin)
            ->put(route('admin.orders.update', $order), [
                'status' => $order->status->value,
                'notes' => 'Internal note only.',
            ]);

        Mail::assertNotQueued(OrderStatusChangedMail::class);
    }

    public function test_status_email_goes_to_customer_email(): void
    {
        Mail::fake();

        $admin = User::factory()->create();
        $user = User::factory()->create(['email' => 'customer@example.com']);
        $customer = Customer::factory()->create(['user_id' => $user->id]);
        $order = Order::factory()->create([
            'customer_id' => $customer->id,
            'status' => OrderStatus::Pending,
            'shipping_method_id' => $this->shippingMethod->id,
        ]);
        OrderItem::factory()->create(['order_id' => $order->id]);

        $this->actingAs($admin)
            ->put(route('admin.orders.update', $order), ['status' => 'shipped']);

        Mail::assertQueued(OrderStatusChangedMail::class, function (OrderStatusChangedMail $mail) {
            return $mail->hasTo('customer@example.com');
        });
    }

    private function createOrderWithCustomer(): Order
    {
        $user = User::factory()->create();
        $customer = Customer::factory()->create(['user_id' => $user->id]);
        $order = Order::factory()->create([
            'customer_id' => $customer->id,
            'status' => OrderStatus::Pending,
            'shipping_method_id' => $this->shippingMethod->id,
        ]);
        OrderItem::factory()->create(['order_id' => $order->id]);

        return $order;
    }
}
