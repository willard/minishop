<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Models\Order;
use App\Models\Product;
use App\Models\ProductVariant;
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

        $productLowStockQuery = Product::query()
            ->where('is_active', true)
            ->whereDoesntHave('variants')
            ->when($lowStockThreshold !== null, fn ($q) => $q
                ->where('stock_quantity', '<=', $lowStockThreshold)
                ->where('stock_quantity', '>', 0)
            );

        $productLowStockCount = $lowStockThreshold !== null ? (clone $productLowStockQuery)->count() : 0;

        $variantLowStockCount = $lowStockThreshold !== null
            ? ProductVariant::where('stock_quantity', '<=', $lowStockThreshold)
                ->where('stock_quantity', '>', 0)
                ->whereHas('product', fn ($q) => $q->where('is_active', true))
                ->count()
            : 0;

        $lowStockCount = $productLowStockCount + $variantLowStockCount;

        $lowStockProducts = collect();

        if ($lowStockThreshold !== null) {
            $standaloneProducts = (clone $productLowStockQuery)
                ->orderBy('stock_quantity')
                ->limit(5)
                ->get()
                ->map(fn (Product $p) => [
                    'id' => $p->id,
                    'name' => $p->name,
                    'sku' => $p->sku,
                    'stock_quantity' => $p->stock_quantity,
                ]);

            $variantItems = ProductVariant::with(['product', 'optionValues.option'])
                ->where('stock_quantity', '<=', $lowStockThreshold)
                ->where('stock_quantity', '>', 0)
                ->whereHas('product', fn ($q) => $q->where('is_active', true))
                ->orderBy('stock_quantity')
                ->limit(5)
                ->get()
                ->map(function (ProductVariant $v) {
                    $optionLabel = $v->optionValues
                        ->map(fn ($ov) => $ov->value)
                        ->join(' / ');

                    return [
                        'id' => $v->product_id,
                        'name' => $v->product->name.($optionLabel ? " ({$optionLabel})" : ''),
                        'sku' => $v->sku,
                        'stock_quantity' => $v->stock_quantity,
                    ];
                });

            $lowStockProducts = $standaloneProducts
                ->merge($variantItems)
                ->sortBy('stock_quantity')
                ->take(5)
                ->values();
        }

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
