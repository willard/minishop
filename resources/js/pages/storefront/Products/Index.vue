<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import { Search, SlidersHorizontal } from 'lucide-vue-next';
import { ref, watch } from 'vue';
import { useDebounceFn } from '@vueuse/core';
import StorefrontLayout from '@/layouts/StorefrontLayout.vue';
import { useCart } from '@/composables/useCart';
import { formatPrice } from '@/lib/utils';
import { index, show as productShow } from '@/actions/App/Http/Controllers/Storefront/ProductController';
import type { StorefrontProduct, StorefrontCategory, PaginatedProducts } from '@/types/storefront';

const props = defineProps<{
    products: PaginatedProducts;
    categories: StorefrontCategory[];
    filters: { category?: string; search?: string };
}>();

const { addItem } = useCart();
const search = ref(props.filters.search ?? '');
const showFilters = ref(false);

const debouncedSearch = useDebounceFn(() => {
    router.get(
        index().url,
        { search: search.value || undefined, category: props.filters.category || undefined },
        { preserveState: true, replace: true },
    );
}, 400);

watch(search, debouncedSearch);

function filterByCategory(slug?: string): void {
    router.get(
        index().url,
        { category: slug || undefined, search: search.value || undefined },
        { preserveState: true, replace: true },
    );
}

function getProductImage(product: StorefrontProduct): string | null {
    return product.images?.[0]?.path ?? null;
}

function handleAddToCart(product: StorefrontProduct): void {
    addItem({
        productId: product.id,
        variantId: null,
        name: product.name,
        slug: product.slug,
        sku: product.sku,
        price: product.price,
        image: getProductImage(product),
        variantLabel: null,
    });
}
</script>

