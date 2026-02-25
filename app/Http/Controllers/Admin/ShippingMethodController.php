<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreShippingMethodRequest;
use App\Http\Requests\Admin\UpdateShippingMethodRequest;
use App\Models\ShippingMethod;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class ShippingMethodController extends Controller
{
    public function index(): Response
    {
        $shippingMethods = ShippingMethod::query()
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();

        return Inertia::render('admin/ShippingMethods/Index', [
            'shippingMethods' => $shippingMethods,
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('admin/ShippingMethods/Create');
    }

    public function store(StoreShippingMethodRequest $request): RedirectResponse
    {
        $data = $request->validated();
        $data['is_free'] = $request->boolean('is_free');
        $data['is_active'] = $request->boolean('is_active', true);
        if ($data['is_free']) {
            $data['price'] = 0;
        }

        ShippingMethod::query()->create($data);

        return redirect()->route('admin.shipping-methods.index')
            ->with('success', 'Shipping method created successfully.');
    }

    public function edit(ShippingMethod $shippingMethod): Response
    {
        return Inertia::render('admin/ShippingMethods/Edit', [
            'shippingMethod' => $shippingMethod,
        ]);
    }

    public function update(UpdateShippingMethodRequest $request, ShippingMethod $shippingMethod): RedirectResponse
    {
        $data = $request->validated();
        $data['is_free'] = $request->boolean('is_free');
        $data['is_active'] = $request->boolean('is_active', true);
        if ($data['is_free']) {
            $data['price'] = 0;
        }

        $shippingMethod->update($data);

        return redirect()->route('admin.shipping-methods.index')
            ->with('success', 'Shipping method updated successfully.');
    }

    public function destroy(ShippingMethod $shippingMethod): RedirectResponse
    {
        $shippingMethod->delete();

        return redirect()->route('admin.shipping-methods.index')
            ->with('success', 'Shipping method deleted successfully.');
    }
}
