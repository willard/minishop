<?php

namespace App\Http\Controllers\Storefront;

use App\Actions\CreateOrderAction;
use App\Enums\OrderStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Storefront\StoreCheckoutRequest;
use App\Mail\OrderConfirmationMail;
use App\Models\Customer;
use App\Models\Order;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\StoreSettings;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

class CheckoutController extends Controller
{
    public function create(): Response
    {
        return Inertia::render('storefront/Checkout');
    }

    public function store(StoreCheckoutRequest $request, CreateOrderAction $createOrder): RedirectResponse
    {
        $validated = $request->validated();

        $lineItems = $this->buildLineItems($validated['items']);

        $user = User::query()->firstOrCreate(
            ['email' => $validated['email']],
            ['name' => $validated['name'], 'password' => bcrypt(Str::random(32))]
        );

        $customer = Customer::query()->firstOrCreate(['user_id' => $user->id]);

        $settings = StoreSettings::current();

        $order = $createOrder->execute([
            'customer_id' => $customer->id,
            'status' => OrderStatus::Pending->value,
            'payment_status' => 'pending',
            'payment_gateway' => $settings->active_payment_gateway,
            'items' => $lineItems,
            'coupon_code' => $validated['coupon_code'] ?? null,
            'shipping_method_id' => $validated['shipping_method_id'],
            'shipping_name' => $validated['name'],
            'shipping_address_line1' => $validated['address_line1'],
            'shipping_address_line2' => $validated['address_line2'] ?? null,
            'shipping_city' => $validated['city'],
            'shipping_state' => $validated['state'],
            'shipping_postcode' => $validated['postcode'],
            'shipping_country' => $validated['country'],
            'notes' => $validated['notes'] ?? null,
        ]);

        $gateway = $order->payment_gateway;

        if (in_array($gateway, ['stripe', 'paymongo'])) {
            return redirect()->route('storefront.checkout.payment.show', $order->order_number);
        }

        // Non-gateway orders (COD, bank transfer) are confirmed immediately — send email now.
        // Gateway orders (Stripe, PayMongo) send the confirmation after the payment webhook fires.
        Mail::to($order->customer->user->email)
            ->queue(new OrderConfirmationMail($order->load(['items', 'customer.user', 'shippingMethod', 'coupon'])));

        return redirect()->route('storefront.order.confirmation', $order);
    }

    public function confirmation(Order $order): Response
    {
        $order->load(['items', 'customer.user']);

        return Inertia::render('storefront/OrderConfirmation', [
            'order' => $order,
        ]);
    }

    /**
     * @param  array<int, array{product_id: int, variant_id: int|null, quantity: int}>  $items
     * @return array<int, array{product_id: int, variant_id: int|null, product_name: string, product_sku: string|null, unit_price: int, quantity: int, subtotal: int}>
     */
    private function buildLineItems(array $items): array
    {
        $lineItems = [];

        foreach ($items as $item) {
            $product = Product::query()->findOrFail($item['product_id']);
            abort_unless($product->is_active, 422, 'One or more products are no longer available.');

            $unitPrice = $product->price;
            $sku = $product->sku;

            if (! empty($item['variant_id'])) {
                $variant = ProductVariant::query()
                    ->where('id', $item['variant_id'])
                    ->where('product_id', $product->id)
                    ->where('is_active', true)
                    ->firstOrFail();

                $unitPrice = $variant->price ?? $product->price;
                $sku = $variant->sku ?? $product->sku;

                abort_if($variant->stock_quantity < $item['quantity'], 422, "Insufficient stock for {$product->name}.");
            } else {
                abort_if($product->stock_quantity < $item['quantity'], 422, "Insufficient stock for {$product->name}.");
            }

            $lineItems[] = [
                'product_id' => $product->id,
                'variant_id' => $item['variant_id'] ?? null,
                'product_name' => $product->name,
                'product_sku' => $sku,
                'unit_price' => $unitPrice,
                'quantity' => $item['quantity'],
                'subtotal' => $unitPrice * $item['quantity'],
            ];
        }

        return $lineItems;
    }
}
