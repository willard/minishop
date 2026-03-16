<?php

namespace App\Http\Controllers\Storefront;

use App\Http\Controllers\Controller;
use App\Http\Requests\Storefront\AddCartItemRequest;
use App\Http\Requests\Storefront\UpdateCartItemRequest;
use App\Http\Resources\CartResource;
use App\Models\Cart;
use App\Models\CartItem;
use App\Models\Product;
use App\Models\ProductVariant;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class CartController extends Controller
{
    public function show(Request $request): Response
    {
        $cart = Cart::resolveOrCreate($request);
        $cart->load(['items.product.images', 'items.variant']);

        return Inertia::render('storefront/Cart', [
            'cart' => new CartResource($cart),
        ]);
    }

    public function addItem(AddCartItemRequest $request): JsonResponse
    {
        $cart = Cart::resolveOrCreate($request);
        $validated = $request->validated();

        $product = Product::query()->findOrFail($validated['product_id']);
        abort_unless($product->is_active, 422, 'This product is no longer available.');

        $unitPrice = $product->price;

        if (! empty($validated['variant_id'])) {
            $variant = ProductVariant::query()
                ->where('id', $validated['variant_id'])
                ->where('product_id', $product->id)
                ->where('is_active', true)
                ->firstOrFail();

            $unitPrice = $variant->price ?? $product->price;
        }

        $existing = $cart->items()
            ->where('product_id', $validated['product_id'])
            ->where('variant_id', $validated['variant_id'] ?? null)
            ->first();

        if ($existing) {
            $existing->increment('quantity', $validated['quantity']);
        } else {
            $cart->items()->create([
                'product_id' => $validated['product_id'],
                'variant_id' => $validated['variant_id'] ?? null,
                'quantity' => $validated['quantity'],
                'unit_price' => $unitPrice,
            ]);
        }

        $cart->load(['items.product.images', 'items.variant']);

        return response()->json(new CartResource($cart));
    }

    public function updateItem(UpdateCartItemRequest $request, CartItem $cartItem): JsonResponse
    {
        $this->authorizeCartItem($request, $cartItem);

        $quantity = $request->validated()['quantity'];

        if ($quantity === 0) {
            $cartItem->delete();
        } else {
            $cartItem->update(['quantity' => $quantity]);
        }

        $cart = $cartItem->cart->load(['items.product.images', 'items.variant']);

        return response()->json(new CartResource($cart));
    }

    public function removeItem(Request $request, CartItem $cartItem): JsonResponse
    {
        $this->authorizeCartItem($request, $cartItem);

        $cart = $cartItem->cart;
        $cartItem->delete();
        $cart->load(['items.product.images', 'items.variant']);

        return response()->json(new CartResource($cart));
    }

    public function clear(Request $request): JsonResponse
    {
        $cart = Cart::resolveOrCreate($request);
        $cart->items()->delete();
        $cart->load('items');

        return response()->json(new CartResource($cart));
    }

    public function sync(Request $request): JsonResponse
    {
        $request->validate([
            'items' => ['required', 'array'],
            'items.*.product_id' => ['required', 'integer', 'exists:products,id'],
            'items.*.variant_id' => ['nullable', 'integer', 'exists:product_variants,id'],
            'items.*.quantity' => ['required', 'integer', 'min:1', 'max:999'],
        ]);

        $cart = Cart::resolveOrCreate($request);

        foreach ($request->input('items') as $item) {
            $product = Product::query()->find($item['product_id']);

            if (! $product || ! $product->is_active) {
                continue;
            }

            $unitPrice = $product->price;
            $variantId = $item['variant_id'] ?? null;

            if ($variantId) {
                $variant = ProductVariant::query()
                    ->where('id', $variantId)
                    ->where('product_id', $product->id)
                    ->where('is_active', true)
                    ->first();

                if (! $variant) {
                    continue;
                }

                $unitPrice = $variant->price ?? $product->price;
            }

            $cart->items()->updateOrCreate(
                ['product_id' => $item['product_id'], 'variant_id' => $variantId],
                ['quantity' => $item['quantity'], 'unit_price' => $unitPrice],
            );
        }

        $cart->load(['items.product.images', 'items.variant']);

        return response()->json(new CartResource($cart));
    }

    private function authorizeCartItem(Request $request, CartItem $cartItem): void
    {
        $cart = $cartItem->cart;

        $isOwner = $request->user()
            ? $cart->user_id === $request->user()->id
            : $cart->session_id === $request->cookie('cart_token');

        abort_unless($isOwner, 403);
    }
}
