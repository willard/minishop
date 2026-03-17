<?php

namespace App\Http\Controllers\Admin;

use App\Actions\ProcessReturnAction;
use App\Enums\ReturnReason;
use App\Enums\ReturnStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreReturnRequest;
use App\Http\Requests\Admin\UpdateReturnRequest;
use App\Http\Resources\OrderReturnResource;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\OrderReturn;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class ReturnController extends Controller
{
    private const ALLOWED_SORTS = ['return_number', 'status', 'created_at'];

    public function index(Request $request): Response
    {
        $this->authorize('viewAny', OrderReturn::class);

        $filters = $request->only(['status', 'search', 'sort_by', 'sort_dir']);

        $sortBy = in_array($filters['sort_by'] ?? null, self::ALLOWED_SORTS)
            ? $filters['sort_by']
            : 'created_at';
        $sortDir = ($filters['sort_dir'] ?? 'desc') === 'asc' ? 'asc' : 'desc';

        $returns = OrderReturn::query()
            ->with('order')
            ->when(
                $filters['status'] ?? null,
                fn ($query, $status) => $query->where('status', $status)
            )
            ->when(
                $filters['search'] ?? null,
                fn ($query, $search) => $query->where(function ($q) use ($search): void {
                    $q->where('return_number', 'like', "%{$search}%")
                        ->orWhereHas('order', fn ($q) => $q->where('order_number', 'like', "%{$search}%"));
                })
            )
            ->orderBy($sortBy, $sortDir)
            ->paginate(20)
            ->withQueryString()
            ->through(fn (OrderReturn $r) => new OrderReturnResource($r));

        return Inertia::render('admin/Returns/Index', [
            'returns' => $returns,
            'filters' => $filters,
            'statuses' => array_map(
                fn (ReturnStatus $s) => ['value' => $s->value, 'label' => $s->label()],
                ReturnStatus::cases()
            ),
        ]);
    }

    public function create(Request $request): Response
    {
        $this->authorize('create', OrderReturn::class);

        $order = null;
        if ($request->has('order_id')) {
            $order = Order::query()
                ->with(['items.product', 'items.variant'])
                ->findOrFail($request->integer('order_id'));
        }

        return Inertia::render('admin/Returns/Create', [
            'order' => $order,
            'reasons' => array_map(
                fn (ReturnReason $r) => ['value' => $r->value, 'label' => $r->label()],
                ReturnReason::cases()
            ),
        ]);
    }

    public function store(StoreReturnRequest $request): RedirectResponse
    {
        $this->authorize('create', OrderReturn::class);

        $data = $request->validated();

        $order = Order::query()->findOrFail($data['order_id']);

        $orderItemIds = array_column($data['items'], 'order_item_id');
        $orderItems = OrderItem::query()
            ->where('order_id', $order->id)
            ->whereIn('id', $orderItemIds)
            ->get()
            ->keyBy('id');

        $orderReturn = DB::transaction(function () use ($data, $order, $orderItems): OrderReturn {
            /** @var OrderReturn $orderReturn */
            $orderReturn = OrderReturn::query()->create([
                'return_number' => '',
                'order_id' => $order->id,
                'status' => ReturnStatus::Requested->value,
                'reason' => $data['reason'],
                'notes' => $data['notes'] ?? null,
                'admin_notes' => $data['admin_notes'] ?? null,
            ]);

            $itemsToCreate = [];

            foreach ($data['items'] as $item) {
                $orderItem = $orderItems->get($item['order_item_id']);

                if (! $orderItem) {
                    continue;
                }

                $quantity = min((int) $item['quantity'], $orderItem->quantity);
                $unitPrice = $orderItem->unit_price;

                $itemsToCreate[] = [
                    'order_item_id' => $orderItem->id,
                    'quantity' => $quantity,
                    'unit_price' => $unitPrice,
                    'subtotal' => $unitPrice * $quantity,
                ];
            }

            $orderReturn->items()->createMany($itemsToCreate);

            return $orderReturn;
        });

        return redirect()->route('admin.returns.show', $orderReturn)
            ->with('success', 'Return request created successfully.');
    }

    public function show(OrderReturn $return): Response
    {
        $this->authorize('view', $return);

        $return->load(['order.customer.user', 'items.orderItem']);

        return Inertia::render('admin/Returns/Show', [
            'orderReturn' => (new OrderReturnResource($return))->resolve(),
            'statuses' => array_map(
                fn (ReturnStatus $s) => ['value' => $s->value, 'label' => $s->label()],
                ReturnStatus::cases()
            ),
            'reasons' => array_map(
                fn (ReturnReason $r) => ['value' => $r->value, 'label' => $r->label()],
                ReturnReason::cases()
            ),
        ]);
    }

    public function update(UpdateReturnRequest $request, OrderReturn $return): RedirectResponse
    {
        $this->authorize('update', $return);

        $return->update($request->validated());

        return redirect()->route('admin.returns.show', $return)
            ->with('success', 'Return updated successfully.');
    }

    public function approve(OrderReturn $return): RedirectResponse
    {
        $this->authorize('approve', $return);

        if (! in_array(ReturnStatus::Approved->value, $return->status->allowedTransitions())) {
            return redirect()->route('admin.returns.show', $return)
                ->with('error', 'This return cannot be approved in its current status.');
        }

        $return->update(['status' => ReturnStatus::Approved]);

        return redirect()->route('admin.returns.show', $return)
            ->with('success', 'Return approved.');
    }

    public function reject(OrderReturn $return): RedirectResponse
    {
        $this->authorize('reject', $return);

        if (! in_array(ReturnStatus::Rejected->value, $return->status->allowedTransitions())) {
            return redirect()->route('admin.returns.show', $return)
                ->with('error', 'This return cannot be rejected in its current status.');
        }

        $return->update(['status' => ReturnStatus::Rejected]);

        return redirect()->route('admin.returns.show', $return)
            ->with('success', 'Return rejected.');
    }

    public function receive(OrderReturn $return, ProcessReturnAction $processReturn): RedirectResponse
    {
        $this->authorize('receive', $return);

        if (! in_array(ReturnStatus::Received->value, $return->status->allowedTransitions())) {
            return redirect()->route('admin.returns.show', $return)
                ->with('error', 'This return cannot be marked as received in its current status.');
        }

        $processReturn->restock($return);

        return redirect()->route('admin.returns.show', $return)
            ->with('success', 'Return marked as received and inventory restocked.');
    }

    public function refund(OrderReturn $return, ProcessReturnAction $processReturn): RedirectResponse
    {
        $this->authorize('refund', $return);

        if (! in_array(ReturnStatus::Refunded->value, $return->status->allowedTransitions())) {
            return redirect()->route('admin.returns.show', $return)
                ->with('error', 'This return cannot be refunded in its current status.');
        }

        $processReturn->issueRefund($return);

        return redirect()->route('admin.returns.show', $return)
            ->with('success', 'Refund issued successfully.');
    }
}
