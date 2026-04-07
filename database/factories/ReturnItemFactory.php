<?php

namespace Database\Factories;

use App\Models\OrderItem;
use App\Models\OrderReturn;
use App\Models\ReturnItem;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ReturnItem>
 */
class ReturnItemFactory extends Factory
{
    public function definition(): array
    {
        $unitPrice = fake()->numberBetween(100, 10000);
        $quantity = fake()->numberBetween(1, 3);

        return [
            'return_id' => OrderReturn::factory(),
            'order_item_id' => OrderItem::factory(),
            'quantity' => $quantity,
            'unit_price' => $unitPrice,
            'subtotal' => $unitPrice * $quantity,
        ];
    }
}
