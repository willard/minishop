<?php

namespace App\Http\Requests\Admin;

use App\Enums\OrderStatus;
use App\Models\Order;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateOrderRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        /** @var Order $order */
        $order = $this->route('order');
        $allowed = OrderStatus::transitions()[$order->status->value] ?? [];

        return [
            'status' => ['required', Rule::enum(OrderStatus::class), Rule::in($allowed)],
            'notes' => ['nullable', 'string'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'status.required' => 'An order status is required.',
            'status.enum' => 'The selected status is invalid.',
            'status.in' => 'This status transition is not allowed.',
        ];
    }
}
