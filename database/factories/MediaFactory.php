<?php

namespace Database\Factories;

use App\Models\Media;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Media>
 */
class MediaFactory extends Factory
{
    protected $model = Media::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $name = fake()->slug(2).'.jpg';

        return [
            'disk' => 'public',
            'path' => 'media/'.now()->format('Y/m').'/'.$name,
            'original_name' => $name,
            'mime_type' => 'image/jpeg',
            'size' => fake()->numberBetween(10_000, 500_000),
            'alt_text' => fake()->sentence(),
            'uploaded_by' => User::factory(),
        ];
    }
}
