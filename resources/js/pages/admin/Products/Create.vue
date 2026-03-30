<script setup lang="ts">
import { Form, Head, Link } from '@inertiajs/vue3';
import { ArrowLeft } from 'lucide-vue-next';
import {
    index,
    create,
    store,
} from '@/actions/App/Http/Controllers/Admin/ProductController';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem } from '@/types';

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
        <div class="flex max-w-2xl flex-col gap-6 p-4">
            <!-- Header -->
            <div class="flex items-center gap-4">
                <Link :href="index().url">
                    <Button variant="ghost" size="sm">
                        <ArrowLeft class="size-4" />
                    </Button>
                </Link>
                <div>
                    <h1 class="text-2xl font-semibold">Add Product</h1>
                    <p class="text-sm text-muted-foreground">
                        Create a new product in your store
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
                        placeholder="Product name"
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
                        rows="4"
                        placeholder="Product description"
                        class="flex w-full resize-none rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background placeholder:text-muted-foreground focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 focus-visible:outline-none disabled:cursor-not-allowed disabled:opacity-50"
                    />
                    <InputError :message="errors.description" />
                </div>

                <!-- SEO -->
                <div class="grid gap-4 rounded-lg border border-sidebar-border p-4">
                    <p class="text-xs font-semibold tracking-wide text-muted-foreground uppercase">SEO</p>
                    <div class="grid gap-2">
                        <Label for="meta_title">Meta Title</Label>
                        <Input
                            id="meta_title"
                            name="meta_title"
                            placeholder="Page title for search engines"
                        />
                        <p class="text-xs text-muted-foreground">Leave blank to use product name.</p>
                        <InputError :message="errors.meta_title" />
                    </div>
                    <div class="grid gap-2">
                        <Label for="meta_description">Meta Description</Label>
                        <textarea
                            id="meta_description"
                            name="meta_description"
                            rows="3"
                            placeholder="Brief description for search results"
                            class="flex w-full resize-none rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background placeholder:text-muted-foreground focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 focus-visible:outline-none disabled:cursor-not-allowed disabled:opacity-50"
                        />
                        <p class="text-xs text-muted-foreground">Recommended: 150–160 characters.</p>
                        <InputError :message="errors.meta_description" />
                    </div>
                </div>

                <!-- Price & Compare Price -->
                <div class="grid grid-cols-2 gap-4">
                    <div class="grid gap-2">
                        <Label for="price"
                            >Price (cents)
                            <span class="text-destructive">*</span></Label
                        >
                        <Input
                            id="price"
                            name="price"
                            type="number"
                            min="0"
                            placeholder="e.g. 1999"
                        />
                        <p class="text-xs text-muted-foreground">
                            Enter amount in cents (e.g. 1999 = $19.99)
                        </p>
                        <InputError :message="errors.price" />
                    </div>
                    <div class="grid gap-2">
                        <Label for="compare_price">Compare Price (cents)</Label>
                        <Input
                            id="compare_price"
                            name="compare_price"
                            type="number"
                            min="0"
                            placeholder="e.g. 2999"
                        />
                        <p class="text-xs text-muted-foreground">
                            Must be greater than price
                        </p>
                        <InputError :message="errors.compare_price" />
                    </div>
                </div>

                <!-- SKU & Stock -->
                <div class="grid grid-cols-2 gap-4">
                    <div class="grid gap-2">
                        <Label for="sku">SKU</Label>
                        <Input
                            id="sku"
                            name="sku"
                            placeholder="e.g. ABC-1234"
                        />
                        <InputError :message="errors.sku" />
                    </div>
                    <div class="grid gap-2">
                        <Label for="stock_quantity">Stock Quantity</Label>
                        <Input
                            id="stock_quantity"
                            name="stock_quantity"
                            type="number"
                            min="0"
                            placeholder="0"
                        />
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
                            class="flex cursor-pointer items-center gap-2"
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
                    <Checkbox
                        id="is_active"
                        name="is_active"
                        value="1"
                        :default-value="true"
                    />
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
