<?php

namespace App\Http\Requests\Admin;

use App\Enums\ProductType;
use App\Models\BundleItem;
use App\Models\Product;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class StoreBundleItemRequest extends FormRequest
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
            'component_product_id' => ['required', 'integer', 'exists:products,id'],
            'component_variant_id' => [
                'nullable',
                'integer',
                Rule::exists('product_variants', 'id')
                    ->where('product_id', $this->input('component_product_id')),
            ],
            'quantity' => ['required', 'integer', 'min:1', 'max:999'],
            'sort_order' => ['sometimes', 'integer', 'min:0', 'max:999'],
        ];
    }

    public function after(): array
    {
        return [
            function (Validator $validator): void {
                /** @var Product $product */
                $product = $this->route('product');

                if (! $product->isBundled()) {
                    $validator->errors()->add('product', 'Bundle items can only be added to bundled products.');

                    return;
                }

                $componentProductId = (int) $this->input('component_product_id');

                if ($componentProductId === $product->id) {
                    $validator->errors()->add('component_product_id', 'A bundle cannot contain itself as a component.');

                    return;
                }

                $component = Product::find($componentProductId);

                if ($component && $component->type === ProductType::Bundled) {
                    $validator->errors()->add('component_product_id', 'A bundled product cannot be added as a component.');

                    return;
                }

                if ($product->bundleItems()->count() >= 50) {
                    $validator->errors()->add('component_product_id', 'A bundle can have a maximum of 50 components.');

                    return;
                }

                $variantId = $this->input('component_variant_id');
                $existsQuery = BundleItem::where('bundle_product_id', $product->id)
                    ->where('component_product_id', $componentProductId);

                if ($variantId === null) {
                    $existsQuery->whereNull('component_variant_id');
                } else {
                    $existsQuery->where('component_variant_id', $variantId);
                }

                if ($existsQuery->exists()) {
                    $validator->errors()->add('component_product_id', 'This component is already in the bundle.');
                }
            },
        ];
    }

    public function messages(): array
    {
        return [
            'component_product_id.exists' => 'The selected product does not exist.',
            'component_variant_id.exists' => 'The selected variant does not belong to this product.',
            'quantity.min' => 'Quantity must be at least 1.',
        ];
    }
}
