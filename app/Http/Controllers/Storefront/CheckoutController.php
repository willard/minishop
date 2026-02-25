<?php

namespace App\Http\Controllers\Storefront;

use App\Enums\OrderStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Storefront\StoreCheckoutRequest;
use App\Mail\OrderConfirmationMail;
use App\Models\Coupon;
use App\Models\Customer;
use App\Models\Order;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\ShippingMethod;
use App\Models\StoreSettings;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
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

    public function store(StoreCheckoutRequest $request): RedirectResponse
    {
        $validated = $request->validated();

        $order = DB::transaction(function () use ($validated) {
            $lineItems = $this->buildLineItems($validated['items']);

            $subtotal = collect($lineItems)->sum('subtotal');

            $coupon = null;
            $discountAmount = 0;

            if (! empty($validated['coupon_code'])) {
                $coupon = Coupon::query()
                    ->whereRaw('UPPER(code) = ?', [strtoupper($validated['coupon_code'])])
                    ->first();

                if ($coupon && $coupon->isValid($subtotal)) {
                    $discountAmount = $coupon->calculateDiscount($subtotal);
                }
            }

            $shippingMethod = ShippingMethod::query()->findOrFail($validated['shipping_method_id']);
            $shippingAmount = $shippingMethod->is_free ? 0 : $shippingMethod->price;

            $settings = StoreSettings::current();
            $taxableAmount = $subtotal - $discountAmount;
            $taxAmount = (int) round($taxableAmount * ($settings->tax_rate / 100));
            $totalAmount = $taxableAmount + $shippingAmount + $taxAmount;

            $user = User::query()->firstOrCreate(
                ['email' => $validated['email']],
                ['name' => $validated['name'], 'password' => bcrypt(Str::random(32))]
            );

            $customer = Customer::query()->firstOrCreate(['user_id' => $user->id]);

            $order = Order::query()->create([
                'order_number' => '',
                'customer_id' => $customer->id,
                'coupon_id' => $coupon?->id,
                'shipping_method_id' => $shippingMethod->id,
                'status' => OrderStatus::Pending,
                'payment_gateway' => $settings->active_payment_gateway,
                'payment_status' => 'pending',
                'subtotal' => $subtotal,
                'discount_amount' => $discountAmount,
                'shipping_amount' => $shippingAmount,
                'tax_amount' => $taxAmount,
                'total_amount' => $totalAmount,
                'shipping_name' => $validated['name'],
                'shipping_address_line1' => $validated['address_line1'],
                'shipping_address_line2' => $validated['address_line2'] ?? null,
                'shipping_city' => $validated['city'],
                'shipping_state' => $validated['state'],
                'shipping_postcode' => $validated['postcode'],
                'shipping_country' => $validated['country'],
                'notes' => $validated['notes'] ?? null,
            ]);

            foreach ($lineItems as $item) {
                $order->items()->create([
                    'product_id' => $item['product_id'],
                    'product_name' => $item['product_name'],
                    'product_sku' => $item['product_sku'],
                    'unit_price' => $item['unit_price'],
                    'quantity' => $item['quantity'],
                    'subtotal' => $item['subtotal'],
                ]);

                if ($item['variant_id']) {
                    ProductVariant::query()
                        ->where('id', $item['variant_id'])
                        ->decrement('stock_quantity', $item['quantity']);
                } else {
                    Product::query()
                        ->where('id', $item['product_id'])
                        ->decrement('stock_quantity', $item['quantity']);
                }
            }

            if ($coupon) {
                $coupon->increment('used_count');
            }

            return $order;
        });

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
