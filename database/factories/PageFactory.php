<?php

namespace Database\Factories;

use App\Enums\PageTemplate;
use App\Enums\PublishStatus;
use App\Models\Page;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Page>
 */
class PageFactory extends Factory
{
    protected $model = Page::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $title = fake()->unique()->sentence(3);

        return [
            'title' => $title,
            'body' => '<p>'.fake()->paragraphs(3, true).'</p>',
            'excerpt' => fake()->sentence(),
            'status' => PublishStatus::Draft,
            'published_at' => null,
            'meta_title' => null,
            'meta_description' => null,
            'template' => PageTemplate::Default,
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

    public function scheduled(): self
    {
        return $this->state([
            'status' => PublishStatus::Scheduled,
            'published_at' => now()->addWeek(),
        ]);
    }
}
