<script setup lang="ts">
import { Head, router, usePage } from '@inertiajs/vue3';
import { loadStripe } from '@stripe/stripe-js';
import type { Stripe, StripeCardElement } from '@stripe/stripe-js';
import { AlertCircle, Loader2, Lock } from 'lucide-vue-next';
import { onMounted, ref } from 'vue';
import { confirmation } from '@/actions/Minishop/Http/Controllers/Storefront/CheckoutController';
import { stripeIntent } from '@/actions/Minishop/Http/Controllers/Storefront/PaymentController';
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
    payment_gateway: string;
    subtotal: number;
    discount_amount: number;
    shipping_amount: number;
    tax_amount: number;
    total_amount: number;
    items: OrderItem[];
    customer: {
        user: {
            name: string;
            email: string;
        };
    };
    shippingMethod: {
        name: string;
    } | null;
}

const props = defineProps<{
    order: Order;
}>();

const { formatPrice } = usePrice();

const page = usePage<{
    storeSettings: {
        stripePublicKey: string | null;
        activeGateway: string;
        currency: string;
        currencyLocale: string;
    };
}>();

const stripe = ref<Stripe | null>(null);
const cardElement = ref<StripeCardElement | null>(null);
const clientSecret = ref<string>('');
const paymentError = ref<string>('');
const isLoading = ref(true);
const isProcessing = ref(false);

const gateway = props.order.payment_gateway;

async function initStripe(): Promise<void> {
    const publicKey = page.props.storeSettings?.stripePublicKey;
    if (!publicKey) {
        paymentError.value =
            'Stripe is not configured. Please contact the store.';
        isLoading.value = false;
        return;
    }

    try {
        // Fetch client secret
        const response = await fetch(stripeIntent(props.order).url, {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN':
                    (
                        document.querySelector(
                            'meta[name="csrf-token"]',
                        ) as HTMLMetaElement
                    )?.content ?? '',
                Accept: 'application/json',
                'Content-Type': 'application/json',
            },
        });

        if (!response.ok) {
            throw new Error('Could not initialise payment.');
        }

        const data = await response.json();
        clientSecret.value = data.clientSecret;

        // Load Stripe.js and mount card element
        const stripeInstance = await loadStripe(publicKey);
        if (!stripeInstance) {
            throw new Error('Failed to load Stripe.');
        }
        stripe.value = stripeInstance;

        const elements = stripeInstance.elements();
        const card = elements.create('card', {
            style: {
                base: {
                    fontSize: '15px',
                    color: '#1c1a17',
                    fontFamily: '"Inter", system-ui, sans-serif',
                    '::placeholder': { color: 'rgba(28, 26, 23, 0.35)' },
                },
                invalid: { color: '#c05c3a' },
            },
        });
        card.mount('#card-element');
        cardElement.value = card;
    } catch (e: unknown) {
        paymentError.value =
            e instanceof Error ? e.message : 'Failed to initialise payment.';
    } finally {
        isLoading.value = false;
    }
}

onMounted(() => {
    if (gateway === 'stripe') {
        initStripe();
    } else {
        isLoading.value = false;
    }
});

async function submitStripePayment(): Promise<void> {
    if (!stripe.value || !cardElement.value || !clientSecret.value) {
        return;
    }
    isProcessing.value = true;
    paymentError.value = '';

    const { paymentIntent, error } = await stripe.value.confirmCardPayment(
        clientSecret.value,
        {
            payment_method: { card: cardElement.value },
        },
    );

    if (error) {
        paymentError.value =
            error.message ?? 'Payment failed. Please try again.';
        isProcessing.value = false;
    } else if (paymentIntent?.status === 'succeeded') {
        router.get(confirmation(props.order).url);
    } else {
        paymentError.value =
            'Unexpected payment status. Please contact support.';
        isProcessing.value = false;
    }
}
</script>

