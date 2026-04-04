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

        $taxZone->rates()->create($request->validated());

        return redirect()->route('admin.tax-zones.edit', $taxZone)
            ->with('success', 'Rate added successfully.');
    }

    public function update(UpdateTaxZoneRateRequest $request, TaxZone $taxZone, TaxZoneRate $rate): RedirectResponse
    {
        $this->authorize('updateRate', $taxZone);

        $rate->update($request->validated());

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
