<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

abstract class TaxZoneRateRequest extends FormRequest
{
    protected function prepareForValidation(): void
    {
        $this->merge([
            'is_compound' => $this->boolean('is_compound', false),
            'is_shipping_taxable' => $this->boolean('is_shipping_taxable', false),
        ]);
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:50'],
            'name_fr' => ['nullable', 'string', 'max:50'],
            'rate' => ['required', 'numeric', 'between:0,100'],
            'is_compound' => ['nullable', 'boolean'],
            'is_shipping_taxable' => ['nullable', 'boolean'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
        ];
    }

    public function messages(): array
    {
        return [
            'rate.between' => 'Rate must be between 0 and 100 (percent).',
            'name.max' => 'Rate name may not be longer than 50 characters.',
        ];
    }
}