<template>
    <Head :title="`Pay for Order ${order.order_number}`" />

    <StorefrontLayout>
        <div class="mx-auto max-w-5xl px-6 py-12">
            <h1
                class="mb-8 text-3xl font-semibold"
                style="font-family: 'Cormorant Garamond', serif; color: #1c1a17"
            >
                Complete Payment
            </h1>

            <div class="grid grid-cols-1 gap-10 lg:grid-cols-[1fr_360px]">
                <!-- Left: Payment form -->
                <div>
                    <!-- Order ref -->
                    <p
                        class="mb-6 text-sm"
                        style="color: rgba(28, 26, 23, 0.55)"
                    >
                        Order
                        <strong style="color: #1c1a17">{{
                            order.order_number
                        }}</strong>
                        &nbsp;·&nbsp; {{ order.customer.user.email }}
                    </p>

                    <!-- Error -->
                    <div
                        v-if="paymentError"
                        class="mb-5 flex items-start gap-3 rounded-xl p-4 text-sm"
                        style="
                            background-color: rgba(192, 92, 58, 0.1);
                            color: #c05c3a;
                        "
                    >
                        <AlertCircle class="mt-0.5 size-4 shrink-0" />
                        {{ paymentError }}
                    </div>

                    <!-- Stripe form -->
                    <template v-if="gateway === 'stripe'">
                        <div
                            class="rounded-2xl border p-6"
                            style="border-color: rgba(28, 26, 23, 0.12)"
                        >
                            <h2
                                class="mb-5 text-sm font-semibold tracking-wider uppercase"
                                style="color: #1c1a17"
                            >
                                Card Details
                            </h2>

                            <!-- Loading skeleton -->
                            <div v-if="isLoading" class="space-y-3">
                                <div
                                    class="h-10 animate-pulse rounded-lg"
                                    style="
                                        background-color: rgba(
                                            28,
                                            26,
                                            23,
                                            0.08
                                        );
                                    "
                                />
                            </div>

                            <!-- Stripe card element mount point -->
                            <div
                                v-show="!isLoading"
                                id="card-element"
                                class="rounded-xl border px-4 py-3"
                                style="
                                    border-color: rgba(28, 26, 23, 0.2);
                                    background-color: #f9f6f0;
                                    min-height: 44px;
                                "
                            />

                            <button
                                v-if="!isLoading"
                                class="mt-6 flex w-full items-center justify-center gap-2 rounded-full py-4 text-sm font-semibold tracking-widest text-white uppercase transition-opacity hover:opacity-80 disabled:cursor-not-allowed disabled:opacity-50"
                                style="background-color: #1c1a17"
                                :disabled="isProcessing"
                                @click="submitStripePayment"
                            >
                                <Loader2
                                    v-if="isProcessing"
                                    class="size-4 animate-spin"
                                />
                                <Lock v-else class="size-4" />
                                {{
                                    isProcessing
                                        ? 'Processing…'
                                        : `Pay ${formatPrice(order.total_amount)}`
                                }}
                            </button>
                        </div>

                        <p
                            class="mt-3 flex items-center justify-center gap-1.5 text-xs"
                            style="color: rgba(28, 26, 23, 0.4)"
                        >
                            <Lock class="size-3" />
                            Secured by Stripe · Your card details are never
                            stored
                        </p>
                    </template>
                </div>

                <!-- Right: Order summary -->
                <div>
                    <div
                        class="rounded-2xl border p-6"
                        style="
                            border-color: rgba(28, 26, 23, 0.12);
                            background-color: #f4f0e8;
                        "
                    >
                        <h2
                            class="mb-5 text-sm font-semibold tracking-wider uppercase"
                            style="color: #1c1a17"
                        >
                            Order Summary
                        </h2>

                        <!-- Items -->
                        <div class="mb-5 space-y-3">
                            <div
                                v-for="item in order.items"
                                :key="item.id"
                                class="flex items-start justify-between gap-3 text-sm"
                            >
                                <div class="min-w-0">
                                    <p
                                        class="leading-snug font-medium"
                                        style="color: #1c1a17"
                                    >
                                        {{ item.product_name }}
                                    </p>
                                    <p
                                        class="mt-0.5 text-xs"
                                        style="color: rgba(28, 26, 23, 0.5)"
                                    >
                                        × {{ item.quantity }}
                                    </p>
                                </div>
                                <span
                                    class="shrink-0 font-medium"
                                    style="color: #1c1a17"
                                >
                                    {{ formatPrice(item.subtotal) }}
                                </span>
                            </div>
                        </div>

                        <!-- Divider -->
                        <div
                            class="mb-4 h-px"
                            style="background-color: rgba(28, 26, 23, 0.1)"
                        />

                        <!-- Totals -->
                        <div class="space-y-2.5 text-sm">
                            <div
                                class="flex justify-between"
                                style="color: rgba(28, 26, 23, 0.65)"
                            >
                                <span>Subtotal</span>
                                <span>{{ formatPrice(order.subtotal) }}</span>
                            </div>
                            <div
                                v-if="order.discount_amount > 0"
                                class="flex justify-between"
                                style="color: #4a7c59"
                            >
                                <span>Discount</span>
                                <span
                                    >−{{
                                        formatPrice(order.discount_amount)
                                    }}</span
                                >
                            </div>
                            <div
                                class="flex justify-between"
                                style="color: rgba(28, 26, 23, 0.65)"
                            >
                                <span>Shipping</span>
                                <span>
                                    <template v-if="order.shipping_amount === 0"
                                        >Free</template
                                    >
                                    <template v-else>{{
                                        formatPrice(order.shipping_amount)
                                    }}</template>
                                </span>
                            </div>
                            <div
                                class="flex justify-between"
                                style="color: rgba(28, 26, 23, 0.65)"
                            >
                                <span>Tax</span>
                                <span>{{ formatPrice(order.tax_amount) }}</span>
                            </div>
                            <div
                                class="h-px"
                                style="background-color: rgba(28, 26, 23, 0.1)"
                            />
                            <div
                                class="flex justify-between text-base font-semibold"
                                style="color: #1c1a17"
                            >
                                <span>Total</span>
                                <span>{{
                                    formatPrice(order.total_amount)
                                }}</span>
                            </div>
                        </div>

                        <!-- Shipping method -->
                        <div
                            v-if="order.shippingMethod"
                            class="mt-5 rounded-xl px-4 py-3 text-xs"
                            style="
                                background-color: rgba(28, 26, 23, 0.05);
                                color: rgba(28, 26, 23, 0.6);
                            "
                        >
                            <span class="font-medium" style="color: #1c1a17"
                                >Shipping:</span
                            >
                            {{ order.shippingMethod.name }}
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </StorefrontLayout>
</template>
