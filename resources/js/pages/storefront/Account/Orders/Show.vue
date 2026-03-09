<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { usePrice } from '@/composables/usePrice';
import AccountLayout from '@/layouts/AccountLayout.vue';
import { index as ordersIndex } from '@/routes/account/orders';

interface OrderItem {
    id: number;
    quantity: number;
    unit_price: number;
    subtotal: number;
    product: { name: string; slug: string } | null;
    variant: { sku: string | null } | null;
}

interface Order {
    id: number;
    order_number: string;
    status: string;
    payment_status: string;
    payment_gateway: string | null;
    subtotal: number;
    discount_amount: number;
    shipping_amount: number;
    tax_amount: number;
    total_amount: number;
    shipping_name: string;
    shipping_address_line1: string;
    shipping_address_line2: string | null;
    shipping_city: string;
    shipping_state: string | null;
    shipping_postcode: string;
    shipping_country: string;
    created_at: string;
    items: OrderItem[];
    shipping_method: { name: string } | null;
}

defineProps<{
    order: Order;
}>();

const { formatPrice } = usePrice();

function statusColor(status: string): string {
    const map: Record<string, string> = {
        pending: '#b45309',
        processing: '#1d4ed8',
        shipped: '#7c3aed',
        delivered: '#15803d',
        cancelled: '#b91c1c',
        refunded: '#6b7280',
    };
    return map[status] ?? '#6b7280';
}
</script>

