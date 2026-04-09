<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import { useDebounceFn } from '@vueuse/core';
import { Search, SlidersHorizontal, Eye } from 'lucide-vue-next';
import { ref, watch } from 'vue';
import {
    index as productsIndex,
    show as productShow,
} from '@/actions/App/Http/Controllers/Storefront/ProductController';
import QuickView from '@/components/storefront/QuickView.vue';
import { useCart } from '@/composables/useCart';
import { usePrice } from '@/composables/usePrice';
import StorefrontLayout from '@/layouts/StorefrontLayout.vue';
import type {
    StorefrontProduct,
    StorefrontCategory,
    StorefrontTag,
    PaginatedProducts,
} from '@/types/storefront';

const props = defineProps<{
    products: PaginatedProducts;
    categories: StorefrontCategory[];
    tags: StorefrontTag[];
    filters: { category?: string; tag?: string; search?: string; price_min?: string; price_max?: string; stock?: string };
}>();

const { addItem, lastAddedItem } = useCart();
const { formatPrice } = usePrice();
const search = ref(props.filters.search ?? '');
const priceMin = ref(props.filters.price_min ?? '');
const priceMax = ref(props.filters.price_max ?? '');
const showFilters = ref(false);

const isQuickViewOpen = ref(false);
const selectedProduct = ref<StorefrontProduct | null>(null);

function openQuickView(product: StorefrontProduct): void {
    selectedProduct.value = product;
    isQuickViewOpen.value = true;
}

const debouncedSearch = useDebounceFn(() => {
    router.get(
        productsIndex().url,
        {
            ...props.filters,
            search: search.value || undefined,
        },
        {
            preserveState: true,
            preserveScroll: true,
            replace: true,
        },
    );
}, 300);

watch(search, debouncedSearch);

const debouncedPrice = useDebounceFn(() => {
    router.get(
        productsIndex().url,
        {
            ...props.filters,
            price_min: priceMin.value || undefined,
            price_max: priceMax.value || undefined,
        },
        {
            preserveState: true,
            preserveScroll: true,
            replace: true,
        },
    );
}, 500);

watch([priceMin, priceMax], debouncedPrice);

