<script setup lang="ts">
import { Form, Head, Link } from '@inertiajs/vue3';
import { ArrowLeft } from 'lucide-vue-next';
import { ref } from 'vue';
import {
    index,
    update,
} from '@/actions/App/Http/Controllers/Admin/ShippingMethodController';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem } from '@/types';

interface ShippingMethod {
    id: number;
    name: string;
    description: string | null;
    price: number;
    is_free: boolean;
    is_active: boolean;
    sort_order: number;
}

const props = defineProps<{
    shippingMethod: ShippingMethod;
}>();

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Dashboard', href: '/dashboard' },
    { title: 'Shipping Methods', href: index().url },
    { title: props.shippingMethod.name, href: '#' },
    { title: 'Edit', href: '#' },
];

const isFree = ref(props.shippingMethod.is_free);
</script>

<template>
    <Head :title="`Edit ${shippingMethod.name}`" />

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
                    <h1 class="text-2xl font-semibold">Edit Shipping Method</h1>
                    <p class="text-sm text-muted-foreground">
                        {{ shippingMethod.name }}
                    </p>
                </div>
            </div>

            <!-- Form -->
            <Form
                v-bind="update.form(shippingMethod.id)"
                class="flex flex-col gap-6"
                v-slot="{ errors, processing }"
            >
                <!-- Name -->
                <div class="grid gap-2">
                    <Label for="name"
                        >Name <span class="text-destructive">*</span></Label
                    >
                    <Input
                        id="name"
                        name="name"
                        :default-value="shippingMethod.name"
                        placeholder="e.g. Standard Delivery"
                        required
                    />
                    <InputError :message="errors.name" />
                </div>

                <!-- Description -->
                <div class="grid gap-2">
                    <Label for="description">Description</Label>
                    <textarea
                        id="description"
                        name="description"
                        rows="2"
                        :value="shippingMethod.description ?? ''"
                        placeholder="e.g. Delivered in 3–5 business days."
                        class="flex w-full resize-none rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background placeholder:text-muted-foreground focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 focus-visible:outline-none disabled:cursor-not-allowed disabled:opacity-50"
                    />
                    <InputError :message="errors.description" />
                </div>

                <!-- Free shipping toggle -->
                <div class="flex items-center gap-2">
                    <Checkbox
                        id="is_free"
                        name="is_free"
                        value="1"
                        :default-value="shippingMethod.is_free"
                        @update:checked="(v) => (isFree = !!v)"
                    />
                    <Label for="is_free"
                        >Free shipping (no charge to customer)</Label
                    >
                </div>

                <!-- Price (hidden when free) -->
                <div v-if="!isFree" class="grid max-w-xs gap-2">
                    <Label for="price"
                        >Price (cents)
                        <span class="text-destructive">*</span></Label
                    >
                    <Input
                        id="price"
                        name="price"
                        type="number"
                        min="0"
                        :default-value="shippingMethod.price"
                    />
                    <p class="text-xs text-muted-foreground">
                        In cents — e.g. 20000 = ₱200.00
                    </p>
                    <InputError :message="errors.price" />
                </div>

                <!-- Sort order + Active side by side -->
                <div class="grid grid-cols-2 gap-4">
                    <div class="grid gap-2">
                        <Label for="sort_order">Sort Order</Label>
                        <Input
                            id="sort_order"
                            name="sort_order"
                            type="number"
                            min="0"
                            :default-value="shippingMethod.sort_order"
                        />
                        <p class="text-xs text-muted-foreground">
                            Lower numbers appear first
                        </p>
                        <InputError :message="errors.sort_order" />
                    </div>
                    <div class="flex flex-col gap-2 pt-1">
                        <Label>Visibility</Label>
                        <div class="mt-1 flex items-center gap-2">
                            <Checkbox
                                id="is_active"
                                name="is_active"
                                value="1"
                                :default-value="shippingMethod.is_active"
                            />
                            <Label for="is_active"
                                >Active (shown to customers)</Label
                            >
                        </div>
                    </div>
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
