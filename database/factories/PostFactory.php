<?php

namespace Database\Factories;

use App\Enums\PublishStatus;
use App\Models\Post;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Post>
 */
class PostFactory extends Factory
{
    protected $model = Post::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $title = fake()->unique()->sentence(4);

        return [
            'title' => $title,
            'excerpt' => fake()->sentence(),
            'body' => '<p>'.fake()->paragraphs(5, true).'</p>',
            'status' => PublishStatus::Draft,
            'published_at' => null,
            'meta_title' => null,
            'meta_description' => null,
            'author_id' => User::factory(),
        ];
    }

    public function published(): self
    {
        return $this->state([
            'status' => PublishStatus::Published,
            'published_at' => now()->subDay(),
        ]);
    }

    public function draft(): self
    {
        return $this->state(['status' => PublishStatus::Draft, 'published_at' => null]);
    }
}
