<?php

namespace App\Http\Requests\Admin;

use App\Models\TaxZone;

class UpdateTaxZoneRateRequest extends TaxZoneRateRequest
{
    public function authorize(): bool
    {
        $taxZone = $this->route('tax_zone');

        return $taxZone instanceof TaxZone && $this->user()->can('updateRate', $taxZone);
    }
}
