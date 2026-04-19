<?php

namespace Database\Factories;

use App\Enums\MenuLocation;
use App\Models\MenuItem;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<MenuItem>
 */
class MenuItemFactory extends Factory
{
    protected $model = MenuItem::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'menu_location' => MenuLocation::HeaderPrimary,
            'label' => fake()->words(2, true),
            'url' => '/'.fake()->slug(),
            'target' => '_self',
            'sort_order' => fake()->numberBetween(0, 100),
            'parent_id' => null,
        ];
    }
}
