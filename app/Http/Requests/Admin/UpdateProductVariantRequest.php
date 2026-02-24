<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateProductVariantRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $variantId = $this->route('variant')?->id;

        return [
            'sku' => ['nullable', 'string', Rule::unique('product_variants', 'sku')->ignore($variantId)],
            'price' => ['nullable', 'integer', 'min:0'],
            'stock_quantity' => ['required', 'integer', 'min:0'],
            'is_active' => ['boolean'],
            'options' => ['required', 'array', 'min:1', 'max:3'],
            'options.*.name' => ['required', 'string', 'max:100'],
            'options.*.value' => ['required', 'string', 'max:100'],
        ];
    }

    public function messages(): array
    {
        return [
            'options.required' => 'At least one option is required.',
            'options.min' => 'At least one option is required.',
            'options.max' => 'You may add up to 3 options.',
            'options.*.name.required' => 'Option name is required.',
            'options.*.value.required' => 'Option value is required.',
        ];
    }
}
