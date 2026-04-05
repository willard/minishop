<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import { ChevronDown, ChevronUp, ChevronsUpDown, Download, Eye, FileText, PackagePlus, Pencil, Trash2 } from 'lucide-vue-next';
import { computed, ref, watch } from 'vue';
import bulkAction from '@/actions/App/Http/Controllers/Admin/ProductBulkActionController';
import {
    index,
    create,
    show,
    edit,
    destroy,
    exportMethod,
} from '@/actions/App/Http/Controllers/Admin/ProductController';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem } from '@/types';

interface Category {
    id: number;
    name: string;
}

interface Product {
    id: number;
    name: string;
    slug: string;
    price: number;
    stock_quantity: number;
    is_active: boolean;
    sku: string | null;
    categories: Category[];
}

interface Pagination {
    data: Product[];
    current_page: number;
    last_page: number;
    per_page: number;
    total: number;
    links: { url: string | null; label: string; active: boolean }[];
}

interface Filters {
    search?: string;
    category_id?: string;
    stock?: string;
    sort_by?: string;
    sort_dir?: string;
}

const props = defineProps<{
    products: Pagination;
    filters: Filters;
    categories: Category[];
}>();

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Dashboard', href: '/dashboard' },
    { title: 'Products', href: index().url },
];

const search = ref(props.filters.search ?? '');
const selectedCategory = ref(props.filters.category_id ?? '');
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

watch(selectedCategory, (value) => {
    router.get(
        index().url,
        {
            ...props.filters,
            search: search.value || undefined,
            category_id: value || undefined,
        },
        { preserveState: true, replace: true },
    );
});

function applyStock(stock: string | undefined): void {
    router.get(
        index().url,
        { ...props.filters, search: search.value || undefined, stock },
        { preserveState: true, replace: true },
    );
}

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

function confirmDelete(product: Product): void {
    if (confirm(`Delete "${product.name}"? This cannot be undone.`)) {
        router.delete(destroy(product).url);
    }
}

function buildExportUrl(format: 'csv' | 'pdf'): string {
    return exportMethod.url({
        query: {
            format,
            search: props.filters.search || undefined,
            category_id: props.filters.category_id || undefined,
            stock: props.filters.stock || undefined,
            sort_by: props.filters.sort_by || undefined,
            sort_dir: props.filters.sort_dir || undefined,
        },
    });
}

const isFiltered =
    props.filters.search || props.filters.category_id || props.filters.stock;

// ── Bulk selection ────────────────────────────────────────────────────────────

const selectedIds = ref<number[]>([]);

watch(() => props.products.data, () => {
    selectedIds.value = [];
});

const allOnPageSelected = computed(
    () =>
        props.products.data.length > 0 &&
        props.products.data.every((p) => selectedIds.value.includes(p.id)),
);

const someOnPageSelected = computed(
    () => props.products.data.some((p) => selectedIds.value.includes(p.id)) && !allOnPageSelected.value,
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
    const pageIds = props.products.data.map((p) => p.id);
    if (allOnPageSelected.value) {
        selectedIds.value = selectedIds.value.filter((id) => !pageIds.includes(id));
    } else {
        const newIds = pageIds.filter((id) => !selectedIds.value.includes(id));
        selectedIds.value = [...selectedIds.value, ...newIds];
    }
}

// ── Bulk actions ──────────────────────────────────────────────────────────────

type ModalType = 'assign_category' | 'update_stock' | 'update_price' | null;

const showModal = ref<ModalType>(null);
const bulkCategoryId = ref<string>('');
const bulkStockQuantity = ref<string>('0');
const bulkPriceInput = ref<string>('');
const processing = ref(false);
const bulkErrors = ref<Record<string, string>>({});

function handleBulkAction(action: string): void {
    bulkErrors.value = {};

    if (action === 'delete') {
        const n = selectedIds.value.length;
        if (!confirm(`Delete ${n} selected product${n !== 1 ? 's' : ''}? This cannot be undone.`)) return;
        submitBulkAction('delete');
    } else if (action === 'activate' || action === 'deactivate') {
        submitBulkAction(action);
    } else {
        showModal.value = action as ModalType;
    }
}

function submitBulkAction(action: string, extra: Record<string, unknown> = {}): void {
    processing.value = true;
    bulkErrors.value = {};

    router.post(
        bulkAction().url,
        { product_ids: selectedIds.value, action, ...extra },
        {
            onFinish: () => {
                processing.value = false;
            },
            onSuccess: () => {
                selectedIds.value = [];
                showModal.value = null;
            },
            onError: (errs) => {
                bulkErrors.value = errs;
            },
        },
    );
}

