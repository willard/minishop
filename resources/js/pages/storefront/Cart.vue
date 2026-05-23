<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { ArrowLeft, ArrowRight, Lock, Minus, Package, Plus, ShoppingBag, X } from 'lucide-vue-next';
import { computed } from 'vue';
import { create as checkoutCreate } from '@/actions/Minishop/Http/Controllers/Storefront/CheckoutController';
import { index as productsIndex } from '@/actions/Minishop/Http/Controllers/Storefront/ProductController';
import { useCart } from '@/composables/useCart';
import { usePrice } from '@/composables/usePrice';
import StorefrontLayout from '@/layouts/StorefrontLayout.vue';
import type { StorefrontCategory } from '@/types/storefront';

defineProps<{
    categories?: StorefrontCategory[];
}>();

const { cartItems, itemCount, subtotal, removeItem, updateQuantity, clearCart } = useCart();
const { formatPrice } = usePrice();

// Free shipping threshold: $200 = 20000 cents
const FREE_SHIPPING_THRESHOLD = 20000;
const shippingProgress = computed(() => Math.min((subtotal.value / FREE_SHIPPING_THRESHOLD) * 100, 100));
const remainingForFreeShipping = computed(() => Math.max(FREE_SHIPPING_THRESHOLD - subtotal.value, 0));
const hasFreeShipping = computed(() => subtotal.value >= FREE_SHIPPING_THRESHOLD);
</script>

