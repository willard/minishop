<?php

namespace Tests\Feature\Admin;

use App\Enums\PageTemplate;
use App\Enums\PublishStatus;
use App\Models\Page;
use App\Models\User;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PageTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RoleAndPermissionSeeder::class);
    }

    public function test_guests_are_redirected_from_index(): void
    {
        $this->get(route('admin.pages.index'))
            ->assertRedirect(route('login'));
    }

    public function test_guests_are_redirected_from_store(): void
    {
        $this->post(route('admin.pages.store'), [])
            ->assertRedirect(route('login'));
    }

    public function test_super_admin_can_view_pages_index(): void
    {
        $user = User::factory()->superAdmin()->create();
        Page::factory(3)->create();

        $this->actingAs($user)
            ->get(route('admin.pages.index'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('admin/Pages/Index')
                ->has('pages.data', 3)
            );
    }

    public function test_super_admin_can_view_create_page_form(): void
    {
        $user = User::factory()->superAdmin()->create();

        $this->actingAs($user)
            ->get(route('admin.pages.create'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page->component('admin/Pages/Create'));
    }

    public function test_super_admin_can_store_a_page(): void
    {
        $user = User::factory()->superAdmin()->create();

        $this->actingAs($user)
            ->post(route('admin.pages.store'), [
                'title' => 'About Us',
                'body' => '<p>Hello world.</p>',
                'excerpt' => 'Short description',
                'status' => PublishStatus::Draft->value,
                'template' => PageTemplate::Default->value,
            ])
            ->assertRedirect(route('admin.pages.index'))
            ->assertSessionHas('success');

        $this->assertDatabaseHas('pages', [
            'title' => 'About Us',
            'slug' => 'about-us',
            'author_id' => $user->id,
        ]);
    }

    public function test_publishing_a_page_sets_published_at(): void
    {
        $user = User::factory()->superAdmin()->create();

        $this->actingAs($user)
            ->post(route('admin.pages.store'), [
                'title' => 'Live Page',
                'body' => '<p>Content.</p>',
                'status' => PublishStatus::Published->value,
                'template' => PageTemplate::Default->value,
            ])
            ->assertSessionHas('success');

        $page = Page::query()->where('title', 'Live Page')->first();

        $this->assertNotNull($page->published_at);
    }

    public function test_store_requires_title(): void
    {
        $user = User::factory()->superAdmin()->create();

        $this->actingAs($user)
            ->post(route('admin.pages.store'), [
                'status' => PublishStatus::Draft->value,
                'template' => PageTemplate::Default->value,
            ])
            ->assertSessionHasErrors('title');
    }

    public function test_store_sanitises_body_html(): void
    {
        $user = User::factory()->superAdmin()->create();

        $this->actingAs($user)
            ->post(route('admin.pages.store'), [
                'title' => 'Dangerous Page',
                'body' => '<p>Safe</p><script>alert(1)</script>',
                'status' => PublishStatus::Draft->value,
                'template' => PageTemplate::Default->value,
            ]);

        $page = Page::query()->where('title', 'Dangerous Page')->first();

        $this->assertStringNotContainsString('<script>', $page->body);
        $this->assertStringContainsString('Safe', $page->body);
    }

    public function test_super_admin_can_update_a_page(): void
    {
        $user = User::factory()->superAdmin()->create();
        $page = Page::factory()->draft()->create(['title' => 'Old Title']);

        $this->actingAs($user)
            ->put(route('admin.pages.update', $page), [
                'title' => 'New Title',
                'slug' => $page->slug,
                'body' => '<p>Updated body</p>',
                'status' => PublishStatus::Published->value,
                'template' => PageTemplate::Default->value,
            ])
            ->assertRedirect(route('admin.pages.index'));

        $this->assertDatabaseHas('pages', [
            'id' => $page->id,
            'title' => 'New Title',
        ]);
    }

    public function test_super_admin_can_delete_a_page(): void
    {
        $user = User::factory()->superAdmin()->create();
        $page = Page::factory()->create();

        $this->actingAs($user)
            ->delete(route('admin.pages.destroy', $page))
            ->assertRedirect(route('admin.pages.index'));

        $this->assertDatabaseMissing('pages', ['id' => $page->id]);
    }

    public function test_manager_cannot_delete_a_page(): void
    {
        $user = User::factory()->manager()->create();
        $page = Page::factory()->create();

        $this->actingAs($user)
            ->delete(route('admin.pages.destroy', $page))
            ->assertForbidden();

        $this->assertDatabaseHas('pages', ['id' => $page->id]);
    }

    public function test_manager_can_create_a_page(): void
    {
        $user = User::factory()->manager()->create();

        $this->actingAs($user)
            ->post(route('admin.pages.store'), [
                'title' => 'Manager Page',
                'body' => '<p>By manager.</p>',
                'status' => PublishStatus::Draft->value,
                'template' => PageTemplate::Default->value,
            ])
            ->assertRedirect(route('admin.pages.index'));
    }
}
