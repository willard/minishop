<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import {
    AlertTriangle,
    ShoppingCart,
    TrendingUp,
    Users,
} from 'lucide-vue-next';
import {
    index as ordersIndex,
    show as showOrder,
} from '@/actions/App/Http/Controllers/Admin/OrderController';
import { index as productsIndex } from '@/actions/App/Http/Controllers/Admin/ProductController';
import RevenueChart from '@/components/RevenueChart.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import AppLayout from '@/layouts/AppLayout.vue';
import { dashboard } from '@/routes';
import { type BreadcrumbItem } from '@/types';

interface RecentOrder {
    id: number;
    order_number: string;
    status: string;
    total_amount: number;
    customer: {
        id: number;
        user: { id: number; name: string; email: string };
    } | null;
    created_at: string;
}

interface LowStockProduct {
    id: number;
    name: string;
    sku: string | null;
    stock_quantity: number;
}

interface RevenuePoint {
    label: string;
    revenue: number;
}

defineProps<{
    totalRevenue: number;
    totalOrders: number;
    totalCustomers: number;
    lowStockCount: number;
    lowStockThreshold: number;
    recentOrders: RecentOrder[];
    lowStockProducts: LowStockProduct[];
    revenueChart: RevenuePoint[];
}>();

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Dashboard', href: dashboard().url },
];

function formatPrice(cents: number): string {
    return (cents / 100).toLocaleString('en-US', {
        minimumFractionDigits: 2,
        maximumFractionDigits: 2,
    });
}

function statusVariant(
    status: string,
): 'default' | 'secondary' | 'destructive' | 'outline' {
    switch (status) {
        case 'delivered':
            return 'default';
        case 'cancelled':
        case 'refunded':
            return 'destructive';
        default:
            return 'secondary';
    }
}
</script>

