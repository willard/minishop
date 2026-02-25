<?php

namespace Tests\Feature\Admin;

use App\Models\StoreSettings;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StoreSettingsTest extends TestCase
{
    use RefreshDatabase;

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

    public function test_authenticated_users_can_view_settings_page(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get(route('admin.settings.edit'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('admin/Settings/Edit')
                ->has('settings')
                ->has('hasStripeSecretKey')
                ->has('hasPaymongoSecretKey')
            );
    }

    public function test_settings_are_created_as_singleton_on_first_access(): void
    {
        $this->assertDatabaseCount('store_settings', 0);

        StoreSettings::current();

        $this->assertDatabaseCount('store_settings', 1);
    }

    public function test_authenticated_users_can_update_settings(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->put(route('admin.settings.update'), [
                'currency' => 'USD',
                'currency_locale' => 'en-US',
                'tax_rate' => 8.5,
                'active_payment_gateway' => 'cod',
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
        $user = User::factory()->create();

        $this->actingAs($user)
            ->put(route('admin.settings.update'), [
                'currency' => 'USD',
                'currency_locale' => 'en-US',
                'tax_rate' => 10,
                'active_payment_gateway' => 'paypal',
            ])
            ->assertSessionHasErrors('active_payment_gateway');
    }

    public function test_update_rejects_invalid_currency_code(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->put(route('admin.settings.update'), [
                'currency' => 'INVALID',
                'currency_locale' => 'en-US',
                'tax_rate' => 10,
                'active_payment_gateway' => 'cod',
            ])
            ->assertSessionHasErrors('currency');
    }

    public function test_update_rejects_tax_rate_over_100(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->put(route('admin.settings.update'), [
                'currency' => 'USD',
                'currency_locale' => 'en-US',
                'tax_rate' => 150,
                'active_payment_gateway' => 'cod',
            ])
            ->assertSessionHasErrors('tax_rate');
    }

    public function test_masked_secret_key_is_not_overwritten(): void
    {
        $user = User::factory()->create();
        $settings = StoreSettings::current();
        $settings->update(['stripe_secret_key' => 'sk_live_original_secret']);

        $this->actingAs($user)
            ->put(route('admin.settings.update'), [
                'currency' => 'USD',
                'currency_locale' => 'en-US',
                'tax_rate' => 10,
                'active_payment_gateway' => 'stripe',
                'stripe_secret_key' => '••••••••',
            ]);

        $this->assertDatabaseHas('store_settings', [
            'id' => $settings->id,
        ]);

        // Re-fetch and decrypt to verify the original key wasn't overwritten
        $updated = StoreSettings::find($settings->id);
        $this->assertSame('sk_live_original_secret', $updated->stripe_secret_key);
    }

    public function test_new_secret_key_replaces_existing(): void
    {
        $user = User::factory()->create();
        $settings = StoreSettings::current();
        $settings->update(['stripe_secret_key' => 'sk_live_old_secret']);

        $this->actingAs($user)
            ->put(route('admin.settings.update'), [
                'currency' => 'USD',
                'currency_locale' => 'en-US',
                'tax_rate' => 10,
                'active_payment_gateway' => 'stripe',
                'stripe_secret_key' => 'sk_live_new_secret',
            ]);

        $updated = StoreSettings::find($settings->id);
        $this->assertSame('sk_live_new_secret', $updated->stripe_secret_key);
    }
}
