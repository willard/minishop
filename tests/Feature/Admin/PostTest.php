<?php

namespace Tests\Feature\Admin;

use App\Enums\PublishStatus;
use App\Models\Post;
use App\Models\Tag;
use App\Models\User;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PostTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RoleAndPermissionSeeder::class);
    }

    public function test_guests_are_redirected_from_index(): void
    {
        $this->get(route('admin.posts.index'))
            ->assertRedirect(route('login'));
    }

    public function test_super_admin_can_view_posts_index(): void
    {
        $user = User::factory()->superAdmin()->create();
        Post::factory(2)->create();

        $this->actingAs($user)
            ->get(route('admin.posts.index'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('admin/Posts/Index')
                ->has('posts.data', 2)
            );
    }

    public function test_super_admin_can_store_a_post_with_tags(): void
    {
        $user = User::factory()->superAdmin()->create();
        $tag = Tag::factory()->create();

        $this->actingAs($user)
            ->post(route('admin.posts.store'), [
                'title' => 'First Post',
                'excerpt' => 'Intro',
                'body' => '<p>Body.</p>',
                'status' => PublishStatus::Draft->value,
                'tag_ids' => [$tag->id],
            ])
            ->assertRedirect(route('admin.posts.index'))
            ->assertSessionHas('success');

        $post = Post::query()->where('title', 'First Post')->first();

        $this->assertNotNull($post);
        $this->assertTrue($post->tags->contains('id', $tag->id));
    }

    public function test_store_requires_title(): void
    {
        $user = User::factory()->superAdmin()->create();

        $this->actingAs($user)
            ->post(route('admin.posts.store'), [
                'status' => PublishStatus::Draft->value,
            ])
            ->assertSessionHasErrors('title');
    }

    public function test_store_sanitises_body_html(): void
    {
        $user = User::factory()->superAdmin()->create();

        $this->actingAs($user)
            ->post(route('admin.posts.store'), [
                'title' => 'XSS Post',
                'body' => '<p>Safe</p><script>alert(1)</script>',
                'status' => PublishStatus::Draft->value,
            ]);

        $post = Post::query()->where('title', 'XSS Post')->first();

        $this->assertStringNotContainsString('<script>', $post->body);
    }

    public function test_super_admin_can_update_post_tags(): void
    {
        $user = User::factory()->superAdmin()->create();
        $post = Post::factory()->create();
        $oldTag = Tag::factory()->create();
        $newTag = Tag::factory()->create();
        $post->tags()->sync([$oldTag->id]);

        $this->actingAs($user)
            ->put(route('admin.posts.update', $post), [
                'title' => $post->title,
                'slug' => $post->slug,
                'excerpt' => 'Updated',
                'body' => '<p>New body</p>',
                'status' => PublishStatus::Published->value,
                'tag_ids' => [$newTag->id],
            ])
            ->assertRedirect(route('admin.posts.index'));

        $post->refresh();

        $this->assertFalse($post->tags->contains('id', $oldTag->id));
        $this->assertTrue($post->tags->contains('id', $newTag->id));
    }

    public function test_super_admin_can_delete_a_post(): void
    {
        $user = User::factory()->superAdmin()->create();
        $post = Post::factory()->create();

        $this->actingAs($user)
            ->delete(route('admin.posts.destroy', $post))
            ->assertRedirect(route('admin.posts.index'));

        $this->assertDatabaseMissing('posts', ['id' => $post->id]);
    }

    public function test_manager_cannot_delete_a_post(): void
    {
        $user = User::factory()->manager()->create();
        $post = Post::factory()->create();

        $this->actingAs($user)
            ->delete(route('admin.posts.destroy', $post))
            ->assertForbidden();
    }
}
