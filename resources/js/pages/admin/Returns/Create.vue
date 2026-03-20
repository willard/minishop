<script setup lang="ts">
import { Head, Link, router, useForm } from '@inertiajs/vue3';
import { ArrowLeft, Minus, Plus, Search } from 'lucide-vue-next';
import { computed, ref } from 'vue';
import {
    create,
    index,
    store,
} from '@/actions/App/Http/Controllers/Admin/ReturnController';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { usePrice } from '@/composables/usePrice';
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem } from '@/types';

interface ProductVariant {
    id: number;
    sku: string | null;
}

interface OrderItem {
    id: number;
    product_name: string;
    product_sku: string | null;
    unit_price: number;
    quantity: number;
    variant_id: number | null;
    variant: ProductVariant | null;
}

interface Order {
    id: number;
    order_number: string;
    total_amount: number;
    items: OrderItem[];
}

interface Reason {
    value: string;
    label: string;
}

const props = defineProps<{
    order: Order | null;
    reasons: Reason[];
}>();

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Dashboard', href: '/dashboard' },
    { title: 'Returns', href: index().url },
    { title: 'New Return', href: '#' },
];

interface ReturnItemEntry {
    order_item_id: number;
    quantity: number;
    max_quantity: number;
    product_name: string;
    product_sku: string | null;
    unit_price: number;
}

const orderNumberInput = ref('');
const selectedItems = ref<ReturnItemEntry[]>([]);

if (props.order) {
    selectedItems.value = props.order.items.map((item) => ({
        order_item_id: item.id,
        quantity: 1,
        max_quantity: item.quantity,
        product_name: item.product_name,
        product_sku: item.product_sku,
        unit_price: item.unit_price,
    }));
}

const form = useForm({
    order_id: props.order?.id ?? null as number | null,
    reason: '',
    notes: '',
    admin_notes: '',
    items: [] as { order_item_id: number; quantity: number }[],
});

const { formatPrice } = usePrice();

const refundTotal = computed(() =>
    selectedItems.value.reduce(
        (sum, item) => sum + item.unit_price * item.quantity,
        0,
    ),
);

function incrementQty(item: ReturnItemEntry): void {
    if (item.quantity < item.max_quantity) {
        item.quantity++;
    }
}

function decrementQty(item: ReturnItemEntry): void {
    if (item.quantity > 1) {
        item.quantity--;
    }
}

function findOrder(): void {
    if (orderNumberInput.value.trim()) {
        router.get(create({ mergeQuery: { order_number: orderNumberInput.value.trim() } }).url);
    }
}

function submit(): void {
    form.order_id = props.order!.id;
    form.items = selectedItems.value.map((item) => ({
        order_item_id: item.order_item_id,
        quantity: item.quantity,
    }));
    form.post(store().url);
}
</script>

