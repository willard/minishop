<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\OrderResource;
use App\Models\Order;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\ResourceCollection;

class OrderController extends Controller
{
    public function index(Request $request): ResourceCollection
    {
        $customer = $request->user()->customer;

        if (! $customer) {
            return OrderResource::collection(collect());
        }

        $orders = Order::query()
            ->where('customer_id', $customer->id)
            ->with(['items', 'shippingMethod'])
            ->orderByDesc('created_at')
            ->paginate(15);

        return OrderResource::collection($orders);
    }

    public function show(Request $request, Order $order): JsonResponse
    {
        $customer = $request->user()->customer;

        abort_unless($customer && $order->customer_id === $customer->id, 403);

        $order->load(['items.product', 'items.variant', 'shippingMethod']);

        return response()->json(new OrderResource($order));
    }
}
