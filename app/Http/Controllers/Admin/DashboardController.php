<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Models\Order;
use App\Models\Product;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function __invoke(): Response
    {
        $totalRevenue = Order::query()
            ->whereNotIn('status', ['cancelled', 'refunded'])
            ->sum('total_amount');

        $totalOrders = Order::query()->count();

        $totalCustomers = Customer::query()->count();

        $lowStockCount = Product::query()
            ->where('is_active', true)
            ->where('stock_quantity', '<=', 10)
            ->count();

        $recentOrders = Order::query()
            ->with('customer.user')
            ->latest()
            ->limit(5)
            ->get();

        $lowStockProducts = Product::query()
            ->where('is_active', true)
            ->where('stock_quantity', '<=', 10)
            ->orderBy('stock_quantity')
            ->limit(5)
            ->get();

        return Inertia::render('Dashboard', [
            'totalRevenue' => $totalRevenue,
            'totalOrders' => $totalOrders,
            'totalCustomers' => $totalCustomers,
            'lowStockCount' => $lowStockCount,
            'recentOrders' => $recentOrders,
            'lowStockProducts' => $lowStockProducts,
        ]);
    }
}
