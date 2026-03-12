<?php

namespace Tests\Feature\Storefront;

use App\Models\Coupon;
use App\Models\Product;
use App\Models\ShippingMethod;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CheckoutTest extends TestCase
{
    use RefreshDatabase;

    private ?ShippingMethod $shippingMethod = null;

    protected function setUp(): void
    {
        parent::setUp();
        $this->shippingMethod = ShippingMethod::factory()->create(['price' => 20000, 'is_free' => false]);
    }

    /**
     * @param  array<string, mixed>  $overrides
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
            'country' => 'CA',
            'shipping_method_id' => $this->shippingMethod->id,
            'items' => [],
        ], $overrides);
    }

    public function test_checkout_page_renders(): void
    {
        $this->get(route('storefront.checkout.create'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page->component('storefront/Checkout'));
    }

    public function test_order_is_created_with_valid_data(): void
    {
        $product = Product::factory()->create(['price' => 5000, 'stock_quantity' => 10]);

        $this->post(route('storefront.checkout.store'), $this->validPayload([
            'items' => [['product_id' => $product->id, 'variant_id' => null, 'quantity' => 2]],
        ]))->assertRedirect();

        $this->assertDatabaseHas('orders', [
            'shipping_name' => 'Maria Santos',
            'shipping_method_id' => $this->shippingMethod->id,
        ]);
        $this->assertDatabaseHas('order_items', ['product_id' => $product->id, 'quantity' => 2]);
    }

    public function test_order_stores_shipping_amount_from_selected_method(): void
    {
        $product = Product::factory()->create(['price' => 5000, 'stock_quantity' => 5]);

        $this->post(route('storefront.checkout.store'), $this->validPayload([
            'items' => [['product_id' => $product->id, 'variant_id' => null, 'quantity' => 1]],
        ]));

        $this->assertDatabaseHas('orders', ['shipping_amount' => 20000]);
    }

    public function test_free_shipping_sets_shipping_amount_to_zero(): void
    {
        $freeMethod = ShippingMethod::factory()->free()->create();
        $product = Product::factory()->create(['price' => 5000, 'stock_quantity' => 5]);

        $this->post(route('storefront.checkout.store'), $this->validPayload([
            'items' => [['product_id' => $product->id, 'variant_id' => null, 'quantity' => 1]],
            'shipping_method_id' => $freeMethod->id,
        ]));

        $this->assertDatabaseHas('orders', ['shipping_amount' => 0]);
    }

    public function test_order_fails_when_shipping_method_missing(): void
    {
        $product = Product::factory()->create(['price' => 5000, 'stock_quantity' => 5]);

        $this->post(route('storefront.checkout.store'), $this->validPayload([
            'items' => [['product_id' => $product->id, 'variant_id' => null, 'quantity' => 1]],
            'shipping_method_id' => null,
        ]))->assertSessionHasErrors('shipping_method_id');
    }

    public function test_order_fails_when_shipping_method_does_not_exist(): void
    {
        $product = Product::factory()->create(['price' => 5000, 'stock_quantity' => 5]);

        $this->post(route('storefront.checkout.store'), $this->validPayload([
            'items' => [['product_id' => $product->id, 'variant_id' => null, 'quantity' => 1]],
            'shipping_method_id' => 99999,
        ]))->assertSessionHasErrors('shipping_method_id');
    }

    public function test_stock_is_decremented_after_order(): void
    {
        $product = Product::factory()->create(['price' => 5000, 'stock_quantity' => 10]);

        $this->post(route('storefront.checkout.store'), $this->validPayload([
            'items' => [['product_id' => $product->id, 'variant_id' => null, 'quantity' => 3]],
        ]));

        $this->assertDatabaseHas('products', ['id' => $product->id, 'stock_quantity' => 7]);
    }

    public function test_order_fails_validation_when_items_empty(): void
    {
        $this->post(route('storefront.checkout.store'), $this->validPayload(['items' => []]))
            ->assertSessionHasErrors('items');
    }

    public function test_order_fails_when_required_fields_missing(): void
    {
        $this->post(route('storefront.checkout.store'), [])
            ->assertSessionHasErrors(['name', 'email', 'address_line1', 'city', 'state', 'postcode', 'country', 'items']);
    }

    public function test_order_fails_when_insufficient_stock(): void
    {
        $product = Product::factory()->create(['price' => 5000, 'stock_quantity' => 1]);

        $this->post(route('storefront.checkout.store'), $this->validPayload([
            'items' => [['product_id' => $product->id, 'variant_id' => null, 'quantity' => 5]],
        ]))->assertStatus(422);
    }

    public function test_coupon_discount_is_applied(): void
    {
        $product = Product::factory()->create(['price' => 10000, 'stock_quantity' => 5]);
        Coupon::factory()->percentage()->create(['code' => 'SAVE10', 'value' => 10]);

        $this->post(route('storefront.checkout.store'), $this->validPayload([
            'items' => [['product_id' => $product->id, 'variant_id' => null, 'quantity' => 1]],
            'coupon_code' => 'SAVE10',
        ]));

        $this->assertDatabaseHas('orders', ['discount_amount' => 1000]);
    }

    public function test_coupon_used_count_is_incremented(): void
    {
        $product = Product::factory()->create(['price' => 5000, 'stock_quantity' => 5]);
        $coupon = Coupon::factory()->percentage()->create(['code' => 'DISC20', 'value' => 20]);

        $this->post(route('storefront.checkout.store'), $this->validPayload([
            'items' => [['product_id' => $product->id, 'variant_id' => null, 'quantity' => 1]],
            'coupon_code' => 'DISC20',
        ]));

        $this->assertDatabaseHas('coupons', ['id' => $coupon->id, 'used_count' => 1]);
    }

    public function test_guest_user_and_customer_are_created_for_new_email(): void
    {
        $product = Product::factory()->create(['price' => 5000, 'stock_quantity' => 5]);

        $this->post(route('storefront.checkout.store'), $this->validPayload([
            'items' => [['product_id' => $product->id, 'variant_id' => null, 'quantity' => 1]],
        ]));

        $this->assertDatabaseHas('users', ['email' => 'maria@example.com']);
        $user = User::query()->where('email', 'maria@example.com')->first();
        $this->assertDatabaseHas('customers', ['user_id' => $user->id]);
    }

    public function test_second_order_reuses_existing_user_and_customer(): void
    {
        $product = Product::factory()->create(['price' => 5000, 'stock_quantity' => 20]);
        $payload = $this->validPayload([
            'items' => [['product_id' => $product->id, 'variant_id' => null, 'quantity' => 1]],
        ]);

        $this->post(route('storefront.checkout.store'), $payload);
        $this->post(route('storefront.checkout.store'), $payload);

        $this->assertDatabaseCount('users', 1);
        $this->assertDatabaseCount('customers', 1);
        $this->assertDatabaseCount('orders', 2);
    }

    public function test_confirmation_page_renders_with_order(): void
    {
        $product = Product::factory()->create(['price' => 5000, 'stock_quantity' => 5]);

        $response = $this->post(route('storefront.checkout.store'), $this->validPayload([
            'items' => [['product_id' => $product->id, 'variant_id' => null, 'quantity' => 1]],
        ]));

        $confirmationUrl = $response->headers->get('Location');

        $this->get($confirmationUrl)
            ->assertOk()
            ->assertInertia(fn ($page) => $page->component('storefront/OrderConfirmation'));
    }
}
