<?php

namespace Tests\Feature\Admin;

use App\Models\ActivityLog;
use App\Models\Coupon;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ActivityLogTest extends TestCase
{
    use RefreshDatabase;

    public function test_guests_are_redirected_from_activity_log(): void
    {
        $this->get(route('admin.activity-log.index'))->assertRedirect(route('login'));
    }

    public function test_authenticated_users_can_view_activity_log(): void
    {
        $user = User::factory()->create();
        ActivityLog::factory(3)->create();

        $this->actingAs($user)
            ->get(route('admin.activity-log.index'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('admin/ActivityLog/Index')
                ->has('logs.data', 3)
            );
    }

    public function test_creating_a_product_logs_an_activity(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->post(route('admin.products.store'), [
                'name' => 'Logged Product',
                'price' => 1000,
            ]);

        $this->assertDatabaseHas('activity_logs', [
            'action' => 'created',
            'subject_type' => 'Product',
            'user_id' => $user->id,
        ]);

        $log = ActivityLog::query()->where('action', 'created')->where('subject_type', 'Product')->first();
        $this->assertStringContainsString('Logged Product', $log->description);
    }

    public function test_updating_a_product_logs_an_activity(): void
    {
        $user = User::factory()->create();
        $product = Product::factory()->create(['name' => 'Old Name', 'price' => 1000]);

        // Clear any creation log
        ActivityLog::query()->delete();

        $this->actingAs($user)
            ->put(route('admin.products.update', $product), [
                'name' => 'New Name',
                'price' => 2000,
            ]);

        $this->assertDatabaseHas('activity_logs', [
            'action' => 'updated',
            'subject_type' => 'Product',
            'subject_id' => $product->id,
            'user_id' => $user->id,
        ]);
    }

    public function test_deleting_a_product_logs_an_activity(): void
    {
        $user = User::factory()->create();
        $product = Product::factory()->create(['name' => 'To Delete', 'price' => 1000]);

        ActivityLog::query()->delete();

        $this->actingAs($user)
            ->delete(route('admin.products.destroy', $product));

        $this->assertDatabaseHas('activity_logs', [
            'action' => 'deleted',
            'subject_type' => 'Product',
            'subject_id' => $product->id,
            'user_id' => $user->id,
        ]);
    }

    public function test_creating_an_order_logs_exactly_one_entry_with_order_number(): void
    {
        $user = User::factory()->create();
        $order = Order::factory()->create();

        $logs = ActivityLog::query()
            ->where('subject_type', 'Order')
            ->where('subject_id', $order->id)
            ->get();

        $this->assertCount(1, $logs);
        $this->assertEquals('created', $logs->first()->action);
        $this->assertStringContainsString('ORD-', $logs->first()->description);
    }

    public function test_updating_order_status_logs_an_activity(): void
    {
        $user = User::factory()->create();
        $order = Order::factory()->create();

        ActivityLog::query()->delete();

        $this->actingAs($user)
            ->put(route('admin.orders.update', $order), [
                'status' => 'processing',
            ]);

        $log = ActivityLog::query()
            ->where('action', 'updated')
            ->where('subject_type', 'Order')
            ->first();

        $this->assertNotNull($log);
        $this->assertStringContainsString('Processing', $log->description);
    }

    public function test_creating_a_coupon_logs_an_activity(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->post(route('admin.coupons.store'), [
                'code' => 'TESTLOG10',
                'type' => 'percentage',
                'value' => 10,
                'is_active' => true,
            ]);

        $this->assertDatabaseHas('activity_logs', [
            'action' => 'created',
            'subject_type' => 'Coupon',
            'user_id' => $user->id,
        ]);

        $log = ActivityLog::query()->where('action', 'created')->where('subject_type', 'Coupon')->first();
        $this->assertStringContainsString('TESTLOG10', $log->description);
    }

    public function test_deleting_a_coupon_logs_an_activity(): void
    {
        $user = User::factory()->create();
        $coupon = Coupon::factory()->create(['code' => 'DELME20']);

        ActivityLog::query()->delete();

        $this->actingAs($user)
            ->delete(route('admin.coupons.destroy', $coupon));

        $this->assertDatabaseHas('activity_logs', [
            'action' => 'deleted',
            'subject_type' => 'Coupon',
            'subject_id' => $coupon->id,
        ]);
    }

    public function test_activity_log_shows_user_name(): void
    {
        $user = User::factory()->create(['name' => 'Alice Admin']);
        ActivityLog::factory()->create(['user_id' => $user->id]);

        $this->actingAs($user)
            ->get(route('admin.activity-log.index'))
            ->assertInertia(fn ($page) => $page
                ->has('logs.data', 1)
                ->where('logs.data.0.user.name', 'Alice Admin')
            );
    }

    public function test_activity_log_is_paginated(): void
    {
        $user = User::factory()->create();
        ActivityLog::factory(55)->create();

        $this->actingAs($user)
            ->get(route('admin.activity-log.index'))
            ->assertInertia(fn ($page) => $page
                ->where('logs.total', 55)
                ->has('logs.data', 50)
            );
    }
}
