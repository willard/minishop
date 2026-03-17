<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import { ChevronDown, ChevronUp, ChevronsUpDown, Eye, Plus } from 'lucide-vue-next';
import { ref, watch } from 'vue';
import {
    index,
    show,
    create,
} from '@/actions/App/Http/Controllers/Admin/ReturnController';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem } from '@/types';

interface OrderSummary {
    id: number;
    order_number: string;
}

interface OrderReturn {
    id: number;
    return_number: string;
    status: string;
    status_label: string;
    reason_label: string;
    refund_amount: number;
    restocked: boolean;
    created_at: string;
    order: OrderSummary;
}

interface Pagination {
    data: OrderReturn[];
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
    returns: Pagination;
    filters: Filters;
    statuses: StatusOption[];
}>();

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Dashboard', href: '/dashboard' },
    { title: 'Returns', href: index().url },
];

const search = ref(props.filters.search ?? '');
const statusFilter = ref(props.filters.status ?? '');
const sortBy = ref(props.filters.sort_by ?? 'created_at');
const sortDir = ref(props.filters.sort_dir ?? 'desc');

let searchTimeout: ReturnType<typeof setTimeout>;
watch(search, (value) => {
    clearTimeout(searchTimeout);
    searchTimeout = setTimeout(() => {
        applyFilters();
    }, 400);
});

watch(statusFilter, () => applyFilters());

function applyFilters(): void {
    router.get(
        index().url,
        {
            search: search.value || undefined,
            status: statusFilter.value || undefined,
            sort_by: sortBy.value,
            sort_dir: sortDir.value,
        },
        { preserveState: true, replace: true },
    );
}

function toggleSort(column: string): void {
    if (sortBy.value === column) {
        sortDir.value = sortDir.value === 'asc' ? 'desc' : 'asc';
    } else {
        sortBy.value = column;
        sortDir.value = 'asc';
    }
    applyFilters();
}

function formatPrice(cents: number): string {
    return (cents / 100).toFixed(2);
}

function statusVariant(
    status: string,
): 'default' | 'secondary' | 'destructive' | 'outline' {
    switch (status) {
        case 'refunded':
            return 'default';
        case 'rejected':
            return 'destructive';
        case 'approved':
        case 'received':
            return 'outline';
        default:
            return 'secondary';
    }
}
</script>

