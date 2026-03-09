<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { useWindowScroll } from '@vueuse/core';
import { ShoppingBag, ChevronLeft, ChevronRight } from 'lucide-vue-next';
import { computed, ref } from 'vue';

// ... (existing imports)

const { y: scrollY } = useWindowScroll();
const showStickyBar = computed(() => scrollY.value > 600);
import { index as productsIndex } from '@/actions/App/Http/Controllers/Storefront/ProductController';
import { useCart } from '@/composables/useCart';
import { usePrice } from '@/composables/usePrice';
import StorefrontLayout from '@/layouts/StorefrontLayout.vue';
import type { StorefrontProduct, StorefrontVariant } from '@/types/storefront';

const props = defineProps<{
    product: StorefrontProduct;
}>();

const { addItem, lastAddedItem } = useCart();
const { formatPrice } = usePrice();

// Image gallery state
const activeImageIndex = ref(0);
const isZoomed = ref(false);
const zoomStyle = ref({ transformOrigin: 'center center' });

function handleMouseMove(e: MouseEvent): void {
    const target = e.currentTarget as HTMLElement;
    const { left, top, width, height } = target.getBoundingClientRect();
    const x = ((e.pageX - left) / width) * 100;
    const y = ((e.pageY - top) / height) * 100;
    zoomStyle.value = { transformOrigin: `${x}% ${y}%` };
}

// Variant selection: map optionId → optionValueId
const selectedOptions = ref<Record<number, number>>({});

// Pre-select first value for each option
if (props.product.options) {
    for (const option of props.product.options) {
        if (option.values.length > 0) {
            selectedOptions.value[option.id] = option.values[0].id;
        }
    }
}

const selectedVariant = computed<StorefrontVariant | null>(() => {
    if (!props.product.variants || props.product.variants.length === 0) {
        return null;
    }

    const selectedValueIds = Object.values(selectedOptions.value);

    return (
        props.product.variants.find((variant) => {
            if (!variant.is_active) {
                return false;
            }
            const variantValueIds = variant.option_values.map((ov) => ov.id);

            return selectedValueIds.every((id) => variantValueIds.includes(id));
        }) ?? null
    );
});

const effectivePrice = computed<number>(() => {
    return selectedVariant.value?.price ?? props.product.price;
});

const effectiveStock = computed<number>(() => {
    return (
        selectedVariant.value?.stock_quantity ?? props.product.stock_quantity
    );
});

const inStock = computed<boolean>(() => effectiveStock.value > 0);

const variantLabel = computed<string | null>(() => {
    if (!props.product.options || props.product.options.length === 0) {
        return null;
    }

    return props.product.options
        .map((option) => {
            const valueId = selectedOptions.value[option.id];
            const value = option.values.find((v) => v.id === valueId);

            return value ? `${option.name}: ${value.value}` : null;
        })
        .filter(Boolean)
        .join(', ');
});

const hasVariants = computed<boolean>(() => {
    return (props.product.options?.length ?? 0) > 0;
});

function handleAddToCart(): void {
    if (!inStock.value) {
        return;
    }

    addItem({
        productId: props.product.id,
        variantId: selectedVariant.value?.id ?? null,
        name: props.product.name,
        slug: props.product.slug,
        sku: selectedVariant.value?.sku ?? props.product.sku,
        price: effectivePrice.value,
        image: props.product.images?.[0]?.path ?? null,
        variantLabel: variantLabel.value,
    });
}
</script>