<template>
    <Head title="Shop All Products" />

    <StorefrontLayout :categories="categories">
        <!-- Page header -->
        <section class="border-b px-6 py-10" style="border-color: rgba(28, 26, 23, 0.1)">
            <div class="mx-auto max-w-7xl">
                <h1
                    class="mb-2 text-4xl font-semibold md:text-5xl"
                    style="font-family: 'Cormorant Garamond', serif; color: #1c1a17"
                >
                    {{ filters.category ? categories.find((c) => c.slug === filters.category)?.name ?? 'Products' : 'All Products' }}
                </h1>
                <p class="text-sm" style="color: rgba(28, 26, 23, 0.5)">
                    {{ products.total }} {{ products.total === 1 ? 'product' : 'products' }}
                </p>
            </div>
        </section>

        <div class="mx-auto max-w-7xl px-6 py-10">
            <!-- Toolbar: search + category filters -->
            <div class="mb-8 flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                <!-- Search -->
                <div class="relative max-w-xs flex-1">
                    <Search
                        class="absolute left-3 top-1/2 size-4 -translate-y-1/2"
                        style="color: rgba(28, 26, 23, 0.4)"
                    />
                    <input
                        v-model="search"
                        type="text"
                        placeholder="Search products…"
                        class="w-full rounded-full border py-2.5 pl-10 pr-4 text-sm outline-none transition-colors focus:ring-1"
                        style="border-color: rgba(28, 26, 23, 0.2); background-color: #f9f6f0; color: #1c1a17; --tw-ring-color: rgba(28, 26, 23, 0.4)"
                    />
                </div>

                <!-- Filter toggle (mobile) -->
                <button
                    class="flex items-center gap-2 text-sm font-medium sm:hidden"
                    style="color: #1c1a17"
                    @click="showFilters = !showFilters"
                >
                    <SlidersHorizontal class="size-4" />
                    Filters
                </button>

                <!-- Desktop category pills -->
                <div class="hidden flex-wrap items-center gap-2 sm:flex">
                    <button
                        class="rounded-full border px-4 py-1.5 text-xs font-semibold uppercase tracking-wider transition-all"
                        :style="
                            !filters.category
                                ? 'background-color: #1c1a17; color: #f9f6f0; border-color: #1c1a17'
                                : 'border-color: rgba(28, 26, 23, 0.2); color: #1c1a17'
                        "
                        @click="filterByCategory(undefined)"
                    >
                        All
                    </button>
                    <button
                        v-for="category in categories"
                        :key="category.id"
                        class="rounded-full border px-4 py-1.5 text-xs font-semibold uppercase tracking-wider transition-all"
                        :style="
                            filters.category === category.slug
                                ? 'background-color: #1c1a17; color: #f9f6f0; border-color: #1c1a17'
                                : 'border-color: rgba(28, 26, 23, 0.2); color: #1c1a17'
                        "
                        @click="filterByCategory(category.slug)"
                    >
                        {{ category.name }}
                    </button>
                </div>
            </div>

            <!-- Mobile filters panel -->
            <div v-if="showFilters" class="mb-6 flex flex-wrap gap-2 sm:hidden">
                <button
                    class="rounded-full border px-4 py-1.5 text-xs font-semibold uppercase tracking-wider"
                    :style="
                        !filters.category
                            ? 'background-color: #1c1a17; color: #f9f6f0; border-color: #1c1a17'
                            : 'border-color: rgba(28, 26, 23, 0.2); color: #1c1a17'
                    "
                    @click="filterByCategory(undefined)"
                >
                    All
                </button>
                <button
                    v-for="category in categories"
                    :key="category.id"
                    class="rounded-full border px-4 py-1.5 text-xs font-semibold uppercase tracking-wider"
                    :style="
                        filters.category === category.slug
                            ? 'background-color: #1c1a17; color: #f9f6f0; border-color: #1c1a17'
                            : 'border-color: rgba(28, 26, 23, 0.2); color: #1c1a17'
                    "
                    @click="filterByCategory(category.slug)"
                >
                    {{ category.name }}
                </button>
            </div>

            <!-- Product grid -->
            <div
                v-if="products.data.length > 0"
                class="grid grid-cols-1 gap-6 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4"
            >
                <div
                    v-for="product in products.data"
                    :key="product.id"
                    class="group flex flex-col"
                >
                    <!-- Image -->
                    <Link
                        :href="productShow(product).url"
                        class="relative mb-4 block overflow-hidden rounded-xl"
                    >
                        <div
                            class="aspect-square overflow-hidden"
                            style="background: linear-gradient(135deg, #e8dfd4 0%, #d4c8b8 100%)"
                        >
                            <img
                                v-if="getProductImage(product)"
                                :src="getProductImage(product)!"
                                :alt="product.name"
                                class="h-full w-full object-cover transition-transform duration-500 group-hover:scale-105"
                            />
                            <div v-else class="flex h-full w-full items-center justify-center">
                                <span
                                    class="text-3xl font-medium"
                                    style="font-family: 'Cormorant Garamond', serif; color: rgba(28, 26, 23, 0.25)"
                                >
                                    {{ product.name.charAt(0) }}
                                </span>
                            </div>
                        </div>

                        <!-- Sale badge -->
                        <div
                            v-if="product.compare_price && product.compare_price > product.price"
                            class="absolute left-3 top-3 rounded-full px-2.5 py-1 text-xs font-semibold text-white"
                            style="background-color: #c05c3a"
                        >
                            Sale
                        </div>

                        <!-- Out of stock -->
                        <div
                            v-if="product.stock_quantity === 0"
                            class="absolute inset-0 flex items-center justify-center rounded-xl"
                            style="background-color: rgba(249, 246, 240, 0.8)"
                        >
                            <span class="text-sm font-semibold uppercase tracking-widest" style="color: rgba(28, 26, 23, 0.5)">
                                Sold Out
                            </span>
                        </div>
                    </Link>

                    <!-- Info -->
                    <div class="flex flex-1 flex-col">
                        <div class="mb-1 flex flex-wrap gap-1">
                            <span
                                v-for="cat in product.categories.slice(0, 2)"
                                :key="cat.id"
                                class="text-[11px] uppercase tracking-wider"
                                style="color: rgba(28, 26, 23, 0.45)"
                            >
                                {{ cat.name }}
                            </span>
                        </div>

                        <Link
                            :href="productShow(product).url"
                            class="mb-2 text-base font-medium leading-snug transition-opacity hover:opacity-70"
                            style="color: #1c1a17"
                        >
                            {{ product.name }}
                        </Link>

                        <div class="mt-auto flex items-center justify-between pt-3">
                            <div class="flex items-baseline gap-2">
                                <span class="text-base font-semibold" style="color: #1c1a17">
                                    {{ formatPrice(product.price) }}
                                </span>
                                <span
                                    v-if="product.compare_price && product.compare_price > product.price"
                                    class="text-sm line-through"
                                    style="color: rgba(28, 26, 23, 0.4)"
                                >
                                    {{ formatPrice(product.compare_price) }}
                                </span>
                            </div>

                            <button
                                v-if="product.stock_quantity > 0"
                                class="rounded-full px-4 py-1.5 text-xs font-semibold uppercase tracking-wider text-white transition-opacity hover:opacity-80"
                                style="background-color: #1c1a17"
                                @click="handleAddToCart(product)"
                            >
                                Add
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Empty state -->
            <div v-else class="py-24 text-center">
                <p class="mb-2 text-lg font-medium" style="color: #1c1a17">No products found</p>
                <p class="mb-6 text-sm" style="color: rgba(28, 26, 23, 0.5)">
                    Try adjusting your search or browse all categories.
                </p>
                <button
                    class="text-sm font-medium underline underline-offset-4 transition-opacity hover:opacity-60"
                    style="color: #1c1a17"
                    @click="search = ''; filterByCategory(undefined)"
                >
                    Clear filters
                </button>
            </div>

            <!-- Pagination -->
            <div v-if="products.last_page > 1" class="mt-12 flex flex-wrap justify-center gap-2">
                <Link
                    v-for="link in products.links"
                    :key="link.label"
                    :href="link.url ?? '#'"
                    :class="[
                        'flex min-w-10 items-center justify-center rounded-full border px-3 py-2 text-sm transition-all',
                        link.active ? 'font-semibold' : 'hover:opacity-70',
                        !link.url ? 'cursor-not-allowed opacity-30' : '',
                    ]"
                    :style="
                        link.active
                            ? 'background-color: #1c1a17; color: #f9f6f0; border-color: #1c1a17'
                            : 'border-color: rgba(28, 26, 23, 0.2); color: #1c1a17'
                    "
                    preserve-scroll
                    v-html="link.label"
                />
            </div>
        </div>
    </StorefrontLayout>
</template>
