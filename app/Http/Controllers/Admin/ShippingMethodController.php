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
        $this->authorize('viewAny', ShippingMethod::class);

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
        $this->authorize('create', ShippingMethod::class);

        return Inertia::render('admin/ShippingMethods/Create');
    }

    public function store(StoreShippingMethodRequest $request): RedirectResponse
    {
        $this->authorize('create', ShippingMethod::class);

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
        $this->authorize('update', $shippingMethod);

        return Inertia::render('admin/ShippingMethods/Edit', [
            'shippingMethod' => $shippingMethod,
        ]);
    }

    public function update(UpdateShippingMethodRequest $request, ShippingMethod $shippingMethod): RedirectResponse
    {
        $this->authorize('update', $shippingMethod);

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
        $this->authorize('delete', $shippingMethod);

        $shippingMethod->delete();

        return redirect()->route('admin.shipping-methods.index')
            ->with('success', 'Shipping method deleted successfully.');
    }
}
