<?php

namespace Tests\Feature\Admin;

use App\Models\Product;
use App\Models\ProductImage;
use App\Models\ProductVariant;
use App\Models\User;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ProductImageTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RoleAndPermissionSeeder::class);
    }

    public function test_guests_are_redirected_from_store(): void
    {
        $product = Product::factory()->create();

        $this->post(route('admin.products.images.store', $product))
            ->assertRedirect(route('login'));
    }

    public function test_guests_are_redirected_from_destroy(): void
    {
        $product = Product::factory()->create();
        $image = ProductImage::factory()->create(['product_id' => $product->id]);

        $this->delete(route('admin.products.images.destroy', [$product, $image]))
            ->assertRedirect(route('login'));
    }

    public function test_guests_are_redirected_from_reorder(): void
    {
        $product = Product::factory()->create();

        $this->put(route('admin.products.images.reorder', $product))
            ->assertRedirect(route('login'));
    }

    public function test_upload_stores_files_and_creates_records(): void
    {
        Storage::fake('public');
        $user = User::factory()->superAdmin()->create();
        $product = Product::factory()->create();

        $this->actingAs($user)
            ->post(route('admin.products.images.store', $product), [
                'images' => [
                    UploadedFile::fake()->image('photo1.jpg'),
                    UploadedFile::fake()->image('photo2.png'),
                ],
            ])
            ->assertRedirect(route('admin.products.show', $product));

        $this->assertDatabaseCount('product_images', 2);
        $this->assertDatabaseHas('product_images', [
            'product_id' => $product->id,
            'sort_order' => 0,
        ]);
        $this->assertDatabaseHas('product_images', [
            'product_id' => $product->id,
            'sort_order' => 1,
        ]);

        $images = ProductImage::where('product_id', $product->id)->get();
        foreach ($images as $image) {
            Storage::disk('public')->assertExists($image->path);
        }
    }

    public function test_upload_auto_increments_sort_order(): void
    {
        Storage::fake('public');
        $user = User::factory()->superAdmin()->create();
        $product = Product::factory()->create();
        ProductImage::factory()->create(['product_id' => $product->id, 'sort_order' => 2]);

        $this->actingAs($user)
            ->post(route('admin.products.images.store', $product), [
                'images' => [UploadedFile::fake()->image('new.jpg')],
            ]);

        $newImage = ProductImage::where('product_id', $product->id)
            ->orderByDesc('sort_order')
            ->first();

        $this->assertSame(3, $newImage->sort_order);
    }

    public function test_upload_saves_alt_text(): void
    {
        Storage::fake('public');
        $user = User::factory()->superAdmin()->create();
        $product = Product::factory()->create();

        $this->actingAs($user)
            ->post(route('admin.products.images.store', $product), [
                'images' => [UploadedFile::fake()->image('photo.jpg')],
                'alt_text' => 'Custom alt text',
            ]);

        $this->assertDatabaseHas('product_images', [
            'product_id' => $product->id,
            'alt_text' => 'Custom alt text',
        ]);
    }

    public function test_upload_rejects_non_image_files(): void
    {
        Storage::fake('public');
        $user = User::factory()->superAdmin()->create();
        $product = Product::factory()->create();

        $this->actingAs($user)
            ->post(route('admin.products.images.store', $product), [
                'images' => [UploadedFile::fake()->create('document.pdf', 100)],
            ])
            ->assertSessionHasErrors('images.0');
    }

    public function test_upload_rejects_files_over_2mb(): void
    {
        Storage::fake('public');
        $user = User::factory()->superAdmin()->create();
        $product = Product::factory()->create();

        $this->actingAs($user)
            ->post(route('admin.products.images.store', $product), [
                'images' => [UploadedFile::fake()->image('huge.jpg')->size(3000)],
            ])
            ->assertSessionHasErrors('images.0');
    }

    public function test_upload_requires_at_least_one_image(): void
    {
        Storage::fake('public');
        $user = User::factory()->superAdmin()->create();
        $product = Product::factory()->create();

        $this->actingAs($user)
            ->post(route('admin.products.images.store', $product), [
                'images' => [],
            ])
            ->assertSessionHasErrors('images');
    }

    public function test_delete_removes_file_and_record(): void
    {
        Storage::fake('public');
        $user = User::factory()->superAdmin()->create();
        $product = Product::factory()->create();
        $path = UploadedFile::fake()->image('test.jpg')
            ->storeAs("products/{$product->id}", 'test.jpg', 'public');
        $image = ProductImage::factory()->create([
            'product_id' => $product->id,
            'path' => $path,
        ]);

        $this->actingAs($user)
            ->delete(route('admin.products.images.destroy', [$product, $image]))
            ->assertRedirect(route('admin.products.show', $product));

        $this->assertDatabaseMissing('product_images', ['id' => $image->id]);
        Storage::disk('public')->assertMissing($path);
    }

    public function test_delete_only_works_for_owning_product(): void
    {
        $user = User::factory()->superAdmin()->create();
        $product = Product::factory()->create();
        $otherProduct = Product::factory()->create();
        $image = ProductImage::factory()->create(['product_id' => $otherProduct->id]);

        $this->actingAs($user)
            ->delete(route('admin.products.images.destroy', [$product, $image]))
            ->assertNotFound();
    }

    public function test_reorder_updates_sort_order(): void
    {
        $user = User::factory()->superAdmin()->create();
        $product = Product::factory()->create();
        $image1 = ProductImage::factory()->create(['product_id' => $product->id, 'sort_order' => 0]);
        $image2 = ProductImage::factory()->create(['product_id' => $product->id, 'sort_order' => 1]);
        $image3 = ProductImage::factory()->create(['product_id' => $product->id, 'sort_order' => 2]);

        $this->actingAs($user)
            ->put(route('admin.products.images.reorder', $product), [
                'image_ids' => [$image3->id, $image1->id, $image2->id],
            ])
            ->assertRedirect(route('admin.products.show', $product));

        $this->assertSame(1, $image1->fresh()->sort_order);
        $this->assertSame(2, $image2->fresh()->sort_order);
        $this->assertSame(0, $image3->fresh()->sort_order);
    }

    public function test_reorder_requires_image_ids(): void
    {
        $user = User::factory()->superAdmin()->create();
        $product = Product::factory()->create();

        $this->actingAs($user)
            ->put(route('admin.products.images.reorder', $product), [])
            ->assertSessionHasErrors('image_ids');
    }

    public function test_upload_with_variant_id_assigns_image_to_variant(): void
    {
        Storage::fake('public');
        $user = User::factory()->superAdmin()->create();
        $product = Product::factory()->create();
        $variant = ProductVariant::factory()->create(['product_id' => $product->id]);

        $this->actingAs($user)
            ->post(route('admin.products.images.store', $product), [
                'images' => [UploadedFile::fake()->image('variant-photo.jpg')],
                'variant_id' => $variant->id,
            ])
            ->assertRedirect(route('admin.products.show', $product));

        $this->assertDatabaseHas('product_images', [
            'product_id' => $product->id,
            'variant_id' => $variant->id,
        ]);
    }

    public function test_product_images_scope_excludes_variant_images(): void
    {
        $product = Product::factory()->create();
        $variant = ProductVariant::factory()->create(['product_id' => $product->id]);

        ProductImage::factory()->create(['product_id' => $product->id, 'variant_id' => null]);
        ProductImage::factory()->create(['product_id' => $product->id, 'variant_id' => $variant->id]);

        $this->assertCount(1, $product->images);
        $this->assertNull($product->images->first()->variant_id);
    }

    public function test_variant_images_relationship_returns_only_variant_images(): void
    {
        $product = Product::factory()->create();
        $variant = ProductVariant::factory()->create(['product_id' => $product->id]);

        ProductImage::factory()->create(['product_id' => $product->id, 'variant_id' => null]);
        ProductImage::factory()->create(['product_id' => $product->id, 'variant_id' => $variant->id]);
        ProductImage::factory()->create(['product_id' => $product->id, 'variant_id' => $variant->id]);

        $this->assertCount(2, $variant->images);
        foreach ($variant->images as $image) {
            $this->assertEquals($variant->id, $image->variant_id);
        }
    }

    public function test_upload_with_invalid_variant_id_fails_validation(): void
    {
        Storage::fake('public');
        $user = User::factory()->superAdmin()->create();
        $product = Product::factory()->create();

        $this->actingAs($user)
            ->post(route('admin.products.images.store', $product), [
                'images' => [UploadedFile::fake()->image('photo.jpg')],
                'variant_id' => 99999,
            ])
            ->assertSessionHasErrors('variant_id');
    }

    public function test_product_delete_cleans_up_image_files(): void
    {
        Storage::fake('public');
        $user = User::factory()->superAdmin()->create();
        $product = Product::factory()->create();

        $path1 = UploadedFile::fake()->image('a.jpg')
            ->storeAs("products/{$product->id}", 'a.jpg', 'public');
        $path2 = UploadedFile::fake()->image('b.jpg')
            ->storeAs("products/{$product->id}", 'b.jpg', 'public');

        ProductImage::factory()->create(['product_id' => $product->id, 'path' => $path1]);
        ProductImage::factory()->create(['product_id' => $product->id, 'path' => $path2]);

        Storage::disk('public')->assertExists($path1);
        Storage::disk('public')->assertExists($path2);

        $product->delete();

        Storage::disk('public')->assertMissing($path1);
        Storage::disk('public')->assertMissing($path2);
    }
}