<template>
    <AccountLayout title="Order Details">
        <Head :title="`Order ${order.order_number}`" />

        <!-- Back -->
        <Link
            :href="ordersIndex().url"
            class="mb-6 inline-flex items-center gap-1 text-sm underline underline-offset-4 transition-opacity hover:opacity-60"
            style="color: rgba(28, 26, 23, 0.6)"
        >
            &larr; Back to orders
        </Link>

        <div class="grid gap-6 lg:grid-cols-3">
            <!-- Items -->
            <div class="lg:col-span-2">
                <div
                    class="rounded-xl border"
                    style="
                        border-color: rgba(28, 26, 23, 0.12);
                        background-color: #fff;
                    "
                >
                    <div
                        class="border-b px-5 py-4"
                        style="border-color: rgba(28, 26, 23, 0.08)"
                    >
                        <div class="flex items-center justify-between">
                            <div>
                                <p
                                    class="text-sm font-semibold"
                                    style="color: #1c1a17"
                                >
                                    {{ order.order_number }}
                                </p>
                                <p
                                    class="mt-0.5 text-xs"
                                    style="color: rgba(28, 26, 23, 0.45)"
                                >
                                    Placed
                                    {{
                                        new Date(
                                            order.created_at,
                                        ).toLocaleDateString('en-PH', {
                                            year: 'numeric',
                                            month: 'long',
                                            day: 'numeric',
                                        })
                                    }}
                                </p>
                            </div>
                            <span
                                class="rounded-full px-3 py-1 text-xs font-semibold capitalize"
                                :style="{
                                    color: statusColor(order.status),
                                    backgroundColor:
                                        statusColor(order.status) + '18',
                                }"
                            >
                                {{ order.status }}
                            </span>
                        </div>
                    </div>

                    <div
                        class="divide-y"
                        style="
                            --tw-divide-opacity: 1;
                            border-color: rgba(28, 26, 23, 0.06);
                        "
                    >
                        <div
                            v-for="item in order.items"
                            :key="item.id"
                            class="flex items-center justify-between px-5 py-4"
                        >
                            <div class="min-w-0 flex-1">
                                <p
                                    class="truncate text-sm font-medium"
                                    style="color: #1c1a17"
                                >
                                    {{
                                        item.product?.name ?? 'Product removed'
                                    }}
                                </p>
                                <p
                                    class="text-xs"
                                    style="color: rgba(28, 26, 23, 0.45)"
                                >
                                    Qty: {{ item.quantity }} &middot;
                                    {{ formatPrice(item.unit_price) }} each
                                </p>
                            </div>
                            <p
                                class="ml-4 text-sm font-semibold"
                                style="color: #1c1a17"
                            >
                                {{ formatPrice(item.subtotal) }}
                            </p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Summary + Shipping -->
            <div class="flex flex-col gap-4">
                <!-- Totals -->
                <div
                    class="rounded-xl border p-5"
                    style="
                        border-color: rgba(28, 26, 23, 0.12);
                        background-color: #fff;
                    "
                >
                    <p
                        class="mb-4 text-xs font-semibold tracking-widest uppercase"
                        style="color: rgba(28, 26, 23, 0.4)"
                    >
                        Order Summary
                    </p>
                    <div class="flex flex-col gap-2 text-sm">
                        <div class="flex justify-between">
                            <span style="color: rgba(28, 26, 23, 0.6)"
                                >Subtotal</span
                            >
                            <span style="color: #1c1a17">{{
                                formatPrice(order.subtotal)
                            }}</span>
                        </div>
                        <div
                            v-if="order.discount_amount > 0"
                            class="flex justify-between"
                        >
                            <span style="color: rgba(28, 26, 23, 0.6)"
                                >Discount</span
                            >
                            <span style="color: #15803d"
                                >-{{ formatPrice(order.discount_amount) }}</span
                            >
                        </div>
                        <div class="flex justify-between">
                            <span style="color: rgba(28, 26, 23, 0.6)"
                                >Shipping</span
                            >
                            <span style="color: #1c1a17">{{
                                order.shipping_amount > 0
                                    ? formatPrice(order.shipping_amount)
                                    : 'Free'
                            }}</span>
                        </div>
                        <div
                            v-if="order.tax_amount > 0"
                            class="flex justify-between"
                        >
                            <span style="color: rgba(28, 26, 23, 0.6)"
                                >Tax</span
                            >
                            <span style="color: #1c1a17">{{
                                formatPrice(order.tax_amount)
                            }}</span>
                        </div>
                        <div
                            class="mt-2 flex justify-between border-t pt-2 font-semibold"
                            style="border-color: rgba(28, 26, 23, 0.1)"
                        >
                            <span style="color: #1c1a17">Total</span>
                            <span style="color: #1c1a17">{{
                                formatPrice(order.total_amount)
                            }}</span>
                        </div>
                    </div>
                </div>

                <!-- Shipping address -->
                <div
                    class="rounded-xl border p-5"
                    style="
                        border-color: rgba(28, 26, 23, 0.12);
                        background-color: #fff;
                    "
                >
                    <p
                        class="mb-3 text-xs font-semibold tracking-widest uppercase"
                        style="color: rgba(28, 26, 23, 0.4)"
                    >
                        Shipping Address
                    </p>
                    <div class="text-sm" style="color: rgba(28, 26, 23, 0.7)">
                        <p class="font-medium" style="color: #1c1a17">
                            {{ order.shipping_name }}
                        </p>
                        <p>{{ order.shipping_address_line1 }}</p>
                        <p v-if="order.shipping_address_line2">
                            {{ order.shipping_address_line2 }}
                        </p>
                        <p>
                            {{ order.shipping_city
                            }}<span v-if="order.shipping_state"
                                >, {{ order.shipping_state }}</span
                            >
                            {{ order.shipping_postcode }}
                        </p>
                        <p>{{ order.shipping_country }}</p>
                    </div>
                </div>

                <!-- Payment & shipping method -->
                <div
                    class="rounded-xl border p-5"
                    style="
                        border-color: rgba(28, 26, 23, 0.12);
                        background-color: #fff;
                    "
                >
                    <p
                        class="mb-3 text-xs font-semibold tracking-widest uppercase"
                        style="color: rgba(28, 26, 23, 0.4)"
                    >
                        Payment & Delivery
                    </p>
                    <div
                        class="flex flex-col gap-2 text-sm"
                        style="color: rgba(28, 26, 23, 0.7)"
                    >
                        <div class="flex justify-between">
                            <span>Payment</span>
                            <span
                                class="font-medium capitalize"
                                style="color: #1c1a17"
                                >{{ order.payment_status }}</span
                            >
                        </div>
                        <div
                            v-if="order.payment_gateway"
                            class="flex justify-between"
                        >
                            <span>Via</span>
                            <span
                                class="font-medium capitalize"
                                style="color: #1c1a17"
                                >{{ order.payment_gateway }}</span
                            >
                        </div>
                        <div
                            v-if="order.shipping_method"
                            class="flex justify-between"
                        >
                            <span>Carrier</span>
                            <span class="font-medium" style="color: #1c1a17">{{
                                order.shipping_method.name
                            }}</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AccountLayout>
</template>
