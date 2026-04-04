<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreTaxZoneRateRequest;
use App\Http\Requests\Admin\UpdateTaxZoneRateRequest;
use App\Models\TaxZone;
use App\Models\TaxZoneRate;
use Illuminate\Http\RedirectResponse;

class TaxZoneRateController extends Controller
{
    public function store(StoreTaxZoneRateRequest $request, TaxZone $taxZone): RedirectResponse
    {
        $this->authorize('createRate', $taxZone);

        $data = $request->validated();
        $data['is_compound'] = $request->boolean('is_compound', false);
        $data['is_shipping_taxable'] = $request->boolean('is_shipping_taxable', false);

        $taxZone->rates()->create($data);

        return redirect()->route('admin.tax-zones.edit', $taxZone)
            ->with('success', 'Rate added successfully.');
    }

    public function update(UpdateTaxZoneRateRequest $request, TaxZone $taxZone, TaxZoneRate $rate): RedirectResponse
    {
        $this->authorize('updateRate', $taxZone);

        $data = $request->validated();
        $data['is_compound'] = $request->boolean('is_compound', false);
        $data['is_shipping_taxable'] = $request->boolean('is_shipping_taxable', false);

        $rate->update($data);

        return redirect()->route('admin.tax-zones.edit', $taxZone)
            ->with('success', 'Rate updated successfully.');
    }

    public function destroy(TaxZone $taxZone, TaxZoneRate $rate): RedirectResponse
    {
        $this->authorize('deleteRate', $taxZone);

        $rate->delete();

        return redirect()->route('admin.tax-zones.edit', $taxZone)
            ->with('success', 'Rate deleted successfully.');
    }
}
