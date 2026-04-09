<?php

namespace App\Http\Requests\Admin;

use App\Enums\ProductType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateProductRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        // Strip type — it cannot be changed after creation
        $this->request->remove('type');

        if ($this->route('product')?->type === ProductType::Bundled) {
            // Bundled products derive stock from components
            $this->request->remove('stock_quantity');
        } elseif ($this->has('stock_quantity')) {
            $this->merge(['stock_quantity' => $this->input('stock_quantity') ?? 0]);
        }
    }

    public function rules(): array
    {
        $productId = $this->route('product')?->id;

        return [
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'meta_title' => ['nullable', 'string', 'max:255'],
            'meta_description' => ['nullable', 'string', 'max:500'],
            'price' => ['required', 'integer', 'min:0'],
            'compare_price' => ['nullable', 'integer', 'min:0', 'gt:price'],
            'sku' => ['nullable', 'string', 'max:100', Rule::unique('products', 'sku')->ignore($productId)],
            'stock_quantity' => ['sometimes', 'nullable', 'integer'],
            'weight_grams' => ['sometimes', 'nullable', 'integer', 'min:1', 'max:100000'],
            'is_active' => ['sometimes', 'nullable', 'boolean'],
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
