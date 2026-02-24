<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateCouponRequest extends FormRequest
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
        return [
            'code' => ['required', 'string', 'alpha_dash', 'max:50', Rule::unique('coupons')->ignore($this->coupon)],
            'description' => ['nullable', 'string', 'max:500'],
            'type' => ['required', 'string', 'in:fixed,percentage'],
            'value' => ['required', 'integer', 'min:1'],
            'minimum_order_amount' => ['nullable', 'integer', 'min:0'],
            'expiry_date' => ['nullable', 'date', 'after:today'],
            'usage_limit' => ['nullable', 'integer', 'min:1'],
            'is_active' => ['nullable', 'boolean'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'code.unique' => 'This coupon code is already in use.',
            'type.in' => 'The type must be either fixed or percentage.',
            'value.min' => 'The value must be at least 1.',
            'expiry_date.after' => 'The expiry date must be a future date.',
        ];
    }
}
