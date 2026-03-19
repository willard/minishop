<?php

namespace App\Http\Requests\Admin;

use App\Enums\ReturnReason;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreReturnRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'order_id' => ['required', 'integer', 'exists:orders,id'],
            'reason' => ['required', Rule::enum(ReturnReason::class)],
            'notes' => ['nullable', 'string', 'max:2000'],
            'admin_notes' => ['nullable', 'string', 'max:2000'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.order_item_id' => ['required', 'integer', 'exists:order_items,id'],
            'items.*.quantity' => ['required', 'integer', 'min:1'],
        ];
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return [
            'order_id.required' => 'An order is required.',
            'order_id.exists' => 'The selected order does not exist.',
            'reason.required' => 'A return reason is required.',
            'reason.enum' => 'The selected return reason is invalid.',
            'items.required' => 'At least one item is required.',
            'items.min' => 'At least one item is required.',
            'items.*.order_item_id.required' => 'Each item must reference an order item.',
            'items.*.order_item_id.exists' => 'One or more selected items do not exist.',
            'items.*.quantity.required' => 'A quantity is required for each item.',
            'items.*.quantity.min' => 'Item quantity must be at least 1.',
        ];
    }
}
