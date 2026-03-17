<?php

namespace App\Actions;

use App\Enums\OrderStatus;
use App\Enums\ReturnStatus;
use App\Models\Order;
use App\Models\OrderReturn;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Services\StripeRefundService;
use Illuminate\Support\Facades\DB;

class ProcessReturnAction
{
    public function __construct(private readonly StripeRefundService $stripeRefundService) {}

    /**
     * Restock inventory for all items in the return and mark the return as received.
     */
    public function restock(OrderReturn $orderReturn): void
    {
        DB::transaction(function () use ($orderReturn): void {
            $orderReturn->load('items.orderItem');

            $variantQtys = [];
            $productQtys = [];

            foreach ($orderReturn->items as $returnItem) {
                $orderItem = $returnItem->orderItem;

                if ($orderItem->variant_id) {
                    $variantQtys[$orderItem->variant_id] = ($variantQtys[$orderItem->variant_id] ?? 0) + $returnItem->quantity;
                } else {
                    $productQtys[$orderItem->product_id] = ($productQtys[$orderItem->product_id] ?? 0) + $returnItem->quantity;
                }
            }

            foreach ($variantQtys as $variantId => $qty) {
                ProductVariant::query()->where('id', $variantId)->increment('stock_quantity', $qty);
            }

            foreach ($productQtys as $productId => $qty) {
                Product::query()->where('id', $productId)->increment('stock_quantity', $qty);
            }

            $orderReturn->update([
                'status' => ReturnStatus::Received,
                'restocked' => true,
            ]);
        });
    }

    /**
     * Issue the Stripe refund, update return status, and mark order as Refunded if fully refunded.
     */
    public function issueRefund(OrderReturn $orderReturn): void
    {
        DB::transaction(function () use ($orderReturn): void {
            $orderReturn->loadMissing('order', 'items');

            $order = $orderReturn->order;
            $refundAmount = $orderReturn->items->sum('subtotal');

            $stripeRefundId = $this->stripeRefundService->refund($order, $refundAmount);

            $orderReturn->update([
                'status' => ReturnStatus::Refunded,
                'refund_amount' => $refundAmount,
                'stripe_refund_id' => $stripeRefundId,
                'refunded_at' => now(),
            ]);

            // Mark order as Refunded when the full order amount has been refunded
            $totalRefunded = $order->returns()
                ->where('status', ReturnStatus::Refunded->value)
                ->sum('refund_amount');

            if ($totalRefunded >= $order->total_amount) {
                $order->update(['status' => OrderStatus::Refunded]);
            }
        });
    }
}
