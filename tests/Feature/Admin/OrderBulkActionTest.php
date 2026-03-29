<?php

namespace Tests\Feature\Admin;

use App\Mail\OrderStatusChangedMail;
use App\Models\Order;
use App\Models\User;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class OrderBulkActionTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RoleAndPermissionSeeder::class);
    }

    private function superAdmin(): User
    {
        return User::factory()->superAdmin()->create();
    }

    // ── Auth & authorization ──────────────────────────────────────────────────

    public function test_guests_cannot_perform_bulk_actions(): void
    {
        $orders = Order::factory(2)->create();

        $this->post(route('admin.orders.bulk'), [
            'order_ids' => $orders->pluck('id')->toArray(),
            'action' => 'update_status',
            'status' => 'processing',
        ])->assertRedirect(route('login'));
    }

    public function test_customers_cannot_perform_bulk_actions(): void
    {
        $user = User::factory()->customer()->create();
        $orders = Order::factory(2)->create();

        $this->actingAs($user)
            ->post(route('admin.orders.bulk'), [
                'order_ids' => $orders->pluck('id')->toArray(),
                'action' => 'update_status',
                'status' => 'processing',
            ])->assertForbidden();
    }

    // ── Validation ────────────────────────────────────────────────────────────

    public function test_bulk_action_requires_order_ids(): void
    {
        $this->actingAs($this->superAdmin())
            ->post(route('admin.orders.bulk'), ['action' => 'update_status', 'status' => 'processing'])
            ->assertSessionHasErrors('order_ids');
    }

    public static function validationProvider(): array
    {
        return [
            'invalid action' => [['action' => 'invalid_action'], 'action'],
            'update_status missing status' => [['action' => 'update_status'], 'status'],
            'update_status invalid status value' => [['action' => 'update_status', 'status' => 'invalid_status'], 'status'],
        ];
    }

    #[DataProvider('validationProvider')]
    public function test_bulk_action_validation(array $payload, string $errorField): void
    {
        $order = Order::factory()->create();

        $this->actingAs($this->superAdmin())
            ->post(route('admin.orders.bulk'), array_merge(['order_ids' => [$order->id]], $payload))
            ->assertSessionHasErrors($errorField);
    }

    // ── Delete ────────────────────────────────────────────────────────────────

    public function test_super_admin_can_bulk_delete_orders(): void
    {
        $orders = Order::factory(3)->create();

        $this->actingAs($this->superAdmin())
            ->post(route('admin.orders.bulk'), [
                'order_ids' => $orders->pluck('id')->toArray(),
                'action' => 'delete',
            ])
            ->assertRedirect(route('admin.orders.index'))
            ->assertSessionHas('success');

        foreach ($orders as $order) {
            $this->assertModelMissing($order);
        }
    }

    public function test_bulk_delete_only_deletes_selected_orders(): void
    {
        $toDelete = Order::factory(2)->create();
        $toKeep = Order::factory()->create();

        $this->actingAs($this->superAdmin())
            ->post(route('admin.orders.bulk'), [
                'order_ids' => $toDelete->pluck('id')->toArray(),
                'action' => 'delete',
            ]);

        $this->assertModelExists($toKeep);
        foreach ($toDelete as $order) {
            $this->assertModelMissing($order);
        }
    }

    public function test_manager_cannot_bulk_delete_orders(): void
    {
        $user = User::factory()->manager()->create();
        $orders = Order::factory(2)->create();

        $this->actingAs($user)
            ->post(route('admin.orders.bulk'), [
                'order_ids' => $orders->pluck('id')->toArray(),
                'action' => 'delete',
            ])
            ->assertForbidden();

        foreach ($orders as $order) {
            $this->assertModelExists($order);
        }
    }

    // ── Update Status ─────────────────────────────────────────────────────────

    public function test_super_admin_can_bulk_update_status(): void
    {
        $orders = Order::factory(3)->pending()->create();

        $this->actingAs($this->superAdmin())
            ->post(route('admin.orders.bulk'), [
                'order_ids' => $orders->pluck('id')->toArray(),
                'action' => 'update_status',
                'status' => 'processing',
            ])
            ->assertRedirect(route('admin.orders.index'))
            ->assertSessionHas('success');

        foreach ($orders as $order) {
            $this->assertDatabaseHas('orders', ['id' => $order->id, 'status' => 'processing']);
        }
    }

    public function test_bulk_status_update_skips_invalid_transitions(): void
    {
        $pendingOrder = Order::factory()->pending()->create();
        $deliveredOrder = Order::factory()->delivered()->create();

        $this->actingAs($this->superAdmin())
            ->post(route('admin.orders.bulk'), [
                'order_ids' => [$pendingOrder->id, $deliveredOrder->id],
                'action' => 'update_status',
                'status' => 'processing',
            ])
            ->assertSessionHas('success');

        // Pending → Processing is valid
        $this->assertDatabaseHas('orders', ['id' => $pendingOrder->id, 'status' => 'processing']);
        // Delivered → Processing is invalid; status unchanged
        $this->assertDatabaseHas('orders', ['id' => $deliveredOrder->id, 'status' => 'delivered']);
    }

    public function test_bulk_status_update_reports_skipped_count_in_message(): void
    {
        $pendingOrder = Order::factory()->pending()->create();
        $cancelledOrder = Order::factory()->cancelled()->create();

        $this->actingAs($this->superAdmin())
            ->post(route('admin.orders.bulk'), [
                'order_ids' => [$pendingOrder->id, $cancelledOrder->id],
                'action' => 'update_status',
                'status' => 'processing',
            ])
            ->assertSessionHas('success', fn (string $msg) => str_contains($msg, 'skipped'));
    }

    public function test_bulk_status_update_only_updates_selected_orders(): void
    {
        $toUpdate = Order::factory()->pending()->create();
        $other = Order::factory()->pending()->create();

        $this->actingAs($this->superAdmin())
            ->post(route('admin.orders.bulk'), [
                'order_ids' => [$toUpdate->id],
                'action' => 'update_status',
                'status' => 'processing',
            ]);

        $this->assertDatabaseHas('orders', ['id' => $toUpdate->id, 'status' => 'processing']);
        $this->assertDatabaseHas('orders', ['id' => $other->id, 'status' => 'pending']);
    }

    // ── Email notifications ───────────────────────────────────────────────────

    public function test_bulk_status_update_sends_email_for_shipped_orders(): void
    {
        Mail::fake();

        $orders = Order::factory(2)->processing()->create();

        $this->actingAs($this->superAdmin())
            ->post(route('admin.orders.bulk'), [
                'order_ids' => $orders->pluck('id')->toArray(),
                'action' => 'update_status',
                'status' => 'shipped',
            ]);

        Mail::assertQueued(OrderStatusChangedMail::class, 2);
    }

    public function test_bulk_status_update_sends_email_for_cancelled_orders(): void
    {
        Mail::fake();

        $orders = Order::factory(2)->pending()->create();

        $this->actingAs($this->superAdmin())
            ->post(route('admin.orders.bulk'), [
                'order_ids' => $orders->pluck('id')->toArray(),
                'action' => 'update_status',
                'status' => 'cancelled',
            ]);

        Mail::assertQueued(OrderStatusChangedMail::class, 2);
    }

    public function test_bulk_status_update_does_not_send_email_for_processing_status(): void
    {
        Mail::fake();

        $orders = Order::factory(2)->pending()->create();

        $this->actingAs($this->superAdmin())
            ->post(route('admin.orders.bulk'), [
                'order_ids' => $orders->pluck('id')->toArray(),
                'action' => 'update_status',
                'status' => 'processing',
            ]);

        Mail::assertNothingQueued();
    }

    // ── Manager role ──────────────────────────────────────────────────────────

    public function test_manager_can_bulk_update_order_status(): void
    {
        $user = User::factory()->manager()->create();
        $orders = Order::factory(2)->pending()->create();

        $this->actingAs($user)
            ->post(route('admin.orders.bulk'), [
                'order_ids' => $orders->pluck('id')->toArray(),
                'action' => 'update_status',
                'status' => 'processing',
            ])
            ->assertRedirect(route('admin.orders.index'));
    }

    // ── Success message ───────────────────────────────────────────────────────

    public function test_success_message_uses_plural_for_multiple_orders(): void
    {
        $orders = Order::factory(3)->pending()->create();

        $this->actingAs($this->superAdmin())
            ->post(route('admin.orders.bulk'), [
                'order_ids' => $orders->pluck('id')->toArray(),
                'action' => 'update_status',
                'status' => 'processing',
            ])
            ->assertSessionHas('success', '3 orders updated to "processing".');
    }

    public function test_success_message_uses_singular_for_one_order(): void
    {
        $order = Order::factory()->pending()->create();

        $this->actingAs($this->superAdmin())
            ->post(route('admin.orders.bulk'), [
                'order_ids' => [$order->id],
                'action' => 'update_status',
                'status' => 'processing',
            ])
            ->assertSessionHas('success', '1 order updated to "processing".');
    }
}
