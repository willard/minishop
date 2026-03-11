<script setup lang="ts">
import { Head, Link, useForm } from '@inertiajs/vue3';
import { ArrowLeft, Plus, Trash2 } from 'lucide-vue-next';
import { computed } from 'vue';
import {
    index,
    create,
    store,
} from '@/actions/App/Http/Controllers/Admin/OrderController';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem } from '@/types';

interface Customer {
    id: number;
    name: string;
    email: string;
}

interface ProductVariant {
    id: number;
    sku: string | null;
    price: number;
    stock_quantity: number;
    label: string;
}

interface Product {
    id: number;
    name: string;
    sku: string | null;
    price: number;
    stock_quantity: number;
    variants: ProductVariant[];
}

interface ShippingMethod {
    id: number;
    name: string;
    price: number;
    is_free: boolean;
}

interface StatusOption {
    value: string;
    label: string;
}

const props = defineProps<{
    customers: Customer[];
    products: Product[];
    shippingMethods: ShippingMethod[];
    statuses: StatusOption[];
    taxRate: number;
}>();

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Dashboard', href: '/dashboard' },
    { title: 'Orders', href: index().url },
    { title: 'New Order', href: create().url },
];

const form = useForm({
    customer_id: '' as number | '',
    status: 'pending',
    items: [{ product_id: '' as number | '', variant_id: '' as number | '', quantity: 1, unit_price: '' as number | '' }],
    shipping_name: '',
    shipping_address_line1: '',
    shipping_address_line2: '',
    shipping_city: '',
    shipping_state: '',
    shipping_postcode: '',
    shipping_country: 'PH',
    shipping_method_id: '' as number | '',
    coupon_code: '',
    notes: '',
});

function variantsForItem(i: number): ProductVariant[] {
    const product = props.products.find((p) => p.id === Number(form.items[i].product_id));
    return product?.variants ?? [];
}

function onProductChange(i: number): void {
    form.items[i].variant_id = '';
    const product = props.products.find((p) => p.id === Number(form.items[i].product_id));
    if (product) {
        form.items[i].unit_price = +(product.price / 100).toFixed(2);
    }
}

function onVariantChange(i: number): void {
    const variants = variantsForItem(i);
    const variant = variants.find((v) => v.id === Number(form.items[i].variant_id));
    const product = props.products.find((p) => p.id === Number(form.items[i].product_id));
    if (variant) {
        const price = variant.price ?? product?.price ?? 0;
        form.items[i].unit_price = +(price / 100).toFixed(2);
    }
}

function addItem(): void {
    form.items.push({ product_id: '', variant_id: '', quantity: 1, unit_price: '' });
}

function removeItem(i: number): void {
    form.items.splice(i, 1);
}

function lineTotal(i: number): number {
    const price = Number(form.items[i].unit_price) || 0;
    const qty = Number(form.items[i].quantity) || 0;
    return price * qty;
}

const selectedShipping = computed(() =>
    props.shippingMethods.find((m) => m.id === Number(form.shipping_method_id)),
);

const subtotal = computed(() =>
    form.items.reduce((sum, _, i) => sum + lineTotal(i), 0),
);

const shippingCost = computed(() => {
    if (!selectedShipping.value) return 0;
    return selectedShipping.value.is_free ? 0 : selectedShipping.value.price / 100;
});

const taxAmount = computed(() =>
    Math.round(subtotal.value * props.taxRate) / 100,
);

const orderTotal = computed(() => subtotal.value + shippingCost.value + taxAmount.value);

function formatCurrency(value: number): string {
    return value.toFixed(2);
}

function submit(): void {
    form
        .transform((data) => ({
            ...data,
            customer_id: Number(data.customer_id) || undefined,
            shipping_method_id: data.shipping_method_id ? Number(data.shipping_method_id) : undefined,
            coupon_code: data.coupon_code || undefined,
            shipping_address_line2: data.shipping_address_line2 || undefined,
            notes: data.notes || undefined,
            items: data.items.map((item) => ({
                product_id: Number(item.product_id),
                variant_id: item.variant_id ? Number(item.variant_id) : undefined,
                quantity: Number(item.quantity),
                unit_price: Math.round(Number(item.unit_price) * 100),
            })),
        }))
        .post(store().url);
}
</script>