function getProductImage(product: StorefrontProduct): string | null {
    return product.images?.[0]?.url ?? null;
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
    <Head title="Our Collection" />

    <StorefrontLayout :categories="categories">
        <div class="mx-auto max-w-7xl px-6 py-12">
            <!-- Header -->
            <div
                class="mb-10 flex flex-col justify-between gap-6 md:flex-row md:items-end"
            >
                <div>
                    <h1
                        class="mb-3 text-4xl font-semibold md:text-5xl"
                        style="
                            font-family: 'Cormorant Garamond', serif;
                            color: #1c1a17;
                        "
                    >
                        Our Collection
                    </h1>
                    <p class="text-sm opacity-60">
                        Showing {{ products.from }}-{{ products.to }} of
                        {{ products.total }} products
                    </p>
                </div>

                <div class="flex items-center gap-3">
                    <!-- Search -->
                    <div class="relative w-full md:w-64">
                        <Search
                            class="absolute top-1/2 left-3 size-4 -translate-y-1/2 opacity-30"
                        />
                        <input
                            v-model="search"
                            type="text"
                            placeholder="Search products..."
                            class="w-full rounded-full border bg-transparent py-2 pr-4 pl-10 text-sm focus:ring-1 focus:outline-none"
                            style="
                                border-color: rgba(28, 26, 23, 0.2);
                                ring-color: #1c1a17;
                            "
                        />
                    </div>

                    <!-- Filter toggle -->
                    <button
                        class="flex items-center gap-2 rounded-full border px-5 py-2 text-sm font-medium transition-all"
                        style="border-color: rgba(28, 26, 23, 0.2)"
                        @click="showFilters = !showFilters"
                    >
                        <SlidersHorizontal class="size-4" />
                        Filters
                    </button>
                </div>
            </div>

            <!-- Filters -->
            <div
                v-if="showFilters"
                class="mb-10 rounded-2xl border p-6"
                style="
                    border-color: rgba(28, 26, 23, 0.1);
                    background-color: rgba(28, 26, 23, 0.02);
                "
            >
                <p
                    class="mb-4 text-xs font-semibold tracking-widest uppercase opacity-40"
                >
                    Categories
                </p>
                <div class="flex flex-wrap gap-2">
                    <Link
                        :href="
                            productsIndex({
                                query: { ...filters, category: undefined },
                            }).url
                        "
                        class="rounded-full border px-4 py-1.5 text-xs font-medium transition-all"
                        :style="
                            !filters.category
                                ? 'background-color: #1c1a17; color: #f9f6f0; border-color: #1c1a17'
                                : 'border-color: rgba(28, 26, 23, 0.2)'
                        "
                    >
                        All Products
                    </Link>
                    <Link
                        v-for="category in categories"
                        :key="category.id"
                        :href="
                            productsIndex({
                                query: { ...filters, category: category.slug },
                            }).url
                        "
                        class="rounded-full border px-4 py-1.5 text-xs font-medium transition-all"
                        :style="
                            filters.category === category.slug
                                ? 'background-color: #1c1a17; color: #f9f6f0; border-color: #1c1a17'
                                : 'border-color: rgba(28, 26, 23, 0.2)'
                        "
                    >
                        {{ category.name }}
                    </Link>
                </div>

                <template v-if="tags.length > 0">
                    <p
                        class="mb-4 mt-6 text-xs font-semibold tracking-widest uppercase opacity-40"
                    >
                        Tags
                    </p>
                    <div class="flex flex-wrap gap-2">
                        <Link
                            :href="
                                productsIndex({
                                    query: { ...filters, tag: undefined },
                                }).url
                            "
                            class="rounded-full border px-4 py-1.5 text-xs font-medium transition-all"
                            :style="
                                !filters.tag
                                    ? 'background-color: #1c1a17; color: #f9f6f0; border-color: #1c1a17'
                                    : 'border-color: rgba(28, 26, 23, 0.2)'
                            "
                        >
                            All Tags
                        </Link>
                        <Link
                            v-for="tag in tags"
                            :key="tag.id"
                            :href="
                                productsIndex({
                                    query: { ...filters, tag: tag.slug },
                                }).url
                            "
                            class="rounded-full border px-4 py-1.5 text-xs font-medium transition-all"
                            :style="
                                filters.tag === tag.slug
                                    ? tag.color
                                        ? `background-color: ${tag.color}; color: #f9f6f0; border-color: ${tag.color}`
                                        : 'background-color: #1c1a17; color: #f9f6f0; border-color: #1c1a17'
                                    : 'border-color: rgba(28, 26, 23, 0.2)'
                            "
                        >
                            {{ tag.name }}
                        </Link>
                    </div>
                </template>

                <!-- Price range -->
                <p
                    class="mb-4 mt-6 text-xs font-semibold tracking-widest uppercase opacity-40"
                >
                    Price Range
                </p>
                <div class="flex flex-wrap items-center gap-2">
                    <div class="relative">
                        <span
                            class="absolute top-1/2 left-3 -translate-y-1/2 text-sm"
                            style="color: rgba(28, 26, 23, 0.4)"
                        >$</span>
                        <input
                            v-model="priceMin"
                            type="number"
                            min="0"
                            step="0.01"
                            placeholder="Min"
                            class="w-28 rounded-full border bg-transparent py-1.5 pr-3 pl-7 text-sm focus:ring-1 focus:outline-none"
                            style="border-color: rgba(28, 26, 23, 0.2)"
                        />
                    </div>
                    <span style="color: rgba(28, 26, 23, 0.4)">—</span>
                    <div class="relative">
                        <span
                            class="absolute top-1/2 left-3 -translate-y-1/2 text-sm"
                            style="color: rgba(28, 26, 23, 0.4)"
                        >$</span>
                        <input
                            v-model="priceMax"
                            type="number"
                            min="0"
                            step="0.01"
                            placeholder="Max"
                            class="w-28 rounded-full border bg-transparent py-1.5 pr-3 pl-7 text-sm focus:ring-1 focus:outline-none"
                            style="border-color: rgba(28, 26, 23, 0.2)"
                        />
                    </div>
                </div>

                <!-- Availability -->
                <p
                    class="mb-4 mt-6 text-xs font-semibold tracking-widest uppercase opacity-40"
                >
                    Availability
                </p>
                <div class="flex flex-wrap gap-2">
                    <Link
                        :href="productsIndex({ query: { ...filters, stock: undefined } }).url"
                        class="rounded-full border px-4 py-1.5 text-xs font-medium transition-all"
                        :style="
                            !filters.stock
                                ? 'background-color: #1c1a17; color: #f9f6f0; border-color: #1c1a17'
                                : 'border-color: rgba(28, 26, 23, 0.2)'
                        "
                    >
                        All
                    </Link>
                    <Link
                        :href="productsIndex({ query: { ...filters, stock: 'in_stock' } }).url"
                        class="rounded-full border px-4 py-1.5 text-xs font-medium transition-all"
                        :style="
                            filters.stock === 'in_stock'
                                ? 'background-color: #1c1a17; color: #f9f6f0; border-color: #1c1a17'
                                : 'border-color: rgba(28, 26, 23, 0.2)'
                        "
                    >
                        In Stock
                    </Link>
                    <Link
                        :href="productsIndex({ query: { ...filters, stock: 'out_of_stock' } }).url"
                        class="rounded-full border px-4 py-1.5 text-xs font-medium transition-all"
                        :style="
                            filters.stock === 'out_of_stock'
                                ? 'background-color: #1c1a17; color: #f9f6f0; border-color: #1c1a17'
                                : 'border-color: rgba(28, 26, 23, 0.2)'
                        "
                    >
                        Out of Stock
                    </Link>
                </div>
            </div>

            <!-- Product grid -->
            <TransitionGroup
                v-if="products.data.length > 0"
                tag="div"
                enter-active-class="transition duration-700 ease-out"
                enter-from-class="opacity-0 translate-y-4"
                enter-to-class="opacity-100 translate-y-0"
                class="grid grid-cols-1 gap-x-6 gap-y-10 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4"
            >
                <div
                    v-for="(product, index) in products.data"
                    :key="product.id"
                    class="group flex flex-col"
                    :style="{ transitionDelay: `${index * 50}ms` }"
                >
                    <!-- Image container -->
                    <div class="relative mb-4 overflow-hidden rounded-xl">
                        <Link :href="productShow(product).url" class="block">
                            <div
                                class="aspect-square overflow-hidden"
                                style="
                                    background: linear-gradient(
                                        135deg,
                                        #e8dfd4 0%,
                                        #d4c8b8 100%
                                    );
                                "
                            >
                                <img
                                    v-if="getProductImage(product)"
                                    :src="getProductImage(product)!"
                                    :alt="product.name"
                                    class="h-full w-full object-cover transition-transform duration-500 group-hover:scale-105"
                                />
                                <div
                                    v-else
                                    class="flex h-full w-full items-center justify-center"
                                >
                                    <span
                                        class="text-3xl font-medium"
                                        style="
                                            font-family:
                                                'Cormorant Garamond', serif;
                                            color: rgba(28, 26, 23, 0.25);
                                        "
                                    >
                                        {{ product.name.charAt(0) }}
                                    </span>
                                </div>
                            </div>
                        </Link>

                        <!-- Sale badge -->
                        <div
                            v-if="
                                product.compare_price &&
                                product.compare_price > product.price
                            "
                            class="absolute top-3 left-3 rounded-full px-2.5 py-1 text-xs font-semibold text-white"
                            style="background-color: #c05c3a"
                        >
                            Sale
                        </div>

                        <!-- Out of stock overlay -->
                        <div
                            v-if="product.stock_quantity === 0"
                            class="absolute inset-0 flex items-center justify-center rounded-xl"
                            style="background-color: rgba(249, 246, 240, 0.8)"
                        >
                            <span
                                class="text-sm font-semibold tracking-widest uppercase"
                                style="color: rgba(28, 26, 23, 0.5)"
                            >
                                Sold Out
                            </span>
                        </div>

                        <!-- Quick view button (desktop) -->
                        <div
                            class="absolute inset-0 hidden items-center justify-center bg-black/5 opacity-0 transition-opacity group-hover:opacity-100 md:flex"
                        >
                            <button
                                class="flex items-center gap-2 rounded-full bg-white px-5 py-2.5 text-xs font-semibold tracking-widest uppercase shadow-sm transition-transform hover:scale-105 active:scale-95"
                                style="color: #1c1a17"
                                @click="openQuickView(product)"
                            >
                                <Eye class="size-3.5" />
                                Quick View
                            </button>
                        </div>
                    </div>

                    <!-- Product info -->
                    <div class="flex flex-1 flex-col">
                        <div class="mb-1 flex flex-wrap gap-1">
                            <span
                                v-for="cat in product.categories.slice(0, 2)"
                                :key="cat.id"
                                class="text-[11px] tracking-wider uppercase"
                                style="color: rgba(28, 26, 23, 0.45)"
                            >
                                {{ cat.name }}
                            </span>
                            <span
                                v-for="tag in (product.tags ?? []).slice(0, 2)"
                                :key="`t-${tag.id}`"
                                class="rounded-full px-2 py-0.5 text-[10px] font-medium"
                                :style="tag.color
                                    ? { backgroundColor: tag.color, color: '#fff' }
                                    : { backgroundColor: 'rgba(28, 26, 23, 0.08)', color: 'rgba(28, 26, 23, 0.6)' }
                                "
                            >
                                {{ tag.name }}
                            </span>
                        </div>

                        <Link
                            :href="productShow(product).url"
                            class="mb-2 text-base leading-snug font-medium transition-opacity hover:opacity-70"
                            style="color: #1c1a17"
                        >
                            {{ product.name }}
                        </Link>

                        <div
                            class="mt-auto flex items-center justify-between pt-3"
                        >
                            <div class="flex items-baseline gap-2">
                                <span
                                    class="text-base font-semibold"
                                    style="color: #1c1a17"
                                >
                                    {{ formatPrice(product.price) }}
                                </span>
                                <span
                                    v-if="
                                        product.compare_price &&
                                        product.compare_price > product.price
                                    "
                                    class="text-sm line-through"
                                    style="color: rgba(28, 26, 23, 0.4)"
                                >
                                    {{ formatPrice(product.compare_price) }}
                                </span>
                            </div>

                            <button
                                v-if="product.stock_quantity > 0"
                                class="rounded-full px-4 py-1.5 text-xs font-semibold tracking-wider text-white uppercase transition-all hover:opacity-80"
                                :style="{
                                    backgroundColor:
                                        lastAddedItem?.productId === product.id
                                            ? '#4a7c59'
                                            : '#1c1a17',
                                }"
                                @click="handleAddToCart(product)"
                            >
                                {{
                                    lastAddedItem?.productId === product.id
                                        ? 'Added!'
                                        : 'Add'
                                }}
                            </button>
                        </div>
                    </div>
                </div>
            </TransitionGroup>

            <!-- Empty state -->
            <div v-else class="py-24 text-center">
                <p class="text-xl" style="color: rgba(28, 26, 23, 0.5)">
                    No products found matching your criteria.
                </p>
                <Link
                    :href="productsIndex().url"
                    class="mt-4 inline-block text-sm font-semibold tracking-widest uppercase underline underline-offset-4"
                >
                    Clear all filters
                </Link>
            </div>

            <!-- Pagination -->
            <div
                v-if="products.last_page > 1"
                class="mt-16 flex justify-center gap-2"
            >
                <Link
                    v-for="link in products.links"
                    :key="link.label"
                    :href="link.url || '#'"
                    class="flex size-10 items-center justify-center rounded-full border text-sm transition-all"
                    :class="{ 'pointer-events-none opacity-50': !link.url }"
                    :style="
                        link.active
                            ? 'background-color: #1c1a17; color: #f9f6f0; border-color: #1c1a17'
                            : 'border-color: rgba(28, 26, 23, 0.2); color: #1c1a17'
                    "
                    preserve-scroll
                    ><span v-html="link.label"
                /></Link>
            </div>
        </div>

        <QuickView
            :product="selectedProduct"
            :is-open="isQuickViewOpen"
            @close="isQuickViewOpen = false"
        />
    </StorefrontLayout>
</template>
