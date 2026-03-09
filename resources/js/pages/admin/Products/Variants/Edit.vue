<script setup lang="ts">
import { Head, Link, useForm } from '@inertiajs/vue3';
import { ArrowLeft } from 'lucide-vue-next';
import {
    index,
    show,
} from '@/actions/App/Http/Controllers/Admin/ProductController';
import { update } from '@/actions/App/Http/Controllers/Admin/ProductVariantController';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem } from '@/types';

interface OptionValue {
    id: number;
    value: string;
    position: number;
}

interface OptionType {
    id: number;
    name: string;
    values: OptionValue[];
}

interface ExistingOptionValue {
    id: number;
    value: string;
    option: { id: number; name: string };
}

interface ProductVariant {
    id: number;
    sku: string | null;
    price: number | null;
    stock_quantity: number;
    is_active: boolean;
    option_values: ExistingOptionValue[];
}

interface Product {
    id: number;
    name: string;
    slug: string;
}

const props = defineProps<{
    product: Product;
    variant: ProductVariant;
    optionTypes: OptionType[];
}>();

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Dashboard', href: '/dashboard' },
    { title: 'Products', href: index().url },
    { title: props.product.name, href: show(props.product).url },
    { title: 'Edit Variant', href: '#' },
];

function initialOptionValueIds(): (number | null)[] {
    return props.optionTypes.map((optionType) => {
        const existing = props.variant.option_values.find(
            (ov) => ov.option.id === optionType.id,
        );
        return existing?.id ?? optionType.values[0]?.id ?? null;
    });
}

const form = useForm({
    sku: props.variant.sku ?? '',
    price: props.variant.price,
    stock_quantity: props.variant.stock_quantity,
    option_value_ids: initialOptionValueIds(),
    is_active: props.variant.is_active,
});

function submit(): void {
    form.put(update({ product: props.product, variant: props.variant }).url);
}
</script>

<template>
    <Head title="Edit Variant" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex max-w-2xl flex-col gap-6 p-4">
            <!-- Header -->
            <div class="flex items-center gap-4">
                <Link :href="show(product).url">
                    <Button variant="ghost" size="sm">
                        <ArrowLeft class="size-4" />
                    </Button>
                </Link>
                <div>
                    <h1 class="text-2xl font-semibold">Edit Variant</h1>
                    <p class="text-sm text-muted-foreground">
                        {{ product.name }}
                    </p>
                </div>
            </div>

            <!-- Form -->
            <form class="flex flex-col gap-6" @submit.prevent="submit">
                <!-- Option selects -->
                <div class="grid gap-4">
                    <div
                        v-for="(optionType, idx) in optionTypes"
                        :key="optionType.id"
                        class="grid gap-2"
                    >
                        <Label :for="`option-${optionType.id}`">
                            {{ optionType.name }}
                            <span class="text-destructive">*</span>
                        </Label>
                        <select
                            :id="`option-${optionType.id}`"
                            v-model="form.option_value_ids[idx]"
                            class="h-9 w-full max-w-xs rounded-md border border-input bg-transparent px-3 py-1 text-sm shadow-xs outline-none focus-visible:border-ring focus-visible:ring-[3px] focus-visible:ring-ring/50"
                        >
                            <option
                                v-for="val in optionType.values"
                                :key="val.id"
                                :value="val.id"
                            >
                                {{ val.value }}
                            </option>
                        </select>
                        <InputError
                            :message="
                                (form.errors as Record<string, string>)[
                                    `option_value_ids.${idx}`
                                ]
                            "
                        />
                    </div>
                    <InputError
                        :message="form.errors.option_value_ids as string"
                    />
                </div>

                <!-- SKU & Price -->
                <div class="grid grid-cols-2 gap-4">
                    <div class="grid gap-2">
                        <Label for="sku">SKU</Label>
                        <Input
                            id="sku"
                            v-model="form.sku"
                            placeholder="e.g. TSH-M-RED"
                        />
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
                        <p class="text-xs text-muted-foreground">
                            Leave empty to use the product price
                        </p>
                        <InputError :message="form.errors.price" />
                    </div>
                </div>

                <!-- Stock -->
                <div class="grid gap-2">
                    <Label for="stock_quantity"
                        >Stock Quantity
                        <span class="text-destructive">*</span></Label
                    >
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
                        {{ form.processing ? 'Saving...' : 'Save Changes' }}
                    </Button>
                    <Link :href="show(product).url">
                        <Button variant="ghost" type="button">Cancel</Button>
                    </Link>
                </div>
            </form>
        </div>
    </AppLayout>
</template>
