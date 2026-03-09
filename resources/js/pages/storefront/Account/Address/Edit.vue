<script setup lang="ts">
import { Form, Head } from '@inertiajs/vue3';
import AccountLayout from '@/layouts/AccountLayout.vue';
import InputError from '@/components/InputError.vue';
import { update } from '@/routes/account/address';

interface Address {
    id: number;
    name: string;
    line1: string;
    line2: string | null;
    city: string;
    state: string | null;
    postal_code: string;
    country: string;
}

defineProps<{
    address: Address | null;
}>();
</script>

<template>
    <AccountLayout title="Billing Address">
        <Head title="Billing Address" />

        <div class="max-w-lg">
            <p class="mb-6 text-sm" style="color: rgba(28, 26, 23, 0.55)">
                Your default billing address is used when placing orders.
            </p>

            <Form
                v-bind="update.form()"
                set-defaults-on-success
                v-slot="{ errors, processing, recentlySuccessful }"
                class="flex flex-col gap-5"
            >
                <!-- Hidden method override for PUT -->
                <input type="hidden" name="_method" value="PUT" />

                <div class="flex flex-col gap-1.5">
                    <label class="text-sm font-medium" style="color: #1c1a17" for="name">Full name</label>
                    <input
                        id="name"
                        type="text"
                        name="name"
                        :value="address?.name"
                        required
                        autocomplete="name"
                        placeholder="Jane Doe"
                        class="rounded-lg border px-4 py-3 text-sm outline-none focus:ring-2 focus:ring-black/20"
                        style="border-color: rgba(28, 26, 23, 0.2); background-color: #fff; color: #1c1a17"
                    />
                    <InputError :message="errors.name" />
                </div>

                <div class="flex flex-col gap-1.5">
                    <label class="text-sm font-medium" style="color: #1c1a17" for="line1">Address line 1</label>
                    <input
                        id="line1"
                        type="text"
                        name="line1"
                        :value="address?.line1"
                        required
                        autocomplete="address-line1"
                        placeholder="123 Main St"
                        class="rounded-lg border px-4 py-3 text-sm outline-none focus:ring-2 focus:ring-black/20"
                        style="border-color: rgba(28, 26, 23, 0.2); background-color: #fff; color: #1c1a17"
                    />
                    <InputError :message="errors.line1" />
                </div>

                <div class="flex flex-col gap-1.5">
                    <label class="text-sm font-medium" style="color: #1c1a17" for="line2">
                        Address line 2
                        <span class="font-normal" style="color: rgba(28, 26, 23, 0.4)">(optional)</span>
                    </label>
                    <input
                        id="line2"
                        type="text"
                        name="line2"
                        :value="address?.line2"
                        autocomplete="address-line2"
                        placeholder="Apt, suite, unit, etc."
                        class="rounded-lg border px-4 py-3 text-sm outline-none focus:ring-2 focus:ring-black/20"
                        style="border-color: rgba(28, 26, 23, 0.2); background-color: #fff; color: #1c1a17"
                    />
                    <InputError :message="errors.line2" />
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <div class="flex flex-col gap-1.5">
                        <label class="text-sm font-medium" style="color: #1c1a17" for="city">City</label>
                        <input
                            id="city"
                            type="text"
                            name="city"
                            :value="address?.city"
                            required
                            autocomplete="address-level2"
                            placeholder="Manila"
                            class="rounded-lg border px-4 py-3 text-sm outline-none focus:ring-2 focus:ring-black/20"
                            style="border-color: rgba(28, 26, 23, 0.2); background-color: #fff; color: #1c1a17"
                        />
                        <InputError :message="errors.city" />
                    </div>

                    <div class="flex flex-col gap-1.5">
                        <label class="text-sm font-medium" style="color: #1c1a17" for="state">
                            Province
                            <span class="font-normal" style="color: rgba(28, 26, 23, 0.4)">(optional)</span>
                        </label>
                        <input
                            id="state"
                            type="text"
                            name="state"
                            :value="address?.state"
                            autocomplete="address-level1"
                            placeholder="Metro Manila"
                            class="rounded-lg border px-4 py-3 text-sm outline-none focus:ring-2 focus:ring-black/20"
                            style="border-color: rgba(28, 26, 23, 0.2); background-color: #fff; color: #1c1a17"
                        />
                        <InputError :message="errors.state" />
                    </div>
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <div class="flex flex-col gap-1.5">
                        <label class="text-sm font-medium" style="color: #1c1a17" for="postal_code">Postal code</label>
                        <input
                            id="postal_code"
                            type="text"
                            name="postal_code"
                            :value="address?.postal_code"
                            required
                            autocomplete="postal-code"
                            placeholder="1000"
                            class="rounded-lg border px-4 py-3 text-sm outline-none focus:ring-2 focus:ring-black/20"
                            style="border-color: rgba(28, 26, 23, 0.2); background-color: #fff; color: #1c1a17"
                        />
                        <InputError :message="errors.postal_code" />
                    </div>

                    <div class="flex flex-col gap-1.5">
                        <label class="text-sm font-medium" style="color: #1c1a17" for="country">Country</label>
                        <select
                            id="country"
                            name="country"
                            class="rounded-lg border px-4 py-3 text-sm outline-none focus:ring-2 focus:ring-black/20"
                            style="border-color: rgba(28, 26, 23, 0.2); background-color: #fff; color: #1c1a17"
                        >
                            <option value="PH" :selected="(address?.country ?? 'PH') === 'PH'">Philippines</option>
                            <option value="US" :selected="address?.country === 'US'">United States</option>
                            <option value="SG" :selected="address?.country === 'SG'">Singapore</option>
                            <option value="AU" :selected="address?.country === 'AU'">Australia</option>
                            <option value="GB" :selected="address?.country === 'GB'">United Kingdom</option>
                        </select>
                        <InputError :message="errors.country" />
                    </div>
                </div>

                <div class="flex items-center gap-4">
                    <button
                        type="submit"
                        :disabled="processing"
                        class="rounded-lg px-6 py-3 text-sm font-semibold uppercase tracking-widest text-white transition-opacity hover:opacity-80 disabled:opacity-50"
                        style="background-color: #1c1a17"
                    >
                        {{ processing ? 'Saving…' : 'Save address' }}
                    </button>

                    <span
                        v-if="recentlySuccessful"
                        class="text-sm"
                        style="color: #15803d"
                    >
                        Saved!
                    </span>
                </div>
            </Form>
        </div>
    </AccountLayout>
</template>
