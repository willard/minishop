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
    /**
     * @return array<string, string[]>
     */
    private static function allowedTransitions(): array
    {
        return [
            OrderStatus::Pending->value => [OrderStatus::Processing->value, OrderStatus::Cancelled->value],
            OrderStatus::Processing->value => [OrderStatus::Shipped->value, OrderStatus::Cancelled->value],
            OrderStatus::Shipped->value => [OrderStatus::Delivered->value, OrderStatus::Cancelled->value],
            OrderStatus::Delivered->value => [OrderStatus::Refunded->value],
            OrderStatus::Cancelled->value => [],
            OrderStatus::Refunded->value => [],
        ];
    }

    public function __invoke(BulkOrderActionRequest $request): RedirectResponse
    {
        $data = $request->validated();

        $orders = Order::query()->whereIn('id', $data['order_ids'])->get();
        $count = $orders->count();
        $noun = Str::plural('order', $count);

        if ($data['action'] === 'delete') {
            $orders->each->delete();

            return redirect()->route('admin.orders.index')
                ->with('success', "{$count} {$noun} deleted successfully.");
        }

        $targetStatus = $data['status'];
        $transitions = self::allowedTransitions();
        $notifiable = [OrderStatus::Shipped->value, OrderStatus::Delivered->value, OrderStatus::Cancelled->value];

        $updatedCount = 0;

        foreach ($orders as $order) {
            $allowed = $transitions[$order->status->value] ?? [];

            if (! in_array($targetStatus, $allowed)) {
                continue;
            }

            $order->update(['status' => $targetStatus]);

            if (in_array($targetStatus, $notifiable)) {
                Mail::to($order->customer->user->email)
                    ->queue(new OrderStatusChangedMail($order->load(['items', 'customer.user', 'shippingMethod'])));
            }

            $updatedCount++;
        }

        $skippedCount = $count - $updatedCount;
        $updatedNoun = Str::plural('order', $updatedCount);

        $message = $skippedCount > 0
            ? "{$updatedCount} {$updatedNoun} updated to \"{$targetStatus}\". {$skippedCount} skipped (invalid transition)."
            : "{$updatedCount} {$updatedNoun} updated to \"{$targetStatus}\".";

        return redirect()->route('admin.orders.index')->with('success', $message);
    }
}
