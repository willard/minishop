<?php

namespace Tests\Feature\Admin;

use App\Models\Post;
use App\Models\Product;
use App\Models\Tag;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TagPolymorphicTest extends TestCase
{
    use RefreshDatabase;

    public function test_tag_can_be_attached_to_product_and_post(): void
    {
        $tag = Tag::factory()->create();
        $product = Product::factory()->create();
        $post = Post::factory()->create();

        $product->tags()->attach($tag);
        $post->tags()->attach($tag);

        $this->assertTrue($product->fresh()->tags->contains('id', $tag->id));
        $this->assertTrue($post->fresh()->tags->contains('id', $tag->id));
    }

    public function test_tag_exposes_products_and_posts_morphed_relations(): void
    {
        $tag = Tag::factory()->create();
        $product = Product::factory()->create();
        $post = Post::factory()->create();

        $product->tags()->attach($tag);
        $post->tags()->attach($tag);

        $this->assertTrue($tag->products->contains('id', $product->id));
        $this->assertTrue($tag->posts->contains('id', $post->id));
        $this->assertCount(1, $tag->products);
        $this->assertCount(1, $tag->posts);
    }

    public function test_taggable_rows_use_correct_morph_type(): void
    {
        $tag = Tag::factory()->create();
        $product = Product::factory()->create();
        $post = Post::factory()->create();

        $product->tags()->attach($tag);
        $post->tags()->attach($tag);

        $this->assertDatabaseHas('taggables', [
            'tag_id' => $tag->id,
            'taggable_id' => $product->id,
            'taggable_type' => Product::class,
        ]);

        $this->assertDatabaseHas('taggables', [
            'tag_id' => $tag->id,
            'taggable_id' => $post->id,
            'taggable_type' => Post::class,
        ]);
    }

    public function test_detaching_from_one_model_does_not_affect_other(): void
    {
        $tag = Tag::factory()->create();
        $product = Product::factory()->create();
        $post = Post::factory()->create();

        $product->tags()->attach($tag);
        $post->tags()->attach($tag);

        $product->tags()->detach($tag);

        $this->assertFalse($product->fresh()->tags->contains('id', $tag->id));
        $this->assertTrue($post->fresh()->tags->contains('id', $tag->id));
    }
}
