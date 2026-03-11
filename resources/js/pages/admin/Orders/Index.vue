<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import { Eye, ShoppingCart, Trash2 } from 'lucide-vue-next';
import { ref, watch } from 'vue';
import {
    index,
    create,
    show,
    destroy,
} from '@/actions/App/Http/Controllers/Admin/OrderController';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem } from '@/types';

interface Customer {
    id: number;
    user: {
        id: number;
        name: string;
        email: string;
    };
}

interface Order {
    id: number;
    order_number: string;
    status: string;
    total_amount: number;
    customer: Customer;
    items_count?: number;
    created_at: string;
}

interface Pagination {
    data: Order[];
    current_page: number;
    last_page: number;
    per_page: number;
    total: number;
    links: { url: string | null; label: string; active: boolean }[];
}

interface Filters {
    status?: string;
    search?: string;
}

interface StatusOption {
    value: string;
    label: string;
}

const props = defineProps<{
    orders: Pagination;
    filters: Filters;
    statuses: StatusOption[];
}>();

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Dashboard', href: '/dashboard' },
    { title: 'Orders', href: index().url },
];

const search = ref(props.filters.search ?? '');
let searchTimeout: ReturnType<typeof setTimeout> | null = null;

watch(search, (value) => {
    if (searchTimeout) {
        clearTimeout(searchTimeout);
    }
    searchTimeout = setTimeout(() => {
        router.get(
            index().url,
            { ...props.filters, search: value || undefined },
            { preserveState: true, replace: true },
        );
    }, 300);
});

function applyFilter(status: string | undefined): void {
    router.get(
        index().url,
        { search: search.value || undefined, status },
        { preserveState: true, replace: true },
    );
}

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

function confirmDelete(order: Order): void {
    if (confirm(`Delete order ${order.order_number}? This cannot be undone.`)) {
        router.delete(destroy(order).url);
    }
}
</script>

<template>
    <Head title="Orders" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex flex-col gap-6 p-4">
            <!-- Header -->
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-2xl font-semibold">Orders</h1>
                    <p class="text-sm text-muted-foreground">
                        {{ orders.total }} total orders
                    </p>
                </div>
                <Link :href="create().url">
                    <Button>
                        <ShoppingCart class="mr-2 size-4" />
                        New Order
                    </Button>
                </Link>
            </div>

            <!-- Filters -->
            <div class="flex flex-col gap-3 sm:flex-row sm:items-center">
                <Input
                    v-model="search"
                    placeholder="Search order # or customer..."
                    class="max-w-xs"
                />
                <div class="flex flex-wrap gap-2">
                    <Button
                        size="sm"
                        :variant="!filters.status ? 'default' : 'outline'"
                        @click="applyFilter(undefined)"
                    >
                        All
                    </Button>
                    <Button
                        v-for="s in statuses"
                        :key="s.value"
                        size="sm"
                        :variant="
                            filters.status === s.value ? 'default' : 'outline'
                        "
                        class="capitalize"
                        @click="applyFilter(s.value)"
                    >
                        {{ s.label }}
                    </Button>
                </div>
            </div>

            <!-- Table -->
            <div
                class="overflow-hidden rounded-lg border border-sidebar-border"
            >
                <table class="w-full text-sm">
                    <thead class="bg-muted/50 text-muted-foreground">
                        <tr>
                            <th class="px-4 py-3 text-left font-medium">
                                Order #
                            </th>
                            <th class="px-4 py-3 text-left font-medium">
                                Customer
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
                        <tr v-if="orders.data.length === 0">
                            <td
                                colspan="6"
                                class="px-4 py-8 text-center text-muted-foreground"
                            >
                                {{
                                    filters.status || filters.search
                                        ? 'No orders found.'
                                        : 'No orders yet.'
                                }}
                            </td>
                        </tr>
                        <tr
                            v-for="order in orders.data"
                            :key="order.id"
                            class="transition-colors hover:bg-muted/30"
                        >
                            <td class="px-4 py-3 font-mono text-xs font-medium">
                                {{ order.order_number }}
                            </td>
                            <td class="px-4 py-3">
                                <div>
                                    <p class="font-medium">
                                        {{ order.customer?.user?.name ?? '—' }}
                                    </p>
                                    <p class="text-xs text-muted-foreground">
                                        {{ order.customer?.user?.email ?? '' }}
                                    </p>
                                </div>
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
                            <td class="px-4 py-3">
                                <div class="flex justify-end gap-2">
                                    <Link :href="show(order).url">
                                        <Button variant="ghost" size="sm">
                                            <Eye class="size-4" />
                                        </Button>
                                    </Link>
                                    <Button
                                        variant="ghost"
                                        size="sm"
                                        class="text-destructive hover:text-destructive"
                                        @click="confirmDelete(order)"
                                    >
                                        <Trash2 class="size-4" />
                                    </Button>
                                </div>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <!-- Pagination -->
            <div v-if="orders.last_page > 1" class="flex justify-center gap-1">
                <template v-for="link in orders.links" :key="link.label">
                    <Link
                        v-if="link.url"
                        :href="link.url"
                        class="rounded border border-sidebar-border px-3 py-1.5 text-sm transition-colors hover:bg-muted/50"
                        :class="{
                            'border-primary bg-primary text-primary-foreground':
                                link.active,
                        }"
                        ><span v-html="link.label"
                    /></Link>
                    <span
                        v-else
                        class="rounded border border-sidebar-border px-3 py-1.5 text-sm text-muted-foreground opacity-50"
                        v-html="link.label"
                    />
                </template>
            </div>
        </div>
    </AppLayout>
</template>
