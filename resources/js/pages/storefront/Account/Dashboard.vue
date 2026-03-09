<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import AccountLayout from '@/layouts/AccountLayout.vue';
import { usePrice } from '@/composables/usePrice';
import {
    index as ordersIndex,
    show as orderShow,
} from '@/routes/account/orders';

interface OrderItem {
    id: number;
    product: { name: string } | null;
    quantity: number;
    unit_price: number;
}

interface Order {
    id: number;
    order_number: string;
    status: string;
    total_amount: number;
    created_at: string;
    items: OrderItem[];
}

defineProps<{
    recentOrders: Order[];
    totalOrders: number;
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
    <AccountLayout title="Overview">
        <Head title="My Account" />

        <!-- Stats row -->
        <div class="mb-8 grid grid-cols-1 gap-4">
            <div
                class="rounded-xl border p-5"
                style="
                    border-color: rgba(28, 26, 23, 0.12);
                    background-color: #fff;
                "
            >
                <p
                    class="mb-1 text-xs font-semibold tracking-widest uppercase"
                    style="color: rgba(28, 26, 23, 0.4)"
                >
                    Total Orders
                </p>
                <p class="text-3xl font-semibold" style="color: #1c1a17">
                    {{ totalOrders }}
                </p>
            </div>
        </div>

        <!-- Recent orders -->
        <div>
            <h2
                class="mb-4 text-sm font-semibold tracking-widest uppercase"
                style="color: rgba(28, 26, 23, 0.4)"
            >
                Recent Orders
            </h2>

            <div
                v-if="recentOrders.length === 0"
                class="rounded-xl border p-8 text-center"
                style="border-color: rgba(28, 26, 23, 0.12)"
            >
                <p class="text-sm" style="color: rgba(28, 26, 23, 0.5)">
                    No orders yet.
                </p>
            </div>

            <div v-else class="flex flex-col gap-3">
                <Link
                    v-for="order in recentOrders"
                    :key="order.id"
                    :href="orderShow({ order: order.order_number }).url"
                    class="flex items-center justify-between rounded-xl border p-4 transition-shadow hover:shadow-sm"
                    style="
                        border-color: rgba(28, 26, 23, 0.12);
                        background-color: #fff;
                    "
                >
                    <div>
                        <p
                            class="mb-0.5 text-sm font-semibold"
                            style="color: #1c1a17"
                        >
                            {{ order.order_number }}
                        </p>
                        <p
                            class="text-xs"
                            style="color: rgba(28, 26, 23, 0.45)"
                        >
                            {{
                                new Date(order.created_at).toLocaleDateString(
                                    'en-PH',
                                    {
                                        year: 'numeric',
                                        month: 'short',
                                        day: 'numeric',
                                    },
                                )
                            }}
                        </p>
                    </div>
                    <div class="flex items-center gap-4">
                        <span
                            class="rounded-full px-2.5 py-0.5 text-xs font-semibold capitalize"
                            :style="{
                                color: statusColor(order.status),
                                backgroundColor:
                                    statusColor(order.status) + '18',
                            }"
                        >
                            {{ order.status }}
                        </span>
                        <span
                            class="text-sm font-semibold"
                            style="color: #1c1a17"
                            >{{ formatPrice(order.total_amount) }}</span
                        >
                    </div>
                </Link>

                <div class="mt-1 text-right">
                    <Link
                        :href="ordersIndex().url"
                        class="text-sm underline underline-offset-4 transition-opacity hover:opacity-60"
                        style="color: rgba(28, 26, 23, 0.6)"
                    >
                        View all orders &rarr;
                    </Link>
                </div>
            </div>
        </div>
    </AccountLayout>
</template>
