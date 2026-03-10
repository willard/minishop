<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { ShoppingBag, X, Trash2, ArrowRight } from 'lucide-vue-next';
import { create as checkoutCreate } from '@/actions/App/Http/Controllers/Storefront/CheckoutController';
import { useCart } from '@/composables/useCart';
import { usePrice } from '@/composables/usePrice';

defineProps<{
    isOpen: boolean;
}>();

const emit = defineEmits<{
    (e: 'close'): void;
}>();

const { cartItems, itemCount, subtotal, removeItem, updateQuantity } =
    useCart();
const { formatPrice } = usePrice();

function close(): void {
    emit('close');
}
</script>

<template>
    <!-- Backdrop -->
    <Transition
        enter-active-class="transition-opacity duration-300 ease-out"
        enter-from-class="opacity-0"
        enter-to-class="opacity-100"
        leave-active-class="transition-opacity duration-200 ease-in"
        leave-from-class="opacity-100"
        leave-to-class="opacity-0"
    >
        <div
            v-if="isOpen"
            class="fixed inset-0 z-[60] bg-black/30 backdrop-blur-[2px]"
            @click="close"
        />
    </Transition>

    <!-- Drawer -->
    <Transition
        enter-active-class="transition-transform duration-300 ease-out"
        enter-from-class="translate-x-full"
        enter-to-class="translate-x-0"
        leave-active-class="transition-transform duration-200 ease-in"
        leave-from-class="translate-x-0"
        leave-to-class="translate-x-full"
    >
        <div
            v-if="isOpen"
            class="fixed inset-y-0 right-0 z-[70] flex w-full max-w-md flex-col shadow-2xl"
            style="background-color: #f9f6f0; color: #1c1a17"
        >
            <!-- Header -->
            <div
                class="flex items-center justify-between border-b px-6 py-5"
                style="border-color: rgba(28, 26, 23, 0.1)"
            >
                <h2
                    class="flex items-center gap-2 text-xl font-semibold"
                    style="font-family: 'Cormorant Garamond', serif"
                >
                    <ShoppingBag class="size-5" />
                    Shopping Bag
                    <span
                        class="text-sm font-normal"
                        style="color: rgba(28, 26, 23, 0.5)"
                    >
                        ({{ itemCount }} item{{ itemCount !== 1 ? 's' : '' }})
                    </span>
                </h2>
                <button
                    class="rounded-full p-2 transition-colors hover:bg-black/5"
                    @click="close"
                >
                    <X class="size-5" />
                </button>
            </div>

            <!-- Content -->
            <div class="flex-1 overflow-y-auto px-6 py-6">
                <div v-if="cartItems.length > 0" class="space-y-6">
                    <div
                        v-for="item in cartItems"
                        :key="`${item.productId}-${item.variantId}`"
                        class="flex gap-4"
                    >
                        <!-- Image placeholder / thumbnail -->
                        <div
                            class="size-24 flex-shrink-0 overflow-hidden rounded-xl"
                            style="
                                background: linear-gradient(
                                    135deg,
                                    #e8dfd4,
                                    #d4c8b8
                                );
                            "
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
                                    class="text-2xl"
                                    style="
                                        font-family:
                                            'Cormorant Garamond', serif;
                                        color: rgba(28, 26, 23, 0.2);
                                    "
                                >
                                    {{ item.name.charAt(0) }}
                                </span>
                            </div>
                        </div>

                        <div class="flex min-w-0 flex-1 flex-col">
                            <div class="flex items-start justify-between gap-2">
                                <h3
                                    class="line-clamp-2 text-sm leading-tight font-medium"
                                >
                                    {{ item.name }}
                                </h3>
                                <button
                                    class="mt-0.5 text-xs transition-opacity hover:opacity-60"
                                    @click="
                                        removeItem(
                                            item.productId,
                                            item.variantId,
                                        )
                                    "
                                >
                                    <Trash2
                                        class="size-3.5"
                                        style="color: rgba(28, 26, 23, 0.4)"
                                    />
                                </button>
                            </div>

                            <p
                                v-if="item.variantLabel"
                                class="mt-1 text-xs"
                                style="color: rgba(28, 26, 23, 0.5)"
                            >
                                {{ item.variantLabel }}
                            </p>

                            <div
                                class="mt-auto flex items-center justify-between"
                            >
                                <!-- Quantity controls -->
                                <div class="flex items-center gap-3">
                                    <button
                                        class="flex size-7 items-center justify-center rounded-full border text-xs transition-opacity hover:opacity-70"
                                        style="
                                            border-color: rgba(28, 26, 23, 0.2);
                                        "
                                        @click="
                                            updateQuantity(
                                                item.productId,
                                                item.variantId,
                                                item.quantity - 1,
                                            )
                                        "
                                    >
                                        −
                                    </button>
                                    <span
                                        class="w-4 text-center text-sm font-medium"
                                    >
                                        {{ item.quantity }}
                                    </span>
                                    <button
                                        class="flex size-7 items-center justify-center rounded-full border text-xs transition-opacity hover:opacity-70"
                                        style="
                                            border-color: rgba(28, 26, 23, 0.2);
                                        "
                                        @click="
                                            updateQuantity(
                                                item.productId,
                                                item.variantId,
                                                item.quantity + 1,
                                            )
                                        "
                                    >
                                        +
                                    </button>
                                </div>

                                <span class="text-sm font-semibold">
                                    {{
                                        formatPrice(item.price * item.quantity)
                                    }}
                                </span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Empty state -->
                <div
                    v-else
                    class="flex h-full flex-col items-center justify-center py-20 text-center"
                >
                    <ShoppingBag class="mb-4 size-12 opacity-20" />
                    <p class="mb-1 text-lg font-medium">Your bag is empty</p>
                    <p class="mb-8 text-sm opacity-50">
                        Explore our collection and find something you love.
                    </p>
                    <button
                        class="rounded-full px-8 py-3 text-sm font-semibold tracking-widest text-white uppercase transition-opacity hover:opacity-80"
                        style="background-color: #1c1a17"
                        @click="close"
                    >
                        Browse Shop
                    </button>
                </div>
            </div>

            <!-- Footer -->
            <div
                v-if="cartItems.length > 0"
                class="space-y-4 border-t px-6 py-6"
                style="
                    border-color: rgba(28, 26, 23, 0.1);
                    background-color: #f9f6f0;
                "
            >
                <div class="flex items-center justify-between">
                    <span
                        class="text-sm tracking-widest uppercase"
                        style="color: rgba(28, 26, 23, 0.5)"
                        >Subtotal</span
                    >
                    <span class="text-xl font-semibold">{{
                        formatPrice(subtotal)
                    }}</span>
                </div>
                <p class="text-xs" style="color: rgba(28, 26, 23, 0.4)">
                    Shipping and taxes calculated at checkout.
                </p>

                <Link
                    :href="checkoutCreate().url"
                    class="flex w-full items-center justify-center gap-2 rounded-full py-4 text-sm font-semibold tracking-widest text-white uppercase transition-opacity hover:opacity-80"
                    style="background-color: #1c1a17"
                    @click="close"
                >
                    Checkout
                    <ArrowRight class="size-4" />
                </Link>

                <button
                    class="w-full text-center text-xs font-semibold tracking-widest uppercase transition-opacity hover:opacity-60"
                    style="color: #1c1a17"
                    @click="close"
                >
                    Continue Shopping
                </button>
            </div>
        </div>
    </Transition>
</template>
