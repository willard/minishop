<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import { Eye, PackagePlus, Pencil, Trash2 } from 'lucide-vue-next';
import AppLayout from '@/layouts/AppLayout.vue';
import { Button } from '@/components/ui/button';
import { Badge } from '@/components/ui/badge';
import { type BreadcrumbItem } from '@/types';
import { index, create, show, edit, destroy } from '@/actions/App/Http/Controllers/Admin/ProductController';

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

defineProps<{
    products: Pagination;
}>();

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Dashboard', href: '/dashboard' },
    { title: 'Products', href: index().url },
];

function formatPrice(cents: number): string {
    return (cents / 100).toFixed(2);
}

function confirmDelete(product: Product): void {
    if (confirm(`Delete "${product.name}"? This cannot be undone.`)) {
        router.delete(destroy(product).url);
    }
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
                    <p class="text-sm text-muted-foreground">{{ products.total }} total products</p>
                </div>
                <Link :href="create().url">
                    <Button>
                        <PackagePlus class="mr-2 size-4" />
                        Add Product
                    </Button>
                </Link>
            </div>

            <!-- Table -->
            <div class="rounded-lg border border-sidebar-border overflow-hidden">
                <table class="w-full text-sm">
                    <thead class="bg-muted/50 text-muted-foreground">
                        <tr>
                            <th class="px-4 py-3 text-left font-medium">Name</th>
                            <th class="px-4 py-3 text-left font-medium">SKU</th>
                            <th class="px-4 py-3 text-left font-medium">Price</th>
                            <th class="px-4 py-3 text-left font-medium">Stock</th>
                            <th class="px-4 py-3 text-left font-medium">Categories</th>
                            <th class="px-4 py-3 text-left font-medium">Status</th>
                            <th class="px-4 py-3 text-right font-medium">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-sidebar-border">
                        <tr v-if="products.data.length === 0">
                            <td colspan="7" class="px-4 py-8 text-center text-muted-foreground">
                                No products yet.
                                <Link :href="create().url" class="text-primary underline ml-1">Add your first product</Link>
                            </td>
                        </tr>
                        <tr
                            v-for="product in products.data"
                            :key="product.id"
                            class="hover:bg-muted/30 transition-colors"
                        >
                            <td class="px-4 py-3 font-medium">{{ product.name }}</td>
                            <td class="px-4 py-3 text-muted-foreground font-mono text-xs">{{ product.sku ?? '—' }}</td>
                            <td class="px-4 py-3">${{ formatPrice(product.price) }}</td>
                            <td class="px-4 py-3">
                                <span :class="product.stock_quantity === 0 ? 'text-destructive font-medium' : ''">
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
                                    <span v-if="product.categories.length === 0" class="text-muted-foreground">—</span>
                                </div>
                            </td>
                            <td class="px-4 py-3">
                                <Badge :variant="product.is_active ? 'default' : 'secondary'">
                                    {{ product.is_active ? 'Active' : 'Inactive' }}
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
            <div v-if="products.last_page > 1" class="flex justify-center gap-1">
                <template v-for="link in products.links" :key="link.label">
                    <Link
                        v-if="link.url"
                        :href="link.url"
                        class="px-3 py-1.5 rounded text-sm border border-sidebar-border hover:bg-muted/50 transition-colors"
                        :class="{ 'bg-primary text-primary-foreground border-primary': link.active }"
                    ><span v-html="link.label" /></Link>
                    <span
                        v-else
                        class="px-3 py-1.5 rounded text-sm border border-sidebar-border text-muted-foreground opacity-50"
                        v-html="link.label"
                    />
                </template>
            </div>
        </div>
    </AppLayout>
</template>
