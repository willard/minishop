<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Cart>
 */
class CartFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'session_id' => fake()->uuid(),
            'user_id' => null,
        ];
    }

    public function forUser(\App\Models\User $user): static
    {
        return $this->state(['user_id' => $user->id, 'session_id' => null]);
    }
}
