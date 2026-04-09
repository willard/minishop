<?php

namespace App\Http\Requests\Admin;

use App\Enums\ProductType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreProductRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'stock_quantity' => $this->input('type') === ProductType::Bundled->value
                ? 0
                : ($this->input('stock_quantity') ?? 0),
            'is_active' => $this->input('is_active') ?? true,
        ]);
    }

    public function rules(): array
    {
        return [
            'type' => ['required', Rule::enum(ProductType::class)],
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'meta_title' => ['nullable', 'string', 'max:255'],
            'meta_description' => ['nullable', 'string', 'max:500'],
            'price' => ['required', 'integer', 'min:0'],
            'compare_price' => ['nullable', 'integer', 'min:0', 'gt:price'],
            'sku' => ['nullable', 'string', 'max:100', 'unique:products,sku'],
            'stock_quantity' => ['nullable', 'integer'],
            'weight_grams' => ['nullable', 'integer', 'min:1', 'max:100000'],
            'is_active' => ['nullable', 'boolean'],
            'category_ids' => ['nullable', 'array'],
            'category_ids.*' => ['integer', 'exists:categories,id'],
            'tag_ids' => ['nullable', 'array'],
            'tag_ids.*' => ['integer', 'exists:tags,id'],
        ];
    }

    public function messages(): array
    {
        return [
            'price.min' => 'Price cannot be negative.',
            'compare_price.gt' => 'Compare price must be greater than the sale price.',
            'sku.unique' => 'This SKU is already assigned to another product.',
            'category_ids.*.exists' => 'One or more selected categories do not exist.',
            'tag_ids.*.exists' => 'One or more selected tags do not exist.',
        ];
    }
}