<template>
    <Head title="New Return" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex max-w-2xl flex-col gap-6 p-4">
            <!-- Header -->
            <div class="flex items-center gap-4">
                <Link :href="index().url">
                    <Button variant="ghost" size="sm">
                        <ArrowLeft class="size-4" />
                    </Button>
                </Link>
                <h1 class="text-2xl font-semibold">New Return</h1>
            </div>

            <form class="flex flex-col gap-6" @submit.prevent="submit">
                <!-- Order info -->
                <div v-if="order" class="rounded-lg border border-sidebar-border p-4">
                    <h2 class="mb-2 text-sm font-semibold tracking-wide text-muted-foreground uppercase">
                        Order
                    </h2>
                    <p class="font-mono font-medium">{{ order.order_number }}</p>
                    <p class="text-sm text-muted-foreground">
                        Total: ${{ formatPrice(order.total_amount) }}
                    </p>
                </div>

                <div v-else class="rounded-lg border border-sidebar-border p-4">
                    <h2 class="mb-3 text-sm font-semibold tracking-wide text-muted-foreground uppercase">
                        Find Order
                    </h2>
                    <div class="flex gap-2">
                        <Input
                            id="order_number"
                            v-model="orderNumberInput"
                            type="text"
                            placeholder="e.g. ORD-000001"
                            class="font-mono"
                            @keydown.enter.prevent="findOrder"
                        />
                        <Button type="button" variant="outline" @click="findOrder">
                            <Search class="mr-2 size-4" />
                            Find
                        </Button>
                    </div>
                </div>

                <!-- Reason -->
                <div class="grid gap-2">
                    <Label for="reason">Return Reason</Label>
                    <select
                        id="reason"
                        v-model="form.reason"
                        class="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 focus-visible:outline-none"
                        required
                    >
                        <option value="" disabled>Select a reason...</option>
                        <option
                            v-for="r in reasons"
                            :key="r.value"
                            :value="r.value"
                        >
                            {{ r.label }}
                        </option>
                    </select>
                    <InputError :message="form.errors.reason" />
                </div>

                <!-- Items -->
                <div v-if="order" class="flex flex-col gap-2">
                    <Label>Items to Return</Label>
                    <div class="overflow-hidden rounded-lg border border-sidebar-border">
                        <table class="w-full text-sm">
                            <thead class="bg-muted/50 text-muted-foreground">
                                <tr>
                                    <th class="px-4 py-3 text-left font-medium">Product</th>
                                    <th class="px-4 py-3 text-right font-medium">Unit Price</th>
                                    <th class="px-4 py-3 text-center font-medium">Qty to Return</th>
                                    <th class="px-4 py-3 text-right font-medium">Subtotal</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-sidebar-border">
                                <tr
                                    v-for="item in selectedItems"
                                    :key="item.order_item_id"
                                    class="hover:bg-muted/30"
                                >
                                    <td class="px-4 py-3">
                                        <p class="font-medium">{{ item.product_name }}</p>
                                        <p
                                            v-if="item.product_sku"
                                            class="font-mono text-xs text-muted-foreground"
                                        >
                                            {{ item.product_sku }}
                                        </p>
                                        <p class="text-xs text-muted-foreground">
                                            Ordered: {{ item.max_quantity }}
                                        </p>
                                    </td>
                                    <td class="px-4 py-3 text-right">
                                        ${{ formatPrice(item.unit_price) }}
                                    </td>
                                    <td class="px-4 py-3">
                                        <div class="flex items-center justify-center gap-2">
                                            <button
                                                type="button"
                                                class="flex size-7 items-center justify-center rounded border border-input bg-background hover:bg-muted disabled:opacity-50"
                                                :disabled="item.quantity <= 1"
                                                @click="decrementQty(item)"
                                            >
                                                <Minus class="size-3" />
                                            </button>
                                            <span class="w-8 text-center font-medium">
                                                {{ item.quantity }}
                                            </span>
                                            <button
                                                type="button"
                                                class="flex size-7 items-center justify-center rounded border border-input bg-background hover:bg-muted disabled:opacity-50"
                                                :disabled="item.quantity >= item.max_quantity"
                                                @click="incrementQty(item)"
                                            >
                                                <Plus class="size-3" />
                                            </button>
                                        </div>
                                    </td>
                                    <td class="px-4 py-3 text-right font-medium">
                                        ${{ formatPrice(item.unit_price * item.quantity) }}
                                    </td>
                                </tr>
                            </tbody>
                            <tfoot>
                                <tr class="border-t border-sidebar-border bg-muted/20">
                                    <td colspan="3" class="px-4 py-2 text-right text-sm font-semibold">
                                        Refund Total
                                    </td>
                                    <td class="px-4 py-2 text-right text-sm font-semibold">
                                        ${{ formatPrice(refundTotal) }}
                                    </td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                    <InputError :message="form.errors.items" />
                </div>

                <!-- Customer notes -->
                <div class="grid gap-2">
                    <Label for="notes">Customer Notes</Label>
                    <textarea
                        id="notes"
                        v-model="form.notes"
                        rows="3"
                        placeholder="Reason details from the customer..."
                        class="flex w-full resize-none rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background placeholder:text-muted-foreground focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 focus-visible:outline-none disabled:cursor-not-allowed disabled:opacity-50"
                    />
                    <InputError :message="form.errors.notes" />
                </div>

                <!-- Admin notes -->
                <div class="grid gap-2">
                    <Label for="admin_notes">Admin Notes <span class="text-muted-foreground">(internal)</span></Label>
                    <textarea
                        id="admin_notes"
                        v-model="form.admin_notes"
                        rows="2"
                        placeholder="Internal notes..."
                        class="flex w-full resize-none rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background placeholder:text-muted-foreground focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 focus-visible:outline-none disabled:cursor-not-allowed disabled:opacity-50"
                    />
                    <InputError :message="form.errors.admin_notes" />
                </div>

                <div v-if="order" class="flex gap-3">
                    <Button type="submit" :disabled="form.processing">
                        {{ form.processing ? 'Creating...' : 'Create Return' }}
                    </Button>
                    <Link :href="index().url">
                        <Button type="button" variant="outline">Cancel</Button>
                    </Link>
                </div>
            </form>
        </div>
    </AppLayout>
</template>
