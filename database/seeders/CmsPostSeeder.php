<?php

namespace Database\Seeders;

use App\Enums\PublishStatus;
use App\Models\Post;
use App\Models\Tag;
use App\Models\User;
use Illuminate\Database\Seeder;

class CmsPostSeeder extends Seeder
{
    public function run(): void
    {
        $author = User::query()->role('super-admin')->first() ?? User::query()->first();

        $news = Tag::query()->firstOrCreate(
            ['slug' => 'news'],
            ['name' => 'News', 'color' => '#2563eb', 'is_active' => true],
        );

        $tutorials = Tag::query()->firstOrCreate(
            ['slug' => 'tutorials'],
            ['name' => 'Tutorials', 'color' => '#16a34a', 'is_active' => true],
        );

        $posts = [
            [
                'title' => 'Welcome to our blog',
                'slug' => 'welcome',
                'excerpt' => 'We are launching a blog to share news, tutorials, and stories.',
                'body' => '<p>Welcome! This is our first post. Stay tuned for more updates from the shop.</p>',
                'tags' => [$news->id],
            ],
            [
                'title' => 'How to use the storefront',
                'slug' => 'how-to-use-the-storefront',
                'excerpt' => 'A quick tour of browsing, checkout, and account features.',
                'body' => '<p>Browse products, add to cart, and checkout in a few clicks.</p>',
                'tags' => [$tutorials->id],
            ],
        ];

        foreach ($posts as $data) {
            $tagIds = $data['tags'];
            unset($data['tags']);

            $post = Post::query()->updateOrCreate(
                ['slug' => $data['slug']],
                $data + [
                    'status' => PublishStatus::Published,
                    'published_at' => now(),
                    'author_id' => $author?->id,
                ],
            );

            $post->tags()->sync($tagIds);
        }
    }
}
