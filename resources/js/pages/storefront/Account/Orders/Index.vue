<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { usePrice } from '@/composables/usePrice';
import AccountLayout from '@/layouts/AccountLayout.vue';
import { show as orderShow } from '@/routes/account/orders';

interface OrderItem {
    id: number;
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

interface Paginated<T> {
    data: T[];
    current_page: number;
    last_page: number;
    next_page_url: string | null;
    prev_page_url: string | null;
    links: { url: string | null; label: string; active: boolean }[];
}

defineProps<{
    orders: Paginated<Order>;
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
    <AccountLayout title="Order History">
        <Head title="My Orders" />

        <div
            v-if="orders.data.length === 0"
            class="rounded-xl border p-12 text-center"
            style="border-color: rgba(28, 26, 23, 0.12)"
        >
            <p
                class="text-base font-medium"
                style="color: rgba(28, 26, 23, 0.5)"
            >
                You haven't placed any orders yet.
            </p>
        </div>

        <div v-else class="flex flex-col gap-3">
            <Link
                v-for="order in orders.data"
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
                    <p class="text-xs" style="color: rgba(28, 26, 23, 0.45)">
                        {{
                            new Date(order.created_at).toLocaleDateString(
                                'en-CA',
                                {
                                    year: 'numeric',
                                    month: 'short',
                                    day: 'numeric',
                                },
                            )
                        }}
                        &middot; {{ order.items.length }} item{{
                            order.items.length !== 1 ? 's' : ''
                        }}
                    </p>
                </div>
                <div class="flex items-center gap-4">
                    <span
                        class="rounded-full px-2.5 py-0.5 text-xs font-semibold capitalize"
                        :style="{
                            color: statusColor(order.status),
                            backgroundColor: statusColor(order.status) + '18',
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

            <!-- Pagination -->
            <div
                v-if="orders.last_page > 1"
                class="mt-4 flex justify-center gap-1"
            >
                <a
                    v-for="link in orders.links"
                    :key="link.label"
                    :href="link.url ?? '#'"
                    class="rounded px-3 py-1.5 text-xs"
                    :style="{
                        backgroundColor: link.active
                            ? '#1c1a17'
                            : 'transparent',
                        color: link.active
                            ? '#f9f6f0'
                            : 'rgba(28, 26, 23, 0.6)',
                        pointerEvents: link.url ? 'auto' : 'none',
                    }"
                    v-html="link.label"
                />
            </div>
        </div>
    </AccountLayout>
</template>
