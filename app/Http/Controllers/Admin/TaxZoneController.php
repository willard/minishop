<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreTaxZoneRequest;
use App\Http\Requests\Admin\UpdateTaxZoneRequest;
use App\Models\TaxZone;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class TaxZoneController extends Controller
{
    public function index(): Response
    {
        $this->authorize('viewAny', TaxZone::class);

        $taxZones = TaxZone::query()
            ->with('rates')
            ->orderByDesc('priority')
            ->orderBy('name')
            ->paginate(20);

        return Inertia::render('admin/TaxZones/Index', [
            'taxZones' => $taxZones,
        ]);
    }

    public function create(): Response
    {
        $this->authorize('create', TaxZone::class);

        return Inertia::render('admin/TaxZones/Create');
    }

    public function store(StoreTaxZoneRequest $request): RedirectResponse
    {
        $data = $request->validated();
        $data['is_active'] = $request->boolean('is_active', true);

        TaxZone::query()->create($data);

        return redirect()->route('admin.tax-zones.index')
            ->with('success', 'Tax zone created successfully.');
    }

    public function edit(TaxZone $taxZone): Response
    {
        $this->authorize('update', $taxZone);

        $taxZone->load('rates');

        return Inertia::render('admin/TaxZones/Edit', [
            'taxZone' => $taxZone,
        ]);
    }

    public function update(UpdateTaxZoneRequest $request, TaxZone $taxZone): RedirectResponse
    {
        $data = $request->validated();
        $data['is_active'] = $request->boolean('is_active', true);

        $taxZone->update($data);

        return redirect()->route('admin.tax-zones.index')
            ->with('success', 'Tax zone updated successfully.');
    }

    public function destroy(TaxZone $taxZone): RedirectResponse
    {
        $this->authorize('delete', $taxZone);

        $taxZone->delete();

        return redirect()->route('admin.tax-zones.index')
            ->with('success', 'Tax zone deleted successfully.');
    }
}
