<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import { ChevronDown, ChevronUp, ChevronsUpDown, Eye, ShoppingCart, Trash2 } from 'lucide-vue-next';
import { ref, watch } from 'vue';
import {
    index,
    create,
    show,
    destroy,
} from '@/actions/App/Http/Controllers/Admin/OrderController';
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
    sort_by?: string;
    sort_dir?: string;
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
const selectedStatus = ref(props.filters.status ?? '');
let searchTimeout: ReturnType<typeof setTimeout> | null = null;

watch(search, (value) => {
    if (searchTimeout) clearTimeout(searchTimeout);
    searchTimeout = setTimeout(() => {
        router.get(
            index().url,
            { ...props.filters, search: value || undefined },
            { preserveState: true, replace: true },
        );
    }, 300);
});

watch(selectedStatus, (value) => {
    router.get(
        index().url,
        { ...props.filters, search: search.value || undefined, status: value || undefined },
        { preserveState: true, replace: true },
    );
});

function applySort(column: string): void {
    const newDir =
        props.filters.sort_by === column && props.filters.sort_dir === 'asc'
            ? 'desc'
            : 'asc';
    router.get(
        index().url,
        { ...props.filters, search: search.value || undefined, sort_by: column, sort_dir: newDir },
        { preserveState: true, replace: true },
    );
}

function sortDir(column: string): 'asc' | 'desc' | null {
    if (props.filters.sort_by !== column) return null;
    return props.filters.sort_dir === 'asc' ? 'asc' : 'desc';
}

function formatPrice(cents: number): string {
    return (cents / 100).toFixed(2);
}

function statusClass(status: string): string {
    const base = 'inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium capitalize';
    switch (status) {
        case 'pending':
            return `${base} bg-amber-100 text-amber-800 dark:bg-amber-900/30 dark:text-amber-400`;
        case 'processing':
            return `${base} bg-blue-100 text-blue-800 dark:bg-blue-900/30 dark:text-blue-400`;
        case 'shipped':
            return `${base} bg-violet-100 text-violet-800 dark:bg-violet-900/30 dark:text-violet-400`;
        case 'delivered':
            return `${base} bg-emerald-100 text-emerald-800 dark:bg-emerald-900/30 dark:text-emerald-400`;
        case 'cancelled':
            return `${base} bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-400`;
        case 'refunded':
            return `${base} bg-orange-100 text-orange-800 dark:bg-orange-900/30 dark:text-orange-400`;
        default:
            return `${base} bg-muted text-muted-foreground`;
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
            <div class="flex flex-wrap items-center gap-3">
                <Input
                    v-model="search"
                    placeholder="Search order # or customer..."
                    class="max-w-xs"
                />

                <!-- Status dropdown -->
                <select
                    v-model="selectedStatus"
                    class="h-9 rounded-md border border-input bg-background px-3 py-1 text-sm shadow-sm transition-colors focus:outline-none focus:ring-1 focus:ring-ring"
                >
                    <option value="">All Statuses</option>
                    <option
                        v-for="s in statuses"
                        :key="s.value"
                        :value="s.value"
                    >
                        {{ s.label }}
                    </option>
                </select>
            </div>

            <!-- Table -->
            <div
                class="overflow-hidden rounded-lg border border-sidebar-border"
            >
                <table class="w-full text-sm">
                    <thead class="bg-muted/50 text-muted-foreground">
                        <tr>
                            <th class="px-4 py-3 text-left font-medium">
                                <button
                                    class="flex items-center gap-1 hover:text-foreground"
                                    @click="applySort('order_number')"
                                >
                                    Order #
                                    <ChevronUp v-if="sortDir('order_number') === 'asc'" class="size-3.5" />
                                    <ChevronDown v-else-if="sortDir('order_number') === 'desc'" class="size-3.5" />
                                    <ChevronsUpDown v-else class="size-3.5 opacity-40" />
                                </button>
                            </th>
                            <th class="px-4 py-3 text-left font-medium">
                                Customer
                            </th>
                            <th class="px-4 py-3 text-left font-medium">
                                <button
                                    class="flex items-center gap-1 hover:text-foreground"
                                    @click="applySort('total_amount')"
                                >
                                    Total
                                    <ChevronUp v-if="sortDir('total_amount') === 'asc'" class="size-3.5" />
                                    <ChevronDown v-else-if="sortDir('total_amount') === 'desc'" class="size-3.5" />
                                    <ChevronsUpDown v-else class="size-3.5 opacity-40" />
                                </button>
                            </th>
                            <th class="px-4 py-3 text-left font-medium">
                                <button
                                    class="flex items-center gap-1 hover:text-foreground"
                                    @click="applySort('status')"
                                >
                                    Status
                                    <ChevronUp v-if="sortDir('status') === 'asc'" class="size-3.5" />
                                    <ChevronDown v-else-if="sortDir('status') === 'desc'" class="size-3.5" />
                                    <ChevronsUpDown v-else class="size-3.5 opacity-40" />
                                </button>
                            </th>
                            <th class="px-4 py-3 text-left font-medium">
                                <button
                                    class="flex items-center gap-1 hover:text-foreground"
                                    @click="applySort('created_at')"
                                >
                                    Date
                                    <ChevronUp v-if="sortDir('created_at') === 'asc'" class="size-3.5" />
                                    <ChevronDown v-else-if="sortDir('created_at') === 'desc'" class="size-3.5" />
                                    <ChevronsUpDown v-else class="size-3.5 opacity-40" />
                                </button>
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
                                <span :class="statusClass(order.status)">
                                    {{ order.status }}
                                </span>
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
