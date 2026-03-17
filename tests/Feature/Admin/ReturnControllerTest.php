<?php

namespace Tests\Feature\Admin;

use App\Actions\ProcessReturnAction;
use App\Enums\ReturnReason;
use App\Enums\ReturnStatus;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\OrderReturn;
use App\Models\ReturnItem;
use App\Models\User;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use Tests\TestCase;

class ReturnControllerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleAndPermissionSeeder::class);
    }

    // ── Authorization ─────────────────────────────────────────────────────────

    public function test_guests_are_redirected_from_returns_index(): void
    {
        $this->get(route('admin.returns.index'))->assertRedirect(route('login'));
    }

    public function test_guests_are_redirected_from_returns_show(): void
    {
        $orderReturn = OrderReturn::factory()->create();

        $this->get(route('admin.returns.show', $orderReturn))->assertRedirect(route('login'));
    }

    public function test_customers_cannot_access_admin_returns(): void
    {
        $user = User::factory()->create();
        $user->assignRole('customer');

        $this->actingAs($user)
            ->get(route('admin.returns.index'))
            ->assertForbidden();
    }

    // ── Index ─────────────────────────────────────────────────────────────────

    public function test_super_admin_can_view_returns_index(): void
    {
        $user = User::factory()->superAdmin()->create();
        OrderReturn::factory(3)->create();

        $this->actingAs($user)
            ->get(route('admin.returns.index'))
            ->assertOk()
            ->assertInertia(
                fn ($page) => $page
                    ->component('admin/Returns/Index')
                    ->has('returns.data', 3)
            );
    }

    public function test_returns_index_can_filter_by_status(): void
    {
        $user = User::factory()->superAdmin()->create();
        OrderReturn::factory()->requested()->create();
        OrderReturn::factory()->approved()->create();
        OrderReturn::factory()->approved()->create();

        $this->actingAs($user)
            ->get(route('admin.returns.index', ['status' => 'approved']))
            ->assertOk()
            ->assertInertia(
                fn ($page) => $page->has('returns.data', 2)
            );
    }

    public function test_returns_index_can_search_by_return_number(): void
    {
        $user = User::factory()->superAdmin()->create();
        $target = OrderReturn::factory()->create();
        OrderReturn::factory(2)->create();

        $this->actingAs($user)
            ->get(route('admin.returns.index', ['search' => $target->return_number]))
            ->assertOk()
            ->assertInertia(
                fn ($page) => $page->has('returns.data', 1)
            );
    }

    // ── Show ──────────────────────────────────────────────────────────────────

    public function test_super_admin_can_view_a_return(): void
    {
        $user = User::factory()->superAdmin()->create();
        $orderReturn = OrderReturn::factory()->create();
        $orderItem = OrderItem::factory()->for($orderReturn->order)->create();
        ReturnItem::factory()->for($orderReturn, 'orderReturn')->for($orderItem, 'orderItem')->create();

        $this->actingAs($user)
            ->get(route('admin.returns.show', $orderReturn))
            ->assertOk()
            ->assertInertia(
                fn ($page) => $page
                    ->component('admin/Returns/Show')
                    ->has('orderReturn')
                    ->where('orderReturn.return_number', $orderReturn->return_number)
            );
    }

    // ── Store ─────────────────────────────────────────────────────────────────

    public function test_super_admin_can_create_a_return(): void
    {
        $user = User::factory()->superAdmin()->create();
        $order = Order::factory()->create();
        $orderItem = OrderItem::factory()->for($order)->create(['quantity' => 2]);

        $this->actingAs($user)
            ->post(route('admin.returns.store'), [
                'order_id' => $order->id,
                'reason' => ReturnReason::Defective->value,
                'notes' => 'Item arrived broken.',
                'items' => [
                    ['order_item_id' => $orderItem->id, 'quantity' => 1],
                ],
            ])
            ->assertRedirect();

        $orderReturn = OrderReturn::query()
            ->where('order_id', $order->id)
            ->where('status', ReturnStatus::Requested->value)
            ->firstOrFail();

        $this->assertDatabaseHas('order_returns', [
            'order_id' => $order->id,
            'status' => ReturnStatus::Requested->value,
            'reason' => ReturnReason::Defective->value,
        ]);

        $this->assertDatabaseHas('return_items', [
            'order_item_id' => $orderItem->id,
            'quantity' => 1,
            'unit_price' => $orderItem->unit_price,
        ]);
    }

    public function test_store_validates_required_fields(): void
    {
        $user = User::factory()->superAdmin()->create();

        $this->actingAs($user)
            ->post(route('admin.returns.store'), [])
            ->assertSessionHasErrors(['order_id', 'reason', 'items']);
    }

    public function test_store_validates_items_belong_to_order(): void
    {
        $user = User::factory()->superAdmin()->create();
        $order = Order::factory()->create();
        $otherItem = OrderItem::factory()->create();

        $this->actingAs($user)
            ->post(route('admin.returns.store'), [
                'order_id' => $order->id,
                'reason' => ReturnReason::Defective->value,
                'items' => [
                    ['order_item_id' => $otherItem->id, 'quantity' => 1],
                ],
            ])
            ->assertRedirect();

        // The return is created but the item from a different order is skipped
        $this->assertDatabaseMissing('return_items', [
            'order_item_id' => $otherItem->id,
        ]);
    }

    // ── Update ────────────────────────────────────────────────────────────────

    public function test_super_admin_can_update_admin_notes(): void
    {
        $user = User::factory()->superAdmin()->create();
        $orderReturn = OrderReturn::factory()->create();

        $this->actingAs($user)
            ->put(route('admin.returns.update', $orderReturn), [
                'admin_notes' => 'Checked and confirmed defective.',
            ])
            ->assertRedirect(route('admin.returns.show', $orderReturn));

        $this->assertDatabaseHas('order_returns', [
            'id' => $orderReturn->id,
            'admin_notes' => 'Checked and confirmed defective.',
        ]);
    }

    // ── Approve ───────────────────────────────────────────────────────────────

    public function test_super_admin_can_approve_a_requested_return(): void
    {
        $user = User::factory()->superAdmin()->create();
        $orderReturn = OrderReturn::factory()->requested()->create();

        $this->actingAs($user)
            ->post(route('admin.returns.approve', $orderReturn))
            ->assertRedirect(route('admin.returns.show', $orderReturn));

        $this->assertDatabaseHas('order_returns', [
            'id' => $orderReturn->id,
            'status' => ReturnStatus::Approved->value,
        ]);
    }

    public function test_cannot_approve_an_already_approved_return(): void
    {
        $user = User::factory()->superAdmin()->create();
        $orderReturn = OrderReturn::factory()->approved()->create();

        $this->actingAs($user)
            ->post(route('admin.returns.approve', $orderReturn))
            ->assertRedirect(route('admin.returns.show', $orderReturn));

        $this->assertDatabaseHas('order_returns', [
            'id' => $orderReturn->id,
            'status' => ReturnStatus::Approved->value,
        ]);
    }

    // ── Reject ────────────────────────────────────────────────────────────────

    public function test_super_admin_can_reject_a_requested_return(): void
    {
        $user = User::factory()->superAdmin()->create();
        $orderReturn = OrderReturn::factory()->requested()->create();

        $this->actingAs($user)
            ->post(route('admin.returns.reject', $orderReturn))
            ->assertRedirect(route('admin.returns.show', $orderReturn));

        $this->assertDatabaseHas('order_returns', [
            'id' => $orderReturn->id,
            'status' => ReturnStatus::Rejected->value,
        ]);
    }

    public function test_cannot_reject_an_already_approved_return(): void
    {
        $user = User::factory()->superAdmin()->create();
        $orderReturn = OrderReturn::factory()->approved()->create();

        $this->actingAs($user)
            ->post(route('admin.returns.reject', $orderReturn))
            ->assertRedirect();

        $this->assertDatabaseHas('order_returns', [
            'id' => $orderReturn->id,
            'status' => ReturnStatus::Approved->value,
        ]);
    }

    // ── Receive ───────────────────────────────────────────────────────────────

    public function test_receive_calls_process_return_action_restock(): void
    {
        $user = User::factory()->superAdmin()->create();
        $orderReturn = OrderReturn::factory()->approved()->create();

        $mock = Mockery::mock(ProcessReturnAction::class);
        $mock->shouldReceive('restock')
            ->once()
            ->with(Mockery::on(fn ($r) => $r->id === $orderReturn->id));
        $this->app->instance(ProcessReturnAction::class, $mock);

        $this->actingAs($user)
            ->post(route('admin.returns.receive', $orderReturn))
            ->assertRedirect(route('admin.returns.show', $orderReturn));
    }

    public function test_cannot_receive_a_requested_return(): void
    {
        $user = User::factory()->superAdmin()->create();
        $orderReturn = OrderReturn::factory()->requested()->create();

        $this->actingAs($user)
            ->post(route('admin.returns.receive', $orderReturn))
            ->assertRedirect();

        $this->assertDatabaseHas('order_returns', [
            'id' => $orderReturn->id,
            'status' => ReturnStatus::Requested->value,
        ]);
    }

    // ── Refund ────────────────────────────────────────────────────────────────

    public function test_refund_calls_process_return_action_issue_refund(): void
    {
        $user = User::factory()->superAdmin()->create();
        $orderReturn = OrderReturn::factory()->received()->create();

        $mock = Mockery::mock(ProcessReturnAction::class);
        $mock->shouldReceive('issueRefund')
            ->once()
            ->with(Mockery::on(fn ($r) => $r->id === $orderReturn->id));
        $this->app->instance(ProcessReturnAction::class, $mock);

        $this->actingAs($user)
            ->post(route('admin.returns.refund', $orderReturn))
            ->assertRedirect(route('admin.returns.show', $orderReturn));
    }

    public function test_cannot_refund_a_requested_return(): void
    {
        $user = User::factory()->superAdmin()->create();
        $orderReturn = OrderReturn::factory()->requested()->create();

        $this->actingAs($user)
            ->post(route('admin.returns.refund', $orderReturn))
            ->assertRedirect();

        $this->assertDatabaseHas('order_returns', [
            'id' => $orderReturn->id,
            'status' => ReturnStatus::Requested->value,
        ]);
    }

    // ── Observer ──────────────────────────────────────────────────────────────

    public function test_creating_a_return_logs_activity(): void
    {
        $user = User::factory()->superAdmin()->create();
        $order = Order::factory()->create();
        $orderItem = OrderItem::factory()->for($order)->create();

        $this->actingAs($user)
            ->post(route('admin.returns.store'), [
                'order_id' => $order->id,
                'reason' => ReturnReason::Defective->value,
                'items' => [
                    ['order_item_id' => $orderItem->id, 'quantity' => 1],
                ],
            ]);

        $this->assertDatabaseHas('activity_logs', [
            'action' => 'created',
            'subject_type' => 'OrderReturn',
        ]);
    }
}
