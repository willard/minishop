<?php

namespace App\Http\Requests\Admin;

use App\Enums\OrderStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class BulkOrderActionRequest extends FormRequest
{
    public function authorize(): bool
    {
        if ($this->input('action') === 'delete') {
            return $this->user()->can('orders.delete');
        }

        return $this->user()->can('orders.update');
    }

    public function rules(): array
    {
        return [
            'order_ids' => ['required', 'array', 'min:1'],
            'order_ids.*' => ['integer', 'exists:orders,id'],
            'action' => ['required', 'string', Rule::in(['update_status', 'delete'])],
            'status' => ['required_if:action,update_status', 'nullable', Rule::enum(OrderStatus::class)],
        ];
    }

    public function messages(): array
    {
        return [
            'order_ids.required' => 'Please select at least one order.',
            'order_ids.min' => 'Please select at least one order.',
            'status.required_if' => 'Please select a status.',
        ];
    }
}
