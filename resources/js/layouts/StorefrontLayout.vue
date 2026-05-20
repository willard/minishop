<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import { useDebounceFn, onClickOutside } from '@vueuse/core';
import {
    ShoppingBag,
    Menu,
    X,
    Search,
    ArrowRight,
    Loader2,
} from 'lucide-vue-next';
import { ref, watch } from 'vue';
import {
    index as productsIndex,
    show as productShow,
} from '@/actions/Minishop/Http/Controllers/Storefront/ProductController';
import CartDrawer from '@/components/storefront/CartDrawer.vue';
import ChatWidget from '@/components/storefront/ChatWidget.vue';
import { useCart } from '@/composables/useCart';
import { usePrice } from '@/composables/usePrice';
import type { StorefrontCategory, StorefrontProduct } from '@/types/storefront';

defineProps<{
    categories?: StorefrontCategory[];
}>();

const { itemCount, isDrawerOpen, openDrawer, closeDrawer } = useCart();
const { formatPrice } = usePrice();
const mobileMenuOpen = ref(false);

// Search logic
const searchInput = ref('');
const searchResults = ref<StorefrontProduct[]>([]);
const isSearching = ref(false);
const showResults = ref(false);
const searchContainer = ref<HTMLElement | null>(null);

onClickOutside(searchContainer, () => {
    showResults.value = false;
});

const performSearch = useDebounceFn(async () => {
    if (!searchInput.value.trim()) {
        searchResults.value = [];
        showResults.value = false;
        return;
    }

    isSearching.value = true;
    showResults.value = true;

    try {
        const response = await fetch(
            `/api/v1/products?search=${encodeURIComponent(searchInput.value)}`,
        );
        const data = await response.json();
        searchResults.value = data.data;
    } catch (error) {
        console.error('Search error:', error);
    } finally {
        isSearching.value = false;
    }
}, 300);

watch(searchInput, performSearch);

function navigateToProduct(product: StorefrontProduct): void {
    showResults.value = false;
    searchInput.value = '';
    router.get(productShow(product).url);
}

function handleSearchSubmit(): void {
    if (searchInput.value.trim()) {
        showResults.value = false;
        router.get(productsIndex({ query: { search: searchInput.value } }).url);
    }
}
</script>

