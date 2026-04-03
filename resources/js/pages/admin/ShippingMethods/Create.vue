<script setup lang="ts">
import { Form, Head, Link } from '@inertiajs/vue3';
import { ArrowLeft } from 'lucide-vue-next';
import { ref } from 'vue';
import {
    index,
    create,
    store,
} from '@/actions/App/Http/Controllers/Admin/ShippingMethodController';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem } from '@/types';

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Dashboard', href: '/dashboard' },
    { title: 'Shipping Methods', href: index().url },
    { title: 'Add Method', href: create().url },
];

const isFree = ref(false);
const methodType = ref<'flat_rate' | 'calculated'>('flat_rate');
</script>

<template>
    <Head title="Add Shipping Method" />

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
                    <h1 class="text-2xl font-semibold">Add Shipping Method</h1>
                    <p class="text-sm text-muted-foreground">
                        Create a new shipping option for customers
                    </p>
                </div>
            </div>

            <!-- Form -->
            <Form
                v-bind="store.form()"
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
                        placeholder="e.g. Delivered in 3–5 business days."
                        class="flex w-full resize-none rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background placeholder:text-muted-foreground focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 focus-visible:outline-none disabled:cursor-not-allowed disabled:opacity-50"
                    />
                    <InputError :message="errors.description" />
                </div>

                <!-- Type -->
                <div class="grid gap-2">
                    <Label for="type">Type</Label>
                    <select
                        id="type"
                        name="type"
                        class="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 focus-visible:outline-none"
                        @change="(e) => (methodType = (e.target as HTMLSelectElement).value as 'flat_rate' | 'calculated')"
                    >
                        <option value="flat_rate">Flat Rate</option>
                        <option value="calculated">Calculated (Live Carrier Rate)</option>
                    </select>
                    <InputError :message="errors.type" />
                </div>

                <!-- Calculated fields -->
                <template v-if="methodType === 'calculated'">
                    <div class="grid gap-2">
                        <Label for="carrier">Carrier <span class="text-destructive">*</span></Label>
                        <select
                            id="carrier"
                            name="carrier"
                            class="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 focus-visible:outline-none"
                        >
                            <option value="canada_post">Canada Post</option>
                        </select>
                        <InputError :message="errors.carrier" />
                    </div>
                    <div class="grid gap-2">
                        <Label for="service_code">Service Code <span class="text-destructive">*</span></Label>
                        <Input
                            id="service_code"
                            name="service_code"
                            placeholder="e.g. DOM.EP"
                        />
                        <p class="text-xs text-muted-foreground">Canada Post service code (e.g. DOM.EP = Expedited Parcel)</p>
                        <InputError :message="errors.service_code" />
                    </div>
                </template>

                <!-- Free shipping toggle (flat rate only) -->
                <div v-if="methodType === 'flat_rate'" class="flex items-center gap-2">
                    <Checkbox
                        id="is_free"
                        name="is_free"
                        value="1"
                        @update:checked="(v) => (isFree = !!v)"
                    />
                    <Label for="is_free"
                        >Free shipping (no charge to customer)</Label
                    >
                </div>

                <!-- Price (flat rate only, hidden when free) -->
                <div v-if="methodType === 'flat_rate' && !isFree" class="grid max-w-xs gap-2">
                    <Label for="price"
                        >Price (cents)
                        <span class="text-destructive">*</span></Label
                    >
                    <Input
                        id="price"
                        name="price"
                        type="number"
                        min="0"
                        placeholder="e.g. 20000"
                    />
                    <p class="text-xs text-muted-foreground">
                        In cents — e.g. 20000 = $200.00
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
                            default-value="0"
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
                                :default-value="true"
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
                        {{ processing ? 'Creating...' : 'Create Method' }}
                    </Button>
                    <Link :href="index().url">
                        <Button variant="ghost" type="button">Cancel</Button>
                    </Link>
                </div>
            </Form>
        </div>
    </AppLayout>
</template>