function submitAssignCategory(): void {
    submitBulkAction('assign_category', { category_id: bulkCategoryId.value || null });
}

function submitUpdateStock(): void {
    submitBulkAction('update_stock', { stock_quantity: parseInt(bulkStockQuantity.value, 10) });
}

function submitUpdatePrice(): void {
    const dollars = parseFloat(bulkPriceInput.value);
    submitBulkAction('update_price', { price: isNaN(dollars) ? null : Math.round(dollars * 100) });
}
</script>

<template>
    <Head title="Products" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex flex-col gap-6 p-4">
            <!-- Header -->
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-2xl font-semibold">Products</h1>
                    <p class="text-sm text-muted-foreground">
                        {{ products.total }} total products
                    </p>
                </div>
                <div class="flex items-center gap-2">
                    <a :href="buildExportUrl('csv')" download>
                        <Button variant="outline" size="sm">
                            <Download class="mr-1.5 size-4" />
                            CSV
                        </Button>
                    </a>
                    <a :href="buildExportUrl('pdf')" target="_blank" rel="noopener">
                        <Button variant="outline" size="sm">
                            <FileText class="mr-1.5 size-4" />
                            PDF
                        </Button>
                    </a>
                    <Link :href="create().url">
                        <Button>
                            <PackagePlus class="mr-2 size-4" />
                            Add Product
                        </Button>
                    </Link>
                </div>
            </div>

            <!-- Filters -->
            <div class="flex flex-wrap items-center gap-3">
                <Input
                    v-model="search"
                    placeholder="Search by name or SKU..."
                    class="max-w-xs"
                />

                <!-- Stock filters -->
                <Button
                    size="sm"
                    :variant="!filters.stock ? 'default' : 'outline'"
                    @click="applyStock(undefined)"
                >
                    All Stock
                </Button>
                <Button
                    size="sm"
                    :variant="filters.stock === 'in_stock' ? 'default' : 'outline'"
                    @click="applyStock('in_stock')"
                >
                    In Stock
                </Button>
                <Button
                    size="sm"
                    :variant="filters.stock === 'low_stock' ? 'default' : 'outline'"
                    @click="applyStock('low_stock')"
                >
                    Low Stock
                </Button>
                <Button
                    size="sm"
                    :variant="filters.stock === 'out_of_stock' ? 'default' : 'outline'"
                    @click="applyStock('out_of_stock')"
                >
                    Out of Stock
                </Button>

                <!-- Category dropdown -->
                <select
                    v-if="categories.length > 0"
                    v-model="selectedCategory"
                    class="h-9 rounded-md border border-input bg-background px-3 py-1 text-sm shadow-sm transition-colors focus:outline-none focus:ring-1 focus:ring-ring"
                >
                    <option value="">All Categories</option>
                    <option
                        v-for="cat in categories"
                        :key="cat.id"
                        :value="String(cat.id)"
                    >
                        {{ cat.name }}
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
                    {{ selectedIds.length === 1 ? 'product' : 'products' }} selected
                </span>
                <div class="ml-auto flex flex-wrap items-center gap-2">
                    <Button
                        size="sm"
                        variant="outline"
                        :disabled="processing"
                        @click="handleBulkAction('activate')"
                    >
                        Activate
                    </Button>
                    <Button
                        size="sm"
                        variant="outline"
                        :disabled="processing"
                        @click="handleBulkAction('deactivate')"
                    >
                        Deactivate
                    </Button>
                    <Button
                        size="sm"
                        variant="outline"
                        :disabled="processing"
                        @click="handleBulkAction('assign_category')"
                    >
                        Assign Category
                    </Button>
                    <Button
                        size="sm"
                        variant="outline"
                        :disabled="processing"
                        @click="handleBulkAction('update_stock')"
                    >
                        Update Stock
                    </Button>
                    <Button
                        size="sm"
                        variant="outline"
                        :disabled="processing"
                        @click="handleBulkAction('update_price')"
                    >
                        Update Price
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
            <div class="overflow-hidden rounded-lg border border-sidebar-border">
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
                                    @click="applySort('name')"
                                >
                                    Name
                                    <ChevronUp v-if="sortDir('name') === 'asc'" class="size-3.5" />
                                    <ChevronDown v-else-if="sortDir('name') === 'desc'" class="size-3.5" />
                                    <ChevronsUpDown v-else class="size-3.5 opacity-40" />
                                </button>
                            </th>
                            <th class="px-4 py-3 text-left font-medium">
                                <button
                                    class="flex items-center gap-1 hover:text-foreground"
                                    @click="applySort('sku')"
                                >
                                    SKU
                                    <ChevronUp v-if="sortDir('sku') === 'asc'" class="size-3.5" />
                                    <ChevronDown v-else-if="sortDir('sku') === 'desc'" class="size-3.5" />
                                    <ChevronsUpDown v-else class="size-3.5 opacity-40" />
                                </button>
                            </th>
                            <th class="px-4 py-3 text-left font-medium">
                                <button
                                    class="flex items-center gap-1 hover:text-foreground"
                                    @click="applySort('price')"
                                >
                                    Price
                                    <ChevronUp v-if="sortDir('price') === 'asc'" class="size-3.5" />
                                    <ChevronDown v-else-if="sortDir('price') === 'desc'" class="size-3.5" />
                                    <ChevronsUpDown v-else class="size-3.5 opacity-40" />
                                </button>
                            </th>
                            <th class="px-4 py-3 text-left font-medium">
                                <button
                                    class="flex items-center gap-1 hover:text-foreground"
                                    @click="applySort('stock_quantity')"
                                >
                                    Stock
                                    <ChevronUp v-if="sortDir('stock_quantity') === 'asc'" class="size-3.5" />
                                    <ChevronDown v-else-if="sortDir('stock_quantity') === 'desc'" class="size-3.5" />
                                    <ChevronsUpDown v-else class="size-3.5 opacity-40" />
                                </button>
                            </th>
                            <th class="px-4 py-3 text-left font-medium">
                                Categories
                            </th>
                            <th class="px-4 py-3 text-left font-medium">
                                <button
                                    class="flex items-center gap-1 hover:text-foreground"
                                    @click="applySort('is_active')"
                                >
                                    Status
                                    <ChevronUp v-if="sortDir('is_active') === 'asc'" class="size-3.5" />
                                    <ChevronDown v-else-if="sortDir('is_active') === 'desc'" class="size-3.5" />
                                    <ChevronsUpDown v-else class="size-3.5 opacity-40" />
                                </button>
                            </th>
                            <th class="px-4 py-3 text-right font-medium">
                                Actions
                            </th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-sidebar-border">
                        <tr v-if="products.data.length === 0">
                            <td
                                colspan="8"
                                class="px-4 py-8 text-center text-muted-foreground"
                            >
                                <template v-if="isFiltered">
                                    No products found.
                                </template>
                                <template v-else>
                                    No products yet.
                                    <Link
                                        :href="create().url"
                                        class="ml-1 text-primary underline"
                                        >Add your first product</Link
                                    >
                                </template>
                            </td>
                        </tr>
                        <tr
                            v-for="product in products.data"
                            :key="product.id"
                            class="transition-colors hover:bg-muted/30"
                            :class="{ 'bg-primary/5': isSelected(product.id) }"
                        >
                            <td class="w-10 px-4 py-3">
                                <input
                                    type="checkbox"
                                    :checked="isSelected(product.id)"
                                    class="size-4 cursor-pointer rounded accent-primary"
                                    @change="toggleSelect(product.id)"
                                />
                            </td>
                            <td class="px-4 py-3 font-medium">
                                {{ product.name }}
                            </td>
                            <td
                                class="px-4 py-3 font-mono text-xs text-muted-foreground"
                            >
                                {{ product.sku ?? '—' }}
                            </td>
                            <td class="px-4 py-3">
                                ${{ formatPrice(product.price) }}
                            </td>
                            <td class="px-4 py-3">
                                <span
                                    :class="
                                        product.stock_quantity === 0
                                            ? 'font-medium text-destructive'
                                            : ''
                                    "
                                >
                                    {{ product.stock_quantity }}
                                </span>
                            </td>
                            <td class="px-4 py-3">
                                <div class="flex flex-wrap gap-1">
                                    <Badge
                                        v-for="cat in product.categories"
                                        :key="cat.id"
                                        variant="secondary"
                                        class="text-xs"
                                    >
                                        {{ cat.name }}
                                    </Badge>
                                    <span
                                        v-if="product.categories.length === 0"
                                        class="text-muted-foreground"
                                        >—</span
                                    >
                                </div>
                            </td>
                            <td class="px-4 py-3">
                                <Badge
                                    :variant="
                                        product.is_active
                                            ? 'default'
                                            : 'secondary'
                                    "
                                >
                                    {{
                                        product.is_active
                                            ? 'Active'
                                            : 'Inactive'
                                    }}
                                </Badge>
                            </td>
                            <td class="px-4 py-3">
                                <div class="flex justify-end gap-2">
                                    <Link :href="show(product).url">
                                        <Button variant="ghost" size="sm">
                                            <Eye class="size-4" />
                                        </Button>
                                    </Link>
                                    <Link :href="edit(product).url">
                                        <Button variant="ghost" size="sm">
                                            <Pencil class="size-4" />
                                        </Button>
                                    </Link>
                                    <Button
                                        variant="ghost"
                                        size="sm"
                                        class="text-destructive hover:text-destructive"
                                        @click="confirmDelete(product)"
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
            <div
                v-if="products.last_page > 1"
                class="flex justify-center gap-1"
            >
                <template v-for="link in products.links" :key="link.label">
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

        <!-- Assign Category Modal -->
        <div
            v-if="showModal === 'assign_category'"
            class="fixed inset-0 z-50 flex items-center justify-center bg-black/50"
            @click.self="showModal = null"
        >
            <div class="w-full max-w-sm rounded-lg border border-sidebar-border bg-background p-6 shadow-lg">
                <h2 class="mb-1 text-lg font-semibold">Assign Category</h2>
                <p class="mb-4 text-sm text-muted-foreground">
                    Add a category to {{ selectedIds.length }} selected
                    {{ selectedIds.length === 1 ? 'product' : 'products' }}.
                </p>
                <select
                    v-model="bulkCategoryId"
                    class="mb-1 h-9 w-full rounded-md border border-input bg-background px-3 py-1 text-sm shadow-sm focus:outline-none focus:ring-1 focus:ring-ring"
                >
                    <option value="">Select a category…</option>
                    <option
                        v-for="cat in categories"
                        :key="cat.id"
                        :value="String(cat.id)"
                    >
                        {{ cat.name }}
                    </option>
                </select>
                <p v-if="bulkErrors.category_id" class="mb-3 text-xs text-destructive">
                    {{ bulkErrors.category_id }}
                </p>
                <div class="mt-4 flex justify-end gap-2">
                    <Button variant="outline" @click="showModal = null">Cancel</Button>
                    <Button :disabled="processing" @click="submitAssignCategory">Apply</Button>
                </div>
            </div>
        </div>

        <!-- Update Stock Modal -->
        <div
            v-if="showModal === 'update_stock'"
            class="fixed inset-0 z-50 flex items-center justify-center bg-black/50"
            @click.self="showModal = null"
        >
            <div class="w-full max-w-sm rounded-lg border border-sidebar-border bg-background p-6 shadow-lg">
                <h2 class="mb-1 text-lg font-semibold">Update Stock</h2>
                <p class="mb-4 text-sm text-muted-foreground">
                    Set the stock quantity for {{ selectedIds.length }} selected
                    {{ selectedIds.length === 1 ? 'product' : 'products' }}.
                </p>
                <Input
                    v-model="bulkStockQuantity"
                    type="number"
                    min="0"
                    placeholder="0"
                    class="mb-1"
                />
                <p v-if="bulkErrors.stock_quantity" class="mb-3 text-xs text-destructive">
                    {{ bulkErrors.stock_quantity }}
                </p>
                <div class="mt-4 flex justify-end gap-2">
                    <Button variant="outline" @click="showModal = null">Cancel</Button>
                    <Button :disabled="processing" @click="submitUpdateStock">Apply</Button>
                </div>
            </div>
        </div>

        <!-- Update Price Modal -->
        <div
            v-if="showModal === 'update_price'"
            class="fixed inset-0 z-50 flex items-center justify-center bg-black/50"
            @click.self="showModal = null"
        >
            <div class="w-full max-w-sm rounded-lg border border-sidebar-border bg-background p-6 shadow-lg">
                <h2 class="mb-1 text-lg font-semibold">Update Price</h2>
                <p class="mb-4 text-sm text-muted-foreground">
                    Set the price for {{ selectedIds.length }} selected
                    {{ selectedIds.length === 1 ? 'product' : 'products' }}.
                </p>
                <div class="relative mb-1">
                    <span class="absolute left-3 top-1/2 -translate-y-1/2 text-sm text-muted-foreground">$</span>
                    <Input
                        v-model="bulkPriceInput"
                        type="number"
                        min="0"
                        step="0.01"
                        placeholder="0.00"
                        class="pl-7"
                    />
                </div>
                <p v-if="bulkErrors.price" class="mb-3 text-xs text-destructive">
                    {{ bulkErrors.price }}
                </p>
                <div class="mt-4 flex justify-end gap-2">
                    <Button variant="outline" @click="showModal = null">Cancel</Button>
                    <Button :disabled="processing" @click="submitUpdatePrice">Apply</Button>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
