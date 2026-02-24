<script setup lang="ts">
import { Form, Head, Link } from '@inertiajs/vue3';
import { ArrowLeft } from 'lucide-vue-next';
import AppLayout from '@/layouts/AppLayout.vue';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Checkbox } from '@/components/ui/checkbox';
import { type BreadcrumbItem } from '@/types';
import { index, create, store } from '@/actions/App/Http/Controllers/Admin/CouponController';

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Dashboard', href: '/dashboard' },
    { title: 'Coupons', href: index().url },
    { title: 'Add Coupon', href: create().url },
];
</script>

<template>
    <Head title="Add Coupon" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex flex-col gap-6 p-4 max-w-2xl">
            <!-- Header -->
            <div class="flex items-center gap-4">
                <Link :href="index().url">
                    <Button variant="ghost" size="sm">
                        <ArrowLeft class="size-4" />
                    </Button>
                </Link>
                <div>
                    <h1 class="text-2xl font-semibold">Add Coupon</h1>
                    <p class="text-sm text-muted-foreground">Create a new discount code</p>
                </div>
            </div>

            <!-- Form -->
            <Form
                v-bind="store.form()"
                class="flex flex-col gap-6"
                v-slot="{ errors, processing }"
            >
                <!-- Code -->
                <div class="grid gap-2">
                    <Label for="code">Code <span class="text-destructive">*</span></Label>
                    <Input
                        id="code"
                        name="code"
                        placeholder="e.g. SAVE10"
                        class="uppercase font-mono tracking-wider"
                        required
                    />
                    <p class="text-xs text-muted-foreground">Letters, numbers, dashes, and underscores only. Will be uppercased automatically.</p>
                    <InputError :message="errors.code" />
                </div>

                <!-- Description -->
                <div class="grid gap-2">
                    <Label for="description">Description</Label>
                    <textarea
                        id="description"
                        name="description"
                        rows="2"
                        placeholder="Short description of this coupon"
                        class="flex w-full rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background placeholder:text-muted-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-50 resize-none"
                    />
                    <InputError :message="errors.description" />
                </div>

                <!-- Type + Value side by side -->
                <div class="grid grid-cols-2 gap-4">
                    <div class="grid gap-2">
                        <Label for="type">Type <span class="text-destructive">*</span></Label>
                        <select
                            id="type"
                            name="type"
                            class="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2"
                            required
                        >
                            <option value="percentage">Percentage (%)</option>
                            <option value="fixed">Fixed Amount (₱)</option>
                        </select>
                        <InputError :message="errors.type" />
                    </div>
                    <div class="grid gap-2">
                        <Label for="value">Value <span class="text-destructive">*</span></Label>
                        <Input
                            id="value"
                            name="value"
                            type="number"
                            min="1"
                            placeholder="e.g. 10"
                            required
                        />
                        <p class="text-xs text-muted-foreground">% for percentage; cents (100 = ₱1) for fixed</p>
                        <InputError :message="errors.value" />
                    </div>
                </div>

                <!-- Min Order Amount + Usage Limit side by side -->
                <div class="grid grid-cols-2 gap-4">
                    <div class="grid gap-2">
                        <Label for="minimum_order_amount">Min. Order Amount (cents)</Label>
                        <Input
                            id="minimum_order_amount"
                            name="minimum_order_amount"
                            type="number"
                            min="0"
                            placeholder="Leave empty for none"
                        />
                        <p class="text-xs text-muted-foreground">e.g. 20000 = ₱200 minimum</p>
                        <InputError :message="errors.minimum_order_amount" />
                    </div>
                    <div class="grid gap-2">
                        <Label for="usage_limit">Usage Limit</Label>
                        <Input
                            id="usage_limit"
                            name="usage_limit"
                            type="number"
                            min="1"
                            placeholder="Leave empty for unlimited"
                        />
                        <p class="text-xs text-muted-foreground">Total times this code can be used</p>
                        <InputError :message="errors.usage_limit" />
                    </div>
                </div>

                <!-- Expiry Date -->
                <div class="grid gap-2">
                    <Label for="expiry_date">Expiry Date</Label>
                    <Input
                        id="expiry_date"
                        name="expiry_date"
                        type="date"
                        class="max-w-xs"
                    />
                    <p class="text-xs text-muted-foreground">Leave empty for no expiry</p>
                    <InputError :message="errors.expiry_date" />
                </div>

                <!-- Active Status -->
                <div class="flex items-center gap-2">
                    <Checkbox id="is_active" name="is_active" value="1" :default-value="true" />
                    <Label for="is_active">Active (visible and usable in store)</Label>
                </div>

                <!-- Submit -->
                <div class="flex items-center gap-3">
                    <Button type="submit" :disabled="processing">
                        {{ processing ? 'Creating...' : 'Create Coupon' }}
                    </Button>
                    <Link :href="index().url">
                        <Button variant="ghost" type="button">Cancel</Button>
                    </Link>
                </div>
            </Form>
        </div>
    </AppLayout>
</template>