<template>
    <Head title="Your Bag — Minishop" />

    <StorefrontLayout :categories="categories ?? []">
        <div class="mx-auto max-w-6xl px-6 py-10 md:py-16">

            <!-- Breadcrumb -->
            <Link
                :href="productsIndex().url"
                class="mb-12 inline-flex items-center gap-2 text-xs tracking-widest uppercase transition-opacity hover:opacity-50"
                style="color: rgba(28, 26, 23, 0.45)"
            >
                <ArrowLeft class="size-3" />
                Continue Shopping
            </Link>

            <!-- Page heading -->
            <div class="mb-10 border-b pb-8" style="border-color: rgba(28, 26, 23, 0.1)">
                <div class="flex items-baseline gap-4">
                    <h1
                        class="text-5xl font-light tracking-tight"
                        style="font-family: 'Cormorant Garamond', serif; color: #1c1a17"
                    >
                        Your Bag
                    </h1>
                    <span
                        v-if="itemCount > 0"
                        class="text-sm"
                        style="color: rgba(28, 26, 23, 0.4)"
                    >
                        {{ itemCount }}&nbsp;{{ itemCount === 1 ? 'item' : 'items' }}
                    </span>
                </div>
            </div>

            <!-- Cart with items -->
            <div v-if="cartItems.length > 0" class="grid grid-cols-1 gap-16 lg:grid-cols-[1fr_340px]">

                <!-- Left: items -->
                <div>
                    <!-- Column headers (desktop only) -->
                    <div
                        class="mb-2 hidden grid-cols-[1fr_auto_auto_auto] items-center gap-6 text-[10px] tracking-widest uppercase md:grid"
                        style="color: rgba(28, 26, 23, 0.3)"
                    >
                        <span>Product</span>
                        <span class="w-28 text-center">Qty</span>
                        <span class="w-20 text-right">Price</span>
                        <span class="w-6" />
                    </div>

                    <!-- Items -->
                    <div>
                        <div
                            v-for="item in cartItems"
                            :key="`${item.productId}-${item.variantId}`"
                            class="group border-t py-7"
                            style="border-color: rgba(28, 26, 23, 0.08)"
                        >
                            <!-- Mobile layout -->
                            <div class="flex gap-5 md:hidden">
                                <!-- Thumbnail -->
                                <div
                                    class="size-24 flex-shrink-0 overflow-hidden rounded-2xl"
                                    style="background: linear-gradient(135deg, #e8dfd4, #d4c8b8)"
                                >
                                    <img
                                        v-if="item.image"
                                        :src="item.image"
                                        :alt="item.name"
                                        class="h-full w-full object-cover"
                                    />
                                    <div v-else class="flex h-full w-full items-center justify-center">
                                        <span
                                            class="text-3xl font-light"
                                            style="font-family: 'Cormorant Garamond', serif; color: rgba(28, 26, 23, 0.2)"
                                        >
                                            {{ item.name.charAt(0) }}
                                        </span>
                                    </div>
                                </div>

                                <div class="flex flex-1 flex-col">
                                    <div class="flex items-start justify-between gap-2">
                                        <div>
                                            <h3 class="text-sm font-medium leading-snug" style="color: #1c1a17">
                                                {{ item.name }}
                                            </h3>
                                            <p v-if="item.variantLabel" class="mt-0.5 text-xs" style="color: rgba(28, 26, 23, 0.45)">
                                                {{ item.variantLabel }}
                                            </p>
                                        </div>
                                        <button
                                            class="mt-0.5 rounded-full p-1 transition-colors hover:bg-black/5"
                                            @click="removeItem(item.productId, item.variantId)"
                                            aria-label="Remove item"
                                        >
                                            <X class="size-3.5" style="color: rgba(28, 26, 23, 0.35)" />
                                        </button>
                                    </div>

                                    <div class="mt-4 flex items-center justify-between">
                                        <!-- Qty pill -->
                                        <div
                                            class="flex items-center gap-1 rounded-full border px-1 py-1"
                                            style="border-color: rgba(28, 26, 23, 0.15)"
                                        >
                                            <button
                                                class="flex size-7 items-center justify-center rounded-full transition-colors hover:bg-black/5"
                                                style="color: #1c1a17"
                                                @click="updateQuantity(item.productId, item.variantId, item.quantity - 1)"
                                            >
                                                <Minus class="size-3" />
                                            </button>
                                            <span class="w-6 text-center text-sm font-medium tabular-nums" style="color: #1c1a17">
                                                {{ item.quantity }}
                                            </span>
                                            <button
                                                class="flex size-7 items-center justify-center rounded-full transition-colors hover:bg-black/5"
                                                style="color: #1c1a17"
                                                @click="updateQuantity(item.productId, item.variantId, item.quantity + 1)"
                                            >
                                                <Plus class="size-3" />
                                            </button>
                                        </div>
                                        <span class="text-sm font-semibold" style="color: #1c1a17">
                                            {{ formatPrice(item.price * item.quantity) }}
                                        </span>
                                    </div>
                                </div>
                            </div>

                            <!-- Desktop layout -->
                            <div class="hidden grid-cols-[1fr_auto_auto_auto] items-center gap-6 md:grid">
                                <!-- Product info -->
                                <div class="flex gap-5 items-center min-w-0">
                                    <div
                                        class="size-20 flex-shrink-0 overflow-hidden rounded-2xl"
                                        style="background: linear-gradient(135deg, #e8dfd4, #d4c8b8)"
                                    >
                                        <img
                                            v-if="item.image"
                                            :src="item.image"
                                            :alt="item.name"
                                            class="h-full w-full object-cover"
                                        />
                                        <div v-else class="flex h-full w-full items-center justify-center">
                                            <span
                                                class="text-2xl font-light"
                                                style="font-family: 'Cormorant Garamond', serif; color: rgba(28, 26, 23, 0.2)"
                                            >
                                                {{ item.name.charAt(0) }}
                                            </span>
                                        </div>
                                    </div>
                                    <div class="min-w-0">
                                        <h3 class="text-sm font-medium leading-snug" style="color: #1c1a17">
                                            {{ item.name }}
                                        </h3>
                                        <p v-if="item.variantLabel" class="mt-0.5 text-xs" style="color: rgba(28, 26, 23, 0.45)">
                                            {{ item.variantLabel }}
                                        </p>
                                        <p class="mt-1 text-xs" style="color: rgba(28, 26, 23, 0.35)">
                                            {{ formatPrice(item.price) }} each
                                        </p>
                                    </div>
                                </div>

                                <!-- Qty pill -->
                                <div
                                    class="flex w-28 items-center justify-between rounded-full border px-1 py-1"
                                    style="border-color: rgba(28, 26, 23, 0.15)"
                                >
                                    <button
                                        class="flex size-7 items-center justify-center rounded-full transition-colors hover:bg-black/5"
                                        style="color: #1c1a17"
                                        @click="updateQuantity(item.productId, item.variantId, item.quantity - 1)"
                                    >
                                        <Minus class="size-3" />
                                    </button>
                                    <span class="w-6 text-center text-sm font-medium tabular-nums" style="color: #1c1a17">
                                        {{ item.quantity }}
                                    </span>
                                    <button
                                        class="flex size-7 items-center justify-center rounded-full transition-colors hover:bg-black/5"
                                        style="color: #1c1a17"
                                        @click="updateQuantity(item.productId, item.variantId, item.quantity + 1)"
                                    >
                                        <Plus class="size-3" />
                                    </button>
                                </div>

                                <!-- Line total -->
                                <span class="w-20 text-right text-sm font-semibold tabular-nums" style="color: #1c1a17">
                                    {{ formatPrice(item.price * item.quantity) }}
                                </span>

                                <!-- Remove -->
                                <button
                                    class="flex size-6 w-6 items-center justify-center rounded-full opacity-0 transition-all group-hover:opacity-100 hover:bg-black/5"
                                    style="color: rgba(28, 26, 23, 0.4)"
                                    @click="removeItem(item.productId, item.variantId)"
                                    aria-label="Remove item"
                                >
                                    <X class="size-3.5" />
                                </button>
                            </div>
                        </div>

                        <!-- Bottom border -->
                        <div class="border-t" style="border-color: rgba(28, 26, 23, 0.08)" />
                    </div>

                    <!-- Clear cart -->
                    <div class="mt-5 flex justify-end">
                        <button
                            class="text-xs tracking-wide transition-opacity hover:opacity-60"
                            style="color: rgba(28, 26, 23, 0.35)"
                            @click="clearCart"
                        >
                            Clear bag
                        </button>
                    </div>
                </div>

                <!-- Right: order summary -->
                <div class="lg:sticky lg:top-24 lg:self-start">
                    <div
                        class="overflow-hidden rounded-3xl"
                        style="background-color: #f0ebe3"
                    >
                        <!-- Free shipping bar -->
                        <div class="px-6 pt-6 pb-5">
                            <div v-if="hasFreeShipping" class="mb-4 text-center">
                                <p class="text-xs font-semibold tracking-widest uppercase" style="color: #3a7c5c">
                                    🎉 You've unlocked free shipping!
                                </p>
                            </div>
                            <div v-else class="mb-4">
                                <div class="mb-2 flex items-center justify-between">
                                    <p class="text-xs" style="color: rgba(28, 26, 23, 0.55)">
                                        Add <span class="font-semibold" style="color: #1c1a17">{{ formatPrice(remainingForFreeShipping) }}</span> for free shipping
                                    </p>
                                    <Package class="size-3.5" style="color: rgba(28, 26, 23, 0.3)" />
                                </div>
                                <div class="h-1 overflow-hidden rounded-full" style="background-color: rgba(28, 26, 23, 0.1)">
                                    <div
                                        class="h-full rounded-full transition-all duration-500"
                                        style="background-color: #1c1a17"
                                        :style="{ width: `${shippingProgress}%` }"
                                    />
                                </div>
                            </div>
                        </div>

                        <div class="border-t px-6 pb-6" style="border-color: rgba(28, 26, 23, 0.1)">
                            <h2
                                class="mt-5 mb-5 text-lg font-semibold"
                                style="font-family: 'Cormorant Garamond', serif; color: #1c1a17"
                            >
                                Order Summary
                            </h2>

                            <div class="space-y-3 text-sm">
                                <div class="flex justify-between">
                                    <span style="color: rgba(28, 26, 23, 0.55)">
                                        Subtotal ({{ itemCount }}&nbsp;{{ itemCount === 1 ? 'item' : 'items' }})
                                    </span>
                                    <span class="font-medium tabular-nums" style="color: #1c1a17">
                                        {{ formatPrice(subtotal) }}
                                    </span>
                                </div>
                                <div class="flex justify-between">
                                    <span style="color: rgba(28, 26, 23, 0.55)">Shipping</span>
                                    <span class="font-medium" style="color: #1c1a17">
                                        {{ hasFreeShipping ? 'Free' : 'Calculated at checkout' }}
                                    </span>
                                </div>
                            </div>

                            <div class="my-5 border-t" style="border-color: rgba(28, 26, 23, 0.12)" />

                            <div class="mb-6 flex items-baseline justify-between">
                                <span class="text-sm font-semibold" style="color: #1c1a17">Estimated Total</span>
                                <span
                                    class="text-2xl font-light tabular-nums"
                                    style="font-family: 'Cormorant Garamond', serif; color: #1c1a17"
                                >
                                    {{ formatPrice(subtotal) }}
                                </span>
                            </div>

                            <Link
                                :href="checkoutCreate().url"
                                class="mb-3 flex w-full items-center justify-center gap-2 rounded-full py-4 text-sm font-semibold tracking-widest text-white uppercase transition-opacity hover:opacity-80"
                                style="background-color: #1c1a17"
                            >
                                Proceed to Checkout
                                <ArrowRight class="size-4" />
                            </Link>

                            <p
                                class="flex items-center justify-center gap-1.5 text-[10px] tracking-wide"
                                style="color: rgba(28, 26, 23, 0.35)"
                            >
                                <Lock class="size-3" />
                                Secure, encrypted checkout
                            </p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Empty state -->
            <div v-else class="flex flex-col items-center py-32 text-center">
                <div
                    class="mb-8 flex size-24 items-center justify-center rounded-full"
                    style="background-color: rgba(28, 26, 23, 0.05)"
                >
                    <ShoppingBag class="size-10 opacity-25" style="color: #1c1a17" />
                </div>
                <h2
                    class="mb-3 text-3xl font-light"
                    style="font-family: 'Cormorant Garamond', serif; color: #1c1a17"
                >
                    Your bag is empty
                </h2>
                <p class="mb-10 max-w-xs text-sm leading-relaxed" style="color: rgba(28, 26, 23, 0.5)">
                    Discover our curated collection and find something you'll love.
                </p>
                <Link
                    :href="productsIndex().url"
                    class="inline-flex items-center gap-2 rounded-full px-8 py-3.5 text-sm font-semibold tracking-widest text-white uppercase transition-opacity hover:opacity-80"
                    style="background-color: #1c1a17"
                >
                    Browse Products
                    <ArrowRight class="size-4" />
                </Link>
            </div>

        </div>
    </StorefrontLayout>
</template>
