<?php

namespace Tests\Feature\Admin;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Minishop\Database\Seeders\RoleAndPermissionSeeder;
use Minishop\Models\Product;
use Minishop\Models\ProductOption;
use Minishop\Models\ProductOptionValue;
use Minishop\Models\ProductVariant;
use Minishop\Models\User;
use Tests\TestCase;

class ProductVariantTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RoleAndPermissionSeeder::class);
    }

    /**
     * Create a product with a Size option (S, M) and return all relevant models.
     *
     * @return array{product: Product, sizeOption: ProductOption, valueS: ProductOptionValue, valueM: ProductOptionValue}
     */
    private function productWithOptions(): array
    {
        $product = Product::factory()->create();
        $sizeOption = $product->options()->create(['name' => 'Size', 'position' => 0]);
        $valueS = $sizeOption->values()->create(['value' => 'S', 'position' => 0]);
        $valueM = $sizeOption->values()->create(['value' => 'M', 'position' => 1]);

        return compact('product', 'sizeOption', 'valueS', 'valueM');
    }

    public function test_guests_are_redirected_from_create_form(): void
    {
        $product = Product::factory()->create();

        $this->get(route('admin.products.variants.create', $product))
            ->assertRedirect(route('login'));
    }

    public function test_guests_are_redirected_from_store(): void
    {
        $product = Product::factory()->create();

        $this->post(route('admin.products.variants.store', $product), [])
            ->assertRedirect(route('login'));
    }

    public function test_guests_are_redirected_from_edit_form(): void
    {
        $product = Product::factory()->create();
        $variant = ProductVariant::factory()->for($product)->create();

        $this->get(route('admin.products.variants.edit', [$product, $variant]))
            ->assertRedirect(route('login'));
    }

    public function test_super_admin_can_view_create_variant_form(): void
    {
        $user = User::factory()->superAdmin()->create();
        ['product' => $product] = $this->productWithOptions();

        $this->actingAs($user)
            ->get(route('admin.products.variants.create', $product))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('admin/Products/Variants/Create')
                ->has('product')
                ->has('optionTypes')
            );
    }

    public function test_super_admin_can_view_edit_variant_form(): void
    {
        $user = User::factory()->superAdmin()->create();
        ['product' => $product, 'valueS' => $valueS] = $this->productWithOptions();
        $variant = ProductVariant::factory()->for($product)->create();
        $variant->optionValues()->sync([$valueS->id]);

        $this->actingAs($user)
            ->get(route('admin.products.variants.edit', [$product, $variant]))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('admin/Products/Variants/Edit')
                ->has('product')
                ->has('variant')
                ->has('optionTypes')
            );
    }

    public function test_super_admin_can_store_a_variant(): void
    {
        $user = User::factory()->superAdmin()->create();
        ['product' => $product, 'valueM' => $valueM] = $this->productWithOptions();

        $this->actingAs($user)
            ->post(route('admin.products.variants.store', $product), [
                'option_value_ids' => [$valueM->id],
                'stock_quantity' => 10,
                'is_active' => true,
            ])
            ->assertRedirect(route('admin.products.show', $product));

        $this->assertDatabaseHas('product_variants', [
            'product_id' => $product->id,
            'stock_quantity' => 10,
        ]);

        $variant = ProductVariant::query()->where('product_id', $product->id)->first();
        $this->assertTrue($variant->optionValues->contains($valueM));
    }

    public function test_store_syncs_multiple_option_values(): void
    {
        $user = User::factory()->superAdmin()->create();
        $product = Product::factory()->create();
        $sizeOption = $product->options()->create(['name' => 'Size', 'position' => 0]);
        $valueM = $sizeOption->values()->create(['value' => 'M', 'position' => 0]);
        $colorOption = $product->options()->create(['name' => 'Color', 'position' => 1]);
        $valueBlue = $colorOption->values()->create(['value' => 'Blue', 'position' => 0]);

        $this->actingAs($user)
            ->post(route('admin.products.variants.store', $product), [
                'option_value_ids' => [$valueM->id, $valueBlue->id],
                'stock_quantity' => 5,
            ]);

        $variant = ProductVariant::query()->where('product_id', $product->id)->first();
        $this->assertCount(2, $variant->optionValues);
        $this->assertTrue($variant->optionValues->contains($valueM));
        $this->assertTrue($variant->optionValues->contains($valueBlue));
    }

    public function test_store_requires_stock_quantity(): void
    {
        $user = User::factory()->superAdmin()->create();
        ['product' => $product, 'valueS' => $valueS] = $this->productWithOptions();

        $this->actingAs($user)
            ->post(route('admin.products.variants.store', $product), [
                'option_value_ids' => [$valueS->id],
            ])
            ->assertSessionHasErrors('stock_quantity');
    }

    public function test_store_requires_at_least_one_option_value_id(): void
    {
        $user = User::factory()->superAdmin()->create();
        $product = Product::factory()->create();

        $this->actingAs($user)
            ->post(route('admin.products.variants.store', $product), [
                'option_value_ids' => [],
                'stock_quantity' => 5,
            ])
            ->assertSessionHasErrors('option_value_ids');
    }

    public function test_store_rejects_non_existent_option_value_id(): void
    {
        $user = User::factory()->superAdmin()->create();
        $product = Product::factory()->create();

        $this->actingAs($user)
            ->post(route('admin.products.variants.store', $product), [
                'option_value_ids' => [99999],
                'stock_quantity' => 5,
            ])
            ->assertSessionHasErrors('option_value_ids.0');
    }

    public function test_store_rejects_duplicate_sku(): void
    {
        $user = User::factory()->superAdmin()->create();
        ['product' => $product, 'valueS' => $valueS] = $this->productWithOptions();
        ProductVariant::factory()->for($product)->create(['sku' => 'UNIQUE-SKU']);

        $this->actingAs($user)
            ->post(route('admin.products.variants.store', $product), [
                'option_value_ids' => [$valueS->id],
                'stock_quantity' => 5,
                'sku' => 'UNIQUE-SKU',
            ])
            ->assertSessionHasErrors('sku');
    }

    public function test_super_admin_can_update_a_variant(): void
    {
        $user = User::factory()->superAdmin()->create();
        ['product' => $product, 'valueS' => $valueS, 'valueM' => $valueM] = $this->productWithOptions();
        $variant = ProductVariant::factory()->for($product)->create(['stock_quantity' => 5]);
        $variant->optionValues()->sync([$valueS->id]);

        $this->actingAs($user)
            ->put(route('admin.products.variants.update', [$product, $variant]), [
                'option_value_ids' => [$valueM->id],
                'stock_quantity' => 20,
                'is_active' => true,
            ])
            ->assertRedirect(route('admin.products.show', $product));

        $this->assertDatabaseHas('product_variants', [
            'id' => $variant->id,
            'stock_quantity' => 20,
        ]);

        $variant->refresh();
        $variant->load('optionValues');
        $this->assertTrue($variant->optionValues->contains($valueM));
        $this->assertFalse($variant->optionValues->contains($valueS));
    }

    public function test_update_ignores_own_sku_uniqueness(): void
    {
        $user = User::factory()->superAdmin()->create();
        ['product' => $product, 'valueM' => $valueM] = $this->productWithOptions();
        $variant = ProductVariant::factory()->for($product)->create(['sku' => 'MY-SKU']);

        $this->actingAs($user)
            ->put(route('admin.products.variants.update', [$product, $variant]), [
                'option_value_ids' => [$valueM->id],
                'stock_quantity' => 5,
                'sku' => 'MY-SKU',
            ])
            ->assertSessionDoesntHaveErrors('sku');
    }

    public function test_super_admin_can_delete_a_variant(): void
    {
        $user = User::factory()->superAdmin()->create();
        $product = Product::factory()->create();
        $variant = ProductVariant::factory()->for($product)->create();

        $this->actingAs($user)
            ->delete(route('admin.products.variants.destroy', [$product, $variant]))
            ->assertRedirect(route('admin.products.show', $product));

        $this->assertDatabaseMissing('product_variants', ['id' => $variant->id]);
    }

    public function test_variant_scoped_to_parent_product(): void
    {
        $user = User::factory()->superAdmin()->create();
        $productA = Product::factory()->create();
        $productB = Product::factory()->create();
        $variantOfB = ProductVariant::factory()->for($productB)->create();

        $this->actingAs($user)
            ->get(route('admin.products.variants.edit', [$productA, $variantOfB]))
            ->assertNotFound();
    }
}