<template>
    <Head title="Returns" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex flex-col gap-4 p-4">
            <!-- Header -->
            <div class="flex items-center justify-between">
                <h1 class="text-2xl font-semibold">Returns</h1>
                <Link :href="create().url">
                    <Button size="sm">
                        <Plus class="mr-1 size-4" />
                        New Return
                    </Button>
                </Link>
            </div>

            <!-- Filters -->
            <div class="flex flex-wrap gap-3">
                <Input
                    v-model="search"
                    placeholder="Search by RMA or order number..."
                    class="max-w-xs"
                />
                <select
                    v-model="statusFilter"
                    class="flex h-10 rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 focus-visible:outline-none"
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
            <div class="overflow-hidden rounded-lg border border-sidebar-border">
                <table class="w-full text-sm">
                    <thead class="bg-muted/50 text-muted-foreground">
                        <tr>
                            <th class="px-4 py-3 text-left font-medium">
                                <button
                                    class="flex items-center gap-1 hover:text-foreground"
                                    @click="toggleSort('return_number')"
                                >
                                    RMA #
                                    <ChevronUp
                                        v-if="sortBy === 'return_number' && sortDir === 'asc'"
                                        class="size-3"
                                    />
                                    <ChevronDown
                                        v-else-if="sortBy === 'return_number' && sortDir === 'desc'"
                                        class="size-3"
                                    />
                                    <ChevronsUpDown v-else class="size-3" />
                                </button>
                            </th>
                            <th class="px-4 py-3 text-left font-medium">
                                Order
                            </th>
                            <th class="px-4 py-3 text-left font-medium">
                                Reason
                            </th>
                            <th class="px-4 py-3 text-left font-medium">
                                <button
                                    class="flex items-center gap-1 hover:text-foreground"
                                    @click="toggleSort('status')"
                                >
                                    Status
                                    <ChevronUp
                                        v-if="sortBy === 'status' && sortDir === 'asc'"
                                        class="size-3"
                                    />
                                    <ChevronDown
                                        v-else-if="sortBy === 'status' && sortDir === 'desc'"
                                        class="size-3"
                                    />
                                    <ChevronsUpDown v-else class="size-3" />
                                </button>
                            </th>
                            <th class="px-4 py-3 text-right font-medium">
                                Refund
                            </th>
                            <th class="px-4 py-3 text-left font-medium">
                                <button
                                    class="flex items-center gap-1 hover:text-foreground"
                                    @click="toggleSort('created_at')"
                                >
                                    Date
                                    <ChevronUp
                                        v-if="sortBy === 'created_at' && sortDir === 'asc'"
                                        class="size-3"
                                    />
                                    <ChevronDown
                                        v-else-if="sortBy === 'created_at' && sortDir === 'desc'"
                                        class="size-3"
                                    />
                                    <ChevronsUpDown v-else class="size-3" />
                                </button>
                            </th>
                            <th class="px-4 py-3 text-right font-medium">
                                Actions
                            </th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-sidebar-border">
                        <tr
                            v-for="orderReturn in returns.data"
                            :key="orderReturn.id"
                            class="hover:bg-muted/30"
                        >
                            <td class="px-4 py-3 font-mono text-xs font-medium">
                                {{ orderReturn.return_number }}
                            </td>
                            <td class="px-4 py-3 font-mono text-xs text-muted-foreground">
                                {{ orderReturn.order.order_number }}
                            </td>
                            <td class="px-4 py-3 text-muted-foreground">
                                {{ orderReturn.reason_label }}
                            </td>
                            <td class="px-4 py-3">
                                <Badge :variant="statusVariant(orderReturn.status)" class="capitalize">
                                    {{ orderReturn.status_label }}
                                </Badge>
                            </td>
                            <td class="px-4 py-3 text-right">
                                <span v-if="orderReturn.refund_amount > 0">
                                    ${{ formatPrice(orderReturn.refund_amount) }}
                                </span>
                                <span v-else class="text-muted-foreground">—</span>
                            </td>
                            <td class="px-4 py-3 text-muted-foreground">
                                {{
                                    new Date(orderReturn.created_at).toLocaleDateString(
                                        'en-US',
                                        {
                                            year: 'numeric',
                                            month: 'short',
                                            day: 'numeric',
                                        },
                                    )
                                }}
                            </td>
                            <td class="px-4 py-3 text-right">
                                <Link :href="show(orderReturn.return_number).url">
                                    <Button variant="ghost" size="sm">
                                        <Eye class="size-4" />
                                    </Button>
                                </Link>
                            </td>
                        </tr>
                        <tr v-if="returns.data.length === 0">
                            <td
                                colspan="7"
                                class="px-4 py-10 text-center text-muted-foreground"
                            >
                                No returns found.
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <!-- Pagination -->
            <div
                v-if="returns.last_page > 1"
                class="flex items-center justify-between text-sm"
            >
                <p class="text-muted-foreground">
                    Showing {{ returns.data.length }} of {{ returns.total }} returns
                </p>
                <div class="flex gap-1">
                    <template v-for="link in returns.links" :key="link.label">
                        <Link
                            v-if="link.url"
                            :href="link.url"
                            class="rounded px-2 py-1 hover:bg-muted"
                            :class="{ 'bg-muted font-medium': link.active }"
                            v-html="link.label"
                        />
                        <span
                            v-else
                            class="rounded px-2 py-1 text-muted-foreground"
                            v-html="link.label"
                        />
                    </template>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
