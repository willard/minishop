<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class CustomerController extends Controller
{
    public function index(): Response
    {
        Gate::authorize('customers.view');

        $customers = Customer::query()
            ->with('user')
            ->withCount('orders')
            ->orderByDesc('created_at')
            ->paginate(20);

        return Inertia::render('admin/Customers/Index', [
            'customers' => $customers,
        ]);
    }

    public function show(Customer $customer): Response
    {
        Gate::authorize('customers.view');

        $customer->load([
            'user',
            'orders' => fn ($query) => $query->latest()->limit(10)->with('items'),
        ]);

        return Inertia::render('admin/Customers/Show', [
            'customer' => $customer,
        ]);
    }
}
