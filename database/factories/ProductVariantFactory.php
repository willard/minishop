<?php

namespace Database\Factories;

use App\Models\Product;
use App\Models\ProductVariant;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\ProductVariant>
 */
class ProductVariantFactory extends Factory
{
    protected $model = ProductVariant::class;

    public function definition(): array
    {
        return [
            'product_id' => Product::factory(),
            'sku' => $this->faker->boolean(50) ? strtoupper($this->faker->unique()->bothify('VAR-###??')) : null,
            'price' => $this->faker->boolean(50) ? $this->faker->numberBetween(500, 9999) : null,
            'stock_quantity' => $this->faker->numberBetween(0, 100),
            'is_active' => true,
        ];
    }
}
