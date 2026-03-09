<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { ArrowLeft } from 'lucide-vue-next';
import { index } from '@/actions/App/Http/Controllers/Admin/CustomerController';
import { show as showOrder } from '@/actions/App/Http/Controllers/Admin/OrderController';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem } from '@/types';

interface OrderItem {
    id: number;
    product_name: string;
    quantity: number;
    subtotal: number;
}

interface Order {
    id: number;
    order_number: string;
    status: string;
    total_amount: number;
    items: OrderItem[];
    created_at: string;
}

interface User {
    id: number;
    name: string;
    email: string;
}

interface Customer {
    id: number;
    phone: string | null;
    notes: string | null;
    is_active: boolean;
    user: User;
    orders: Order[];
    created_at: string;
}

const props = defineProps<{
    customer: Customer;
}>();

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Dashboard', href: '/dashboard' },
    { title: 'Customers', href: index().url },
    { title: props.customer.user?.name ?? 'Customer', href: '#' },
];

function formatPrice(cents: number): string {
    return (cents / 100).toFixed(2);
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
    <Head :title="customer.user?.name ?? 'Customer'" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex max-w-4xl flex-col gap-6 p-4">
            <!-- Header -->
            <div class="flex items-center gap-4">
                <Link :href="index().url">
                    <Button variant="ghost" size="sm">
                        <ArrowLeft class="size-4" />
                    </Button>
                </Link>
                <div>
                    <h1 class="text-2xl font-semibold">
                        {{ customer.user?.name ?? '—' }}
                    </h1>
                    <p class="text-sm text-muted-foreground">
                        {{ customer.user?.email }}
                    </p>
                </div>
                <Badge
                    :variant="customer.is_active ? 'default' : 'secondary'"
                    class="ml-auto"
                >
                    {{ customer.is_active ? 'Active' : 'Inactive' }}
                </Badge>
            </div>

            <!-- Customer Details -->
            <div class="space-y-3 rounded-lg border border-sidebar-border p-4">
                <h2
                    class="text-sm font-semibold tracking-wide text-muted-foreground uppercase"
                >
                    Contact Information
                </h2>
                <div class="grid grid-cols-2 gap-4 text-sm">
                    <div>
                        <p class="text-muted-foreground">Email</p>
                        <p class="font-medium">
                            {{ customer.user?.email ?? '—' }}
                        </p>
                    </div>
                    <div>
                        <p class="text-muted-foreground">Phone</p>
                        <p class="font-medium">{{ customer.phone ?? '—' }}</p>
                    </div>
                    <div>
                        <p class="text-muted-foreground">Customer Since</p>
                        <p class="font-medium">
                            {{
                                new Date(
                                    customer.created_at,
                                ).toLocaleDateString('en-US', {
                                    year: 'numeric',
                                    month: 'long',
                                    day: 'numeric',
                                })
                            }}
                        </p>
                    </div>
                    <div v-if="customer.notes">
                        <p class="text-muted-foreground">Notes</p>
                        <p class="font-medium">{{ customer.notes }}</p>
                    </div>
                </div>
            </div>

            <!-- Recent Orders -->
            <div
                class="overflow-hidden rounded-lg border border-sidebar-border"
            >
                <div
                    class="border-b border-sidebar-border bg-muted/50 px-4 py-3"
                >
                    <h2
                        class="text-sm font-semibold tracking-wide text-muted-foreground uppercase"
                    >
                        Recent Orders
                    </h2>
                </div>
                <div
                    v-if="customer.orders.length === 0"
                    class="px-4 py-8 text-center text-sm text-muted-foreground"
                >
                    No orders yet.
                </div>
                <table v-else class="w-full text-sm">
                    <thead class="text-muted-foreground">
                        <tr>
                            <th class="px-4 py-3 text-left font-medium">
                                Order #
                            </th>
                            <th class="px-4 py-3 text-left font-medium">
                                Items
                            </th>
                            <th class="px-4 py-3 text-left font-medium">
                                Total
                            </th>
                            <th class="px-4 py-3 text-left font-medium">
                                Status
                            </th>
                            <th class="px-4 py-3 text-left font-medium">
                                Date
                            </th>
                            <th class="px-4 py-3 text-right font-medium">
                                Actions
                            </th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-sidebar-border">
                        <tr
                            v-for="order in customer.orders"
                            :key="order.id"
                            class="transition-colors hover:bg-muted/30"
                        >
                            <td class="px-4 py-3 font-mono text-xs font-medium">
                                {{ order.order_number }}
                            </td>
                            <td class="px-4 py-3 text-muted-foreground">
                                {{ order.items.length }}
                            </td>
                            <td class="px-4 py-3">
                                ${{ formatPrice(order.total_amount) }}
                            </td>
                            <td class="px-4 py-3">
                                <Badge
                                    :variant="statusVariant(order.status)"
                                    class="capitalize"
                                >
                                    {{ order.status }}
                                </Badge>
                            </td>
                            <td class="px-4 py-3 text-xs text-muted-foreground">
                                {{
                                    new Date(
                                        order.created_at,
                                    ).toLocaleDateString()
                                }}
                            </td>
                            <td class="px-4 py-3 text-right">
                                <Link :href="showOrder(order).url">
                                    <Button variant="ghost" size="sm"
                                        >View</Button
                                    >
                                </Link>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </AppLayout>
</template>
