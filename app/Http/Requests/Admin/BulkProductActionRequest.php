<?php

namespace App\Http\Requests\Admin;

use App\Models\Product;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class BulkProductActionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('viewAny', Product::class);
    }

    public function rules(): array
    {
        return [
            'product_ids' => ['required', 'array', 'min:1'],
            'product_ids.*' => ['integer', 'exists:products,id'],
            'action' => ['required', 'string', Rule::in(['delete', 'activate', 'deactivate', 'assign_category', 'update_stock', 'update_price'])],
            'category_id' => ['required_if:action,assign_category', 'nullable', 'integer', 'exists:categories,id'],
            'stock_quantity' => ['required_if:action,update_stock', 'nullable', 'integer', 'min:0'],
            'price' => ['required_if:action,update_price', 'nullable', 'integer', 'min:0'],
        ];
    }

    public function messages(): array
    {
        return [
            'product_ids.required' => 'Please select at least one product.',
            'product_ids.min' => 'Please select at least one product.',
            'category_id.required_if' => 'Please select a category.',
            'stock_quantity.required_if' => 'Please enter a stock quantity.',
            'price.required_if' => 'Please enter a price.',
        ];
    }
}
