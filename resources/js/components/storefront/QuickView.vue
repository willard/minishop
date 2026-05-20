<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import {
    ShoppingBag,
    ChevronLeft,
    ChevronRight,
    X,
    ArrowUpRight,
} from 'lucide-vue-next';
import { computed, ref, watch } from 'vue';
import { show as productShow } from '@/actions/Minishop/Http/Controllers/Storefront/ProductController';
import { Dialog, DialogContent, DialogDescription, DialogOverlay, DialogTitle } from '@/components/ui/dialog';
import { useCart } from '@/composables/useCart';
import { usePrice } from '@/composables/usePrice';
import type { StorefrontProduct, StorefrontVariant } from '@/types/storefront';

const props = defineProps<{
    product: StorefrontProduct | null;
    isOpen: boolean;
}>();

const emit = defineEmits<{
    (e: 'close'): void;
}>();

const { addItem, lastAddedItem } = useCart();
const { formatPrice } = usePrice();

// Image gallery state
const activeImageIndex = ref(0);

// Variant selection: map optionId → optionValueId
const selectedOptions = ref<Record<number, number>>({});

// Initialize selection when product changes
watch(
    () => props.product,
    (newProduct) => {
        if (newProduct) {
            activeImageIndex.value = 0;
            selectedOptions.value = {};
            if (newProduct.options) {
                for (const option of newProduct.options) {
                    if (option.values.length > 0) {
                        selectedOptions.value[option.id] = option.values[0].id;
                    }
                }
            }
        }
    },
    { immediate: true },
);

const selectedVariant = computed<StorefrontVariant | null>(() => {
    if (!props.product?.variants || props.product.variants.length === 0) {
        return null;
    }

    const selectedValueIds = Object.values(selectedOptions.value);

    return (
        props.product.variants.find((variant) => {
            if (!variant.is_active) {
                return false;
            }
            const variantValueIds = variant.option_values.map((ov) => ov.id);

            return selectedValueIds.every((id) => variantValueIds.includes(id));
        }) ?? null
    );
});

const effectivePrice = computed<number>(() => {
    return selectedVariant.value?.price ?? props.product?.price ?? 0;
});

const effectiveStock = computed<number>(() => {
    return (
        selectedVariant.value?.stock_quantity ??
        props.product?.stock_quantity ??
        0
    );
});

const inStock = computed<boolean>(() => effectiveStock.value > 0);

const variantLabel = computed<string | null>(() => {
    if (!props.product?.options || props.product.options.length === 0) {
        return null;
    }

    return props.product.options
        .map((option) => {
            const valueId = selectedOptions.value[option.id];
            const value = option.values.find((v) => v.id === valueId);

            return value ? `${option.name}: ${value.value}` : null;
        })
        .filter(Boolean)
        .join(', ');
});

const hasVariants = computed<boolean>(() => {
    return (props.product?.options?.length ?? 0) > 0;
});

const displayImages = computed(() => {
    if (
        selectedVariant.value &&
        (selectedVariant.value.images?.length ?? 0) > 0
    ) {
        return selectedVariant.value.images;
    }

    return props.product?.images ?? [];
});

watch(selectedVariant, () => {
    activeImageIndex.value = 0;
});

function handleAddToCart(): void {
    if (!props.product || !inStock.value) {
        return;
    }

    addItem({
        productId: props.product.id,
        variantId: selectedVariant.value?.id ?? null,
        name: props.product.name,
        slug: props.product.slug,
        sku: selectedVariant.value?.sku ?? props.product.sku,
        price: effectivePrice.value,
        image: displayImages.value[0]?.url ?? null,
        variantLabel: variantLabel.value,
    });
}
</script>

