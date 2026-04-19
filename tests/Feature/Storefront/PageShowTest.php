<?php

namespace Tests\Feature\Storefront;

use App\Models\Page;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PageShowTest extends TestCase
{
    use RefreshDatabase;

    public function test_published_page_is_accessible(): void
    {
        $page = Page::factory()->published()->create(['slug' => 'about']);

        $this->get('/pages/about')
            ->assertOk()
            ->assertInertia(fn ($props) => $props
                ->component('storefront/Pages/Show')
                ->where('page.id', $page->id)
                ->where('page.slug', 'about')
            );
    }

    public function test_draft_page_returns_404(): void
    {
        Page::factory()->draft()->create(['slug' => 'secret']);

        $this->get('/pages/secret')->assertNotFound();
    }

    public function test_scheduled_page_returns_404(): void
    {
        Page::factory()->scheduled()->create(['slug' => 'coming-soon']);

        $this->get('/pages/coming-soon')->assertNotFound();
    }

    public function test_missing_page_returns_404(): void
    {
        $this->get('/pages/does-not-exist')->assertNotFound();
    }
}
