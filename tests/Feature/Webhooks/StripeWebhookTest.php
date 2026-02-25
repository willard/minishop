<?php

namespace Tests\Feature\Webhooks;

use App\Enums\OrderStatus;
use App\Models\Order;
use App\Models\ShippingMethod;
use App\Models\StoreSettings;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StripeWebhookTest extends TestCase
{
    use RefreshDatabase;

    private string $webhookSecret = 'whsec_test_stripe_secret';

    private function buildSignatureHeader(string $payload): string
    {
        $timestamp = time();
        $signedPayload = "{$timestamp}.{$payload}";
        $signature = hash_hmac('sha256', $signedPayload, $this->webhookSecret);

        return "t={$timestamp},v1={$signature}";
    }

    private function makeOrder(): Order
    {
        $shippingMethod = ShippingMethod::factory()->create();
        $user = User::factory()->create();

        return Order::factory()->create([
            'payment_intent_id' => 'pi_test_123',
            'payment_status' => 'pending',
            'payment_gateway' => 'stripe',
            'shipping_method_id' => $shippingMethod->id,
        ]);
    }

    public function test_stripe_webhook_requires_valid_signature(): void
    {
        StoreSettings::current()->update(['stripe_webhook_secret' => $this->webhookSecret]);

        $payload = json_encode(['type' => 'payment_intent.succeeded', 'data' => ['object' => ['id' => 'pi_test_123']]]);

        $this->postJson(route('webhooks.stripe'), json_decode($payload, true), [
            'Stripe-Signature' => 'invalid_signature',
        ])->assertStatus(400);
    }

    public function test_stripe_webhook_rejects_missing_signature(): void
    {
        StoreSettings::current()->update(['stripe_webhook_secret' => $this->webhookSecret]);

        $this->postJson(route('webhooks.stripe'), [])
            ->assertStatus(400);
    }

    public function test_stripe_webhook_marks_order_as_paid_on_payment_intent_succeeded(): void
    {
        $settings = StoreSettings::current();
        $settings->update(['stripe_webhook_secret' => $this->webhookSecret]);

        $order = $this->makeOrder();

        $eventPayload = json_encode([
            'type' => 'payment_intent.succeeded',
            'data' => [
                'object' => [
                    'id' => 'pi_test_123',
                    'object' => 'payment_intent',
                    'status' => 'succeeded',
                    'amount' => 10000,
                    'currency' => 'php',
                ],
            ],
        ]);

        $sigHeader = $this->buildSignatureHeader($eventPayload);

        $this->call('POST', route('webhooks.stripe'), [], [], [], [
            'HTTP_STRIPE_SIGNATURE' => $sigHeader,
            'CONTENT_TYPE' => 'application/json',
        ], $eventPayload)->assertStatus(200);

        $this->assertDatabaseHas('orders', [
            'id' => $order->id,
            'payment_status' => 'paid',
            'status' => OrderStatus::Processing->value,
        ]);
        $this->assertNotNull($order->fresh()->paid_at);
    }

    public function test_stripe_webhook_does_not_double_process_paid_order(): void
    {
        $settings = StoreSettings::current();
        $settings->update(['stripe_webhook_secret' => $this->webhookSecret]);

        $shippingMethod = ShippingMethod::factory()->create();
        $order = Order::factory()->create([
            'payment_intent_id' => 'pi_test_456',
            'payment_status' => 'paid',
            'status' => OrderStatus::Processing,
            'shipping_method_id' => $shippingMethod->id,
        ]);

        $eventPayload = json_encode([
            'type' => 'payment_intent.succeeded',
            'data' => ['object' => ['id' => 'pi_test_456', 'object' => 'payment_intent', 'status' => 'succeeded', 'amount' => 5000, 'currency' => 'php']],
        ]);

        $sigHeader = $this->buildSignatureHeader($eventPayload);

        $this->call('POST', route('webhooks.stripe'), [], [], [], [
            'HTTP_STRIPE_SIGNATURE' => $sigHeader,
            'CONTENT_TYPE' => 'application/json',
        ], $eventPayload)->assertStatus(200);

        // Status should remain unchanged
        $this->assertDatabaseHas('orders', [
            'id' => $order->id,
            'payment_status' => 'paid',
        ]);
    }

    public function test_stripe_webhook_ignores_unhandled_event_types(): void
    {
        $settings = StoreSettings::current();
        $settings->update(['stripe_webhook_secret' => $this->webhookSecret]);

        $eventPayload = json_encode([
            'type' => 'customer.created',
            'data' => ['object' => ['id' => 'cus_test']],
        ]);

        $sigHeader = $this->buildSignatureHeader($eventPayload);

        $this->call('POST', route('webhooks.stripe'), [], [], [], [
            'HTTP_STRIPE_SIGNATURE' => $sigHeader,
            'CONTENT_TYPE' => 'application/json',
        ], $eventPayload)->assertStatus(200);
    }
}
