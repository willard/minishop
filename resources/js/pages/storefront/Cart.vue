<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { ShoppingBag, Trash2, ArrowRight, ArrowLeft } from 'lucide-vue-next';
import { create as checkoutCreate } from '@/actions/App/Http/Controllers/Storefront/CheckoutController';
import { index as productsIndex } from '@/actions/App/Http/Controllers/Storefront/ProductController';
import { useCart } from '@/composables/useCart';
import { usePrice } from '@/composables/usePrice';
import StorefrontLayout from '@/layouts/StorefrontLayout.vue';
import type { StorefrontCategory } from '@/types/storefront';

defineProps<{
    categories?: StorefrontCategory[];
}>();

const { cartItems, itemCount, subtotal, removeItem, updateQuantity } = useCart();
const { formatPrice } = usePrice();
</script>

<template>
    <Head title="Your Cart — Minishop" />

    <StorefrontLayout :categories="categories ?? []">
        <div class="mx-auto max-w-4xl px-6 py-12 md:py-20">
            <div class="mb-10 flex items-center gap-4">
                <Link
                    :href="productsIndex().url"
                    class="flex items-center gap-1.5 text-sm transition-opacity hover:opacity-60"
                    style="color: rgba(28, 26, 23, 0.5)"
                >
                    <ArrowLeft class="size-3.5" />
                    Continue Shopping
                </Link>
            </div>

            <h1
                class="mb-10 flex items-center gap-3 text-4xl font-semibold"
                style="font-family: 'Cormorant Garamond', serif; color: #1c1a17"
            >
                <ShoppingBag class="size-8" />
                Shopping Bag
                <span
                    class="text-xl font-normal"
                    style="color: rgba(28, 26, 23, 0.4)"
                >
                    ({{ itemCount }} item{{ itemCount !== 1 ? 's' : '' }})
                </span>
            </h1>

            <div v-if="cartItems.length > 0" class="grid grid-cols-1 gap-10 lg:grid-cols-3">
                <!-- Items -->
                <div class="space-y-6 lg:col-span-2">
                    <div
                        v-for="item in cartItems"
                        :key="`${item.productId}-${item.variantId}`"
                        class="flex gap-5 border-b pb-6"
                        style="border-color: rgba(28, 26, 23, 0.1)"
                    >
                        <div
                            class="size-28 flex-shrink-0 overflow-hidden rounded-xl"
                            style="background: linear-gradient(135deg, #e8dfd4, #d4c8b8)"
                        >
                            <img
                                v-if="item.image"
                                :src="item.image"
                                :alt="item.name"
                                class="h-full w-full object-cover"
                            />
                            <div
                                v-else
                                class="flex h-full w-full items-center justify-center"
                            >
                                <span
                                    class="text-3xl"
                                    style="font-family: 'Cormorant Garamond', serif; color: rgba(28, 26, 23, 0.2)"
                                >
                                    {{ item.name.charAt(0) }}
                                </span>
                            </div>
                        </div>

                        <div class="flex min-w-0 flex-1 flex-col">
                            <div class="mb-1 flex items-start justify-between gap-2">
                                <h3 class="text-base font-medium leading-snug" style="color: #1c1a17">
                                    {{ item.name }}
                                </h3>
                                <button
                                    class="mt-0.5 transition-opacity hover:opacity-60"
                                    @click="removeItem(item.productId, item.variantId)"
                                >
                                    <Trash2 class="size-4" style="color: rgba(28, 26, 23, 0.4)" />
                                </button>
                            </div>

                            <p
                                v-if="item.variantLabel"
                                class="mb-3 text-xs"
                                style="color: rgba(28, 26, 23, 0.5)"
                            >
                                {{ item.variantLabel }}
                            </p>

                            <div class="mt-auto flex items-center justify-between">
                                <div class="flex items-center gap-3">
                                    <button
                                        class="flex size-8 items-center justify-center rounded-full border text-sm transition-opacity hover:opacity-70"
                                        style="border-color: rgba(28, 26, 23, 0.2); color: #1c1a17"
                                        @click="updateQuantity(item.productId, item.variantId, item.quantity - 1)"
                                    >
                                        −
                                    </button>
                                    <span class="w-5 text-center text-sm font-medium" style="color: #1c1a17">
                                        {{ item.quantity }}
                                    </span>
                                    <button
                                        class="flex size-8 items-center justify-center rounded-full border text-sm transition-opacity hover:opacity-70"
                                        style="border-color: rgba(28, 26, 23, 0.2); color: #1c1a17"
                                        @click="updateQuantity(item.productId, item.variantId, item.quantity + 1)"
                                    >
                                        +
                                    </button>
                                </div>
                                <span class="text-base font-semibold" style="color: #1c1a17">
                                    {{ formatPrice(item.price * item.quantity) }}
                                </span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Order summary -->
                <div class="lg:col-span-1">
                    <div
                        class="sticky top-6 rounded-2xl p-6"
                        style="background-color: #f0ebe3"
                    >
                        <h2
                            class="mb-6 text-xl font-semibold"
                            style="font-family: 'Cormorant Garamond', serif; color: #1c1a17"
                        >
                            Order Summary
                        </h2>

                        <div class="space-y-3 text-sm" style="color: #1c1a17">
                            <div class="flex justify-between">
                                <span style="color: rgba(28, 26, 23, 0.6)">Subtotal</span>
                                <span class="font-medium">{{ formatPrice(subtotal) }}</span>
                            </div>
                            <div class="flex justify-between">
                                <span style="color: rgba(28, 26, 23, 0.6)">Shipping</span>
                                <span class="font-medium">Calculated at checkout</span>
                            </div>
                        </div>

                        <div
                            class="my-4 border-t"
                            style="border-color: rgba(28, 26, 23, 0.15)"
                        />

                        <div class="mb-6 flex justify-between text-lg font-semibold" style="color: #1c1a17">
                            <span>Total</span>
                            <span>{{ formatPrice(subtotal) }}</span>
                        </div>

                        <Link
                            :href="checkoutCreate().url"
                            class="flex w-full items-center justify-center gap-2 rounded-full py-4 text-sm font-semibold tracking-widest text-white uppercase transition-opacity hover:opacity-80"
                            style="background-color: #1c1a17"
                        >
                            Proceed to Checkout
                            <ArrowRight class="size-4" />
                        </Link>
                    </div>
                </div>
            </div>

            <!-- Empty state -->
            <div v-else class="py-24 text-center">
                <ShoppingBag class="mx-auto mb-6 size-16 opacity-15" style="color: #1c1a17" />
                <h2
                    class="mb-3 text-2xl font-semibold"
                    style="font-family: 'Cormorant Garamond', serif; color: #1c1a17"
                >
                    Your bag is empty
                </h2>
                <p class="mb-8 text-sm" style="color: rgba(28, 26, 23, 0.5)">
                    Explore our collection and find something you love.
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
