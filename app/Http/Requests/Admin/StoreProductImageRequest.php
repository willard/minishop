<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class StoreProductImageRequest extends FormRequest
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
            'images' => ['required', 'array', 'min:1'],
            'images.*' => ['required', 'image', 'mimes:jpg,jpeg,png,webp,gif', 'max:2048'],
            'alt_text' => ['nullable', 'string', 'max:255'],
            'variant_id' => ['nullable', 'integer', 'exists:product_variants,id'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'images.required' => 'Please select at least one image to upload.',
            'images.*.image' => 'Each file must be a valid image.',
            'images.*.mimes' => 'Accepted formats: JPG, PNG, WebP, GIF.',
            'images.*.max' => 'Each image must not exceed 2 MB.',
        ];
    }
}
