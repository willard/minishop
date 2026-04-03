<script setup lang="ts">
import { Head, useForm } from '@inertiajs/vue3';
import { Eye, EyeOff } from 'lucide-vue-next';
import { ref } from 'vue';
import {
    edit,
    update,
} from '@/actions/App/Http/Controllers/Admin/StoreSettingsController';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem } from '@/types';

interface Settings {
    currency: string;
    currency_locale: string;
    tax_rate: number;
    active_payment_gateway: string;
    paymongo_public_key: string | null;
    paymongo_secret_key: string | null;
    paymongo_webhook_secret: string | null;
    low_stock_threshold: number;
    origin_postcode: string | null;
}

const props = defineProps<{
    settings: Settings;
    hasPaymongoSecretKey: boolean;
    hasPaymongoWebhookSecret: boolean;
}>();

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Dashboard', href: '/dashboard' },
    { title: 'Store Settings', href: edit().url },
];

const currencies = [
    { code: 'CAD', locale: 'en-CA', label: 'CAD — Canadian Dollar' },
    { code: 'USD', locale: 'en-US', label: 'USD — US Dollar' },
    { code: 'GBP', locale: 'en-GB', label: 'GBP — British Pound' },
    { code: 'EUR', locale: 'de-DE', label: 'EUR — Euro' },
    { code: 'AUD', locale: 'en-AU', label: 'AUD — Australian Dollar' },
    { code: 'SGD', locale: 'en-SG', label: 'SGD — Singapore Dollar' },
    { code: 'JPY', locale: 'ja-JP', label: 'JPY — Japanese Yen' },
    { code: 'PHP', locale: 'en-PH', label: 'PHP — Philippine Peso' },
    { code: 'MYR', locale: 'ms-MY', label: 'MYR — Malaysian Ringgit' },
    { code: 'IDR', locale: 'id-ID', label: 'IDR — Indonesian Rupiah' },
];

const form = useForm({
    currency: props.settings.currency,
    currency_locale: props.settings.currency_locale,
    tax_rate: props.settings.tax_rate,
    active_payment_gateway: props.settings.active_payment_gateway,
    paymongo_public_key: props.settings.paymongo_public_key ?? '',
    paymongo_secret_key: '',
    paymongo_webhook_secret: '',
    low_stock_threshold: props.settings.low_stock_threshold,
    origin_postcode: props.settings.origin_postcode ?? '',
});

const showFields = ref<Record<string, boolean>>({});

function toggleVisibility(field: string) {
    showFields.value[field] = !showFields.value[field];
}

function onCurrencyChange(code: string) {
    const match = currencies.find((c) => c.code === code);
    if (match) {
        form.currency_locale = match.locale;
    }
}

function submit() {
    form.put(update().url);
}
</script>

