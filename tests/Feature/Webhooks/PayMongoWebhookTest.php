<?php

namespace Tests\Feature\Webhooks;

use App\Enums\OrderStatus;
use App\Models\Order;
use App\Models\ShippingMethod;
use App\Models\StoreSettings;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PayMongoWebhookTest extends TestCase
{
    use RefreshDatabase;

    private string $webhookSecret = 'test_paymongo_webhook_secret';

    private function buildSignatureHeader(string $payload): string
    {
        $timestamp = (string) time();
        $signedPayload = "{$timestamp}.{$payload}";
        $signature = hash_hmac('sha256', $signedPayload, $this->webhookSecret);

        return "t={$timestamp},te={$signature}";
    }

    private function makeOrder(string $orderNumber = 'ORD-TEST-001'): Order
    {
        $shippingMethod = ShippingMethod::factory()->create();
        $user = User::factory()->create();

        return Order::factory()->create([
            'order_number' => $orderNumber,
            'payment_status' => 'pending',
            'payment_gateway' => 'paymongo',
            'shipping_method_id' => $shippingMethod->id,
        ]);
    }

    public function test_paymongo_webhook_rejects_missing_signature(): void
    {
        StoreSettings::current()->update(['paymongo_webhook_secret' => $this->webhookSecret]);

        $this->postJson(route('webhooks.paymongo'), ['type' => 'payment.paid'])
            ->assertStatus(400);
    }

    public function test_paymongo_webhook_rejects_invalid_signature(): void
    {
        StoreSettings::current()->update(['paymongo_webhook_secret' => $this->webhookSecret]);

        $payload = json_encode(['type' => 'payment.paid']);

        $this->call('POST', route('webhooks.paymongo'), [], [], [], [
            'HTTP_PAYMONGO_SIGNATURE' => 't=123,te=invalidsig',
            'CONTENT_TYPE' => 'application/json',
        ], $payload)->assertStatus(400);
    }

    public function test_paymongo_webhook_marks_order_as_paid_on_payment_paid(): void
    {
        StoreSettings::current()->update(['paymongo_webhook_secret' => $this->webhookSecret]);

        $order = $this->makeOrder('ORD-PM-001');

        $eventPayload = json_encode([
            'data' => [
                'attributes' => [
                    'type' => 'payment.paid',
                    'data' => [
                        'attributes' => [
                            'metadata' => ['order_number' => 'ORD-PM-001'],
                        ],
                    ],
                ],
            ],
        ]);

        $sigHeader = $this->buildSignatureHeader($eventPayload);

        $this->call('POST', route('webhooks.paymongo'), [], [], [], [
            'HTTP_PAYMONGO_SIGNATURE' => $sigHeader,
            'CONTENT_TYPE' => 'application/json',
        ], $eventPayload)->assertStatus(200);

        $this->assertDatabaseHas('orders', [
            'id' => $order->id,
            'payment_status' => 'paid',
            'status' => OrderStatus::Processing->value,
        ]);
        $this->assertNotNull($order->fresh()->paid_at);
    }

    public function test_paymongo_webhook_ignores_unhandled_event_types(): void
    {
        StoreSettings::current()->update(['paymongo_webhook_secret' => $this->webhookSecret]);

        $eventPayload = json_encode([
            'data' => [
                'attributes' => [
                    'type' => 'source.chargeable',
                    'data' => ['attributes' => ['metadata' => []]],
                ],
            ],
        ]);

        $sigHeader = $this->buildSignatureHeader($eventPayload);

        $this->call('POST', route('webhooks.paymongo'), [], [], [], [
            'HTTP_PAYMONGO_SIGNATURE' => $sigHeader,
            'CONTENT_TYPE' => 'application/json',
        ], $eventPayload)->assertStatus(200);
    }

    public function test_paymongo_webhook_does_not_process_if_no_secret_configured(): void
    {
        // No webhook secret set — should reject
        $this->call('POST', route('webhooks.paymongo'), [], [], [], [
            'HTTP_PAYMONGO_SIGNATURE' => 't=123,te=anysig',
            'CONTENT_TYPE' => 'application/json',
        ], '{}')->assertStatus(400);
    }
}