<template>
    <Head title="Dashboard" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex flex-col gap-6 p-4">
            <!-- KPI Cards -->
            <div class="grid grid-cols-2 gap-4 md:grid-cols-4">
                <!-- Total Revenue -->
                <div
                    class="flex flex-col gap-2 rounded-xl border border-sidebar-border p-4"
                >
                    <div class="flex items-center justify-between">
                        <p class="text-sm text-muted-foreground">
                            Total Revenue
                        </p>
                        <TrendingUp class="size-4 text-muted-foreground" />
                    </div>
                    <p class="text-2xl font-bold">
                        ${{ formatPrice(totalRevenue) }}
                    </p>
                    <p class="text-xs text-muted-foreground">
                        Excluding cancelled & refunded
                    </p>
                </div>

                <!-- Total Orders -->
                <div
                    class="flex flex-col gap-2 rounded-xl border border-sidebar-border p-4"
                >
                    <div class="flex items-center justify-between">
                        <p class="text-sm text-muted-foreground">
                            Total Orders
                        </p>
                        <ShoppingCart class="size-4 text-muted-foreground" />
                    </div>
                    <p class="text-2xl font-bold">{{ totalOrders }}</p>
                    <p class="text-xs text-muted-foreground">All time</p>
                </div>

                <!-- Total Customers -->
                <div
                    class="flex flex-col gap-2 rounded-xl border border-sidebar-border p-4"
                >
                    <div class="flex items-center justify-between">
                        <p class="text-sm text-muted-foreground">Customers</p>
                        <Users class="size-4 text-muted-foreground" />
                    </div>
                    <p class="text-2xl font-bold">{{ totalCustomers }}</p>
                    <p class="text-xs text-muted-foreground">
                        Registered accounts
                    </p>
                </div>

                <!-- Low Stock -->
                <div
                    class="flex flex-col gap-2 rounded-xl border p-4"
                    :class="
                        lowStockCount > 0
                            ? 'border-destructive/50 bg-destructive/5'
                            : 'border-sidebar-border'
                    "
                >
                    <div class="flex items-center justify-between">
                        <p class="text-sm text-muted-foreground">Low Stock</p>
                        <AlertTriangle
                            class="size-4"
                            :class="
                                lowStockCount > 0
                                    ? 'text-destructive'
                                    : 'text-muted-foreground'
                            "
                        />
                    </div>
                    <p
                        class="text-2xl font-bold"
                        :class="lowStockCount > 0 ? 'text-destructive' : ''"
                    >
                        {{ lowStockCount }}
                    </p>
                    <p class="text-xs text-muted-foreground">
                        Products with &le; {{ lowStockThreshold }} units
                    </p>
                </div>
            </div>

            <!-- Revenue Chart -->
            <div class="overflow-hidden rounded-xl border border-sidebar-border">
                <div class="border-b border-sidebar-border bg-muted/50 px-4 py-3">
                    <h2 class="text-sm font-semibold">Revenue — Last 12 Months</h2>
                </div>
                <div class="p-4">
                    <RevenueChart :data="revenueChart" />
                </div>
            </div>

            <!-- Bottom Panels -->
            <div class="grid grid-cols-1 gap-6 md:grid-cols-2">
                <!-- Recent Orders -->
                <div
                    class="overflow-hidden rounded-xl border border-sidebar-border"
                >
                    <div
                        class="flex items-center justify-between border-b border-sidebar-border bg-muted/50 px-4 py-3"
                    >
                        <h2 class="text-sm font-semibold">Recent Orders</h2>
                        <Link :href="ordersIndex().url">
                            <Button
                                variant="ghost"
                                size="sm"
                                class="h-7 text-xs"
                                >View all</Button
                            >
                        </Link>
                    </div>

                    <div
                        v-if="recentOrders.length === 0"
                        class="px-4 py-8 text-center text-sm text-muted-foreground"
                    >
                        No orders yet.
                    </div>

                    <table v-else class="w-full text-sm">
                        <tbody class="divide-y divide-sidebar-border">
                            <tr
                                v-for="order in recentOrders"
                                :key="order.id"
                                class="transition-colors hover:bg-muted/30"
                            >
                                <td class="px-4 py-2.5">
                                    <Link
                                        :href="showOrder(order).url"
                                        class="font-mono text-xs font-medium hover:underline"
                                    >
                                        {{ order.order_number }}
                                    </Link>
                                    <p class="text-xs text-muted-foreground">
                                        {{ order.customer?.user?.name ?? '—' }}
                                    </p>
                                </td>
                                <td class="px-4 py-2.5 text-right">
                                    <p class="text-xs font-medium">
                                        ${{ formatPrice(order.total_amount) }}
                                    </p>
                                    <Badge
                                        :variant="statusVariant(order.status)"
                                        class="mt-0.5 text-xs capitalize"
                                    >
                                        {{ order.status }}
                                    </Badge>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- Low Stock Products -->
                <div
                    class="overflow-hidden rounded-xl border border-sidebar-border"
                >
                    <div
                        class="flex items-center justify-between border-b border-sidebar-border bg-muted/50 px-4 py-3"
                    >
                        <h2 class="text-sm font-semibold">
                            Low Stock Products
                        </h2>
                        <Link :href="productsIndex().url">
                            <Button
                                variant="ghost"
                                size="sm"
                                class="h-7 text-xs"
                                >View all</Button
                            >
                        </Link>
                    </div>

                    <div
                        v-if="lowStockProducts.length === 0"
                        class="px-4 py-8 text-center text-sm text-muted-foreground"
                    >
                        All products are well stocked.
                    </div>

                    <table v-else class="w-full text-sm">
                        <tbody class="divide-y divide-sidebar-border">
                            <tr
                                v-for="product in lowStockProducts"
                                :key="product.id"
                                class="transition-colors hover:bg-muted/30"
                            >
                                <td class="px-4 py-2.5">
                                    <p class="font-medium">
                                        {{ product.name }}
                                    </p>
                                    <p
                                        class="font-mono text-xs text-muted-foreground"
                                    >
                                        {{ product.sku ?? '—' }}
                                    </p>
                                </td>
                                <td class="px-4 py-2.5 text-right">
                                    <span
                                        class="text-sm font-semibold"
                                        :class="
                                            product.stock_quantity <= 5
                                                ? 'text-destructive'
                                                : 'text-yellow-600 dark:text-yellow-400'
                                        "
                                    >
                                        {{ product.stock_quantity }} left
                                    </span>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
