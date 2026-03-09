<script setup lang="ts">
import { Head, Link, usePage } from '@inertiajs/vue3';
import { logout } from '@/routes';
import { dashboard } from '@/routes/account';
import { index as ordersIndex } from '@/routes/account/orders';
import { edit as addressEdit } from '@/routes/account/address';
import { index as paymentIndex } from '@/routes/account/payment';
import type { Auth } from '@/types';

defineProps<{
    title?: string;
}>();

const page = usePage<{ auth: Auth }>();
const user = page.props.auth.user;

const navLinks: { label: string; href: string; exact?: boolean }[] = [
    { label: 'Overview', href: dashboard().url, exact: true },
    { label: 'Orders', href: ordersIndex().url },
    { label: 'Billing Address', href: addressEdit().url },
    { label: 'Payment Methods', href: paymentIndex().url },
];
</script>

<template>
    <div class="min-h-screen" style="background-color: #f9f6f0; font-family: 'Instrument Sans', sans-serif">
        <Head>
            <link rel="preconnect" href="https://fonts.googleapis.com" />
            <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin="" />
            <link
                href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:ital,wght@0,400;0,500;0,600;1,400;1,500&display=swap"
                rel="stylesheet"
            />
        </Head>

        <!-- Header -->
        <header class="border-b px-6 py-4" style="border-color: rgba(28, 26, 23, 0.1); background-color: #f9f6f0">
            <div class="mx-auto flex max-w-7xl items-center justify-between">
                <Link
                    href="/"
                    class="text-2xl font-semibold tracking-wide transition-opacity hover:opacity-70"
                    style="font-family: 'Cormorant Garamond', serif; color: #1c1a17"
                >
                    Minishop
                </Link>

                <div class="flex items-center gap-4">
                    <span class="hidden text-sm sm:block" style="color: rgba(28, 26, 23, 0.55)">{{ user.name }}</span>
                    <Link
                        v-bind="logout.form()"
                        as="button"
                        class="text-sm underline underline-offset-4 transition-opacity hover:opacity-60"
                        style="color: rgba(28, 26, 23, 0.6)"
                    >
                        Sign out
                    </Link>
                </div>
            </div>
        </header>

        <div class="mx-auto max-w-7xl px-6 py-10">
            <div class="flex flex-col gap-8 lg:flex-row lg:gap-12">
                <!-- Sidebar -->
                <aside class="lg:w-52 lg:shrink-0">
                    <p class="mb-4 text-xs font-semibold uppercase tracking-widest" style="color: rgba(28, 26, 23, 0.4)">
                        My Account
                    </p>
                    <nav class="flex flex-row flex-wrap gap-2 lg:flex-col lg:gap-1">
                        <Link
                            v-for="link in navLinks"
                            :key="link.href"
                            :href="link.href"
                            class="rounded-lg px-3 py-2 text-sm font-medium transition-colors"
                            :style="{
                                color: (link.exact ? $page.url === link.href : $page.url.startsWith(link.href)) ? '#1c1a17' : 'rgba(28, 26, 23, 0.55)',
                                backgroundColor: (link.exact ? $page.url === link.href : $page.url.startsWith(link.href)) ? 'rgba(28, 26, 23, 0.06)' : 'transparent',
                            }"
                        >
                            {{ link.label }}
                        </Link>
                    </nav>
                </aside>

                <!-- Content -->
                <main class="min-w-0 flex-1">
                    <h1
                        v-if="title"
                        class="mb-6 text-2xl font-semibold"
                        style="font-family: 'Cormorant Garamond', serif; color: #1c1a17"
                    >
                        {{ title }}
                    </h1>
                    <slot />
                </main>
            </div>
        </div>
    </div>
</template>
