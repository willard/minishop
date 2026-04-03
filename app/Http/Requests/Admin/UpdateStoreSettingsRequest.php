<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class UpdateStoreSettingsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'currency' => ['required', 'string', 'size:3'],
            'currency_locale' => ['required', 'string', 'max:10'],
            'tax_rate' => ['required', 'numeric', 'min:0', 'max:100'],
            'active_payment_gateway' => ['required', 'string', 'in:stripe,paymongo,cod,bank_transfer'],
            'paymongo_public_key' => ['nullable', 'string', 'max:500'],
            'paymongo_secret_key' => ['nullable', 'string', 'max:500'],
            'paymongo_webhook_secret' => ['nullable', 'string', 'max:500'],
            'low_stock_threshold' => ['required', 'integer', 'min:0', 'max:10000'],
            'origin_postcode' => ['nullable', 'string', 'max:20'],
        ];
    }

    public function messages(): array
    {
        return [
            'currency.size' => 'Currency must be a 3-letter ISO code (e.g. PHP, USD).',
            'tax_rate.min' => 'Tax rate cannot be negative.',
            'tax_rate.max' => 'Tax rate cannot exceed 100%.',
            'active_payment_gateway.in' => 'Payment gateway must be stripe, paymongo, cod, or bank_transfer.',
            'low_stock_threshold.min' => 'Threshold cannot be negative.',
            'low_stock_threshold.max' => 'Threshold cannot exceed 10,000 units.',
        ];
    }
}
