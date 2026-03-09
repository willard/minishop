<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { CheckCircle, ArrowRight } from 'lucide-vue-next';
import { index as productsIndex } from '@/actions/App/Http/Controllers/Storefront/ProductController';
import { usePrice } from '@/composables/usePrice';
import StorefrontLayout from '@/layouts/StorefrontLayout.vue';

interface OrderItem {
    id: number;
    product_name: string;
    product_sku: string | null;
    unit_price: number;
    quantity: number;
    subtotal: number;
}

interface Order {
    id: number;
    order_number: string;
    status: string;
    subtotal: number;
    discount_amount: number;
    shipping_amount: number;
    tax_amount: number;
    total_amount: number;
    shipping_name: string;
    shipping_address_line1: string;
    shipping_address_line2: string | null;
    shipping_city: string;
    shipping_state: string;
    shipping_postcode: string;
    shipping_country: string;
    items: OrderItem[];
    customer: {
        user: {
            name: string;
            email: string;
        };
    };
}

defineProps<{
    order: Order;
}>();

const { formatPrice } = usePrice();
</script>

<template>
    <Head :title="`Order ${order.order_number} Confirmed`" />

    <StorefrontLayout>
        <div class="mx-auto max-w-3xl px-6 py-16">
            <!-- Success header -->
            <div class="mb-12 text-center">
                <div
                    class="mx-auto mb-6 flex size-16 items-center justify-center rounded-full"
                    style="background-color: rgba(74, 124, 89, 0.12)"
                >
                    <CheckCircle class="size-8" style="color: #4a7c59" />
                </div>
                <p
                    class="mb-2 text-xs font-semibold tracking-[0.2em] uppercase"
                    style="color: #4a7c59"
                >
                    Order Confirmed
                </p>
                <h1
                    class="mb-3 text-4xl font-semibold md:text-5xl"
                    style="
                        font-family: 'Cormorant Garamond', serif;
                        color: #1c1a17;
                    "
                >
                    Thank you, {{ order.customer.user.name.split(' ')[0] }}!
                </h1>
                <p class="text-base" style="color: rgba(28, 26, 23, 0.6)">
                    Your order
                    <strong style="color: #1c1a17">{{
                        order.order_number
                    }}</strong>
                    has been placed and is being processed.
                </p>
                <p class="mt-1 text-sm" style="color: rgba(28, 26, 23, 0.5)">
                    A confirmation will be sent to
                    <strong>{{ order.customer.user.email }}</strong>
                </p>
            </div>

            <!-- Order details card -->
            <div
                class="mb-6 overflow-hidden rounded-2xl border"
                style="border-color: rgba(28, 26, 23, 0.12)"
            >
                <!-- Items -->
                <div class="p-6">
                    <h2
                        class="mb-5 text-sm font-semibold tracking-wider uppercase"
                        style="color: #1c1a17"
                    >
                        Items Ordered
                    </h2>
                    <div
                        class="divide-y"
                        style="
                            --tw-divide-opacity: 0.08;
                            border-color: rgba(28, 26, 23, 0.08);
                        "
                    >
                        <div
                            v-for="item in order.items"
                            :key="item.id"
                            class="flex items-start justify-between gap-4 py-4 first:pt-0 last:pb-0"
                        >
                            <div class="min-w-0 flex-1">
                                <p
                                    class="text-sm font-medium"
                                    style="color: #1c1a17"
                                >
                                    {{ item.product_name }}
                                </p>
                                <p
                                    v-if="item.product_sku"
                                    class="mt-0.5 font-mono text-xs"
                                    style="color: rgba(28, 26, 23, 0.4)"
                                >
                                    {{ item.product_sku }}
                                </p>
                                <p
                                    class="mt-1 text-xs"
                                    style="color: rgba(28, 26, 23, 0.55)"
                                >
                                    {{ formatPrice(item.unit_price) }} ×
                                    {{ item.quantity }}
                                </p>
                            </div>
                            <span
                                class="flex-shrink-0 text-sm font-semibold"
                                style="color: #1c1a17"
                            >
                                {{ formatPrice(item.subtotal) }}
                            </span>
                        </div>
                    </div>
                </div>

                <!-- Price breakdown -->
                <div
                    class="border-t p-6"
                    style="
                        border-color: rgba(28, 26, 23, 0.1);
                        background-color: #f4f0e8;
                    "
                >
                    <div class="space-y-2.5">
                        <div
                            class="flex justify-between text-sm"
                            style="color: rgba(28, 26, 23, 0.65)"
                        >
                            <span>Subtotal</span>
                            <span>{{ formatPrice(order.subtotal) }}</span>
                        </div>
                        <div
                            v-if="order.discount_amount > 0"
                            class="flex justify-between text-sm"
                            style="color: #4a7c59"
                        >
                            <span>Discount</span>
                            <span
                                >−{{ formatPrice(order.discount_amount) }}</span
                            >
                        </div>
                        <div
                            class="flex justify-between text-sm"
                            style="color: rgba(28, 26, 23, 0.65)"
                        >
                            <span>Shipping</span>
                            <span>{{
                                formatPrice(order.shipping_amount)
                            }}</span>
                        </div>
                        <div
                            class="flex justify-between text-sm"
                            style="color: rgba(28, 26, 23, 0.65)"
                        >
                            <span>Tax (12%)</span>
                            <span>{{ formatPrice(order.tax_amount) }}</span>
                        </div>
                        <div
                            class="h-px"
                            style="background-color: rgba(28, 26, 23, 0.1)"
                        />
                        <div
                            class="flex justify-between font-semibold"
                            style="color: #1c1a17"
                        >
                            <span>Total</span>
                            <span>{{ formatPrice(order.total_amount) }}</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Shipping address -->
            <div
                class="mb-10 rounded-2xl border p-6"
                style="border-color: rgba(28, 26, 23, 0.12)"
            >
                <h2
                    class="mb-4 text-sm font-semibold tracking-wider uppercase"
                    style="color: #1c1a17"
                >
                    Shipping To
                </h2>
                <address
                    class="text-sm leading-relaxed not-italic"
                    style="color: rgba(28, 26, 23, 0.7)"
                >
                    <strong style="color: #1c1a17">{{
                        order.shipping_name
                    }}</strong
                    ><br />
                    {{ order.shipping_address_line1 }}<br />
                    <template v-if="order.shipping_address_line2">
                        {{ order.shipping_address_line2 }}<br />
                    </template>
                    {{ order.shipping_city }}, {{ order.shipping_state }}
                    {{ order.shipping_postcode }}<br />
                    {{ order.shipping_country }}
                </address>
            </div>

            <!-- CTA -->
            <div class="text-center">
                <Link
                    :href="productsIndex().url"
                    class="inline-flex items-center gap-2 rounded-full px-8 py-4 text-sm font-semibold tracking-widest text-white uppercase transition-opacity hover:opacity-80"
                    style="background-color: #1c1a17"
                >
                    Continue Shopping
                    <ArrowRight class="size-4" />
                </Link>
            </div>
        </div>
    </StorefrontLayout>
</template>
