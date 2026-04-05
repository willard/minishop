<?php

namespace Tests\Feature\Admin;

use App\Actions\CreateOrderAction;
use App\Actions\ProcessReturnAction;
use App\Data\LowStockSubject;
use App\Models\Customer;
use App\Models\Order;
use App\Models\OrderReturn;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\ReturnItem;
use App\Models\StoreSettings;
use App\Models\User;
use App\Notifications\LowStockAlert;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class LowStockAlertTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RoleAndPermissionSeeder::class);
    }

    // -------------------------------------------------------------------------
    // Product observer tests
    // -------------------------------------------------------------------------

    public function test_notification_is_sent_when_stock_drops_below_threshold(): void
    {
        Notification::fake();

        $user = User::factory()->superAdmin()->create();
        StoreSettings::current()->update(['low_stock_threshold' => 10]);

        $product = Product::factory()->create(['stock_quantity' => 20]);

        $this->actingAs($user)
            ->put(route('admin.products.update', $product), [
                'name' => $product->name,
                'price' => $product->price,
                'stock_quantity' => 5,
            ]);

        Notification::assertSentTo($user, LowStockAlert::class, function (LowStockAlert $notification) use ($product) {
            return $notification->subject->name === $product->name;
        });
    }

    public function test_notification_is_not_sent_when_stock_is_above_threshold(): void
    {
        Notification::fake();

        $user = User::factory()->superAdmin()->create();
        StoreSettings::current()->update(['low_stock_threshold' => 10]);

        $product = Product::factory()->create(['stock_quantity' => 50]);

        $this->actingAs($user)
            ->put(route('admin.products.update', $product), [
                'name' => $product->name,
                'price' => $product->price,
                'stock_quantity' => 30,
            ]);

        Notification::assertNothingSent();
    }

    public function test_notification_is_sent_only_once_until_restocked(): void
    {
        Notification::fake();

        $user = User::factory()->superAdmin()->create();
        StoreSettings::current()->update(['low_stock_threshold' => 10]);

        $product = Product::factory()->create(['stock_quantity' => 20]);

        // First drop below threshold
        $this->actingAs($user)
            ->put(route('admin.products.update', $product), [
                'name' => $product->name,
                'price' => $product->price,
                'stock_quantity' => 5,
            ]);

        // Second drop (already below, already notified)
        $this->actingAs($user)
            ->put(route('admin.products.update', $product->fresh()), [
                'name' => $product->name,
                'price' => $product->price,
                'stock_quantity' => 3,
            ]);

        Notification::assertSentToTimes($user, LowStockAlert::class, 1);
    }

    public function test_low_stock_notified_flag_resets_when_restocked_above_threshold(): void
    {
        Notification::fake();

        $user = User::factory()->superAdmin()->create();
        StoreSettings::current()->update(['low_stock_threshold' => 10]);

        $product = Product::factory()->create(['stock_quantity' => 20]);

        // Drop below threshold
        $this->actingAs($user)
            ->put(route('admin.products.update', $product), [
                'name' => $product->name,
                'price' => $product->price,
                'stock_quantity' => 5,
            ]);

        $this->assertTrue($product->fresh()->low_stock_notified);

        // Restock above threshold
        $this->actingAs($user)
            ->put(route('admin.products.update', $product->fresh()), [
                'name' => $product->name,
                'price' => $product->price,
                'stock_quantity' => 25,
            ]);

        $this->assertFalse($product->fresh()->low_stock_notified);
    }

    public function test_notification_is_sent_again_after_restock_and_redrop(): void
    {
        Notification::fake();

        $user = User::factory()->superAdmin()->create();
        StoreSettings::current()->update(['low_stock_threshold' => 10]);

        $product = Product::factory()->create(['stock_quantity' => 20]);

        // First drop
        $this->actingAs($user)
            ->put(route('admin.products.update', $product), [
                'name' => $product->name,
                'price' => $product->price,
                'stock_quantity' => 5,
            ]);

        // Restock
        $this->actingAs($user)
            ->put(route('admin.products.update', $product->fresh()), [
                'name' => $product->name,
                'price' => $product->price,
                'stock_quantity' => 25,
            ]);

        // Second drop
        $this->actingAs($user)
            ->put(route('admin.products.update', $product->fresh()), [
                'name' => $product->name,
                'price' => $product->price,
                'stock_quantity' => 3,
            ]);

        Notification::assertSentToTimes($user, LowStockAlert::class, 2);
    }

    public function test_notification_is_not_sent_when_stock_field_unchanged(): void
    {
        Notification::fake();

        $user = User::factory()->superAdmin()->create();
        StoreSettings::current()->update(['low_stock_threshold' => 10]);

        $product = Product::factory()->create(['stock_quantity' => 5]);

        // Update name only, stock unchanged
        $this->actingAs($user)
            ->put(route('admin.products.update', $product), [
                'name' => 'New Name',
                'price' => $product->price,
                'stock_quantity' => 5,
            ]);

        Notification::assertNothingSent();
    }

    public function test_notification_is_sent_when_stock_equals_threshold(): void
    {
        Notification::fake();

        $user = User::factory()->superAdmin()->create();
        StoreSettings::current()->update(['low_stock_threshold' => 10]);

        $product = Product::factory()->create(['stock_quantity' => 20]);

        $this->actingAs($user)
            ->put(route('admin.products.update', $product), [
                'name' => $product->name,
                'price' => $product->price,
                'stock_quantity' => 10,
            ]);

        Notification::assertSentTo($user, LowStockAlert::class);
    }

    public function test_notification_is_sent_to_admin_users_only(): void
    {
        Notification::fake();

        $adminUsers = User::factory(2)->superAdmin()->create();
        $customerUser = User::factory()->create();
        Customer::factory()->create(['user_id' => $customerUser->id]);

        StoreSettings::current()->update(['low_stock_threshold' => 10]);

        $product = Product::factory()->create(['stock_quantity' => 20]);

        $this->actingAs($adminUsers->first())
            ->put(route('admin.products.update', $product), [
                'name' => $product->name,
                'price' => $product->price,
                'stock_quantity' => 5,
            ]);

        foreach ($adminUsers as $admin) {
            Notification::assertSentTo($admin, LowStockAlert::class);
        }

        Notification::assertNotSentTo($customerUser, LowStockAlert::class);
    }

    // -------------------------------------------------------------------------
    // Variant observer tests
    // -------------------------------------------------------------------------

    public function test_notification_sent_when_variant_stock_drops_to_threshold(): void
    {
        Notification::fake();

        $admin = User::factory()->superAdmin()->create();
        StoreSettings::current()->update(['low_stock_threshold' => 5]);

        $variant = ProductVariant::factory()->atThreshold(5)->create();

        // Simulate observer firing by saving the variant after changing stock
        $variant->stock_quantity = 20;
        $variant->save();
        $variant->stock_quantity = 5;
        $variant->save();

        Notification::assertSentTo($admin, LowStockAlert::class, function (LowStockAlert $notification) {
            return $notification->subject instanceof LowStockSubject;
        });
    }

    public function test_notification_sent_when_variant_stock_drops_below_threshold(): void
    {
        Notification::fake();

        $admin = User::factory()->superAdmin()->create();
        StoreSettings::current()->update(['low_stock_threshold' => 5]);

        $variant = ProductVariant::factory()->create(['stock_quantity' => 20, 'low_stock_threshold' => null]);

        $variant->stock_quantity = 3;
        $variant->save();

        Notification::assertSentTo($admin, LowStockAlert::class);
    }

    public function test_notification_not_sent_when_variant_already_notified(): void
    {
        Notification::fake();

        User::factory()->superAdmin()->create();
        StoreSettings::current()->update(['low_stock_threshold' => 5]);

        $variant = ProductVariant::factory()->alreadyNotified(5)->create();

        $variant->stock_quantity = 2;
        $variant->save();

        Notification::assertNothingSent();
    }

    public function test_variant_low_stock_notified_flag_resets_when_restocked_above_threshold(): void
    {
        Notification::fake();

        User::factory()->superAdmin()->create();
        StoreSettings::current()->update(['low_stock_threshold' => 5]);

        $variant = ProductVariant::factory()->alreadyNotified(5)->create();

        $variant->stock_quantity = 20;
        $variant->save();

        $this->assertFalse($variant->fresh()->low_stock_notified);
    }

    public function test_notification_sent_again_after_variant_restock_and_redrop(): void
    {
        Notification::fake();

        $admin = User::factory()->superAdmin()->create();
        StoreSettings::current()->update(['low_stock_threshold' => 5]);

        $variant = ProductVariant::factory()->create(['stock_quantity' => 20]);

        // Drop below threshold
        $variant->stock_quantity = 3;
        $variant->save();

        // Restock above threshold
        $variant = $variant->fresh();
        $variant->stock_quantity = 20;
        $variant->save();

        // Drop again
        $variant = $variant->fresh();
        $variant->stock_quantity = 2;
        $variant->save();

        Notification::assertSentToTimes($admin, LowStockAlert::class, 2);
    }

    public function test_notification_uses_global_threshold_when_variant_threshold_is_null(): void
    {
        Notification::fake();

        $admin = User::factory()->superAdmin()->create();
        // Set global threshold to 5; variant has no per-variant threshold
        StoreSettings::current()->update(['low_stock_threshold' => 5]);

        $variant = ProductVariant::factory()->create(['stock_quantity' => 20, 'low_stock_threshold' => null]);

        // Drop to 3 — below the global threshold of 5
        $variant->stock_quantity = 3;
        $variant->save();

        Notification::assertSentTo($admin, LowStockAlert::class);
    }

    public function test_notification_targets_only_verified_admin_users(): void
    {
        Notification::fake();

        $verifiedAdmin = User::factory()->superAdmin()->create(['email_verified_at' => now()]);
        $unverifiedAdmin = User::factory()->superAdmin()->create(['email_verified_at' => null]);
        StoreSettings::current()->update(['low_stock_threshold' => 5]);

        $variant = ProductVariant::factory()->create(['stock_quantity' => 20]);
        $variant->stock_quantity = 3;
        $variant->save();

        Notification::assertSentTo($verifiedAdmin, LowStockAlert::class);
        Notification::assertNotSentTo($unverifiedAdmin, LowStockAlert::class);
    }

    // -------------------------------------------------------------------------
    // Observer bypass fix tests (CreateOrderAction / ProcessReturnAction)
    // -------------------------------------------------------------------------

    public function test_notification_sent_when_order_drops_product_below_threshold(): void
    {
        Notification::fake();

        $admin = User::factory()->superAdmin()->create();
        StoreSettings::current()->update(['low_stock_threshold' => 5]);

        $customer = Customer::factory()->create();
        $product = Product::factory()->create(['stock_quantity' => 10, 'price' => 1000]);

        app(CreateOrderAction::class)->execute($this->buildOrderData($customer, [
            [
                'product_id' => $product->id,
                'variant_id' => null,
                'product_name' => $product->name,
                'product_sku' => $product->sku,
                'unit_price' => 1000,
                'quantity' => 7,
                'subtotal' => 7000,
            ],
        ]));

        $this->assertEquals(3, $product->fresh()->stock_quantity);
        Notification::assertSentTo($admin, LowStockAlert::class);
    }

    public function test_notification_sent_when_order_drops_variant_below_threshold(): void
    {
        Notification::fake();

        $admin = User::factory()->superAdmin()->create();
        StoreSettings::current()->update(['low_stock_threshold' => 5]);

        $customer = Customer::factory()->create();
        $product = Product::factory()->create(['price' => 1000]);
        $variant = ProductVariant::factory()->create(['product_id' => $product->id, 'stock_quantity' => 10, 'price' => 1000]);

        app(CreateOrderAction::class)->execute($this->buildOrderData($customer, [
            [
                'product_id' => $product->id,
                'variant_id' => $variant->id,
                'product_name' => $product->name,
                'product_sku' => $variant->sku,
                'unit_price' => 1000,
                'quantity' => 7,
                'subtotal' => 7000,
            ],
        ]));

        $this->assertEquals(3, $variant->fresh()->stock_quantity);
        Notification::assertSentTo($admin, LowStockAlert::class);
    }

    public function test_low_stock_flag_resets_when_return_restocks_variant_above_threshold(): void
    {
        Notification::fake();

        User::factory()->superAdmin()->create();
        StoreSettings::current()->update(['low_stock_threshold' => 5]);

        $variant = ProductVariant::factory()->alreadyNotified(5)->create(['stock_quantity' => 3]);
        $product = $variant->product;

        $order = Order::factory()->create(['customer_id' => Customer::factory()->create()->id]);
        $orderItem = $order->items()->create([
            'product_id' => $product->id,
            'variant_id' => $variant->id,
            'product_name' => $product->name,
            'product_sku' => $variant->sku,
            'unit_price' => 1000,
            'quantity' => 3,
            'subtotal' => 3000,
        ]);

        $orderReturn = OrderReturn::factory()->create(['order_id' => $order->id]);
        ReturnItem::factory()->create([
            'return_id' => $orderReturn->id,
            'order_item_id' => $orderItem->id,
            'quantity' => 3,
            'unit_price' => 1000,
            'subtotal' => 3000,
        ]);

        app(ProcessReturnAction::class)->restock($orderReturn);

        $this->assertEquals(6, $variant->fresh()->stock_quantity);
        $this->assertFalse($variant->fresh()->low_stock_notified);
    }

    public function test_low_stock_flag_does_not_reset_when_return_restocks_but_still_below_threshold(): void
    {
        Notification::fake();

        User::factory()->superAdmin()->create();
        StoreSettings::current()->update(['low_stock_threshold' => 5]);

        $variant = ProductVariant::factory()->alreadyNotified(5)->create(['stock_quantity' => 1]);
        $product = $variant->product;

        $order = Order::factory()->create(['customer_id' => Customer::factory()->create()->id]);
        $orderItem = $order->items()->create([
            'product_id' => $product->id,
            'variant_id' => $variant->id,
            'product_name' => $product->name,
            'product_sku' => $variant->sku,
            'unit_price' => 1000,
            'quantity' => 1,
            'subtotal' => 1000,
        ]);

        $orderReturn = OrderReturn::factory()->create(['order_id' => $order->id]);
        ReturnItem::factory()->create([
            'return_id' => $orderReturn->id,
            'order_item_id' => $orderItem->id,
            'quantity' => 1,
            'unit_price' => 1000,
            'subtotal' => 1000,
        ]);

        app(ProcessReturnAction::class)->restock($orderReturn);

        // 1 + 1 = 2, still below threshold of 5 — flag must NOT reset
        $this->assertEquals(2, $variant->fresh()->stock_quantity);
        $this->assertTrue($variant->fresh()->low_stock_notified);
    }

    // -------------------------------------------------------------------------
    // Helpers
    // -------------------------------------------------------------------------

    /**
     * @param  array<int, array<string, mixed>>  $items
     * @return array<string, mixed>
     */
    private function buildOrderData(Customer $customer, array $items): array
    {
        return [
            'customer_id' => $customer->id,
            'status' => 'pending',
            'payment_status' => 'unpaid',
            'payment_gateway' => null,
            'items' => $items,
            'coupon_code' => null,
            'shipping_method_id' => null,
            'carrier' => null,
            'service_code' => null,
            'session_id' => null,
            'shipping_name' => 'Test User',
            'shipping_address_line1' => '123 Test St',
            'shipping_address_line2' => null,
            'shipping_city' => 'Toronto',
            'shipping_state' => 'ON',
            'shipping_postcode' => 'M5V 2T6',
            'shipping_country' => 'CA',
            'notes' => null,
        ];
    }
}
