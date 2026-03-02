<?php

namespace Tests\Feature\Admin;

use App\Models\User;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class UserTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RoleAndPermissionSeeder::class);
    }

    // ── Guest redirects ──────────────────────────────────────────

    public function test_guests_are_redirected_from_index(): void
    {
        $this->get(route('admin.users.index'))->assertRedirect(route('login'));
    }

    public function test_guests_are_redirected_from_create(): void
    {
        $this->get(route('admin.users.create'))->assertRedirect(route('login'));
    }

    public function test_guests_are_redirected_from_store(): void
    {
        $this->post(route('admin.users.store'), [])->assertRedirect(route('login'));
    }

    public function test_guests_are_redirected_from_edit(): void
    {
        $user = User::factory()->admin()->create();
        $this->get(route('admin.users.edit', $user))->assertRedirect(route('login'));
    }

    public function test_guests_are_redirected_from_update(): void
    {
        $user = User::factory()->admin()->create();
        $this->put(route('admin.users.update', $user), [])->assertRedirect(route('login'));
    }

    public function test_guests_are_redirected_from_destroy(): void
    {
        $user = User::factory()->admin()->create();
        $this->delete(route('admin.users.destroy', $user))->assertRedirect(route('login'));
    }

    // ── Authorization ────────────────────────────────────────────

    public function test_manager_cannot_access_users_index(): void
    {
        $user = User::factory()->manager()->create();

        $this->actingAs($user)
            ->get(route('admin.users.index'))
            ->assertForbidden();
    }

    public function test_manager_cannot_create_users(): void
    {
        $user = User::factory()->manager()->create();

        $this->actingAs($user)
            ->get(route('admin.users.create'))
            ->assertForbidden();

        $this->actingAs($user)
            ->post(route('admin.users.store'), [
                'name' => 'Test',
                'email' => 'test@example.com',
                'password' => 'password',
                'password_confirmation' => 'password',
                'role' => 'admin',
            ])
            ->assertForbidden();
    }

    // ── Happy path: Index ────────────────────────────────────────

    public function test_super_admin_can_view_users_index(): void
    {
        $user = User::factory()->superAdmin()->create();
        User::factory(2)->admin()->create();

        $this->actingAs($user)
            ->get(route('admin.users.index'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('admin/Users/Index')
                ->has('users.data', 3)
            );
    }

    public function test_index_only_shows_staff_users(): void
    {
        $admin = User::factory()->superAdmin()->create();
        User::factory(2)->admin()->create();
        // Create a user without any role (a customer-type user)
        User::factory()->create();

        $this->actingAs($admin)
            ->get(route('admin.users.index'))
            ->assertInertia(fn ($page) => $page
                ->has('users.data', 3) // 1 super-admin + 2 admins, NOT the roleless user
            );
    }

    // ── Happy path: Create ───────────────────────────────────────

    public function test_super_admin_can_view_create_form(): void
    {
        $user = User::factory()->superAdmin()->create();

        $this->actingAs($user)
            ->get(route('admin.users.create'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('admin/Users/Create')
                ->where('roles', ['super-admin', 'admin', 'manager'])
            );
    }

    public function test_admin_sees_only_admin_and_manager_roles_on_create(): void
    {
        $user = User::factory()->admin()->create();

        $this->actingAs($user)
            ->get(route('admin.users.create'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->where('roles', ['admin', 'manager'])
            );
    }

    public function test_super_admin_can_store_a_user(): void
    {
        $admin = User::factory()->superAdmin()->create();

        $this->actingAs($admin)
            ->post(route('admin.users.store'), [
                'name' => 'New Staff',
                'email' => 'newstaff@example.com',
                'password' => 'password123',
                'password_confirmation' => 'password123',
                'role' => 'admin',
            ])
            ->assertRedirect(route('admin.users.index'));

        $this->assertDatabaseHas('users', [
            'name' => 'New Staff',
            'email' => 'newstaff@example.com',
        ]);

        $newUser = User::query()->where('email', 'newstaff@example.com')->first();
        $this->assertTrue($newUser->hasRole('admin'));
        $this->assertNotNull($newUser->email_verified_at);
    }

    // ── Happy path: Edit / Update ────────────────────────────────

    public function test_super_admin_can_view_edit_form(): void
    {
        $admin = User::factory()->superAdmin()->create();
        $target = User::factory()->admin()->create();

        $this->actingAs($admin)
            ->get(route('admin.users.edit', $target))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('admin/Users/Edit')
                ->has('user')
                ->where('currentRole', 'admin')
                ->where('roles', ['super-admin', 'admin', 'manager'])
            );
    }

    public function test_super_admin_can_update_a_user(): void
    {
        $admin = User::factory()->superAdmin()->create();
        $target = User::factory()->admin()->create();

        $this->actingAs($admin)
            ->put(route('admin.users.update', $target), [
                'name' => 'Updated Name',
                'email' => 'updated@example.com',
                'role' => 'manager',
            ])
            ->assertRedirect(route('admin.users.index'));

        $target->refresh();
        $this->assertEquals('Updated Name', $target->name);
        $this->assertEquals('updated@example.com', $target->email);
        $this->assertTrue($target->hasRole('manager'));
    }

    public function test_update_without_password_keeps_existing_password(): void
    {
        $admin = User::factory()->superAdmin()->create();
        $target = User::factory()->admin()->create(['password' => 'original-password']);
        $originalHash = $target->password;

        $this->actingAs($admin)
            ->put(route('admin.users.update', $target), [
                'name' => $target->name,
                'email' => $target->email,
                'role' => 'admin',
            ])
            ->assertRedirect();

        $this->assertEquals($originalHash, $target->fresh()->password);
    }

    public function test_update_with_password_changes_password(): void
    {
        $admin = User::factory()->superAdmin()->create();
        $target = User::factory()->admin()->create(['password' => 'original-password']);
        $originalHash = $target->password;

        $this->actingAs($admin)
            ->put(route('admin.users.update', $target), [
                'name' => $target->name,
                'email' => $target->email,
                'password' => 'new-password-123',
                'password_confirmation' => 'new-password-123',
                'role' => 'admin',
            ])
            ->assertRedirect();

        $this->assertNotEquals($originalHash, $target->fresh()->password);
        $this->assertTrue(Hash::check('new-password-123', $target->fresh()->password));
    }

    // ── Happy path: Destroy ──────────────────────────────────────

    public function test_super_admin_can_delete_a_user(): void
    {
        $admin = User::factory()->superAdmin()->create();
        $target = User::factory()->admin()->create();

        $this->actingAs($admin)
            ->delete(route('admin.users.destroy', $target))
            ->assertRedirect(route('admin.users.index'));

        $this->assertDatabaseMissing('users', ['id' => $target->id]);
    }

    // ── Validation: Store ────────────────────────────────────────

    public function test_store_requires_name(): void
    {
        $admin = User::factory()->superAdmin()->create();

        $this->actingAs($admin)
            ->post(route('admin.users.store'), [
                'email' => 'test@example.com',
                'password' => 'password123',
                'password_confirmation' => 'password123',
                'role' => 'admin',
            ])
            ->assertSessionHasErrors('name');
    }

    public function test_store_requires_email(): void
    {
        $admin = User::factory()->superAdmin()->create();

        $this->actingAs($admin)
            ->post(route('admin.users.store'), [
                'name' => 'Test',
                'password' => 'password123',
                'password_confirmation' => 'password123',
                'role' => 'admin',
            ])
            ->assertSessionHasErrors('email');
    }

    public function test_store_requires_unique_email(): void
    {
        $admin = User::factory()->superAdmin()->create();
        User::factory()->create(['email' => 'taken@example.com']);

        $this->actingAs($admin)
            ->post(route('admin.users.store'), [
                'name' => 'Test',
                'email' => 'taken@example.com',
                'password' => 'password123',
                'password_confirmation' => 'password123',
                'role' => 'admin',
            ])
            ->assertSessionHasErrors('email');
    }

    public function test_store_requires_password(): void
    {
        $admin = User::factory()->superAdmin()->create();

        $this->actingAs($admin)
            ->post(route('admin.users.store'), [
                'name' => 'Test',
                'email' => 'test@example.com',
                'role' => 'admin',
            ])
            ->assertSessionHasErrors('password');
    }

    public function test_store_requires_password_confirmation(): void
    {
        $admin = User::factory()->superAdmin()->create();

        $this->actingAs($admin)
            ->post(route('admin.users.store'), [
                'name' => 'Test',
                'email' => 'test@example.com',
                'password' => 'password123',
                'role' => 'admin',
            ])
            ->assertSessionHasErrors('password');
    }

    public function test_store_requires_valid_role(): void
    {
        $admin = User::factory()->superAdmin()->create();

        $this->actingAs($admin)
            ->post(route('admin.users.store'), [
                'name' => 'Test',
                'email' => 'test@example.com',
                'password' => 'password123',
                'password_confirmation' => 'password123',
                'role' => 'invalid-role',
            ])
            ->assertSessionHasErrors('role');
    }

    // ── Validation: Update ───────────────────────────────────────

    public function test_update_ignores_own_email_uniqueness(): void
    {
        $admin = User::factory()->superAdmin()->create();
        $target = User::factory()->admin()->create();

        $this->actingAs($admin)
            ->put(route('admin.users.update', $target), [
                'name' => $target->name,
                'email' => $target->email,
                'role' => 'admin',
            ])
            ->assertSessionDoesntHaveErrors('email');
    }

    // ── Security: Super-admin role assignment ────────────────────

    public function test_admin_cannot_assign_super_admin_role_on_store(): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)
            ->post(route('admin.users.store'), [
                'name' => 'Escalation Attempt',
                'email' => 'escalate@example.com',
                'password' => 'password123',
                'password_confirmation' => 'password123',
                'role' => 'super-admin',
            ])
            ->assertSessionHasErrors('role');
    }

    public function test_admin_cannot_assign_super_admin_role_on_update(): void
    {
        $admin = User::factory()->admin()->create();
        $target = User::factory()->manager()->create();

        $this->actingAs($admin)
            ->put(route('admin.users.update', $target), [
                'name' => $target->name,
                'email' => $target->email,
                'role' => 'super-admin',
            ])
            ->assertSessionHasErrors('role');
    }

    public function test_super_admin_can_assign_super_admin_role(): void
    {
        $admin = User::factory()->superAdmin()->create();

        $this->actingAs($admin)
            ->post(route('admin.users.store'), [
                'name' => 'New Super Admin',
                'email' => 'newsuper@example.com',
                'password' => 'password123',
                'password_confirmation' => 'password123',
                'role' => 'super-admin',
            ])
            ->assertRedirect(route('admin.users.index'));

        $newUser = User::query()->where('email', 'newsuper@example.com')->first();
        $this->assertTrue($newUser->hasRole('super-admin'));
    }

    // ── Security: Self-deletion ──────────────────────────────────

    public function test_user_cannot_delete_themselves(): void
    {
        $admin = User::factory()->superAdmin()->create();

        $this->actingAs($admin)
            ->delete(route('admin.users.destroy', $admin))
            ->assertForbidden();

        $this->assertDatabaseHas('users', ['id' => $admin->id]);
    }

    // ── Security: Self role-change ───────────────────────────────

    public function test_user_cannot_change_own_role(): void
    {
        $admin = User::factory()->superAdmin()->create();

        $this->actingAs($admin)
            ->put(route('admin.users.update', $admin), [
                'name' => $admin->name,
                'email' => $admin->email,
                'role' => 'manager',
            ])
            ->assertSessionHasErrors('role');
    }

    // ── Security: Admin cannot delete super-admin ────────────────

    public function test_admin_cannot_delete_super_admin(): void
    {
        $admin = User::factory()->admin()->create();
        $superAdmin = User::factory()->superAdmin()->create();

        $this->actingAs($admin)
            ->delete(route('admin.users.destroy', $superAdmin))
            ->assertForbidden();

        $this->assertDatabaseHas('users', ['id' => $superAdmin->id]);
    }

    // ── Route: show does not exist ───────────────────────────────

    public function test_show_route_name_does_not_exist(): void
    {
        $this->assertFalse(route('admin.users.index') === null);
        $this->expectException(\Symfony\Component\Routing\Exception\RouteNotFoundException::class);
        route('admin.users.show', 1);
    }
}
