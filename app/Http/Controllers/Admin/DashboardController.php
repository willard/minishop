<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Models\Order;
use App\Models\Product;
use App\Models\StoreSettings;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function __invoke(): Response
    {
        Gate::authorize('dashboard.view');

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

        $monthExpr = DB::connection()->getDriverName() === 'sqlite'
            ? "strftime('%Y-%m', created_at)"
            : "DATE_FORMAT(created_at, '%Y-%m')";

        $revenueByMonth = Order::query()
            ->whereNotIn('status', ['cancelled', 'refunded'])
            ->where('created_at', '>=', now()->subMonths(11)->startOfMonth())
            ->selectRaw("{$monthExpr} as month, SUM(total_amount) as revenue")
            ->groupByRaw($monthExpr)
            ->orderBy('month')
            ->get()
            ->keyBy('month');

        $revenueChart = collect(range(11, 0))->map(function (int $i) use ($revenueByMonth): array {
            $date = Carbon::now()->subMonths($i);
            $key = $date->format('Y-m');

            return [
                'label' => $date->format('M Y'),
                'revenue' => (int) ($revenueByMonth->get($key)?->revenue ?? 0),
            ];
        })->values()->all();

        return Inertia::render('Dashboard', [
            'totalRevenue' => $totalRevenue,
            'totalOrders' => $totalOrders,
            'totalCustomers' => $totalCustomers,
            'lowStockCount' => $lowStockCount,
            'lowStockThreshold' => $threshold,
            'recentOrders' => $recentOrders,
            'lowStockProducts' => $lowStockProducts,
            'revenueChart' => $revenueChart,
        ]);
    }
}
