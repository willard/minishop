<?php

namespace App\Http\Requests\Admin;

use App\Models\TaxZone;
use Illuminate\Foundation\Http\FormRequest;

class StoreTaxZoneRateRequest extends FormRequest
{
    public function authorize(): bool
    {
        $taxZone = $this->route('tax_zone');

        return $taxZone instanceof TaxZone && $this->user()->can('createRate', $taxZone);
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
