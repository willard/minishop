<?php

namespace App\Http\Requests\Admin;

use App\Models\TaxZone;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateTaxZoneRequest extends FormRequest
{
    public function authorize(): bool
    {
        $taxZone = $this->route('tax_zone');

        return $taxZone instanceof TaxZone && $this->user()->can('update', $taxZone);
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:100'],
            'country_code' => ['nullable', 'string', 'size:2', 'alpha', 'uppercase', Rule::notIn(['*'])],
            'province_code' => ['nullable', 'string', 'size:2', 'alpha', 'uppercase',
                Rule::in(['AB', 'BC', 'MB', 'NB', 'NL', 'NS', 'NT', 'NU', 'ON', 'PE', 'QC', 'SK', 'YT'])],
            'is_active' => ['nullable', 'boolean'],
            'priority' => ['nullable', 'integer', 'min:0'],
        ];
    }

    public function messages(): array
    {
        return [
            'country_code.size' => 'Country code must be exactly 2 characters (e.g. CA, US).',
            'country_code.alpha' => 'Country code must contain only letters.',
            'province_code.size' => 'Province code must be exactly 2 characters (e.g. ON, QC).',
            'province_code.in' => 'Province code must be a valid Canadian province or territory code.',
        ];
    }
}