<template>
    <Head title="New Order" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="p-4">
            <!-- Header -->
            <div class="mb-6 flex items-center gap-4">
                <Link :href="index().url">
                    <Button variant="ghost" size="sm">
                        <ArrowLeft class="size-4" />
                    </Button>
                </Link>
                <div>
                    <h1 class="text-2xl font-semibold">New Order</h1>
                    <p class="text-sm text-muted-foreground">
                        Create an order manually for a customer
                    </p>
                </div>
            </div>

            <form @submit.prevent="submit">
                <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">
                    <!-- Left: main form fields -->
                    <div class="flex flex-col gap-6 lg:col-span-2">
                        <!-- Customer -->
                        <div class="rounded-lg border border-sidebar-border p-5">
                            <h2 class="mb-4 text-sm font-semibold uppercase tracking-wide text-muted-foreground">
                                Customer
                            </h2>
                            <div class="grid gap-1.5">
                                <Label for="customer_id">Customer</Label>
                                <select
                                    id="customer_id"
                                    v-model="form.customer_id"
                                    class="h-9 w-full rounded-md border border-input bg-background px-3 py-1 text-sm shadow-sm transition-colors focus:outline-none focus:ring-1 focus:ring-ring"
                                    :class="{ 'border-destructive': form.errors.customer_id }"
                                >
                                    <option value="">Select a customer…</option>
                                    <option
                                        v-for="c in customers"
                                        :key="c.id"
                                        :value="c.id"
                                    >
                                        {{ c.name }} — {{ c.email }}
                                    </option>
                                </select>
                                <InputError :message="form.errors.customer_id" />
                            </div>
                        </div>

                        <!-- Items -->
                        <div class="rounded-lg border border-sidebar-border p-5">
                            <h2 class="mb-4 text-sm font-semibold uppercase tracking-wide text-muted-foreground">
                                Items
                            </h2>

                            <InputError :message="form.errors.items" class="mb-3" />

                            <div class="flex flex-col gap-4">
                                <div
                                    v-for="(item, i) in form.items"
                                    :key="i"
                                    class="rounded-md border border-sidebar-border p-3"
                                >
                                    <div class="grid grid-cols-1 gap-3 sm:grid-cols-[1fr_1fr]">
                                        <!-- Product -->
                                        <div class="grid gap-1">
                                            <Label class="text-xs">Product</Label>
                                            <select
                                                v-model="item.product_id"
                                                class="h-9 rounded-md border border-input bg-background px-3 py-1 text-sm shadow-sm focus:outline-none focus:ring-1 focus:ring-ring"
                                                :class="{ 'border-destructive': form.errors[`items.${i}.product_id`] }"
                                                @change="onProductChange(i)"
                                            >
                                                <option value="">Select…</option>
                                                <option
                                                    v-for="p in products"
                                                    :key="p.id"
                                                    :value="p.id"
                                                >
                                                    {{ p.name }}{{ p.sku ? ` — ${p.sku}` : '' }}
                                                </option>
                                            </select>
                                            <InputError :message="form.errors[`items.${i}.product_id`]" />
                                        </div>

                                        <!-- Variant (only shown when product has variants) -->
                                        <div v-if="variantsForItem(i).length > 0" class="grid gap-1">
                                            <Label class="text-xs">Variant</Label>
                                            <select
                                                v-model="item.variant_id"
                                                class="h-9 rounded-md border border-input bg-background px-3 py-1 text-sm shadow-sm focus:outline-none focus:ring-1 focus:ring-ring"
                                                :class="{ 'border-destructive': form.errors[`items.${i}.variant_id`] }"
                                                @change="onVariantChange(i)"
                                            >
                                                <option value="">No variant</option>
                                                <option
                                                    v-for="v in variantsForItem(i)"
                                                    :key="v.id"
                                                    :value="v.id"
                                                >
                                                    {{ v.label }}
                                                </option>
                                            </select>
                                            <InputError :message="form.errors[`items.${i}.variant_id`]" />
                                        </div>
                                    </div>

                                    <div class="mt-3 grid grid-cols-[80px_130px_1fr_36px] items-end gap-2">
                                        <!-- Quantity -->
                                        <div class="grid gap-1">
                                            <Label class="text-xs">Qty</Label>
                                            <Input
                                                v-model="item.quantity"
                                                type="number"
                                                min="1"
                                                :class="{ 'border-destructive': form.errors[`items.${i}.quantity`] }"
                                            />
                                            <InputError :message="form.errors[`items.${i}.quantity`]" />
                                        </div>

                                        <!-- Unit price -->
                                        <div class="grid gap-1">
                                            <Label class="text-xs">Unit Price ($)</Label>
                                            <Input
                                                v-model="item.unit_price"
                                                type="number"
                                                min="0"
                                                step="0.01"
                                                :class="{ 'border-destructive': form.errors[`items.${i}.unit_price`] }"
                                            />
                                            <InputError :message="form.errors[`items.${i}.unit_price`]" />
                                        </div>

                                        <!-- Line total -->
                                        <div class="flex h-9 items-center text-sm font-medium">
                                            ${{ formatCurrency(lineTotal(i)) }}
                                        </div>

                                        <!-- Remove -->
                                        <Button
                                            type="button"
                                            variant="ghost"
                                            size="sm"
                                            class="text-muted-foreground hover:text-destructive"
                                            :disabled="form.items.length === 1"
                                            @click="removeItem(i)"
                                        >
                                            <Trash2 class="size-4" />
                                        </Button>
                                    </div>
                                </div>
                            </div>

                            <Button
                                type="button"
                                variant="outline"
                                size="sm"
                                class="mt-4"
                                @click="addItem"
                            >
                                <Plus class="mr-1.5 size-4" />
                                Add Item
                            </Button>
                        </div>

                        <!-- Shipping Address -->
                        <div class="rounded-lg border border-sidebar-border p-5">
                            <h2 class="mb-4 text-sm font-semibold uppercase tracking-wide text-muted-foreground">
                                Shipping Address
                            </h2>
                            <div class="grid gap-4">
                                <div class="grid gap-1.5">
                                    <Label for="shipping_name">Full Name</Label>
                                    <Input
                                        id="shipping_name"
                                        v-model="form.shipping_name"
                                        placeholder="Recipient name"
                                        :class="{ 'border-destructive': form.errors.shipping_name }"
                                    />
                                    <InputError :message="form.errors.shipping_name" />
                                </div>

                                <div class="grid gap-1.5">
                                    <Label for="shipping_address_line1">Address Line 1</Label>
                                    <Input
                                        id="shipping_address_line1"
                                        v-model="form.shipping_address_line1"
                                        placeholder="Street address"
                                        :class="{ 'border-destructive': form.errors.shipping_address_line1 }"
                                    />
                                    <InputError :message="form.errors.shipping_address_line1" />
                                </div>

                                <div class="grid gap-1.5">
                                    <Label for="shipping_address_line2">Address Line 2 <span class="text-muted-foreground">(optional)</span></Label>
                                    <Input
                                        id="shipping_address_line2"
                                        v-model="form.shipping_address_line2"
                                        placeholder="Apt, unit, floor, etc."
                                    />
                                </div>

                                <div class="grid grid-cols-2 gap-4">
                                    <div class="grid gap-1.5">
                                        <Label for="shipping_city">City</Label>
                                        <Input
                                            id="shipping_city"
                                            v-model="form.shipping_city"
                                            :class="{ 'border-destructive': form.errors.shipping_city }"
                                        />
                                        <InputError :message="form.errors.shipping_city" />
                                    </div>
                                    <div class="grid gap-1.5">
                                        <Label for="shipping_state">State / Province</Label>
                                        <Input
                                            id="shipping_state"
                                            v-model="form.shipping_state"
                                            :class="{ 'border-destructive': form.errors.shipping_state }"
                                        />
                                        <InputError :message="form.errors.shipping_state" />
                                    </div>
                                </div>

                                <div class="grid grid-cols-2 gap-4">
                                    <div class="grid gap-1.5">
                                        <Label for="shipping_postcode">Postcode</Label>
                                        <Input
                                            id="shipping_postcode"
                                            v-model="form.shipping_postcode"
                                            :class="{ 'border-destructive': form.errors.shipping_postcode }"
                                        />
                                        <InputError :message="form.errors.shipping_postcode" />
                                    </div>
                                    <div class="grid gap-1.5">
                                        <Label for="shipping_country">Country Code</Label>
                                        <Input
                                            id="shipping_country"
                                            v-model="form.shipping_country"
                                            maxlength="2"
                                            placeholder="PH"
                                            :class="{ 'border-destructive': form.errors.shipping_country }"
                                        />
                                        <InputError :message="form.errors.shipping_country" />
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Right: summary + extras -->
                    <div class="flex flex-col gap-4 lg:sticky lg:top-4 lg:self-start">
                        <!-- Order Details -->
                        <div class="rounded-lg border border-sidebar-border p-5">
                            <h2 class="mb-4 text-sm font-semibold uppercase tracking-wide text-muted-foreground">
                                Order Details
                            </h2>
                            <div class="flex flex-col gap-4">
                                <div class="grid gap-1.5">
                                    <Label for="status">Status</Label>
                                    <select
                                        id="status"
                                        v-model="form.status"
                                        class="h-9 w-full rounded-md border border-input bg-background px-3 py-1 text-sm shadow-sm focus:outline-none focus:ring-1 focus:ring-ring"
                                    >
                                        <option
                                            v-for="s in statuses"
                                            :key="s.value"
                                            :value="s.value"
                                        >
                                            {{ s.label }}
                                        </option>
                                    </select>
                                    <InputError :message="form.errors.status" />
                                </div>

                                <div class="grid gap-1.5">
                                    <Label for="shipping_method_id">Shipping Method</Label>
                                    <select
                                        id="shipping_method_id"
                                        v-model="form.shipping_method_id"
                                        class="h-9 w-full rounded-md border border-input bg-background px-3 py-1 text-sm shadow-sm focus:outline-none focus:ring-1 focus:ring-ring"
                                    >
                                        <option value="">No shipping</option>
                                        <option
                                            v-for="m in shippingMethods"
                                            :key="m.id"
                                            :value="m.id"
                                        >
                                            {{ m.name }}
                                            {{ m.is_free ? '(Free)' : `($${(m.price / 100).toFixed(2)})` }}
                                        </option>
                                    </select>
                                </div>

                                <div class="grid gap-1.5">
                                    <Label for="coupon_code">Coupon Code <span class="text-muted-foreground">(optional)</span></Label>
                                    <Input
                                        id="coupon_code"
                                        v-model="form.coupon_code"
                                        placeholder="e.g. SUMMER20"
                                        :class="{ 'border-destructive': form.errors.coupon_code }"
                                    />
                                    <InputError :message="form.errors.coupon_code" />
                                </div>

                                <div class="grid gap-1.5">
                                    <Label for="notes">Notes <span class="text-muted-foreground">(optional)</span></Label>
                                    <textarea
                                        id="notes"
                                        v-model="form.notes"
                                        rows="3"
                                        placeholder="Internal notes about this order…"
                                        class="w-full rounded-md border border-input bg-background px-3 py-2 text-sm shadow-sm placeholder:text-muted-foreground focus:outline-none focus:ring-1 focus:ring-ring"
                                    />
                                </div>
                            </div>
                        </div>

                        <!-- Order Summary -->
                        <div class="rounded-lg border border-sidebar-border p-5">
                            <h2 class="mb-4 text-sm font-semibold uppercase tracking-wide text-muted-foreground">
                                Order Summary
                            </h2>
                            <div class="flex flex-col gap-2 text-sm">
                                <div class="flex justify-between">
                                    <span class="text-muted-foreground">Subtotal</span>
                                    <span>${{ formatCurrency(subtotal) }}</span>
                                </div>
                                <div class="flex justify-between">
                                    <span class="text-muted-foreground">Shipping</span>
                                    <span>
                                        <template v-if="selectedShipping">
                                            {{ selectedShipping.is_free ? 'Free' : `$${formatCurrency(shippingCost)}` }}
                                        </template>
                                        <template v-else>—</template>
                                    </span>
                                </div>
                                <div v-if="taxRate > 0" class="flex justify-between">
                                    <span class="text-muted-foreground">Tax ({{ taxRate }}%)</span>
                                    <span>${{ formatCurrency(taxAmount) }}</span>
                                </div>
                                <div v-if="form.coupon_code" class="flex justify-between text-muted-foreground">
                                    <span>Coupon <span class="font-mono text-xs">{{ form.coupon_code }}</span></span>
                                    <span class="text-xs italic">applied on submit</span>
                                </div>
                                <div class="mt-2 flex justify-between border-t border-sidebar-border pt-2 font-semibold">
                                    <span>Total</span>
                                    <span>${{ formatCurrency(orderTotal) }}</span>
                                </div>
                            </div>
                        </div>

                        <Button
                            type="submit"
                            class="w-full"
                            :disabled="form.processing"
                        >
                            {{ form.processing ? 'Creating…' : 'Create Order' }}
                        </Button>
                    </div>
                </div>
            </form>
        </div>
    </AppLayout>
</template>
