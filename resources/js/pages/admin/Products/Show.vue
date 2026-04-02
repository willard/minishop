<script setup lang="ts">
import { Head, Link, router, useForm } from '@inertiajs/vue3';
import {
    ArrowLeft,
    ChevronDown,
    ChevronUp,
    ImagePlus,
    Pencil,
    Plus,
    Trash2,
    Upload,
    X,
} from 'lucide-vue-next';
import { ref, computed } from 'vue';
import {
    index,
    edit,
    destroy,
} from '@/actions/App/Http/Controllers/Admin/ProductController';
import {
    store as storeImage,
    destroy as destroyImage,
    reorder as reorderImages,
} from '@/actions/App/Http/Controllers/Admin/ProductImageController';
import {
    create as createOption,
    destroy as destroyOption,
} from '@/actions/App/Http/Controllers/Admin/ProductOptionController';
import {
    store as storeRelated,
    destroy as destroyRelated,
} from '@/actions/App/Http/Controllers/Admin/ProductRelatedController';
import {
    create as createVariant,
    edit as editVariant,
    destroy as destroyVariant,
} from '@/actions/App/Http/Controllers/Admin/ProductVariantController';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem } from '@/types';

interface Category {
    id: number;
    name: string;
}

interface ProductImage {
    id: number;
    path: string;
    url: string;
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
    images: ProductImage[];
}

interface RelatedProduct {
    id: number;
    name: string;
    slug: string;
    images: ProductImage[];
}

interface AvailableProduct {
    id: number;
    name: string;
    slug: string;
}

interface Product {
    id: number;
    name: string;
    slug: string;
    description: string | null;
    meta_title: string | null;
    meta_description: string | null;
    price: number;
    compare_price: number | null;
    stock_quantity: number;
    is_active: boolean;
    sku: string | null;
    categories: Category[];
    images: ProductImage[];
    options: ProductOption[];
    variants: ProductVariant[];
    related_products: RelatedProduct[];
}

