<?php

namespace App\Http\Controllers\Admin;

use App\Enums\OrderStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreOrderRequest;
use App\Http\Requests\Admin\UpdateOrderRequest;
use App\Mail\OrderStatusChangedMail;
use App\Models\Coupon;
use App\Models\Customer;
use App\Models\Order;
use App\Models\Product;
use App\Models\ShippingMethod;
use App\Models\StoreSettings;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response as HttpResponse;
use Illuminate\Support\Facades\Mail;
use Inertia\Inertia;
use Inertia\Response;

class OrderController extends Controller
{
    private const ALLOWED_SORTS = ['order_number', 'total_amount', 'status', 'created_at'];

    public function index(Request $request): Response
    {
        $this->authorize('viewAny', Order::class);

        $filters = $request->only(['status', 'search', 'sort_by', 'sort_dir']);

        $sortBy = in_array($filters['sort_by'] ?? null, self::ALLOWED_SORTS)
            ? $filters['sort_by']
            : 'created_at';
        $sortDir = ($filters['sort_dir'] ?? 'desc') === 'asc' ? 'asc' : 'desc';

        $orders = Order::query()
            ->with('customer.user')
            ->when(
                $filters['status'] ?? null,
                fn ($query, $status) => $query->where('status', $status)
            )
            ->when(
                $filters['search'] ?? null,
                function ($query, $search): void {
                    $query->where(function ($q) use ($search): void {
                        $q->where('order_number', 'like', "%{$search}%")
                            ->orWhereHas('customer.user', function ($q) use ($search): void {
                                $q->where('name', 'like', "%{$search}%");
                            });
                    });
                }
            )
            ->orderBy($sortBy, $sortDir)
            ->paginate(20)
            ->withQueryString();

        return Inertia::render('admin/Orders/Index', [
            'orders' => $orders,
            'filters' => $filters,
            'statuses' => array_map(
                fn (OrderStatus $s) => ['value' => $s->value, 'label' => $s->label()],
                OrderStatus::cases()
            ),
        ]);
    }

    public function create(): Response
    {
        $this->authorize('create', Order::class);

        $customers = Customer::query()
            ->with('user')
            ->whereHas('user')
            ->orderBy('id')
            ->get()
            ->map(fn (Customer $c) => [
                'id' => $c->id,
                'name' => $c->user->name,
                'email' => $c->user->email,
            ]);

        $products = Product::query()
            ->where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name', 'sku', 'price', 'stock_quantity']);

        $shippingMethods = ShippingMethod::query()
            ->active()
            ->orderBy('sort_order')
            ->get(['id', 'name', 'price', 'is_free']);

        $settings = StoreSettings::current();

        return Inertia::render('admin/Orders/Create', [
            'customers' => $customers,
            'products' => $products,
            'shippingMethods' => $shippingMethods,
            'statuses' => array_map(
                fn (OrderStatus $s) => ['value' => $s->value, 'label' => $s->label()],
                OrderStatus::cases()
            ),
            'taxRate' => (float) ($settings->tax_rate ?? 0),
        ]);
    }

    public function store(StoreOrderRequest $request): RedirectResponse
    {
        $this->authorize('create', Order::class);

        $data = $request->validated();

        $subtotal = array_sum(array_map(
            fn (array $item) => $item['unit_price'] * $item['quantity'],
            $data['items']
        ));

        $shippingMethod = isset($data['shipping_method_id'])
            ? ShippingMethod::find($data['shipping_method_id'])
            : null;
        $shippingAmount = $shippingMethod ? $shippingMethod->effective_price : 0;

        $coupon = null;
        $discountAmount = 0;
        if (! empty($data['coupon_code'])) {
            $coupon = Coupon::where('code', $data['coupon_code'])->first();
            if ($coupon && $coupon->isValid($subtotal)) {
                $discountAmount = $coupon->calculateDiscount($subtotal);
            }
        }

        $settings = StoreSettings::current();
        $taxableAmount = max(0, $subtotal - $discountAmount + $shippingAmount);
        $taxAmount = (int) round($taxableAmount * (float) ($settings->tax_rate ?? 0) / 100);
        $totalAmount = max(0, $subtotal - $discountAmount + $shippingAmount + $taxAmount);

        $order = Order::create([
            'order_number' => '',
            'customer_id' => $data['customer_id'],
            'coupon_id' => $coupon?->id,
            'shipping_method_id' => $data['shipping_method_id'] ?? null,
            'status' => $data['status'],
            'subtotal' => $subtotal,
            'discount_amount' => $discountAmount,
            'shipping_amount' => $shippingAmount,
            'tax_amount' => $taxAmount,
            'total_amount' => $totalAmount,
            'shipping_name' => $data['shipping_name'],
            'shipping_address_line1' => $data['shipping_address_line1'],
            'shipping_address_line2' => $data['shipping_address_line2'] ?? null,
            'shipping_city' => $data['shipping_city'],
            'shipping_state' => $data['shipping_state'],
            'shipping_postcode' => $data['shipping_postcode'],
            'shipping_country' => $data['shipping_country'],
            'notes' => $data['notes'] ?? null,
            'payment_status' => 'pending',
        ]);

        foreach ($data['items'] as $item) {
            $product = Product::find($item['product_id']);
            $order->items()->create([
                'product_id' => $item['product_id'],
                'product_name' => $product->name,
                'product_sku' => $product->sku,
                'unit_price' => $item['unit_price'],
                'quantity' => $item['quantity'],
                'subtotal' => $item['unit_price'] * $item['quantity'],
            ]);
        }

        return redirect()->route('admin.orders.show', $order)
            ->with('success', 'Order created successfully.');
    }

    public function show(Order $order): Response
    {
        $this->authorize('view', $order);

        $order->load(['customer.user', 'items.product']);

        return Inertia::render('admin/Orders/Show', [
            'order' => $order,
            'statuses' => array_map(
                fn (OrderStatus $s) => ['value' => $s->value, 'label' => $s->label()],
                OrderStatus::cases()
            ),
        ]);
    }

    public function update(UpdateOrderRequest $request, Order $order): RedirectResponse
    {
        $this->authorize('update', $order);

        $order->update($request->validated());

        if ($order->wasChanged('status')) {
            $notifiable = [OrderStatus::Shipped, OrderStatus::Delivered, OrderStatus::Cancelled];

            if (in_array($order->status, $notifiable)) {
                Mail::to($order->customer->user->email)
                    ->queue(new OrderStatusChangedMail($order->load(['items', 'customer.user', 'shippingMethod'])));
            }
        }

        return redirect()->route('admin.orders.show', $order)
            ->with('success', 'Order updated successfully.');
    }

    public function invoice(Order $order): HttpResponse
    {
        $this->authorize('invoice', $order);

        $order->load(['customer.user', 'items', 'shippingMethod', 'coupon']);
        $settings = StoreSettings::current();

        $pdf = Pdf::loadView('pdf.invoice', compact('order', 'settings'))
            ->setPaper('a4', 'portrait');

        return $pdf->download('invoice-'.$order->order_number.'.pdf');
    }

    public function destroy(Order $order): RedirectResponse
    {
        $this->authorize('delete', $order);

        $order->delete();

        return redirect()->route('admin.orders.index')
            ->with('success', 'Order deleted successfully.');
    }
}
