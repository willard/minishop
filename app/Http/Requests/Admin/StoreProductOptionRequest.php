<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class StoreProductOptionRequest extends FormRequest
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
                    $validator->errors()->add('product', 'Options cannot be added to bundled products.');
                }
            },
        ];
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:100'],
            'values' => ['required', 'array', 'min:1', 'max:20'],
            'values.*' => ['required', 'string', 'max:100'],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'Option name is required.',
            'values.required' => 'At least one value is required.',
            'values.min' => 'At least one value is required.',
            'values.*.required' => 'Each value must not be empty.',
        ];
    }
}
