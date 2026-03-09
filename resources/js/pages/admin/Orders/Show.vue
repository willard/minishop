<script setup lang="ts">
import { Form, Head, Link } from '@inertiajs/vue3';
import { ArrowLeft, Download } from 'lucide-vue-next';
import {
    index,
    invoice,
    update,
} from '@/actions/App/Http/Controllers/Admin/OrderController';
import InputError from '@/components/InputError.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Label } from '@/components/ui/label';
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem } from '@/types';

interface OrderStatus {
    value: string;
    label: string;
}

interface OrderItem {
    id: number;
    product_id: number | null;
    product_name: string;
    product_sku: string | null;
    unit_price: number;
    quantity: number;
    subtotal: number;
}

interface Customer {
    id: number;
    phone: string | null;
    user: {
        id: number;
        name: string;
        email: string;
    };
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
    notes: string | null;
    customer: Customer;
    items: OrderItem[];
    created_at: string;
}

const props = defineProps<{
    order: Order;
    statuses: OrderStatus[];
}>();

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Dashboard', href: '/dashboard' },
    { title: 'Orders', href: index().url },
    { title: props.order.order_number, href: '#' },
];

function formatPrice(cents: number): string {
    return (cents / 100).toFixed(2);
}

function statusVariant(
    status: string,
): 'default' | 'secondary' | 'destructive' | 'outline' {
    switch (status) {
        case 'delivered':
            return 'default';
        case 'cancelled':
        case 'refunded':
            return 'destructive';
        default:
            return 'secondary';
    }
}
</script>

