<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class StoreShippingMethodRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:100'],
            'description' => ['nullable', 'string', 'max:500'],
            'type' => ['nullable', 'string', 'in:flat_rate,calculated'],
            'carrier' => ['required_if:type,calculated', 'string', 'in:canada_post'],
            'service_code' => ['required_if:type,calculated', 'string', 'max:50'],
            'price' => ['required_if:is_free,false', 'nullable', 'integer', 'min:0'],
            'is_free' => ['nullable', 'boolean'],
            'is_active' => ['nullable', 'boolean'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
        ];
    }

    public function messages(): array
    {
        return [
            'price.required_if' => 'Price is required when the method is not free.',
            'price.min' => 'Price cannot be negative.',
            'carrier.required_if' => 'A carrier is required for calculated shipping methods.',
            'service_code.required_if' => 'A service code is required for calculated shipping methods.',
        ];
    }
}
