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

            foreach ($orderReturn->items as $returnItem) {
                $orderItem = $returnItem->orderItem;

                if ($orderItem->variant_id) {
                    ProductVariant::query()
                        ->where('id', $orderItem->variant_id)
                        ->increment('stock_quantity', $returnItem->quantity);
                } else {
                    Product::query()
                        ->where('id', $orderItem->product_id)
                        ->increment('stock_quantity', $returnItem->quantity);
                }
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
