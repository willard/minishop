<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\ActivityLog>
 */
class ActivityLogFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $action = fake()->randomElement(['created', 'updated', 'deleted']);
        $subjectType = fake()->randomElement(['Product', 'Order', 'Coupon']);
        $subjectId = fake()->numberBetween(1, 100);

        return [
            'user_id' => User::factory(),
            'action' => $action,
            'subject_type' => $subjectType,
            'subject_id' => $subjectId,
            'description' => "{$action} {$subjectType} #{$subjectId}",
            'properties' => null,
        ];
    }
}
