<script setup lang="ts">
import { Form, Head, Link } from '@inertiajs/vue3';
import { ArrowLeft } from 'lucide-vue-next';
import {
    index,
    update,
} from '@/actions/App/Http/Controllers/Admin/CouponController';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem } from '@/types';

interface Coupon {
    id: number;
    code: string;
    description: string | null;
    type: 'fixed' | 'percentage';
    value: number;
    minimum_order_amount: number | null;
    expiry_date: string | null;
    usage_limit: number | null;
    used_count: number;
    is_active: boolean;
}

const props = defineProps<{
    coupon: Coupon;
}>();

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Dashboard', href: '/dashboard' },
    { title: 'Coupons', href: index().url },
    { title: props.coupon.code, href: '#' },
    { title: 'Edit', href: '#' },
];
</script>

<template>
    <Head :title="`Edit ${coupon.code}`" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex max-w-2xl flex-col gap-6 p-4">
            <!-- Header -->
            <div class="flex items-center gap-4">
                <Link :href="index().url">
                    <Button variant="ghost" size="sm">
                        <ArrowLeft class="size-4" />
                    </Button>
                </Link>
                <div>
                    <h1 class="text-2xl font-semibold">Edit Coupon</h1>
                    <p
                        class="font-mono text-sm tracking-wide text-muted-foreground"
                    >
                        {{ coupon.code }}
                    </p>
                </div>
            </div>

            <!-- Usage stat -->
            <div
                class="rounded-lg border border-sidebar-border px-4 py-3 text-sm text-muted-foreground"
            >
                Used
                <strong class="text-foreground">{{ coupon.used_count }}</strong>
                time{{ coupon.used_count === 1 ? '' : 's' }}
                <template v-if="coupon.usage_limit !== null">
                    out of
                    <strong class="text-foreground">{{
                        coupon.usage_limit
                    }}</strong>
                </template>
            </div>

            <!-- Form -->
            <Form
                v-bind="update.form(coupon)"
                class="flex flex-col gap-6"
                v-slot="{ errors, processing }"
            >
                <!-- Code -->
                <div class="grid gap-2">
                    <Label for="code"
                        >Code <span class="text-destructive">*</span></Label
                    >
                    <Input
                        id="code"
                        name="code"
                        :default-value="coupon.code"
                        placeholder="e.g. SAVE10"
                        class="max-w-xs font-mono tracking-wider uppercase"
                        required
                    />
                    <InputError :message="errors.code" />
                </div>

                <!-- Description -->
                <div class="grid gap-2">
                    <Label for="description">Description</Label>
                    <textarea
                        id="description"
                        name="description"
                        rows="2"
                        :value="coupon.description ?? ''"
                        placeholder="Short description of this coupon"
                        class="flex w-full resize-none rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background placeholder:text-muted-foreground focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 focus-visible:outline-none disabled:cursor-not-allowed disabled:opacity-50"
                    />
                    <InputError :message="errors.description" />
                </div>

                <!-- Type + Value side by side -->
                <div class="grid grid-cols-2 gap-4">
                    <div class="grid gap-2">
                        <Label for="type"
                            >Type <span class="text-destructive">*</span></Label
                        >
                        <select
                            id="type"
                            name="type"
                            :value="coupon.type"
                            class="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 focus-visible:outline-none"
                            required
                        >
                            <option value="percentage">Percentage (%)</option>
                            <option value="fixed">Fixed Amount ($)</option>
                        </select>
                        <InputError :message="errors.type" />
                    </div>
                    <div class="grid gap-2">
                        <Label for="value"
                            >Value
                            <span class="text-destructive">*</span></Label
                        >
                        <Input
                            id="value"
                            name="value"
                            type="number"
                            min="1"
                            :default-value="coupon.value"
                            required
                        />
                        <p class="text-xs text-muted-foreground">
                            % for percentage; cents (100 = $1) for fixed
                        </p>
                        <InputError :message="errors.value" />
                    </div>
                </div>

                <!-- Min Order + Usage Limit side by side -->
                <div class="grid grid-cols-2 gap-4">
                    <div class="grid gap-2">
                        <Label for="minimum_order_amount"
                            >Min. Order Amount (cents)</Label
                        >
                        <Input
                            id="minimum_order_amount"
                            name="minimum_order_amount"
                            type="number"
                            min="0"
                            :default-value="
                                coupon.minimum_order_amount ?? undefined
                            "
                            placeholder="Leave empty for none"
                        />
                        <InputError :message="errors.minimum_order_amount" />
                    </div>
                    <div class="grid gap-2">
                        <Label for="usage_limit">Usage Limit</Label>
                        <Input
                            id="usage_limit"
                            name="usage_limit"
                            type="number"
                            min="1"
                            :default-value="coupon.usage_limit ?? undefined"
                            placeholder="Leave empty for unlimited"
                        />
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
                        :default-value="coupon.expiry_date ?? undefined"
                        class="max-w-xs"
                    />
                    <p class="text-xs text-muted-foreground">
                        Leave empty for no expiry
                    </p>
                    <InputError :message="errors.expiry_date" />
                </div>

                <!-- Active Status -->
                <div class="flex items-center gap-2">
                    <Checkbox
                        id="is_active"
                        name="is_active"
                        value="1"
                        :default-value="coupon.is_active"
                    />
                    <Label for="is_active"
                        >Active (visible and usable in store)</Label
                    >
                </div>

                <!-- Submit -->
                <div class="flex items-center gap-3">
                    <Button type="submit" :disabled="processing">
                        {{ processing ? 'Saving...' : 'Save Changes' }}
                    </Button>
                    <Link :href="index().url">
                        <Button variant="ghost" type="button">Cancel</Button>
                    </Link>
                </div>
            </Form>
        </div>
    </AppLayout>
</template>
