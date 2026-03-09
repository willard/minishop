<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { ArrowRight, Eye } from 'lucide-vue-next';
import { ref } from 'vue';
import {
    index as productsIndex,
    show as productShow,
} from '@/actions/App/Http/Controllers/Storefront/ProductController';
import QuickView from '@/components/storefront/QuickView.vue';
import { useCart } from '@/composables/useCart';
import { usePrice } from '@/composables/usePrice';
import StorefrontLayout from '@/layouts/StorefrontLayout.vue';
import type { StorefrontProduct, StorefrontCategory } from '@/types/storefront';

defineProps<{
    featuredProducts: StorefrontProduct[];
    categories: StorefrontCategory[];
}>();

const { addItem, lastAddedItem } = useCart();
const { formatPrice } = usePrice();

const isQuickViewOpen = ref(false);
const selectedProduct = ref<StorefrontProduct | null>(null);

function openQuickView(product: StorefrontProduct): void {
    selectedProduct.value = product;
    isQuickViewOpen.value = true;
}

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
    <Head title="Welcome to Minishop" />

    <StorefrontLayout :categories="categories">
        <!-- Hero Section -->
        <section class="relative overflow-hidden px-6 py-24 md:py-36">
            <!-- Decorative background element -->
            <div
                class="pointer-events-none absolute inset-0 opacity-[0.04]"
                style="
                    background-image: radial-gradient(
                        circle at 70% 50%,
                        #c05c3a 0%,
                        transparent 60%
                    );
                "
            />

            <div class="relative mx-auto max-w-7xl">
                <div
                    class="grid grid-cols-1 items-center gap-16 lg:grid-cols-2"
                >
                    <div>
                        <p
                            class="mb-4 text-xs font-semibold tracking-[0.2em] uppercase"
                            style="color: #c05c3a"
                        >
                            New Arrivals
                        </p>
                        <h1
                            class="mb-6 text-5xl leading-[1.1] tracking-tight md:text-6xl lg:text-7xl"
                            style="
                                font-family: 'Cormorant Garamond', serif;
                                color: #1c1a17;
                            "
                        >
                            Crafted for<br />
                            <em>everyday</em><br />
                            living.
                        </h1>
                        <p
                            class="mb-8 max-w-sm text-base leading-relaxed"
                            style="color: rgba(28, 26, 23, 0.6)"
                        >
                            Thoughtfully selected goods for the modern home.
                            Each piece chosen for quality, beauty, and the
                            stories they carry.
                        </p>
                        <Link
                            :href="productsIndex().url"
                            class="inline-flex items-center gap-2 px-8 py-4 text-sm font-semibold tracking-widest text-white uppercase transition-opacity hover:opacity-80"
                            style="background-color: #1c1a17"
                        >
                            Shop All Products
                            <ArrowRight class="size-4" />
                        </Link>
                    </div>

                    <!-- Hero visual — abstract composition of category cards -->
                    <div class="hidden lg:block">
                        <div class="relative h-[480px]">
                            <!-- Main card -->
                            <div
                                class="absolute top-0 left-0 h-72 w-56 rounded-2xl"
                                style="
                                    background: linear-gradient(
                                        135deg,
                                        #e8dfd4 0%,
                                        #d4c8b8 100%
                                    );
                                "
                            />
                            <!-- Offset card -->
                            <div
                                class="absolute right-0 bottom-0 h-80 w-64 rounded-2xl"
                                style="
                                    background: linear-gradient(
                                        135deg,
                                        #c8b8a4 0%,
                                        #b4a490 100%
                                    );
                                "
                            />
                            <!-- Small accent -->
                            <div
                                class="absolute bottom-20 left-40 h-32 w-32 rounded-xl"
                                style="background-color: #c05c3a; opacity: 0.15"
                            />
                            <!-- Category pills -->
                            <div
                                v-for="(category, i) in categories.slice(0, 3)"
                                :key="category.id"
                                class="absolute z-10 rounded-full px-4 py-2 text-xs font-semibold tracking-wider uppercase"
                                :style="{
                                    backgroundColor: '#1c1a17',
                                    color: '#f9f6f0',
                                    top: `${30 + i * 120}px`,
                                    right: `${20 + i * 10}px`,
                                }"
                            >
                                {{ category.name }}
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- Categories row -->
        <section
            v-if="categories.length > 0"
            class="border-y px-6 py-8"
            style="border-color: rgba(28, 26, 23, 0.1)"
        >
            <div class="mx-auto max-w-7xl">
                <div class="flex flex-wrap items-center gap-3">
                    <span
                        class="mr-2 text-xs font-semibold tracking-widest uppercase"
                        style="color: rgba(28, 26, 23, 0.4)"
                    >
                        Browse
                    </span>
                    <Link
                        :href="productsIndex().url"
                        class="rounded-full border px-5 py-2 text-sm font-medium transition-all hover:shadow-sm"
                        style="
                            border-color: rgba(28, 26, 23, 0.2);
                            color: #1c1a17;
                        "
                    >
                        All
                    </Link>
                    <Link
                        v-for="category in categories"
                        :key="category.id"
                        :href="
                            productsIndex({
                                query: { category: category.slug },
                            }).url
                        "
                        class="rounded-full border px-5 py-2 text-sm font-medium transition-all hover:shadow-sm"
                        style="
                            border-color: rgba(28, 26, 23, 0.2);
                            color: #1c1a17;
                        "
                    >
                        {{ category.name }}
                    </Link>
                </div>
            </div>
        </section>

        <!-- Featured products -->
        <section class="px-6 py-16 md:py-20">
            <div class="mx-auto max-w-7xl">
                <div class="mb-12 flex items-end justify-between">
                    <div>
                        <p
                            class="mb-2 text-xs font-semibold tracking-[0.2em] uppercase"
                            style="color: #c05c3a"
                        >
                            Featured
                        </p>
                        <h2
                            class="text-3xl font-semibold md:text-4xl"
                            style="
                                font-family: 'Cormorant Garamond', serif;
                                color: #1c1a17;
                            "
                        >
                            New &amp; Notable
                        </h2>
                    </div>
                    <Link
                        :href="productsIndex().url"
                        class="hidden items-center gap-1 text-sm font-medium underline underline-offset-4 transition-opacity hover:opacity-60 md:flex"
                        style="color: #1c1a17"
                    >
                        View all
                        <ArrowRight class="size-3.5" />
                    </Link>
                </div>

                <TransitionGroup
                    v-if="featuredProducts.length > 0"
                    tag="div"
                    enter-active-class="transition duration-700 ease-out"
                    enter-from-class="opacity-0 translate-y-4"
                    enter-to-class="opacity-100 translate-y-0"
                    class="grid grid-cols-1 gap-6 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4"
                >
                    <div
                        v-for="(product, index) in featuredProducts"
                        :key="product.id"
                        class="group flex flex-col"
                        :style="{ transitionDelay: `${index * 50}ms` }"
                    >
                        <!-- Image -->
                        <div
                            class="relative mb-4 block overflow-hidden rounded-xl"
                        >
                            <Link
                                :href="productShow(product).url"
                                class="block"
                            >
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
                                style="
                                    background-color: rgba(249, 246, 240, 0.8);
                                "
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

                        <!-- Info -->
                        <div class="flex flex-1 flex-col">
                            <div class="mb-1 flex flex-wrap gap-1">
                                <span
                                    v-for="cat in product.categories.slice(
                                        0,
                                        2,
                                    )"
                                    :key="cat.id"
                                    class="text-[11px] tracking-wider uppercase"
                                    style="color: rgba(28, 26, 23, 0.45)"
                                >
                                    {{ cat.name }}
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
                                            product.compare_price >
                                                product.price
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
                                            lastAddedItem?.productId ===
                                            product.id
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

                <div v-else class="py-20 text-center">
                    <p class="text-lg" style="color: rgba(28, 26, 23, 0.5)">
                        No products yet. Check back soon!
                    </p>
                </div>

                <div class="mt-12 text-center md:hidden">
                    <Link
                        :href="productsIndex().url"
                        class="inline-flex items-center gap-2 text-sm font-medium underline underline-offset-4"
                        style="color: #1c1a17"
                    >
                        View all products
                        <ArrowRight class="size-3.5" />
                    </Link>
                </div>
            </div>
        </section>

        <!-- Value proposition banner -->
        <section
            class="mx-6 mb-16 rounded-2xl px-8 py-12 md:mx-auto md:max-w-7xl"
            style="background-color: #1c1a17"
        >
            <div class="grid grid-cols-1 gap-8 text-center md:grid-cols-3">
                <div>
                    <p
                        class="mb-2 text-xl font-semibold"
                        style="
                            font-family: 'Cormorant Garamond', serif;
                            color: #f9f6f0;
                        "
                    >
                        Free Shipping
                    </p>
                    <p class="text-sm" style="color: rgba(249, 246, 240, 0.55)">
                        On orders over ₱2,000
                    </p>
                </div>
                <div>
                    <p
                        class="mb-2 text-xl font-semibold"
                        style="
                            font-family: 'Cormorant Garamond', serif;
                            color: #f9f6f0;
                        "
                    >
                        Easy Returns
                    </p>
                    <p class="text-sm" style="color: rgba(249, 246, 240, 0.55)">
                        Hassle-free 30-day returns
                    </p>
                </div>
                <div>
                    <p
                        class="mb-2 text-xl font-semibold"
                        style="
                            font-family: 'Cormorant Garamond', serif;
                            color: #f9f6f0;
                        "
                    >
                        Secure Checkout
                    </p>
                    <p class="text-sm" style="color: rgba(249, 246, 240, 0.55)">
                        Your data is always safe
                    </p>
                </div>
            </div>
        </section>

        <QuickView
            :product="selectedProduct"
            :is-open="isQuickViewOpen"
            @close="isQuickViewOpen = false"
        />
    </StorefrontLayout>
</template>
