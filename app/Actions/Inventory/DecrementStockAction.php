<?php

namespace App\Actions\Inventory;

use App\Models\Product;
use App\Models\ProductVariant;

class DecrementStockAction
{
    /**
     * Decrement stock for all line items, expanding bundles into component tuples.
     *
     * Must be called inside a DB transaction.
     *
     * @param  array<int, array{product_id: int, variant_id: int|null, quantity: int}>  $items
     */
    public function execute(array $items): void
    {
        $tuples = $this->expandAndAggregate($items);

        $variantIds = collect($tuples)->where('variant_id', '!=', null)->pluck('variant_id')->unique()->values()->all();
        $productIds = collect($tuples)->where('variant_id', null)->pluck('product_id')->unique()->values()->all();

        // Lock rows in consistent order to prevent deadlocks.
        $variants = ProductVariant::query()->whereIn('id', $variantIds)->orderBy('id')->lockForUpdate()->get()->keyBy('id');
        $products = Product::query()->whereIn('id', $productIds)->orderBy('id')->lockForUpdate()->get()->keyBy('id');

        foreach ($tuples as $tuple) {
            if ($tuple['variant_id'] !== null) {
                $variants[$tuple['variant_id']]->decrement('stock_quantity', $tuple['quantity']);
            } else {
                $products[$tuple['product_id']]->decrement('stock_quantity', $tuple['quantity']);
            }
        }
    }

    /**
     * Expand bundled products into component tuples and aggregate shared components.
     *
     * @param  array<int, array{product_id: int, variant_id: int|null, quantity: int}>  $items
     * @return array<int, array{product_id: int, variant_id: int|null, quantity: int}>
     */
    private function expandAndAggregate(array $items): array
    {
        // Pre-load all products with their type and bundle items.
        $productIds = collect($items)->pluck('product_id')->unique()->values()->all();
        $products = Product::query()
            ->whereIn('id', $productIds)
            ->with('bundleItems')
            ->get()
            ->keyBy('id');

        $tuples = [];

        foreach ($items as $item) {
            $product = $products[$item['product_id']];

            if ($product->isBundled()) {
                foreach ($product->bundleItems as $bundleItem) {
                    $key = $bundleItem->component_product_id.'-'.($bundleItem->component_variant_id ?? 'null');
                    $decrementQty = $bundleItem->quantity * $item['quantity'];

                    if (isset($tuples[$key])) {
                        $tuples[$key]['quantity'] += $decrementQty;
                    } else {
                        $tuples[$key] = [
                            'product_id' => $bundleItem->component_product_id,
                            'variant_id' => $bundleItem->component_variant_id,
                            'quantity' => $decrementQty,
                        ];
                    }
                }
            } else {
                $key = $item['product_id'].'-'.($item['variant_id'] ?? 'null');

                if (isset($tuples[$key])) {
                    $tuples[$key]['quantity'] += $item['quantity'];
                } else {
                    $tuples[$key] = [
                        'product_id' => $item['product_id'],
                        'variant_id' => $item['variant_id'] ?? null,
                        'quantity' => $item['quantity'],
                    ];
                }
            }
        }

        return array_values($tuples);
    }
}
