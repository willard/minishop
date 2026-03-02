<?php

namespace Tests\Feature\Admin;

use App\Models\Product;
use App\Models\ProductOption;
use App\Models\User;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProductOptionTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RoleAndPermissionSeeder::class);
    }

    public function test_guests_are_redirected_from_create_form(): void
    {
        $product = Product::factory()->create();

        $this->get(route('admin.products.options.create', $product))
            ->assertRedirect(route('login'));
    }

    public function test_guests_are_redirected_from_store(): void
    {
        $product = Product::factory()->create();

        $this->post(route('admin.products.options.store', $product), [])
            ->assertRedirect(route('login'));
    }

    public function test_guests_are_redirected_from_destroy(): void
    {
        $product = Product::factory()->create();
        $option = $product->options()->create(['name' => 'Size', 'position' => 0]);

        $this->delete(route('admin.products.options.destroy', [$product, $option]))
            ->assertRedirect(route('login'));
    }

    public function test_authenticated_users_can_view_create_option_form(): void
    {
        $user = User::factory()->superAdmin()->create();
        $product = Product::factory()->create();

        $this->actingAs($user)
            ->get(route('admin.products.options.create', $product))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('admin/Products/Options/Create')
                ->has('product')
            );
    }

    public function test_store_creates_option_type_with_values(): void
    {
        $user = User::factory()->superAdmin()->create();
        $product = Product::factory()->create();

        $this->actingAs($user)
            ->post(route('admin.products.options.store', $product), [
                'name' => 'Size',
                'values' => ['S', 'M', 'L'],
            ])
            ->assertRedirect(route('admin.products.show', $product))
            ->assertSessionHas('success');

        $this->assertDatabaseHas('product_options', [
            'product_id' => $product->id,
            'name' => 'Size',
        ]);

        $option = ProductOption::query()->where('product_id', $product->id)->first();
        $this->assertNotNull($option);
        $this->assertCount(3, $option->values);
        $this->assertEquals(['S', 'M', 'L'], $option->values->pluck('value')->all());
    }

    public function test_store_assigns_sequential_positions_to_values(): void
    {
        $user = User::factory()->superAdmin()->create();
        $product = Product::factory()->create();

        $this->actingAs($user)
            ->post(route('admin.products.options.store', $product), [
                'name' => 'Color',
                'values' => ['Red', 'Blue', 'Green'],
            ]);

        $option = ProductOption::query()->where('product_id', $product->id)->first();
        $positions = $option->values->pluck('position')->all();
        $this->assertEquals([0, 1, 2], $positions);
    }

    public function test_store_requires_name(): void
    {
        $user = User::factory()->superAdmin()->create();
        $product = Product::factory()->create();

        $this->actingAs($user)
            ->post(route('admin.products.options.store', $product), [
                'name' => '',
                'values' => ['S'],
            ])
            ->assertSessionHasErrors('name');
    }

    public function test_store_requires_at_least_one_value(): void
    {
        $user = User::factory()->superAdmin()->create();
        $product = Product::factory()->create();

        $this->actingAs($user)
            ->post(route('admin.products.options.store', $product), [
                'name' => 'Size',
                'values' => [],
            ])
            ->assertSessionHasErrors('values');
    }

    public function test_store_requires_values_to_be_non_empty_strings(): void
    {
        $user = User::factory()->superAdmin()->create();
        $product = Product::factory()->create();

        $this->actingAs($user)
            ->post(route('admin.products.options.store', $product), [
                'name' => 'Size',
                'values' => [''],
            ])
            ->assertSessionHasErrors('values.0');
    }

    public function test_destroy_deletes_option_type_and_cascades_to_values(): void
    {
        $user = User::factory()->superAdmin()->create();
        $product = Product::factory()->create();
        $option = $product->options()->create(['name' => 'Size', 'position' => 0]);
        $value = $option->values()->create(['value' => 'M', 'position' => 0]);

        $this->actingAs($user)
            ->delete(route('admin.products.options.destroy', [$product, $option]))
            ->assertRedirect(route('admin.products.show', $product))
            ->assertSessionHas('success');

        $this->assertDatabaseMissing('product_options', ['id' => $option->id]);
        $this->assertDatabaseMissing('product_option_values', ['id' => $value->id]);
    }

    public function test_option_scoped_to_parent_product(): void
    {
        $user = User::factory()->superAdmin()->create();
        $productA = Product::factory()->create();
        $productB = Product::factory()->create();
        $optionOfB = $productB->options()->create(['name' => 'Size', 'position' => 0]);

        $this->actingAs($user)
            ->delete(route('admin.products.options.destroy', [$productA, $optionOfB]))
            ->assertNotFound();
    }
}