<template>
    <Dialog :open="isOpen" @update:open="emit('close')">
        <DialogOverlay
            class="fixed inset-0 z-[80] bg-black/40 backdrop-blur-sm"
        />
        <DialogContent
            :show-close-button="false"
            class="fixed top-1/2 left-1/2 z-[90] w-[95%] max-w-4xl -translate-x-1/2 -translate-y-1/2 overflow-hidden rounded-2xl shadow-2xl transition-all duration-300 sm:w-full"
            style="background-color: #f9f6f0; color: #1c1a17"
        >
            <button
                class="absolute top-4 right-4 z-10 rounded-full p-2 transition-colors hover:bg-black/5"
                @click="emit('close')"
            >
                <X class="size-5" />
            </button>

            <div v-if="product" class="grid grid-cols-1 md:grid-cols-2">
                <!-- Left: Image -->
                <div class="relative aspect-square md:aspect-auto md:h-[600px]">
                    <div
                        class="h-full w-full overflow-hidden"
                        style="
                            background: linear-gradient(
                                135deg,
                                #e8dfd4 0%,
                                #d4c8b8 100%
                            );
                        "
                    >
                        <img
                            v-if="displayImages[activeImageIndex]"
                            :src="displayImages[activeImageIndex].url"
                            :alt="
                                displayImages[activeImageIndex].alt_text ??
                                product.name
                            "
                            class="h-full w-full object-cover"
                        />
                        <div
                            v-else
                            class="flex h-full w-full items-center justify-center"
                        >
                            <span
                                class="text-6xl font-medium"
                                style="
                                    font-family: 'Cormorant Garamond', serif;
                                    color: rgba(28, 26, 23, 0.2);
                                "
                            >
                                {{ product.name.charAt(0) }}
                            </span>
                        </div>
                    </div>

                    <!-- Navigation arrows -->
                    <div
                        v-if="displayImages.length > 1"
                        class="absolute inset-x-4 top-1/2 flex -translate-y-1/2 justify-between"
                    >
                        <button
                            v-if="activeImageIndex > 0"
                            class="flex size-10 items-center justify-center rounded-full bg-white/80 shadow-sm transition-opacity hover:bg-white"
                            @click="activeImageIndex--"
                        >
                            <ChevronLeft class="size-5" />
                        </button>
                        <div v-else class="size-10" />

                        <button
                            v-if="activeImageIndex < displayImages.length - 1"
                            class="flex size-10 items-center justify-center rounded-full bg-white/80 shadow-sm transition-opacity hover:bg-white"
                            @click="activeImageIndex++"
                        >
                            <ChevronRight class="size-5" />
                        </button>
                    </div>

                    <!-- Thumbnails overlay -->
                    <div
                        v-if="displayImages.length > 1"
                        class="absolute bottom-4 left-1/2 flex -translate-x-1/2 gap-2"
                    >
                        <button
                            v-for="(img, i) in displayImages"
                            :key="img.id"
                            class="size-2 rounded-full transition-all"
                            :style="
                                i === activeImageIndex
                                    ? 'background-color: #1c1a17; width: 16px'
                                    : 'background-color: rgba(28, 26, 23, 0.2)'
                            "
                            @click="activeImageIndex = i"
                        />
                    </div>
                </div>

                <!-- Right: Info -->
                <div
                    class="flex flex-col p-8 md:max-h-[600px] md:overflow-y-auto"
                >
                    <div class="mb-2">
                        <span
                            v-for="category in product.categories"
                            :key="category.id"
                            class="mr-3 text-xs font-semibold tracking-wider uppercase"
                            style="color: #c05c3a"
                        >
                            {{ category.name }}
                        </span>
                    </div>

                    <DialogTitle
                        class="mb-2 text-3xl leading-tight font-semibold"
                        style="font-family: 'Cormorant Garamond', serif"
                    >
                        {{ product.name }}
                    </DialogTitle>

                    <div class="mb-6 flex items-baseline gap-3">
                        <span class="text-xl font-semibold">
                            {{ formatPrice(effectivePrice) }}
                        </span>
                        <span
                            v-if="
                                product.compare_price &&
                                product.compare_price > product.price
                            "
                            class="text-base line-through opacity-40"
                        >
                            {{ formatPrice(product.compare_price) }}
                        </span>
                    </div>

                    <DialogDescription class="mb-8 text-sm leading-relaxed opacity-70">
                        {{ product.description?.substring(0, 180) }}...
                    </DialogDescription>

                    <!-- Variants -->
                    <div v-if="hasVariants" class="mb-8 space-y-6">
                        <div v-for="option in product.options" :key="option.id">
                            <p
                                class="mb-3 text-xs font-semibold tracking-widest uppercase opacity-50"
                            >
                                {{ option.name }}
                            </p>
                            <div class="flex flex-wrap gap-2">
                                <button
                                    v-for="value in option.values"
                                    :key="value.id"
                                    class="rounded-full border px-4 py-2 text-xs font-medium transition-all"
                                    :style="
                                        selectedOptions[option.id] === value.id
                                            ? 'background-color: #1c1a17; color: #f9f6f0; border-color: #1c1a17'
                                            : 'border-color: rgba(28, 26, 23, 0.2); hover:border-black'
                                    "
                                    @click="
                                        selectedOptions[option.id] = value.id
                                    "
                                >
                                    {{ value.value }}
                                </button>
                            </div>
                        </div>
                    </div>

                    <!-- Add to cart -->
                    <div class="mt-auto space-y-4">
                        <button
                            :disabled="!inStock"
                            class="flex w-full items-center justify-center gap-3 rounded-full py-4 text-sm font-semibold tracking-widest text-white uppercase transition-all"
                            :style="{
                                backgroundColor:
                                    lastAddedItem?.productId === product.id
                                        ? '#4a7c59'
                                        : inStock
                                          ? '#1c1a17'
                                          : 'rgba(28, 26, 23, 0.3)',
                                cursor: inStock ? 'pointer' : 'not-allowed',
                            }"
                            @click="handleAddToCart"
                        >
                            <ShoppingBag class="size-4" />
                            {{
                                lastAddedItem?.productId === product.id
                                    ? 'Added to Bag!'
                                    : inStock
                                      ? 'Add to Bag'
                                      : 'Sold Out'
                            }}
                        </button>

                        <Link
                            :href="productShow(product).url"
                            class="flex w-full items-center justify-center gap-2 text-xs font-semibold tracking-[0.2em] uppercase transition-opacity hover:opacity-60"
                        >
                            View full details
                            <ArrowUpRight class="size-3" />
                        </Link>
                    </div>
                </div>
            </div>
        </DialogContent>
    </Dialog>
</template>
