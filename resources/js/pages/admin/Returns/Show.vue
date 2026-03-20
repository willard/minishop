<script setup lang="ts">
import { Form, Head, Link, router } from '@inertiajs/vue3';
import { ArrowLeft, CheckCircle, Package, RefreshCw, XCircle } from 'lucide-vue-next';
import {
    index,
    approve,
    reject,
    receive,
    refund,
    update,
} from '@/actions/App/Http/Controllers/Admin/ReturnController';
import InputError from '@/components/InputError.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Label } from '@/components/ui/label';
import { usePrice } from '@/composables/usePrice';
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem } from '@/types';

interface ReturnItem {
    id: number;
    order_item_id: number;
    quantity: number;
    unit_price: number;
    subtotal: number;
    order_item: {
        id: number;
        product_name: string;
        product_sku: string | null;
        quantity: number;
        unit_price: number;
    } | null;
}

interface OrderSummary {
    id: number;
    order_number: string;
    total_amount: number;
    status: string;
    status_label: string;
}

interface OrderReturn {
    id: number;
    return_number: string;
    order_id: number;
    status: string;
    status_label: string;
    reason: string;
    reason_label: string;
    notes: string | null;
    admin_notes: string | null;
    refund_amount: number;
    stripe_refund_id: string | null;
    restocked: boolean;
    refunded_at: string | null;
    created_at: string;
    allowed_transitions: string[];
    order: OrderSummary;
    items: ReturnItem[];
}

const props = defineProps<{
    orderReturn: OrderReturn;
    statuses: { value: string; label: string }[];
    reasons: { value: string; label: string }[];
}>();

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Dashboard', href: '/dashboard' },
    { title: 'Returns', href: index().url },
    { title: props.orderReturn.return_number, href: '#' },
];

const { formatPrice } = usePrice();

function statusVariant(
    status: string,
): 'default' | 'secondary' | 'destructive' | 'outline' {
    switch (status) {
        case 'refunded':
            return 'default';
        case 'rejected':
            return 'destructive';
        case 'approved':
        case 'received':
            return 'outline';
        default:
            return 'secondary';
    }
}

function canTransitionTo(status: string): boolean {
    return props.orderReturn.allowed_transitions.includes(status);
}

</script>