const props = defineProps<{
    product: Product;
    availableProducts: AvailableProduct[];
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

// Per-variant image upload: track a form and file input ref per variant id
const variantImageForms = ref<Record<number, ReturnType<typeof useForm>>>({});
const variantFileInputs = ref<Record<number, HTMLInputElement | null>>({});
const expandedVariants = ref<number[]>([]);

function getVariantImageForm(variantId: number): ReturnType<typeof useForm> {
    if (!variantImageForms.value[variantId]) {
        variantImageForms.value[variantId] = useForm({
            images: null as File[] | null,
            variant_id: variantId,
        });
    }

    return variantImageForms.value[variantId];
}

function toggleVariantImages(variantId: number): void {
    if (expandedVariants.value.includes(variantId)) {
        expandedVariants.value = expandedVariants.value.filter((id) => id !== variantId);
    } else {
        expandedVariants.value = [...expandedVariants.value, variantId];
    }
}

function onVariantFileChange(variantId: number, event: Event): void {
    const target = event.target as HTMLInputElement;
    const form = getVariantImageForm(variantId);
    form.images = target.files ? Array.from(target.files) : null;
}

function uploadVariantImages(variantId: number): void {
    const form = getVariantImageForm(variantId);
    form.post(storeImage(props.product).url, {
        forceFormData: true,
        preserveScroll: true,
        onSuccess: () => {
            form.reset();
            const input = variantFileInputs.value[variantId];
            if (input) {
                input.value = '';
            }
        },
    });
}

function confirmDeleteVariantImage(image: ProductImage): void {
    if (confirm('Delete this image? This cannot be undone.')) {
        router.delete(destroyImage({ product: props.product, image }).url, {
            preserveScroll: true,
        });
    }
}

function confirmDeleteOption(option: ProductOption): void {
    if (
        confirm(
            `Delete option "${option.name}" and all its values? Variants using these values will also be affected.`,
        )
    ) {
        router.delete(destroyOption({ product: props.product, option }).url);
    }
}

const selectedRelatedId = ref<number | ''>('');
const relatedAlreadyAdded = computed(() =>
    props.product.related_products.map((p) => p.id),
);
const availableToAdd = computed(() =>
    props.availableProducts.filter((p) => !relatedAlreadyAdded.value.includes(p.id)),
);

function addRelated(): void {
    if (!selectedRelatedId.value) {
        return;
    }
    router.post(
        storeRelated(props.product).url,
        { related_product_id: selectedRelatedId.value },
        {
            preserveScroll: true,
            onSuccess: () => {
                selectedRelatedId.value = '';
            },
        },
    );
}

function confirmRemoveRelated(related: RelatedProduct): void {
    if (confirm(`Remove "${related.name}" from related products?`)) {
        router.delete(destroyRelated({ product: props.product, related }).url, {
            preserveScroll: true,
        });
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

    router.put(
        reorderImages(props.product).url,
        {
            image_ids: ids,
        } as Record<string, unknown>,
        {
            preserveScroll: true,
        },
    );
}
</script>

<template>
    <Head :title="product.name" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex max-w-3xl flex-col gap-6 p-4">
            <!-- Header -->
            <div class="flex items-center justify-between">
                <div class="flex items-center gap-4">
                    <Link :href="index().url">
                        <Button variant="ghost" size="sm">
                            <ArrowLeft class="size-4" />
                        </Button>
                    </Link>
                    <div>
                        <h1 class="text-2xl font-semibold">
                            {{ product.name }}
                        </h1>
                        <p class="font-mono text-sm text-muted-foreground">
                            {{ product.slug }}
                        </p>
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
            <div
                class="divide-y divide-sidebar-border rounded-lg border border-sidebar-border"
            >
                <!-- Status & Categories -->
                <div class="grid grid-cols-2 gap-4 px-4 py-3">
                    <div>
                        <p
                            class="mb-1 text-xs tracking-wide text-muted-foreground uppercase"
                        >
                            Status
                        </p>
                        <Badge
                            :variant="
                                product.is_active ? 'default' : 'secondary'
                            "
                        >
                            {{ product.is_active ? 'Active' : 'Inactive' }}
                        </Badge>
                    </div>
                    <div>
                        <p
                            class="mb-1 text-xs tracking-wide text-muted-foreground uppercase"
                        >
                            Categories
                        </p>
                        <div class="flex flex-wrap gap-1">
                            <Badge
                                v-for="cat in product.categories"
                                :key="cat.id"
                                variant="secondary"
                                class="text-xs"
                            >
                                {{ cat.name }}
                            </Badge>
                            <span
                                v-if="product.categories.length === 0"
                                class="text-sm text-muted-foreground"
                                >—</span
                            >
                        </div>
                    </div>
                </div>

                <!-- Pricing -->
                <div class="grid grid-cols-2 gap-4 px-4 py-3">
                    <div>
                        <p
                            class="mb-1 text-xs tracking-wide text-muted-foreground uppercase"
                        >
                            Price
                        </p>
                        <p class="font-medium">
                            ${{ formatPrice(product.price) }}
                        </p>
                    </div>
                    <div v-if="product.compare_price">
                        <p
                            class="mb-1 text-xs tracking-wide text-muted-foreground uppercase"
                        >
                            Compare Price
                        </p>
                        <p
                            class="font-medium text-muted-foreground line-through"
                        >
                            ${{ formatPrice(product.compare_price) }}
                        </p>
                    </div>
                </div>

                <!-- SKU & Stock -->
                <div class="grid grid-cols-2 gap-4 px-4 py-3">
                    <div>
                        <p
                            class="mb-1 text-xs tracking-wide text-muted-foreground uppercase"
                        >
                            SKU
                        </p>
                        <p class="font-mono text-sm">
                            {{ product.sku ?? '—' }}
                        </p>
                    </div>
                    <div>
                        <p
                            class="mb-1 text-xs tracking-wide text-muted-foreground uppercase"
                        >
                            Stock
                        </p>
                        <p
                            :class="
                                product.stock_quantity === 0
                                    ? 'font-medium text-destructive'
                                    : ''
                            "
                        >
                            {{ product.stock_quantity }} units
                        </p>
                    </div>
                </div>

                <!-- Description -->
                <div v-if="product.description" class="px-4 py-3">
                    <p
                        class="mb-1 text-xs tracking-wide text-muted-foreground uppercase"
                    >
                        Description
                    </p>
                    <p class="text-sm whitespace-pre-wrap">
                        {{ product.description }}
                    </p>
                </div>

                <!-- SEO -->
                <div v-if="product.meta_title || product.meta_description" class="px-4 py-3">
                    <p class="mb-2 text-xs tracking-wide text-muted-foreground uppercase">
                        SEO
                    </p>
                    <div class="flex flex-col gap-2">
                        <div v-if="product.meta_title">
                            <p class="text-xs text-muted-foreground">Meta Title</p>
                            <p class="text-sm">{{ product.meta_title }}</p>
                        </div>
                        <div v-if="product.meta_description">
                            <p class="text-xs text-muted-foreground">Meta Description</p>
                            <p class="text-sm text-muted-foreground">{{ product.meta_description }}</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Images -->
            <div
                class="overflow-hidden rounded-lg border border-sidebar-border"
            >
                <div
                    class="border-b border-sidebar-border bg-muted/50 px-4 py-3"
                >
                    <h2 class="text-sm font-semibold">Images</h2>
                </div>

                <div
                    v-if="product.images.length === 0"
                    class="px-4 py-6 text-center text-sm text-muted-foreground"
                >
                    No images yet. Upload images below.
                </div>

                <div v-else class="p-4">
                    <div class="grid grid-cols-3 gap-3">
                        <div
                            v-for="(image, idx) in product.images"
                            :key="image.id"
                            class="group relative overflow-hidden rounded-md border border-sidebar-border"
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
                            <div
                                class="absolute top-1.5 right-1.5 flex flex-col gap-1 opacity-0 transition-opacity group-hover:opacity-100"
                            >
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
                    <form
                        class="flex items-end gap-3"
                        @submit.prevent="uploadImages"
                    >
                        <div class="flex-1">
                            <label
                                class="mb-1 block text-xs text-muted-foreground"
                                >Upload Images</label
                            >
                            <input
                                ref="fileInput"
                                type="file"
                                multiple
                                accept="image/jpeg,image/png,image/webp,image/gif"
                                class="text-sm file:mr-3 file:rounded file:border-0 file:bg-muted file:px-3 file:py-1.5 file:text-xs file:font-medium"
                                @change="onFileChange"
                            />
                            <p
                                v-if="imageForm.errors.images"
                                class="mt-1 text-xs text-destructive"
                            >
                                {{ imageForm.errors.images }}
                            </p>
                            <template
                                v-for="(error, key) in imageForm.errors"
                                :key="key"
                            >
                                <p
                                    v-if="String(key).startsWith('images.')"
                                    class="mt-1 text-xs text-destructive"
                                >
                                    {{ error }}
                                </p>
                            </template>
                        </div>
                        <Button
                            type="submit"
                            size="sm"
                            :disabled="
                                !imageForm.images || imageForm.processing
                            "
                        >
                            <Upload class="mr-1 size-3" />
                            Upload
                        </Button>
                    </form>
                </div>
            </div>

            <!-- Options -->
            <div
                class="overflow-hidden rounded-lg border border-sidebar-border"
            >
                <div
                    class="flex items-center justify-between border-b border-sidebar-border bg-muted/50 px-4 py-3"
                >
                    <h2 class="text-sm font-semibold">Option Types</h2>
                    <Link :href="createOption(product).url">
                        <Button variant="outline" size="sm" class="h-7 text-xs">
                            <Plus class="mr-1 size-3" />
                            Add Option Type
                        </Button>
                    </Link>
                </div>

                <div
                    v-if="product.options.length === 0"
                    class="px-4 py-6 text-center text-sm text-muted-foreground"
                >
                    No option types yet. Add one (e.g. Size, Color) before
                    creating variants.
                </div>

                <div v-else class="divide-y divide-sidebar-border">
                    <div
                        v-for="option in product.options"
                        :key="option.id"
                        class="flex items-center justify-between px-4 py-3"
                    >
                        <div class="flex items-center gap-3">
                            <span class="w-20 shrink-0 text-sm font-medium">{{
                                option.name
                            }}</span>
                            <div class="flex flex-wrap gap-1">
                                <Badge
                                    v-for="val in option.values"
                                    :key="val.id"
                                    variant="secondary"
                                    class="text-xs font-normal"
                                >
                                    {{ val.value }}
                                </Badge>
                                <span
                                    v-if="option.values.length === 0"
                                    class="text-xs text-muted-foreground italic"
                                    >No values</span
                                >
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
            <div
                class="overflow-hidden rounded-lg border border-sidebar-border"
            >
                <div
                    class="flex items-center justify-between border-b border-sidebar-border bg-muted/50 px-4 py-3"
                >
                    <h2 class="text-sm font-semibold">Variants</h2>
                    <Link :href="createVariant(product).url">
                        <Button
                            variant="outline"
                            size="sm"
                            class="h-7 text-xs"
                            :disabled="product.options.length === 0"
                        >
                            <Plus class="mr-1 size-3" />
                            Add Variant
                        </Button>
                    </Link>
                </div>

                <div
                    v-if="product.options.length === 0"
                    class="px-4 py-6 text-center text-sm text-muted-foreground"
                >
                    Define option types above before adding variants.
                </div>

                <div
                    v-else-if="product.variants.length === 0"
                    class="px-4 py-8 text-center text-sm text-muted-foreground"
                >
                    No variants yet. Add one to offer size, color, or other
                    options.
                </div>

                <div v-else class="divide-y divide-sidebar-border">
                    <div
                        v-for="variant in product.variants"
                        :key="variant.id"
                    >
                        <!-- Variant row -->
                        <div
                            class="flex items-center gap-3 px-4 py-3 transition-colors hover:bg-muted/20"
                        >
                            <!-- Option badges -->
                            <div class="flex min-w-0 flex-1 flex-wrap gap-1">
                                <Badge
                                    v-for="ov in variant.option_values"
                                    :key="ov.id"
                                    variant="secondary"
                                    class="text-xs font-normal"
                                >
                                    {{ ov.option.name }}: {{ ov.value }}
                                </Badge>
                                <span
                                    v-if="variant.option_values.length === 0"
                                    class="text-xs text-muted-foreground italic"
                                    >No options</span
                                >
                            </div>

                            <!-- Meta info -->
                            <div
                                class="hidden items-center gap-4 text-sm sm:flex"
                            >
                                <span class="font-mono text-xs text-muted-foreground">{{
                                    variant.sku ?? '—'
                                }}</span>
                                <span v-if="variant.price !== null"
                                    >${{ formatPrice(variant.price) }}</span
                                >
                                <span
                                    v-else
                                    class="text-xs text-muted-foreground italic"
                                    >Inherited</span
                                >
                                <span
                                    :class="
                                        variant.stock_quantity === 0
                                            ? 'font-medium text-destructive'
                                            : 'text-muted-foreground'
                                    "
                                    class="text-xs"
                                    >{{ variant.stock_quantity }} in stock</span
                                >
                                <Badge
                                    :variant="
                                        variant.is_active
                                            ? 'default'
                                            : 'secondary'
                                    "
                                    class="text-xs"
                                >
                                    {{
                                        variant.is_active
                                            ? 'Active'
                                            : 'Inactive'
                                    }}
                                </Badge>
                            </div>

                            <!-- Actions -->
                            <div class="flex items-center gap-1">
                                <Button
                                    variant="ghost"
                                    size="sm"
                                    class="h-7 gap-1 px-2 text-xs"
                                    :class="
                                        expandedVariants.includes(variant.id)
                                            ? 'text-foreground'
                                            : 'text-muted-foreground'
                                    "
                                    @click="toggleVariantImages(variant.id)"
                                >
                                    <ImagePlus class="size-3" />
                                    <span
                                        >{{
                                            variant.images.length || ''
                                        }}
                                        Images</span
                                    >
                                    <ChevronDown
                                        class="size-3 transition-transform"
                                        :class="
                                            expandedVariants.includes(variant.id)
                                                ? 'rotate-180'
                                                : ''
                                        "
                                    />
                                </Button>
                                <Link
                                    :href="
                                        editVariant({ product, variant }).url
                                    "
                                >
                                    <Button
                                        variant="ghost"
                                        size="sm"
                                        class="h-7 w-7 p-0"
                                    >
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
                        </div>

                        <!-- Expandable image section -->
                        <div
                            v-if="expandedVariants.includes(variant.id)"
                            class="border-t border-dashed border-sidebar-border bg-muted/30 px-4 py-4"
                        >
                            <!-- Existing variant images -->
                            <div
                                v-if="variant.images.length > 0"
                                class="mb-4 grid grid-cols-4 gap-2"
                            >
                                <div
                                    v-for="image in variant.images"
                                    :key="image.id"
                                    class="group relative overflow-hidden rounded-md border border-sidebar-border"
                                >
                                    <img
                                        :src="`/storage/${image.path}`"
                                        :alt="image.alt_text ?? product.name"
                                        class="aspect-square w-full object-cover"
                                    />
                                    <Button
                                        variant="destructive"
                                        size="sm"
                                        class="absolute top-1 right-1 h-5 w-5 p-0 opacity-0 transition-opacity group-hover:opacity-100"
                                        @click="confirmDeleteVariantImage(image)"
                                    >
                                        <X class="size-3" />
                                    </Button>
                                </div>
                            </div>
                            <p
                                v-else
                                class="mb-3 text-xs text-muted-foreground"
                            >
                                No images for this variant yet.
                            </p>

                            <!-- Upload form -->
                            <form
                                class="flex items-end gap-2"
                                @submit.prevent="
                                    uploadVariantImages(variant.id)
                                "
                            >
                                <input
                                    :ref="
                                        (el) =>
                                            (variantFileInputs[variant.id] =
                                                el as HTMLInputElement | null)
                                    "
                                    type="file"
                                    multiple
                                    accept="image/jpeg,image/png,image/webp,image/gif"
                                    class="text-sm file:mr-2 file:rounded file:border-0 file:bg-muted file:px-2.5 file:py-1 file:text-xs file:font-medium"
                                    @change="
                                        onVariantFileChange(variant.id, $event)
                                    "
                                />
                                <Button
                                    type="submit"
                                    size="sm"
                                    class="h-8 shrink-0"
                                    :disabled="
                                        !getVariantImageForm(variant.id)
                                            .images ||
                                        getVariantImageForm(variant.id)
                                            .processing
                                    "
                                >
                                    <Upload class="mr-1 size-3" />
                                    Upload
                                </Button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
            <!-- Related Products -->
            <div class="overflow-hidden rounded-lg border border-sidebar-border">
                <div class="flex items-center justify-between border-b border-sidebar-border bg-muted/50 px-4 py-3">
                    <h2 class="text-sm font-semibold">Related Products / Upsells</h2>
                </div>

                <div v-if="product.related_products.length === 0" class="px-4 py-6 text-center text-sm text-muted-foreground">
                    No related products yet.
                </div>

                <div v-else class="divide-y divide-sidebar-border">
                    <div
                        v-for="related in product.related_products"
                        :key="related.id"
                        class="flex items-center justify-between px-4 py-3"
                    >
                        <div class="flex items-center gap-3">
                            <img
                                v-if="related.images.length > 0"
                                :src="`/storage/${related.images[0].path}`"
                                :alt="related.name"
                                class="size-10 rounded-md border border-sidebar-border object-cover"
                            />
                            <div
                                v-else
                                class="flex size-10 items-center justify-center rounded-md border border-sidebar-border bg-muted text-xs text-muted-foreground"
                            >
                                —
                            </div>
                            <div>
                                <p class="text-sm font-medium">{{ related.name }}</p>
                                <p class="font-mono text-xs text-muted-foreground">{{ related.slug }}</p>
                            </div>
                        </div>
                        <Button
                            variant="ghost"
                            size="sm"
                            class="h-7 w-7 p-0 text-destructive hover:text-destructive"
                            @click="confirmRemoveRelated(related)"
                        >
                            <X class="size-3" />
                        </Button>
                    </div>
                </div>

                <div class="border-t border-sidebar-border px-4 py-3">
                    <div class="flex items-center gap-2">
                        <select
                            v-model="selectedRelatedId"
                            class="flex-1 rounded-md border border-input bg-background px-3 py-1.5 text-sm focus-visible:ring-2 focus-visible:ring-ring focus-visible:outline-none"
                        >
                            <option value="">Select a product…</option>
                            <option
                                v-for="p in availableToAdd"
                                :key="p.id"
                                :value="p.id"
                            >
                                {{ p.name }}
                            </option>
                        </select>
                        <Button size="sm" :disabled="!selectedRelatedId" @click="addRelated">
                            <Plus class="mr-1 size-3" />
                            Add
                        </Button>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
