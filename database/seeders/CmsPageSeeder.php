<?php

namespace Database\Seeders;

use App\Enums\PageTemplate;
use App\Enums\PublishStatus;
use App\Models\Page;
use App\Models\User;
use Illuminate\Database\Seeder;

class CmsPageSeeder extends Seeder
{
    public function run(): void
    {
        $author = User::query()->role('super-admin')->first() ?? User::query()->first();

        $pages = [
            [
                'title' => 'About Us',
                'slug' => 'about',
                'excerpt' => 'Learn more about our story and mission.',
                'body' => '<h2>Our story</h2><p>We are a small business focused on quality and community. Update this page from the admin dashboard.</p>',
                'meta_title' => 'About Us',
                'meta_description' => 'Learn more about our story and mission.',
            ],
            [
                'title' => 'Contact',
                'slug' => 'contact',
                'excerpt' => 'Get in touch with our team.',
                'body' => '<h2>Contact us</h2><p>Reach out via email or the form below.</p>',
            ],
            [
                'title' => 'Privacy Policy',
                'slug' => 'privacy',
                'excerpt' => 'How we handle your data.',
                'body' => '<h2>Privacy policy</h2><p>Placeholder policy — replace with your real policy before launch.</p>',
            ],
            [
                'title' => 'Terms of Service',
                'slug' => 'terms',
                'excerpt' => 'The terms of using our storefront.',
                'body' => '<h2>Terms of service</h2><p>Placeholder terms — replace with your real terms before launch.</p>',
            ],
            [
                'title' => 'FAQ',
                'slug' => 'faq',
                'excerpt' => 'Answers to common questions.',
                'body' => '<h2>Frequently asked questions</h2><p>Add your FAQ content here.</p>',
            ],
        ];

        foreach ($pages as $data) {
            Page::query()->updateOrCreate(
                ['slug' => $data['slug']],
                $data + [
                    'status' => PublishStatus::Published,
                    'published_at' => now(),
                    'template' => PageTemplate::Default,
                    'author_id' => $author?->id,
                ],
            );
        }
    }
}
