<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import { ChevronDown, ChevronUp, ChevronsUpDown, Download, Eye, FileText, PackagePlus, Pencil, Trash2 } from 'lucide-vue-next';
import { ref, watch } from 'vue';
import {
    index,
    create,
    show,
    edit,
    destroy,
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
    const params = new URLSearchParams();
    if (props.filters.search) params.set('search', props.filters.search);
    if (props.filters.category_id) params.set('category_id', props.filters.category_id);
    if (props.filters.stock) params.set('stock', props.filters.stock);
    if (props.filters.sort_by) params.set('sort_by', props.filters.sort_by);
    if (props.filters.sort_dir) params.set('sort_dir', props.filters.sort_dir);
    params.set('format', format);
    return `/dashboard/products/export?${params.toString()}`;
}

const isFiltered =
    props.filters.search || props.filters.category_id || props.filters.stock;
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
                                colspan="7"
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
                        >
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
    </AppLayout>
</template>
