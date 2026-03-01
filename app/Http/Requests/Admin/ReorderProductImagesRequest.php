<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class ReorderProductImagesRequest extends FormRequest
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
            'image_ids' => ['required', 'array'],
            'image_ids.*' => ['integer', 'exists:product_images,id'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'image_ids.required' => 'Image order data is required.',
            'image_ids.array' => 'Image order must be a list of image IDs.',
            'image_ids.*.integer' => 'Each image ID must be a valid number.',
            'image_ids.*.exists' => 'One or more images could not be found.',
        ];
    }
}
