<?php

namespace Tests\Feature\Admin;

use App\Enums\CouponType;
use App\Models\Coupon;
use App\Models\User;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CouponTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RoleAndPermissionSeeder::class);
    }

    public function test_guests_are_redirected_from_index(): void
    {
        $this->get(route('admin.coupons.index'))
            ->assertRedirect(route('login'));
    }

    public function test_guests_are_redirected_from_create(): void
    {
        $this->get(route('admin.coupons.create'))
            ->assertRedirect(route('login'));
    }

    public function test_guests_are_redirected_from_store(): void
    {
        $this->post(route('admin.coupons.store'), [])
            ->assertRedirect(route('login'));
    }

    public function test_guests_are_redirected_from_edit(): void
    {
        $coupon = Coupon::factory()->create();

        $this->get(route('admin.coupons.edit', $coupon))
            ->assertRedirect(route('login'));
    }

    public function test_guests_are_redirected_from_update(): void
    {
        $coupon = Coupon::factory()->create();

        $this->put(route('admin.coupons.update', $coupon), [])
            ->assertRedirect(route('login'));
    }

    public function test_guests_are_redirected_from_destroy(): void
    {
        $coupon = Coupon::factory()->create();

        $this->delete(route('admin.coupons.destroy', $coupon))
            ->assertRedirect(route('login'));
    }

    public function test_authenticated_users_can_view_coupons_index(): void
    {
        $user = User::factory()->superAdmin()->create();
        Coupon::factory(3)->create();

        $this->actingAs($user)
            ->get(route('admin.coupons.index'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('admin/Coupons/Index')
                ->has('coupons.data', 3)
            );
    }

    public function test_authenticated_users_can_view_create_coupon_form(): void
    {
        $user = User::factory()->superAdmin()->create();

        $this->actingAs($user)
            ->get(route('admin.coupons.create'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page->component('admin/Coupons/Create'));
    }

    public function test_authenticated_users_can_store_a_coupon(): void
    {
        $user = User::factory()->superAdmin()->create();

        $this->actingAs($user)
            ->post(route('admin.coupons.store'), [
                'code' => 'save10',
                'type' => 'percentage',
                'value' => 10,
                'is_active' => true,
            ])
            ->assertRedirect(route('admin.coupons.index'))
            ->assertSessionHas('success');

        $this->assertDatabaseHas('coupons', [
            'code' => 'SAVE10',
            'type' => 'percentage',
            'value' => 10,
        ]);
    }

    public function test_store_uppercases_coupon_code(): void
    {
        $user = User::factory()->superAdmin()->create();

        $this->actingAs($user)
            ->post(route('admin.coupons.store'), [
                'code' => 'lowercase',
                'type' => 'fixed',
                'value' => 500,
            ]);

        $this->assertDatabaseHas('coupons', ['code' => 'LOWERCASE']);
    }

    public function test_store_requires_code(): void
    {
        $user = User::factory()->superAdmin()->create();

        $this->actingAs($user)
            ->post(route('admin.coupons.store'), ['type' => 'fixed', 'value' => 100])
            ->assertSessionHasErrors('code');
    }

    public function test_store_rejects_duplicate_code(): void
    {
        $user = User::factory()->superAdmin()->create();
        Coupon::factory()->create(['code' => 'DUPE']);

        $this->actingAs($user)
            ->post(route('admin.coupons.store'), [
                'code' => 'DUPE',
                'type' => 'fixed',
                'value' => 100,
            ])
            ->assertSessionHasErrors('code');
    }

    public function test_store_requires_valid_type(): void
    {
        $user = User::factory()->superAdmin()->create();

        $this->actingAs($user)
            ->post(route('admin.coupons.store'), [
                'code' => 'TEST1',
                'type' => 'invalid',
                'value' => 10,
            ])
            ->assertSessionHasErrors('type');
    }

    public function test_store_requires_value_of_at_least_one(): void
    {
        $user = User::factory()->superAdmin()->create();

        $this->actingAs($user)
            ->post(route('admin.coupons.store'), [
                'code' => 'TEST2',
                'type' => 'percentage',
                'value' => 0,
            ])
            ->assertSessionHasErrors('value');
    }

    public function test_authenticated_users_can_view_edit_coupon_form(): void
    {
        $user = User::factory()->superAdmin()->create();
        $coupon = Coupon::factory()->create();

        $this->actingAs($user)
            ->get(route('admin.coupons.edit', $coupon))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('admin/Coupons/Edit')
                ->has('coupon')
            );
    }

    public function test_authenticated_users_can_update_a_coupon(): void
    {
        $user = User::factory()->superAdmin()->create();
        $coupon = Coupon::factory()->create(['code' => 'OLD10', 'type' => CouponType::Percentage, 'value' => 10]);

        $this->actingAs($user)
            ->put(route('admin.coupons.update', $coupon), [
                'code' => 'NEW20',
                'type' => 'percentage',
                'value' => 20,
                'is_active' => true,
            ])
            ->assertRedirect(route('admin.coupons.index'))
            ->assertSessionHas('success');

        $this->assertDatabaseHas('coupons', [
            'id' => $coupon->id,
            'code' => 'NEW20',
            'value' => 20,
        ]);
    }

    public function test_update_ignores_own_code_uniqueness(): void
    {
        $user = User::factory()->superAdmin()->create();
        $coupon = Coupon::factory()->create(['code' => 'MYCODE']);

        $this->actingAs($user)
            ->put(route('admin.coupons.update', $coupon), [
                'code' => 'MYCODE',
                'type' => 'fixed',
                'value' => 100,
            ])
            ->assertSessionDoesntHaveErrors('code');
    }

    public function test_authenticated_users_can_delete_a_coupon(): void
    {
        $user = User::factory()->superAdmin()->create();
        $coupon = Coupon::factory()->create();

        $this->actingAs($user)
            ->delete(route('admin.coupons.destroy', $coupon))
            ->assertRedirect(route('admin.coupons.index'))
            ->assertSessionHas('success');

        $this->assertDatabaseMissing('coupons', ['id' => $coupon->id]);
    }

    public function test_show_route_name_does_not_exist(): void
    {
        $this->assertFalse(
            collect(\Illuminate\Support\Facades\Route::getRoutes()->getRoutesByName())
                ->has('admin.coupons.show')
        );
    }
}
