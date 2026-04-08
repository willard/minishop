<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class StoreProductVariantRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function after(): array
    {
        return [
            function (Validator $validator): void {
                $product = $this->route('product');

                if ($product && $product->isBundled()) {
                    $validator->errors()->add('product', 'Variants cannot be added to bundled products.');
                }
            },
        ];
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
