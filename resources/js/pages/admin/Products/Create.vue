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
import { index, create, store } from '@/actions/App/Http/Controllers/Admin/ProductController';

interface Category {
    id: number;
    name: string;
}

defineProps<{
    categories: Category[];
}>();

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Dashboard', href: '/dashboard' },
    { title: 'Products', href: index().url },
    { title: 'Add Product', href: create().url },
];
</script>

<template>
    <Head title="Add Product" />

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
                    <h1 class="text-2xl font-semibold">Add Product</h1>
                    <p class="text-sm text-muted-foreground">Create a new product in your store</p>
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
                    <Label for="name">Name <span class="text-destructive">*</span></Label>
                    <Input id="name" name="name" placeholder="Product name" required />
                    <InputError :message="errors.name" />
                </div>

                <!-- Description -->
                <div class="grid gap-2">
                    <Label for="description">Description</Label>
                    <textarea
                        id="description"
                        name="description"
                        rows="4"
                        placeholder="Product description"
                        class="flex w-full rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background placeholder:text-muted-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-50 resize-none"
                    />
                    <InputError :message="errors.description" />
                </div>

                <!-- Price & Compare Price -->
                <div class="grid grid-cols-2 gap-4">
                    <div class="grid gap-2">
                        <Label for="price">Price (cents) <span class="text-destructive">*</span></Label>
                        <Input id="price" name="price" type="number" min="0" placeholder="e.g. 1999" />
                        <p class="text-xs text-muted-foreground">Enter amount in cents (e.g. 1999 = $19.99)</p>
                        <InputError :message="errors.price" />
                    </div>
                    <div class="grid gap-2">
                        <Label for="compare_price">Compare Price (cents)</Label>
                        <Input id="compare_price" name="compare_price" type="number" min="0" placeholder="e.g. 2999" />
                        <p class="text-xs text-muted-foreground">Must be greater than price</p>
                        <InputError :message="errors.compare_price" />
                    </div>
                </div>

                <!-- SKU & Stock -->
                <div class="grid grid-cols-2 gap-4">
                    <div class="grid gap-2">
                        <Label for="sku">SKU</Label>
                        <Input id="sku" name="sku" placeholder="e.g. ABC-1234" />
                        <InputError :message="errors.sku" />
                    </div>
                    <div class="grid gap-2">
                        <Label for="stock_quantity">Stock Quantity</Label>
                        <Input id="stock_quantity" name="stock_quantity" type="number" min="0" placeholder="0" />
                        <InputError :message="errors.stock_quantity" />
                    </div>
                </div>

                <!-- Categories -->
                <div class="grid gap-2" v-if="categories.length > 0">
                    <Label>Categories</Label>
                    <div class="flex flex-wrap gap-3">
                        <label
                            v-for="category in categories"
                            :key="category.id"
                            class="flex items-center gap-2 cursor-pointer"
                        >
                            <Checkbox
                                :name="`category_ids[]`"
                                :value="category.id"
                            />
                            <span class="text-sm">{{ category.name }}</span>
                        </label>
                    </div>
                    <InputError :message="errors.category_ids" />
                </div>

                <!-- Active Status -->
                <div class="flex items-center gap-2">
                    <Checkbox id="is_active" name="is_active" value="1" :default-checked="true" />
                    <Label for="is_active">Active (visible in store)</Label>
                </div>

                <!-- Submit -->
                <div class="flex items-center gap-3">
                    <Button type="submit" :disabled="processing">
                        {{ processing ? 'Creating...' : 'Create Product' }}
                    </Button>
                    <Link :href="index().url">
                        <Button variant="ghost" type="button">Cancel</Button>
                    </Link>
                </div>
            </Form>
        </div>
    </AppLayout>
</template>
