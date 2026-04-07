<?php

namespace App\Actions;

use App\Models\Product;
use App\Models\ProductVariant;

class BuildLineItemsAction
{
    /**
     * Build resolved line items from raw cart items, handling all product types.
     *
     * @param  array<int, array{product_id: int, variant_id: int|null, quantity: int}>  $items
     * @return array<int, array{product_id: int, variant_id: int|null, product_name: string, product_sku: string|null, unit_price: int, quantity: int, subtotal: int}>
     */
    public function execute(array $items): array
    {
        $lineItems = [];

        foreach ($items as $item) {
            $product = Product::query()->findOrFail($item['product_id']);
            abort_unless($product->is_active, 422, 'One or more products are no longer available.');

            if ($product->isBundled()) {
                $lineItems[] = $this->buildBundledLineItem($product, $item['quantity']);
            } elseif (! empty($item['variant_id'])) {
                $lineItems[] = $this->buildVariantLineItem($product, $item['variant_id'], $item['quantity']);
            } else {
                $lineItems[] = $this->buildSimpleLineItem($product, $item['quantity']);
            }
        }

        return $lineItems;
    }

    /**
     * @return array{product_id: int, variant_id: int|null, product_name: string, product_sku: string|null, unit_price: int, quantity: int, subtotal: int}
     */
    private function buildSimpleLineItem(Product $product, int $quantity): array
    {
        abort_if($product->stock_quantity < $quantity, 422, "Insufficient stock for {$product->name}.");

        return [
            'product_id' => $product->id,
            'variant_id' => null,
            'product_name' => $product->name,
            'product_sku' => $product->sku,
            'unit_price' => $product->price,
            'quantity' => $quantity,
            'subtotal' => $product->price * $quantity,
        ];
    }

    /**
     * @return array{product_id: int, variant_id: int|null, product_name: string, product_sku: string|null, unit_price: int, quantity: int, subtotal: int}
     */
    private function buildVariantLineItem(Product $product, int $variantId, int $quantity): array
    {
        $variant = ProductVariant::query()
            ->where('id', $variantId)
            ->where('product_id', $product->id)
            ->where('is_active', true)
            ->firstOrFail();

        abort_if($variant->stock_quantity < $quantity, 422, "Insufficient stock for {$product->name}.");

        $unitPrice = $variant->price ?? $product->price;
        $sku = $variant->sku ?? $product->sku;

        return [
            'product_id' => $product->id,
            'variant_id' => $variant->id,
            'product_name' => $product->name,
            'product_sku' => $sku,
            'unit_price' => $unitPrice,
            'quantity' => $quantity,
            'subtotal' => $unitPrice * $quantity,
        ];
    }

    /**
     * @return array{product_id: int, variant_id: int|null, product_name: string, product_sku: string|null, unit_price: int, quantity: int, subtotal: int}
     */
    private function buildBundledLineItem(Product $product, int $quantity): array
    {
        $effectiveStock = $product->getEffectiveStock();

        abort_if($effectiveStock < $quantity, 422, "Insufficient stock for {$product->name}.");

        return [
            'product_id' => $product->id,
            'variant_id' => null,
            'product_name' => $product->name,
            'product_sku' => $product->sku,
            'unit_price' => $product->price,
            'quantity' => $quantity,
            'subtotal' => $product->price * $quantity,
        ];
    }
}
