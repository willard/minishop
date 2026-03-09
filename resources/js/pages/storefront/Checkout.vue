<script setup lang="ts">
import { Head, Link, useForm, usePage } from '@inertiajs/vue3';
import {
    ShoppingBag,
    Trash2,
    Tag,
    ChevronDown,
    ChevronUp,
    Truck,
} from 'lucide-vue-next';
import { computed, ref } from 'vue';
import { store } from '@/actions/App/Http/Controllers/Storefront/CheckoutController';
import { index as productsIndex } from '@/actions/App/Http/Controllers/Storefront/ProductController';
import { useCart } from '@/composables/useCart';
import StorefrontLayout from '@/layouts/StorefrontLayout.vue';
import { usePrice } from '@/composables/usePrice';

interface ShippingMethod {
    id: number;
    name: string;
    description: string | null;
    price: number;
    is_free: boolean;
}

const page = usePage<{
    storeSettings: {
        currency: string;
        currencyLocale: string;
        taxRate: number;
    };
    shippingMethods: ShippingMethod[];
}>();

const {
    cartItems,
    itemCount,
    subtotal,
    removeItem,
    updateQuantity,
    clearCart,
} = useCart();

const { formatPrice: price } = usePrice();

const shippingMethods = computed(() => page.props.shippingMethods ?? []);
const storeSettings = computed(() => page.props.storeSettings);

const couponExpanded = ref(false);
const notesExpanded = ref(false);
const orderSummaryExpanded = ref(true);

const form = useForm({
    name: '',
    email: '',
    phone: '',
    address_line1: '',
    address_line2: '',
    city: '',
    state: '',
    postcode: '',
    country: 'PH',
    shipping_method_id: computed(
        () => shippingMethods.value[0]?.id ?? null,
    ) as unknown as number | null,
    coupon_code: '',
    notes: '',
    items: computed(() =>
        cartItems.value.map((item) => ({
            product_id: item.productId,
            variant_id: item.variantId,
            quantity: item.quantity,
        })),
    ),
});

const selectedShippingMethod = computed(
    () =>
        shippingMethods.value.find((m) => m.id === form.shipping_method_id) ??
        shippingMethods.value[0] ??
        null,
);

const shippingAmount = computed(() => {
    if (!selectedShippingMethod.value) return 0;
    return selectedShippingMethod.value.is_free
        ? 0
        : selectedShippingMethod.value.price;
});

const taxRate = computed(() => (storeSettings.value?.taxRate ?? 12) / 100);
const taxAmount = computed(() => Math.round(subtotal.value * taxRate.value));
const total = computed(
    () => subtotal.value + shippingAmount.value + taxAmount.value,
);

const currency = computed(() => storeSettings.value?.currency ?? 'PHP');
const locale = computed(() => storeSettings.value?.currencyLocale ?? 'en-PH');

function submit(): void {
    form.post(store().url, {
        onSuccess: () => clearCart(),
    });
}
</script>

