<?php

namespace App\Http\Controllers\Admin;

use App\Enums\OrderStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\BulkOrderActionRequest;
use App\Mail\OrderStatusChangedMail;
use App\Models\Order;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

class OrderBulkActionController extends Controller
{
    public function __invoke(BulkOrderActionRequest $request): RedirectResponse
    {
        $data = $request->validated();

        $orders = Order::query()
            ->with(['customer.user', 'items', 'shippingMethod'])
            ->whereIn('id', $data['order_ids'])
            ->get();

        $count = $orders->count();

        if ($data['action'] === 'delete') {
            $orders->each->delete();

            return redirect()->route('admin.orders.index')
                ->with('success', "{$count} ".Str::plural('order', $count).' deleted successfully.');
        }

        $targetStatus = $data['status'];
        $transitions = OrderStatus::transitions();
        $notifiable = [OrderStatus::Shipped->value, OrderStatus::Delivered->value, OrderStatus::Cancelled->value];
        $shouldNotify = in_array($targetStatus, $notifiable);

        $updatable = $orders->filter(
            fn (Order $order) => in_array($targetStatus, $transitions[$order->status->value] ?? [])
        );

        $updatable->each(function (Order $order) use ($targetStatus, $shouldNotify): void {
            $order->update(['status' => $targetStatus]);

            if ($shouldNotify) {
                Mail::to($order->customer->user->email)
                    ->queue(new OrderStatusChangedMail($order));
            }
        });

        $updatedCount = $updatable->count();
        $skippedCount = $count - $updatedCount;
        $updatedNoun = Str::plural('order', $updatedCount);

        $message = $skippedCount > 0
            ? "{$updatedCount} {$updatedNoun} updated to \"{$targetStatus}\". {$skippedCount} skipped (invalid transition)."
            : "{$updatedCount} {$updatedNoun} updated to \"{$targetStatus}\".";

        return redirect()->route('admin.orders.index')->with('success', $message);
    }
}
