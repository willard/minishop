<?php

namespace App\Http\Controllers\Webhooks;

use App\Enums\OrderStatus;
use App\Http\Controllers\Controller;
use App\Mail\OrderConfirmationMail;
use App\Models\Order;
use App\Models\StoreSettings;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Mail;
use Stripe\Exception\SignatureVerificationException;
use Stripe\Webhook;

class StripeWebhookController extends Controller
{
    public function __invoke(Request $request): Response
    {
        $settings = StoreSettings::current();
        $payload = $request->getContent();
        $sigHeader = $request->header('Stripe-Signature');

        try {
            $event = Webhook::constructEvent($payload, $sigHeader, $settings->stripe_webhook_secret);
        } catch (SignatureVerificationException) {
            return response('Invalid signature', 400);
        }

        if ($event->type === 'payment_intent.succeeded') {
            $paymentIntent = $event->data->object;

            $order = Order::query()
                ->where('payment_intent_id', $paymentIntent->id)
                ->first();

            if ($order && $order->payment_status !== 'paid') {
                $order->update([
                    'payment_status' => 'paid',
                    'status' => OrderStatus::Processing,
                    'paid_at' => now(),
                ]);

                Mail::to($order->customer->user->email)
                    ->queue(new OrderConfirmationMail($order->load(['items', 'customer.user', 'shippingMethod', 'coupon'])));
            }
        }

        return response('OK', 200);
    }
}