<template>
    <Head :title="product.name" />

    <StorefrontLayout>
        <!-- Breadcrumb -->
        <nav class="mx-auto max-w-7xl px-6 py-4">
            <div
                class="flex items-center gap-2 text-sm"
                style="color: rgba(28, 26, 23, 0.5)"
            >
                <Link
                    :href="productsIndex().url"
                    class="transition-opacity hover:opacity-70"
                    style="color: rgba(28, 26, 23, 0.5)"
                >
                    Products
                </Link>
                <ChevronRight class="size-3.5" />
                <span style="color: #1c1a17">{{ product.name }}</span>
            </div>
        </nav>

        <!-- Product detail -->
        <div class="mx-auto max-w-7xl px-6 py-6 pb-20">
            <div class="grid grid-cols-1 gap-12 lg:grid-cols-2">
                <!-- Image gallery -->
                <div class="space-y-4">
                    <!-- Main image -->
                    <div
                        class="relative aspect-square cursor-zoom-in overflow-hidden rounded-2xl"
                        style="
                            background: linear-gradient(
                                135deg,
                                #e8dfd4 0%,
                                #d4c8b8 100%
                            );
                        "
                        @mouseenter="isZoomed = true"
                        @mouseleave="isZoomed = false"
                        @mousemove="handleMouseMove"
                    >
                        <img
                            v-if="product.images?.[activeImageIndex]"
                            :src="product.images[activeImageIndex].path"
                            :alt="
                                product.images[activeImageIndex].alt_text ??
                                product.name
                            "
                            class="h-full w-full object-cover transition-transform duration-200 ease-out"
                            :style="{
                                transform: isZoomed ? 'scale(1.5)' : 'scale(1)',
                                ...zoomStyle,
                            }"
                        />
                        <div
                            v-else
                            class="flex h-full w-full items-center justify-center"
                        >
                            <span
                                class="text-6xl font-medium"
                                style="
                                    font-family: 'Cormorant Garamond', serif;
                                    color: rgba(28, 26, 23, 0.2);
                                "
                            >
                                {{ product.name.charAt(0) }}
                            </span>
                        </div>

                        <!-- Navigation arrows (when multiple images) -->
                        <button
                            v-if="
                                product.images &&
                                product.images.length > 1 &&
                                activeImageIndex > 0
                            "
                            class="absolute top-1/2 left-3 flex size-10 -translate-y-1/2 items-center justify-center rounded-full bg-white/80 shadow-sm transition-opacity hover:bg-white"
                            @click="activeImageIndex--"
                        >
                            <ChevronLeft
                                class="size-5"
                                style="color: #1c1a17"
                            />
                        </button>
                        <button
                            v-if="
                                product.images &&
                                product.images.length > 1 &&
                                activeImageIndex < product.images.length - 1
                            "
                            class="absolute top-1/2 right-3 flex size-10 -translate-y-1/2 items-center justify-center rounded-full bg-white/80 shadow-sm transition-opacity hover:bg-white"
                            @click="activeImageIndex++"
                        >
                            <ChevronRight
                                class="size-5"
                                style="color: #1c1a17"
                            />
                        </button>
                    </div>

                    <!-- Thumbnails -->
                    <div
                        v-if="product.images && product.images.length > 1"
                        class="flex gap-3 overflow-x-auto"
                    >
                        <button
                            v-for="(image, i) in product.images"
                            :key="image.id"
                            class="size-16 flex-shrink-0 overflow-hidden rounded-lg border-2 transition-all"
                            :style="
                                i === activeImageIndex
                                    ? 'border-color: #1c1a17'
                                    : 'border-color: transparent; opacity: 0.6'
                            "
                            @click="activeImageIndex = i"
                        >
                            <img
                                :src="image.path"
                                :alt="image.alt_text ?? product.name"
                                class="h-full w-full object-cover"
                            />
                        </button>
                    </div>
                </div>

                <!-- Product info -->
                <div class="flex flex-col">
                    <!-- Categories -->
                    <div class="mb-3 flex flex-wrap gap-2">
                        <span
                            v-for="category in product.categories"
                            :key="category.id"
                            class="text-xs font-semibold tracking-wider uppercase"
                            style="color: #c05c3a"
                        >
                            {{ category.name }}
                        </span>
                    </div>

                    <!-- Name -->
                    <h1
                        class="mb-4 text-4xl leading-tight font-semibold md:text-5xl"
                        style="
                            font-family: 'Cormorant Garamond', serif;
                            color: #1c1a17;
                        "
                    >
                        {{ product.name }}
                    </h1>

                    <!-- Price -->
                    <div class="mb-6 flex items-baseline gap-3">
                        <span
                            class="text-2xl font-semibold"
                            style="color: #1c1a17"
                        >
                            {{ formatPrice(effectivePrice) }}
                        </span>
                        <span
                            v-if="
                                product.compare_price &&
                                product.compare_price > product.price
                            "
                            class="text-lg line-through"
                            style="color: rgba(28, 26, 23, 0.4)"
                        >
                            {{ formatPrice(product.compare_price) }}
                        </span>
                        <span
                            v-if="
                                product.compare_price &&
                                product.compare_price > product.price
                            "
                            class="rounded-full px-2.5 py-1 text-xs font-semibold text-white"
                            style="background-color: #c05c3a"
                        >
                            Save
                            {{
                                Math.round(
                                    (1 -
                                        product.price / product.compare_price) *
                                        100,
                                )
                            }}%
                        </span>
                    </div>

                    <!-- Divider -->
                    <div
                        class="mb-6 h-px"
                        style="background-color: rgba(28, 26, 23, 0.1)"
                    />

                    <!-- Variant options -->
                    <div v-if="hasVariants" class="mb-6 space-y-5">
                        <div v-for="option in product.options" :key="option.id">
                            <p
                                class="mb-3 text-sm font-semibold tracking-wider uppercase"
                                style="color: #1c1a17"
                            >
                                {{ option.name }}
                                <span
                                    v-if="selectedOptions[option.id]"
                                    class="ml-2 font-normal tracking-normal normal-case"
                                    style="color: rgba(28, 26, 23, 0.55)"
                                >
                                    —
                                    {{
                                        option.values.find(
                                            (v) =>
                                                v.id ===
                                                selectedOptions[option.id],
                                        )?.value
                                    }}
                                </span>
                            </p>
                            <div class="flex flex-wrap gap-2">
                                <button
                                    v-for="value in option.values"
                                    :key="value.id"
                                    class="rounded-full border px-4 py-2 text-sm font-medium transition-all"
                                    :style="
                                        selectedOptions[option.id] === value.id
                                            ? 'background-color: #1c1a17; color: #f9f6f0; border-color: #1c1a17'
                                            : 'border-color: rgba(28, 26, 23, 0.25); color: #1c1a17; background-color: transparent'
                                    "
                                    @click="
                                        selectedOptions[option.id] = value.id
                                    "
                                >
                                    {{ value.value }}
                                </button>
                            </div>
                        </div>
                    </div>

                    <!-- Stock indicator -->
                    <div class="mb-6">
                        <div v-if="inStock" class="flex items-center gap-2">
                            <div
                                class="size-2 rounded-full"
                                style="background-color: #4a7c59"
                            />
                            <span
                                class="text-sm"
                                style="color: rgba(28, 26, 23, 0.6)"
                            >
                                <template v-if="effectiveStock <= 5">
                                    Only {{ effectiveStock }} left in stock
                                </template>
                                <template v-else>In stock</template>
                            </span>
                        </div>
                        <div v-else class="flex items-center gap-2">
                            <div
                                class="size-2 rounded-full"
                                style="background-color: #c05c3a"
                            />
                            <span
                                class="text-sm"
                                style="color: rgba(28, 26, 23, 0.6)"
                                >Out of stock</span
                            >
                        </div>
                    </div>

                    <!-- Add to cart -->
                    <button
                        :disabled="!inStock"
                        class="mb-4 flex items-center justify-center gap-3 rounded-full py-4 text-sm font-semibold tracking-widest text-white uppercase transition-all"
                        :style="{
                            backgroundColor:
                                lastAddedItem?.productId === product.id
                                    ? '#4a7c59'
                                    : inStock
                                      ? '#1c1a17'
                                      : 'rgba(28, 26, 23, 0.3)',
                            cursor: inStock ? 'pointer' : 'not-allowed',
                        }"
                        @click="handleAddToCart"
                    >
                        <ShoppingBag class="size-4" />
                        {{
                            lastAddedItem?.productId === product.id
                                ? 'Added to Bag!'
                                : inStock
                                  ? 'Add to Cart'
                                  : 'Sold Out'
                        }}
                    </button>

                    <!-- SKU -->
                    <p
                        v-if="product.sku"
                        class="text-xs"
                        style="color: rgba(28, 26, 23, 0.4)"
                    >
                        SKU: {{ selectedVariant?.sku ?? product.sku }}
                    </p>

                    <!-- Divider -->
                    <div
                        class="my-8 h-px"
                        style="background-color: rgba(28, 26, 23, 0.1)"
                    />

                    <!-- Description -->
                    <div v-if="product.description">
                        <h2
                            class="mb-3 text-sm font-semibold tracking-wider uppercase"
                            style="color: #1c1a17"
                        >
                            Description
                        </h2>
                        <div
                            class="prose prose-sm max-w-none text-sm leading-relaxed"
                            style="color: rgba(28, 26, 23, 0.7)"
                        >
                            {{ product.description }}
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </StorefrontLayout>

    <!-- Mobile Sticky Bar -->
    <Transition
        enter-active-class="transition-transform duration-300 ease-out"
        enter-from-class="translate-y-full"
        enter-to-class="translate-y-0"
        leave-active-class="transition-transform duration-200 ease-in"
        leave-from-class="translate-y-0"
        leave-to-class="translate-y-full"
    >
        <div
            v-if="showStickyBar"
            class="fixed right-0 bottom-0 left-0 z-40 border-t p-4 md:hidden"
            style="
                background-color: #f9f6f0;
                border-color: rgba(28, 26, 23, 0.1);
            "
        >
            <div class="flex items-center justify-between gap-4">
                <div class="flex flex-col">
                    <span class="text-xs opacity-50">{{
                        variantLabel || 'Price'
                    }}</span>
                    <span class="font-semibold">{{
                        formatPrice(effectivePrice)
                    }}</span>
                </div>
                <button
                    :disabled="!inStock"
                    class="flex flex-1 items-center justify-center gap-2 rounded-full py-3 text-sm font-semibold tracking-widest text-white uppercase transition-all"
                    :style="{
                        backgroundColor:
                            lastAddedItem?.productId === product.id
                                ? '#4a7c59'
                                : inStock
                                  ? '#1c1a17'
                                  : 'rgba(28, 26, 23, 0.3)',
                    }"
                    @click="handleAddToCart"
                >
                    <ShoppingBag class="size-4" />
                    {{
                        lastAddedItem?.productId === product.id
                            ? 'Added!'
                            : inStock
                              ? 'Add to Bag'
                              : 'Sold Out'
                    }}
                </button>
            </div>
        </div>
    </Transition>
</template>
