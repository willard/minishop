<script setup lang="ts">
import { Head, Link, router, useForm } from '@inertiajs/vue3';
import { ArrowLeft, ChevronDown, ChevronUp, Pencil, Plus, Trash2, Upload, X } from 'lucide-vue-next';
import { ref } from 'vue';
import AppLayout from '@/layouts/AppLayout.vue';
import { Button } from '@/components/ui/button';
import { Badge } from '@/components/ui/badge';
import { type BreadcrumbItem } from '@/types';
import { index, edit, destroy } from '@/actions/App/Http/Controllers/Admin/ProductController';
import {
    create as createVariant,
    edit as editVariant,
    destroy as destroyVariant,
} from '@/actions/App/Http/Controllers/Admin/ProductVariantController';
import {
    create as createOption,
    destroy as destroyOption,
} from '@/actions/App/Http/Controllers/Admin/ProductOptionController';
import {
    store as storeImage,
    destroy as destroyImage,
    reorder as reorderImages,
} from '@/actions/App/Http/Controllers/Admin/ProductImageController';

interface Category {
    id: number;
    name: string;
}

interface ProductImage {
    id: number;
    path: string;
    alt_text: string | null;
    sort_order: number;
}

interface ProductOptionValue {
    id: number;
    value: string;
    position: number;
}

interface ProductOption {
    id: number;
    name: string;
    position: number;
    values: ProductOptionValue[];
}

interface OptionValue {
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
    option_values: OptionValue[];
}

interface Product {
    id: number;
    name: string;
    slug: string;
    description: string | null;
    price: number;
    compare_price: number | null;
    stock_quantity: number;
    is_active: boolean;
    sku: string | null;
    categories: Category[];
    images: ProductImage[];
    options: ProductOption[];
    variants: ProductVariant[];
}

const props = defineProps<{
    product: Product;
}>();

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Dashboard', href: '/dashboard' },
    { title: 'Products', href: index().url },
    { title: props.product.name, href: '#' },
];

function formatPrice(cents: number): string {
    return (cents / 100).toFixed(2);
}

function confirmDelete(): void {
    if (confirm(`Delete "${props.product.name}"? This cannot be undone.`)) {
        router.delete(destroy(props.product).url);
    }
}

function confirmDeleteVariant(variant: ProductVariant): void {
    if (confirm('Delete this variant? This cannot be undone.')) {
        router.delete(destroyVariant({ product: props.product, variant }).url);
    }
}

function confirmDeleteOption(option: ProductOption): void {
    if (confirm(`Delete option "${option.name}" and all its values? Variants using these values will also be affected.`)) {
        router.delete(destroyOption({ product: props.product, option }).url);
    }
}

const imageForm = useForm({
    images: null as File[] | null,
    alt_text: null as string | null,
});

const fileInput = ref<HTMLInputElement | null>(null);

function uploadImages(): void {
    imageForm.post(storeImage(props.product).url, {
        forceFormData: true,
        preserveScroll: true,
        onSuccess: () => {
            imageForm.reset();
            if (fileInput.value) {
                fileInput.value.value = '';
            }
        },
    });
}

function onFileChange(event: Event): void {
    const target = event.target as HTMLInputElement;
    imageForm.images = target.files ? Array.from(target.files) : null;
}

function confirmDeleteImage(image: ProductImage): void {
    if (confirm('Delete this image? This cannot be undone.')) {
        router.delete(destroyImage({ product: props.product, image }).url, {
            preserveScroll: true,
        });
    }
}

function moveImage(fromIndex: number, toIndex: number): void {
    const ids = props.product.images.map((img) => img.id);
    const [moved] = ids.splice(fromIndex, 1);
    ids.splice(toIndex, 0, moved);

    router.put(reorderImages(props.product).url, {
        image_ids: ids,
    } as Record<string, unknown>, {
        preserveScroll: true,
    });
}
</script>

