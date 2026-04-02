<?php

namespace App\Http\Requests\Admin;

use App\Models\Product;
use Illuminate\Foundation\Http\FormRequest;

class StoreProductRelatedRequest extends FormRequest
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
        /** @var Product $product */
        $product = $this->route('product');

        return [
            'related_product_id' => [
                'required',
                'integer',
                'exists:products,id',
                function (string $attribute, mixed $value, \Closure $fail) use ($product): void {
                    if ((int) $value === $product->id) {
                        $fail('A product cannot be related to itself.');
                    }
                },
            ],
        ];
    }
}
