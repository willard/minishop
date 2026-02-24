<script setup lang="ts">
import { Head, Link, useForm } from '@inertiajs/vue3';
import { ArrowLeft, Minus, Plus } from 'lucide-vue-next';
import AppLayout from '@/layouts/AppLayout.vue';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { type BreadcrumbItem } from '@/types';
import { index, show } from '@/actions/App/Http/Controllers/Admin/ProductController';
import { store } from '@/actions/App/Http/Controllers/Admin/ProductOptionController';

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
    { title: 'Add Option Type', href: '#' },
];

const form = useForm({
    name: '',
    values: [''] as string[],
});

function addValue(): void {
    form.values.push('');
}

function removeValue(index: number): void {
    if (form.values.length > 1) {
        form.values.splice(index, 1);
    }
}

function submit(): void {
    form.post(store(props.product).url);
}
</script>

<template>
    <Head title="Add Option Type" />

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
                    <h1 class="text-2xl font-semibold">Add Option Type</h1>
                    <p class="text-sm text-muted-foreground">{{ product.name }}</p>
                </div>
            </div>

            <!-- Form -->
            <form class="flex flex-col gap-6" @submit.prevent="submit">
                <!-- Name -->
                <div class="grid gap-2">
                    <Label for="name">Option Name <span class="text-destructive">*</span></Label>
                    <p class="text-xs text-muted-foreground -mt-1">e.g. Size, Color, Material</p>
                    <Input
                        id="name"
                        v-model="form.name"
                        placeholder="e.g. Size"
                        class="max-w-xs"
                        autofocus
                    />
                    <InputError :message="form.errors.name" />
                </div>

                <!-- Values -->
                <div class="grid gap-3">
                    <Label>Values <span class="text-destructive">*</span></Label>
                    <p class="text-xs text-muted-foreground -mt-1">The available choices for this option (e.g. S, M, L, XL).</p>

                    <div
                        v-for="(value, idx) in form.values"
                        :key="idx"
                        class="flex items-center gap-2"
                    >
                        <Input
                            :id="`value-${idx}`"
                            v-model="form.values[idx]"
                            placeholder="e.g. M"
                            class="max-w-xs"
                        />
                        <Button
                            v-if="form.values.length > 1"
                            type="button"
                            variant="ghost"
                            size="sm"
                            @click="removeValue(idx)"
                        >
                            <Minus class="size-4" />
                        </Button>
                        <InputError :message="(form.errors as Record<string, string>)[`values.${idx}`]" />
                    </div>

                    <InputError :message="form.errors.values as string" />

                    <Button
                        type="button"
                        variant="outline"
                        size="sm"
                        class="w-fit text-xs"
                        @click="addValue"
                    >
                        <Plus class="mr-1 size-3" />
                        Add value
                    </Button>
                </div>

                <!-- Submit -->
                <div class="flex items-center gap-3">
                    <Button type="submit" :disabled="form.processing">
                        {{ form.processing ? 'Saving...' : 'Add Option Type' }}
                    </Button>
                    <Link :href="show(product).url">
                        <Button variant="ghost" type="button">Cancel</Button>
                    </Link>
                </div>
            </form>
        </div>
    </AppLayout>
</template>