<template>
    <Head :title="product.name" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex flex-col gap-6 p-4 max-w-3xl">
            <!-- Header -->
            <div class="flex items-center justify-between">
                <div class="flex items-center gap-4">
                    <Link :href="index().url">
                        <Button variant="ghost" size="sm">
                            <ArrowLeft class="size-4" />
                        </Button>
                    </Link>
                    <div>
                        <h1 class="text-2xl font-semibold">{{ product.name }}</h1>
                        <p class="text-sm text-muted-foreground font-mono">{{ product.slug }}</p>
                    </div>
                </div>
                <div class="flex gap-2">
                    <Link :href="edit(product).url">
                        <Button variant="outline" size="sm">
                            <Pencil class="mr-2 size-4" />
                            Edit
                        </Button>
                    </Link>
                    <Button
                        variant="destructive"
                        size="sm"
                        @click="confirmDelete"
                    >
                        <Trash2 class="mr-2 size-4" />
                        Delete
                    </Button>
                </div>
            </div>

            <!-- Details -->
            <div class="rounded-lg border border-sidebar-border divide-y divide-sidebar-border">
                <!-- Status & Categories -->
                <div class="grid grid-cols-2 gap-4 px-4 py-3">
                    <div>
                        <p class="text-xs text-muted-foreground uppercase tracking-wide mb-1">Status</p>
                        <Badge :variant="product.is_active ? 'default' : 'secondary'">
                            {{ product.is_active ? 'Active' : 'Inactive' }}
                        </Badge>
                    </div>
                    <div>
                        <p class="text-xs text-muted-foreground uppercase tracking-wide mb-1">Categories</p>
                        <div class="flex flex-wrap gap-1">
                            <Badge
                                v-for="cat in product.categories"
                                :key="cat.id"
                                variant="secondary"
                                class="text-xs"
                            >
                                {{ cat.name }}
                            </Badge>
                            <span v-if="product.categories.length === 0" class="text-sm text-muted-foreground">—</span>
                        </div>
                    </div>
                </div>

                <!-- Pricing -->
                <div class="grid grid-cols-2 gap-4 px-4 py-3">
                    <div>
                        <p class="text-xs text-muted-foreground uppercase tracking-wide mb-1">Price</p>
                        <p class="font-medium">${{ formatPrice(product.price) }}</p>
                    </div>
                    <div v-if="product.compare_price">
                        <p class="text-xs text-muted-foreground uppercase tracking-wide mb-1">Compare Price</p>
                        <p class="font-medium line-through text-muted-foreground">${{ formatPrice(product.compare_price) }}</p>
                    </div>
                </div>

                <!-- SKU & Stock -->
                <div class="grid grid-cols-2 gap-4 px-4 py-3">
                    <div>
                        <p class="text-xs text-muted-foreground uppercase tracking-wide mb-1">SKU</p>
                        <p class="font-mono text-sm">{{ product.sku ?? '—' }}</p>
                    </div>
                    <div>
                        <p class="text-xs text-muted-foreground uppercase tracking-wide mb-1">Stock</p>
                        <p :class="product.stock_quantity === 0 ? 'text-destructive font-medium' : ''">
                            {{ product.stock_quantity }} units
                        </p>
                    </div>
                </div>

                <!-- Description -->
                <div v-if="product.description" class="px-4 py-3">
                    <p class="text-xs text-muted-foreground uppercase tracking-wide mb-1">Description</p>
                    <p class="text-sm whitespace-pre-wrap">{{ product.description }}</p>
                </div>
            </div>

            <!-- Images -->
            <div class="rounded-lg border border-sidebar-border overflow-hidden">
                <div class="px-4 py-3 border-b border-sidebar-border bg-muted/50">
                    <h2 class="font-semibold text-sm">Images</h2>
                </div>

                <div v-if="product.images.length === 0" class="px-4 py-6 text-center text-sm text-muted-foreground">
                    No images yet. Upload images below.
                </div>

                <div v-else class="p-4">
                    <div class="grid grid-cols-3 gap-3">
                        <div
                            v-for="(image, idx) in product.images"
                            :key="image.id"
                            class="group relative rounded-md border border-sidebar-border overflow-hidden"
                        >
                            <img
                                :src="`/storage/${image.path}`"
                                :alt="image.alt_text ?? product.name"
                                class="aspect-square w-full object-cover"
                            />
                            <Badge
                                v-if="idx === 0"
                                variant="default"
                                class="absolute top-1.5 left-1.5 text-[10px]"
                            >
                                Primary
                            </Badge>
                            <div class="absolute top-1.5 right-1.5 flex flex-col gap-1 opacity-0 group-hover:opacity-100 transition-opacity">
                                <Button
                                    v-if="idx > 0"
                                    variant="secondary"
                                    size="sm"
                                    class="h-6 w-6 p-0"
                                    @click="moveImage(idx, idx - 1)"
                                >
                                    <ChevronUp class="size-3" />
                                </Button>
                                <Button
                                    v-if="idx < product.images.length - 1"
                                    variant="secondary"
                                    size="sm"
                                    class="h-6 w-6 p-0"
                                    @click="moveImage(idx, idx + 1)"
                                >
                                    <ChevronDown class="size-3" />
                                </Button>
                                <Button
                                    variant="destructive"
                                    size="sm"
                                    class="h-6 w-6 p-0"
                                    @click="confirmDeleteImage(image)"
                                >
                                    <X class="size-3" />
                                </Button>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="border-t border-sidebar-border px-4 py-3">
                    <form class="flex items-end gap-3" @submit.prevent="uploadImages">
                        <div class="flex-1">
                            <label class="text-xs text-muted-foreground mb-1 block">Upload Images</label>
                            <input
                                ref="fileInput"
                                type="file"
                                multiple
                                accept="image/jpeg,image/png,image/webp,image/gif"
                                class="text-sm file:mr-3 file:rounded file:border-0 file:bg-muted file:px-3 file:py-1.5 file:text-xs file:font-medium"
                                @change="onFileChange"
                            />
                            <p v-if="imageForm.errors.images" class="text-xs text-destructive mt-1">{{ imageForm.errors.images }}</p>
                            <template v-for="(error, key) in imageForm.errors" :key="key">
                                <p v-if="String(key).startsWith('images.')" class="text-xs text-destructive mt-1">{{ error }}</p>
                            </template>
                        </div>
                        <Button
                            type="submit"
                            size="sm"
                            :disabled="!imageForm.images || imageForm.processing"
                        >
                            <Upload class="mr-1 size-3" />
                            Upload
                        </Button>
                    </form>
                </div>
            </div>

            <!-- Options -->
            <div class="rounded-lg border border-sidebar-border overflow-hidden">
                <div class="flex items-center justify-between px-4 py-3 border-b border-sidebar-border bg-muted/50">
                    <h2 class="font-semibold text-sm">Option Types</h2>
                    <Link :href="createOption(product).url">
                        <Button variant="outline" size="sm" class="text-xs h-7">
                            <Plus class="mr-1 size-3" />
                            Add Option Type
                        </Button>
                    </Link>
                </div>

                <div v-if="product.options.length === 0" class="px-4 py-6 text-center text-sm text-muted-foreground">
                    No option types yet. Add one (e.g. Size, Color) before creating variants.
                </div>

                <div v-else class="divide-y divide-sidebar-border">
                    <div
                        v-for="option in product.options"
                        :key="option.id"
                        class="flex items-center justify-between px-4 py-3"
                    >
                        <div class="flex items-center gap-3">
                            <span class="text-sm font-medium w-20 shrink-0">{{ option.name }}</span>
                            <div class="flex flex-wrap gap-1">
                                <Badge
                                    v-for="val in option.values"
                                    :key="val.id"
                                    variant="secondary"
                                    class="text-xs font-normal"
                                >
                                    {{ val.value }}
                                </Badge>
                                <span v-if="option.values.length === 0" class="text-xs text-muted-foreground italic">No values</span>
                            </div>
                        </div>
                        <Button
                            variant="ghost"
                            size="sm"
                            class="h-7 w-7 p-0 text-destructive hover:text-destructive"
                            @click="confirmDeleteOption(option)"
                        >
                            <Trash2 class="size-3" />
                        </Button>
                    </div>
                </div>
            </div>

            <!-- Variants -->
            <div class="rounded-lg border border-sidebar-border overflow-hidden">
                <div class="flex items-center justify-between px-4 py-3 border-b border-sidebar-border bg-muted/50">
                    <h2 class="font-semibold text-sm">Variants</h2>
                    <Link :href="createVariant(product).url">
                        <Button variant="outline" size="sm" class="text-xs h-7" :disabled="product.options.length === 0">
                            <Plus class="mr-1 size-3" />
                            Add Variant
                        </Button>
                    </Link>
                </div>

                <div v-if="product.options.length === 0" class="px-4 py-6 text-center text-sm text-muted-foreground">
                    Define option types above before adding variants.
                </div>

                <div v-else-if="product.variants.length === 0" class="px-4 py-8 text-center text-sm text-muted-foreground">
                    No variants yet. Add one to offer size, color, or other options.
                </div>

                <table v-else class="w-full text-sm">
                    <thead class="border-b border-sidebar-border bg-muted/20">
                        <tr>
                            <th class="px-4 py-2 text-left text-xs font-medium text-muted-foreground">Options</th>
                            <th class="px-4 py-2 text-left text-xs font-medium text-muted-foreground">SKU</th>
                            <th class="px-4 py-2 text-left text-xs font-medium text-muted-foreground">Price</th>
                            <th class="px-4 py-2 text-left text-xs font-medium text-muted-foreground">Stock</th>
                            <th class="px-4 py-2 text-left text-xs font-medium text-muted-foreground">Status</th>
                            <th class="px-4 py-2" />
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-sidebar-border">
                        <tr
                            v-for="variant in product.variants"
                            :key="variant.id"
                            class="hover:bg-muted/30 transition-colors"
                        >
                            <td class="px-4 py-2.5">
                                <div class="flex flex-wrap gap-1">
                                    <Badge
                                        v-for="ov in variant.option_values"
                                        :key="ov.id"
                                        variant="secondary"
                                        class="text-xs font-normal"
                                    >
                                        {{ ov.option.name }}: {{ ov.value }}
                                    </Badge>
                                    <span v-if="variant.option_values.length === 0" class="text-xs text-muted-foreground italic">No options</span>
                                </div>
                            </td>
                            <td class="px-4 py-2.5 font-mono text-xs text-muted-foreground">
                                {{ variant.sku ?? '—' }}
                            </td>
                            <td class="px-4 py-2.5">
                                <span v-if="variant.price !== null">${{ formatPrice(variant.price) }}</span>
                                <span v-else class="text-xs text-muted-foreground italic">Inherited</span>
                            </td>
                            <td class="px-4 py-2.5" :class="variant.stock_quantity === 0 ? 'text-destructive font-medium' : ''">
                                {{ variant.stock_quantity }}
                            </td>
                            <td class="px-4 py-2.5">
                                <Badge :variant="variant.is_active ? 'default' : 'secondary'" class="text-xs">
                                    {{ variant.is_active ? 'Active' : 'Inactive' }}
                                </Badge>
                            </td>
                            <td class="px-4 py-2.5 text-right">
                                <div class="flex items-center justify-end gap-1">
                                    <Link :href="editVariant({ product, variant }).url">
                                        <Button variant="ghost" size="sm" class="h-7 w-7 p-0">
                                            <Pencil class="size-3" />
                                        </Button>
                                    </Link>
                                    <Button
                                        variant="ghost"
                                        size="sm"
                                        class="h-7 w-7 p-0 text-destructive hover:text-destructive"
                                        @click="confirmDeleteVariant(variant)"
                                    >
                                        <Trash2 class="size-3" />
                                    </Button>
                                </div>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </AppLayout>
</template>
