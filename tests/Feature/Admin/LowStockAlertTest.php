<?php

namespace Tests\Feature\Admin;

use App\Models\Product;
use App\Models\StoreSettings;
use App\Models\User;
use App\Notifications\LowStockAlert;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class LowStockAlertTest extends TestCase
{
    use RefreshDatabase;

    public function test_notification_is_sent_when_stock_drops_below_threshold(): void
    {
        Notification::fake();

        $user = User::factory()->create();
        StoreSettings::current()->update(['low_stock_threshold' => 10]);

        $product = Product::factory()->create(['stock_quantity' => 20]);

        $this->actingAs($user)
            ->put(route('admin.products.update', $product), [
                'name' => $product->name,
                'price' => $product->price,
                'stock_quantity' => 5,
            ]);

        Notification::assertSentTo($user, LowStockAlert::class, function (LowStockAlert $notification) use ($product) {
            return $notification->product->id === $product->id;
        });
    }

    public function test_notification_is_not_sent_when_stock_is_above_threshold(): void
    {
        Notification::fake();

        $user = User::factory()->create();
        StoreSettings::current()->update(['low_stock_threshold' => 10]);

        $product = Product::factory()->create(['stock_quantity' => 50]);

        $this->actingAs($user)
            ->put(route('admin.products.update', $product), [
                'name' => $product->name,
                'price' => $product->price,
                'stock_quantity' => 30,
            ]);

        Notification::assertNothingSent();
    }

    public function test_notification_is_sent_only_once_until_restocked(): void
    {
        Notification::fake();

        $user = User::factory()->create();
        StoreSettings::current()->update(['low_stock_threshold' => 10]);

        $product = Product::factory()->create(['stock_quantity' => 20]);

        // First drop below threshold
        $this->actingAs($user)
            ->put(route('admin.products.update', $product), [
                'name' => $product->name,
                'price' => $product->price,
                'stock_quantity' => 5,
            ]);

        // Second drop (already below, already notified)
        $this->actingAs($user)
            ->put(route('admin.products.update', $product->fresh()), [
                'name' => $product->name,
                'price' => $product->price,
                'stock_quantity' => 3,
            ]);

        Notification::assertSentToTimes($user, LowStockAlert::class, 1);
    }

    public function test_low_stock_notified_flag_resets_when_restocked_above_threshold(): void
    {
        Notification::fake();

        $user = User::factory()->create();
        StoreSettings::current()->update(['low_stock_threshold' => 10]);

        $product = Product::factory()->create(['stock_quantity' => 20]);

        // Drop below threshold
        $this->actingAs($user)
            ->put(route('admin.products.update', $product), [
                'name' => $product->name,
                'price' => $product->price,
                'stock_quantity' => 5,
            ]);

        $this->assertTrue($product->fresh()->low_stock_notified);

        // Restock above threshold
        $this->actingAs($user)
            ->put(route('admin.products.update', $product->fresh()), [
                'name' => $product->name,
                'price' => $product->price,
                'stock_quantity' => 25,
            ]);

        $this->assertFalse($product->fresh()->low_stock_notified);
    }

    public function test_notification_is_sent_again_after_restock_and_redrop(): void
    {
        Notification::fake();

        $user = User::factory()->create();
        StoreSettings::current()->update(['low_stock_threshold' => 10]);

        $product = Product::factory()->create(['stock_quantity' => 20]);

        // First drop
        $this->actingAs($user)
            ->put(route('admin.products.update', $product), [
                'name' => $product->name,
                'price' => $product->price,
                'stock_quantity' => 5,
            ]);

        // Restock
        $this->actingAs($user)
            ->put(route('admin.products.update', $product->fresh()), [
                'name' => $product->name,
                'price' => $product->price,
                'stock_quantity' => 25,
            ]);

        // Second drop
        $this->actingAs($user)
            ->put(route('admin.products.update', $product->fresh()), [
                'name' => $product->name,
                'price' => $product->price,
                'stock_quantity' => 3,
            ]);

        Notification::assertSentToTimes($user, LowStockAlert::class, 2);
    }

    public function test_notification_is_not_sent_when_stock_field_unchanged(): void
    {
        Notification::fake();

        $user = User::factory()->create();
        StoreSettings::current()->update(['low_stock_threshold' => 10]);

        $product = Product::factory()->create(['stock_quantity' => 5]);

        // Update name only, stock unchanged
        $this->actingAs($user)
            ->put(route('admin.products.update', $product), [
                'name' => 'New Name',
                'price' => $product->price,
                'stock_quantity' => 5,
            ]);

        Notification::assertNothingSent();
    }

    public function test_notification_is_sent_when_stock_equals_threshold(): void
    {
        Notification::fake();

        $user = User::factory()->create();
        StoreSettings::current()->update(['low_stock_threshold' => 10]);

        $product = Product::factory()->create(['stock_quantity' => 20]);

        $this->actingAs($user)
            ->put(route('admin.products.update', $product), [
                'name' => $product->name,
                'price' => $product->price,
                'stock_quantity' => 10,
            ]);

        Notification::assertSentTo($user, LowStockAlert::class);
    }

    public function test_notification_is_sent_to_all_users(): void
    {
        Notification::fake();

        $users = User::factory(3)->create();
        StoreSettings::current()->update(['low_stock_threshold' => 10]);

        $product = Product::factory()->create(['stock_quantity' => 20]);

        $this->actingAs($users->first())
            ->put(route('admin.products.update', $product), [
                'name' => $product->name,
                'price' => $product->price,
                'stock_quantity' => 5,
            ]);

        foreach ($users as $user) {
            Notification::assertSentTo($user, LowStockAlert::class);
        }
    }
}
