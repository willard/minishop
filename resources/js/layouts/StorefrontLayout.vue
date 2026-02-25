<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { ShoppingBag, Menu, X } from 'lucide-vue-next';
import { ref } from 'vue';
import { useCart } from '@/composables/useCart';
import { index as productsIndex } from '@/actions/App/Http/Controllers/Storefront/ProductController';
import { create as checkoutCreate } from '@/actions/App/Http/Controllers/Storefront/CheckoutController';
import type { StorefrontCategory } from '@/types/storefront';

defineProps<{
    categories?: StorefrontCategory[];
}>();

const { itemCount } = useCart();
const mobileMenuOpen = ref(false);
</script>

<template>
    <Head>
        <link rel="preconnect" href="https://fonts.googleapis.com" />
        <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin="" />
        <link
            href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:ital,wght@0,400;0,500;0,600;1,400;1,500&display=swap"
            rel="stylesheet"
        />
    </Head>

    <div class="min-h-screen" style="background-color: #f9f6f0; color: #1c1a17; font-family: 'Instrument Sans', sans-serif">
        <!-- Announcement bar -->
        <div
            class="py-2 text-center text-xs tracking-widest uppercase"
            style="background-color: #1c1a17; color: #f9f6f0"
        >
            Free shipping on orders over ₱2,000
        </div>

        <!-- Navbar -->
        <header class="sticky top-0 z-50 border-b" style="background-color: #f9f6f0; border-color: rgba(28, 26, 23, 0.12)">
            <nav class="mx-auto flex max-w-7xl items-center justify-between px-6 py-4">
                <!-- Logo -->
                <Link
                    href="/"
                    class="text-2xl font-semibold tracking-wide transition-opacity hover:opacity-70"
                    style="font-family: 'Cormorant Garamond', serif; color: #1c1a17"
                >
                    Minishop
                </Link>

                <!-- Desktop nav -->
                <div class="hidden items-center gap-8 md:flex">
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
                        :href="productsIndex({ query: { category: category.slug } }).url"
                        class="text-sm font-medium tracking-wide transition-colors hover:opacity-60"
                        style="color: #1c1a17"
                    >
                        {{ category.name }}
                    </Link>
                </div>

                <!-- Right actions -->
                <div class="flex items-center gap-4">
                    <Link :href="checkoutCreate().url" class="relative">
                        <ShoppingBag class="size-5" style="color: #1c1a17" />
                        <span
                            v-if="itemCount > 0"
                            class="absolute -right-2 -top-2 flex size-4 items-center justify-center rounded-full text-[10px] font-semibold text-white"
                            style="background-color: #c05c3a"
                        >
                            {{ itemCount > 9 ? '9+' : itemCount }}
                        </span>
                    </Link>

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
            <div v-if="mobileMenuOpen" class="border-t px-6 py-4 md:hidden" style="border-color: rgba(28, 26, 23, 0.12)">
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
                        :href="productsIndex({ query: { category: category.slug } }).url"
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

        <!-- Footer -->
        <footer class="mt-24 border-t px-6 py-16" style="border-color: rgba(28, 26, 23, 0.12)">
            <div class="mx-auto max-w-7xl">
                <div class="mb-10 grid grid-cols-1 gap-8 md:grid-cols-3">
                    <div>
                        <p
                            class="mb-3 text-xl font-semibold"
                            style="font-family: 'Cormorant Garamond', serif; color: #1c1a17"
                        >
                            Minishop
                        </p>
                        <p class="text-sm leading-relaxed" style="color: rgba(28, 26, 23, 0.55)">
                            Thoughtfully curated products for everyday living.
                        </p>
                    </div>
                    <div>
                        <p class="mb-3 text-xs font-semibold uppercase tracking-widest" style="color: #1c1a17">
                            Shop
                        </p>
                        <div class="flex flex-col gap-2">
                            <Link
                                :href="productsIndex().url"
                                class="text-sm transition-opacity hover:opacity-60"
                                style="color: rgba(28, 26, 23, 0.7)"
                            >
                                All Products
                            </Link>
                            <Link
                                v-for="category in categories"
                                :key="category.id"
                                :href="productsIndex({ query: { category: category.slug } }).url"
                                class="text-sm transition-opacity hover:opacity-60"
                                style="color: rgba(28, 26, 23, 0.7)"
                            >
                                {{ category.name }}
                            </Link>
                        </div>
                    </div>
                    <div>
                        <p class="mb-3 text-xs font-semibold uppercase tracking-widest" style="color: #1c1a17">
                            Help
                        </p>
                        <div class="flex flex-col gap-2">
                            <span class="text-sm" style="color: rgba(28, 26, 23, 0.7)">Shipping & Returns</span>
                            <span class="text-sm" style="color: rgba(28, 26, 23, 0.7)">Contact Us</span>
                            <span class="text-sm" style="color: rgba(28, 26, 23, 0.7)">FAQ</span>
                        </div>
                    </div>
                </div>
                <div class="border-t pt-8" style="border-color: rgba(28, 26, 23, 0.12)">
                    <p class="text-center text-xs" style="color: rgba(28, 26, 23, 0.4)">
                        &copy; {{ new Date().getFullYear() }} Minishop. All rights reserved.
                    </p>
                </div>
            </div>
        </footer>
    </div>
</template>
