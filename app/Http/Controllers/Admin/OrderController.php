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
use Illuminate\Http\Response as HttpResponse;
use Illuminate\Support\Facades\Mail;
use Inertia\Inertia;
use Inertia\Response;

class OrderController extends Controller
{
    public function index(): Response
    {
        $orders = Order::query()
            ->with('customer.user')
            ->orderByDesc('created_at')
            ->paginate(20);

        return Inertia::render('admin/Orders/Index', [
            'orders' => $orders,
        ]);
    }

    public function show(Order $order): Response
    {
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
        $order->load(['customer.user', 'items', 'shippingMethod', 'coupon']);
        $settings = StoreSettings::current();

        $pdf = Pdf::loadView('pdf.invoice', compact('order', 'settings'))
            ->setPaper('a4', 'portrait');

        return $pdf->download('invoice-'.$order->order_number.'.pdf');
    }

    public function destroy(Order $order): RedirectResponse
    {
        $order->delete();

        return redirect()->route('admin.orders.index')
            ->with('success', 'Order deleted successfully.');
    }
}
