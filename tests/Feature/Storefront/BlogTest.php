<?php

namespace Tests\Feature\Storefront;

use App\Models\Post;
use App\Models\Tag;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BlogTest extends TestCase
{
    use RefreshDatabase;

    public function test_blog_index_lists_published_posts(): void
    {
        Post::factory()->published()->create(['title' => 'Live One']);
        Post::factory()->draft()->create(['title' => 'Hidden Draft']);

        $this->get(route('storefront.blog.index'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('storefront/Blog/Index')
                ->has('posts.data', 1)
                ->where('posts.data.0.title', 'Live One')
            );
    }

    public function test_blog_index_filters_by_tag(): void
    {
        $tag = Tag::factory()->create(['slug' => 'news']);
        $tagged = Post::factory()->published()->create(['title' => 'Tagged Post']);
        $tagged->tags()->attach($tag);
        Post::factory()->published()->create(['title' => 'Untagged Post']);

        $this->get(route('storefront.blog.index', ['tag' => 'news']))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->has('posts.data', 1)
                ->where('posts.data.0.title', 'Tagged Post')
                ->where('activeTag', 'news')
            );
    }

    public function test_blog_show_displays_published_post(): void
    {
        $post = Post::factory()->published()->create(['slug' => 'hello-world']);

        $this->get(route('storefront.blog.show', $post))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('storefront/Blog/Show')
                ->where('post.id', $post->id)
            );
    }

    public function test_blog_show_returns_404_for_draft_post(): void
    {
        $post = Post::factory()->draft()->create(['slug' => 'draft-post']);

        $this->get(route('storefront.blog.show', $post))->assertNotFound();
    }

    public function test_blog_show_includes_related_posts_by_shared_tag(): void
    {
        $tag = Tag::factory()->create();
        $main = Post::factory()->published()->create();
        $main->tags()->attach($tag);
        $related = Post::factory()->published()->create();
        $related->tags()->attach($tag);
        Post::factory()->published()->create(); // unrelated

        $this->get(route('storefront.blog.show', $main))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->has('related', 1)
                ->where('related.0.id', $related->id)
            );
    }
}