<template>
    <Head :title="`Return ${orderReturn.return_number}`" />

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
                            {{ orderReturn.return_number }}
                        </h1>
                        <Badge
                            :variant="statusVariant(orderReturn.status)"
                            class="capitalize"
                        >
                            {{ orderReturn.status_label }}
                        </Badge>
                    </div>
                    <p class="text-sm text-muted-foreground">
                        Submitted
                        {{
                            new Date(orderReturn.created_at).toLocaleDateString(
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
            </div>

            <!-- Summary cards -->
            <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                <!-- Order info -->
                <div class="space-y-2 rounded-lg border border-sidebar-border p-4">
                    <h2 class="text-sm font-semibold tracking-wide text-muted-foreground uppercase">
                        Order
                    </h2>
                    <p class="font-mono font-medium">{{ orderReturn.order.order_number }}</p>
                    <p class="text-sm text-muted-foreground">
                        Total: ${{ formatPrice(orderReturn.order.total_amount) }}
                    </p>
                    <Badge :variant="statusVariant(orderReturn.order.status)" class="capitalize text-xs">
                        {{ orderReturn.order.status_label }}
                    </Badge>
                </div>

                <!-- Return info -->
                <div class="space-y-2 rounded-lg border border-sidebar-border p-4">
                    <h2 class="text-sm font-semibold tracking-wide text-muted-foreground uppercase">
                        Return Details
                    </h2>
                    <p class="text-sm">
                        <span class="text-muted-foreground">Reason: </span>
                        {{ orderReturn.reason_label }}
                    </p>
                    <p v-if="orderReturn.notes" class="text-sm text-muted-foreground">
                        {{ orderReturn.notes }}
                    </p>
                    <div class="flex gap-4 pt-1">
                        <span class="text-sm">
                            <span class="text-muted-foreground">Restocked: </span>
                            {{ orderReturn.restocked ? 'Yes' : 'No' }}
                        </span>
                        <span v-if="orderReturn.refund_amount > 0" class="text-sm">
                            <span class="text-muted-foreground">Refunded: </span>
                            ${{ formatPrice(orderReturn.refund_amount) }}
                        </span>
                    </div>
                </div>
            </div>

            <!-- Return items -->
            <div class="overflow-hidden rounded-lg border border-sidebar-border">
                <div class="border-b border-sidebar-border bg-muted/50 px-4 py-3">
                    <h2 class="text-sm font-semibold tracking-wide text-muted-foreground uppercase">
                        Returned Items
                    </h2>
                </div>
                <table class="w-full text-sm">
                    <thead class="text-muted-foreground">
                        <tr>
                            <th class="px-4 py-3 text-left font-medium">Product</th>
                            <th class="px-4 py-3 text-left font-medium">SKU</th>
                            <th class="px-4 py-3 text-right font-medium">Unit Price</th>
                            <th class="px-4 py-3 text-right font-medium">Qty</th>
                            <th class="px-4 py-3 text-right font-medium">Subtotal</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-sidebar-border">
                        <tr
                            v-for="item in orderReturn.items"
                            :key="item.id"
                            class="hover:bg-muted/30"
                        >
                            <td class="px-4 py-3 font-medium">
                                {{ item.order_item?.product_name ?? '—' }}
                            </td>
                            <td class="px-4 py-3 font-mono text-xs text-muted-foreground">
                                {{ item.order_item?.product_sku ?? '—' }}
                            </td>
                            <td class="px-4 py-3 text-right">
                                ${{ formatPrice(item.unit_price) }}
                            </td>
                            <td class="px-4 py-3 text-right">{{ item.quantity }}</td>
                            <td class="px-4 py-3 text-right font-medium">
                                ${{ formatPrice(item.subtotal) }}
                            </td>
                        </tr>
                    </tbody>
                    <tfoot>
                        <tr class="border-t border-sidebar-border bg-muted/20">
                            <td colspan="4" class="px-4 py-2 text-right text-sm font-semibold">
                                Total Refundable
                            </td>
                            <td class="px-4 py-2 text-right text-sm font-semibold">
                                ${{
                                    formatPrice(
                                        orderReturn.items.reduce(
                                            (sum, item) => sum + item.subtotal,
                                            0,
                                        ),
                                    )
                                }}
                            </td>
                        </tr>
                    </tfoot>
                </table>
            </div>

            <!-- Stripe refund info -->
            <div
                v-if="orderReturn.stripe_refund_id"
                class="rounded-lg border border-sidebar-border p-4"
            >
                <h2 class="mb-2 text-sm font-semibold tracking-wide text-muted-foreground uppercase">
                    Stripe Refund
                </h2>
                <p class="font-mono text-sm">{{ orderReturn.stripe_refund_id }}</p>
                <p v-if="orderReturn.refunded_at" class="mt-1 text-sm text-muted-foreground">
                    Issued on
                    {{
                        new Date(orderReturn.refunded_at).toLocaleDateString('en-US', {
                            year: 'numeric',
                            month: 'long',
                            day: 'numeric',
                        })
                    }}
                </p>
            </div>

            <!-- Actions -->
            <div class="rounded-lg border border-sidebar-border p-4">
                <h2 class="mb-4 font-semibold">Actions</h2>
                <div class="flex flex-wrap gap-3">
                    <Button
                        v-if="canTransitionTo('approved')"
                        variant="default"
                        @click="router.post(approve(orderReturn.return_number).url)"
                    >
                        <CheckCircle class="mr-2 size-4" />
                        Approve Return
                    </Button>
                    <Button
                        v-if="canTransitionTo('rejected')"
                        variant="destructive"
                        @click="router.post(reject(orderReturn.return_number).url)"
                    >
                        <XCircle class="mr-2 size-4" />
                        Reject Return
                    </Button>
                    <Button
                        v-if="canTransitionTo('received')"
                        variant="outline"
                        @click="router.post(receive(orderReturn.return_number).url)"
                    >
                        <Package class="mr-2 size-4" />
                        Mark as Received &amp; Restock
                    </Button>
                    <Button
                        v-if="canTransitionTo('refunded')"
                        variant="default"
                        @click="router.post(refund(orderReturn.return_number).url)"
                    >
                        <RefreshCw class="mr-2 size-4" />
                        Issue Stripe Refund
                    </Button>
                </div>
                <p
                    v-if="orderReturn.allowed_transitions.length === 0"
                    class="text-sm text-muted-foreground"
                >
                    No further actions available for this return.
                </p>
            </div>

            <!-- Admin notes -->
            <div class="rounded-lg border border-sidebar-border p-4">
                <h2 class="mb-4 font-semibold">Admin Notes</h2>
                <Form
                    v-bind="update.form(orderReturn.return_number)"
                    class="flex flex-col gap-4"
                    v-slot="{ errors, processing }"
                >
                    <div class="grid gap-2">
                        <Label for="admin_notes">Notes (internal only)</Label>
                        <textarea
                            id="admin_notes"
                            name="admin_notes"
                            rows="4"
                            :value="orderReturn.admin_notes ?? ''"
                            placeholder="Add internal notes about this return..."
                            class="flex w-full resize-none rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background placeholder:text-muted-foreground focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 focus-visible:outline-none disabled:cursor-not-allowed disabled:opacity-50"
                        />
                        <InputError :message="errors.admin_notes" />
                    </div>
                    <div>
                        <Button type="submit" :disabled="processing">
                            {{ processing ? 'Saving...' : 'Save Notes' }}
                        </Button>
                    </div>
                </Form>
            </div>
        </div>
    </AppLayout>
</template>
