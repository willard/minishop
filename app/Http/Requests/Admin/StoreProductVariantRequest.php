<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreProductVariantRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'sku' => ['nullable', 'string', Rule::unique('product_variants', 'sku')],
            'price' => ['nullable', 'integer', 'min:0'],
            'stock_quantity' => ['required', 'integer', 'min:0'],
            'is_active' => ['boolean'],
            'option_value_ids' => ['required', 'array', 'min:1'],
            'option_value_ids.*' => ['required', 'integer', 'exists:product_option_values,id'],
        ];
    }

    public function messages(): array
    {
        return [
            'option_value_ids.required' => 'Select a value for each option.',
            'option_value_ids.min' => 'Select a value for each option.',
            'option_value_ids.*.exists' => 'Invalid option value selected.',
        ];
    }
}
