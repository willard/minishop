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

        $lowStockThreshold = StoreSettings::current()->low_stock_threshold;

        $totalRevenue = Order::query()
            ->whereNotIn('status', ['cancelled', 'refunded'])
            ->sum('total_amount');

        $totalOrders = Order::query()->count();

        $totalCustomers = Customer::query()->count();

        $lowStockQuery = Product::query()
            ->where('is_active', true)
            ->when($lowStockThreshold !== null, fn ($q) => $q->where('stock_quantity', '<=', $lowStockThreshold));

        $lowStockCount = $lowStockThreshold !== null ? (clone $lowStockQuery)->count() : 0;

        $lowStockProducts = $lowStockThreshold !== null
            ? (clone $lowStockQuery)->orderBy('stock_quantity')->limit(5)->get()
            : collect();

        $recentOrders = Order::query()
            ->with('customer.user')
            ->latest()
            ->limit(5)
            ->get();

        $revenueChart = $this->buildRevenueChart();

        return Inertia::render('Dashboard', [
            'totalRevenue' => $totalRevenue,
            'totalOrders' => $totalOrders,
            'totalCustomers' => $totalCustomers,
            'lowStockCount' => $lowStockCount,
            'lowStockThreshold' => $lowStockThreshold,
            'recentOrders' => $recentOrders,
            'lowStockProducts' => $lowStockProducts,
            'revenueChart' => $revenueChart,
        ]);
    }

    private function monthExpression(): string
    {
        if (DB::connection()->getDriverName() === 'sqlite') {
            return "strftime('%Y-%m', created_at)";
        }

        return "DATE_FORMAT(created_at, '%Y-%m')";
    }

    /**
     * @return array<int, array{label: string, revenue: int}>
     */
    private function buildRevenueChart(): array
    {
        $monthExpr = $this->monthExpression();

        $revenueByMonth = Order::query()
            ->whereNotIn('status', ['cancelled', 'refunded'])
            ->where('created_at', '>=', now()->subMonths(11)->startOfMonth())
            ->selectRaw("{$monthExpr} as month, SUM(total_amount) as revenue")
            ->groupByRaw($monthExpr)
            ->orderBy('month')
            ->get()
            ->keyBy('month');

        return collect(range(11, 0))
            ->map(function (int $monthsAgo) use ($revenueByMonth): array {
                $date = Carbon::now()->subMonths($monthsAgo);
                $key = $date->format('Y-m');

                return [
                    'label' => $date->format('M Y'),
                    'revenue' => (int) ($revenueByMonth->get($key)?->revenue ?? 0),
                ];
            })
            ->values()
            ->all();
    }
}