<template>
    <Head title="Store Settings" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex max-w-2xl flex-col gap-8 p-4">
            <div>
                <h1 class="text-2xl font-semibold">Store Settings</h1>
                <p class="text-sm text-muted-foreground">
                    Configure currency, tax, and payment gateways.
                </p>
            </div>

            <form class="flex flex-col gap-8" @submit.prevent="submit">
                <!-- Currency & Tax -->
                <section class="flex flex-col gap-4">
                    <h2 class="border-b pb-2 text-base font-semibold">
                        Currency &amp; Tax
                    </h2>

                    <div class="grid gap-2">
                        <Label for="currency">Currency</Label>
                        <select
                            id="currency"
                            v-model="form.currency"
                            class="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 focus-visible:outline-none"
                            @change="onCurrencyChange(form.currency)"
                        >
                            <option
                                v-for="c in currencies"
                                :key="c.code"
                                :value="c.code"
                            >
                                {{ c.label }}
                            </option>
                        </select>
                        <InputError :message="form.errors.currency" />
                    </div>

                    <div class="grid max-w-xs gap-2">
                        <Label for="tax_rate">Tax Rate (%)</Label>
                        <Input
                            id="tax_rate"
                            v-model="form.tax_rate"
                            type="number"
                            min="0"
                            max="100"
                            step="0.01"
                            placeholder="e.g. 12"
                        />
                        <InputError :message="form.errors.tax_rate" />
                    </div>
                </section>

                <!-- Payment Gateway -->
                <section class="flex flex-col gap-4">
                    <h2 class="border-b pb-2 text-base font-semibold">
                        Payment Gateway
                    </h2>

                    <div class="flex flex-col gap-3">
                        <label
                            v-for="gw in [
                                {
                                    value: 'stripe',
                                    label: 'Stripe',
                                    description:
                                        'Credit/debit cards via Stripe Elements.',
                                },
                                {
                                    value: 'paymongo',
                                    label: 'PayMongo',
                                    description:
                                        'GCash, Grab Pay, cards via PayMongo hosted checkout.',
                                },
                                {
                                    value: 'cod',
                                    label: 'Cash on Delivery',
                                    description:
                                        'Customer pays on delivery. No online processing.',
                                },
                                {
                                    value: 'bank_transfer',
                                    label: 'Bank Transfer',
                                    description:
                                        'Customer transfers payment manually. Admin confirms.',
                                },
                            ]"
                            :key="gw.value"
                            class="flex cursor-pointer items-start gap-3 rounded-lg border p-4 transition-colors"
                            :class="
                                form.active_payment_gateway === gw.value
                                    ? 'border-primary bg-primary/5'
                                    : 'border-border'
                            "
                        >
                            <input
                                v-model="form.active_payment_gateway"
                                type="radio"
                                :value="gw.value"
                                class="mt-0.5"
                            />
                            <div>
                                <p class="text-sm font-medium">
                                    {{ gw.label }}
                                </p>
                                <p class="text-xs text-muted-foreground">
                                    {{ gw.description }}
                                </p>
                            </div>
                        </label>
                        <InputError
                            :message="form.errors.active_payment_gateway"
                        />
                    </div>
                </section>

                <!-- PayMongo Keys -->
                <section
                    v-if="form.active_payment_gateway === 'paymongo'"
                    class="flex flex-col gap-4"
                >
                    <h2 class="border-b pb-2 text-base font-semibold">
                        PayMongo Keys
                    </h2>

                    <div class="grid gap-2">
                        <Label for="paymongo_public_key">Public Key</Label>
                        <Input
                            id="paymongo_public_key"
                            v-model="form.paymongo_public_key"
                            placeholder="pk_live_..."
                        />
                        <InputError
                            :message="form.errors.paymongo_public_key"
                        />
                    </div>

                    <div class="grid gap-2">
                        <Label for="paymongo_secret_key">
                            Secret Key
                            <span
                                v-if="hasPaymongoSecretKey"
                                class="ml-1 text-xs text-muted-foreground"
                                >(leave blank to keep existing)</span
                            >
                        </Label>
                        <div class="relative">
                            <Input
                                id="paymongo_secret_key"
                                v-model="form.paymongo_secret_key"
                                :type="
                                    showFields.paymongo_secret_key
                                        ? 'text'
                                        : 'password'
                                "
                                :placeholder="
                                    hasPaymongoSecretKey
                                        ? '••••••••'
                                        : 'sk_live_...'
                                "
                            />
                            <button
                                type="button"
                                class="absolute top-1/2 right-3 -translate-y-1/2 text-muted-foreground hover:text-foreground"
                                @click="toggleVisibility('paymongo_secret_key')"
                            >
                                <EyeOff
                                    v-if="showFields.paymongo_secret_key"
                                    class="size-4"
                                />
                                <Eye v-else class="size-4" />
                            </button>
                        </div>
                        <InputError
                            :message="form.errors.paymongo_secret_key"
                        />
                    </div>

                    <div class="grid gap-2">
                        <Label for="paymongo_webhook_secret">
                            Webhook Secret
                            <span
                                v-if="hasPaymongoWebhookSecret"
                                class="ml-1 text-xs text-muted-foreground"
                                >(leave blank to keep existing)</span
                            >
                        </Label>
                        <div class="relative">
                            <Input
                                id="paymongo_webhook_secret"
                                v-model="form.paymongo_webhook_secret"
                                :type="
                                    showFields.paymongo_webhook_secret
                                        ? 'text'
                                        : 'password'
                                "
                                :placeholder="
                                    hasPaymongoWebhookSecret
                                        ? '••••••••'
                                        : 'whsec_...'
                                "
                            />
                            <button
                                type="button"
                                class="absolute top-1/2 right-3 -translate-y-1/2 text-muted-foreground hover:text-foreground"
                                @click="
                                    toggleVisibility('paymongo_webhook_secret')
                                "
                            >
                                <EyeOff
                                    v-if="showFields.paymongo_webhook_secret"
                                    class="size-4"
                                />
                                <Eye v-else class="size-4" />
                            </button>
                        </div>
                        <p class="text-xs text-muted-foreground">
                            Webhook endpoint:
                            <code class="font-mono"
                                >{{
                                    $page.props.ziggy?.url ?? ''
                                }}/webhooks/paymongo</code
                            >
                        </p>
                        <InputError
                            :message="form.errors.paymongo_webhook_secret"
                        />
                    </div>
                </section>

                <!-- Inventory -->
                <section class="flex flex-col gap-4">
                    <h2 class="border-b pb-2 text-base font-semibold">
                        Inventory
                    </h2>

                    <div class="grid max-w-xs gap-2">
                        <Label for="low_stock_threshold"
                            >Low Stock Threshold</Label
                        >
                        <Input
                            id="low_stock_threshold"
                            v-model="form.low_stock_threshold"
                            type="number"
                            min="0"
                            max="10000"
                            placeholder="e.g. 10"
                        />
                        <p class="text-xs text-muted-foreground">
                            Products with stock at or below this number will
                            appear as "low stock" on the dashboard and trigger
                            email alerts.
                        </p>
                        <InputError
                            :message="form.errors.low_stock_threshold"
                        />
                    </div>
                </section>

                <!-- Submit -->
                <!-- Shipping -->
                <section class="flex flex-col gap-4">
                    <h2 class="border-b pb-2 text-base font-semibold">
                        Shipping
                    </h2>
                    <div class="grid max-w-xs gap-2">
                        <Label for="origin_postcode">Origin Postal Code</Label>
                        <Input
                            id="origin_postcode"
                            v-model="form.origin_postcode"
                            placeholder="e.g. K1A 0A6"
                            maxlength="20"
                        />
                        <p class="text-xs text-muted-foreground">
                            Used to calculate live carrier shipping rates (e.g. Canada Post).
                            Leave blank to show only flat-rate methods.
                        </p>
                        <InputError :message="form.errors.origin_postcode" />
                    </div>
                </section>

                <div>
                    <Button type="submit" :disabled="form.processing">
                        {{ form.processing ? 'Saving...' : 'Save Settings' }}
                    </Button>
                </div>
            </form>
        </div>
    </AppLayout>
</template>
