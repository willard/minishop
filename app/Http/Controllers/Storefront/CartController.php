<?php

namespace App\Http\Controllers\Storefront;

use App\Http\Controllers\Concerns\ManagesCartItems;
use App\Http\Controllers\Controller;
use App\Http\Resources\CartResource;
use App\Models\Cart;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class CartController extends Controller
{
    use ManagesCartItems;

    public function show(Request $request): Response
    {
        $cart = Cart::resolveOrCreate($request);
        $cart->load(['items.product.images', 'items.variant']);

        return Inertia::render('storefront/Cart', [
            'cart' => new CartResource($cart),
        ]);
    }
}
