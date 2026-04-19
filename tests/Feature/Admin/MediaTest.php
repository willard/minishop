<?php

namespace Tests\Feature\Admin;

use App\Models\Media;
use App\Models\User;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class MediaTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RoleAndPermissionSeeder::class);
    }

    public function test_guests_are_redirected_from_index(): void
    {
        $this->get(route('admin.media.index'))
            ->assertRedirect(route('login'));
    }

    public function test_super_admin_can_view_media_index(): void
    {
        $user = User::factory()->superAdmin()->create();
        Media::factory(3)->create();

        $this->actingAs($user)
            ->get(route('admin.media.index'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('admin/Media/Index')
                ->has('media.data', 3)
            );
    }

    public function test_super_admin_can_upload_a_file(): void
    {
        Storage::fake('public');

        $user = User::factory()->superAdmin()->create();
        $file = UploadedFile::fake()->image('logo.png');

        $this->actingAs($user)
            ->post(route('admin.media.store'), [
                'file' => $file,
                'alt_text' => 'Company logo',
            ])
            ->assertRedirect(route('admin.media.index'))
            ->assertSessionHas('success');

        $media = Media::query()->first();

        $this->assertNotNull($media);
        $this->assertSame('Company logo', $media->alt_text);
        $this->assertSame('logo.png', $media->original_name);
        $this->assertSame($user->id, $media->uploaded_by);
        Storage::disk('public')->assertExists($media->path);
    }

    public function test_upload_requires_valid_file(): void
    {
        $user = User::factory()->superAdmin()->create();

        $this->actingAs($user)
            ->post(route('admin.media.store'), [])
            ->assertSessionHasErrors('file');
    }

    public function test_upload_rejects_disallowed_mime(): void
    {
        Storage::fake('public');

        $user = User::factory()->superAdmin()->create();
        $file = UploadedFile::fake()->create('malicious.exe', 10, 'application/octet-stream');

        $this->actingAs($user)
            ->post(route('admin.media.store'), ['file' => $file])
            ->assertSessionHasErrors('file');
    }

    public function test_super_admin_can_update_alt_text(): void
    {
        $user = User::factory()->superAdmin()->create();
        $media = Media::factory()->create(['alt_text' => 'old']);

        $this->actingAs($user)
            ->put(route('admin.media.update', $media), ['alt_text' => 'new'])
            ->assertRedirect();

        $this->assertDatabaseHas('media', ['id' => $media->id, 'alt_text' => 'new']);
    }

    public function test_super_admin_can_delete_media(): void
    {
        Storage::fake('public');

        $user = User::factory()->superAdmin()->create();
        $file = UploadedFile::fake()->image('delete.png');
        $path = $file->storeAs('media/test', 'delete.png', 'public');
        $media = Media::factory()->create(['disk' => 'public', 'path' => $path]);

        $this->actingAs($user)
            ->delete(route('admin.media.destroy', $media))
            ->assertRedirect(route('admin.media.index'));

        $this->assertDatabaseMissing('media', ['id' => $media->id]);
        Storage::disk('public')->assertMissing($path);
    }

    public function test_manager_cannot_delete_media(): void
    {
        $user = User::factory()->manager()->create();
        $media = Media::factory()->create();

        $this->actingAs($user)
            ->delete(route('admin.media.destroy', $media))
            ->assertForbidden();
    }
}
