<?php

namespace Tests\Feature\Admin;

use App\Models\StoreSettings;
use App\Models\User;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StoreSettingsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RoleAndPermissionSeeder::class);
    }

    public function test_guests_are_redirected_from_edit(): void
    {
        $this->get(route('admin.settings.edit'))
            ->assertRedirect(route('login'));
    }

    public function test_guests_are_redirected_from_update(): void
    {
        $this->put(route('admin.settings.update'), [])
            ->assertRedirect(route('login'));
    }

    public function test_super_admin_can_view_settings_page(): void
    {
        $user = User::factory()->superAdmin()->create();

        $this->actingAs($user)
            ->get(route('admin.settings.edit'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('admin/Settings/Edit')
                ->has('settings')
                ->has('hasPaymongoSecretKey')
            );
    }

    public function test_settings_are_created_as_singleton_on_first_access(): void
    {
        $this->assertDatabaseCount('store_settings', 0);

        StoreSettings::current();

        $this->assertDatabaseCount('store_settings', 1);
    }

    public function test_super_admin_can_update_settings(): void
    {
        $user = User::factory()->superAdmin()->create();

        $this->actingAs($user)
            ->put(route('admin.settings.update'), [
                'currency' => 'USD',
                'currency_locale' => 'en-US',
                'tax_rate' => 8.5,
                'active_payment_gateway' => 'cod',
                'low_stock_threshold' => 10,
                'sale_discount_percentage' => 0,
            ])
            ->assertRedirect(route('admin.settings.edit'))
            ->assertSessionHas('success');

        $this->assertDatabaseHas('store_settings', [
            'currency' => 'USD',
            'active_payment_gateway' => 'cod',
        ]);
    }

    public function test_update_rejects_invalid_gateway(): void
    {
        $user = User::factory()->superAdmin()->create();

        $this->actingAs($user)
            ->put(route('admin.settings.update'), [
                'currency' => 'USD',
                'currency_locale' => 'en-US',
                'tax_rate' => 10,
                'active_payment_gateway' => 'paypal',
                'low_stock_threshold' => 10,
                'sale_discount_percentage' => 0,
            ])
            ->assertSessionHasErrors('active_payment_gateway');
    }

    public function test_update_rejects_invalid_currency_code(): void
    {
        $user = User::factory()->superAdmin()->create();

        $this->actingAs($user)
            ->put(route('admin.settings.update'), [
                'currency' => 'INVALID',
                'currency_locale' => 'en-US',
                'tax_rate' => 10,
                'active_payment_gateway' => 'cod',
                'low_stock_threshold' => 10,
                'sale_discount_percentage' => 0,
            ])
            ->assertSessionHasErrors('currency');
    }

    public function test_update_rejects_tax_rate_over_100(): void
    {
        $user = User::factory()->superAdmin()->create();

        $this->actingAs($user)
            ->put(route('admin.settings.update'), [
                'currency' => 'USD',
                'currency_locale' => 'en-US',
                'tax_rate' => 150,
                'active_payment_gateway' => 'cod',
                'low_stock_threshold' => 10,
                'sale_discount_percentage' => 0,
            ])
            ->assertSessionHasErrors('tax_rate');
    }

    public function test_super_admin_can_update_low_stock_threshold(): void
    {
        $user = User::factory()->superAdmin()->create();

        $this->actingAs($user)
            ->put(route('admin.settings.update'), [
                'currency' => 'USD',
                'currency_locale' => 'en-US',
                'tax_rate' => 10,
                'active_payment_gateway' => 'cod',
                'low_stock_threshold' => 15,
                'sale_discount_percentage' => 0,
            ])
            ->assertRedirect(route('admin.settings.edit'));

        $this->assertDatabaseHas('store_settings', [
            'low_stock_threshold' => 15,
        ]);
    }

    public function test_update_rejects_negative_low_stock_threshold(): void
    {
        $user = User::factory()->superAdmin()->create();

        $this->actingAs($user)
            ->put(route('admin.settings.update'), [
                'currency' => 'USD',
                'currency_locale' => 'en-US',
                'tax_rate' => 10,
                'active_payment_gateway' => 'cod',
                'low_stock_threshold' => -1,
                'sale_discount_percentage' => 0,
            ])
            ->assertSessionHasErrors('low_stock_threshold');
    }

    public function test_update_rejects_low_stock_threshold_over_max(): void
    {
        $user = User::factory()->superAdmin()->create();

        $this->actingAs($user)
            ->put(route('admin.settings.update'), [
                'currency' => 'USD',
                'currency_locale' => 'en-US',
                'tax_rate' => 10,
                'active_payment_gateway' => 'cod',
                'low_stock_threshold' => 10001,
                'sale_discount_percentage' => 0,
            ])
            ->assertSessionHasErrors('low_stock_threshold');
    }

    public function test_settings_page_passes_low_stock_threshold_prop(): void
    {
        $user = User::factory()->superAdmin()->create();
        StoreSettings::current()->update(['low_stock_threshold' => 15]);

        $this->actingAs($user)
            ->get(route('admin.settings.edit'))
            ->assertInertia(fn ($page) => $page
                ->component('admin/Settings/Edit')
                ->where('settings.low_stock_threshold', 15)
            );
    }

    public function test_sale_discount_percentage_can_be_updated(): void
    {
        $user = User::factory()->superAdmin()->create();

        $this->actingAs($user)
            ->put(route('admin.settings.update'), [
                'currency' => 'CAD',
                'currency_locale' => 'en-CA',
                'tax_rate' => 5,
                'active_payment_gateway' => 'cod',
                'low_stock_threshold' => 5,
                'sale_discount_percentage' => 20,
            ])
            ->assertRedirect(route('admin.settings.edit'));

        $this->assertSame(20, StoreSettings::current()->fresh()->sale_discount_percentage);
    }

    public function test_sale_discount_percentage_must_be_between_0_and_100(): void
    {
        $user = User::factory()->superAdmin()->create();

        $this->actingAs($user)
            ->put(route('admin.settings.update'), [
                'currency' => 'CAD',
                'currency_locale' => 'en-CA',
                'tax_rate' => 5,
                'active_payment_gateway' => 'cod',
                'low_stock_threshold' => 5,
                'sale_discount_percentage' => 101,
            ])
            ->assertSessionHasErrors('sale_discount_percentage');

        $this->actingAs($user)
            ->put(route('admin.settings.update'), [
                'currency' => 'CAD',
                'currency_locale' => 'en-CA',
                'tax_rate' => 5,
                'active_payment_gateway' => 'cod',
                'low_stock_threshold' => 5,
                'sale_discount_percentage' => -1,
            ])
            ->assertSessionHasErrors('sale_discount_percentage');
    }

    public function test_settings_page_passes_sale_discount_percentage_prop(): void
    {
        $user = User::factory()->superAdmin()->create();
        StoreSettings::current()->update(['sale_discount_percentage' => 15]);

        $this->actingAs($user)
            ->get(route('admin.settings.edit'))
            ->assertInertia(fn ($page) => $page
                ->where('settings.sale_discount_percentage', 15)
            );
    }
}
