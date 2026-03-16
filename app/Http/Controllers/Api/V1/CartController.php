<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Concerns\ManagesCartItems;
use App\Http\Controllers\Controller;
use App\Http\Resources\CartResource;
use App\Models\Cart;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CartController extends Controller
{
    use ManagesCartItems;

    public function show(Request $request): JsonResponse
    {
        $cart = Cart::resolveOrCreate($request);
        $cart->load(['items.product.images', 'items.variant']);

        return response()->json(new CartResource($cart));
    }
}