<template>
    <Head :title="`Order ${order.order_number}`" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex max-w-4xl flex-col gap-6 p-4">
            <!-- Header -->
            <div class="flex items-center gap-4">
                <Link :href="index().url">
                    <Button variant="ghost" size="sm">
                        <ArrowLeft class="size-4" />
                    </Button>
                </Link>
                <div class="flex-1">
                    <div class="flex items-center gap-3">
                        <h1 class="font-mono text-2xl font-semibold">
                            {{ order.order_number }}
                        </h1>
                        <Badge
                            :variant="statusVariant(order.status)"
                            class="capitalize"
                        >
                            {{ order.status }}
                        </Badge>
                    </div>
                    <p class="text-sm text-muted-foreground">
                        Placed
                        {{
                            new Date(order.created_at).toLocaleDateString(
                                'en-US',
                                {
                                    year: 'numeric',
                                    month: 'long',
                                    day: 'numeric',
                                },
                            )
                        }}
                    </p>
                </div>
                <a
                    :href="invoice(order).url"
                    class="inline-flex items-center gap-2 rounded-md border border-input bg-background px-3 py-2 text-sm font-medium shadow-sm hover:bg-accent hover:text-accent-foreground"
                >
                    <Download class="size-4" />
                    Download Invoice
                </a>
            </div>

            <div class="grid grid-cols-1 gap-6 md:grid-cols-2">
                <!-- Customer Info -->
                <div
                    class="space-y-2 rounded-lg border border-sidebar-border p-4"
                >
                    <h2
                        class="text-sm font-semibold tracking-wide text-muted-foreground uppercase"
                    >
                        Customer
                    </h2>
                    <p class="font-medium">{{ order.customer.user.name }}</p>
                    <p class="text-sm text-muted-foreground">
                        {{ order.customer.user.email }}
                    </p>
                    <p
                        v-if="order.customer.phone"
                        class="text-sm text-muted-foreground"
                    >
                        {{ order.customer.phone }}
                    </p>
                </div>

                <!-- Shipping Address -->
                <div
                    class="space-y-2 rounded-lg border border-sidebar-border p-4"
                >
                    <h2
                        class="text-sm font-semibold tracking-wide text-muted-foreground uppercase"
                    >
                        Shipping Address
                    </h2>
                    <p class="font-medium">{{ order.shipping_name }}</p>
                    <p class="text-sm">{{ order.shipping_address_line1 }}</p>
                    <p v-if="order.shipping_address_line2" class="text-sm">
                        {{ order.shipping_address_line2 }}
                    </p>
                    <p class="text-sm">
                        {{ order.shipping_city }}, {{ order.shipping_state }}
                        {{ order.shipping_postcode }}
                    </p>
                    <p class="text-sm text-muted-foreground">
                        {{ order.shipping_country }}
                    </p>
                </div>
            </div>

            <!-- Order Items -->
            <div
                class="overflow-hidden rounded-lg border border-sidebar-border"
            >
                <div
                    class="border-b border-sidebar-border bg-muted/50 px-4 py-3"
                >
                    <h2
                        class="text-sm font-semibold tracking-wide text-muted-foreground uppercase"
                    >
                        Items
                    </h2>
                </div>
                <table class="w-full text-sm">
                    <thead class="text-muted-foreground">
                        <tr>
                            <th class="px-4 py-3 text-left font-medium">
                                Product
                            </th>
                            <th class="px-4 py-3 text-left font-medium">SKU</th>
                            <th class="px-4 py-3 text-right font-medium">
                                Unit Price
                            </th>
                            <th class="px-4 py-3 text-right font-medium">
                                Qty
                            </th>
                            <th class="px-4 py-3 text-right font-medium">
                                Subtotal
                            </th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-sidebar-border">
                        <tr
                            v-for="item in order.items"
                            :key="item.id"
                            class="hover:bg-muted/30"
                        >
                            <td class="px-4 py-3 font-medium">
                                {{ item.product_name }}
                            </td>
                            <td
                                class="px-4 py-3 font-mono text-xs text-muted-foreground"
                            >
                                {{ item.product_sku ?? '—' }}
                            </td>
                            <td class="px-4 py-3 text-right">
                                ${{ formatPrice(item.unit_price) }}
                            </td>
                            <td class="px-4 py-3 text-right">
                                {{ item.quantity }}
                            </td>
                            <td class="px-4 py-3 text-right font-medium">
                                ${{ formatPrice(item.subtotal) }}
                            </td>
                        </tr>
                    </tbody>
                </table>

                <!-- Totals -->
                <div
                    class="space-y-1 border-t border-sidebar-border bg-muted/20 px-4 py-3"
                >
                    <div class="flex justify-between text-sm">
                        <span class="text-muted-foreground">Subtotal</span>
                        <span>${{ formatPrice(order.subtotal) }}</span>
                    </div>
                    <div
                        v-if="order.shipping_amount > 0"
                        class="flex justify-between text-sm"
                    >
                        <span class="text-muted-foreground">Shipping</span>
                        <span>${{ formatPrice(order.shipping_amount) }}</span>
                    </div>
                    <div
                        v-if="order.tax_amount > 0"
                        class="flex justify-between text-sm"
                    >
                        <span class="text-muted-foreground">Tax</span>
                        <span>${{ formatPrice(order.tax_amount) }}</span>
                    </div>
                    <div
                        v-if="order.discount_amount > 0"
                        class="flex justify-between text-sm text-green-600"
                    >
                        <span>Discount</span>
                        <span>−${{ formatPrice(order.discount_amount) }}</span>
                    </div>
                    <div
                        class="flex justify-between border-t border-sidebar-border pt-1 font-semibold"
                    >
                        <span>Total</span>
                        <span>${{ formatPrice(order.total_amount) }}</span>
                    </div>
                </div>
            </div>

            <!-- Update Status -->
            <div class="rounded-lg border border-sidebar-border p-4">
                <h2 class="mb-4 font-semibold">Update Order</h2>
                <Form
                    v-bind="update.form(order)"
                    class="flex flex-col gap-4"
                    v-slot="{ errors, processing }"
                >
                    <div class="grid gap-2">
                        <Label for="status">Status</Label>
                        <select
                            id="status"
                            name="status"
                            :value="order.status"
                            class="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 focus-visible:outline-none"
                        >
                            <option
                                v-for="s in statuses"
                                :key="s.value"
                                :value="s.value"
                            >
                                {{ s.label }}
                            </option>
                        </select>
                        <InputError :message="errors.status" />
                    </div>

                    <div class="grid gap-2">
                        <Label for="notes">Notes</Label>
                        <textarea
                            id="notes"
                            name="notes"
                            rows="3"
                            :value="order.notes ?? ''"
                            placeholder="Internal notes about this order"
                            class="flex w-full resize-none rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background placeholder:text-muted-foreground focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 focus-visible:outline-none disabled:cursor-not-allowed disabled:opacity-50"
                        />
                        <InputError :message="errors.notes" />
                    </div>

                    <div>
                        <Button type="submit" :disabled="processing">
                            {{ processing ? 'Saving...' : 'Save Changes' }}
                        </Button>
                    </div>
                </Form>
            </div>
        </div>
    </AppLayout>
</template>
