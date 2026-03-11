<script setup lang="ts">
import { Form, Head, Link, useForm, usePage } from '@inertiajs/vue3';
import {
    ShoppingBag,
    Trash2,
    Tag,
    ChevronDown,
    ChevronUp,
    Truck,
    User,
    LogIn,
    UserPlus,
    CheckCircle2,
    X,
} from 'lucide-vue-next';
import { computed, ref } from 'vue';
import { store } from '@/actions/App/Http/Controllers/Storefront/CheckoutController';
import { index as productsIndex } from '@/actions/App/Http/Controllers/Storefront/ProductController';
import { store as loginStore } from '@/routes/login';
import { store as registerStore } from '@/routes/register';
import InputError from '@/components/InputError.vue';
import { useCart } from '@/composables/useCart';
import { usePrice } from '@/composables/usePrice';
import StorefrontLayout from '@/layouts/StorefrontLayout.vue';

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

const authMode = ref<'collapsed' | 'login' | 'register'>('collapsed');

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
    country: 'CA',
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
                    <!-- Auth section -->
                    <section>
                        <!-- Signed-in indicator -->
                        <div
                            v-if="page.props.auth?.user"
                            class="flex items-center gap-3 rounded-xl px-4 py-3"
                            style="
                                background-color: rgba(28, 26, 23, 0.04);
                                border: 1px solid rgba(28, 26, 23, 0.1);
                            "
                        >
                            <div
                                class="flex size-8 shrink-0 items-center justify-center rounded-full"
                                style="background-color: #1c1a17"
                            >
                                <User
                                    class="size-3.5"
                                    style="color: #f9f6f0"
                                />
                            </div>
                            <div class="min-w-0 flex-1">
                                <p
                                    class="text-sm font-medium leading-tight"
                                    style="color: #1c1a17"
                                >
                                    {{ page.props.auth.user.name }}
                                </p>
                                <p
                                    class="text-xs leading-tight"
                                    style="color: rgba(28, 26, 23, 0.5)"
                                >
                                    {{ page.props.auth.user.email }}
                                </p>
                            </div>
                            <CheckCircle2
                                class="size-4 shrink-0"
                                style="color: #5a8a6a"
                            />
                        </div>

                        <!-- Guest: collapsed prompt -->
                        <div
                            v-else-if="authMode === 'collapsed'"
                            class="flex flex-col items-start justify-between gap-3 rounded-xl px-5 py-4 sm:flex-row sm:items-center"
                            style="
                                background-color: #f4f0e8;
                                border: 1px solid rgba(28, 26, 23, 0.1);
                            "
                        >
                            <div class="flex items-start gap-3">
                                <div
                                    class="flex size-8 shrink-0 items-center justify-center rounded-full"
                                    style="background-color: rgba(28, 26, 23, 0.08)"
                                >
                                    <User
                                        class="size-3.5"
                                        style="color: rgba(28, 26, 23, 0.5)"
                                    />
                                </div>
                                <div>
                                    <p
                                        class="text-sm font-medium leading-snug"
                                        style="color: #1c1a17"
                                    >
                                        Have an account?
                                    </p>
                                    <p
                                        class="mt-0.5 text-xs leading-snug"
                                        style="color: rgba(28, 26, 23, 0.5)"
                                    >
                                        Sign in to track orders &amp; checkout
                                        faster
                                    </p>
                                </div>
                            </div>
                            <div class="flex shrink-0 gap-2">
                                <button
                                    class="flex items-center gap-1.5 rounded-full border px-4 py-2 text-xs font-semibold tracking-widest uppercase transition-colors hover:bg-black/5"
                                    style="
                                        border-color: rgba(28, 26, 23, 0.2);
                                        color: #1c1a17;
                                    "
                                    @click="authMode = 'login'"
                                >
                                    <LogIn class="size-3" />
                                    Sign in
                                </button>
                                <button
                                    class="flex items-center gap-1.5 rounded-full px-4 py-2 text-xs font-semibold tracking-widest text-white uppercase transition-opacity hover:opacity-80"
                                    style="background-color: #1c1a17"
                                    @click="authMode = 'register'"
                                >
                                    <UserPlus class="size-3" />
                                    Register
                                </button>
                            </div>
                        </div>

                        <!-- Guest: auth form panel -->
                        <Transition
                            enter-active-class="transition-all duration-300 ease-out"
                            enter-from-class="opacity-0 -translate-y-2"
                            enter-to-class="opacity-100 translate-y-0"
                            leave-active-class="transition-all duration-200 ease-in"
                            leave-from-class="opacity-100 translate-y-0"
                            leave-to-class="opacity-0 -translate-y-2"
                        >
                            <div
                                v-if="
                                    authMode === 'login' ||
                                    authMode === 'register'
                                "
                                class="overflow-hidden rounded-2xl"
                                style="
                                    border: 1px solid rgba(28, 26, 23, 0.12);
                                    background-color: #f4f0e8;
                                "
                            >
                                <!-- Panel header -->
                                <div
                                    class="flex items-center justify-between border-b px-5 pt-5 pb-4"
                                    style="
                                        border-color: rgba(28, 26, 23, 0.08);
                                    "
                                >
                                    <!-- Tab switcher -->
                                    <div
                                        class="flex gap-0.5 rounded-full p-1"
                                        style="
                                            background-color: rgba(
                                                28,
                                                26,
                                                23,
                                                0.08
                                            );
                                        "
                                    >
                                        <button
                                            class="flex items-center gap-1.5 rounded-full px-4 py-1.5 text-xs font-semibold tracking-widest uppercase transition-all"
                                            :style="
                                                authMode === 'login'
                                                    ? 'background-color: #1c1a17; color: #f9f6f0;'
                                                    : 'color: rgba(28, 26, 23, 0.45);'
                                            "
                                            @click="authMode = 'login'"
                                        >
                                            <LogIn class="size-3" />
                                            Sign In
                                        </button>
                                        <button
                                            class="flex items-center gap-1.5 rounded-full px-4 py-1.5 text-xs font-semibold tracking-widest uppercase transition-all"
                                            :style="
                                                authMode === 'register'
                                                    ? 'background-color: #1c1a17; color: #f9f6f0;'
                                                    : 'color: rgba(28, 26, 23, 0.45);'
                                            "
                                            @click="authMode = 'register'"
                                        >
                                            <UserPlus class="size-3" />
                                            Create Account
                                        </button>
                                    </div>

                                    <button
                                        class="transition-opacity hover:opacity-60"
                                        style="color: rgba(28, 26, 23, 0.4)"
                                        @click="authMode = 'collapsed'"
                                    >
                                        <X class="size-4" />
                                    </button>
                                </div>

                                <!-- Form body -->
                                <div class="px-5 pt-4 pb-5">
                                    <!-- Sign In form -->
                                    <Form
                                        v-if="authMode === 'login'"
                                        v-bind="loginStore.form()"
                                        :reset-on-success="['password']"
                                        class="flex flex-col gap-4"
                                        v-slot="{ errors, processing }"
                                    >
                                        <input
                                            type="hidden"
                                            name="redirect"
                                            value="/checkout"
                                        />
                                        <div class="flex flex-col gap-1.5">
                                            <label
                                                class="text-xs font-medium"
                                                style="color: #1c1a17"
                                                for="auth-email"
                                            >
                                                Email address
                                            </label>
                                            <input
                                                id="auth-email"
                                                type="email"
                                                name="email"
                                                required
                                                autocomplete="email"
                                                placeholder="you@example.com"
                                                class="w-full rounded-xl border px-4 py-3 text-sm transition-colors outline-none"
                                                :class="{
                                                    'border-[#c05c3a] ring-1 ring-[#c05c3a]':
                                                        errors.email,
                                                }"
                                                style="
                                                    border-color: rgba(
                                                        28,
                                                        26,
                                                        23,
                                                        0.2
                                                    );
                                                    background-color: #f9f6f0;
                                                    color: #1c1a17;
                                                "
                                            />
                                            <InputError
                                                :message="errors.email"
                                            />
                                        </div>

                                        <div class="flex flex-col gap-1.5">
                                            <div
                                                class="flex items-center justify-between"
                                            >
                                                <label
                                                    class="text-xs font-medium"
                                                    style="color: #1c1a17"
                                                    for="auth-password"
                                                >
                                                    Password
                                                </label>
                                                <Link
                                                    href="/forgot-password"
                                                    class="text-xs underline underline-offset-4 transition-opacity hover:opacity-60"
                                                    style="
                                                        color: rgba(
                                                            28,
                                                            26,
                                                            23,
                                                            0.5
                                                        );
                                                    "
                                                >
                                                    Forgot password?
                                                </Link>
                                            </div>
                                            <input
                                                id="auth-password"
                                                type="password"
                                                name="password"
                                                required
                                                autocomplete="current-password"
                                                placeholder="Password"
                                                class="w-full rounded-xl border px-4 py-3 text-sm transition-colors outline-none"
                                                :class="{
                                                    'border-[#c05c3a] ring-1 ring-[#c05c3a]':
                                                        errors.password,
                                                }"
                                                style="
                                                    border-color: rgba(
                                                        28,
                                                        26,
                                                        23,
                                                        0.2
                                                    );
                                                    background-color: #f9f6f0;
                                                    color: #1c1a17;
                                                "
                                            />
                                            <InputError
                                                :message="errors.password"
                                            />
                                        </div>

                                        <div
                                            class="flex items-center justify-between gap-4"
                                        >
                                            <button
                                                type="submit"
                                                :disabled="processing"
                                                class="flex items-center gap-2 rounded-full px-6 py-2.5 text-xs font-semibold tracking-widest text-white uppercase transition-opacity hover:opacity-80 disabled:opacity-50"
                                                style="
                                                    background-color: #1c1a17;
                                                "
                                            >
                                                <LogIn class="size-3" />
                                                {{
                                                    processing
                                                        ? 'Signing in…'
                                                        : 'Sign in'
                                                }}
                                            </button>
                                            <button
                                                type="button"
                                                class="text-xs underline underline-offset-4 transition-opacity hover:opacity-70"
                                                style="
                                                    color: rgba(28, 26, 23, 0.45);
                                                "
                                                @click="authMode = 'collapsed'"
                                            >
                                                Continue as guest
                                            </button>
                                        </div>
                                    </Form>

                                    <!-- Create Account form -->
                                    <Form
                                        v-else-if="authMode === 'register'"
                                        v-bind="registerStore.form()"
                                        :reset-on-success="[
                                            'password',
                                            'password_confirmation',
                                        ]"
                                        class="flex flex-col gap-4"
                                        v-slot="{ errors, processing }"
                                    >
                                        <input
                                            type="hidden"
                                            name="redirect"
                                            value="/checkout"
                                        />
                                        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                                            <div class="flex flex-col gap-1.5 sm:col-span-2">
                                                <label
                                                    class="text-xs font-medium"
                                                    style="color: #1c1a17"
                                                    for="reg-name"
                                                >
                                                    Full name
                                                </label>
                                                <input
                                                    id="reg-name"
                                                    type="text"
                                                    name="name"
                                                    required
                                                    autocomplete="name"
                                                    placeholder="Jane Smith"
                                                    class="w-full rounded-xl border px-4 py-3 text-sm transition-colors outline-none"
                                                    :class="{
                                                        'border-[#c05c3a] ring-1 ring-[#c05c3a]':
                                                            errors.name,
                                                    }"
                                                    style="
                                                        border-color: rgba(
                                                            28,
                                                            26,
                                                            23,
                                                            0.2
                                                        );
                                                        background-color: #f9f6f0;
                                                        color: #1c1a17;
                                                    "
                                                />
                                                <InputError
                                                    :message="errors.name"
                                                />
                                            </div>

                                            <div class="flex flex-col gap-1.5 sm:col-span-2">
                                                <label
                                                    class="text-xs font-medium"
                                                    style="color: #1c1a17"
                                                    for="reg-email"
                                                >
                                                    Email address
                                                </label>
                                                <input
                                                    id="reg-email"
                                                    type="email"
                                                    name="email"
                                                    required
                                                    autocomplete="email"
                                                    placeholder="you@example.com"
                                                    class="w-full rounded-xl border px-4 py-3 text-sm transition-colors outline-none"
                                                    :class="{
                                                        'border-[#c05c3a] ring-1 ring-[#c05c3a]':
                                                            errors.email,
                                                    }"
                                                    style="
                                                        border-color: rgba(
                                                            28,
                                                            26,
                                                            23,
                                                            0.2
                                                        );
                                                        background-color: #f9f6f0;
                                                        color: #1c1a17;
                                                    "
                                                />
                                                <InputError
                                                    :message="errors.email"
                                                />
                                            </div>

                                            <div class="flex flex-col gap-1.5">
                                                <label
                                                    class="text-xs font-medium"
                                                    style="color: #1c1a17"
                                                    for="reg-password"
                                                >
                                                    Password
                                                </label>
                                                <input
                                                    id="reg-password"
                                                    type="password"
                                                    name="password"
                                                    required
                                                    autocomplete="new-password"
                                                    placeholder="Min. 8 characters"
                                                    class="w-full rounded-xl border px-4 py-3 text-sm transition-colors outline-none"
                                                    :class="{
                                                        'border-[#c05c3a] ring-1 ring-[#c05c3a]':
                                                            errors.password,
                                                    }"
                                                    style="
                                                        border-color: rgba(
                                                            28,
                                                            26,
                                                            23,
                                                            0.2
                                                        );
                                                        background-color: #f9f6f0;
                                                        color: #1c1a17;
                                                    "
                                                />
                                                <InputError
                                                    :message="errors.password"
                                                />
                                            </div>

                                            <div class="flex flex-col gap-1.5">
                                                <label
                                                    class="text-xs font-medium"
                                                    style="color: #1c1a17"
                                                    for="reg-password-confirm"
                                                >
                                                    Confirm password
                                                </label>
                                                <input
                                                    id="reg-password-confirm"
                                                    type="password"
                                                    name="password_confirmation"
                                                    required
                                                    autocomplete="new-password"
                                                    placeholder="Confirm password"
                                                    class="w-full rounded-xl border px-4 py-3 text-sm transition-colors outline-none"
                                                    :class="{
                                                        'border-[#c05c3a] ring-1 ring-[#c05c3a]':
                                                            errors.password_confirmation,
                                                    }"
                                                    style="
                                                        border-color: rgba(
                                                            28,
                                                            26,
                                                            23,
                                                            0.2
                                                        );
                                                        background-color: #f9f6f0;
                                                        color: #1c1a17;
                                                    "
                                                />
                                                <InputError
                                                    :message="
                                                        errors.password_confirmation
                                                    "
                                                />
                                            </div>
                                        </div>

                                        <div
                                            class="flex items-center justify-between gap-4"
                                        >
                                            <button
                                                type="submit"
                                                :disabled="processing"
                                                class="flex items-center gap-2 rounded-full px-6 py-2.5 text-xs font-semibold tracking-widest text-white uppercase transition-opacity hover:opacity-80 disabled:opacity-50"
                                                style="
                                                    background-color: #1c1a17;
                                                "
                                            >
                                                <UserPlus class="size-3" />
                                                {{
                                                    processing
                                                        ? 'Creating…'
                                                        : 'Create account'
                                                }}
                                            </button>
                                            <button
                                                type="button"
                                                class="text-xs underline underline-offset-4 transition-opacity hover:opacity-70"
                                                style="
                                                    color: rgba(28, 26, 23, 0.45);
                                                "
                                                @click="authMode = 'collapsed'"
                                            >
                                                Continue as guest
                                            </button>
                                        </div>
                                    </Form>
                                </div>
                            </div>
                        </Transition>
                    </section>

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
                                    placeholder="Jane Smith"
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
                                    placeholder="jane@example.com"
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
                                    placeholder="+1 416 555 0123"
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
                                    placeholder="123 Maple Avenue"
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
                                    placeholder="Toronto"
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
                                    placeholder="Ontario"
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
                                    placeholder="M5V 3A8"
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
                                    <option value="CA">Canada</option>
                                    <option value="US">United States</option>
                                    <option value="GB">United Kingdom</option>
                                    <option value="AU">Australia</option>
                                    <option value="SG">Singapore</option>
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
