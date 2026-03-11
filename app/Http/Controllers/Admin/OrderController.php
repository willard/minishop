<?php

namespace App\Http\Controllers\Admin;

use App\Actions\CreateOrderAction;
use App\Enums\OrderStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreOrderRequest;
use App\Http\Requests\Admin\UpdateOrderRequest;
use App\Mail\OrderStatusChangedMail;
use App\Models\Customer;
use App\Models\Order;
use App\Models\Product;
use App\Models\ProductVariant;
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
    public function index(Request $request): Response
    {
        $this->authorize('viewAny', Order::class);

        $filters = $request->only(['status', 'search']);

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
            ->orderByDesc('created_at')
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
            ->with(['variants' => fn ($q) => $q->where('is_active', true)->with('optionValues')])
            ->orderBy('name')
            ->get(['id', 'name', 'sku', 'price', 'stock_quantity'])
            ->map(fn (Product $p) => [
                'id' => $p->id,
                'name' => $p->name,
                'sku' => $p->sku,
                'price' => $p->price,
                'stock_quantity' => $p->stock_quantity,
                'variants' => $p->variants->map(fn (ProductVariant $v) => [
                    'id' => $v->id,
                    'sku' => $v->sku,
                    'price' => $v->price,
                    'stock_quantity' => $v->stock_quantity,
                    'label' => $v->optionValues->pluck('value')->join(' / ') ?: ($v->sku ?? "Variant #{$v->id}"),
                ]),
            ]);

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

    public function store(StoreOrderRequest $request, CreateOrderAction $createOrder): RedirectResponse
    {
        $this->authorize('create', Order::class);

        $data = $request->validated();

        $productIds = array_column($data['items'], 'product_id');
        $products = Product::query()->whereIn('id', $productIds)->get()->keyBy('id');

        $variantIds = array_filter(array_column($data['items'], 'variant_id'));
        $variants = ! empty($variantIds)
            ? ProductVariant::query()->whereIn('id', $variantIds)->get()->keyBy('id')
            : collect();

        $lineItems = array_map(function (array $item) use ($products, $variants): array {
            $product = $products->get($item['product_id']);
            $variant = isset($item['variant_id']) ? $variants->get($item['variant_id']) : null;

            return [
                'product_id' => $item['product_id'],
                'variant_id' => $item['variant_id'] ?? null,
                'product_name' => $product->name,
                'product_sku' => $variant?->sku ?? $product->sku,
                'unit_price' => $item['unit_price'],
                'quantity' => $item['quantity'],
                'subtotal' => $item['unit_price'] * $item['quantity'],
            ];
        }, $data['items']);

        $order = $createOrder->execute([
            'customer_id' => $data['customer_id'],
            'status' => $data['status'],
            'payment_status' => 'pending',
            'payment_gateway' => null,
            'items' => $lineItems,
            'coupon_code' => $data['coupon_code'] ?? null,
            'shipping_method_id' => $data['shipping_method_id'] ?? null,
            'shipping_name' => $data['shipping_name'],
            'shipping_address_line1' => $data['shipping_address_line1'],
            'shipping_address_line2' => $data['shipping_address_line2'] ?? null,
            'shipping_city' => $data['shipping_city'],
            'shipping_state' => $data['shipping_state'],
            'shipping_postcode' => $data['shipping_postcode'],
            'shipping_country' => $data['shipping_country'],
            'notes' => $data['notes'] ?? null,
        ]);

        return redirect()->route('admin.orders.show', $order->refresh())
            ->with('success', 'Order created successfully.');
    }

    public function show(Order $order): Response
    {
        $this->authorize('view', $order);

        $order->load(['customer.user', 'items.product', 'items.variant']);

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
