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
        $optionType = $this->faker->randomElement(['Size', 'Color']);
        $optionValue = $optionType === 'Size'
            ? $this->faker->randomElement(['XS', 'S', 'M', 'L', 'XL'])
            : $this->faker->randomElement(['Red', 'Blue', 'Green', 'Black', 'White']);

        return [
            'product_id' => Product::factory(),
            'sku' => $this->faker->boolean(50) ? strtoupper($this->faker->unique()->bothify('VAR-###??')) : null,
            'price' => $this->faker->boolean(50) ? $this->faker->numberBetween(500, 9999) : null,
            'stock_quantity' => $this->faker->numberBetween(0, 100),
            'options' => [$optionType => $optionValue],
            'is_active' => true,
        ];
    }
}
