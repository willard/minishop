<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Models\Order;
use App\Models\Product;
use App\Models\StoreSettings;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function __invoke(): Response
    {
        $threshold = StoreSettings::current()->low_stock_threshold;

        $totalRevenue = Order::query()
            ->whereNotIn('status', ['cancelled', 'refunded'])
            ->sum('total_amount');

        $totalOrders = Order::query()->count();

        $totalCustomers = Customer::query()->count();

        $lowStockCount = Product::query()
            ->where('is_active', true)
            ->where('stock_quantity', '<=', $threshold)
            ->count();

        $recentOrders = Order::query()
            ->with('customer.user')
            ->latest()
            ->limit(5)
            ->get();

        $lowStockProducts = Product::query()
            ->where('is_active', true)
            ->where('stock_quantity', '<=', $threshold)
            ->orderBy('stock_quantity')
            ->limit(5)
            ->get();

        return Inertia::render('Dashboard', [
            'totalRevenue' => $totalRevenue,
            'totalOrders' => $totalOrders,
            'totalCustomers' => $totalCustomers,
            'lowStockCount' => $lowStockCount,
            'lowStockThreshold' => $threshold,
            'recentOrders' => $recentOrders,
            'lowStockProducts' => $lowStockProducts,
        ]);
    }
}
