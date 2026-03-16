<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\CartItem>
 */
class CartItemFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'cart_id' => \App\Models\Cart::factory(),
            'product_id' => \App\Models\Product::factory(),
            'variant_id' => null,
            'quantity' => fake()->numberBetween(1, 5),
            'unit_price' => fake()->numberBetween(100, 20000),
        ];
    }
}
