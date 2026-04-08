<?php

namespace Database\Factories;

use App\Models\BundleItem;
use App\Models\Product;
use App\Models\ProductVariant;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<BundleItem>
 */
class BundleItemFactory extends Factory
{
    protected $model = BundleItem::class;

    public function definition(): array
    {
        return [
            'bundle_product_id' => Product::factory()->bundled(),
            'component_product_id' => Product::factory()->simple(),
            'component_variant_id' => null,
            'quantity' => 1,
            'sort_order' => 0,
        ];
    }

    public function withVariant(): static
    {
        return $this->state(function (array $attributes) {
            return [
                'component_variant_id' => ProductVariant::factory()->for(
                    Product::find($attributes['component_product_id']) ?? Product::factory()->variable()
                ),
            ];
        });
    }

    public function quantity(int $quantity): static
    {
        return $this->state(fn (array $attributes) => ['quantity' => $quantity]);
    }
}