<template>
    <Head>
        <link rel="preconnect" href="https://fonts.googleapis.com" />
        <link
            rel="preconnect"
            href="https://fonts.gstatic.com"
            crossorigin=""
        />
        <link
            href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:ital,wght@0,400;0,500;0,600;1,400;1,500&display=swap"
            rel="stylesheet"
        />
    </Head>

    <div
        class="min-h-screen"
        style="
            background-color: #f9f6f0;
            color: #1c1a17;
            font-family: 'Instrument Sans', sans-serif;
        "
    >
        <!-- Announcement bar -->
        <div
            class="py-2 text-center text-xs tracking-widest uppercase"
            style="background-color: #1c1a17; color: #f9f6f0"
        >
            Free shipping on orders over $200
        </div>

        <!-- Navbar -->
        <header
            class="sticky top-0 z-50 border-b"
            style="
                background-color: #f9f6f0;
                border-color: rgba(28, 26, 23, 0.12);
            "
        >
            <nav
                class="mx-auto flex max-w-7xl items-center justify-between px-6 py-4"
            >
                <!-- Logo -->
                <Link
                    href="/"
                    class="text-2xl font-semibold tracking-wide transition-opacity hover:opacity-70"
                    style="
                        font-family: 'Cormorant Garamond', serif;
                        color: #1c1a17;
                    "
                >
                    Minishop
                </Link>

                <!-- Desktop nav -->
                <div class="hidden items-center gap-8 md:flex">
                    <!-- Search Bar -->
                    <div ref="searchContainer" class="relative">
                        <div class="relative w-64">
                            <Search
                                class="absolute top-1/2 left-3 size-4 -translate-y-1/2 opacity-30"
                            />
                            <input
                                v-model="searchInput"
                                type="text"
                                placeholder="Search our collection..."
                                class="w-full rounded-full border bg-transparent py-2 pr-4 pl-10 text-xs transition-all focus:ring-1 focus:outline-none md:w-48 lg:w-64"
                                style="
                                    border-color: rgba(28, 26, 23, 0.15);
                                    ring-color: #1c1a17;
                                "
                                @focus="
                                    showResults =
                                        searchResults.length > 0 || isSearching
                                "
                                @keydown.enter="handleSearchSubmit"
                            />
                            <Loader2
                                v-if="isSearching"
                                class="absolute top-1/2 right-3 size-3 -translate-y-1/2 animate-spin opacity-40"
                            />
                        </div>

                        <!-- Search Results Dropdown -->
                        <Transition
                            enter-active-class="transition duration-200 ease-out"
                            enter-from-class="opacity-0 translate-y-1"
                            enter-to-class="opacity-100 translate-y-0"
                            leave-active-class="transition duration-150 ease-in"
                            leave-from-class="opacity-100 translate-y-0"
                            leave-to-class="opacity-0 translate-y-1"
                        >
                            <div
                                v-if="showResults"
                                class="absolute right-0 left-0 z-[60] mt-2 max-h-[400px] overflow-hidden rounded-xl border shadow-xl"
                                style="
                                    background-color: #f9f6f0;
                                    border-color: rgba(28, 26, 23, 0.1);
                                "
                            >
                                <div class="overflow-y-auto py-2">
                                    <template v-if="searchResults.length > 0">
                                        <div
                                            v-for="product in searchResults"
                                            :key="product.id"
                                            class="flex cursor-pointer items-center gap-3 px-4 py-2 hover:bg-black/5"
                                            @click="navigateToProduct(product)"
                                        >
                                            <div
                                                class="size-10 flex-shrink-0 overflow-hidden rounded-lg"
                                                style="
                                                    background: linear-gradient(
                                                        135deg,
                                                        #e8dfd4,
                                                        #d4c8b8
                                                    );
                                                "
                                            >
                                                <img
                                                    v-if="product.images?.[0]"
                                                    :src="
                                                        product.images[0].url
                                                    "
                                                    class="h-full w-full object-cover"
                                                />
                                            </div>
                                            <div
                                                class="flex flex-1 flex-col overflow-hidden text-left"
                                            >
                                                <span
                                                    class="truncate text-xs font-medium"
                                                    >{{ product.name }}</span
                                                >
                                                <span
                                                    class="text-[10px] opacity-40"
                                                    >{{
                                                        formatPrice(
                                                            product.price,
                                                        )
                                                    }}</span
                                                >
                                            </div>
                                        </div>
                                        <div
                                            class="border-t px-4 py-2"
                                            style="
                                                border-color: rgba(
                                                    28,
                                                    26,
                                                    23,
                                                    0.05
                                                );
                                            "
                                        >
                                            <button
                                                class="flex w-full items-center justify-between text-[10px] font-semibold tracking-widest uppercase opacity-60 hover:opacity-100"
                                                @click="handleSearchSubmit"
                                            >
                                                View all results
                                                <ArrowRight class="size-3" />
                                            </button>
                                        </div>
                                    </template>
                                    <div
                                        v-else-if="!isSearching"
                                        class="px-4 py-6 text-center"
                                    >
                                        <p class="text-xs opacity-40">
                                            No products found for "{{
                                                searchInput
                                            }}"
                                        </p>
                                    </div>
                                </div>
                            </div>
                        </Transition>
                    </div>

                    <Link
                        :href="productsIndex().url"
                        class="text-sm font-medium tracking-wide transition-colors hover:opacity-60"
                        style="color: #1c1a17"
                    >
                        All Products
                    </Link>
                    <Link
                        v-for="category in categories"
                        :key="category.id"
                        :href="
                            productsIndex({
                                query: { category: category.slug },
                            }).url
                        "
                        class="text-sm font-medium tracking-wide transition-colors hover:opacity-60"
                        style="color: #1c1a17"
                    >
                        {{ category.name }}
                    </Link>
                </div>

                <!-- Right actions -->
                <div class="flex items-center gap-4">
                    <button class="relative" @click="openDrawer">
                        <ShoppingBag class="size-5" style="color: #1c1a17" />
                        <span
                            v-if="itemCount > 0"
                            class="absolute -top-2 -right-2 flex size-4 items-center justify-center rounded-full text-[10px] font-semibold text-white transition-transform duration-300"
                            style="background-color: #c05c3a"
                        >
                            {{ itemCount > 9 ? '9+' : itemCount }}
                        </span>
                    </button>

                    <!-- Mobile menu toggle -->
                    <button
                        class="md:hidden"
                        style="color: #1c1a17"
                        @click="mobileMenuOpen = !mobileMenuOpen"
                    >
                        <X v-if="mobileMenuOpen" class="size-5" />
                        <Menu v-else class="size-5" />
                    </button>
                </div>
            </nav>

            <!-- Mobile menu -->
            <div
                v-if="mobileMenuOpen"
                class="border-t px-6 py-4 md:hidden"
                style="border-color: rgba(28, 26, 23, 0.12)"
            >
                <div class="flex flex-col gap-4">
                    <Link
                        :href="productsIndex().url"
                        class="text-sm font-medium"
                        style="color: #1c1a17"
                        @click="mobileMenuOpen = false"
                    >
                        All Products
                    </Link>
                    <Link
                        v-for="category in categories"
                        :key="category.id"
                        :href="
                            productsIndex({
                                query: { category: category.slug },
                            }).url
                        "
                        class="text-sm font-medium"
                        style="color: #1c1a17"
                        @click="mobileMenuOpen = false"
                    >
                        {{ category.name }}
                    </Link>
                </div>
            </div>
        </header>

        <!-- Page content -->
        <main>
            <slot />
        </main>

        <!-- Cart Drawer Component -->
        <CartDrawer :is-open="isDrawerOpen" @close="closeDrawer" />

        <!-- Support Chat Widget -->
        <ChatWidget />

        <!-- Footer -->
        <footer
            class="mt-24 border-t px-6 py-16"
            style="border-color: rgba(28, 26, 23, 0.12)"
        >
            <div class="mx-auto max-w-7xl">
                <div class="mb-10 grid grid-cols-1 gap-8 md:grid-cols-3">
                    <div>
                        <h3
                            class="mb-4 text-xl font-semibold"
                            style="font-family: 'Cormorant Garamond', serif"
                        >
                            Minishop
                        </h3>
                        <p class="text-sm leading-relaxed opacity-60">
                            A curated shop for the minimalist lifestyle.
                            Focusing on quality, longevity, and thoughtful
                            design.
                        </p>
                    </div>
                    <div>
                        <h4
                            class="mb-4 text-xs font-bold tracking-widest uppercase opacity-40"
                        >
                            Categories
                        </h4>
                        <div class="flex flex-col gap-2">
                            <Link
                                v-for="category in categories"
                                :key="category.id"
                                :href="
                                    productsIndex({
                                        query: { category: category.slug },
                                    }).url
                                "
                                class="text-sm transition-opacity hover:opacity-70"
                            >
                                {{ category.name }}
                            </Link>
                        </div>
                    </div>
                    <div>
                        <h4
                            class="mb-4 text-xs font-bold tracking-widest uppercase opacity-40"
                        >
                            Connect
                        </h4>
                        <div class="flex flex-col gap-2">
                            <a
                                href="#"
                                class="text-sm transition-opacity hover:opacity-70"
                                >Instagram</a
                            >
                            <a
                                href="#"
                                class="text-sm transition-opacity hover:opacity-70"
                                >Twitter</a
                            >
                            <a
                                href="#"
                                class="text-sm transition-opacity hover:opacity-70"
                                >Newsletter</a
                            >
                        </div>
                    </div>
                </div>
                <div
                    class="flex flex-col items-center justify-between border-t pt-8 md:flex-row"
                    style="border-color: rgba(28, 26, 23, 0.08)"
                >
                    <p
                        class="text-xs opacity-50"
                        style="color: rgba(28, 26, 23, 0.4)"
                    >
                        &copy; {{ new Date().getFullYear() }} Minishop. All
                        rights reserved.
                    </p>
                </div>
            </div>
        </footer>
    </div>
</template>
