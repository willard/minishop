<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import { ArrowLeft, Pencil, Trash2 } from 'lucide-vue-next';
import AppLayout from '@/layouts/AppLayout.vue';
import { Button } from '@/components/ui/button';
import { Badge } from '@/components/ui/badge';
import { type BreadcrumbItem } from '@/types';
import { index, edit, destroy } from '@/actions/App/Http/Controllers/Admin/ProductController';

interface Category {
    id: number;
    name: string;
}

interface ProductImage {
    id: number;
    path: string;
    alt_text: string | null;
}

interface Product {
    id: number;
    name: string;
    slug: string;
    description: string | null;
    price: number;
    compare_price: number | null;
    stock_quantity: number;
    is_active: boolean;
    sku: string | null;
    categories: Category[];
    images: ProductImage[];
}

const props = defineProps<{
    product: Product;
}>();

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Dashboard', href: '/dashboard' },
    { title: 'Products', href: index().url },
    { title: props.product.name, href: '#' },
];

function formatPrice(cents: number): string {
    return (cents / 100).toFixed(2);
}

function confirmDelete(): void {
    if (confirm(`Delete "${props.product.name}"? This cannot be undone.`)) {
        router.delete(destroy(props.product).url);
    }
}
</script>

<template>
    <Head :title="product.name" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex flex-col gap-6 p-4 max-w-3xl">
            <!-- Header -->
            <div class="flex items-center justify-between">
                <div class="flex items-center gap-4">
                    <Link :href="index().url">
                        <Button variant="ghost" size="sm">
                            <ArrowLeft class="size-4" />
                        </Button>
                    </Link>
                    <div>
                        <h1 class="text-2xl font-semibold">{{ product.name }}</h1>
                        <p class="text-sm text-muted-foreground font-mono">{{ product.slug }}</p>
                    </div>
                </div>
                <div class="flex gap-2">
                    <Link :href="edit(product).url">
                        <Button variant="outline" size="sm">
                            <Pencil class="mr-2 size-4" />
                            Edit
                        </Button>
                    </Link>
                    <Button
                        variant="destructive"
                        size="sm"
                        @click="confirmDelete"
                    >
                        <Trash2 class="mr-2 size-4" />
                        Delete
                    </Button>
                </div>
            </div>

            <!-- Details -->
            <div class="rounded-lg border border-sidebar-border divide-y divide-sidebar-border">
                <!-- Status & Categories -->
                <div class="grid grid-cols-2 gap-4 px-4 py-3">
                    <div>
                        <p class="text-xs text-muted-foreground uppercase tracking-wide mb-1">Status</p>
                        <Badge :variant="product.is_active ? 'default' : 'secondary'">
                            {{ product.is_active ? 'Active' : 'Inactive' }}
                        </Badge>
                    </div>
                    <div>
                        <p class="text-xs text-muted-foreground uppercase tracking-wide mb-1">Categories</p>
                        <div class="flex flex-wrap gap-1">
                            <Badge
                                v-for="cat in product.categories"
                                :key="cat.id"
                                variant="secondary"
                                class="text-xs"
                            >
                                {{ cat.name }}
                            </Badge>
                            <span v-if="product.categories.length === 0" class="text-sm text-muted-foreground">—</span>
                        </div>
                    </div>
                </div>

                <!-- Pricing -->
                <div class="grid grid-cols-2 gap-4 px-4 py-3">
                    <div>
                        <p class="text-xs text-muted-foreground uppercase tracking-wide mb-1">Price</p>
                        <p class="font-medium">${{ formatPrice(product.price) }}</p>
                    </div>
                    <div v-if="product.compare_price">
                        <p class="text-xs text-muted-foreground uppercase tracking-wide mb-1">Compare Price</p>
                        <p class="font-medium line-through text-muted-foreground">${{ formatPrice(product.compare_price) }}</p>
                    </div>
                </div>

                <!-- SKU & Stock -->
                <div class="grid grid-cols-2 gap-4 px-4 py-3">
                    <div>
                        <p class="text-xs text-muted-foreground uppercase tracking-wide mb-1">SKU</p>
                        <p class="font-mono text-sm">{{ product.sku ?? '—' }}</p>
                    </div>
                    <div>
                        <p class="text-xs text-muted-foreground uppercase tracking-wide mb-1">Stock</p>
                        <p :class="product.stock_quantity === 0 ? 'text-destructive font-medium' : ''">
                            {{ product.stock_quantity }} units
                        </p>
                    </div>
                </div>

                <!-- Description -->
                <div v-if="product.description" class="px-4 py-3">
                    <p class="text-xs text-muted-foreground uppercase tracking-wide mb-1">Description</p>
                    <p class="text-sm whitespace-pre-wrap">{{ product.description }}</p>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
