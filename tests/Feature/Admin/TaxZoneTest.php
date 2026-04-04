<?php

namespace Tests\Feature\Admin;

use App\Models\TaxZone;
use App\Models\TaxZoneRate;
use App\Models\User;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TaxZoneTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleAndPermissionSeeder::class);
    }

    // -------------------------------------------------------------------------
    // Zone CRUD — guest redirects
    // -------------------------------------------------------------------------

    public function test_guest_is_redirected_from_index(): void
    {
        $this->get(route('admin.tax-zones.index'))
            ->assertRedirect(route('login'));
    }

    public function test_guest_is_redirected_from_create(): void
    {
        $this->get(route('admin.tax-zones.create'))
            ->assertRedirect(route('login'));
    }

    public function test_guest_is_redirected_from_store(): void
    {
        $this->post(route('admin.tax-zones.store'), [])
            ->assertRedirect(route('login'));
    }

    public function test_guest_is_redirected_from_edit(): void
    {
        $zone = TaxZone::factory()->ontario()->create();

        $this->get(route('admin.tax-zones.edit', $zone))
            ->assertRedirect(route('login'));
    }

    public function test_guest_is_redirected_from_destroy(): void
    {
        $zone = TaxZone::factory()->ontario()->create();

        $this->delete(route('admin.tax-zones.destroy', $zone))
            ->assertRedirect(route('login'));
    }

    // -------------------------------------------------------------------------
    // Zone CRUD — admin happy paths
    // -------------------------------------------------------------------------

    public function test_admin_can_view_index(): void
    {
        $admin = User::factory()->admin()->create();
        TaxZone::factory(3)->create();

        $this->actingAs($admin)
            ->get(route('admin.tax-zones.index'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page->component('admin/TaxZones/Index'));
    }

    public function test_admin_can_create_zone(): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)
            ->post(route('admin.tax-zones.store'), [
                'name' => 'Ontario',
                'country_code' => 'CA',
                'province_code' => 'ON',
                'is_active' => true,
                'priority' => 10,
            ])
            ->assertRedirect(route('admin.tax-zones.index'))
            ->assertSessionHas('success');

        $this->assertDatabaseHas('tax_zones', [
            'name' => 'Ontario',
            'country_code' => 'CA',
            'province_code' => 'ON',
        ]);
    }

    public function test_admin_can_update_zone(): void
    {
        $admin = User::factory()->admin()->create();
        $zone = TaxZone::factory()->ontario()->create();

        $this->actingAs($admin)
            ->put(route('admin.tax-zones.update', $zone), [
                'name' => 'Ontario (Updated)',
                'country_code' => 'CA',
                'province_code' => 'ON',
                'is_active' => true,
                'priority' => 10,
            ])
            ->assertRedirect(route('admin.tax-zones.index'))
            ->assertSessionHas('success');

        $this->assertDatabaseHas('tax_zones', [
            'id' => $zone->id,
            'name' => 'Ontario (Updated)',
        ]);
    }

    public function test_admin_can_delete_zone(): void
    {
        $admin = User::factory()->admin()->create();
        $zone = TaxZone::factory()->ontario()->create();

        $this->actingAs($admin)
            ->delete(route('admin.tax-zones.destroy', $zone))
            ->assertRedirect(route('admin.tax-zones.index'))
            ->assertSessionHas('success');

        $this->assertDatabaseMissing('tax_zones', ['id' => $zone->id]);
    }

    public function test_cascade_delete_removes_rates(): void
    {
        $admin = User::factory()->admin()->create();
        $zone = TaxZone::factory()->ontario()->create();
        $rate = TaxZoneRate::factory()->hst()->for($zone, 'zone')->create();

        $this->actingAs($admin)
            ->delete(route('admin.tax-zones.destroy', $zone));

        $this->assertDatabaseMissing('tax_zone_rates', ['id' => $rate->id]);
    }

    // -------------------------------------------------------------------------
    // Zone CRUD — manager authorization
    // -------------------------------------------------------------------------

    public function test_manager_can_view_index(): void
    {
        $manager = User::factory()->manager()->create();

        $this->actingAs($manager)
            ->get(route('admin.tax-zones.index'))
            ->assertOk();
    }

    public function test_manager_gets_403_on_create(): void
    {
        $manager = User::factory()->manager()->create();

        $this->actingAs($manager)
            ->get(route('admin.tax-zones.create'))
            ->assertForbidden();
    }

    public function test_manager_gets_403_on_store(): void
    {
        $manager = User::factory()->manager()->create();

        $this->actingAs($manager)
            ->post(route('admin.tax-zones.store'), ['name' => 'Test'])
            ->assertForbidden();
    }

    public function test_manager_gets_403_on_update(): void
    {
        $manager = User::factory()->manager()->create();
        $zone = TaxZone::factory()->ontario()->create();

        $this->actingAs($manager)
            ->put(route('admin.tax-zones.update', $zone), ['name' => 'Updated'])
            ->assertForbidden();
    }

    public function test_manager_gets_403_on_destroy(): void
    {
        $manager = User::factory()->manager()->create();
        $zone = TaxZone::factory()->ontario()->create();

        $this->actingAs($manager)
            ->delete(route('admin.tax-zones.destroy', $zone))
            ->assertForbidden();
    }

    // -------------------------------------------------------------------------
    // Rate CRUD — guest redirects
    // -------------------------------------------------------------------------

    public function test_guest_cannot_store_rate(): void
    {
        $zone = TaxZone::factory()->ontario()->create();

        $this->post(route('admin.tax-zones.rates.store', $zone), [])
            ->assertRedirect(route('login'));
    }

    public function test_guest_cannot_update_rate(): void
    {
        $zone = TaxZone::factory()->ontario()->create();
        $rate = TaxZoneRate::factory()->hst()->for($zone, 'zone')->create();

        $this->put(route('admin.tax-zones.rates.update', [$zone, $rate]), [])
            ->assertRedirect(route('login'));
    }

    public function test_guest_cannot_destroy_rate(): void
    {
        $zone = TaxZone::factory()->ontario()->create();
        $rate = TaxZoneRate::factory()->hst()->for($zone, 'zone')->create();

        $this->delete(route('admin.tax-zones.rates.destroy', [$zone, $rate]))
            ->assertRedirect(route('login'));
    }

    // -------------------------------------------------------------------------
    // Rate CRUD — manager gets 403 on mutations
    // -------------------------------------------------------------------------

    public function test_manager_cannot_store_rate(): void
    {
        $manager = User::factory()->manager()->create();
        $zone = TaxZone::factory()->ontario()->create();

        $this->actingAs($manager)
            ->post(route('admin.tax-zones.rates.store', $zone), [
                'name' => 'HST',
                'rate' => 13.0,
            ])
            ->assertForbidden();
    }

    public function test_manager_cannot_update_rate(): void
    {
        $manager = User::factory()->manager()->create();
        $zone = TaxZone::factory()->ontario()->create();
        $rate = TaxZoneRate::factory()->hst()->for($zone, 'zone')->create();

        $this->actingAs($manager)
            ->put(route('admin.tax-zones.rates.update', [$zone, $rate]), [
                'name' => 'HST',
                'rate' => 14.0,
            ])
            ->assertForbidden();
    }

    public function test_manager_cannot_destroy_rate(): void
    {
        $manager = User::factory()->manager()->create();
        $zone = TaxZone::factory()->ontario()->create();
        $rate = TaxZoneRate::factory()->hst()->for($zone, 'zone')->create();

        $this->actingAs($manager)
            ->delete(route('admin.tax-zones.rates.destroy', [$zone, $rate]))
            ->assertForbidden();
    }

    // -------------------------------------------------------------------------
    // Rate CRUD — admin happy paths
    // -------------------------------------------------------------------------

    public function test_admin_can_store_rate_for_zone(): void
    {
        $admin = User::factory()->admin()->create();
        $zone = TaxZone::factory()->ontario()->create();

        $this->actingAs($admin)
            ->post(route('admin.tax-zones.rates.store', $zone), [
                'name' => 'HST',
                'name_fr' => null,
                'rate' => 13.0,
                'is_compound' => false,
                'sort_order' => 1,
            ])
            ->assertRedirect(route('admin.tax-zones.edit', $zone))
            ->assertSessionHas('success');

        $this->assertDatabaseHas('tax_zone_rates', [
            'tax_zone_id' => $zone->id,
            'name' => 'HST',
            'rate' => 13.0,
        ]);
    }

    public function test_admin_can_update_rate(): void
    {
        $admin = User::factory()->admin()->create();
        $zone = TaxZone::factory()->ontario()->create();
        $rate = TaxZoneRate::factory()->hst()->for($zone, 'zone')->create();

        $this->actingAs($admin)
            ->put(route('admin.tax-zones.rates.update', [$zone, $rate]), [
                'name' => 'HST',
                'rate' => 14.0,
                'is_compound' => false,
                'sort_order' => 1,
            ])
            ->assertRedirect(route('admin.tax-zones.edit', $zone))
            ->assertSessionHas('success');

        $this->assertDatabaseHas('tax_zone_rates', [
            'id' => $rate->id,
            'rate' => 14.0,
        ]);
    }

    public function test_admin_can_destroy_rate(): void
    {
        $admin = User::factory()->admin()->create();
        $zone = TaxZone::factory()->ontario()->create();
        $rate = TaxZoneRate::factory()->hst()->for($zone, 'zone')->create();

        $this->actingAs($admin)
            ->delete(route('admin.tax-zones.rates.destroy', [$zone, $rate]))
            ->assertRedirect(route('admin.tax-zones.edit', $zone))
            ->assertSessionHas('success');

        $this->assertDatabaseMissing('tax_zone_rates', ['id' => $rate->id]);
    }

    // -------------------------------------------------------------------------
    // Model / scope tests
    // -------------------------------------------------------------------------

    public function test_scope_active_excludes_inactive_zones(): void
    {
        TaxZone::factory()->create(['is_active' => true]);
        TaxZone::factory()->inactive()->create();

        $activeZones = TaxZone::query()->active()->get();

        $this->assertCount(1, $activeZones);
        $this->assertTrue($activeZones->first()->is_active);
    }
}
