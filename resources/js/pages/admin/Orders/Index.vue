<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import { ChevronDown, ChevronUp, ChevronsUpDown, Eye, ShoppingCart, Trash2 } from 'lucide-vue-next';
import { computed, ref, watch } from 'vue';
import bulkAction from '@/actions/App/Http/Controllers/Admin/OrderBulkActionController';
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

// ── Bulk selection ────────────────────────────────────────────────────────────

const selectedIds = ref<number[]>([]);

watch(() => props.orders.data, () => {
    selectedIds.value = [];
});

const allOnPageSelected = computed(
    () =>
        props.orders.data.length > 0 &&
        props.orders.data.every((o) => selectedIds.value.includes(o.id)),
);

const someOnPageSelected = computed(
    () => props.orders.data.some((o) => selectedIds.value.includes(o.id)) && !allOnPageSelected.value,
);

function isSelected(id: number): boolean {
    return selectedIds.value.includes(id);
}

function toggleSelect(id: number): void {
    if (isSelected(id)) {
        selectedIds.value = selectedIds.value.filter((i) => i !== id);
    } else {
        selectedIds.value = [...selectedIds.value, id];
    }
}

function toggleSelectAll(): void {
    const pageIds = props.orders.data.map((o) => o.id);
    if (allOnPageSelected.value) {
        selectedIds.value = selectedIds.value.filter((id) => !pageIds.includes(id));
    } else {
        const newIds = pageIds.filter((id) => !selectedIds.value.includes(id));
        selectedIds.value = [...selectedIds.value, ...newIds];
    }
}

// ── Bulk actions ──────────────────────────────────────────────────────────────

const showStatusModal = ref(false);
const bulkStatusValue = ref<string>('');
const processing = ref(false);
const bulkErrors = ref<Record<string, string>>({});

function handleBulkAction(action: string): void {
    bulkErrors.value = {};

    if (action === 'delete') {
        const n = selectedIds.value.length;
        if (!confirm(`Delete ${n} selected order${n !== 1 ? 's' : ''}? This cannot be undone.`)) return;
        submitBulkAction('delete');
    } else if (action === 'update_status') {
        bulkStatusValue.value = '';
        showStatusModal.value = true;
    }
}

function submitBulkAction(action: string, extra: Record<string, unknown> = {}): void {
    processing.value = true;
    bulkErrors.value = {};

    router.post(
        bulkAction().url,
        { order_ids: selectedIds.value, action, ...extra },
        {
            onFinish: () => {
                processing.value = false;
            },
            onSuccess: () => {
                selectedIds.value = [];
                showStatusModal.value = false;
            },
            onError: (errs) => {
                bulkErrors.value = errs;
            },
        },
    );
}

function submitUpdateStatus(): void {
    if (!bulkStatusValue.value) {
        bulkErrors.value = { status: 'Please select a status.' };
        return;
    }
    submitBulkAction('update_status', { status: bulkStatusValue.value });
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

            <!-- Bulk action toolbar -->
            <div
                v-if="selectedIds.length > 0"
                class="flex flex-wrap items-center gap-3 rounded-lg border border-primary/20 bg-primary/5 px-4 py-2.5"
            >
                <span class="text-sm font-medium">
                    {{ selectedIds.length }}
                    {{ selectedIds.length === 1 ? 'order' : 'orders' }} selected
                </span>
                <div class="ml-auto flex flex-wrap items-center gap-2">
                    <Button
                        size="sm"
                        variant="outline"
                        :disabled="processing"
                        @click="handleBulkAction('update_status')"
                    >
                        Update Status
                    </Button>
                    <Button
                        size="sm"
                        variant="destructive"
                        :disabled="processing"
                        @click="handleBulkAction('delete')"
                    >
                        Delete
                    </Button>
                    <Button
                        size="sm"
                        variant="ghost"
                        @click="selectedIds = []"
                    >
                        Clear
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
                            <th class="w-10 px-4 py-3">
                                <input
                                    type="checkbox"
                                    :checked="allOnPageSelected"
                                    :class="someOnPageSelected ? 'opacity-60' : ''"
                                    class="size-4 cursor-pointer rounded accent-primary"
                                    @change="toggleSelectAll"
                                />
                            </th>
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
                                colspan="7"
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
                            :class="{ 'bg-primary/5': isSelected(order.id) }"
                        >
                            <td class="w-10 px-4 py-3">
                                <input
                                    type="checkbox"
                                    :checked="isSelected(order.id)"
                                    class="size-4 cursor-pointer rounded accent-primary"
                                    @change="toggleSelect(order.id)"
                                />
                            </td>
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

        <!-- Update Status Modal -->
        <div
            v-if="showStatusModal"
            class="fixed inset-0 z-50 flex items-center justify-center bg-black/50"
            @click.self="showStatusModal = false"
        >
            <div class="w-full max-w-sm rounded-lg border border-sidebar-border bg-background p-6 shadow-lg">
                <h2 class="mb-4 text-lg font-semibold">Update Order Status</h2>
                <p class="mb-4 text-sm text-muted-foreground">
                    Orders where this transition is not valid will be skipped.
                </p>

                <div class="mb-4">
                    <label class="mb-1.5 block text-sm font-medium">New Status</label>
                    <select
                        v-model="bulkStatusValue"
                        class="w-full rounded-md border border-input bg-background px-3 py-2 text-sm shadow-sm focus:outline-none focus:ring-1 focus:ring-ring"
                    >
                        <option value="">Select a status…</option>
                        <option
                            v-for="s in statuses"
                            :key="s.value"
                            :value="s.value"
                        >
                            {{ s.label }}
                        </option>
                    </select>
                    <p v-if="bulkErrors.status" class="mt-1 text-xs text-destructive">
                        {{ bulkErrors.status }}
                    </p>
                </div>

                <div class="flex justify-end gap-2">
                    <Button
                        variant="outline"
                        @click="showStatusModal = false"
                    >
                        Cancel
                    </Button>
                    <Button
                        :disabled="processing"
                        @click="submitUpdateStatus"
                    >
                        Update
                    </Button>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