<template>
    <Head title="Checkout" />

    <StorefrontLayout>
        <div class="mx-auto max-w-7xl px-6 py-10">
            <div
                class="mb-10 flex flex-col justify-between gap-4 sm:flex-row sm:items-center"
            >
                <h1
                    class="text-4xl font-semibold"
                    style="
                        font-family: 'Cormorant Garamond', serif;
                        color: #1c1a17;
                    "
                >
                    Checkout
                </h1>
                <Link
                    :href="productsIndex().url"
                    class="text-xs font-semibold tracking-widest uppercase opacity-40 transition-opacity hover:opacity-100"
                >
                    &larr; Back to shopping
                </Link>
            </div>

            <!-- Empty cart -->
            <div v-if="itemCount === 0" class="py-20 text-center">
                <ShoppingBag
                    class="mx-auto mb-4 size-12"
                    style="color: rgba(28, 26, 23, 0.25)"
                />
                <p class="mb-2 text-xl font-semibold" style="color: #1c1a17">
                    Your cart is empty
                </p>
                <p class="mb-8 text-sm" style="color: rgba(28, 26, 23, 0.5)">
                    Add some products before checking out.
                </p>
                <Link
                    :href="productsIndex().url"
                    class="inline-block rounded-full px-8 py-3 text-sm font-semibold tracking-widest text-white uppercase transition-opacity hover:opacity-80"
                    style="background-color: #1c1a17"
                >
                    Shop Now
                </Link>
            </div>

            <!-- Checkout form -->
            <div
                v-else
                class="grid grid-cols-1 gap-12 lg:grid-cols-[1fr_400px]"
            >
                <!-- Left: Form -->
                <div class="space-y-8">
                    <!-- Contact information -->
                    <section>
                        <h2
                            class="mb-5 text-lg font-semibold"
                            style="color: #1c1a17"
                        >
                            Contact Information
                        </h2>
                        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                            <div class="sm:col-span-2">
                                <label
                                    class="mb-1.5 block text-sm font-medium"
                                    style="color: #1c1a17"
                                >
                                    Full Name
                                    <span style="color: #c05c3a">*</span>
                                </label>
                                <input
                                    v-model="form.name"
                                    type="text"
                                    autocomplete="name"
                                    class="w-full rounded-xl border px-4 py-3 text-sm transition-colors outline-none"
                                    :class="{
                                        'border-[#c05c3a] ring-1 ring-[#c05c3a]':
                                            form.errors.name,
                                    }"
                                    style="
                                        border-color: rgba(28, 26, 23, 0.2);
                                        background-color: #f9f6f0;
                                        color: #1c1a17;
                                    "
                                    placeholder="Maria Santos"
                                />
                                <p
                                    v-if="form.errors.name"
                                    class="mt-1.5 text-xs"
                                    style="color: #c05c3a"
                                >
                                    {{ form.errors.name }}
                                </p>
                            </div>
                            <div>
                                <label
                                    class="mb-1.5 block text-sm font-medium"
                                    style="color: #1c1a17"
                                >
                                    Email <span style="color: #c05c3a">*</span>
                                </label>
                                <input
                                    v-model="form.email"
                                    type="email"
                                    autocomplete="email"
                                    class="w-full rounded-xl border px-4 py-3 text-sm transition-colors outline-none"
                                    :class="{
                                        'border-[#c05c3a] ring-1 ring-[#c05c3a]':
                                            form.errors.email,
                                    }"
                                    style="
                                        border-color: rgba(28, 26, 23, 0.2);
                                        background-color: #f9f6f0;
                                        color: #1c1a17;
                                    "
                                    placeholder="maria@example.com"
                                />
                                <p
                                    v-if="form.errors.email"
                                    class="mt-1.5 text-xs"
                                    style="color: #c05c3a"
                                >
                                    {{ form.errors.email }}
                                </p>
                            </div>
                            <div>
                                <label
                                    class="mb-1.5 block text-sm font-medium"
                                    style="color: #1c1a17"
                                >
                                    Phone
                                </label>
                                <input
                                    v-model="form.phone"
                                    type="tel"
                                    autocomplete="tel"
                                    class="w-full rounded-xl border px-4 py-3 text-sm transition-colors outline-none"
                                    style="
                                        border-color: rgba(28, 26, 23, 0.2);
                                        background-color: #f9f6f0;
                                        color: #1c1a17;
                                    "
                                    placeholder="+63 912 345 6789"
                                />
                            </div>
                        </div>
                    </section>

                    <!-- Shipping address -->
                    <section>
                        <h2
                            class="mb-5 text-lg font-semibold"
                            style="color: #1c1a17"
                        >
                            Shipping Address
                        </h2>
                        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                            <div class="sm:col-span-2">
                                <label
                                    class="mb-1.5 block text-sm font-medium"
                                    style="color: #1c1a17"
                                >
                                    Address Line 1
                                    <span style="color: #c05c3a">*</span>
                                </label>
                                <input
                                    v-model="form.address_line1"
                                    type="text"
                                    autocomplete="address-line1"
                                    class="w-full rounded-xl border px-4 py-3 text-sm transition-colors outline-none"
                                    :class="{
                                        'border-[#c05c3a] ring-1 ring-[#c05c3a]':
                                            form.errors.address_line1,
                                    }"
                                    style="
                                        border-color: rgba(28, 26, 23, 0.2);
                                        background-color: #f9f6f0;
                                        color: #1c1a17;
                                    "
                                    placeholder="123 Rizal Street"
                                />
                                <p
                                    v-if="form.errors.address_line1"
                                    class="mt-1.5 text-xs"
                                    style="color: #c05c3a"
                                >
                                    {{ form.errors.address_line1 }}
                                </p>
                            </div>
                            <div class="sm:col-span-2">
                                <label
                                    class="mb-1.5 block text-sm font-medium"
                                    style="color: #1c1a17"
                                >
                                    Address Line 2
                                </label>
                                <input
                                    v-model="form.address_line2"
                                    type="text"
                                    autocomplete="address-line2"
                                    class="w-full rounded-xl border px-4 py-3 text-sm transition-colors outline-none"
                                    style="
                                        border-color: rgba(28, 26, 23, 0.2);
                                        background-color: #f9f6f0;
                                        color: #1c1a17;
                                    "
                                    placeholder="Apt, Suite, Unit (optional)"
                                />
                            </div>
                            <div>
                                <label
                                    class="mb-1.5 block text-sm font-medium"
                                    style="color: #1c1a17"
                                >
                                    City <span style="color: #c05c3a">*</span>
                                </label>
                                <input
                                    v-model="form.city"
                                    type="text"
                                    autocomplete="address-level2"
                                    class="w-full rounded-xl border px-4 py-3 text-sm transition-colors outline-none"
                                    :class="{
                                        'border-[#c05c3a] ring-1 ring-[#c05c3a]':
                                            form.errors.city,
                                    }"
                                    style="
                                        border-color: rgba(28, 26, 23, 0.2);
                                        background-color: #f9f6f0;
                                        color: #1c1a17;
                                    "
                                    placeholder="Makati"
                                />
                                <p
                                    v-if="form.errors.city"
                                    class="mt-1.5 text-xs"
                                    style="color: #c05c3a"
                                >
                                    {{ form.errors.city }}
                                </p>
                            </div>
                            <div>
                                <label
                                    class="mb-1.5 block text-sm font-medium"
                                    style="color: #1c1a17"
                                >
                                    Province / State
                                    <span style="color: #c05c3a">*</span>
                                </label>
                                <input
                                    v-model="form.state"
                                    type="text"
                                    autocomplete="address-level1"
                                    class="w-full rounded-xl border px-4 py-3 text-sm transition-colors outline-none"
                                    :class="{
                                        'border-[#c05c3a] ring-1 ring-[#c05c3a]':
                                            form.errors.state,
                                    }"
                                    style="
                                        border-color: rgba(28, 26, 23, 0.2);
                                        background-color: #f9f6f0;
                                        color: #1c1a17;
                                    "
                                    placeholder="Metro Manila"
                                />
                                <p
                                    v-if="form.errors.state"
                                    class="mt-1.5 text-xs"
                                    style="color: #c05c3a"
                                >
                                    {{ form.errors.state }}
                                </p>
                            </div>
                            <div>
                                <label
                                    class="mb-1.5 block text-sm font-medium"
                                    style="color: #1c1a17"
                                >
                                    Postal Code
                                    <span style="color: #c05c3a">*</span>
                                </label>
                                <input
                                    v-model="form.postcode"
                                    type="text"
                                    autocomplete="postal-code"
                                    class="w-full rounded-xl border px-4 py-3 text-sm transition-colors outline-none"
                                    :class="{
                                        'border-[#c05c3a] ring-1 ring-[#c05c3a]':
                                            form.errors.postcode,
                                    }"
                                    style="
                                        border-color: rgba(28, 26, 23, 0.2);
                                        background-color: #f9f6f0;
                                        color: #1c1a17;
                                    "
                                    placeholder="1200"
                                />
                                <p
                                    v-if="form.errors.postcode"
                                    class="mt-1.5 text-xs"
                                    style="color: #c05c3a"
                                >
                                    {{ form.errors.postcode }}
                                </p>
                            </div>
                            <div>
                                <label
                                    class="mb-1.5 block text-sm font-medium"
                                    style="color: #1c1a17"
                                >
                                    Country
                                    <span style="color: #c05c3a">*</span>
                                </label>
                                <select
                                    v-model="form.country"
                                    class="w-full rounded-xl border px-4 py-3 text-sm transition-colors outline-none"
                                    style="
                                        border-color: rgba(28, 26, 23, 0.2);
                                        background-color: #f9f6f0;
                                        color: #1c1a17;
                                    "
                                >
                                    <option value="PH">Philippines</option>
                                    <option value="US">United States</option>
                                    <option value="SG">Singapore</option>
                                    <option value="AU">Australia</option>
                                    <option value="GB">United Kingdom</option>
                                </select>
                            </div>
                        </div>
                    </section>

                    <!-- Shipping method -->
                    <section v-if="shippingMethods.length > 0">
                        <h2
                            class="mb-5 flex items-center gap-2 text-lg font-semibold"
                            style="color: #1c1a17"
                        >
                            <Truck
                                class="size-5"
                                style="color: rgba(28, 26, 23, 0.45)"
                            />
                            Shipping Method
                        </h2>
                        <div class="space-y-3">
                            <label
                                v-for="method in shippingMethods"
                                :key="method.id"
                                class="flex cursor-pointer items-start gap-4 rounded-xl border px-4 py-4 transition-colors"
                                :style="
                                    form.shipping_method_id === method.id
                                        ? 'border-color: #1c1a17; background-color: rgba(28, 26, 23, 0.03)'
                                        : 'border-color: rgba(28, 26, 23, 0.15)'
                                "
                            >
                                <input
                                    v-model="form.shipping_method_id"
                                    type="radio"
                                    :value="method.id"
                                    class="mt-0.5 shrink-0 accent-[#1c1a17]"
                                />
                                <div class="min-w-0 flex-1">
                                    <p
                                        class="text-sm font-medium"
                                        style="color: #1c1a17"
                                    >
                                        {{ method.name }}
                                    </p>
                                    <p
                                        v-if="method.description"
                                        class="mt-0.5 text-xs"
                                        style="color: rgba(28, 26, 23, 0.55)"
                                    >
                                        {{ method.description }}
                                    </p>
                                </div>
                                <span
                                    class="shrink-0 text-sm font-semibold"
                                    style="color: #1c1a17"
                                >
                                    <template v-if="method.is_free"
                                        >Free</template
                                    >
                                    <template v-else>{{
                                        price(method.price)
                                    }}</template>
                                </span>
                            </label>
                        </div>
                        <p
                            v-if="form.errors.shipping_method_id"
                            class="mt-1.5 text-xs"
                            style="color: #c05c3a"
                        >
                            {{ form.errors.shipping_method_id }}
                        </p>
                    </section>

                    <!-- Order notes -->
                    <section>
                        <button
                            class="mb-5 flex w-full items-center justify-between"
                            @click="notesExpanded = !notesExpanded"
                        >
                            <h2
                                class="text-lg font-semibold"
                                style="color: #1c1a17"
                            >
                                Additional Notes
                            </h2>
                            <component
                                :is="notesExpanded ? ChevronUp : ChevronDown"
                                class="size-4"
                                style="color: rgba(28, 26, 23, 0.4)"
                            />
                        </button>
                        <div v-if="notesExpanded">
                            <textarea
                                v-model="form.notes"
                                rows="3"
                                class="w-full rounded-xl border px-4 py-3 text-sm transition-colors outline-none"
                                style="
                                    border-color: rgba(28, 26, 23, 0.2);
                                    background-color: #f9f6f0;
                                    color: #1c1a17;
                                    resize: vertical;
                                "
                                placeholder="Special instructions, delivery notes…"
                            />
                        </div>
                    </section>

                    <!-- Global form errors -->
                    <div
                        v-if="form.errors.items"
                        class="rounded-xl p-4 text-sm"
                        style="
                            background-color: rgba(192, 92, 58, 0.1);
                            color: #c05c3a;
                        "
                    >
                        {{ form.errors.items }}
                    </div>
                </div>

                <!-- Right: Order summary -->
                <div class="lg:sticky lg:top-24 lg:self-start">
                    <div
                        class="rounded-2xl border p-6"
                        style="
                            border-color: rgba(28, 26, 23, 0.12);
                            background-color: #f4f0e8;
                        "
                    >
                        <!-- Summary header -->
                        <button
                            class="mb-6 flex w-full items-center justify-between"
                            @click="
                                orderSummaryExpanded = !orderSummaryExpanded
                            "
                        >
                            <h2
                                class="text-lg font-semibold"
                                style="color: #1c1a17"
                            >
                                Order Summary
                                <span
                                    class="ml-2 text-sm font-normal"
                                    style="color: rgba(28, 26, 23, 0.5)"
                                >
                                    ({{ itemCount }} item{{
                                        itemCount !== 1 ? 's' : ''
                                    }})
                                </span>
                            </h2>
                            <component
                                :is="
                                    orderSummaryExpanded
                                        ? ChevronUp
                                        : ChevronDown
                                "
                                class="size-4"
                                style="color: rgba(28, 26, 23, 0.5)"
                            />
                        </button>

                        <!-- Cart items -->
                        <div v-if="orderSummaryExpanded" class="mb-6 space-y-4">
                            <div
                                v-for="item in cartItems"
                                :key="`${item.productId}-${item.variantId}`"
                                class="flex items-start gap-3"
                            >
                                <!-- Image placeholder / thumbnail -->
                                <div
                                    class="size-16 flex-shrink-0 overflow-hidden rounded-lg"
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
                                            class="text-lg"
                                            style="
                                                font-family:
                                                    'Cormorant Garamond', serif;
                                                color: rgba(28, 26, 23, 0.3);
                                            "
                                        >
                                            {{ item.name.charAt(0) }}
                                        </span>
                                    </div>
                                </div>

                                <div class="min-w-0 flex-1">
                                    <p
                                        class="text-sm leading-snug font-medium"
                                        style="color: #1c1a17"
                                    >
                                        {{ item.name }}
                                    </p>
                                    <p
                                        v-if="item.variantLabel"
                                        class="text-xs"
                                        style="color: rgba(28, 26, 23, 0.55)"
                                    >
                                        {{ item.variantLabel }}
                                    </p>

                                    <div
                                        class="mt-2 flex items-center justify-between"
                                    >
                                        <!-- Quantity controls -->
                                        <div class="flex items-center gap-2">
                                            <button
                                                class="flex size-6 items-center justify-center rounded-full border text-xs transition-opacity hover:opacity-70"
                                                style="
                                                    border-color: rgba(
                                                        28,
                                                        26,
                                                        23,
                                                        0.2
                                                    );
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
                                                class="w-6 text-center text-sm"
                                                style="color: #1c1a17"
                                            >
                                                {{ item.quantity }}
                                            </span>
                                            <button
                                                class="flex size-6 items-center justify-center rounded-full border text-xs transition-opacity hover:opacity-70"
                                                style="
                                                    border-color: rgba(
                                                        28,
                                                        26,
                                                        23,
                                                        0.2
                                                    );
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

                                        <div class="flex items-center gap-2">
                                            <span
                                                class="text-sm font-medium"
                                                style="color: #1c1a17"
                                            >
                                                {{
                                                    price(
                                                        item.price *
                                                            item.quantity,
                                                    )
                                                }}
                                            </span>
                                            <button
                                                class="transition-opacity hover:opacity-60"
                                                @click="
                                                    removeItem(
                                                        item.productId,
                                                        item.variantId,
                                                    )
                                                "
                                            >
                                                <Trash2
                                                    class="size-3.5"
                                                    style="
                                                        color: rgba(
                                                            28,
                                                            26,
                                                            23,
                                                            0.4
                                                        );
                                                    "
                                                />
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Coupon code -->
                        <div class="mb-5">
                            <button
                                class="flex w-full items-center gap-2 text-sm font-medium transition-opacity hover:opacity-70"
                                style="color: #1c1a17"
                                @click="couponExpanded = !couponExpanded"
                            >
                                <Tag class="size-4" />
                                Have a coupon code?
                                <component
                                    :is="
                                        couponExpanded ? ChevronUp : ChevronDown
                                    "
                                    class="ml-auto size-4"
                                    style="color: rgba(28, 26, 23, 0.4)"
                                />
                            </button>
                            <div v-if="couponExpanded" class="mt-3 flex gap-2">
                                <input
                                    v-model="form.coupon_code"
                                    type="text"
                                    class="flex-1 rounded-xl border px-4 py-2.5 text-sm uppercase outline-none"
                                    style="
                                        border-color: rgba(28, 26, 23, 0.2);
                                        background-color: #f9f6f0;
                                        color: #1c1a17;
                                    "
                                    placeholder="ENTER CODE"
                                />
                            </div>
                        </div>

                        <!-- Divider -->
                        <div
                            class="mb-5 h-px"
                            style="background-color: rgba(28, 26, 23, 0.1)"
                        />

                        <!-- Totals -->
                        <div class="space-y-3">
                            <div
                                class="flex justify-between text-sm"
                                style="color: rgba(28, 26, 23, 0.7)"
                            >
                                <span>Subtotal</span>
                                <span>{{ price(subtotal) }}</span>
                            </div>
                            <div
                                class="flex justify-between text-sm"
                                style="color: rgba(28, 26, 23, 0.7)"
                            >
                                <span>Shipping</span>
                                <span>
                                    <template
                                        v-if="selectedShippingMethod?.is_free"
                                        >Free</template
                                    >
                                    <template
                                        v-else-if="selectedShippingMethod"
                                        >{{ price(shippingAmount) }}</template
                                    >
                                    <template v-else>—</template>
                                </span>
                            </div>
                            <div
                                class="flex justify-between text-sm"
                                style="color: rgba(28, 26, 23, 0.7)"
                            >
                                <span
                                    >Tax ({{
                                        storeSettings?.taxRate ?? 12
                                    }}%)</span
                                >
                                <span>{{ price(taxAmount) }}</span>
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
                                <span>{{ price(total) }}</span>
                            </div>
                        </div>

                        <!-- Place order button -->
                        <button
                            class="mt-6 w-full rounded-full py-4 text-sm font-semibold tracking-widest text-white uppercase transition-opacity hover:opacity-80 disabled:cursor-not-allowed disabled:opacity-50"
                            style="background-color: #1c1a17"
                            :disabled="form.processing"
                            @click="submit"
                        >
                            {{
                                form.processing
                                    ? 'Placing Order…'
                                    : 'Place Order'
                            }}
                        </button>

                        <p
                            class="mt-3 text-center text-xs"
                            style="color: rgba(28, 26, 23, 0.4)"
                        >
                            Secure checkout · No account required
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </StorefrontLayout>
</template>
