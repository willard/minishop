<script setup lang="ts">
import { Head, Link, useForm } from '@inertiajs/vue3';
import { ArrowLeft, Minus, Plus } from 'lucide-vue-next';
import AppLayout from '@/layouts/AppLayout.vue';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Checkbox } from '@/components/ui/checkbox';
import { type BreadcrumbItem } from '@/types';
import { index, show } from '@/actions/App/Http/Controllers/Admin/ProductController';
import { store } from '@/actions/App/Http/Controllers/Admin/ProductVariantController';

interface Product {
    id: number;
    name: string;
    slug: string;
}

const props = defineProps<{
    product: Product;
}>();

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Dashboard', href: '/dashboard' },
    { title: 'Products', href: index().url },
    { title: props.product.name, href: show(props.product).url },
    { title: 'Add Variant', href: '#' },
];

const form = useForm({
    sku: '',
    price: null as number | null,
    stock_quantity: 0,
    options: [{ name: '', value: '' }] as { name: string; value: string }[],
    is_active: true,
});

function addOption(): void {
    if (form.options.length < 3) {
        form.options.push({ name: '', value: '' });
    }
}

function removeOption(index: number): void {
    if (form.options.length > 1) {
        form.options.splice(index, 1);
    }
}

function submit(): void {
    form.post(store(props.product).url);
}
</script>

<template>
    <Head title="Add Variant" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex flex-col gap-6 p-4 max-w-2xl">
            <!-- Header -->
            <div class="flex items-center gap-4">
                <Link :href="show(product).url">
                    <Button variant="ghost" size="sm">
                        <ArrowLeft class="size-4" />
                    </Button>
                </Link>
                <div>
                    <h1 class="text-2xl font-semibold">Add Variant</h1>
                    <p class="text-sm text-muted-foreground">{{ product.name }}</p>
                </div>
            </div>

            <!-- Form -->
            <form class="flex flex-col gap-6" @submit.prevent="submit">
                <!-- Options -->
                <div class="grid gap-3">
                    <Label>Options <span class="text-destructive">*</span></Label>
                    <p class="text-xs text-muted-foreground -mt-1">Define option name and value pairs (e.g. Size: M, Color: Red). Up to 3.</p>

                    <div
                        v-for="(option, idx) in form.options"
                        :key="idx"
                        class="flex items-start gap-2"
                    >
                        <div class="grid gap-1 flex-1">
                            <Input
                                :id="`option-name-${idx}`"
                                v-model="form.options[idx].name"
                                placeholder="Name (e.g. Size)"
                            />
                            <InputError :message="(form.errors as Record<string, string>)[`options.${idx}.name`]" />
                        </div>
                        <div class="grid gap-1 flex-1">
                            <Input
                                :id="`option-value-${idx}`"
                                v-model="form.options[idx].value"
                                placeholder="Value (e.g. M)"
                            />
                            <InputError :message="(form.errors as Record<string, string>)[`options.${idx}.value`]" />
                        </div>
                        <Button
                            v-if="form.options.length > 1"
                            type="button"
                            variant="ghost"
                            size="sm"
                            class="mt-0.5"
                            @click="removeOption(idx)"
                        >
                            <Minus class="size-4" />
                        </Button>
                    </div>

                    <InputError :message="form.errors.options as string" />

                    <Button
                        v-if="form.options.length < 3"
                        type="button"
                        variant="outline"
                        size="sm"
                        class="w-fit text-xs"
                        @click="addOption"
                    >
                        <Plus class="mr-1 size-3" />
                        Add option
                    </Button>
                </div>

                <!-- SKU & Price -->
                <div class="grid grid-cols-2 gap-4">
                    <div class="grid gap-2">
                        <Label for="sku">SKU</Label>
                        <Input id="sku" v-model="form.sku" placeholder="e.g. TSH-M-RED" />
                        <InputError :message="form.errors.sku" />
                    </div>
                    <div class="grid gap-2">
                        <Label for="price">Price Override (cents)</Label>
                        <Input
                            id="price"
                            v-model.number="form.price"
                            type="number"
                            min="0"
                            placeholder="Leave empty to inherit"
                        />
                        <p class="text-xs text-muted-foreground">Leave empty to use the product price</p>
                        <InputError :message="form.errors.price" />
                    </div>
                </div>

                <!-- Stock -->
                <div class="grid gap-2">
                    <Label for="stock_quantity">Stock Quantity <span class="text-destructive">*</span></Label>
                    <Input
                        id="stock_quantity"
                        v-model.number="form.stock_quantity"
                        type="number"
                        min="0"
                        placeholder="0"
                        class="max-w-xs"
                    />
                    <InputError :message="form.errors.stock_quantity" />
                </div>

                <!-- Active Status -->
                <div class="flex items-center gap-2">
                    <Checkbox id="is_active" v-model:checked="form.is_active" />
                    <Label for="is_active">Active (visible in store)</Label>
                </div>

                <!-- Submit -->
                <div class="flex items-center gap-3">
                    <Button type="submit" :disabled="form.processing">
                        {{ form.processing ? 'Saving...' : 'Add Variant' }}
                    </Button>
                    <Link :href="show(product).url">
                        <Button variant="ghost" type="button">Cancel</Button>
                    </Link>
                </div>
            </form>
        </div>
    </AppLayout>
</template>
