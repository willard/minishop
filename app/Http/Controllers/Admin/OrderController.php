<?php

namespace App\Http\Controllers\Admin;

use App\Enums\OrderStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\UpdateOrderRequest;
use App\Mail\OrderStatusChangedMail;
use App\Models\Order;
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
